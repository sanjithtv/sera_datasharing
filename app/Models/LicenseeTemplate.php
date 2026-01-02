<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseeTemplate extends Model
{
    use HasFactory;

    protected $table = 'sr_licensee_templates';

    protected $fillable = [
        'licensee_id',
        'subfolder_id',
        'version',
        'department_id',
        'sheet_name',
        'status',
    ];

    public function licensee()
    {
        return $this->belongsTo(Licensee::class, 'licensee_id');
    }

    public function subfolder()
    {
        return $this->belongsTo(LicenseeSubfolder::class, 'subfolder_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function sheets()
{
    return $this->hasMany(TemplateSheet::class, 'template_id');
}

    public function keys()
    {
        return $this->hasMany(LicenseeTemplateKey::class, 'licensee_template_id');
    }

    public function assessments()
{
    return $this->hasMany(Assessment::class, 'licensee_template_id');
}
}
