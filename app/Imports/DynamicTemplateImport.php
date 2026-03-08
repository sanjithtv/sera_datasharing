<?php
namespace App\Imports;

use App\Models\LicenseeTemplateKey;
use App\Models\SlaveMasterData;
use App\Models\LicenseeTemplateSheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use App\Traits\ImportProcessorTrait;

class DynamicTemplateImport implements WithMultipleSheets, WithCalculatedFormulas, WithReadFilter
{
    use ImportProcessorTrait;
    protected $assessmentId;
    protected $licenseeId;
    protected $licenseeTemplateId;
    protected $sheetMapping;   // ['sheet_name' => sheet_id]
    protected $maxDataRows;    // ['sheet_name' => last_row_index]
    protected $isCsv;
    protected $entryCounter = 1;
    public $canProceed = true;
    public $errorsPerSheet = [];
    public $namePerSheet = [];

    public function __construct($assessmentId, $licenseeId,$licenseeTemplateId, $sheetMapping, $maxDataRows = [], $isCsv = false)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId   = $licenseeId;
        $this->licenseeTemplateId = $licenseeTemplateId;
        $this->sheetMapping = $sheetMapping;
        $this->maxDataRows  = $maxDataRows;
        $this->isCsv        = $isCsv;
    }

    /**
     * ✅ Optimized Read Filter
     * Prevents reading thousands of trailing empty rows at the reader level.
     */
    public function readFilter(): IReadFilter
    {
        return new class($this->maxDataRows) implements IReadFilter {
            protected $maxDataRows;
            public function __construct($maxDataRows) {
                $this->maxDataRows = $maxDataRows;
            }
            public function readCell($columnAddress, $row, $worksheetName = ''): bool {
                // Keep header (row 1) and all rows up to detected last data row
                // Default to 100,000 for cases like CSV where maxDataRows is empty
                return $row <= ($this->maxDataRows[$worksheetName] ?? 100000);
            }
        };
    }

    public function sheets(): array
    {
        Log::info("DynamicTemplateImport@sheets triggered", [
            'sheet_mapping' => $this->sheetMapping,
            'max_data_rows' => $this->maxDataRows,
            'is_csv' => $this->isCsv
        ]);
        $imports = [];

        $sheetIndex = 0;
        foreach ($this->sheetMapping as $sheetName => $sheetId) {
            if ($sheetName === 'Master') {
                continue;
            }

            // ✅ Initialize error bag per sheet
            $this->errorsPerSheet[$sheetName] = [];

            // ✅ Load template keys dynamically (column-wise)
            $keys = LicenseeTemplateKey::where('licensee_template_id', $this->licenseeTemplateId)
                ->where('sheet_id', $sheetId)
                ->orderBy('id')
                ->get();

            $parent = $this;
            $import = new class(
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
                protected $licenseeTemplateId;
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
                    $sheetStartTime = microtime(true);
                    Log::info("Processing sheet: {$this->sheetName}", ['total_rows' => count($rows), 'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB']);

                    $rowsProcessed = 0;

                    // Search for the header row dynamically
                    $headerRowIndex = 0;
                    $headerFound = false;
                    $rawHeaders = [];

                    foreach ($rows as $index => $row) {
                        $rowData = [];
                        foreach ($row as $cell) {
                            $val = $cell instanceof Cell ? $cell->getCalculatedValue() : $cell;
                            if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                                $val = $val->getPlainText();
                            }
                            $rowData[] = is_string($val) ? trim($val) : $val;
                        }

                        $rawHeadersStr = array_map(fn($h) => \Illuminate\Support\Str::slug(trim((string)$h), '_'), $rowData);
                        $expectedSlugs = $this->keys->pluck('short_code')->map(fn ($k) => \Illuminate\Support\Str::slug($k, '_'))->toArray();
                        
                        $matchedCount = 0;
                        foreach ($expectedSlugs as $expected) {
                            if (in_array($expected, $rawHeadersStr, true)) {
                                $matchedCount++;
                            }
                        }

                        // Require an 80% match of the expected columns to confidently identify the header row.
                        $requiredToMatch = max(1, (int)ceil(count($expectedSlugs) * 0.8));
                        if ($matchedCount >= $requiredToMatch) {
                            $headerFound = true;
                            $headerRowIndex = $index;
                            $rawHeaders = $rawHeadersStr;
                            
                            $this->headers = collect($rowData)
                                ->filter(fn($v) => !is_null($v) && $v !== '')
                                ->values()
                                ->toArray();
                            break;
                        }
                    }

                    if (!$headerFound) {
                        Log::warning("Header row not found in sheet: {$this->sheetName}. Assuming row 0 is header.");
                        // Fallback to row 0 if we couldn't confidently find a header
                        $rowData = [];
                        if (isset($rows[0])) {
                            foreach ($rows[0] as $cell) {
                                $val = $cell instanceof Cell ? $cell->getCalculatedValue() : $cell;
                                if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) $val = $val->getPlainText();
                                $rowData[] = is_string($val) ? trim($val) : $val;
                            }
                        }
                        $rawHeaders = array_map(fn($h) => \Illuminate\Support\Str::slug(trim((string)$h), '_'), $rowData);
                    }

                    // Map column indices
                    foreach ($this->keys as $key) {
                        $sluggedShortCode = \Illuminate\Support\Str::slug($key->short_code, '_');
                        $foundIndex = array_search($sluggedShortCode, $rawHeaders);
                        if ($foundIndex !== false) {
                            $key->excel_index = $foundIndex;
                        }
                    }

                    $headerErrors = $this->parent->validateHeaders(
                        $this->headers ?? [],
                        $this->keys
                    );

                    if (!empty($headerErrors)) {
                        Log::warning("Header validation failed for sheet: {$this->sheetName}", ['errors' => $headerErrors]);
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

                        if ($rowIndex <= $headerRowIndex) continue; // ✅ Skip header and rows before it

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
                            $rowsProcessed++;
                            
                            $rawRow = [];
                            foreach ($row as $cell) {
                                try {
                                    $value = $cell instanceof Cell ? $cell->getCalculatedValue() : $cell;
                                } catch (\PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                                    $value = ($cell instanceof Cell) ? $cell->getOldCalculatedValue() : null;
                                }

                                if ($cell instanceof Cell && is_string($value) && in_array(strtoupper(trim($value)), $excelErrorStrings)) {
                                    $cached = $cell->getOldCalculatedValue();
                                    $value = ($cached !== null && !(is_string($cached) && in_array(strtoupper(trim($cached)), $excelErrorStrings)))
                                        ? $cached : null;
                                }

                                if ($value instanceof RichText) {
                                    $value = $value->getPlainText();
                                }

                                if ($cell instanceof Cell && $value !== null && Date::isDateTime($cell)) {
                                    try {
                                        $value = Carbon::instance(Date::excelToDateTimeObject($value));
                                    } catch (\Exception $e) { $value = null; }
                                }

                                if (is_string($value)) {
                                    $value = trim(html_entity_decode($value));
                                }
                                $rawRow[] = ($value === '') ? null : $value;
                            }

                            // Use the Trait's processRow
                            $this->parent->processRow(
                                $rawRow,
                                $rowIndex,
                                $this->keys,
                                $this->assessmentId,
                                $this->licenseeId,
                                $this->licenseeTemplateId,
                                $this->sheetId,
                                $this->sheetName,
                                $this->entryCounter
                            );

                            if ($rowsProcessed % 1000 === 0) {
                                Log::info("Sheet '{$this->sheetName}' progress: {$rowsProcessed} rows processed", [
                                    'time_elapsed' => microtime(true) - $sheetStartTime,
                                    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
                                ]);
                            }
                        }
                    }
                    $this->parent->flushBuffer($this->sheetName);
                    Log::info("Finished processing sheet: {$this->sheetName}", [
                        'total_rows_processed' => $rowsProcessed,
                        'total_duration' => microtime(true) - $sheetStartTime
                    ]);
                }
            };

            if ($this->isCsv) {
                $imports[0] = $import;
                Log::info("CSV Mode: Mapping sheet handler to index 0");
                break; // Only one sheet for CSV
            } else {
                $imports[$sheetName] = $import;
            }
            $sheetIndex++;
        }

        Log::info("DynamicTemplateImport@sheets returning " . count($imports) . " sheet handlers", ['sheet_names' => array_keys($imports)]);
        return $imports;
    }

    /**
     * ✅ Validate Excel headers against LicenseeTemplateKey
     * DEPRECATED: Now handled by ImportProcessorTrait
     */


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
