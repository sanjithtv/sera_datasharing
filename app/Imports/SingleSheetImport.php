<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use App\Models\SlaveMasterData;
use App\Models\TemplateSheet;
use App\Models\LicenseeTemplateKey;
use Illuminate\Support\Facades\Validator;

class SingleSheetImport implements ToArray
{
    private $assessmentId;
    private $licenseeId;
    private $sheetName;
    private $templateId;
    private $parent;

    public function __construct($assessmentId, $licenseeId, $sheetName, $templateId, $parent)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId   = $licenseeId;
        $this->sheetName    = $sheetName;
        $this->templateId   = $templateId;
        $this->parent       = $parent;
    }

    public function array(array $rows)
    {
        if (count($rows) < 2) {
            return;
        }

        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);


        $sheetRes = TemplateSheet::where('template_id', $this->templateId)->where('sheet_name',$this->sheetName)->first();

        $templateKeys = LicenseeTemplateKey::where('licensee_template_id', $this->templateId)->where('sheet_id',$sheetRes->id)->get();

        $batch = [];
        $rowIndex = 1;

        foreach ($dataRows as $row) {
            $rowIndex++;

            $validationErrors = $this->validateRow($templateKeys, $headers, $row);

            if (!empty($validationErrors)) {
                $this->parent->canProceed = false;
            }

            $this->parent->errorsPerSheet[$this->sheetName][$rowIndex] = $validationErrors;
            $this->parent->namePerSheet[$this->sheetName] = $sheetRes->id;

            $batch[] = [
                'assessment_id' => $this->assessmentId,
                'licensee_id'   => $this->licenseeId,
                'sheet_id'    => $sheetRes->id,
                'template_id'   => $this->templateId,

                'headers'        => json_encode($headers),
                'row_data'       => json_encode($row),
                'validation_errors' => json_encode($validationErrors),

                'row_index'     => $rowIndex,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        // Batch insert
        SlaveMasterData::insert($batch);
    }

    private function validateRow($templateKeys, $headers, $row)
    {
        $errors = [];

        foreach ($templateKeys as $key) {

            $columnIndex = array_search($key->short_code, $headers);
            $value       = $columnIndex !== false ? ($row[$columnIndex] ?? null) : null;

            $rules = $key->mandatory ? ['required'] : ['nullable'];

            if ($key->type == 'number') {
                $rules[] = 'numeric';
            }

            if ($key->type == 'percentage') {
                $rules[] = 'numeric';
                $rules[] = 'between:0,100';
            }

            if ($key->type == 'date') {
                // Accept various date formats if Excel 
                $rules[] = 'date';
            }

            if ($key->type == 'datetime') {
                // Strict datetime validation
                // Adjust format if your UI uses different format (e.g., Y-m-d H:i)
                $rules[] = 'date_format:Y-m-d H:i:s';
            }

            $validator = Validator::make(['v' => $value], ['v' => $rules]);

            if ($validator->fails()) {
                $errors[$key->short_code] = $validator->errors()->all();
            }
        }

        return $errors;
    }
}
