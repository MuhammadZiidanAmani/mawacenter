<?php

namespace Tests\Feature;

use App\Support\PerformanceCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        config(['performance.query_cache.enabled' => true]);
    }

    public function test_cache_namespaces_can_be_invalidated_independently(): void
    {
        $billCalls = 0;
        $masterCalls = 0;

        $billValue = function () use (&$billCalls) {
            return ++$billCalls;
        };
        $masterValue = function () use (&$masterCalls) {
            return ++$masterCalls;
        };

        $this->assertSame(1, PerformanceCache::remember('bill-overview-v5', [], 60, $billValue));
        $this->assertSame(1, PerformanceCache::remember('master-stats-v1', [], 60, $masterValue));
        $this->assertSame(1, PerformanceCache::remember('bill-overview-v5', [], 60, $billValue));
        $this->assertSame(1, PerformanceCache::remember('master-stats-v1', [], 60, $masterValue));

        PerformanceCache::bust('bills');

        $this->assertSame(2, PerformanceCache::remember('bill-overview-v5', [], 60, $billValue));
        $this->assertSame(1, PerformanceCache::remember('master-stats-v1', [], 60, $masterValue));
        $this->assertSame(2, $billCalls);
        $this->assertSame(1, $masterCalls);
    }
}
