<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class PerformanceCache
{
    private const VERSION_KEY = 'performance-cache:version';

    public static function remember(string $namespace, array $parts, int $seconds, Closure $callback): mixed
    {
        if (! config('performance.query_cache.enabled', true) || $seconds < 1) {
            return $callback();
        }

        return Cache::remember(self::key($namespace, $parts), now()->addSeconds($seconds), $callback);
    }

    public static function bust(): void
    {
        Cache::forever(self::VERSION_KEY, now()->getTimestampMs());
    }

    private static function key(string $namespace, array $parts): string
    {
        $version = Cache::get(self::VERSION_KEY, 1);
        $payload = json_encode($parts, JSON_THROW_ON_ERROR);

        return 'performance-cache:'.$version.':'.$namespace.':'.hash('xxh128', $payload);
    }
}
