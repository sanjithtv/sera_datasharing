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
     * Load the target table for this (assessment, sheet), build a signature -> record map.
     *
     * Returns:
     *   [
     *     signature(string) => [
     *        'entry_counter' => int,
     *        'values'        => [template_key_id => string],
     *     ],
     *     ...
     *   ]
     * Plus the reserved key '__max_entry' with the highest entry_counter seen (int),
     * so callers can allocate new entry_counters for INSERT rows without re-querying.
     */
    public static function preloadExistingRecords(int $assessmentId, int $sheetId, array $mandatoryKeyIds): array
    {
        $rows = DB::table('sr_licensee_assessment_master_data')
            ->where('assessment_id', $assessmentId)
            ->where('template_sheet_id', $sheetId)
            ->select('entry_counter', 'template_key_id', 'template_key_value')
            ->get();

        $byEntry = [];
        $maxEntry = 0;
        foreach ($rows as $r) {
            $ec = (int) $r->entry_counter;
            if ($ec > $maxEntry) $maxEntry = $ec;
            // Trim on load so existing values are normalized for comparison against
            // the (also-trimmed) incoming values in classifyRow. This avoids spurious
            // UPDATEs when historical data has whitespace that the new upload doesn't.
            $byEntry[$ec][(int) $r->template_key_id] = trim((string) ($r->template_key_value ?? ''));
        }

        $map = [];
        foreach ($byEntry as $ec => $values) {
            $sig = self::buildSignature($values, $mandatoryKeyIds);
            if ($sig === null) continue; // record has no mandatory values stored — skip indexing
            // If two existing records collide on the same signature, keep the lowest entry_counter
            // (deterministic; the duplicate pre-existed this feature).
            if (!isset($map[$sig]) || $map[$sig]['entry_counter'] > $ec) {
                $map[$sig] = ['entry_counter' => $ec, 'values' => $values];
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
     * @param array $context    ['assessment_id','licensee_id','sheet_id','entry_counter']
     *                          entry_counter is the row_index from the incoming file;
     *                          it is used ONLY when action=insert and the signature is new.
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

        $sig = self::buildSignature($incomingByKeyId, $mandatoryIds);
        if ($sig === null) {
            return ['action' => 'skip-no-key'];
        }

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
                    'assessment_id'      => $context['assessment_id'],
                    'template_sheet_id'  => $context['sheet_id'],
                    'template_key_id'    => (int) $tk['id'],
                    'template_key_value' => is_scalar($value) ? (string) $value : '',
                    'type'               => $tk['type'] ?? null,
                    'entry_counter'      => $newEntry,
                ];
            }

            // Register so later rows in this chunk with the same signature are no-ops.
            $existingMap[$sig] = [
                'entry_counter' => $newEntry,
                'values'        => $incomingByKeyId,
            ];

            return ['action' => 'insert', 'rows' => $rows];
        }

        // Signature matched → compare non-key values (and key values too, for safety).
        $existing = $existingMap[$sig];
        $updates  = [];
        foreach ($incomingByKeyId as $kid => $newVal) {
            $oldVal = $existing['values'][$kid] ?? '';
            if ((string) $oldVal !== (string) $newVal) {
                $updates[] = [
                    'template_key_id' => (int) $kid,
                    'value'           => (string) $newVal,
                    'entry_counter'   => (int) $existing['entry_counter'],
                    'sheet_id'        => (int) $context['sheet_id'],
                ];
                $existingMap[$sig]['values'][$kid] = (string) $newVal;
            }
        }

        if (empty($updates)) {
            return ['action' => 'noop'];
        }
        return ['action' => 'update', 'updates' => $updates];
    }

    /**
     * Apply a batch of updates produced by classifyRow. One statement per cell,
     * wrapped in a transaction. Returns the number of statements issued.
     */
    public static function applyUpdates(int $assessmentId, int $sheetId, array $updates): int
    {
        if (empty($updates)) return 0;

        $count = 0;
        DB::transaction(function () use ($assessmentId, $sheetId, $updates, &$count) {
            foreach ($updates as $u) {
                DB::table('sr_licensee_assessment_master_data')
                    ->where('assessment_id', $assessmentId)
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
