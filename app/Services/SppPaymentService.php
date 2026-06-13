<?php

namespace App\Services;

use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SppPaymentService
{
    public function __construct(private ChargeCalculator $calculator) {}

    public function quote(Student $student, int $year, array $months): array
    {
        $this->ensureSequentialMonths($student, $year, $months);

        return $this->calculateSelection($student, $year, $months);
    }

    public function monthStatuses(Student $student, int $year): array
    {
        $selection = $this->calculateSelection($student, $year, range(1, 12));
        $firstPayable = collect($selection['items'])->firstWhere('remaining_amount', '>', 0);

        return [
            'first_payable_month' => $firstPayable['month'] ?? null,
            'months' => $selection['items'],
        ];
    }

    public function record(Student $student, array $data): SppPayment
    {
        $this->ensureSequentialMonths($student, (int) $data['year'], $data['months']);
        $quote = $this->calculateSelection($student, (int) $data['year'], $data['months']);

        if ($data['paid_amount'] > $quote['remaining_amount']) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa tagihan Rp '.number_format($quote['remaining_amount'], 0, ',', '.').'.',
            ]);
        }

        $payment = DB::transaction(function () use ($data, $student, $quote) {
            $remainingPayment = (int) $data['paid_amount'];
            $remainingAfter = $quote['remaining_amount'] - $remainingPayment;
            $payment = SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'operator_name' => $data['operator_name'] ?? null,
                'import_source' => $data['import_source'] ?? null,
                'import_key' => $data['import_key'] ?? null,
                'original_amount' => $quote['original_amount'],
                'discount_amount' => $quote['discount_amount'],
                'total_amount' => $quote['total_amount'],
                'paid_amount' => $data['paid_amount'],
                'remaining_amount' => $remainingAfter,
                'payment_status' => $remainingAfter === 0 ? 'Lunas' : 'Belum Lunas',
            ]);

            foreach ($quote['items'] as $item) {
                if ($remainingPayment < 1 || $item['remaining_amount'] < 1) {
                    continue;
                }

                $allocated = min($remainingPayment, $item['remaining_amount']);
                $itemRemaining = $item['remaining_amount'] - $allocated;
                $payment->items()->create([
                    'student_id' => $student->id,
                    'year' => $item['year'],
                    'month' => $item['month'],
                    'original_amount' => $item['original_amount'],
                    'discount_amount' => $item['discount_amount'],
                    'total_amount' => $item['total_amount'],
                    'paid_amount' => $allocated,
                    'remaining_amount' => $itemRemaining,
                    'payment_status' => $itemRemaining === 0 ? 'Lunas' : 'Belum Lunas',
                ]);
                $remainingPayment -= $allocated;
            }

            return $payment;
        });

        return $payment;
    }

    public function updateMetadata(SppPayment $payment, array $data): SppPayment
    {
        $payment->update([
            'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status'],
        ]);

        return $payment->refresh();
    }

    public function correctPaidAmount(SppPayment $payment, array $data): SppPayment
    {
        $newPaidAmount = (int) $data['new_paid_amount'];
        $oldPaidAmount = (int) $payment->paid_amount;

        if ($newPaidAmount >= $oldPaidAmount) {
            throw ValidationException::withMessages([
                'new_paid_amount' => 'Nominal koreksi harus lebih kecil dari nominal sebelumnya. Untuk menambah pembayaran, buat transaksi pembayaran baru.',
            ]);
        }

        $payment = DB::transaction(function () use ($payment, $data, $newPaidAmount, $oldPaidAmount) {
            $remainingAllocation = $newPaidAmount;

            foreach ($payment->items()->orderBy('year')->orderBy('month')->get() as $item) {
                $allocated = min($remainingAllocation, (int) $item->total_amount);
                $remaining = (int) $item->total_amount - $allocated;
                $item->update([
                    'paid_amount' => $allocated,
                    'remaining_amount' => $remaining,
                    'payment_status' => $remaining === 0 ? 'Lunas' : ($allocated > 0 ? 'Belum Lunas' : 'Belum Dibayar'),
                ]);
                $remainingAllocation -= $allocated;
            }

            $remainingAmount = (int) $payment->total_amount - $newPaidAmount;
            $payment->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $remainingAmount === 0 ? 'Lunas' : 'Belum Lunas',
            ]);
            $payment->corrections()->create([
                'old_paid_amount' => $oldPaidAmount,
                'new_paid_amount' => $newPaidAmount,
                'refund_amount' => $oldPaidAmount - $newPaidAmount,
                'reason' => $data['reason'],
            ]);

            return $payment->refresh();
        });

        return $payment;
    }

    public function delete(SppPayment $payment): void
    {
        if ($payment->corrections()->exists()) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi yang sudah memiliki histori koreksi tidak dapat dihapus.',
            ]);
        }

        $payment->delete();
    }

    private function calculateSelection(Student $student, int $year, array $months): array
    {
        $months = array_values(array_unique(array_map('intval', $months)));
        sort($months);

        $items = [];
        foreach ($months as $month) {
            $charge = $this->calculator->calculateSppMonth($student, $year, $month);
            if ($charge['original_amount'] < 1) {
                throw ValidationException::withMessages(['student_id' => 'Set SPP aktif untuk unit pendidikan siswa belum tersedia.']);
            }

            $paidAmount = (int) SppPaymentItem::where('student_id', $student->id)
                ->where('year', $year)
                ->where('month', $month)
                ->sum('paid_amount');
            $remainingAmount = max(0, $charge['final_amount'] - $paidAmount);
            $items[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $this->monthName($month),
                'original_amount' => $charge['original_amount'],
                'discount_amount' => $charge['discount_amount'],
                'total_amount' => $charge['final_amount'],
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $remainingAmount === 0 ? 'Lunas' : ($paidAmount > 0 ? 'Belum Lunas' : 'Belum Dibayar'),
            ];
        }

        return [
            'original_amount' => array_sum(array_column($items, 'original_amount')),
            'discount_amount' => array_sum(array_column($items, 'discount_amount')),
            'total_amount' => array_sum(array_column($items, 'total_amount')),
            'paid_amount' => array_sum(array_column($items, 'paid_amount')),
            'remaining_amount' => array_sum(array_column($items, 'remaining_amount')),
            'payment_status' => array_sum(array_column($items, 'remaining_amount')) === 0 ? 'Lunas' : 'Belum Lunas',
            'items' => $items,
        ];
    }

    private function ensureSequentialMonths(Student $student, int $year, array $months): void
    {
        $selectedMonths = array_values(array_unique(array_map('intval', $months)));
        sort($selectedMonths);
        $yearSelection = $this->calculateSelection($student, $year, range(1, 12));
        $payableMonths = array_values(array_map(
            fn (array $item) => $item['month'],
            array_filter($yearSelection['items'], fn (array $item) => $item['remaining_amount'] > 0),
        ));
        $expectedMonths = array_slice($payableMonths, 0, count($selectedMonths));

        if ($selectedMonths !== $expectedMonths) {
            $firstMonth = $payableMonths[0] ?? null;
            $message = $firstMonth
                ? 'Pembayaran harus dimulai dari bulan '.$this->monthName($firstMonth).' dan dipilih secara berurutan.'
                : 'Seluruh pembayaran SPP pada tahun ini sudah lunas.';
            throw ValidationException::withMessages(['months' => $message]);
        }
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }
}
