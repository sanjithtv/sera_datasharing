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

            // ✅ Skip the Master sheet — it is not part of the data template
            // and not all uploaded files contain it. Requesting it causes
            // "sheet name out of bounds" from PhpSpreadsheet.
            if ($sheetName === 'Master') {
                continue;
            }

            // ✅ Initialize error bag per sheet
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

                        // Excel formula error strings (broken references, bad values, etc.)
                        $excelErrorStrings = ['#NULL!', '#DIV/0!', '#VALUE!', '#REF!', '#NAME?', '#NUM!', '#N/A', '#GETTING_DATA'];

                        try {
                            $value_t = $row[0] instanceof Cell
                                ? $row[0]->getCalculatedValue()
                                : $row[0];
                        } catch (\PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                            // Fall back to Excel's own cached result
                            $value_t = ($row[0] instanceof Cell) ? $row[0]->getOldCalculatedValue() : null;
                        }

                        // If PhpSpreadsheet returned a formula error, fall back to the value
                        // Excel cached in the file when it was last saved (getOldCalculatedValue).
                        // This is the real computed value the user saw in Excel.
                        if ($row[0] instanceof Cell && is_string($value_t) && in_array(strtoupper(trim($value_t)), $excelErrorStrings)) {
                            $cached = $row[0]->getOldCalculatedValue();
                            $value_t = ($cached !== null && !(is_string($cached) && in_array(strtoupper(trim($cached)), $excelErrorStrings)))
                                ? $cached
                                : null;
                        }

                        if($value_t!=''){
                            $rawRow = [];

                            foreach ($row as $cell) {

                                // ✅ Formula + cross-sheet resolved
                                // Wrap in try/catch in case PhpSpreadsheet throws on a broken formula
                                try {
                                    $value = $cell instanceof Cell
                                        ? $cell->getCalculatedValue()
                                        : $cell;
                                } catch (\PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                                    // Fall back to Excel's own cached result on calculation failure
                                    $value = ($cell instanceof Cell) ? $cell->getOldCalculatedValue() : null;
                                }

                                // ✅ If PhpSpreadsheet returned a formula error string (#REF!, #VALUE!,
                                // #N/A, etc.), fall back to the value Excel itself cached in the file
                                // (getOldCalculatedValue). This is the real value the user saw in Excel
                                // before the reference broke during export/copy. Only null out if the
                                // cached value is also an error or empty.
                                if ($cell instanceof Cell && is_string($value) && in_array(strtoupper(trim($value)), $excelErrorStrings)) {
                                    $cached = $cell->getOldCalculatedValue();
                                    $value = ($cached !== null && !(is_string($cached) && in_array(strtoupper(trim($cached)), $excelErrorStrings)))
                                        ? $cached
                                        : null;
                                }

                                // ✅ Rich text → plain string
                                if ($value instanceof RichText) {
                                    $value = $value->getPlainText();
                                }

                                // ✅ Excel Date / Time conversion
                                if ($cell instanceof Cell && $value !== null && Date::isDateTime($cell)) {
                                    try {
                                        $value = Carbon::instance(
                                            Date::excelToDateTimeObject($value)
                                        );
                                        $value = Carbon::instance($value)->format('d-m-Y');
                                    } catch (\Exception $e) {
                                        $value = null;
                                    }
                                }

                                if (is_string($value)) {
                                    $value = trim(html_entity_decode($value));
                                }

                                // ✅ Always append — even null/empty — so that column indices
                                // stay aligned with the template keys. Skipping empty cells
                                // would shift every subsequent index and map values to the
                                // wrong template columns.
                                $rawRow[] = ($value === '') ? null : $value;
                            }
                            // ✅ FIELD MAPPING + TYPE CASTING + VALIDATION
                            $mapped = [];
                            $rules  = [];
                            foreach ($this->keys as $index => $key) {

                                $value = $rawRow[$index] ?? null;

                                // ✅ TYPE HANDLING
                                // Strategy: only replace $value with the cast/parsed result when the
                                // cast succeeds. If it fails, keep the original raw string so that:
                                //   1. The preview table shows the actual bad value (not "—")
                                //   2. The validator reports a meaningful error ("must be a number")
                                //      rather than "required" (which only appears when value is null).
                                switch ($key->type) {
                                    case 'number':
                                        if (is_numeric($value)) {
                                            $value = +$value; // successful cast
                                        }
                                        // else: keep original string — 'numeric' rule will fail with proper message
                                        if ($key->mandatory == 3) {
                                            $rules[$key->short_code] = array_merge(['nullable'], ['numeric']);
                                        } else {
                                            $rules[$key->short_code] = array_merge(
                                                $key->mandatory ? ['required'] : ['nullable'],
                                                ['numeric']
                                            );
                                        }
                                        break;

                                    case 'number_percentage':
                                        if (is_numeric($value)) {
                                            $value = (float) $value; // successful cast
                                        }
                                        // else: keep original string — 'numeric' rule will fail with proper message
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
                                        $parsed = $this->parent->smartParseDate($value, 'Y-m-d');
                                        if ($parsed !== null && $parsed !== '__INVALID_DATE__') {
                                            $value = $parsed; // successful parse — use normalised date
                                        }
                                        // else: keep original string — 'date' rule will fail with proper message
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date']
                                        );
                                        break;

                                    case 'datetime':
                                        $parsed = $this->parent->smartParseDate($value, 'Y-m-d H:i:s');
                                        if ($parsed !== null && $parsed !== '__INVALID_DATE__') {
                                            $value = $parsed;
                                        }
                                        // else: keep original string — 'date' rule will fail with proper message
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date']
                                        );
                                        break;

                                    case 'time':
                                        $parsed = $this->parent->smartParseTime($value);
                                        if ($parsed !== null && $parsed !== '__INVALID_TIME__') {
                                            $value = $parsed;
                                        }
                                        // else: keep original string — 'date_format' rule will fail with proper message
                                        $rules[$key->short_code] = array_merge(
                                            $key->mandatory ? ['required'] : ['nullable'],
                                            ['date_format:H:i:s']
                                        );
                                        break;
                                }

                                $mapped[$key->short_code] = $value;
                            }

                            // ✅ VALIDATION
                            // Laravel's validator treats dots in key names as nested array notation
                            // (e.g. "S.no" → looks for $data['S']['no']).
                            // Sanitize keys before validation, pass original names as custom attributes
                            // so error messages still read "S.no" not the placeholder, then restore
                            // original keys on the error array for display/storage.
                            $dotPlaceholder = '____DOT____';
                            $safeMapped     = [];
                            $safeRules      = [];
                            $customAttribs  = [];  // safeKey => original key (for readable messages)
                            foreach ($mapped as $k => $v) {
                                $safeKey               = str_replace('.', $dotPlaceholder, $k);
                                $safeMapped[$safeKey]  = $v;
                                $customAttribs[$safeKey] = $k;  // e.g. "S____DOT____no" => "S.no"
                            }
                            foreach ($rules as $k => $v) {
                                $safeRules[str_replace('.', $dotPlaceholder, $k)] = $v;
                            }

                            $validator = Validator::make($safeMapped, $safeRules, [], $customAttribs);

                            $status = $validator->fails() ? 'pending' : 'processed';
                            $validationErrors = [];
                            if ($validator->fails()) {
                                foreach ($validator->errors()->toArray() as $safeKey => $messages) {
                                    // Restore original key name (with dots) for storage & display
                                    $originalKey = str_replace($dotPlaceholder, '.', $safeKey);
                                    $validationErrors[$originalKey] = $messages;
                                }
                            }

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
  * Provided By Mohammed AI Crunch
     * ✅ Parse a date value from any common format and return it in the desired output format.
     *    Returns null for empty values.
     *    Returns '__INVALID_DATE__' if the value cannot be parsed as a valid date.
     */
    public function smartParseDate($value, string $outputFormat = 'Y-m-d'): ?string
    {
        // Already a Carbon/DateTime instance (e.g. from PhpSpreadsheet Excel date detection)
        if ($value instanceof Carbon) {
            return $value->format($outputFormat);
        }
        if ($value instanceof DateTime) {
            return Carbon::instance($value)->format($outputFormat);
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        // Numeric value → treat as Excel date serial (e.g. 45777 = 2025-04-30)
        // Maatwebsite Excel passes raw serials for date-formatted cells in ToCollection mode.
        // Guard: values < 1 are pure time-of-day fractions (0.0 = midnight, 0.999 = ~23:59),
        // NOT date serials. Passing them to excelToDateTimeObject() returns 1970-01-01 which
        // is misleading, so we reject them as invalid dates instead.
        if (is_int($value) || is_float($value) || (is_numeric($value) && !str_contains((string) $value, '-') && !str_contains((string) $value, '/'))) {
            $floatVal = (float) $value;
            if ($floatVal < 1) {
                // This is a time-only fraction — not a valid date serial
                return '__INVALID_DATE__';
            }
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($floatVal);
                return Carbon::instance($dt)->format($outputFormat);
            } catch (\Exception $e) {
                // Fall through to string parsing
            }
        }

        $str = trim((string) $value);

        // Try formats in priority order:
        //   1. ISO (unambiguous)
        //   2. Day-first / European  (dd-mm-yyyy) — most common internationally
        //   3. Month-first / US      (mm-dd-yyyy) — fallback; only succeeds when day > 12
        $formats = [
            // ISO — unambiguous
            'Y-m-d',
            'Y/m/d',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            // Abbreviated month name (e.g. 30-Apr-25, 30-Apr-2025, 1-Apr-25)
            'd-M-y',   // 30-Apr-25
            'd-M-Y',   // 30-Apr-2025
            'j-M-y',   // 1-Apr-25  (unpadded day)
            'j-M-Y',   // 1-Apr-2025
            'd/M/y',   // 30/Apr/25
            'd/M/Y',   // 30/Apr/2025
            'j/M/y',
            'j/M/Y',
            'd M y',   // 30 Apr 25
            'd M Y',   // 30 Apr 2025
            'j M y',
            'j M Y',
            // Full month name (e.g. 30 April 2025)
            'd F Y',
            'j F Y',
            // Day-first numeric — European (dd-mm-yyyy)
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'j-n-Y',   // unpadded day/month, dash
            'j/n/Y',   // unpadded day/month, slash
            'j.n.Y',   // unpadded day/month, dot
            // Month-first numeric — US (mm-dd-yyyy), tried last
            'm-d-Y',
            'm/d/Y',
            'n-j-Y',   // unpadded month/day, dash
            'n/j/Y',   // unpadded month/day, slash
        ];

        foreach ($formats as $fmt) {
            try {
                $date = Carbon::createFromFormat($fmt, $str);
                if ($date === false) {
                    continue;
                }

                // Reject overflows (e.g. month 13, day 32, Feb 30)
                $errors = Carbon::getLastErrors();
                if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }

                return $date->format($outputFormat);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: Carbon::parse handles many natural formats (e.g. "30-Apr-25", "April 30 2025")
        // Only safe for strings that contain at least one letter (unambiguous month name present)
        // to avoid Carbon::parse silently misinterpreting pure-numeric date strings.
        if (preg_match('/[a-zA-Z]/', $str)) {
            try {
                $date = Carbon::parse($str);
                $errors = Carbon::getLastErrors();
                if (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                    return $date->format($outputFormat);
                }
            } catch (\Exception $e) {
                // Fall through
            }
        }

        return '__INVALID_DATE__';
    }

    /**
     * Provided By Mohammed AI Crunch
     * ✅ Parse a time value from any common format and return it as H:i:s.
     *    Returns null for empty values.
     *    Returns '__INVALID_TIME__' if the value cannot be parsed.
     */
    public function smartParseTime($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }
        if ($value instanceof DateTime) {
            return Carbon::instance($value)->format('H:i:s');
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        // Numeric value → Excel time fraction (e.g. 0.3058 = 07:20:21)
        // Excel stores time as a fraction of a 24-hour day.
        // Maatwebsite Excel passes raw fractions for time-formatted cells in ToCollection mode.
        if (is_int($value) || is_float($value) || is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->format('H:i:s');
            } catch (\Exception $e) {
                // Fall through to string parsing
            }
        }

        $str = trim((string) $value);

        $formats = [
            'H:i:s',
            'H:i',
            'G:i:s',
            'G:i',
            'g:i:s A',
            'g:i A',
            'g:i:s a',
            'g:i a',
        ];

        foreach ($formats as $fmt) {
            try {
                $time = Carbon::createFromFormat($fmt, $str);
                if ($time === false) {
                    continue;
                }

                $errors = Carbon::getLastErrors();
                if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }

                return $time->format('H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }

        return '__INVALID_TIME__';
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