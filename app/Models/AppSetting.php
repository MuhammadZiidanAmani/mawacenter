<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function values(array $defaults = []): array
    {
        return array_merge($defaults, static::query()->pluck('value', 'key')->all());
    }
}
