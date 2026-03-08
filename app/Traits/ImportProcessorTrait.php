<?php

namespace App\Traits;

use App\Models\SlaveMasterData;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait ImportProcessorTrait
{
    protected $buffer = [];
    protected $cachedEncodedHeaders = null;
    protected $preprocessedKeys = null;
    protected $batchTimestamp = null;
    protected $detectedDateFormat = null;
    protected $detectedTimeFormat = null;
    
    /**
     * ✅ Validate headers against template keys
     */
    public function validateHeaders(array $excelHeaders, $keys): array
    {
        $excelHeaders = collect($excelHeaders)
            ->map(fn ($h) => Str::slug(trim($h), '_'))
            ->filter()
            ->values()
            ->toArray();

        $expectedKeys = $keys
            ->pluck('short_code')
            ->map(fn ($k) => Str::slug($k, '_'))
            ->toArray();

        $missing = array_values(array_diff($expectedKeys, $excelHeaders));

        $errors = [];
        if (!empty($missing)) $errors['missing_columns'] = $missing;

        return $errors;
    }

    /**
     * ✅ Core row processing logic (Highly Optimized)
     */
    protected function processRow(array $row, int $rowIndex, $keys, $assessmentId, $licenseeId, $licenseeTemplateId, $sheetId, $sheetName, &$entryCounter)
    {
        // 1. Initialise cache on first row
        if ($this->cachedEncodedHeaders === null) {
            $this->cachedEncodedHeaders = json_encode($this->headers);
            $this->batchTimestamp = now()->toDateTimeString();
            
            // Pre-process keys once to avoid repeated logic in the loop
            $this->preprocessedKeys = [];
            foreach ($keys as $index => $key) {
                $this->preprocessedKeys[] = [
                    'index'      => $key->excel_index ?? $index,
                    'short_code' => $key->short_code,
                    'type'       => $key->type,
                    'mandatory'  => $key->mandatory == 1,
                ];
            }

            Log::info("ImportProcessorTrait@processRow: First-row key cache built for sheet '{$sheetName}'", [
                'row_index'        => $rowIndex,
                'preprocessed_keys'=> array_map(fn($k) => [
                    'short_code' => $k['short_code'],
                    'excel_index'=> $k['index'],
                    'type'       => $k['type'],
                    'mandatory'  => $k['mandatory'],
                ], $this->preprocessedKeys),
                'row_total_cells'  => count($row),
                'first_3_cells'    => array_slice($row, 0, 3),
            ]);
        }

        $mapped = [];
        $validationErrors = [];
        $isPending = false;

        // 2. Fast Processing & Manual Validation
        foreach ($this->preprocessedKeys as $pKey) {
            $value = $row[$pKey['index']] ?? null;
            $colName = $pKey['short_code'];

            // Normalisation & Type Checks
            switch ($pKey['type']) {
                case 'number':
                case 'number_percentage':
                    if ($value !== null && $value !== '') {
                        // Safe conversion to string for objects like DateTimeImmutable
                        $strValue = ($value instanceof \DateTimeInterface) ? $value->format('Y-m-d') : (string)$value;
                        
                        // Strip commas (thousands separators) so is_numeric can validate correctly
                        $cleanValue = str_replace(',', '', $strValue);
                        
                        if (!is_numeric($cleanValue)) {
                            $validationErrors[$colName][] = "The $colName must be a number.";
                        } else {
                            $value = (float) $cleanValue;
                        }
                    }
                    break;

                case 'text':
                case 'select':
                    if ($value !== null) {
                        $value = ($value instanceof \DateTimeInterface) ? $value->format('Y-m-d') : (string)$value;
                    }
                    break;

                case 'date':
                case 'datetime':
                    $format = ($pKey['type'] === 'date') ? 'Y-m-d' : 'Y-m-d H:i:s';
                    $parsed = $this->smartParseDate($value, $format);
                    if ($parsed !== null) {
                        if ($parsed === '__INVALID_DATE__') {
                            $validationErrors[$colName][] = "The $colName is not a valid date.";
                        } else {
                            $value = $parsed;
                        }
                    }
                    break;

                case 'time':
                    $parsed = $this->smartParseTime($value);
                    if ($parsed !== null) {
                        if ($parsed === '__INVALID_TIME__') {
                            $validationErrors[$colName][] = "The $colName is not a valid time.";
                        } else {
                            $value = $parsed;
                        }
                    }
                    break;
            }

            if ($pKey['mandatory'] && ($value === null || $value === '')) {
                $validationErrors[$colName][] = "The $colName field is required.";
            }

            $mapped[$colName] = $value;
        }

        if (!empty($validationErrors)) {
            $isPending = true;
            $this->canProceed = false;
            $this->errorsPerSheet[$sheetName][$rowIndex] = $validationErrors;
        }

        // Log first 3 rows per sheet to verify mapping is correct
        if ($rowIndex <= 3) {
            Log::debug("ImportProcessorTrait: Row {$rowIndex} mapped data for sheet '{$sheetName}'", [
                'mapped'            => $mapped,
                'validation_errors' => $validationErrors,
                'raw_row_snippet'   => array_slice($row, 0, 5),
            ]);
        }

        // 3. Calculate Fingerprint (Hash) for Duplicate Detection
        $rowHash = md5(json_encode($mapped));

        // 4. Buffer Data
        $this->buffer[] = [
            'assessment_id'      => $assessmentId,
            'licensee_id'        => $licenseeId,
            'template_id'        => $licenseeTemplateId,
            'headers'            => $this->cachedEncodedHeaders,
            'row_data'           => json_encode($mapped),
            'validation_errors'  => $isPending ? json_encode($validationErrors) : json_encode([]),
            'row_index'          => $rowIndex,
            'status'             => $isPending ? 'pending' : 'processed',
            'sheet_id'           => $sheetId,
            'row_hash'           => $rowHash,
            'created_at'         => $this->batchTimestamp,
            'updated_at'         => $this->batchTimestamp,
        ];

        $entryCounter++;

        if (count($this->buffer) >= 500) {
            $this->flushBuffer($sheetName);
        }
    }

    protected function flushBuffer($sheetName)
    {
        if (empty($this->buffer)) return;

        $start = microtime(true);
        SlaveMasterData::insert($this->buffer);
        Log::info("Bulk insert finished for sheet '{$sheetName}'", [
            'row_count' => count($this->buffer),
            'duration' => microtime(true) - $start
        ]);
        $this->buffer = [];
    }

    public function smartParseDate($value, string $outputFormat = 'Y-m-d'): ?string
    {
        if ($value instanceof Carbon) return $value->format($outputFormat);
        if ($value instanceof DateTime) return Carbon::instance($value)->format($outputFormat);
        if ($value === null || trim((string) $value) === '') return null;

        if (is_numeric($value)) {
            $floatVal = (float) $value;
            if ($floatVal < 1) return '__INVALID_DATE__';
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($floatVal);
                return Carbon::instance($dt)->format($outputFormat);
            } catch (\Exception $e) {}
        }

        $str = trim((string) $value);

        // Try memoized format first
        if ($this->detectedDateFormat) {
            try {
                $date = Carbon::createFromFormat($this->detectedDateFormat, $str);
                if ($date && Carbon::getLastErrors()['warning_count'] === 0) return $date->format($outputFormat);
            } catch (\Exception $e) {}
        }

        $formats = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'm-d-Y', 'm/d/Y', 'd-M-y', 'd-M-Y'];
        foreach ($formats as $fmt) {
            try {
                $date = Carbon::createFromFormat($fmt, $str);
                if ($date && Carbon::getLastErrors()['warning_count'] === 0) {
                    $this->detectedDateFormat = $fmt; // Memoize for next row
                    return $date->format($outputFormat);
                }
            } catch (\Exception $e) {}
        }

        try {
            return Carbon::parse($str)->format($outputFormat);
        } catch (\Exception $e) {}

        return '__INVALID_DATE__';
    }

    public function smartParseTime($value): ?string
    {
        if ($value instanceof Carbon) return $value->format('H:i:s');
        if ($value instanceof DateTime) return Carbon::instance($value)->format('H:i:s');
        if ($value === null || trim((string) $value) === '') return null;

        if (is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->format('H:i:s');
            } catch (\Exception $e) {}
        }

        $str = trim((string) $value);

        // Try memoized format first
        if ($this->detectedTimeFormat) {
            try {
                $time = Carbon::createFromFormat($this->detectedTimeFormat, $str);
                if ($time) return $time->format('H:i:s');
            } catch (\Exception $e) {}
        }

        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A'];
        foreach ($formats as $fmt) {
            try {
                $time = Carbon::createFromFormat($fmt, $str);
                if ($time) {
                    $this->detectedTimeFormat = $fmt; // Memoize for next row
                    return $time->format('H:i:s');
                }
            } catch (\Exception $e) {}
        }

        return '__INVALID_TIME__';
    }

    /**
     * ✅ Simple delimiter detection for CSV files
     */
    public function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) return ',';

        $firstLine = fgets($handle);
        fclose($handle);

        if (!$firstLine) return ',';

        $delimiters = [',', ';', "\t", '|'];
        $counts = [];
        foreach ($delimiters as $delim) {
            $counts[$delim] = substr_count($firstLine, $delim);
        }

    arsort($counts);
        return array_key_first($counts) ?: ',';
    }


}
