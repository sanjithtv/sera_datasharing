<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assessment extends Model
{
    use HasFactory;
    protected $table = 'sr_licensee_assessments';

    protected $fillable = [
        'licensee_id',
        'licensee_template_id',
        'assessment_date',
        'status',
    ];

    public function licensee()
    {
        return $this->belongsTo(Licensee::class);
    }

    public function template()
    {
        return $this->belongsTo(LicenseeTemplate::class, 'licensee_template_id');
    }

    public function masterData()
    {
        return $this->hasMany(AssessmentMasterData::class);
    }

    public function slaveData()
{
    return $this->hasMany(\App\Models\SlaveMasterData::class, 'assessment_id');
}

    public function licenseeTemplate()
{
    return $this->belongsTo(\App\Models\LicenseeTemplate::class, 'licensee_template_id');
}
}
