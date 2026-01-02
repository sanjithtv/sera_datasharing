<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseeTemplateSheet extends Model
{
    use HasFactory;

    protected $table = 'sr_licensee_template_sheets'; // ✅ confirm table name

    protected $fillable = [
        'template_id',
        'sheet_name',
        'status'
    ];

    /**
     * ✅ Each Sheet BELONGS TO one Template
     */
    public function template()
    {
        return $this->belongsTo(LicenseeTemplate::class, 'template_id');
    }

    /**
     * ✅ Each Sheet HAS MANY Keys
     */
    public function keys()
    {
        return $this->hasMany(LicenseeTemplateKey::class, 'sheet_id')
                    ->orderBy('sheet_id'); // optional but recommended
    }
}
