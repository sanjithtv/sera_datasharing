<?php

namespace App\Jobs;

use App\Imports\StreamingTemplateImport;
use App\Models\Assessment;
use App\Traits\ImportProcessorTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLargeFileImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ImportProcessorTrait;

    /**
     * Allow up to 2 hours for very large files or complex Excel formula resolution.
     */
    public int $timeout = 7200;

    /**
     * Only try once — re-trying a partial import would corrupt data.
     */
    public int $tries = 1;

    public function __construct(
        public int    $assessmentId,
        public string $filePath,           // absolute path to the stored temp file
        public array  $sheetMapping,
        public int    $licenseeId,
        public int    $licenseeTemplateId,
        public bool   $isExcel = false,
    ) {}

    public function handle(): void
    {
        Log::info('ProcessLargeFileImport: job started', [
            'assessment_id' => $this->assessmentId,
            'file_path'     => $this->filePath,
            'is_excel'      => $this->isExcel,
        ]);

        try {
            // By bypassing PhpSpreadsheet entirely and giving it directly to StreamingTemplateImport, 
            // OpenSpout natively streams the XML fast and instantly reads the MS Excel cached calculations.
            
            // For CSV, use shell wc -l for fast row counting
            if (!$this->isExcel) {
                $lineCount = shell_exec("wc -l < " . escapeshellarg($this->filePath));
                $totalRows = (int)trim($lineCount);
                if ($totalRows > 0) {
                    \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                        ->where('id', $this->assessmentId)
                        ->update(['total_rows' => $totalRows - 1]);
                }
            }

            // Start the streaming import natively via OpenSpout
            $import = new StreamingTemplateImport(
                assessmentId:         $this->assessmentId,
                licenseeId:           $this->licenseeId,
                licenseeTemplateId:   $this->licenseeTemplateId,
                sheetMapping:         $this->sheetMapping,
                isCsv:                !$this->isExcel,
            );

            $import->import($this->filePath);

            // 3. Mark assessment as 'parsed'
            \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $this->assessmentId)
                ->update(['status' => 'parsed']);

            Log::info('ProcessLargeFileImport: job completed successfully', [
                'assessment_id' => $this->assessmentId,
            ]);

        } catch (\Throwable $e) {
            Log::error('ProcessLargeFileImport: job FAILED', [
                'assessment_id' => $this->assessmentId,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $this->assessmentId)
                ->update([
                    'status'           => 'failed',
                    'processing_error' => 'Import Error: ' . $e->getMessage()
                ]);

            // We do NOT re-throw $e here so queue runner doesn't exit.
        } finally {
            // Unset import object aggressively to prevent memory footprint issues
            $import = null;
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            // Clean up the main temp file
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }
    }
}
