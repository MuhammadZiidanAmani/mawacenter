<?php

namespace App\Services;

use App\Models\BillPaymentAllocation;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use Illuminate\Support\Collection;

class PaymentReconciliationService
{
    public function anomalies(?array $unitIds): Collection
    {
        return collect([
            ...$this->payments(SppPayment::class, 'spp', 'SPP', $unitIds),
            ...$this->payments(OtherPayment::class, 'other', 'Lain-lain', $unitIds),
        ])->sortByDesc('timestamp')->values();
    }

    private function payments(string $model, string $allocationType, string $source, ?array $unitIds): array
    {
        $payments = $model::query()
            ->with(['student.schoolClass.educationUnit'])
            ->when($model === OtherPayment::class, fn ($query) => $query->with('feeType'))
            ->when(is_array($unitIds), fn ($query) => $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('student.schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
            ->where(function ($query) use ($allocationType) {
                $query->whereNotNull('reconciliation_status')
                    ->orWhere(function ($accepted) use ($allocationType) {
                        $accepted->where('status', 'Diterima')
                            ->whereRaw('(select coalesce(sum(amount), 0) from bill_payment_allocations where payment_type = ? and payment_id = '.
                                $accepted->getModel()->getTable().'.id) != paid_amount', [$allocationType]);
                    });
            })
            ->latest('transaction_at')
            ->limit(200)
            ->get();

        $allocationTotals = BillPaymentAllocation::query()
            ->where('payment_type', $allocationType)
            ->whereIn('payment_id', $payments->pluck('id'))
            ->selectRaw('payment_id, sum(amount) as total')
            ->groupBy('payment_id')
            ->pluck('total', 'payment_id');

        return $payments->map(function ($payment) use ($allocationTotals, $source, $allocationType) {
            $allocated = (int) ($allocationTotals[$payment->id] ?? 0);
            $recorded = (int) $payment->paid_amount;
            $expired = $payment->reconciliation_status === 'Kadaluarsa';
            $sourceLabel = $allocationType === 'other' ? ($payment->feeType?->name ?? $source) : $source;

            return [
                'type' => $allocationType,
                'id' => $payment->id,
                'reference' => strtoupper($allocationType).'-'.$payment->transaction_at->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'student' => $payment->student?->name ?? '-',
                'nis' => $payment->student?->nis ?? '-',
                'unit' => $payment->student?->schoolClass?->educationUnit?->code ?? '-',
                'class' => $payment->student?->schoolClass?->name ?? '-',
                'date' => $payment->transaction_at->format('d/m/Y H.i').' WIB',
                'timestamp' => $payment->transaction_at->timestamp,
                'source' => $sourceLabel,
                'issue' => $expired ? 'Transaksi kadaluarsa' : ($allocated === 0 ? 'Belum teralokasi' : 'Alokasi tidak sesuai'),
                'issue_key' => $expired ? 'expired' : ($allocated === 0 ? 'unallocated' : 'allocation'),
                'severity' => $expired || $recorded !== $allocated ? 'Kritis' : 'Peringatan',
                'recorded_amount' => $recorded,
                'allocated_amount' => $allocated,
                'difference' => $recorded - $allocated,
                'status' => $payment->status,
            ];
        })->all();
    }
}
