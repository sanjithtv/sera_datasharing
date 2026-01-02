<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'sr_departments';

    protected $fillable = ['code', 'name_en', 'name_ar', 'status'];
}
