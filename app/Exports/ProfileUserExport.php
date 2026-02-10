<?php

namespace App\Exports;

use App\Models\ProfileUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProfileUserExport implements FromCollection,WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ProfileUser::select('*')->where('status','!=','archived')->get();
    }


     /**
     * Map each row
     */
    public function map($profileUser): array
    {
        return [
            $profileUser->id,
            $profileUser->fullname_en,
            $profileUser->email,
            $profileUser->designation,
            $profileUser->user?->getRoleNames()->implode(', '),
            ucfirst($profileUser->status),
        ];
    }

    /**
     * Excel Headers
     */
    public function headings(): array
    {
        return [
            __('translation.id'),
            __('translation.name'),
            __('translation.email'),
            __('translation.designation'),
            __('translation.role'),
            __('translation.status')
        ];
    }
}
