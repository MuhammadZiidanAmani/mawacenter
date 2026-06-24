<?php

namespace App\Models;

use App\Support\PerformanceCache;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function values(array $defaults = []): array
    {
        $settings = PerformanceCache::remember(
            'app-settings',
            [],
            config('performance.query_cache.app_settings_ttl', 3600),
            fn () => static::query()->pluck('value', 'key')->all(),
        );

        return array_merge($defaults, $settings);
    }

    public static function valueFor(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default;
    }
}
