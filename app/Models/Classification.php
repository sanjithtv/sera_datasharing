<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $table = 'sr_template_classifications';

    protected $fillable = ['name_en', 'name_ar', 'status'];
}
