<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Storage;


class AssessmentController extends Controller
{

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
        $assessments = Assessment::with(['licenseeTemplate.subfolder'])
        ->orderByDesc('created_at')
        ->get();

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


    // Group master data by sheet
    $masterData = $assessment->masterData()
    ->orderBy('template_sheet_id')
    ->orderBy('entry_counter')
    ->orderBy('template_key_id')
    ->get()
    ->groupBy('template_sheet_id')
    ->map(function ($sheetGroup) {
        return $sheetGroup->groupBy('entry_counter')
            ->map(function ($rowGroup) {

                $mapped = [];

                foreach ($rowGroup as $item) {

                    $mapped[$item->template_key_id] = $item->template_key_value;
                }

                return $mapped;
            });
    });
    //print_r($masterData);exit;
    return view('modules.assessments.show', compact(
        'assessment',
        'sheets',
        'masterData'
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
    $validated = $request->validate([
        'licensee_id' => 'required|integer|exists:sr_licensees,id',
        'licensee_template_id' => 'required|integer|exists:sr_licensee_templates,id',
        'assessment_date' => 'required|date',
        'status' => 'required|string',
        'file' => 'required|file|mimes:xlsx',
        'assessment_id' => 'required|integer|exists:sr_licensee_assessments,id',
    ]);


   
    $assessment = Assessment::find($request->assessment_id);
     SlaveMasterData::where('assessment_id', $assessment->id)->delete();

    /** ------------------------------------------------------------------
     * 1. Determine sheet → template mapping for this template
     * ------------------------------------------------------------------*/
    // Example (can later come from DB table `template_sheets`)
    $sheetMapping = TemplateSheet::where('template_id', $validated['licensee_template_id'])
    ->pluck('id', 'sheet_name')
    ->toArray();
    //$import = new MultiSheetImport($assessment->id, $validated['licensee_id'], $sheetMapping);
    //Excel::import($import, $request->file('file'));
    //$rows = Excel::toArray([], $request->file('file'));
    
    $import = new DynamicTemplateImport(
            $assessment->id,
            $assessment->licensee_id,
            $assessment->licensee_template_id,
            $sheetMapping
        );


    Excel::import($import,$request->file('file'));
    /** ------------------------------------------------------------------
     * 3. Fetch preview (first 50 rows of all sheets)
     * ------------------------------------------------------------------*/
    /*$previewRows = SlaveMasterData::where('assessment_id', $assessment->id)
        ->orderBy('sheet_id')
        ->orderBy('row_index')
        ->limit(50)
        ->get();*/
    $previewRows = SlaveMasterData::where('assessment_id', $assessment->id)->get();
    return view('modules.assessments.preview_new', [
        'assessment' => $assessment,
        'previewRows' => $previewRows,
        'canProceed' => $import->canProceed,
        'errorsPerSheet' => $import->errorsPerSheet,
        'namePerSheet' => $import->namePerSheet
    ]);
}




public function importData(Request $request, Assessment $assessment)
{
    DB::beginTransaction();

    try {
        // Fetch rows from slave_master_data for this assessment
        $assessment = Assessment::find($request->assessment_id);

        $rows = SlaveMasterData::where('assessment_id', $assessment->id)
            ->orderBy('row_index')
            ->orderBy('sheet_id')
            ->get();

        $insertBatch = [];
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $errors = $row->validation_errors;

            if (!empty($errors)) {
                // Skip row if validation errors
                $skippedCount++;
                continue;
            }

            // Transform for main assessment data table
            $headers = is_array($row->headers) ? $row->headers : json_decode($row->headers, true);
            $rowData = is_array($row->row_data) ? $row->row_data : json_decode($row->row_data, true);
            $mapped = [];
            foreach ($headers as $i => $key) {
                $mapped[$key] = $rowData[$key] ?? null;
            }
            foreach ($rowData as $col => $value) {
                $templateKey = LicenseeTemplateKey::where('short_code', $col)->where('licensee_template_id',$assessment->licensee_template_id)->where('licensee_id',$assessment->licensee_id)->where('sheet_id',$row->sheet_id)->first();
                if ($templateKey) {
                     $insertBatch[] = [
                        'licensee_id' => $row->licensee_id,
                        'assessment_id' => $row->assessment_id,
                        'template_sheet_id' => $row->sheet_id,
                        'template_key_id' => $templateKey->id,
                        'template_key_value' => $value,
                        'type' => $templateKey->type,
                        'entry_counter' => $row->row_index,
                    ];
                }
            }
            // Insert every 500 rows
            if (count($insertBatch) === 500) {
                AssessmentMasterData::insert($insertBatch);
                $insertBatch = [];
            }

            $importedCount++;
        }

        if (!empty($insertBatch)) {
            AssessmentMasterData::insert($insertBatch);
        }

        // Update assessment as completed
        $assessment->update([
            'status' => 'completed',
            'imported_rows' => $importedCount,
            'skipped_rows' => $skippedCount,
        ]);

        SlaveMasterData::where('assessment_id', $assessment->id)->delete();

        DB::commit();

        return redirect()
            ->route('assessments.show', $assessment->id)
            ->with('success', "Import Complete: $importedCount rows imported, $skippedCount skipped.");

    } catch (\Exception $e) {
        DB::rollBack();
        print_r($e->getMessage());
        //return back()->with('error', 'Import failed: ' . $e->getMessage());
    }
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
            $rules[$key->short_code] = $key->mandatory ? 'required' : 'nullable';
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
        'sheets'          => 'required|array',
    ]);

    $sheet = LicenseeTemplateSheet::with('keys')->findOrFail($request->sheet_id);
    $sheetId = $request->sheet_id;
    $sheetData = $request->input("sheets.$sheetId");
    
    if (!$sheetData) {
        return back()->withErrors(['msg' => 'No data found for this sheet.']);
    }
    $assessment = Assessment::findOrFail($request->assessment_id);

    foreach ($sheetData as $entryCounter => $row) {

        foreach ($sheet->keys as $key) {

            $value = $row[$key->id] ?? null;

            // ✅ DYNAMIC VALIDATION BASED ON TYPE
            if ($value !== null && $value !== '') {

                switch ($key->type) {

                    case 'number':
                        if (!is_numeric($value)) {
                            return back()->withErrors([
                                "Invalid number for {$key->desc_en} (Row $entryCounter)"
                            ]);
                        }
                        break;

                    case 'number_percentage':
                        if (!preg_match('/^\d+(\.\d+)?%$/', $value)) {
                            return back()->withErrors([
                                "Invalid percentage for {$key->desc_en}. Use format like 25% or 10.5%"
                            ]);
                        }
                        break;

                    case 'date':
                        if (!strtotime($value)) {
                            return back()->withErrors([
                                "Invalid date for {$key->desc_en}"
                            ]);
                        }
                        break;

                    case 'datetime':
                        if (!strtotime($value)) {
                            return back()->withErrors([
                                "Invalid datetime for {$key->desc_en}"
                            ]);
                        }
                        break;

                    case 'time':
                        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                            return back()->withErrors([
                                "Invalid time for {$key->desc_en}"
                            ]);
                        }
                        break;

                    case 'select':
                        $options = array_map('trim', explode(',', $key->options ?? ''));
                        if (!in_array($value, $options)) {
                            return back()->withErrors([
                                "Invalid option for {$key->desc_en}"
                            ]);
                        }
                        break;
                }
            }
            $maxEntryCounter = AssessmentMasterData::where('assessment_id', $assessment->id)->max('entry_counter');

            // ✅ SAVE INTO MASTER DATA
            
            AssessmentMasterData::create([
                'licensee_id' => $assessment->licensee_id,
                'assessment_id' => $assessment->id,
                'template_sheet_id' => $sheetId,
                'template_key_id' => $key->id,
                'template_key_value' => $value,
                'type' => $key->type,
                'entry_counter' => $maxEntryCounter,
            ]);
        }
    }
    return back()->with('success', 'Sheet data saved successfully.');
}


public function clearData($assessmentId)
{
    try {
        DB::beginTransaction();

        // Delete all master data rows linked to this assessment
        AssessmentMasterData::where('assessment_id', $assessmentId)->delete();

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


   
}
