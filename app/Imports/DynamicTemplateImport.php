<?php
namespace App\Imports;

use App\Models\LicenseeTemplateKey;
use App\Models\SlaveMasterData;
use App\Models\LicenseeTemplateSheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Support\Str;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;



class DynamicTemplateImport implements WithMultipleSheets, WithCalculatedFormulas
{
    protected $assessmentId;
    protected $licenseeId;
    protected $licenseeTemplateId;
    protected $sheetMapping;   // ['sheet_name' => sheet_id]
    protected $entryCounter = 1;
    public $canProceed = true;
    public $errorsPerSheet = [];
    public $namePerSheet = [];

    public function __construct($assessmentId, $licenseeId,$licenseeTemplateId, $sheetMapping)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId   = $licenseeId;
        $this->licenseeTemplateId = $licenseeTemplateId;
        $this->sheetMapping = $sheetMapping;
    }

    public function sheets(): array
    {
        $imports = [];

        foreach ($this->sheetMapping as $sheetName => $sheetId) {

            //$this->namePerSheet[$sheetName] = $sheetId;

            // ✅ Initialize error bag per sheet
            if($sheetName!="Master")
            $this->errorsPerSheet[$sheetName] = [];

            // ✅ Load template keys dynamically (column-wise)
            $keys = LicenseeTemplateKey::where('licensee_id', $this->licenseeId)
                ->where('sheet_id', $sheetId)
                ->orderBy('id') // column order = DB order
                ->get();

            //if ($keys->isEmpty()) continue;
                
            $parent = $this;
            $imports[$sheetName] = new class(
                $this->assessmentId,
                $this->licenseeId,
                $this->licenseeTemplateId,
                $sheetId,
                $sheetName,
                $keys,
                $this->entryCounter,
                $parent
            ) implements ToCollection, WithCalculatedFormulas {

                protected $assessmentId;
                protected $licenseeId;
                protected $sheetId;
                protected $sheetName;
                protected $keys;
                protected $entryCounter;
                protected $buffer = [];
                protected $parent;

                public function __construct($assessmentId, $licenseeId, $licenseeTemplateId,$sheetId,$sheetName, $keys, &$entryCounter,$parent)
                {
                    $this->assessmentId = $assessmentId;
                    $this->licenseeId   = $licenseeId;
                    $this->licenseeTemplateId = $licenseeTemplateId;
                    $this->sheetId      = $sheetId;
                    $this->sheetName    = $sheetName;
                    $this->keys         = $keys;
                    $this->entryCounter =& $entryCounter;
                    $this->parent       = $parent;
                }

                public function collection(Collection $rows)
                {
                    // ✅ HEADER CAPTURE (row 0)
                    $this->headers = collect($rows[0] ?? [])
    ->map(function ($value) {

        // Handle RichText
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        // Trim strings
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value;
    })
    ->filter(function ($value) {
        // Remove null / blank / empty
        return !is_null($value) && $value !== '';
    })
    ->values()   // Re-index array keys
    ->toArray();

                    $headerErrors = $this->parent->validateHeaders(
        $this->headers,
        $this->keys
    );

                    if (!empty($headerErrors)) {
         $sheet_valid_res = LicenseeTemplateSheet::where('template_id',$this->licenseeTemplateId)->where('id',$this->sheetId)->first();
        if($sheet_valid_res){               
            // ❌ Block entire import
            $this->parent->canProceed = false;

            $this->parent->errorsPerSheet[$this->sheetName][] = [
                'type'    => 'header_validation',
                'message' => 'Excel columns do not match template definition.',
                'errors'  => $headerErrors
            ];
            $this->parent->namePerSheet[$this->sheetName] = $this->sheetId;
            // ❌ DO NOT PROCESS ROWS
            return;
        }
    }

         $sheet_valid_res = LicenseeTemplateSheet::where('template_id',$this->licenseeTemplateId)->where('id',$this->sheetId)->first();
        if($sheet_valid_res){        
                    foreach ($rows as $rowIndex => $row) {
                       
                        if ($rowIndex === 0) continue; // ✅ Header skip

                        $value_t = $row[0] instanceof Cell
                                ? $row[0]->getCalculatedValue()
                                : $row[0];
                            
                        if($value_t!=''){
                            $rawRow = [];

                            foreach ($row as $cell) {

                                // ✅ Formula + cross-sheet resolved
                                $value = $cell instanceof Cell
                                    ? $cell->getCalculatedValue()
                                    : $cell;

                                // ✅ Rich text → plain string
                                if ($value instanceof RichText) {
                                    $value = $value->getPlainText();
                                }

                                // ✅ Excel Date / Time conversion
                                if ($cell instanceof Cell && Date::isDateTime($cell)) {
                                    $value = Carbon::instance(
                                        Date::excelToDateTimeObject($value)
                                    );
                                }

                                if (is_string($value)) {
                                    $value = trim(html_entity_decode($value));
                                }

                                if (!is_null($value) && $value !== '') {
                                        $rawRow[] = $value;
                                }

                                /*if ($value === '') $value = null;
                                if($value!=null)
                                    $rawRow[] = $value;*/
                            }
                            // ✅ FIELD MAPPING + TYPE CASTING + VALIDATION
                            $mapped = [];
                            $rules  = [];

                            foreach ($this->keys as $index => $key) {

                                $value = $rawRow[$index] ?? null;
                                
                                // ✅ TYPE HANDLING
                                switch ($key->type) {
                                    case 'number':
                                        $value = is_numeric($value) ? +$value : null;
                                        if($key->mandatory == 3){
                                            $rules[$key->short_code] = array_merge(['nullable'],['numeric']);    
                                        }else{
                                            $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['numeric']
                                            );
                                        }
                                        
                                        break;

                                    case 'number_percentage':
                                        $value = is_numeric($value) ? (float)$value : null;
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['numeric', 'min:0', 'max:100']
                                        );
                                        break;

                                    case 'text':
                                    case 'select':
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['string']
                                        );
                                        break;

                                    case 'date':
                                        $value = $value ? Carbon::parse($value)->format('Y-m-d') : null;
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date']
                                        );
                                        break;

                                    case 'datetime':
                                        $value = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date']
                                        );
                                        break;

                                    case 'time':
                                        $value = $value ? Carbon::parse($value)->format('H:i:s') : null;
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date_format:H:i:s']
                                        );
                                        break;
                                }

                                $mapped[$key->short_code] = $value;
                            }

                            // ✅ VALIDATION
                            $validator = Validator::make($mapped, $rules);

                            $status = $validator->fails() ? 'pending' : 'processed';
                            $validationErrors = $validator->fails()
                                ? $validator->errors()->toArray()
                                : [];

                            // ✅ GLOBAL FLAGS FOR PREVIEW
                            
                            if ($status === 'pending') {
                                $this->parent->canProceed = false;
                                $this->parent->errorsPerSheet[$this->sheetName][$rowIndex] = $validationErrors;
                            }
                            $this->parent->namePerSheet[$this->sheetName] = $this->sheetId;

                       
                            //$this->errorsPerSheet[$this->sheetName][$rowIndex] = $validationErrors;
                            //$this->namePerSheet[$this->sheetName] = $this->sheetId;

                            // ✅ FINAL ROW STORAGE (ALWAYS STORED)
                            
                            
                                $this->buffer[] = [
                                    'assessment_id'      => $this->assessmentId,
                                    'licensee_id'        => $this->licenseeId,
                                    'template_id'        => $this->licenseeTemplateId,
                                    'headers'            => json_encode($this->headers),
                                    'row_data'           => json_encode($mapped),
                                    'validation_errors' => json_encode($validationErrors),
                                    'row_index'          => $rowIndex,
                                    'status'             => $status,
                                    'processing_message'=> null,
                                    'sheet_id'           => $this->sheetId,
                                    'created_at'         => now(),
                                    'updated_at'         => now(),
                                ];

                                $this->entryCounter++;

                                // ✅ BULK FLUSH (500 rows)
                                if (count($this->buffer) >= 500) {
                                    SlaveMasterData::insert($this->buffer);
                                    $this->buffer = [];
                                }    
                            }
                            
                        }
                    }

                    // ✅ FINAL FLUSH
                    if (!empty($this->buffer)) {
                        SlaveMasterData::insert($this->buffer);
                    }
                }
            };
        }

        return $imports;
    }

    /**
     * ✅ Validate Excel headers against LicenseeTemplateKey
     */
    public function validateHeaders(array $excelHeaders, $keys): array
    {
        // Normalize headers
        $excelHeaders = collect($excelHeaders)
            ->map(fn ($h) => Str::slug(trim($h), '_'))
            ->filter()
            ->values()
            ->toArray();

        // Expected keys from DB
        $expectedKeys = $keys
            ->pluck('short_code')
            ->map(fn ($k) => Str::slug($k, '_'))
            ->toArray();

        $missing = array_values(array_diff($expectedKeys, $excelHeaders));
        $extra   = array_values(array_diff($excelHeaders, $expectedKeys));

        $errors = [];

        if (!empty($missing)) {
            $errors['missing_columns'] = $missing;
        }

        if (!empty($extra)) {
            $errors['extra_columns'] = $extra;
        }

        return $errors;
    }


    public function isRowBlank(array $row): bool
{
    foreach ($row as $value) {

        // Resolve RichText
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        // Trim strings
        if (is_string($value)) {
            $value = trim($value);
        }

        // Ignore Excel errors
        if ($this->isExcelError($value)) {
            $value = null;
        }

        // If any real value exists → NOT blank
        if (!is_null($value) && $value !== '') {
            return false;
        }
    }

    return true;
}

}
