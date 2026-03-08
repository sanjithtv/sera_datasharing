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

        // Get unique sheet IDs for this assessment
        $sheetIds = AssessmentMasterData::where('assessment_id', $this->assessmentId)
            ->distinct()
            ->pluck('template_sheet_id');

        if ($sheetIds->count() > 0) {
            // Eager load everything needed for the single sheet exports
            $templateSheets = LicenseeTemplateSheet::with('keys')
                ->whereIn('id', $sheetIds)
                ->get();

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
