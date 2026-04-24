<?php

namespace App\Http\Controllers;

use App\Helpers\IngestionUpsertHelper;
use App\Jobs\ProcessAssessmentImport;
use App\Jobs\ProcessAssessmentInitialImport;
use App\Models\Assessment;
use App\Models\Licensee;
use App\Models\LicenseeTemplate;
use App\Models\LicenseeTemplateKey;
use App\Models\TemplateSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SlaveMasterData;
use Illuminate\Queue\SerializesModels;
use App\Models\AssessmentMasterData;
use App\Models\LicenseeTemplateSheet;

use Illuminate\Support\Facades\DB;


use App\Imports\DynamicTemplateImport;
use App\Imports\StreamingTemplateImport;
use Illuminate\Support\Facades\Storage;

use App\Exports\AssessmentMasterMultiSheetExport;
use App\Exports\AssessmentExport;
use App\Exports\CsvErrorExport;
use App\Jobs\ProcessLargeFileImport;
use App\Traits\ImportProcessorTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;



class AssessmentController extends Controller
{
    use ImportProcessorTrait;

     public function __construct()
    {
        // Role/permission protection
        $this->middleware(['permission:read-assessment'])->only('index');
        $this->middleware(['permission:create-assessment','permission:manual_entry-assessment','permission:upload_excel-assessment'])->only(['create', 'store']);
        $this->middleware(['permission:edit-assessment','permission:manual_entry-assessment','permission:upload_excel-assessment'])->only(['edit', 'update']);
        $this->middleware(['permission:delete-assessment'])->only('archive');
        $this->middleware(['permission:upload_excel-assessment'])->only('showUploadForm');
        $this->middleware(['permission:manual_entry-assessment'])->only('showManualForm');

    }

    /**
     * Display a listing of the assessments sorted by created date.
     */
    public function index(Request $request)
    {
        // Optional: Add filters, pagination, or search later
        $assessments = Assessment::with(['licensee', 'licenseeTemplate.subfolder'])
        ->orderByDesc('created_at')
        ->paginate(20);

        // Return as JSON for API or pass to Blade view
        if ($request->wantsJson()) {
            return response()->json($assessments);
        }

        // For Blade view
        return view('modules.assessments.index', compact('assessments'));
    }

    /**
     * STEP 0 — Create assessment upload form
     */
    public function create()
    {
        $licensees = Licensee::select('id', 'name_en')->get();
        //$templates = LicenseeTemplate::select('id', 'licensee_id','subfolder_id','version')->get();
        $templates = LicenseeTemplate::with(['licensee', 'subfolder'])
            ->where('status','active')
            ->get()
            ->sortBy(function ($t) {
        return $t->licensee->name_en;
    })
            ->map(function ($t) {
                $t->display_name = "{$t->licensee->name_en} - {$t->subfolder->name_en} - v{$t->version}";
                return $t;
            });

        return view('modules.assessments.create', compact('licensees', 'templates'));
    }

    public function show(Assessment $assessment)
{
    // Load template + keys + relations
    $assessment->load(['licensee', 'template.subfolder', 'template.keys']);

    $template = $assessment->template;

    // Load sheets + template keys + assessment master data
    $sheets = $template->sheets()->with('keys')->get();


    // Group master data by sheet - LIMIT to first 100 rows per sheet
    $masterDataMap = [];
    foreach ($sheets as $sheet) {
        $entryCounters = AssessmentMasterData::where('assessment_id', $assessment->id)
            ->where('template_sheet_id', $sheet->id)
            ->distinct()
            ->orderBy('entry_counter')
            ->limit(100)
            ->pluck('entry_counter');

        if ($entryCounters->isEmpty()) {
            $masterDataMap[$sheet->id] = collect();
            continue;
        }

        $sheetData = AssessmentMasterData::where('assessment_id', $assessment->id)
            ->where('template_sheet_id', $sheet->id)
            ->whereIn('entry_counter', $entryCounters)
            ->get();

        $masterDataMap[$sheet->id] = $sheetData->groupBy('entry_counter')
            ->map(function ($rowGroup) {
                $mapped = [];
                foreach ($rowGroup as $item) {
                    $mapped[$item->template_key_id] = $item->template_key_value;
                }
                return $mapped;
            });
    }
    $masterData = collect($masterDataMap);
    // ✅ OPTIMIZED: Get sheet count efficiently
    $sheetIds = $sheets->count();
    //print_r($masterData);exit;
    return view('modules.assessments.show', compact(
        'assessment',
        'sheets',
        'masterData',
        'sheetIds'
    ));
}

