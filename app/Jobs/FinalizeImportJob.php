<?php

namespace App\Jobs;

use App\Helpers\IngestionUpsertHelper;
use App\Models\Assessment;
use App\Models\AssessmentMasterData;
use App\Models\LicenseeTemplateKey;
use App\Models\SlaveMasterData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinalizeImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assessmentId;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 7200;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    public function __construct($assessmentId)
    {
        $this->assessmentId = $assessmentId;
    }

    public function handle()
    {
        Log::emergency("FinalizeImportJob: BREADCRUMB 1 (handle start)", ['id' => $this->assessmentId]);
        $assessment = Assessment::findOrFail($this->assessmentId);

        try {
            $totalRows = SlaveMasterData::where('assessment_id', $assessment->id)->count();
            Log::emergency("FinalizeImportJob: BREADCRUMB 2 (count done)", ['total' => $totalRows]);
            $importedCount = 0;
            $skippedCount = 0;
            $insertedCount = 0;
            $updatedCount = 0;

            // ✅ Pre-fetch ALL keys for this template
            $allKeys = LicenseeTemplateKey::where('licensee_template_id', $assessment->licensee_template_id)
                ->get()
                ->groupBy('sheet_id');

            $keysMaps = [];
            foreach ($allKeys as $sheetId => $sheetKeys) {
                $keysMaps[$sheetId] = $sheetKeys->keyBy('short_code')->toArray();
            }

            Log::info("FinalizeImportJob: Starting optimized background import", [
                'assessment_id' => $assessment->id,
                'total_rows' => $totalRows
            ]);

            // Sync total_rows to assessment for accurate Step 2 progress bar
            $beforeUpdate = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $assessment->id)
                ->value('total_rows');

            $updated = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $assessment->id)
                ->update(['total_rows' => $totalRows]);
            
            $afterUpdate = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $assessment->id)
                ->value('total_rows');

            Log::emergency("FinalizeImportJob: BREADCRUMB 3 (total_rows synced)", [
                'assessment_id' => $assessment->id,
                'total_rows'    => $totalRows,
                'before'        => $beforeUpdate,
                'after'         => $afterUpdate,
                'db_updated'    => $updated
            ]);

            $duplicateCount       = 0;
            $crossUpdateCount     = 0;
            $skippedRows          = [];
            $crossUpdateDetails   = []; // [{ row_index, target_assessment_id, target_entry_counter, sheet_id, was_changed }]
            $lastId               = 0;
            $chunkSize            = 500;

            // Upsert: cached signature maps per sheet. Lazily populated on first sighting of each sheet.
            $existingMapsBySheet = [];
            $mandatoryIdsBySheet = [];

            while (true) {
                DB::beginTransaction();

                $rows = SlaveMasterData::where('assessment_id', $assessment->id)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit($chunkSize)
                    ->get();

                if ($rows->isEmpty()) {
                    DB::commit();
                    break;
                }

                $lastId = $rows->last()->id;

                // NOTE: The hash fast-path (sr_assessment_row_hashes) was REMOVED.
                // It was a performance optimization that short-circuited classifyRow
                // when an identical-byte row was seen before, but it produced false
                // duplicates whenever master_data changed without the hash table being
                // updated to match — e.g. after a same-assessment update that revert
                // a row to a previously-uploaded value (its old hash matches and the
                // new upload is wrongly skipped). The signature-based dedup below
                // (preloadExistingRecords + classifyRow) reads the *current* master_data
                // every time, so it always reaches the correct decision.

                $insertBatch = [];
                $updateBatch = [];

                foreach ($rows as $row) {
                    $rowErrors = is_array($row->validation_errors)
                        ? $row->validation_errors
                        : json_decode($row->validation_errors, true);

                    if (!empty($rowErrors)) {
                        $skippedRows[] = [
                            'row_index' => $row->row_index,
                            'errors'    => $rowErrors,
                        ];
                        $skippedCount++;
                        continue;
                    }

                    $rowData = is_array($row->row_data) ? $row->row_data : json_decode($row->row_data, true);
                    $keysMap = $keysMaps[$row->sheet_id] ?? [];

                    // 2a. Lazy-load the existing-record signature map for this sheet (one query per sheet).
                    //     Scoped to the whole template so cross-assessment duplicates surface.
                    if (!isset($existingMapsBySheet[$row->sheet_id])) {
                        $mandatoryIdsBySheet[$row->sheet_id] = IngestionUpsertHelper::mandatoryKeyIds($keysMap);
                        $existingMapsBySheet[$row->sheet_id] = IngestionUpsertHelper::preloadExistingRecords(
                            $assessment->id,
                            $row->sheet_id,
                            $mandatoryIdsBySheet[$row->sheet_id],
                            (int) $assessment->licensee_template_id
                        );
                    }

                    $decision = IngestionUpsertHelper::classifyRow(
                        $rowData,
                        $keysMap,
                        $existingMapsBySheet[$row->sheet_id],
                        [
                            'assessment_id' => $row->assessment_id,
                            'licensee_id'   => $row->licensee_id,
                            'sheet_id'      => $row->sheet_id,
                            'entry_counter' => $row->row_index,
                            's_no'          => $row->s_no ?? null,
                        ]
                    );

                    // Build a compact log record describing this row's classification.
                    // Helps diagnose "data not updating" by showing which path each row
                    // took (insert / update / cross-update / cross-noop / noop / skip).
                    $changedFields = [];
                    if (in_array($decision['action'], ['update', 'cross-update'], true)) {
                        foreach ($decision['updates'] ?? [] as $u) {
                            $changedFields[] = $u['template_key_id'] . '=>' . substr((string) ($u['value'] ?? ''), 0, 40);
                        }
                    }
                    Log::info('FinalizeImportJob: row decision', [
                        'assessment_id'         => $assessment->id,
                        'sheet_id'              => $row->sheet_id,
                        'row_index'             => $row->row_index,
                        's_no'                  => $row->s_no ?? null,
                        'row_hash'              => $row->row_hash,
                        'action'                => $decision['action'],
                        'owner_assessment_id'   => $decision['owner_assessment_id'] ?? null,
                        'owner_entry_counter'   => $decision['owner_entry_counter'] ?? null,
                        'changed_field_count'   => count($changedFields),
                        'changed_fields_sample' => array_slice($changedFields, 0, 5),
                    ]);

                    switch ($decision['action']) {
                        case 'insert':
                            foreach ($decision['rows'] as $cell) {
                                $insertBatch[] = $cell;
                            }
                            $importedCount++;
                            $insertedCount++;
                            break;
                        case 'update':
                            foreach ($decision['updates'] as $u) {
                                $updateBatch[] = $u;
                            }
                            $importedCount++; // treat update as re-imported so imported_rows stays accurate
                            $updatedCount++;
                            break;
                        case 'cross-update':
                            // Row matches an existing record in a DIFFERENT assessment
                            // under the same template. Apply the update against the
                            // original assessment's row and surface a warning.
                            foreach ($decision['updates'] as $u) {
                                $updateBatch[] = $u; // each $u carries its own assessment_id
                            }
                            $crossUpdateCount++;
                            $crossUpdateDetails[] = [
                                'row_index'             => $row->row_index,
                                'sheet_id'              => $row->sheet_id,
                                'target_assessment_id'  => $decision['owner_assessment_id'] ?? null,
                                'target_entry_counter'  => $decision['owner_entry_counter'] ?? null,
                                'was_changed'           => true,
                            ];
                            break;
                        case 'cross-noop':
                            // Identical row already exists in another assessment under
                            // the same template — silently count as a duplicate.
                            $duplicateCount++;
                            break;
                        case 'noop':
                            $duplicateCount++;
                            break;
                        case 'skip-no-key':
                            $skippedRows[] = [
                                'row_index' => $row->row_index,
                                'errors'    => ['_upsert' => ['Missing mandatory key value(s) — cannot upsert.']],
                            ];
                            $skippedCount++;
                            continue 2;
                    }

                    // (Hash fast-path removed — see note above. We deliberately do
                    //  not write to sr_assessment_row_hashes anymore; stale entries
                    //  there were the source of the false-duplicate bug.)
                }

                if (!empty($insertBatch)) {
                    foreach (array_chunk($insertBatch, 5000) as $chunk) {
                        AssessmentMasterData::insert($chunk);
                    }
                }

                if (!empty($updateBatch)) {
                    $updatesBySheet = [];
                    foreach ($updateBatch as $u) {
                        $updatesBySheet[(int) $u['sheet_id']][] = $u;
                    }
                    foreach ($updatesBySheet as $sid => $us) {
                        IngestionUpsertHelper::applyUpdates($assessment->id, $sid, $us);
                    }
                }

                DB::commit();

                // Update progress
                $updated = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                    ->where('id', $assessment->id)
                    ->update(['finalized_rows' => $importedCount + $skippedCount + $duplicateCount]);
                
                Log::emergency("FinalizeImportJob: BREADCRUMB 4 (Progress update)", [
                    'assessment_id' => $assessment->id,
                    'finalized'     => $importedCount + $skippedCount + $duplicateCount,
                    'duplicates'    => $duplicateCount,
                    'db_updated'    => $updated
                ]);

                // Destroy variables generated in this chunk to prevent memory fragmentation
                unset($insertBatch, $updateBatch, $rows);
                
                // Force memory garbage collection every 500 iterations to avoid OS SIGKILL (OOM)
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            Log::emergency("FinalizeImportJob: BREADCRUMB 5 (Loop finished)");

            // Error file persistence
            if (!empty($skippedRows)) {
                Storage::put(
                    "imports/errors/assessment_{$assessment->id}_errors.json",
                    json_encode($skippedRows)
                );
            } else {
                Storage::delete("imports/errors/assessment_{$assessment->id}_errors.json");
            }

            // Cross-template update warning details (for the post-import summary).
            if (!empty($crossUpdateDetails)) {
                Storage::put(
                    "imports/cross_updates/assessment_{$assessment->id}.json",
                    json_encode($crossUpdateDetails)
                );
            } else {
                Storage::delete("imports/cross_updates/assessment_{$assessment->id}.json");
            }

            // Cleanup staging data
            SlaveMasterData::where('assessment_id', $assessment->id)->delete();

            // Calculate the TRUE total imported rows currently in the database for this assessment
            $actualImportedDbCount = \Illuminate\Support\Facades\DB::table('sr_licensee_assessment_master_data')
                ->where('assessment_id', $assessment->id)
                ->select('template_sheet_id', 'entry_counter')
                ->distinct()
                ->get()
                ->count();

            // Update assessment status
            \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $assessment->id)
                ->update([
                    'status'                 => 'completed',
                    'imported_rows'          => $actualImportedDbCount,
                    'inserted_rows'          => $insertedCount,
                    'updated_rows'           => $updatedCount,
                    'cross_template_updates' => $crossUpdateCount,
                    'skipped_rows'           => \Illuminate\Support\Facades\DB::raw("skipped_rows + $skippedCount"),
                    'duplicate_rows'         => \Illuminate\Support\Facades\DB::raw("duplicate_rows + $duplicateCount"),
                ]);

            Log::emergency("FinalizeImportJob: BREADCRUMB 6 (Completion update done)");

            Log::info("FinalizeImportJob: Completed", [
                'assessment_id'          => $assessment->id,
                'imported'               => $importedCount,
                'skipped'                => $skippedCount,
                'duplicates'             => $duplicateCount,
                'cross_template_updates' => $crossUpdateCount,
            ]);

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            Log::error('FinalizeImportJob failed', [
                'assessment_id' => $this->assessmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $this->assessmentId)
                ->update([
                    'status'           => 'failed',
                    'processing_error' => 'Finalization Error: ' . $e->getMessage()
                ]);

            // We do NOT re-throw $e here. This prevents the worker process from 
            // crashing/exiting due to an unhandled exception.
        }
    }
}
