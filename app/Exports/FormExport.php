<?php

namespace App\Exports;

use App\Models\LicenseeTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FormExport implements FromCollection,WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return LicenseeTemplate::select('*')->where('status','!=','archived')->get();
    }


     /**
     * Map each row
     */
    public function map($template): array
    {
        return [
            $template->id,
            $template->licensee->name_en,
            $template->classification->name_en,
            $template->subfolder->name_en,
            $template->version,
            $template->department->name_en,
            $template->keys_count,
            ucfirst($template->status),
        ];
    }

    /**
     * Excel Headers
     */
    public function headings(): array
    {
        return [
            'ID',
            __('translation.licensee'),
            __('translation.classification'),
            __('translation.subfolder'),
            __('translation.version'),
            __('translation.department'),
            __('translation.keys'),
            __('translation.status')
        ];
    }
}