    /**
     * STEP 1 — Upload file, create assessment, validate + cache
     */

/*    public function upload(Request $request)
{
    $validated = $request->validate([
        'licensee_id' => 'required|integer|exists:sr_licensees,id',
        'licensee_template_id' => 'required|integer|exists:sr_licensee_templates,id',
        'assessment_date' => 'required|date',
        'status' => 'required|string',
        'file' => 'required|file|mimes:xlsx,csv',
    ]);

    // Create the assessment record
    $assessment = Assessment::create([
        'licensee_id' => $validated['licensee_id'],
        'licensee_template_id' => $validated['licensee_template_id'],
        'assessment_date' => $validated['assessment_date'],
        'status' => $validated['status'],
    ]);

    //Parse Excel
    $rows = Excel::toArray([], $request->file('file'))[0];
    $headers = array_map('trim', $rows[0]);
    $dataRows = array_slice($rows, 1);

    //Validate Excel rows
    [$validationErrors, $canProceed] = $this->validateExcelData($assessment, $headers, $dataRows);

    //Persist data into slave_master_data table
    $records = [];
    foreach ($dataRows as $index => $row) {
        $records[] = [
            'assessment_id' => $assessment->id,
            'licensee_id' => $validated['licensee_id'],
            'headers' => json_encode($headers),
            'row_data' => json_encode($row),
            'validation_errors' => json_encode($validationErrors[$index] ?? []),
            'row_index' => $index + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Optionally batch insert every 500 rows for large files
        if (($index + 1) % 500 === 0) {
            SlaveMasterData::insert($records);
            $records = [];
        }
    }
    if (!empty($records)) {
        SlaveMasterData::insert($records);
    }

    // Return preview (first 50 rows from DB)
    $previewRows = SlaveMasterData::where('assessment_id', $assessment->id)
        ->orderBy('row_index')
        ->limit(50)
        ->get();

    return view('modules.assessments.preview', [
        'headers' => $headers,
        'dataRows' => $previewRows,
        'assessment' => $assessment,
        'validationErrors' => $validationErrors,
        'canProceed' => $canProceed,
    ]);
}*/

public function upload(Request $request)
{
    \Illuminate\Support\Facades\Log::info('Assessment upload started', [
        'assessment_id' => $request->assessment_id ?? 'N/A',
        'has_file' => $request->hasFile('file'),
        'file_size' => $request->hasFile('file') ? $request->file('file')->getSize() : 'N/A',
        'mime_type' => $request->hasFile('file') ? $request->file('file')->getMimeType() : 'N/A',
        'client_extension' => $request->hasFile('file') ? $request->file('file')->getClientOriginalExtension() : 'N/A',
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ]);

    $validated = $request->validate([
        'licensee_id'          => 'required|integer|exists:sr_licensees,id',
        'licensee_template_id' => 'required|integer|exists:sr_licensee_templates,id',
        'assessment_date'      => 'required|date',
        'status'               => 'required|string',
        'file'                 => [
            'required', 'file', 'mimes:xlsx,csv,txt',
            function ($attribute, $value, $fail) {
                $ext    = strtolower($value->getClientOriginalExtension());
                $sizeMb = $value->getSize() / 1024 / 1024;
                if (in_array($ext, ['xlsx', 'xls']) && $sizeMb > 20) {
                    $fail('Excel files must be 20 MB or smaller. Your file is ' . round($sizeMb, 1) . ' MB.');
                }
                if (in_array($ext, ['csv', 'txt']) && $sizeMb > 2048) {
                    $fail('CSV files must be 2 GB or smaller. Your file is ' . round($sizeMb, 1) . ' MB.');
                }
            },
        ],
        'assessment_id'        => 'required|integer|exists:sr_licensee_assessments,id',
    ]);


    $startTime = microtime(true);
    $resolvedFilePath = null;

    try{

    $assessment = Assessment::find($request->assessment_id);

    // ── CLEAN SLATE: discard any previous staged data and reset all counters.
    // This is critical when the user re-uploads a file while a previous import
    // is still staged or mid-processing — without this the polling JS sees a
    // stale "processing" status and spins forever.
    SlaveMasterData::where('assessment_id', $assessment->id)->delete();

    \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
        ->where('id', $assessment->id)
        ->update([
            'status'         => 'draft',
            'total_rows'     => 0,
            'processed_rows' => 0,
            'finalized_rows' => 0,
            'imported_rows'  => 0,
            'skipped_rows'   => 0,
            'duplicate_rows' => 0,
        ]);

    // Reload so subsequent code sees the fresh state
    $assessment->refresh();

    Log::info('Upload reset: cleared staged data and counters for re-upload', [
        'assessment_id' => $assessment->id,
    ]);

    /** ------------------------------------------------------------------
     * 1. Determine sheet → template mapping for this template
     * ------------------------------------------------------------------*/
    // Fetch all active sheets for this template
    $allSheets = \App\Models\LicenseeTemplateSheet::where('template_id', $validated['licensee_template_id'])
        ->where('status', 1)
        ->orderByDesc('id')
        ->get();

    $sheetMapping = [];
    foreach ($allSheets->groupBy('sheet_name') as $sheetName => $sheets) {
        if ($sheets->count() === 1) {
            $sheetMapping[$sheetName] = $sheets->first()->id;
        } else {
            // Duplicate sheet names found. Find the one that actually has keys configured.
            $selectedSheet = $sheets->first(function ($sheet) {
                return \App\Models\LicenseeTemplateKey::where('sheet_id', $sheet->id)->exists();
            });
            
            // Fallback to the latest one if neither (or both) have keys
            $sheetMapping[$sheetName] = $selectedSheet ? $selectedSheet->id : $sheets->first()->id;
        }
    }

    $extension = strtolower($request->file('file')->getClientOriginalExtension());
    $isBypassExtension = in_array($extension, ['csv', 'txt']);

    \Illuminate\Support\Facades\Log::info('=== UPLOAD: Sheet Mapping from DB ===', [
        'licensee_template_id' => $validated['licensee_template_id'],
        'db_sheet_mapping'     => $sheetMapping,
        'extension'            => $extension,
        'is_csv_bypass'        => $isBypassExtension,
    ]);

    // For XLSX: log actual sheet names inside the file
    if (!$isBypassExtension) {
        try {
            $sniffReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($request->file('file')->getRealPath());
            $sniffReader->setReadDataOnly(true);
            $sniffReader->setLoadSheetsOnly([]); // Load nothing, just get names
            $sniffSpreadsheet = $sniffReader->load($request->file('file')->getRealPath());
            $actualSheetNames = $sniffSpreadsheet->getSheetNames();
            $sniffSpreadsheet->disconnectWorksheets();
            unset($sniffSpreadsheet);

            \Illuminate\Support\Facades\Log::info('=== UPLOAD: Actual sheet tabs inside Excel file ===', [
                'actual_sheet_names' => $actualSheetNames,
                'mapped_names'       => array_keys($sheetMapping),
                'unmapped_sheets'    => array_values(array_diff($actualSheetNames, array_keys($sheetMapping))),
                'missing_from_file'  => array_values(array_diff(array_keys($sheetMapping), $actualSheetNames)),
            ]);
        } catch (\Exception $sniffEx) {
            \Illuminate\Support\Facades\Log::warning('Could not sniff sheet names from Excel file', ['error' => $sniffEx->getMessage()]);
        }
    }

    // ✅ Fallback for CSV: If no mapping found, pick the first available sheet for this template
    if ($isBypassExtension && empty($sheetMapping)) {
        $firstSheet = \App\Models\LicenseeTemplateSheet::where('template_id', $validated['licensee_template_id'])->first();
        if ($firstSheet) {
            $sheetMapping['CSV'] = $firstSheet->id;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Async threshold: files above this size are dispatched to a queue
    // job instead of being processed synchronously in the HTTP request.
    // Excel (XLSX) is prone to OOM, so we use a lower threshold (5MB).
    // ──────────────────────────────────────────────────────────────────────
    $csvAsyncThresholdMb   = 10;
    $excelAsyncThresholdMb = 0; // Send all Excel files to the background queue

    $fileSizeMb  = $request->file('file')->getSize() / 1024 / 1024;
    $isExcel = in_array($extension, ['xlsx', 'xls']);

    if (($isBypassExtension && $fileSizeMb > $csvAsyncThresholdMb) || ($isExcel && $fileSizeMb > $excelAsyncThresholdMb)) {
        // ── ASYNC PATH: store the file and dispatch a background job ──
        Log::info('Large file detected — dispatching background job', [
            'assessment_id' => $assessment->id,
            'extension'     => $extension,
            'file_size_mb'  => round($fileSizeMb, 2),
        ]);

        $folder = $isBypassExtension ? 'imports/csv' : 'imports/excel';
        $storedRelPath = $request->file('file')->store($folder, 'local');
        $storedAbsPath = storage_path('app/' . $storedRelPath);

        $assessment->update(['status' => 'processing']);
        
        ProcessLargeFileImport::dispatch(
            $assessment->id,
            $storedAbsPath,
            $sheetMapping,
            $assessment->licensee_id,
            $assessment->licensee_template_id,
            !$isBypassExtension // isExcel
        );

        session_write_close();
        return view('modules.assessments.processing', compact('assessment'));
    }

    // ── SYNC PATH (small CSV or small Excel) ──
    
    session_write_close();

    if ($isBypassExtension) {
        Log::info('resolveExcelFormulas BYPASSED for ' . strtoupper($extension) . '. Passing file directly to importer.');
        $resolvedFilePath = $request->file('file');
    } else {
        Log::info('Start resolveExcelFormulas (Sync)', ['time_since_start' => microtime(true) - $startTime]);
        // Resolve formulas synchronously for small files (< 5MB)
        $resolvedData     = $this->resolveExcelFormulas($request->file('file')->getRealPath(), $sheetMapping, $assessment->id);
        $resolvedFilePath = $resolvedData['path'];
        $maxDataRows      = $resolvedData['maxDataRows'] ?? [];
        Log::info('Finished resolveExcelFormulas (Sync)', ['time_since_start' => microtime(true) - $startTime]);
        
        $totalMappedRows = 0;
        foreach ($sheetMapping as $sheetName => $config) {
            $totalMappedRows += max(0, ($maxDataRows[$sheetName] ?? 1) - 1);
        }
        $assessment->update(['total_rows' => $totalMappedRows]);
    }

    Log::info('Init Import Engine', [
        'is_streaming' => true,
        'extension'    => $extension,
    ]);

    $import = new StreamingTemplateImport(
        $assessment->id,
        $assessment->licensee_id,
        $assessment->licensee_template_id,
        $sheetMapping,
        $isBypassExtension
    );

    Log::info('Start Streaming Import', ['time_since_start' => microtime(true) - $startTime]);
    $import->import($resolvedFilePath);
    Log::info('Finished Streaming Import', [
        'time_since_start' => microtime(true) - $startTime,
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
    ]);

    /** ------------------------------------------------------------------
     * 3. Fetch preview — errors-first, up to 100 rows per sheet
     * ------------------------------------------------------------------*/
    \Illuminate\Support\Facades\Log::info('Fetching preview rows (per-sheet, errors-first)', ['time_since_start' => microtime(true) - $startTime]);

    $previewRowsCollection = collect();

    foreach ($import->namePerSheet as $sheetName => $sheetId) {
        $limit = 100;

        // First: fetch rows that HAVE errors (status='pending')
        $errorRows = SlaveMasterData::where('assessment_id', $assessment->id)
            ->where('sheet_id', $sheetId)
            ->where('status', 'pending')
            ->orderBy('row_index')
            ->limit($limit)
            ->get();

        $remaining = $limit - $errorRows->count();

        // Then: fill remaining slots with clean rows
        $cleanRows = collect();
        if ($remaining > 0) {
            $cleanRows = SlaveMasterData::where('assessment_id', $assessment->id)
                ->where('sheet_id', $sheetId)
                ->where('status', '!=', 'pending')
                ->orderBy('row_index')
                ->limit($remaining)
                ->get();
        }

        $sheetPreview = $errorRows->merge($cleanRows);
        $previewRowsCollection = $previewRowsCollection->merge($sheetPreview);
    }

    $previewRows = $previewRowsCollection;

    \Illuminate\Support\Facades\Log::info('Preview rows fetched', [
        'total_rows' => $previewRows->count(),
        'per_sheet'  => $import->namePerSheet,
        'time_since_start' => microtime(true) - $startTime,
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
    ]);

    // Calculate total error count per sheet and total valid rows
    $totalErrorsPerSheet = SlaveMasterData::where('assessment_id', $assessment->id)
        ->where('status', 'pending')
        ->select('sheet_id', \DB::raw('count(*) as count'))
        ->groupBy('sheet_id')
        ->pluck('count', 'sheet_id')
        ->toArray();

    $totalValidCount = SlaveMasterData::where('assessment_id', $assessment->id)
        ->where('status', 'processed')
        ->count();

    $totalErrorCount = array_sum($totalErrorsPerSheet);

    return view('modules.assessments.preview_new', [
        'assessment'          => $assessment,
        'previewRows'         => $previewRows,
        'canProceed'          => $import->canProceed,
        'errorsPerSheet'      => $import->errorsPerSheet,
        'namePerSheet'        => $import->namePerSheet,
        'totalErrorsPerSheet' => $totalErrorsPerSheet,
        'totalValidCount'     => $totalValidCount,
        'totalErrorCount'     => $totalErrorCount
    ]);
    }catch(\Exception $e){
        return back()->with('error', 'Import failed: ' . $e->getMessage());
    } finally {
        // Clean up the resolved Excel temp file — it's only needed during import
        if (isset($resolvedFilePath) && is_string($resolvedFilePath) && file_exists($resolvedFilePath)) {
            @unlink($resolvedFilePath);
            Log::info('Temp Excel file deleted in finally block', ['path' => $resolvedFilePath]);
        }
    }
}

public function importData(Request $request, Assessment $assessment)
{
    try {
        $assessment = Assessment::findOrFail($request->assessment_id);

        // Update status to 'committing' (final background move)
        $assessment->update(['status' => 'committing']);

        // Dispatch background finalize job
        \App\Jobs\FinalizeImportJob::dispatch($assessment->id);

        // Success message is already queued in session by update() or manual,
        // but we close it here to be safe before redirect.
        session_write_close();

        // Redirect to the processing view so the user sees the progress bar and
        // the ingestion-summary modal when FinalizeImportJob finishes.
        return view('modules.assessments.processing', compact('assessment'));

    } catch (\Exception $e) {
        Log::error('Final import dispatch failed', ['error' => $e->getMessage()]);
        return redirect()->back()->with('error', 'Failed to start import: ' . $e->getMessage());
    }
}

/**
 * Download an Error report for rows that were skipped during importData().
 * The report contains: row number (in the original file), column name, and error message.
 * Now streams as high-performance CSV to prevent server out-of-memory crashes on massive files.
 */
public function downloadErrors(Assessment $assessment)
{
    $errorPath = "imports/errors/assessment_{$assessment->id}_errors.json";

    if (!Storage::exists($errorPath)) {
        return back()->with('error', 'No error report found for this assessment. Either there were no errors, or the report has expired.');
    }

    $fileName = "assessment_{$assessment->id}_import_errors.csv";

    return response()->streamDownload(function () use ($errorPath) {
        $skippedRows = json_decode(Storage::get($errorPath), true) ?? [];
        
        // Release session lock immediately to allow other requests
        session_write_close();

        $handle = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility (especially for Arabic content)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add Headers
        fputcsv($handle, ['Row Number (in original file)', 'Column', 'Error Reason']);

        // Stream Write Data
        foreach ($skippedRows as $item) {
            $errors = $item['errors'] ?? [];

            if (empty($errors)) {
                fputcsv($handle, [
                    $item['row_index'] ?? 'Unknown',
                    '—',
                    'Row contained validation errors (no detail available)'
                ]);
                continue;
            }

            foreach ($errors as $column => $messages) {
                foreach ((array) $messages as $message) {
                    fputcsv($handle, [
                        $item['row_index'] ?? 'Unknown',
                        $column,
                        $message
                    ]);
                }
            }
        }

        fclose($handle);
    }, $fileName, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ]);
}

/**
 * Step 1 Validation Error Report (CSV)
 *
 * Generates a CSV with:
 *  Section A — Summary: total errored rows, number of distinct error reasons
 *  Section B — Reason Breakdown: each unique reason and how many rows have it
 *  Section C — Row Detail: one row per errored row with all column values + errors
 */
public function downloadStep1Errors(Assessment $assessment)
{
    // 1. Initial check: are there any errors at all? (Low cost exists)
    $hasErrors = \App\Models\SlaveMasterData::where('assessment_id', $assessment->id)
        ->where('status', 'pending')
        ->exists();

    if (!$hasErrors) {
        return back()->with('info', 'No validation errors found for this assessment.');
    }

    $filename = "assessment_{$assessment->id}_step1_errors.csv";

    return response()->streamDownload(function () use ($assessment) {
        // Release session lock immediately to allow other requests (polling/navigation)
        session_write_close();

        // Increase execution time for massive downloads
        @set_time_limit(900); 

        $output = fopen('php://output', 'w');
        
        // Fetch sheet names for mapping
        $sheetNames = \App\Models\LicenseeTemplateSheet::where('template_id', $assessment->licensee_template_id)
            ->pluck('sheet_name', 'id')
            ->toArray();

        // Use a temporary file to capture detail rows while we calculate
        // the summary in memory from a SINGLE database scan.
        $tempFile = tmpfile(); 

        $reasonCounts = [];
        $erroredCount = 0;
        $columnHeaders = null;
        $sentinelMap = [
            '__INVALID_DATE__'   => 'Invalid Date',
            '__INVALID_NUMBER__' => 'Invalid Number',
            '__INVALID_TIME__'   => 'Invalid Time',
        ];

        // Single Pass Scan - Chunking ensures strict O(1) memory usage by dropping PDO buffers
        \App\Models\SlaveMasterData::where('assessment_id', $assessment->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->chunkById(5000, function ($chunk) use (&$erroredCount, &$columnHeaders, &$reasonCounts, &$tempFile, $sentinelMap, $sheetNames) {
                foreach ($chunk as $row) {
                    $erroredCount++;

                    // Single JSON decode per row
                    $rowData = is_array($row->row_data) ? $row->row_data : json_decode($row->row_data, true);
                    $validationErrors = is_array($row->validation_errors) ? $row->validation_errors : json_decode($row->validation_errors, true);

                    if ($columnHeaders === null && $rowData) {
                        $columnHeaders = array_keys($rowData);
                    }

                    // A. Aggregate Summary (Memory)
                    if ($validationErrors) {
                        foreach ($validationErrors as $col => $messages) {
                            foreach ((array) $messages as $msg) {
                                $key = "{$col}: {$msg}";
                                $reasonCounts[$key] = ($reasonCounts[$key] ?? 0) + 1;
                            }
                        }
                    }

                    // B. Write Detail Row to Temp File
                    $values = [];
                    if ($columnHeaders) {
                        foreach ($columnHeaders as $col) {
                            $val = $rowData[$col] ?? '';
                            $values[] = $sentinelMap[$val] ?? $val;
                        }
                    }

                    $errorStrings = [];
                    if ($validationErrors) {
                        foreach ($validationErrors as $col => $messages) {
                            foreach ((array) $messages as $msg) {
                                $errorStrings[] = "{$col}: {$msg}";
                            }
                        }
                    }

                    $sheetName = $sheetNames[$row->sheet_id] ?? "Sheet #{$row->sheet_id}";
                    fputcsv($tempFile, array_merge([$row->row_index, $row->sheet_id, $sheetName], $values, [implode(' | ', $errorStrings)]));
                }
            });

        // --- OUTPUT SECTION A: Summary ---
        arsort($reasonCounts);
        fputcsv($output, ['=== STEP 1 VALIDATION ERROR REPORT ===']);
        fputcsv($output, ['Assessment ID', $assessment->id]);
        fputcsv($output, ['Generated At', now()->toDateTimeString()]);
        fputcsv($output, []);
        fputcsv($output, ['Total Errored Rows',  $erroredCount]);
        fputcsv($output, ['Distinct Error Reasons', count($reasonCounts)]);
        fputcsv($output, []);

        // --- OUTPUT SECTION B: Breakdown ---
        fputcsv($output, ['=== ERROR REASON BREAKDOWN ===']);
        fputcsv($output, ['Error Reason', 'Number of Rows']);
        foreach ($reasonCounts as $reason => $count) {
            fputcsv($output, [$reason, $count]);
        }
        fputcsv($output, []);

        // --- OUTPUT SECTION C: Details (Drain Temp File) ---
        fputcsv($output, ['=== DETAILED ERROR ROWS ===']);
        if ($columnHeaders) {
            fputcsv($output, array_merge(['Row #', 'Sheet ID', 'Sheet Name'], $columnHeaders, ['Validation Errors']));
        }

        // High-performance stream copy
        rewind($tempFile);
        stream_copy_to_stream($tempFile, $output);

        fclose($tempFile);
        fclose($output);

    }, $filename, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
}

/**
 * Show a preview of staged (slave_master_data) rows for an assessment
 * that was parsed via the background job, so the user can confirm import.
 * Reuses the same preview_new.blade.php as the synchronous upload path.
 */
public function reviewParsed(Assessment $assessment)
{
    if (!in_array($assessment->status, ['parsed', 'processing_preview'])) {
        return redirect()->route('assessments.show', $assessment->id)
            ->with('error', 'This assessment does not have staged data to review.');
    }

    $startTime = microtime(true);

    // Release session lock so parallel uploads or navigation 
    // are not blocked by the database queries below.
    session_write_close();

    // Build the sheet mapping
    $allSheets = \App\Models\LicenseeTemplateSheet::where('template_id', $assessment->licensee_template_id)
        ->where('status', 1)
        ->orderByDesc('id')
        ->get();

    $sheetMapping = [];
    foreach ($allSheets->groupBy('sheet_name') as $sheetName => $sheets) {
        if ($sheets->count() === 1) {
            $sheetMapping[$sheetName] = $sheets->first()->id;
        } else {
            $selectedSheet = $sheets->first(function ($sheet) {
                return \App\Models\LicenseeTemplateKey::where('sheet_id', $sheet->id)->exists();
            });
            $sheetMapping[$sheetName] = $selectedSheet ? $selectedSheet->id : $sheets->first()->id;
        }
    }

    $previewRowsCollection = collect();
    $errorsPerSheet = [];
    $namePerSheet = [];

    foreach ($sheetMapping as $sheetName => $sheetId) {
        $namePerSheet[$sheetName] = $sheetId;
        $errorsPerSheet[$sheetName] = [];

        $limit = 100;
        
        // Use the new idx_review_preview index
        $errorRows = SlaveMasterData::where('assessment_id', $assessment->id)
            ->where('sheet_id', $sheetId)
            ->where('status', 'pending')
            ->orderBy('row_index')
            ->limit($limit)
            ->get();

        $errorCount = $errorRows->count();
        $remaining = $limit - $errorCount;
        $cleanRows = collect();

        if ($remaining > 0) {
            $cleanRows = SlaveMasterData::where('assessment_id', $assessment->id)
                ->where('sheet_id', $sheetId)
                ->where('status', '!=', 'pending')
                ->orderBy('row_index')
                ->limit($remaining)
                ->get();
        }

        // Optimized error mapping
        if ($errorCount > 0) {
            foreach ($errorRows as $row) {
                $errs = is_array($row->validation_errors)
                    ? $row->validation_errors
                    : json_decode($row->validation_errors, true);
                if (!empty($errs)) {
                    if (isset($errs['__missing_sheet'])) {
                        $errorsPerSheet[$sheetName][] = [
                            'type'    => 'missing_sheet',
                            'message' => is_array($errs['__missing_sheet']) ? $errs['__missing_sheet'][0] : $errs['__missing_sheet'],
                        ];
                    } elseif (isset($errs['__empty_sheet'])) {
                        $emptyErrorData = is_array($errs['__empty_sheet']) && isset($errs['__empty_sheet']['message']) 
                            ? $errs['__empty_sheet'] 
                            : ['message' => is_array($errs['__empty_sheet']) ? $errs['__empty_sheet'][0] : $errs['__empty_sheet']];
                            
                        $errorsPerSheet[$sheetName][] = [
                            'type'    => 'empty_sheet',
                            'message' => $emptyErrorData['message'],
                            'missing_columns' => $emptyErrorData['missing_columns'] ?? []
                        ];
                    } elseif (isset($errs['__header_error'])) {
                        $errorsPerSheet[$sheetName][] = [
                            'type'    => 'header_validation',
                            'message' => is_array($errs['__header_error']) ? implode(' ', $errs['__header_error']) : (string)$errs['__header_error'],
                            'errors'  => $errs['__header_error']
                        ];
                    } else {
                        // Standard row validation errors
                        $errorsPerSheet[$sheetName][$row->row_index] = $errs;
                    }
                }
            }
        }

        // Merge directly to collection
        $previewRowsCollection = $previewRowsCollection->concat($errorRows)->concat($cleanRows);
    }

    $hasErrors = !empty(array_filter($errorsPerSheet));
    $canProceed = !$hasErrors;

    // Calculate total error count per sheet and total valid rows
    $totalErrorsPerSheet = SlaveMasterData::where('assessment_id', $assessment->id)
        ->where('status', 'pending')
        ->select('sheet_id', \DB::raw('count(*) as count'))
        ->groupBy('sheet_id')
        ->pluck('count', 'sheet_id')
        ->toArray();

    $totalValidCount = SlaveMasterData::where('assessment_id', $assessment->id)
        ->where('status', 'processed')
        ->count();

    $totalErrorCount = array_sum($totalErrorsPerSheet);

    Log::info('reviewParsed execution finished', [
        'assessment_id' => $assessment->id,
        'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
        'rows_previewed' => $previewRowsCollection->count()
    ]);

    return view('modules.assessments.preview_new', [
        'assessment'          => $assessment,
        'previewRows'         => $previewRowsCollection,
        'canProceed'          => $canProceed,
        'errorsPerSheet'      => $errorsPerSheet,
        'namePerSheet'        => $namePerSheet,
        'totalErrorsPerSheet' => $totalErrorsPerSheet,
        'totalValidCount'     => $totalValidCount,
        'totalErrorCount'     => $totalErrorCount
    ]);
}

    /**
     * STEP 2 — Async import job trigger
     */
    /*public function commitMasterData()
    {
        $session = Session::get('assessment_upload');
        if (!$session || empty($session['assessment_id'])) {
            return back()->withErrors(['msg' => 'Session expired. Please re-upload the file.']);
        }
        //echo "OUT";exit;
        // Dispatch async job for large file import
        ProcessAssessmentImport::dispatch($session);

        // Clear session immediately to avoid re-processing
        Session::forget('assessment_upload');

        return redirect()->route('assessments.index')
            ->with('success', 'Import started in background. You will be notified once it’s complete.');
    }*/
    public function commitMasterData(Request $request)
{
    $assessmentId = $request->input('assessment_id');

    $assessment = Assessment::findOrFail($assessmentId);

    ProcessAssessmentImport::dispatch($assessment->id, $assessment->licensee_id);

    return redirect()->route('assessments.index')
        ->with('success', 'Import started in background. You will be notified once it’s complete.');
}


    /**
     * STEP 3 — Validate Excel headers & data
     */
    private function validateExcelData($assessment, $headers, $dataRows)
{
    $template = \App\Models\LicenseeTemplate::with('keys')->find($assessment->licensee_template_id);
    $templateKeys = $template->keys->keyBy(fn($k) => trim($k->short_code));

    $missing = array_diff(array_keys($templateKeys->toArray()), $headers);
    $extra = array_diff($headers, array_keys($templateKeys->toArray()));

    $validationErrors = []; // structured errors
    $hasError = false;

    // Header mismatch (non-row-based)
    if ($missing || $extra) {
        $validationErrors['headers'] = [
            'missing' => $missing,
            'extra' => $extra
        ];
        $hasError = true;
    }

    // Validate up to 500 rows for performance
    $sampleRows = array_slice($dataRows, 0, 500);

    foreach ($sampleRows as $rowIndex => $row) {
        foreach ($headers as $colIndex => $header) {
            $key = $templateKeys[$header] ?? null;
            $value = trim($row[$colIndex] ?? '');

            if (!$key) continue;

            // Mandatory field
            if ($key->mandatory && $value === '') {
                $validationErrors["{$rowIndex}_{$colIndex}"] = "'{$header}' is mandatory.";
                $hasError = true;
                continue;
            }

            // Type-based checks
            if ($value !== '') {
                switch ($key->type) {
                    case 'number':
                        if (!is_numeric($value)) {
                            $validationErrors["{$rowIndex}_{$colIndex}"] = "'{$header}' should be numeric.";
                            $hasError = true;
                        }
                        break;
                    case 'number_percentage':
                         if (!preg_match('/^\s*\d+(\.\d+)?\s*%?\s*$/', $value)) {
                            $validationErrors["{$rowIndex}_{$colIndex}"] = "'{$header}' invalid percentage.";
                            $hasError = true;
                        }
                        break;
                    case 'text':
                        if (strlen($value) > 255) {
                            $validationErrors["{$rowIndex}_{$colIndex}"] = "'{$header}' exceeds 255 chars.";
                            $hasError = true;
                        }
                        break;
                }
            }
        }
    }

    return [$validationErrors, !$hasError];
}


//Manual Entry
/**
     * STEP 1 — Create assessment once, then redirect to chosen path
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'licensee_id' => 'required|integer',
            'licensee_template_id' => 'required|integer|exists:sr_licensee_templates,id',
            'assessment_date' => 'required|date',
            'status' => 'required|string',
            'entry_mode' => 'required|in:manual,excel',
        ]);

        $assessment = Assessment::create([
            'licensee_id' => $validated['licensee_id'],
            'licensee_template_id' => $validated['licensee_template_id'],
            'assessment_date' => $validated['assessment_date'],
            'status' => $validated['status'],
        ]);

        // Redirect based on mode
        return $validated['entry_mode'] === 'excel'
            ? redirect()->route('assessments.upload.form', $assessment->id)
            : redirect()->route('assessments.form', $assessment->id);
    }

    /**
     * STEP 2A — Show upload form (Excel)
     */
    public function showUploadForm(Assessment $assessment)
    {
        $template = LicenseeTemplate::with([
        'sheets.keys' // IMPORTANT
        ])->findOrFail($assessment->licensee_template_id);
        return view('modules.assessments.upload', compact('assessment','template'));
    }

    /**
     * STEP 2B — Manual form
     */
    public function showManualForm(Assessment $assessment)
    {
        //$template = $assessment->licenseeTemplate()->with('keys')->first();
        $template = LicenseeTemplate::with([
        'sheets.keys' // IMPORTANT
        ])->findOrFail($assessment->licensee_template_id);
        return view('modules.assessments.manual_form', [
            'assessment' => $assessment,
            'template' => $template,
            'keys' => $template->keys,
        ]);
    }
    /**
     * STEP 3 — Handle manual form submission
     */
    public function submitManualForm(Request $request, Assessment $assessment)
    {
        $template = $assessment->licenseeTemplate()->with('keys')->first();

        $rules = [];
        foreach ($template->keys as $key) {
            if($key->mandatory==3){
                $rules[$key->short_code] = 'nullable';
            }else{
                $rules[$key->short_code] = $key->mandatory ? 'required' : 'nullable';
            }

        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $template, $assessment) {
            foreach ($template->keys as $key) {
                if (isset($validated[$key->short_code])) {
                    AssessmentMasterData::create([
                        'licensee_id' => $assessment->licensee_id,
                        'assessment_id' => $assessment->id,
                        'template_key_id' => $key->id,
                        'template_key_value' => $validated[$key->short_code],
                        'type' => $key->type,
                        'entry_counter' => 1,
                    ]);
                }
            }
        });

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Manual assessment data saved successfully!');
    }

   /* public function storeManualSheet(Request $request)
{
    $request->validate([
        'assessment_id' => 'required|exists:assessments,id',
        'sheet_id'      => 'required|exists:template_sheets,id',
        'rows'          => 'required|array',
    ]);

    $assessment = Assessment::findOrFail($request->assessment_id);

    foreach ($request->rows as $entryCounter => $columns)
    {
        foreach ($columns as $templateKeyId => $value)
        {
            AssessmentMasterData::updateOrCreate(
                [
                    'assessment_id'   => $assessment->id,
                    'template_key_id' => $templateKeyId,
                    'entry_counter'   => $entryCounter,
                ],
                [
                    'licensee_id'        => $assessment->licensee_id,
                    'template_key_value'=> $value,
                ]
            );
        }
    }

    return back()->with('success', 'Sheet data saved successfully.');
}*/


public function storeManualSheet(Request $request)
{
    $request->validate([
        'assessment_id' => 'required|exists:sr_licensee_assessments,id',
        'sheet_id'      => 'required|exists:sr_licensee_template_sheets,id',
        'sheets'        => 'required|array',
    ]);

    $sheetId    = (int) $request->sheet_id;
    $assessment = Assessment::findOrFail($request->assessment_id);
    $sheet      = LicenseeTemplateSheet::with('keys')->findOrFail($sheetId);
    $sheetData  = $request->input("sheets.$sheetId");

    if (!$sheetData) {
        return back()->withErrors(['msg' => 'No data found for this sheet.']);
    }

    // 1. Per-type validation — collect ALL errors across rows, fail fast with a full list.
    $validationErrors = $this->validateManualSheetRows($sheet, $sheetData);
    if (!empty($validationErrors)) {
        return back()->withErrors($validationErrors)->withInput();
    }

    // 2. Build helper inputs once. keysMap shape: short_code => [id, type, mandatory, ...].
    $keysMap = [];
    foreach ($sheet->keys as $key) {
        $keysMap[$key->short_code] = $key->toArray();
    }
    $mandatoryIds = IngestionUpsertHelper::mandatoryKeyIds($keysMap);
    $existingMap  = IngestionUpsertHelper::preloadExistingRecords($assessment->id, $sheetId, $mandatoryIds);

    // 3. Pre-fetch existing row hashes for this sheet so identical re-submissions short-circuit
    //    (same contract as FinalizeImportJob — matches dedup semantics across entry paths).
    $existingHashes = DB::table('sr_assessment_row_hashes')
        ->where('assessment_id', $assessment->id)
        ->where('sheet_id', $sheetId)
        ->pluck('row_hash')
        ->flip()
        ->toArray();

    $inserted = 0; $updated = 0; $duplicate = 0; $skipped = 0;
    $skippedDetails = [];

    DB::transaction(function () use (
        $assessment, $sheet, $sheetId, $sheetData, $keysMap,
        &$existingMap, &$existingHashes,
        &$inserted, &$updated, &$duplicate, &$skipped, &$skippedDetails
    ) {
        foreach ($sheetData as $entryCounter => $row) {
            // Build [short_code => value] payload for the helper.
            // Auto-counter columns (mandatory==3) get their next value computed HERE, before
            // signature build, so an identical re-submission reuses the same auto-counter
            // (classifyRow will see the same signature → noop) instead of burning a new one.
            $rowByShortCode = [];
            foreach ($sheet->keys as $key) {
                $value = $row[$key->id] ?? null;
                if ((int) ($key->mandatory ?? 0) === 3 && ($value === null || $value === '')) {
                    $maxAuto = (int) DB::table('sr_licensee_assessment_master_data')
                        ->where('assessment_id', $assessment->id)
                        ->where('template_sheet_id', $sheetId)
                        ->where('template_key_id', $key->id)
                        ->max('template_key_value');
                    $value = $maxAuto > 0 ? ($maxAuto + 1) : 1;
                }
                $rowByShortCode[$key->short_code] = $value;
            }

            // 3a. Row-hash fast-path — exact re-submission.
            $rowHash = md5(json_encode($rowByShortCode, JSON_UNESCAPED_UNICODE));
            if (isset($existingHashes[$rowHash])) {
                $duplicate++;
                continue;
            }

            // 3b. Signature-based classify (insert / update / noop / skip).
            $decision = IngestionUpsertHelper::classifyRow(
                $rowByShortCode, $keysMap, $existingMap,
                [
                    'assessment_id' => $assessment->id,
                    'licensee_id'   => $assessment->licensee_id,
                    'sheet_id'      => $sheetId,
                    'entry_counter' => (int) $entryCounter,
                ]
            );

            switch ($decision['action']) {
                case 'insert':
                    if (!empty($decision['rows'])) {
                        AssessmentMasterData::insert($decision['rows']);
                    }
                    $inserted++;
                    break;
                case 'update':
                    IngestionUpsertHelper::applyUpdates($assessment->id, $sheetId, $decision['updates']);
                    $updated++;
                    break;
                case 'noop':
                    $duplicate++;
                    break;
                case 'skip-no-key':
                    $skipped++;
                    $skippedDetails[] = "Row {$entryCounter}: missing mandatory key value(s).";
                    continue 2;
            }

            // Persist hash for future dedup (same table the bulk path writes).
            DB::table('sr_assessment_row_hashes')->insertOrIgnore([
                'assessment_id' => $assessment->id,
                'sheet_id'      => $sheetId,
                'row_hash'      => $rowHash,
                'created_at'    => now(),
            ]);
            $existingHashes[$rowHash] = true;
        }

        // 4. Update assessment counters (mirrors FinalizeImportJob's completion bump).
        $importedTotal = DB::table('sr_licensee_assessment_master_data')
            ->where('assessment_id', $assessment->id)
            ->select('template_sheet_id', 'entry_counter')
            ->distinct()->get()->count();

        DB::table('sr_licensee_assessments')->where('id', $assessment->id)->update([
            'imported_rows'  => $importedTotal,
            'inserted_rows'  => DB::raw("inserted_rows + {$inserted}"),
            'updated_rows'   => DB::raw("updated_rows + {$updated}"),
            'duplicate_rows' => DB::raw("duplicate_rows + {$duplicate}"),
            'skipped_rows'   => DB::raw("skipped_rows + {$skipped}"),
        ]);
    });

    $summary = "Sheet saved. inserted={$inserted}, updated={$updated}, duplicate={$duplicate}, skipped={$skipped}";
    $response = back()->with('success', $summary);
    if (!empty($skippedDetails)) {
        $response = $response->withErrors($skippedDetails);
    }
    return $response;
}

/**
 * Validate every cell in every submitted row against the template key's declared type.
 * Returns an array of human-readable error messages (empty = all valid).
 * Kept in a private helper so storeManualSheet stays readable.
 */
private function validateManualSheetRows($sheet, array $sheetData): array
{
    $errors = [];
    foreach ($sheetData as $entryCounter => $row) {
        foreach ($sheet->keys as $key) {
            $value = $row[$key->id] ?? null;
            if ($value === null || $value === '') {
                continue; // empty values handled by mandatory check elsewhere
            }
            switch ($key->type) {
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = "Invalid number for {$key->desc_en} (Row {$entryCounter})";
                    }
                    break;
                case 'number_percentage':
                    if (!preg_match('/^\d+(\.\d+)?%$/', $value)) {
                        $errors[] = "Invalid percentage for {$key->desc_en} (Row {$entryCounter}). Use format like 25% or 10.5%";
                    }
                    break;
                case 'date':
                    if (!strtotime($value)) {
                        $errors[] = "Invalid date for {$key->desc_en} (Row {$entryCounter})";
                    }
                    break;
                case 'datetime':
                    if (!strtotime($value)) {
                        $errors[] = "Invalid datetime for {$key->desc_en} (Row {$entryCounter})";
                    }
                    break;
                case 'time':
                    if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                        $errors[] = "Invalid time for {$key->desc_en} (Row {$entryCounter})";
                    }
                    break;
                case 'select':
                    $options = array_map('trim', explode(',', $key->options ?? ''));
                    if (!in_array($value, $options)) {
                        $errors[] = "Invalid option for {$key->desc_en} (Row {$entryCounter})";
                    }
                    break;
            }
        }
    }
    return $errors;
}


public function clearData($assessmentId)
{
    try {
        DB::beginTransaction();

        // Delete all master data rows linked to this assessment
        AssessmentMasterData::where('assessment_id', $assessmentId)->delete();
        $assessment = Assessment::findOrFail($assessmentId);
        $assessment->update(['status' => 'draft']);

        DB::commit();

        return redirect()
            ->route('assessments.show', $assessmentId)
            ->with('success', 'All master data for this assessment has been cleared successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->route('assessments.show', $assessmentId)
            ->with('error', 'Failed to clear master data: ' . $e->getMessage());
    }
}

public function archiveSheetEntry($assessmentId,$sheetId,$entryId)
{
    try {
        DB::beginTransaction();

        // Delete all master data rows linked to this assessment
        AssessmentMasterData::where('assessment_id', $assessmentId)->where('template_sheet_id', $sheetId)->where('entry_counter', $entryId)->delete();

        DB::commit();

        return redirect()
            ->route('assessments.show', $assessmentId)
            ->with('success', 'All master data for this entry has been cleared successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->route('assessments.show', $assessmentId)
            ->with('error', 'Failed to clear master data: ' . $e->getMessage());
    }
}




    public function destroy($id)
{
    try {
        $assessment = Assessment::findOrFail($id);

        // Optional: Explicitly delete related data (if no DB cascade)
        $assessment->masterData()->delete();
        $assessment->slaveData()->delete();

        // Delete assessment
        $assessment->delete();

        return redirect()
            ->route('assessments.index')
            ->with('success', 'Assessment and related data deleted successfully.');
    } catch (\Exception $e) {
        return back()->withErrors(['msg' => 'Failed to delete: ' . $e->getMessage()]);
    }
}

public function update(Request $request, Assessment $assessment)
{
    $validated = $request->validate([
        'status' => 'required|string|max:50',
    ]);

    $assessment->update(['status' => $validated['status']]);

    return response()->json(['success' => true, 'message' => 'Assessment updated successfully.']);
}

public function exportAssessment()
{
    $fileName = 'Assessment_' . now()->format('Ymd_His') . '.xlsx';

    return Excel::download(new AssessmentExport, $fileName);
}

public function exportMasterData($assessmentId)
{
    $assessment = Assessment::with('licensee', 'template')->findOrFail($assessmentId);

    // Get unique sheet IDs for this assessment
    $sheetIds = AssessmentMasterData::where('assessment_id', $assessmentId)
        ->distinct()
        ->pluck('template_sheet_id');

    if ($sheetIds->isEmpty()) {
        return back()->with('error', 'No data to export.');
    }

    // ✅ OPTIMIZATION: If only 1 sheet, export as CSV to handle millions of rows without memory issues or Excel limits.
    if ($sheetIds->count() === 1) {
        $sheetId = $sheetIds->first();
        $sheet = LicenseeTemplateSheet::with('keys')->findOrFail($sheetId);
        return $this->exportToCsv($assessment, $sheet);
    }

    // ✅ OPTIMIZATION: If many sheets, export as a ZIP of CSVs to prevent OOM errors from PHPSpreadsheet
    return $this->exportToZip($assessment, $sheetIds);
}

/**
 * Highly optimized streaming ZIP of CSVs export for massive multi-sheet datasets.
 */
private function exportToZip($assessment, $sheetIds)
{
    $fileName = 'Assessment_MasterData_' . $assessment->id . '_' . now()->format('Ymd') . '.zip';
    $tempDir = storage_path('app/temp');
    
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $zipPath = $tempDir . '/' . $fileName;
    $zip = new \ZipArchive();
    
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'Could not create ZIP archive.');
    }

    $templateSheets = LicenseeTemplateSheet::with('keys')
        ->whereIn('id', $sheetIds)
        ->get();

    $tempFiles = [];

    foreach ($templateSheets as $sheet) {
        $csvFileName = Str::slug($sheet->sheet_name) . '.csv';
        $tempCsvPath = $tempDir . '/' . uniqid('csv_') . '.csv';
        $tempFiles[] = $tempCsvPath;

        $handle = fopen($tempCsvPath, 'w');
        // Add UTF-8 BOM for Excel compatibility (especially for Arabic content)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        $keys = $sheet->keys->sortBy('id');
        $keyIds = $keys->pluck('id')->toArray();
        $keyIdToIndex = array_flip($keyIds);
        $headerRow = $keys->pluck('short_code')->toArray();

        fputcsv($handle, $headerRow);

        $maxEntryCounter = DB::table('sr_licensee_assessment_master_data')
            ->where('assessment_id', $assessment->id)
            ->where('template_sheet_id', $sheet->id)
            ->max('entry_counter') ?? 0;

        $currentRowIndex = -1;
        $currentRow = [];
        $columnCount = count($keyIds);

        // Fetch in chunks of 5,000 entry_counters to completely avoid PDO buffering memory exhaustion
        $chunkSize = 5000;
        for ($start = 0; $start <= $maxEntryCounter; $start += $chunkSize) {
            $end = $start + $chunkSize - 1;

            $entries = DB::table('sr_licensee_assessment_master_data')
                ->where('assessment_id', $assessment->id)
                ->where('template_sheet_id', $sheet->id)
                ->whereBetween('entry_counter', [$start, $end])
                ->orderBy('entry_counter')
                ->orderBy('template_key_id')
                ->get();

            foreach ($entries as $entry) {
                if ($entry->entry_counter !== $currentRowIndex) {
                    // Flush the previous row to output
                    if ($currentRowIndex !== -1) {
                        fputcsv($handle, $currentRow);
                    }
                    $currentRowIndex = $entry->entry_counter;
                    $currentRow = array_fill(0, $columnCount, null);
                }

                if (isset($keyIdToIndex[$entry->template_key_id])) {
                    $idx = $keyIdToIndex[$entry->template_key_id];
                    $currentRow[$idx] = $entry->template_key_value;
                }
            }
        }

        // Flush final row
        if ($currentRowIndex !== -1) {
            fputcsv($handle, $currentRow);
        }

        fclose($handle);
        $zip->addFile($tempCsvPath, $csvFileName);
    }

    $zip->close();

    // Clean up temporary CSV files now that they are in the ZIP
    foreach ($tempFiles as $tempFile) {
        @unlink($tempFile);
    }

    return response()->download($zipPath)->deleteFileAfterSend(true);
}

/**
 * Highly optimized streaming CSV export for massive datasets.
 */
private function exportToCsv($assessment, $sheet)
{
    $fileName = 'Assessment_' . $assessment->id . '_' . Str::slug($sheet->sheet_name) . '_' . now()->format('Ymd') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ];

