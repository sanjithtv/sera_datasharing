<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteConfiguration extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue($key, $default = null)
    {
        return optional(static::where('key', $key)->first())->value ?? $default;
    }

    public static function setValue($key, $value)
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
