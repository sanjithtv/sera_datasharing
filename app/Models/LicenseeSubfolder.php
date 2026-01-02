<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseeSubfolder extends Model
{
    use HasFactory;

    protected $table = 'sr_subfolders';

    protected $fillable = ['name_en', 'name_ar', 'status'];
}
