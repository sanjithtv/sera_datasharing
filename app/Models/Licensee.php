<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licensee extends Model
{
    use HasFactory;

    protected $table = 'sr_licensees';

    protected $fillable = ['code', 'name_en', 'name_ar', 'status'];
}
