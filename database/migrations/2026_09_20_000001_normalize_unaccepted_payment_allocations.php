<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $affectedBillIds = [];

            DB::table('spp_payments')
                ->where('status', '!=', 'Diterima')
                ->orderBy('id')
                ->chunkById(200, function ($payments) use (&$affectedBillIds): void {
                    foreach ($payments as $payment) {
                        $affectedBillIds = array_merge(
                            $affectedBillIds,
                            DB::table('bill_payment_allocations')
                                ->where('payment_type', 'spp')
                                ->where('payment_id', $payment->id)
                                ->pluck('bill_id')
                                ->all(),
                        );

                        DB::table('bill_payment_allocations')
                            ->where('payment_type', 'spp')
                            ->where('payment_id', $payment->id)
                            ->delete();

                        $paymentStatus = $payment->status === 'Dibatalkan' ? 'Dibatalkan' : 'Pending';
                        DB::table('spp_payment_items')
                            ->where('spp_payment_id', $payment->id)
                            ->update([
                                'paid_amount' => 0,
                                'remaining_amount' => DB::raw('total_amount'),
                                'payment_status' => $paymentStatus,
                            ]);
                        DB::table('spp_payments')
                            ->where('id', $payment->id)
                            ->update([
                                'remaining_amount' => $payment->total_amount,
                                'payment_status' => $paymentStatus,
                            ]);
                    }
                });

            DB::table('other_payments')
                ->join('fee_types', 'fee_types.id', '=', 'other_payments.fee_type_id')
                ->where('fee_types.payment_group', 'laundry')
                ->where('other_payments.status', '!=', 'Diterima')
                ->select('other_payments.id', 'other_payments.status', 'other_payments.total_amount')
                ->orderBy('other_payments.id')
                ->chunkById(200, function ($payments): void {
                    foreach ($payments as $payment) {
                        $paymentStatus = $payment->status === 'Dibatalkan' ? 'Dibatalkan' : 'Pending';
                        DB::table('other_payment_items')
                            ->where('other_payment_id', $payment->id)
                            ->update([
                                'paid_amount' => 0,
                                'remaining_amount' => DB::raw('total_amount'),
                                'payment_status' => $paymentStatus,
                            ]);
                        DB::table('other_payments')
                            ->where('id', $payment->id)
                            ->update([
                                'remaining_amount' => $payment->total_amount,
                                'payment_status' => $paymentStatus,
                            ]);
                    }
                }, 'other_payments.id', 'id');

            DB::table('guardian_transfer_requests')
                ->where('status', '!=', 'Diterima')
                ->orderBy('id')
                ->chunkById(200, function ($transfers) use (&$affectedBillIds): void {
                    foreach ($transfers as $transfer) {
                        $affectedBillIds = array_merge(
                            $affectedBillIds,
                            DB::table('bill_payment_allocations')
                                ->where('payment_type', 'guardian_transfer')
                                ->where('payment_id', $transfer->id)
                                ->pluck('bill_id')
                                ->all(),
                        );
                        DB::table('bill_payment_allocations')
                            ->where('payment_type', 'guardian_transfer')
                            ->where('payment_id', $transfer->id)
                            ->delete();
                    }
                });

            foreach (array_unique($affectedBillIds) as $billId) {
                $bill = DB::table('bills')->where('id', $billId)->first();
                if (! $bill || $bill->status === 'Dibatalkan') {
                    continue;
                }

                $paid = min(
                    (int) $bill->total_amount,
                    (int) DB::table('bill_payment_allocations')->where('bill_id', $billId)->sum('amount'),
                );
                $remaining = max(0, (int) $bill->total_amount - $paid);
                DB::table('bills')->where('id', $billId)->update([
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'status' => $remaining === 0 ? 'Lunas' : ($paid > 0 ? 'Sebagian' : 'Belum Dibayar'),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Normalized financial state cannot be reconstructed safely.
    }
};
