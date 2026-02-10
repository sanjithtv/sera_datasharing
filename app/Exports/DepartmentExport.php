<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepartmentExport implements FromCollection,WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Department::select('*')->where('status','!=','archived')->get();
    }


     /**
     * Map each row
     */
    public function map($department): array
    {
        return [
            $department->code,
            $department->name_en,
            $department->name_ar,
            ucfirst($department->status),
        ];
    }

    /**
     * Excel Headers
     */
    public function headings(): array
    {
        return [
            __('translation.code'),
            __('translation.name').' '.__('translation.en'),
            __('translation.name').' '.__('translation.ar'),
            __('translation.status')
        ];
    }
}
