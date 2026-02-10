<?php

namespace App\Exports;

use App\Models\AssessmentMasterData;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\LicenseeTemplateSheet;


class AssessmentMasterMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $assessmentId;

    public function __construct($assessmentId)
    {
        $this->assessmentId = $assessmentId;
    }

   
    public function sheets(): array
    {
        $sheets = [];

        
        $sheetIds = AssessmentMasterData::where('assessment_id', $this->assessmentId)
            ->pluck('template_sheet_id')
            ->unique();

        foreach ($sheetIds as $sheetId) {
            $templateSheets = LicenseeTemplateSheet::with('keys')->where('id',$sheetId)->get();
            foreach ($templateSheets as $sheet) {
                $sheets[] = new AssessmentMasterSingleSheetExport(
                    $this->assessmentId,
                    $sheet
                );
            }
        }

        return $sheets;
    }
}
