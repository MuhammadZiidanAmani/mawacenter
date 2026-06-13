<?php

namespace App\Services;

use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class OtherPaymentService
{
    public function __construct(private ChargeCalculator $calculator) {}

    public function quote(Student $student, FeeType $feeType, ?CarbonInterface $date = null): array
    {
        $date ??= now();
        $charge = $this->calculator->calculate($student, 'fee_type', $feeType, $date);
        if ($charge['original_amount'] < 1) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Jenis pembayaran tidak aktif atau tidak berlaku untuk kelas siswa.',
            ]);
        }

        $paidAmount = (int) $this->paymentQuery($student, $feeType, $date)->sum('paid_amount');

        return [
            'original_amount' => $charge['original_amount'],
            'discount_amount' => $charge['discount_amount'],
            'total_amount' => $charge['final_amount'],
            'paid_amount' => $paidAmount,
            'remaining_amount' => max(0, $charge['final_amount'] - $paidAmount),
        ];
    }

    public function record(Student $student, FeeType $feeType, array $data): OtherPayment
    {
        $transactionDate = \Carbon\CarbonImmutable::parse($data['transaction_date']);
        $quote = $this->quote($student, $feeType, $transactionDate);
        if ($data['paid_amount'] > $quote['remaining_amount']) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa tagihan Rp '.number_format($quote['remaining_amount'], 0, ',', '.').'.',
            ]);
        }

        $remainingAmount = $quote['remaining_amount'] - $data['paid_amount'];

        $payment = OtherPayment::create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
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
            'remaining_amount' => $remainingAmount,
            'payment_status' => $remainingAmount === 0 ? 'Lunas' : 'Belum Lunas',
        ]);

        return $payment;
    }

    private function paymentQuery(Student $student, FeeType $feeType, CarbonInterface $date)
    {
        return OtherPayment::where('student_id', $student->id)
            ->where('fee_type_id', $feeType->id)
            ->when($feeType->period === 'Bulanan', fn ($query) => $query->whereYear('transaction_at', $date->year)->whereMonth('transaction_at', $date->month))
            ->when($feeType->period === 'Tahunan', function ($query) use ($student, $date) {
                $student->loadMissing('academicYear');
                if ($student->academicYear?->start_date && $student->academicYear?->end_date) {
                    $query->whereBetween('transaction_at', [$student->academicYear->start_date->startOfDay(), $student->academicYear->end_date->endOfDay()]);
                } else {
                    $query->whereYear('transaction_at', $date->year);
                }
            });
    }
}
