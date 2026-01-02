<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MultiSheetImport;
use App\Models\Assessment;
use App\Models\TemplateSheet; // mapping table model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAssessmentInitialImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $assessmentId;
    public $filePath;

    public $timeout = 3600; // job-level timeout (seconds)
    public $tries = 3;

    public function __construct(int $assessmentId, string $filePath)
    {
        $this->assessmentId = $assessmentId;
        $this->filePath = $filePath;
    }

    public function handle()
    {
        // increase memory/time in job (worker contexts)
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        DB::connection()->disableQueryLog();

        $assessment = Assessment::find($this->assessmentId);
        if (!$assessment) {
            Log::error("ProcessAssessmentImport: assessment {$this->assessmentId} not found");
            return;
        }

        // Build sheet -> template mapping from DB (template_sheets table)
        // TemplateSheet model should have columns: licensee_template_id, licensee_id, sheet_name, template_id (or template_key_id)
        $mapping = TemplateSheet::where('template_id', $assessment->licensee_template_id)
            ->pluck('template_id', 'sheet_name') // ['SheetTitle' => template_id]
            ->toArray();

        // full path to file
        $fullPath = storage_path('app/' . $this->filePath);

        // Run import using Maatwebsite Excel with multiple sheets
        // the MultiSheetImport will build per-sheet SingleSheetImport instances
        Excel::import(new MultiSheetImport($assessment->id, $assessment->licensee_id, $mapping), $fullPath, null, \Maatwebsite\Excel\Excel::XLSX);

        // optionally mark assessment as 'processed_for_preview'
        $assessment->update(['status' => 'processing_preview']);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('ProcessAssessmentImport failed: ' . $exception->getMessage());
        // Optionally update assessment status to failed
        $assessment = Assessment::find($this->assessmentId);
        if ($assessment) {
            $assessment->update(['status' => 'failed']);
        }
    }
}
