<?php

namespace App\Jobs;

use App\Models\OtherPayment;
use App\Models\SppPayment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarkExpiredPendingPayments implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = now()->subHours(max(1, (int) config('payments.reconciliation_pending_expiry_hours', 24)));
        $attributes = [
            'reconciliation_status' => 'Kadaluarsa',
            'reconciliation_flagged_at' => now(),
        ];

        foreach ([SppPayment::query(), OtherPayment::query()] as $query) {
            $query
                ->where('status', 'Pending')
                ->where('transaction_at', '<=', $cutoff)
                ->whereNull('reconciliation_status')
                ->update($attributes);
        }
    }
}
