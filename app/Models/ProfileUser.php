<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileUser extends Model
{
    use HasFactory;

    protected $table = 'sr_profile_users';

    protected $fillable = [
        'fullname_en',
        'fullname_ar',
        'email',
        'phone',
        'designation',
        'status',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'profile_user_id');
    }
}
