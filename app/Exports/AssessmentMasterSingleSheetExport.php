<?php

namespace App\Exports;

use App\Models\AssessmentMasterData;
use App\Models\LicenseeTemplateSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class AssessmentMasterSingleSheetExport implements FromArray, WithTitle
{

    protected $assessmentId;
    protected $sheet;

    public function __construct($assessmentId, $sheet)
    {
        $this->assessmentId = $assessmentId;
        $this->sheet = $sheet;
    }

    public function array(): array
    {
        // Fetch master data for this sheet
        $rows = AssessmentMasterData::where('assessment_id', $this->assessmentId)
            ->where('template_sheet_id', $this->sheet->id)
            ->orderBy('entry_counter')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $exportData = [];

        /*
        |--------------------------------------------------------------------------
        | HEADER ROW  (Same as Blade → $sheet->keys.short_code)
        |--------------------------------------------------------------------------
        */
        $headers = [];

        foreach ($this->sheet->keys as $key) {
            $headers[] = $key->short_code;
        }

        $exportData[] = $headers;

        /*
        |--------------------------------------------------------------------------
        | GROUP BY ENTRY COUNTER
        |--------------------------------------------------------------------------
        */
        $grouped = $rows->groupBy('entry_counter');

        foreach ($grouped as $entryCounter => $entries) {
            foreach ($entries as $entry) {
                $row = [];
                $rowData [$entry->template_key_id]= $entry->template_key_value;
                // If JSON stored
                if (is_string($rowData)) {
                    $rowData = json_decode($rowData, true);
                }

                foreach ($this->sheet->keys as $key) {
                    $row[] = $rowData[$key->id] ?? null;
                }

                
            }
            $exportData[] = $row;
        }
        return $exportData;
    }

    public function title(): string
    {
        return $this->sheet->sheet_name ?? 'Sheet';
    }

  
}
