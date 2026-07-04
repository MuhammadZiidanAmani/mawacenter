<?php

namespace App\Services;

use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\OtherPaymentItem;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaundryPaymentService
{
    public function __construct(private ChargeCalculator $calculator) {}

    public function quote(Student $student, FeeType $feeType, int $year, array $months): array
    {
        $this->ensureSequentialMonths($student, $feeType, $year, $months);

        return $this->calculateSelection($student, $feeType, $year, $months);
    }

    public function monthStatuses(Student $student, FeeType $feeType, int $year): array
    {
        $selection = $this->calculateSelection($student, $feeType, $year, range(1, 12));
        $firstPayable = collect($selection['items'])->firstWhere('remaining_amount', '>', 0);

        return [
            'first_payable_month' => $firstPayable['month'] ?? null,
            'months' => $selection['items'],
        ];
    }

    public function record(Student $student, FeeType $feeType, array $data): OtherPayment
    {
        return DB::transaction(function () use ($student, $feeType, $data) {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $year = (int) $data['year'];
            $this->ensureSequentialMonths($student, $feeType, $year, $data['months']);
            $quote = $this->calculateSelection($student, $feeType, $year, $data['months']);

            if ($data['paid_amount'] > $quote['remaining_amount']) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa tagihan Rp '.number_format($quote['remaining_amount'], 0, ',', '.').'.',
                ]);
            }

            $remainingPayment = (int) $data['paid_amount'];
            $remainingAfter = $quote['remaining_amount'] - $remainingPayment;
            $payment = OtherPayment::create([
                'student_id' => $student->id,
                'fee_type_id' => $feeType->id,
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'transfer_proof_path' => $data['transfer_proof_path'] ?? null,
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
                    'fee_type_id' => $feeType->id,
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
    }

    public function delete(OtherPayment $payment): void
    {
        $payment->delete();
    }

    private function calculateSelection(Student $student, FeeType $feeType, int $year, array $months): array
    {
        $months = array_values(array_unique(array_map('intval', $months)));
        sort($months);
        $items = [];

        foreach ($months as $month) {
            $charge = $this->calculator->calculate(
                $student,
                'fee_type',
                $feeType,
                CarbonImmutable::create($year, $month, 1),
            );
            if ($charge['original_amount'] < 1) {
                throw ValidationException::withMessages([
                    'fee_type_id' => 'Set Laundry aktif untuk unit dan kelas siswa belum tersedia.',
                ]);
            }

            $paidAmount = (int) OtherPaymentItem::where('student_id', $student->id)
                ->where('fee_type_id', $feeType->id)
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

    private function ensureSequentialMonths(Student $student, FeeType $feeType, int $year, array $months): void
    {
        $selectedMonths = array_values(array_unique(array_map('intval', $months)));
        sort($selectedMonths);
        $yearSelection = $this->calculateSelection($student, $feeType, $year, range(1, 12));
        $selectedItems = array_filter(
            $yearSelection['items'],
            fn (array $item) => in_array($item['month'], $selectedMonths, true),
        );
        $paidItem = collect($selectedItems)->firstWhere('remaining_amount', 0);

        if ($paidItem) {
            throw ValidationException::withMessages([
                'months' => 'Laundry bulan '.$paidItem['month_name'].' '.$year.' sudah lunas dan tidak dapat dibayar kembali.',
            ]);
        }

        $payableMonths = array_values(array_map(
            fn (array $item) => $item['month'],
            array_filter($yearSelection['items'], fn (array $item) => $item['remaining_amount'] > 0),
        ));
        $expectedMonths = array_slice($payableMonths, 0, count($selectedMonths));

        if ($selectedMonths !== $expectedMonths) {
            $firstMonth = $payableMonths[0] ?? null;
            throw ValidationException::withMessages([
                'months' => $firstMonth
                    ? 'Pembayaran harus dimulai dari bulan '.$this->monthName($firstMonth).' dan dipilih secara berurutan.'
                    : 'Seluruh pembayaran Laundry pada tahun ini sudah lunas.',
            ]);
        }
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }
}
