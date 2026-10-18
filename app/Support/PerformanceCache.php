<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class PerformanceCache
{
    public const NAMESPACES = ['dashboard', 'bills', 'master-options', 'reports', 'identity', 'settings'];

    public static function remember(string $namespace, array $parts, int $seconds, Closure $callback): mixed
    {
        if (! config('performance.query_cache.enabled', true) || $seconds < 1) {
            return $callback();
        }

        return Cache::remember(self::key($namespace, $parts), now()->addSeconds($seconds), $callback);
    }

    public static function invalidate(array|string|null $namespaces = null): void
    {
        app(PerformanceCacheInvalidator::class)->queue(self::normalizeNamespaces($namespaces));
    }

    public static function bust(array|string|null $namespaces = null): void
    {
        foreach (self::normalizeNamespaces($namespaces) as $namespace) {
            $key = self::versionKey($namespace);
            if (! Cache::has($key)) {
                Cache::forever($key, 1);
            }
            Cache::increment($key);
        }
    }

    private static function key(string $namespace, array $parts): string
    {
        $group = self::groupFor($namespace);
        $version = Cache::get(self::versionKey($group), 1);
        $payload = json_encode($parts, JSON_THROW_ON_ERROR);

        return 'performance-cache:'.$group.':'.$version.':'.$namespace.':'.hash('xxh128', $payload);
    }

    private static function groupFor(string $namespace): string
    {
        return match (true) {
            str_starts_with($namespace, 'bill') => 'bills',
            str_starts_with($namespace, 'dashboard') => 'dashboard',
            str_starts_with($namespace, 'identity') => 'identity',
            str_starts_with($namespace, 'app-setting') => 'settings',
            str_starts_with($namespace, 'master') => 'master-options',
            str_starts_with($namespace, 'report') => 'reports',
            default => $namespace,
        };
    }

    private static function versionKey(string $namespace): string
    {
        return 'performance-cache:version:'.$namespace;
    }

    private static function normalizeNamespaces(array|string|null $namespaces): array
    {
        if ($namespaces === null) {
            return self::NAMESPACES;
        }

        return collect((array) $namespaces)
            ->map(fn ($namespace) => self::groupFor((string) $namespace))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
