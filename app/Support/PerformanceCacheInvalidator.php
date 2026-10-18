<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PerformanceCacheInvalidator
{
    private array $pending = [];

    private array $beforeTransaction = [];

    private bool $transactionCallbackScheduled = false;

    public function queue(array $namespaces): void
    {
        $insideTransaction = DB::transactionLevel() > 0;
        if ($insideTransaction && ! $this->transactionCallbackScheduled) {
            $this->beforeTransaction = $this->pending;
        }

        foreach ($namespaces as $namespace) {
            $this->pending[$namespace] = true;
        }

        if ($this->pending === []) {
            return;
        }

        if (app()->environment('testing')) {
            $this->flush();

            return;
        }

        if ($insideTransaction) {
            $this->scheduleAfterCommit();

            return;
        }
    }

    public function flush(): void
    {
        $namespaces = array_keys($this->pending);
        $this->pending = [];
        $this->beforeTransaction = [];
        $this->transactionCallbackScheduled = false;

        if ($namespaces !== []) {
            PerformanceCache::bust($namespaces);
        }
    }

    private function scheduleAfterCommit(): void
    {
        if ($this->transactionCallbackScheduled) {
            return;
        }

        $this->transactionCallbackScheduled = true;
        DB::afterCommit(function (): void {
            $this->transactionCallbackScheduled = false;
            $this->flush();
        });
        DB::afterRollBack(function (): void {
            $this->pending = $this->beforeTransaction;
            $this->beforeTransaction = [];
            $this->transactionCallbackScheduled = false;
        });
    }
}