    return new StreamedResponse(function () use ($assessment, $sheet) {
        $handle = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility (especially for Arabic content)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Get keys to build header columns
        $keys = $sheet->keys->sortBy('id');
        $keyIds = $keys->pluck('id')->toArray();
        $keyIdToIndex = array_flip($keyIds);
        $headerRow = $keys->pluck('short_code')->toArray();

        fputcsv($handle, $headerRow);

        $maxEntryCounter = DB::table('sr_licensee_assessment_master_data')
            ->where('assessment_id', $assessment->id)
            ->where('template_sheet_id', $sheet->id)
            ->max('entry_counter') ?? 0;

        $currentRowIndex = -1;
        $currentRow = [];
        $columnCount = count($keyIds);

        // Fetch in chunks of 5,000 entry_counters to completely avoid PDO buffering memory exhaustion
        $chunkSize = 5000;
        for ($start = 0; $start <= $maxEntryCounter; $start += $chunkSize) {
            $end = $start + $chunkSize - 1;

            $entries = DB::table('sr_licensee_assessment_master_data')
                ->where('assessment_id', $assessment->id)
                ->where('template_sheet_id', $sheet->id)
                ->whereBetween('entry_counter', [$start, $end])
                ->orderBy('entry_counter')
                ->orderBy('template_key_id')
                ->get();

            foreach ($entries as $entry) {
                if ($entry->entry_counter !== $currentRowIndex) {
                    // Flush the previous row to output
                    if ($currentRowIndex !== -1) {
                        fputcsv($handle, $currentRow);
                    }
                    $currentRowIndex = $entry->entry_counter;
                    $currentRow = array_fill(0, $columnCount, null);
                }

                if (isset($keyIdToIndex[$entry->template_key_id])) {
                    $idx = $keyIdToIndex[$entry->template_key_id];
                    $currentRow[$idx] = $entry->template_key_value;
                }
            }
        }

        // Flush final row
        if ($currentRowIndex !== -1) {
            fputcsv($handle, $currentRow);
        }

        fclose($handle);
    }, 200, $headers);
    }

    /**
     * Get live progress of the assessment import.
     */
    public function getProgress(Assessment $assessment)
    {
        // Prevent session locking from blocking the polling request
        session_write_close();
        
        // Fetch raw record from DB to bypass any Eloquent stale state or isolation issues
        $rawAssessment = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
            ->where('id', $assessment->id)
            ->first();

        if (!$rawAssessment) {
            return response()->json(['error' => 'Assessment not found'], 404);
        }

        $data = [
            'status'         => $rawAssessment->status,
            'total_rows'     => (int)$rawAssessment->total_rows,
            'processed_rows' => (int)$rawAssessment->processed_rows,
            'finalized_rows' => (int)$rawAssessment->finalized_rows,
            'imported_rows'  => (int)($rawAssessment->imported_rows ?? 0),
            'inserted_rows'  => (int)($rawAssessment->inserted_rows ?? 0),
            'updated_rows'   => (int)($rawAssessment->updated_rows ?? 0),
            'duplicate_rows' => (int)($rawAssessment->duplicate_rows ?? 0),
            'skipped_rows'   => (int)($rawAssessment->skipped_rows ?? 0),
            'percentage'     => $this->calculatePercentageFromRaw($rawAssessment),
        ];

        Log::info("Assessment Progress Polling (Direct DB)", [
            'assessment_id' => $assessment->id,
            'data'          => $data
        ]);

        return response()->json($data);
    }

    private function calculatePercentageFromRaw($raw)
    {
        if ($raw->status === 'completed') return 100;
        if ($raw->total_rows <= 0) return 0;

        if ($raw->status === 'committing') {
            return min(100, round(($raw->finalized_rows / $raw->total_rows) * 100));
        }

        return min(100, round(($raw->processed_rows / $raw->total_rows) * 100));
    }

    private function calculatePercentage(Assessment $assessment)
    {
        if ($assessment->status === 'completed') return 100;
        if ($assessment->total_rows <= 0) return 0;

        if ($assessment->status === 'committing') {
            return min(100, round(($assessment->finalized_rows / $assessment->total_rows) * 100));
        }

        return min(100, round(($assessment->processed_rows / $assessment->total_rows) * 100));
    }
}
