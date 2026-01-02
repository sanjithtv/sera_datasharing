<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssessmentMasterData extends Model
{
    use HasFactory;
    protected $table = 'sr_licensee_assessment_master_data';

    protected $fillable = [
        'licensee_id',
        'assessment_id',
        'template_key_id',
        'template_key_value',
        'type',
        'entry_counter',
        'template_sheet_id'
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function templateKey()
    {
        return $this->belongsTo(LicenseeTemplateKey::class, 'template_key_id');
    }

    public function licensee()
    {
        return $this->belongsTo(Licensee::class);
    }
}
