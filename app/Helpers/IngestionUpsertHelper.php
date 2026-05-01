<?php
// app/Helpers/IngestionUpsertHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class IngestionUpsertHelper
{
    const SIG_SEP = "\x1f";

    /**
     * Return the template_key_id list for columns flagged mandatory=1, sorted asc.
     * keysMap shape: [short_code => ['id'=>..,'type'=>..,'mandatory'=>..], ...]
     */
    public static function mandatoryKeyIds(array $keysMap): array
    {
        $ids = [];
        foreach ($keysMap as $key) {
            if (!empty($key['mandatory']) && (int) $key['mandatory'] === 1) {
                $ids[] = (int) $key['id'];
            }
        }
        sort($ids);
        return $ids;
    }

    /**
     * Map short_code => template_key_id (used for pivot inside classifyRow).
     */
    public static function shortCodeToIdMap(array $keysMap): array
    {
        $out = [];
        foreach ($keysMap as $shortCode => $key) {
            $out[$shortCode] = (int) $key['id'];
        }
        return $out;
    }

    /**
     * Load existing records for the same template (across ALL assessments that share
     * this licensee_template_id) for one sheet, build a signature -> record map.
     *
     * Returns:
     *   [
     *     signature(string) => [
     *        'assessment_id' => int,   // owner of the matched record
     *        'entry_counter' => int,
     *        'values'        => [template_key_id => string],
     *     ],
     *     ...
     *   ]
     * Plus the reserved key '__max_entry' with the highest entry_counter seen FOR
     * THE CURRENT ASSESSMENT (int), so callers can allocate new entry_counters for
     * INSERT rows without re-querying. Cross-assessment entries are excluded from
     * __max_entry so the current assessment's counter remains independent.
     */
    public static function preloadExistingRecords(int $assessmentId, int $sheetId, array $mandatoryKeyIds, ?int $licenseeTemplateId = null): array
    {
        $query = DB::table('sr_licensee_assessment_master_data as md')
            ->where('md.template_sheet_id', $sheetId);

        if ($licenseeTemplateId !== null) {
            // Template-wide scope: any assessment under the same template.
            $query->join('sr_licensee_assessments as a', 'a.id', '=', 'md.assessment_id')
                  ->where('a.licensee_template_id', $licenseeTemplateId);
        } else {
            // Backwards compatible single-assessment scope.
            $query->where('md.assessment_id', $assessmentId);
        }

        $rows = $query->select('md.assessment_id', 'md.entry_counter', 'md.template_key_id', 'md.template_key_value', 'md.s_no')
            ->get();

        $byEntry  = [];   // [assessment_id][entry_counter] => ['s_no'=>?, 'values'=>[key_id=>value]]
        $maxEntry = 0;
        foreach ($rows as $r) {
            $aid = (int) $r->assessment_id;
            $ec  = (int) $r->entry_counter;
            // Track max only for the current assessment (so new inserts in this
            // assessment use a fresh entry_counter independent of other assessments).
            if ($aid === $assessmentId && $ec > $maxEntry) $maxEntry = $ec;
            if (!isset($byEntry[$aid][$ec])) {
                $byEntry[$aid][$ec] = ['s_no' => null, 'values' => []];
            }
            $byEntry[$aid][$ec]['values'][(int) $r->template_key_id] = trim((string) ($r->template_key_value ?? ''));
            // s_no is denormalized (same value for every cell of the row) — keep
            // the first non-null we see.
            if ($byEntry[$aid][$ec]['s_no'] === null && $r->s_no !== null && $r->s_no !== '') {
                $byEntry[$aid][$ec]['s_no'] = (string) $r->s_no;
            }
        }

        $map = [];
        foreach ($byEntry as $aid => $entries) {
            foreach ($entries as $ec => $info) {
                $values = $info['values'];
                $sNo    = $info['s_no'];

                // Prefer S.No as the canonical signature (matches the client's
                // unique-row contract). Fall back to mandatory-key signature for
                // sheets where no S.No was captured.
                $sig = ($sNo !== null && $sNo !== '')
                    ? 'SNO:' . $sNo
                    : self::buildSignature($values, $mandatoryKeyIds);

                if ($sig === null) continue;

                // Conflict resolution rule: prefer the EARLIEST owner.
                //   1) lowest assessment_id wins (the "original" assessment)
                //   2) within an assessment, lowest entry_counter wins
                if (isset($map[$sig])) {
                    $existingAid = $map[$sig]['assessment_id'];
                    $existingEc  = $map[$sig]['entry_counter'];
                    if ($aid > $existingAid) continue;
                    if ($aid === $existingAid && $ec >= $existingEc) continue;
                }
                $map[$sig] = [
                    'assessment_id' => $aid,
                    'entry_counter' => $ec,
                    'values'        => $values,
                    's_no'          => $sNo,
                ];
            }
        }

        $map['__max_entry'] = $maxEntry;
        return $map;
    }

    /**
     * Build a stable signature from a [template_key_id => value] map restricted to
     * the mandatory key ids (already sorted asc). Returns null if every mandatory
     * value is missing/empty (so the caller can route to skip-no-key).
     */
    public static function buildSignature(array $valuesByKeyId, array $mandatoryKeyIds): ?string
    {
        if (empty($mandatoryKeyIds)) {
            // No mandatory columns defined for this sheet → fall back to all keys present,
            // sorted by key_id, so the record still has a signature.
            $allIds = array_map('intval', array_keys($valuesByKeyId));
            sort($allIds);
            $mandatoryKeyIds = $allIds;
        }

        $parts = [];
        $hasAny = false;
        foreach ($mandatoryKeyIds as $kid) {
            $v = $valuesByKeyId[$kid] ?? '';
            $v = is_scalar($v) ? (string) $v : '';
            $v = trim($v);
            if ($v !== '') $hasAny = true;
            $parts[] = $v;
        }
        if (!$hasAny) return null;
        return implode(self::SIG_SEP, $parts);
    }

    /**
     * Decide what to do with one incoming staged row.
     *
     * @param array $rowData    short_code => value (from SlaveMasterData.row_data)
     * @param array $keysMap    short_code => template_key row (id, type, mandatory)
     * @param array $existingMap  signature => ['entry_counter','values'=>[key_id=>value]]
     *                           Must be passed by reference: newly classified INSERTs are
     *                           registered so later rows in the same chunk see them.
     * @param array $context    ['assessment_id','licensee_id','sheet_id','entry_counter','s_no'?]
     *                          entry_counter is the row_index from the incoming file;
     *                          it is used ONLY when action=insert and the signature is new.
     *                          s_no (when present) is the canonical row identifier
     *                          used for cross-template duplicate detection.
     */
    public static function classifyRow(array $rowData, array $keysMap, array &$existingMap, array $context): array
    {
        $mandatoryIds   = self::mandatoryKeyIds($keysMap);
        $shortCodeToId  = self::shortCodeToIdMap($keysMap);

        // Build incoming [template_key_id => value] for every known column.
        // Trim each value so whitespace-only differences on mandatory-key cells
        // don't fire spurious UPDATEs when signatures match (buildSignature trims
        // too — keep these two in lockstep).
        $incomingByKeyId = [];
        foreach ($rowData as $col => $value) {
            if (!isset($shortCodeToId[$col])) continue; // column not in template — ignored
            $v = is_scalar($value) ? (string) $value : '';
            $incomingByKeyId[$shortCodeToId[$col]] = trim($v);
        }

        // Prefer S.No as the signature (matches the client's "unique row =
        // unique S.No" contract). Fall back to mandatory-key signature for
        // sheets without an S.No column.
        $incomingSno = isset($context['s_no']) && $context['s_no'] !== '' ? (string) $context['s_no'] : null;
        $sig = $incomingSno !== null
            ? 'SNO:' . $incomingSno
            : self::buildSignature($incomingByKeyId, $mandatoryIds);

        if ($sig === null) {
            return ['action' => 'skip-no-key'];
        }

        $currentAssessmentId = (int) $context['assessment_id'];

        // New record → INSERT with a fresh entry_counter.
        if (!isset($existingMap[$sig])) {
            $maxEntry = (int) ($existingMap['__max_entry'] ?? 0);
            $newEntry = max($maxEntry + 1, (int) $context['entry_counter']);
            // Ensure monotonic growth if input row_index is lower than existing max
            $existingMap['__max_entry'] = $newEntry;

            $rows = [];
            foreach ($rowData as $col => $value) {
                $tk = $keysMap[$col] ?? null;
                if (!$tk) continue;
                $rows[] = [
                    'licensee_id'        => $context['licensee_id'],
                    'assessment_id'      => $currentAssessmentId,
                    'template_sheet_id'  => $context['sheet_id'],
                    'template_key_id'    => (int) $tk['id'],
                    'template_key_value' => is_scalar($value) ? (string) $value : '',
                    'type'               => $tk['type'] ?? null,
                    'entry_counter'      => $newEntry,
                    's_no'               => $incomingSno,
                ];
            }

            // Register so later rows in this chunk with the same signature are no-ops.
            $existingMap[$sig] = [
                'assessment_id' => $currentAssessmentId,
                'entry_counter' => $newEntry,
                'values'        => $incomingByKeyId,
                's_no'          => $incomingSno,
            ];

            return ['action' => 'insert', 'rows' => $rows];
        }

        // Signature matched → compare non-key values (and key values too, for safety).
        $existing      = $existingMap[$sig];
        $ownerAssessId = (int) ($existing['assessment_id'] ?? $currentAssessmentId);
        $isCross       = $ownerAssessId !== $currentAssessmentId;
        $updates       = [];
        foreach ($incomingByKeyId as $kid => $newVal) {
            $oldVal = $existing['values'][$kid] ?? '';
            if ((string) $oldVal !== (string) $newVal) {
                $updates[] = [
                    'template_key_id' => (int) $kid,
                    'value'           => (string) $newVal,
                    'entry_counter'   => (int) $existing['entry_counter'],
                    'sheet_id'        => (int) $context['sheet_id'],
                    'assessment_id'   => $ownerAssessId,
                ];
                $existingMap[$sig]['values'][$kid] = (string) $newVal;
            }
        }

        if (empty($updates)) {
            return $isCross
                ? ['action' => 'cross-noop', 'owner_assessment_id' => $ownerAssessId, 'owner_entry_counter' => (int) $existing['entry_counter']]
                : ['action' => 'noop'];
        }
        return [
            'action'              => $isCross ? 'cross-update' : 'update',
            'updates'             => $updates,
            'owner_assessment_id' => $ownerAssessId,
            'owner_entry_counter' => (int) $existing['entry_counter'],
        ];
    }

    /**
     * Apply a batch of updates produced by classifyRow. One statement per cell,
     * wrapped in a transaction. Returns the number of statements issued.
     *
     * Each $update may carry its own 'assessment_id' (set when the matched record
     * lives in a different assessment under the same template). Falls back to
     * $assessmentId when not provided.
     */
    public static function applyUpdates(int $assessmentId, int $sheetId, array $updates): int
    {
        if (empty($updates)) return 0;

        $count = 0;
        DB::transaction(function () use ($assessmentId, $sheetId, $updates, &$count) {
            foreach ($updates as $u) {
                $targetAssessment = (int) ($u['assessment_id'] ?? $assessmentId);
                DB::table('sr_licensee_assessment_master_data')
                    ->where('assessment_id', $targetAssessment)
                    ->where('template_sheet_id', $sheetId)
                    ->where('entry_counter', (int) $u['entry_counter'])
                    ->where('template_key_id', (int) $u['template_key_id'])
                    ->update(['template_key_value' => $u['value']]);
                $count++;
            }
        });
        return $count;
    }
}
