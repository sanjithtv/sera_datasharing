<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseeTemplateKey extends Model
{
    use HasFactory;

    protected $table = 'sr_licensee_template_keys';

    protected $fillable = [
        'licensee_id',
        'licensee_template_id',
        'short_code',
        'desc_en',
        'desc_ar',
        'mandatory',
        'type',
        'sheet_id'
    ];

    public function template()
    {
        return $this->belongsTo(LicenseeTemplate::class, 'licensee_template_id');
    }

    public function licensee()
    {
        return $this->belongsTo(Licensee::class, 'licensee_id');
    }

    public function sheet()
{
    return $this->belongsTo(LicenseeTemplateSheet::class, 'sheet_id');
}
}
