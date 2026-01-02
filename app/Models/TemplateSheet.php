<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LicenseeTemplate;
use App\Models\Licensee;
use App\Models\LicenseeTemplateKey;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateSheet extends Model
{   
    use HasFactory;

    protected $table = 'sr_licensee_template_sheets';
    protected $fillable = [
        'template_id',
        'sheet_name',
        'status'
    ];

    public function template()
    {
        return $this->belongsTo(LicenseeTemplate::class, 'licensee_template_id');
    }

    public function keys()
{
    return $this->hasMany(LicenseeTemplateKey::class, 'sheet_id');
}

   
}