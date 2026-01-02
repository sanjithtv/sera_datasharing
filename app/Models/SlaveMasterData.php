<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaveMasterData extends Model
{
    use HasFactory;

    protected $table = 'slave_master_data';

    protected $fillable = [
        'assessment_id',
        'licensee_id',
        'headers',
        'row_data',
        'validation_errors',
        'row_index',
        'status',
        'processing_message',
        'sheet_id'
    ];

    protected $casts = [
        'headers' => 'array',
        'row_data' => 'array',
        'validation_errors' => 'array',
    ];
}
