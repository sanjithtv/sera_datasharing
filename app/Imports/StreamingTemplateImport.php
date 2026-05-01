<?php

namespace App\Imports;

use App\Models\LicenseeTemplateKey;
use App\Models\LicenseeTemplateSheet;
use App\Traits\ImportProcessorTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class StreamingTemplateImport
{
    use ImportProcessorTrait;

    protected $assessmentId;
    protected $licenseeId;
    protected $licenseeTemplateId;
    protected $sheetMapping;
    protected $isCsv;
    protected $entryCounter = 1;
    protected $globalProcessedCount = 0;

    public $canProceed = true;
    public $errorsPerSheet = [];
    public $namePerSheet = [];
    protected $headers = [];

    public function __construct($assessmentId, $licenseeId, $licenseeTemplateId, $sheetMapping, $isCsv = false)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId = $licenseeId;
        $this->licenseeTemplateId = $licenseeTemplateId;
        $this->sheetMapping = $sheetMapping;
        $this->isCsv = $isCsv;
    }

    public function import($filePath)
    {
        $startTime = microtime(true);

        Log::info('=== StreamingTemplateImport START ===', [
            'assessment_id'       => $this->assessmentId,
            'licensee_id'         => $this->licenseeId,
            'licensee_template_id'=> $this->licenseeTemplateId,
            'is_csv'              => $this->isCsv,
            'sheet_mapping_keys'  => array_keys($this->sheetMapping),
            'sheet_mapping_ids'   => array_values($this->sheetMapping),
            'file_path'           => is_string($filePath) ? $filePath : get_class($filePath),
        ]);

        if ($this->isCsv) {
            $options = new CsvOptions();
            $options->FIELD_DELIMITER = $this->detectDelimiter($filePath);
            $reader = new CsvReader($options);
            Log::info("CSV Import using delimiter: '{$options->FIELD_DELIMITER}'");
        } else {
            $reader = ReaderFactory::createFromFileByMimeType($filePath);
        }

        $reader->open($filePath);

        $sheetsFound = [];
        $sheetsProcessed = [];

        // Create a normalized mapping for comparison
        $normalizedMapping = [];
        foreach ($this->sheetMapping as $name => $id) {
            $normalizedMapping[strtolower(preg_replace('/\s+/', ' ', trim((string)$name)))] = $id;
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetName = (string)$sheet->getName();
            $sheetsFound[] = $sheetName;

            if ($this->isCsv && empty($sheetName)) {
                $sheetName = 'CSV';
            }

            // For CSV, we only have one sheet. For XLSX, we match against mapping using normalized names.
            // Normalize by trimming and collapsing multiple internal spaces to a single space.
            $normSheetName = strtolower(preg_replace('/\s+/', ' ', trim($sheetName)));
            $sheetId = $this->isCsv ? reset($this->sheetMapping) : ($normalizedMapping[$normSheetName] ?? null);

            if (!$sheetId || $sheetName === 'Master') {
                Log::warning("Skipping sheet '{$sheetName}': no mapping found or it is the Master sheet.", [
                    'sheetMapping_keys' => array_keys($this->sheetMapping),
                    'is_master'         => ($sheetName === 'Master'),
                    'resolved_sheetId'  => $sheetId,
                    'norm_sheetName'    => $normSheetName,
                ]);
                continue;
            }

            $sheetsProcessed[] = $sheetName;

            Log::info("--- Processing sheet: '{$sheetName}' ---", [
                'sheet_id'     => $sheetId,
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            ]);

            // ✅ CRITICAL: Reset per-sheet caches so Sheet 2 doesn't inherit Sheet 1's column mapping
            $this->cachedEncodedHeaders = null;
            $this->preprocessedKeys     = null;
            $this->batchTimestamp       = null;
            $this->detectedDateFormat   = null;
            $this->detectedTimeFormat   = null;
            $this->headers              = [];

            // ✅ Always register this sheet so it appears in preview tabs even when there are zero errors
            $this->errorsPerSheet[$sheetName] = [];
            $this->namePerSheet[$sheetName] = $sheetId;

            $keys = LicenseeTemplateKey::where('licensee_template_id', $this->licenseeTemplateId)
                ->where('sheet_id', $sheetId)
                ->orderBy('id')
                ->get();

            Log::info("Template keys loaded for sheet '{$sheetName}'", [
                'count'       => $keys->count(),
                'short_codes' => $keys->pluck('short_code')->toArray(),
            ]);

            $rowCount = 0;
            $headerMappingLog = [];
            $headerFound = false;
            $dataRowsFound = 0;

            foreach ($sheet->getRowIterator() as $openSpoutRow) {
                $rowData = [];
                foreach ($openSpoutRow->getCells() as $cell) {
                    $cellValue = null;
                    if ($cell instanceof \OpenSpout\Common\Entity\Cell\FormulaCell) {
                        $cellValue = $cell->getComputedValue();
                    } else {
                        $cellValue = $cell->getValue();
                    }
                    
                    // Prevent Exception: Object of class DateTimeImmutable could not be converted to string
                    if ($cellValue instanceof \DateTimeInterface) {
                        $cellValue = $cellValue->format('Y-m-d H:i:s');
                    }
                    
                    $rowData[] = $cellValue;
                }

                if (!$headerFound) {
                    // =====================================================
                    // HEADER DETECTION & DYNAMIC COLUMN INDEX MAPPING
                    // =====================================================
                    $rawHeaders = array_map(function($h) {
                        if ($h instanceof \DateTimeInterface) return $h->format('Y-m-d');
                        return Str::slug(trim((string)$h), '_');
                    }, $rowData);

                    $rawHeadersOriginal = array_map(function($h) {
                        if ($h instanceof \DateTimeInterface) return $h->format('Y-m-d');
                        return trim((string)$h);
                    }, $rowData);

                    $expectedSlugs = $keys->pluck('short_code')->map(fn ($k) => Str::slug($k, '_'))->toArray();
                    $matchedCount = 0;
                    foreach ($expectedSlugs as $expected) {
                        if (in_array($expected, $rawHeaders, true)) {
                            $matchedCount++;
                        }
                    }

                    // Require an 80% match of the expected columns to confidently identify the header row.
                    // This prevents metadata rows (like "Field description in English") from being falsely flagged as the header.
                    $requiredToMatch = max(1, (int)ceil(count($expectedSlugs) * 0.8));
                    if ($matchedCount < $requiredToMatch) {
                        // Skip this row as it doesn't look like a header
                        $rowCount++;
                        continue;
                    }

                    $headerFound = true;
                    Log::info("Header row identified at row {$rowCount} in sheet '{$sheetName}'.");

                    Log::info("Raw headers detected for sheet '{$sheetName}'", [
                        'count'           => count($rawHeaders),
                        'slugged_headers'  => $rawHeaders,
                        'original_headers' => $rawHeadersOriginal,
                    ]);

                    // Match template keys to Excel columns by slugified name
                    $this->headers = [];
                    $slugToHuman = [];
                    foreach ($rowData as $h) {
                        $slug = Str::slug(trim((string)$h), '_');
                        $slugToHuman[$slug] = trim((string)$h);
                    }

                    // Detect the S.No column in the file. It is intentionally
                    // NOT registered as a template_key — captured separately as
                    // a backend-only row identifier for cross-template dedup.
                    $this->setSnoColumnIndex(self::detectSnoColumnIndex($rawHeaders));

                    $matchedCount  = 0;
                    $missingKeys   = [];

                    foreach ($keys as $key) {
                        $sluggedShortCode = Str::slug($key->short_code, '_');
                        $foundIndex       = array_search($sluggedShortCode, $rawHeaders);

                        if ($foundIndex !== false) {
                            $key->excel_index = $foundIndex;
                            $humanName = $slugToHuman[$sluggedShortCode] ?? $key->short_code;
                            $this->headers[$humanName] = $key->short_code;
                            $matchedCount++;

                            $headerMappingLog[] = [
                                'short_code'   => $key->short_code,
                                'excel_index'  => $foundIndex,
                                'human_name'   => $humanName,
                                'status'       => 'matched',
                            ];
                        } else {
                            // Column not found in Excel — will produce validation error for mandatory columns
                            Log::warning("Header '{$key->short_code}' NOT FOUND in sheet '{$sheetName}'", [
                                'slugged_expected' => $sluggedShortCode,
                                'available_slugs'  => $rawHeaders,
                            ]);
                            $this->headers[$key->short_code] = $key->short_code;
                            $missingKeys[] = $key->short_code;

                            $headerMappingLog[] = [
                                'short_code'  => $key->short_code,
                                'excel_index' => null,
                                'status'      => 'MISSING',
                            ];
                        }
                    }

                    Log::info("Header mapping summary for sheet '{$sheetName}'", [
                        'total_template_keys' => $keys->count(),
                        'matched'             => $matchedCount,
                        'missing_keys'        => $missingKeys,
                        'mapping_detail'      => $headerMappingLog,
                    ]);

                    $headerErrors = $this->validateHeaders($rawHeaders, $keys);

                    if (!empty($headerErrors)) {
                        Log::error("Header validation FAILED for sheet '{$sheetName}'", [
                            'errors' => $headerErrors,
                        ]);
                        $this->canProceed = false;
                        $this->errorsPerSheet[$sheetName][] = [
                            'type'    => 'header_validation',
                            'message' => 'Excel columns do not match template definition.',
                            'errors'  => $headerErrors,
                        ];

                        // Ensure header validation error is persisted to DB so async jobs can show it
                        \App\Models\SlaveMasterData::create([
                            'assessment_id'      => $this->assessmentId,
                            'licensee_id'        => $this->licenseeId,
                            'template_id'        => $this->licenseeTemplateId,
                            'headers'            => json_encode($this->headers),
                            'validation_errors'  => json_encode([
                                '__header_error' => ['The uploaded Excel columns do not match the configured template for this sheet. Missing: ' . implode(', ', $missingKeys)]
                            ]),
                            'row_index'          => 0, // 0 represents a sheet-level error
                            'status'             => 'pending',
                            'sheet_id'           => $sheetId,
                            'row_hash'           => md5($sheetName . '_header_error'),
                        ]);

                        break; // Stop sheet processing on heavy header error
                    }

                } else {
                    // =====================================================
                    // DATA ROW PROCESSING
                    // =====================================================

                    // Only process if the row is not completely empty
                    $nonEmptyCells = array_filter($rowData, fn($v) => !is_null($v) && $v !== '');

                    if (!empty($nonEmptyCells)) {
                        if ($rowCount <= 3) {
                            Log::debug("Sheet '{$sheetName}' row {$rowCount} raw data (first 3 rows logged)", [
                                'row_data' => $rowData,
                            ]);
                        }

                        $this->processRow(
                            $rowData,
                            $rowCount,
                            $keys,
                            $this->assessmentId,
                            $this->licenseeId,
                            $this->licenseeTemplateId,
                            $sheetId,
                            $sheetName,
                            $this->entryCounter
                        );
                        $dataRowsFound++;
                    }

                    $this->globalProcessedCount++;

                    // Update DB on first row and every 1000 rows thereafter for smooth UX
                    if ($this->globalProcessedCount === 1 || $this->globalProcessedCount % 1000 === 0) {
                        $updated = \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                            ->where('id', $this->assessmentId)
                            ->update(['processed_rows' => $this->globalProcessedCount]);

                        Log::info("StreamingTemplateImport: Progress update (Direct DB)", [
                            'assessment_id' => $this->assessmentId,
                            'processed'     => $this->globalProcessedCount,
                            'db_updated'    => $updated,
                            'sheet_row'     => $rowCount
                        ]);
                    }
                }
                $rowCount++;
            }

            // Check if the sheet was completely empty (no headers ever found) or has no data
            if (!$headerFound || $dataRowsFound === 0) {
                $reasonMsg = !$headerFound 
                    ? "is completely empty or missing required headers." 
                    : "contains headers but no data rows.";
                    
                Log::warning("Sheet '{$sheetName}' {$reasonMsg}");
                $this->canProceed = false;

                $expectedCols = $keys->pluck('short_code')->unique()->values()->toArray();

                $expectedSheetName = array_search($sheetId, $this->sheetMapping);
                $displaySheetName = ($expectedSheetName && strtolower(trim((string)$expectedSheetName)) !== strtolower(trim((string)$sheetName))) 
                    ? "{$sheetName}' (mapped to '{$expectedSheetName}')"
                    : $sheetName;

                $this->errorsPerSheet[$sheetName][] = [
                    'type'    => 'empty_sheet',
                    'message' => "The sheet '{$displaySheetName}' {$reasonMsg}",
                    'missing_columns' => $expectedCols
                ];

                // Persist the empty sheet error
                \App\Models\SlaveMasterData::create([
                    'assessment_id'      => $this->assessmentId,
                    'licensee_id'        => $this->licenseeId,
                    'template_id'        => $this->licenseeTemplateId,
                    'headers'            => json_encode([]),
                    'row_data'           => json_encode([]),
                    'validation_errors'  => json_encode([
                        '__empty_sheet' => [
                            'message' => "The sheet '{$displaySheetName}' {$reasonMsg}",
                            'missing_columns' => $expectedCols
                        ]
                    ]),
                    'row_index'          => 0, // 0 represents a sheet-level error
                    'status'             => 'pending',
                    'sheet_id'           => $sheetId,
                    'row_hash'           => md5($sheetName . '_empty_sheet'),
                ]);
            }

            // Final flush for the sheet
            $this->flushBuffer($sheetName);
            
            // Sync final count for the sheet
            $sheetCount = \App\Models\SlaveMasterData::where('assessment_id', $this->assessmentId)->count();
            \Illuminate\Support\Facades\DB::table('sr_licensee_assessments')
                ->where('id', $this->assessmentId)
                ->update(['processed_rows' => $sheetCount]);

            Log::info("Finished streaming sheet: '{$sheetName}'", [
                'total_rows'  => $rowCount - 1,
                'duration'    => round(microtime(true) - $startTime, 2) . 's',
                'can_proceed' => $this->canProceed,
            ]);
        }

        $reader->close();

        // Check for completely missing sheets based on the mapping (using normalized names)
        // ✅ For CSV files, we skip this check because a CSV only ever has one data stream
        // and we already assigned it to the first mapping entry in the loop above.
        if ($this->isCsv) {
            Log::info('StreamingTemplateImport: CSV missing sheet check bypassed.');
            return;
        }

        $processedNormNames = array_map(fn($n) => strtolower(trim((string)$n)), $sheetsProcessed);
        
        foreach ($this->sheetMapping as $mappedName => $mappedId) {
            $normMappedName = strtolower(trim((string)$mappedName));
            
            if (!in_array($normMappedName, $processedNormNames)) {
                $this->canProceed = false;
                $this->errorsPerSheet[$mappedName][] = [
                    'type'    => 'missing_sheet',
                    'message' => "The required sheet '{$mappedName}' is missing from the uploaded file.",
                ];
                $this->namePerSheet[$mappedName] = $mappedId;

                // Persist the missing sheet error
                \App\Models\SlaveMasterData::create([
                    'assessment_id'      => $this->assessmentId,
                    'licensee_id'        => $this->licenseeId,
                    'template_id'        => $this->licenseeTemplateId,
                    'headers'            => json_encode([]),
                    'row_data'           => json_encode([]),
                    'validation_errors'  => json_encode([
                        '__missing_sheet' => ["The required sheet '{$mappedName}' is missing from the uploaded file."]
                    ]),
                    'row_index'          => 0, // 0 represents a sheet-level error
                    'status'             => 'pending',
                    'sheet_id'           => $mappedId,
                    'row_hash'           => md5($mappedName . '_missing_sheet'),
                ]);

                Log::warning("Required sheet '{$mappedName}' was completely missing from the file (normalized check).");
            }
        }

        Log::info('=== StreamingTemplateImport DONE ===', [
            'sheets_found_in_file' => $sheetsFound,
            'sheets_processed'     => $sheetsProcessed,
            'sheets_skipped'       => array_values(array_diff($sheetsFound, $sheetsProcessed)),
            'mapping_keys'         => array_keys($this->sheetMapping),
            'can_proceed'          => $this->canProceed,
            'errors_per_sheet'     => array_map('count', $this->errorsPerSheet),
            'total_duration'       => round(microtime(true) - $startTime, 2) . 's',
            'memory_usage'         => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        ]);
    }
}
