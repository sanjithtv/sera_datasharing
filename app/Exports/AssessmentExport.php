<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssessmentExport implements FromCollection,WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Assessment::select('*')->get();
    }


     /**
     * Map each row
     */
    public function map($assessment): array
    {
        return [
            $assessment->id,
            $assessment->licensee->name_en,
            $assessment->licenseeTemplate->subfolder->name_en,
            $assessment->licenseeTemplate->version,
            $assessment->assessment_date,
            ucfirst($assessment->status),
            $assessment->imported_rows,
        ];
    }

    /**
     * Excel Headers
     */
    public function headings(): array
    {
        return [
            'ID',
            'Licensee',
            'Sub Folder',
            'Version',
            'Date',
            'Status',
            'Entries',
        ];
    }
}
