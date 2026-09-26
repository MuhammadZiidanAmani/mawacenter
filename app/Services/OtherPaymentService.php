<?php

namespace App\Services;

use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\OtherPaymentItem;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtherPaymentService
{
    public function __construct(
        private ChargeCalculator $calculator,
        private BillService $bills,
    ) {}

    public function quote(Student $student, FeeType $feeType, ?CarbonInterface $date = null): array
    {
        $date ??= now();
        $charge = $this->calculator->calculate($student, 'fee_type', $feeType, $date);
        if ($charge['original_amount'] < 1) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Kategori pembayaran tidak aktif atau tidak berlaku untuk kelas/sasaran siswa.',
            ]);
        }

        $paidAmount = (int) $this->paymentQuery($student, $feeType, $date)
            ->where('status', 'Diterima')
            ->sum('paid_amount');

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
        $transactionDate = CarbonImmutable::parse($data['transaction_date']);
        $quote = $this->quote($student, $feeType, $transactionDate);
        if ($data['paid_amount'] > $quote['remaining_amount']) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa tagihan Rp '.number_format($quote['remaining_amount'], 0, ',', '.').'.',
            ]);
        }

        $isAccepted = $data['status'] === 'Diterima';
        $remainingAmount = $isAccepted
            ? $quote['remaining_amount'] - $data['paid_amount']
            : $quote['remaining_amount'];

        $payment = OtherPayment::create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
            'payment_method' => $data['payment_method'],
            'transfer_proof_path' => $data['transfer_proof_path'] ?? null,
            'transfer_proof_file_id' => $data['transfer_proof_file_id'] ?? null,
            'transfer_proof_metadata' => $data['transfer_proof_metadata'] ?? null,
            'status' => $data['status'],
            'operator_name' => $data['operator_name'] ?? null,
            'operator_user_id' => $data['operator_user_id'] ?? null,
            'import_source' => $data['import_source'] ?? null,
            'import_key' => $data['import_key'] ?? null,
            'original_amount' => $quote['original_amount'],
            'discount_amount' => $quote['discount_amount'],
            'total_amount' => $quote['total_amount'],
            'paid_amount' => $data['paid_amount'],
            'remaining_amount' => $remainingAmount,
            'payment_status' => $isAccepted
                ? ($remainingAmount === 0 ? 'Lunas' : 'Belum Lunas')
                : 'Pending',
        ]);

        $this->bills->syncOtherPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType']));

        return $payment;
    }

    public function updateMetadata(OtherPayment $payment, array $data): OtherPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = OtherPayment::query()
                ->with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType', 'items'])
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $payment->update([
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
            ]);

            if ($payment->items->isNotEmpty()) {
                $this->reallocateItemPayments($payment->refresh(), (int) $payment->paid_amount);
            } else {
                $this->recalculatePayments($payment->student_id, $payment->fee_type_id);
            }

            $payment = $payment->refresh();
            $this->bills->syncOtherPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType']));

            return $payment;
        });
    }

    public function correctTransaction(OtherPayment $payment, array $data): OtherPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = OtherPayment::query()
                ->with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType', 'items'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status === 'Dibatalkan') {
                throw ValidationException::withMessages([
                    'reason' => 'Transaksi yang sudah dibatalkan tidak dapat dikoreksi.',
                ]);
            }

            $payment->update([
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
            ]);

            if ($payment->items->isNotEmpty()) {
                $this->reallocateItemPayments($payment->refresh(), (int) $data['new_paid_amount']);
            } else {
                $newPaidAmount = (int) $data['new_paid_amount'];
                if ($newPaidAmount > (int) $payment->total_amount) {
                    throw ValidationException::withMessages([
                        'new_paid_amount' => 'Nominal dibayar tidak boleh melebihi total tagihan Rp '.number_format((int) $payment->total_amount, 0, ',', '.').'.',
                    ]);
                }

                $payment->update(['paid_amount' => $newPaidAmount]);
                $this->recalculatePayments($payment->student_id, $payment->fee_type_id);
            }

            $payment = $payment->refresh();
            $this->bills->syncOtherPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType']));

            return $payment;
        });
    }

    public function cancel(OtherPayment $payment, string $reason): OtherPayment
    {
        return DB::transaction(function () use ($payment) {
            $payment = OtherPayment::query()
                ->with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType', 'items'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status === 'Dibatalkan') {
                throw ValidationException::withMessages([
                    'reason' => 'Transaksi ini sudah dibatalkan.',
                ]);
            }

            $studentId = $payment->student_id;
            $feeTypeId = $payment->fee_type_id;

            $this->bills->removePayment('other', $payment->id);
            foreach ($payment->items as $item) {
                $item->update([
                    'paid_amount' => 0,
                    'remaining_amount' => (int) $item->total_amount,
                    'payment_status' => 'Dibatalkan',
                ]);
            }

            $payment->update([
                'status' => 'Dibatalkan',
                'paid_amount' => 0,
                'remaining_amount' => (int) $payment->total_amount,
                'payment_status' => 'Dibatalkan',
            ]);
            $this->recalculatePayments($studentId, $feeTypeId);

            return $payment->refresh();
        });
    }

    public function delete(OtherPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $studentId = $payment->student_id;
            $feeTypeId = $payment->fee_type_id;
            $this->bills->removePayment('other', $payment->id);
            $payment->delete();
            $this->recalculatePayments($studentId, $feeTypeId);
        });
    }

    private function recalculatePayments(int $studentId, int $feeTypeId): void
    {
        $acceptedAmount = 0;

        OtherPayment::where('student_id', $studentId)
            ->where('fee_type_id', $feeTypeId)
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get()
            ->each(function (OtherPayment $payment) use (&$acceptedAmount) {
                if ($payment->status === 'Diterima') {
                    $acceptedAmount += (int) $payment->paid_amount;
                }

                $remainingAmount = max(0, (int) $payment->total_amount - $acceptedAmount);
                $payment->update([
                    'remaining_amount' => $payment->status === 'Dibatalkan' ? (int) $payment->total_amount : $remainingAmount,
                    'payment_status' => match ($payment->status) {
                        'Diterima' => $remainingAmount === 0 ? 'Lunas' : 'Belum Lunas',
                        'Dibatalkan' => 'Dibatalkan',
                        default => 'Pending',
                    },
                ]);
            });
    }

    private function reallocateItemPayments(OtherPayment $payment, int $newPaidAmount): void
    {
        $items = $payment->items()->orderBy('year')->orderBy('month')->get();
        $maxPayable = $items->sum(function (OtherPaymentItem $item) use ($payment) {
            $paidByOtherTransactions = (int) OtherPaymentItem::where('student_id', $payment->student_id)
                ->where('fee_type_id', $payment->fee_type_id)
                ->where('year', $item->year)
                ->where('month', $item->month)
                ->where('other_payment_id', '!=', $payment->id)
                ->whereHas('payment', fn ($query) => $query->where('status', 'Diterima'))
                ->sum('paid_amount');

            return max(0, (int) $item->total_amount - $paidByOtherTransactions);
        });

        if ($newPaidAmount > $maxPayable) {
            throw ValidationException::withMessages([
                'new_paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa ruang pembayaran transaksi ini Rp '.number_format($maxPayable, 0, ',', '.').'.',
            ]);
        }

        $remainingAllocation = $payment->status === 'Diterima' ? $newPaidAmount : 0;
        $paymentRemainingAmount = 0;
        foreach ($items as $item) {
            $paidByOtherTransactions = (int) OtherPaymentItem::where('student_id', $payment->student_id)
                ->where('fee_type_id', $payment->fee_type_id)
                ->where('year', $item->year)
                ->where('month', $item->month)
                ->where('other_payment_id', '!=', $payment->id)
                ->whereHas('payment', fn ($query) => $query->where('status', 'Diterima'))
                ->sum('paid_amount');
            $availableForItem = max(0, (int) $item->total_amount - $paidByOtherTransactions);
            $allocated = min($remainingAllocation, $availableForItem);
            $remaining = max(0, (int) $item->total_amount - $paidByOtherTransactions - $allocated);
            $paymentRemainingAmount += $remaining;

            $item->update([
                'paid_amount' => $allocated,
                'remaining_amount' => $remaining,
                'payment_status' => match (true) {
                    $payment->status !== 'Diterima' => 'Pending',
                    $remaining === 0 => 'Lunas',
                    $allocated > 0 => 'Belum Lunas',
                    default => 'Belum Dibayar',
                },
            ]);
            $remainingAllocation -= $allocated;
        }

        $payment->update([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $payment->status === 'Diterima' ? $paymentRemainingAmount : (int) $payment->total_amount,
            'payment_status' => $payment->status === 'Diterima'
                ? ($paymentRemainingAmount === 0 ? 'Lunas' : 'Belum Lunas')
                : 'Pending',
        ]);
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
