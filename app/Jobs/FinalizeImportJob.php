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

            $duplicateCount = 0;
            $skippedRows    = [];
            $lastId         = 0;
            $chunkSize      = 500;

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
                
                // 1. Pre-fetch existing hashes for this chunk to avoid N+1 queries
                $chunkHashes = $rows->pluck('row_hash')->filter()->unique()->toArray();
                $existingHashesMap = DB::table('sr_assessment_row_hashes')
                    ->where('assessment_id', $assessment->id)
                    ->whereIn('row_hash', $chunkHashes)
                    ->get()
                    ->groupBy('sheet_id')
                    ->map(fn($g) => $g->pluck('row_hash', 'row_hash')->toArray())
                    ->toArray();

                $insertBatch = [];
                $updateBatch = [];
                $hashBatch   = [];

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

                    // 2. Fast-path: identical-row hash dedup (short-circuits the helper).
                    $sheetHashes = $existingHashesMap[$row->sheet_id] ?? [];
                    if (isset($sheetHashes[$row->row_hash])) {
                        $duplicateCount++;
                        continue;
                    }

                    $rowData = is_array($row->row_data) ? $row->row_data : json_decode($row->row_data, true);
                    $keysMap = $keysMaps[$row->sheet_id] ?? [];

                    // 2a. Lazy-load the existing-record signature map for this sheet (one query per sheet).
                    if (!isset($existingMapsBySheet[$row->sheet_id])) {
                        $mandatoryIdsBySheet[$row->sheet_id] = IngestionUpsertHelper::mandatoryKeyIds($keysMap);
                        $existingMapsBySheet[$row->sheet_id] = IngestionUpsertHelper::preloadExistingRecords(
                            $assessment->id,
                            $row->sheet_id,
                            $mandatoryIdsBySheet[$row->sheet_id]
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
                        ]
                    );

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

                    // 3. Mark hash as "seen" both in memory for this chunk and for DB persistence.
                    //    Covers insert AND update so an identical re-upload of the updated row fast-paths.
                    $existingHashesMap[$row->sheet_id][$row->row_hash] = $row->row_hash;
                    $hashBatch[] = [
                        'assessment_id' => $assessment->id,
                        'sheet_id'      => $row->sheet_id,
                        'row_hash'      => $row->row_hash,
                        'created_at'    => now()
                    ];
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

                if (!empty($hashBatch)) {
                    // Use insertOrIgnore in case of race conditions or overlapping chunks
                    DB::table('sr_assessment_row_hashes')->insertOrIgnore($hashBatch);
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
                unset($chunkHashes, $insertBatch, $updateBatch, $hashBatch, $rows);
                
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
                    'status'         => 'completed',
                    'imported_rows'  => $actualImportedDbCount,
                    'inserted_rows'  => $insertedCount,
                    'updated_rows'   => $updatedCount,
                    'skipped_rows'   => \Illuminate\Support\Facades\DB::raw("skipped_rows + $skippedCount"),
                    'duplicate_rows' => \Illuminate\Support\Facades\DB::raw("duplicate_rows + $duplicateCount"),
                ]);

            Log::emergency("FinalizeImportJob: BREADCRUMB 6 (Completion update done)");

            Log::info("FinalizeImportJob: Completed", [
                'assessment_id' => $assessment->id,
                'imported'      => $importedCount,
                'skipped'       => $skippedCount,
                'duplicates'    => $duplicateCount
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
