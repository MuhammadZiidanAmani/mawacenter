<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillService
{
    public function __construct(private ChargeCalculator $calculator) {}

    public function generateSpp(AcademicYear $academicYear, int $year, array $months, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $students = $this->students($academicYear, $filters)->get();

        DB::transaction(function () use ($academicYear, $year, $months, $students, &$result) {
            foreach ($students as $student) {
                foreach (array_unique(array_map('intval', $months)) as $month) {
                    $periodStart = CarbonImmutable::create($year, $month, 1);
                    if (! $this->eligible($student, $periodStart)) {
                        $result['skipped']++;

                        continue;
                    }

                    try {
                        [$bill, $created] = $this->ensureSppBill($student, $academicYear, $year, $month);
                        $result[$created ? 'created' : 'existing']++;
                        $this->syncSppBillPayments($bill);
                    } catch (ValidationException) {
                        $result['skipped']++;
                    }
                }
            }
        });

        return $result;
    }

    public function generateSppFromEntryUntil(AcademicYear $academicYear, int $endYear, int $endMonth, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $students = $this->students($academicYear, $filters)->get();
        $endPeriod = CarbonImmutable::create($endYear, $endMonth, 1)->startOfMonth();

        DB::transaction(function () use ($academicYear, $endPeriod, $students, &$result) {
            foreach ($students as $student) {
                $period = $this->studentBillingStart($student, $academicYear);

                if ($period->gt($endPeriod)) {
                    $result['skipped']++;

                    continue;
                }

                while ($period->lte($endPeriod)) {
                    if (! $this->eligible($student, $period)) {
                        $result['skipped']++;
                        $period = $period->addMonth();

                        continue;
                    }

                    try {
                        [$bill, $created] = $this->ensureSppBill($student, $academicYear, $period->year, $period->month);
                        $result[$created ? 'created' : 'existing']++;
                        $this->syncSppBillPayments($bill);
                    } catch (ValidationException) {
                        $result['skipped']++;
                    }

                    $period = $period->addMonth();
                }
            }
        });

        return $result;
    }

    public function generateFeeType(AcademicYear $academicYear, FeeType $feeType, ?int $year, ?int $month, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];

        DB::transaction(function () use ($academicYear, $feeType, $year, $month, $filters, &$result) {
            foreach ($this->students($academicYear, $filters)->get() as $student) {
                try {
                    [$bill, $created] = $this->ensureFeeTypeBill($student, $academicYear, $feeType, $year, $month);
                    $result[$created ? 'created' : 'existing']++;
                    $this->syncOtherBillPayments($bill);
                } catch (ValidationException) {
                    $result['skipped']++;
                }
            }
        });

        return $result;
    }

    public function createManual(Student $student, array $data): Bill
    {
        $total = (int) $data['amount'];

        return Bill::create([
            'student_id' => $student->id,
            'academic_year_id' => $data['academic_year_id'] ?? $student->academic_year_id,
            'source_type' => 'manual',
            'generation_key' => hash('sha256', 'manual|'.$student->id.'|'.now()->format('YmdHisv').'|'.$data['title']),
            'title' => $data['title'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'original_amount' => $total,
            'discount_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'remaining_amount' => $total,
            'status' => 'Belum Dibayar',
            'unit_name' => $student->schoolClass?->educationUnit?->name,
            'class_name' => $student->schoolClass?->name,
        ]);
    }

    public function payManual(Bill $bill, array $data): Bill
    {
        if ($bill->source_type !== 'manual' || $bill->status === 'Dibatalkan') {
            throw ValidationException::withMessages(['bill' => 'Tagihan ini tidak dapat dibayar melalui pembayaran manual.']);
        }
        if ($data['paid_amount'] > $bill->remaining_amount) {
            throw ValidationException::withMessages(['paid_amount' => 'Nominal melebihi sisa tagihan Rp '.number_format($bill->remaining_amount, 0, ',', '.').'.']);
        }

        DB::transaction(function () use ($bill, $data) {
            $payment = $bill->manualPayments()->create([
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'paid_amount' => $data['paid_amount'],
            ]);
            $bill->allocations()->create(['payment_type' => 'manual', 'payment_id' => $payment->id, 'amount' => $payment->paid_amount]);
            $this->refresh($bill);
        });

        return $bill->refresh();
    }

    public function syncSppPayment(SppPayment $payment): void
    {
        $payment->loadMissing(['student.academicYear', 'student.schoolClass.educationUnit', 'items']);

        DB::transaction(function () use ($payment) {
            foreach ($payment->items as $item) {
                [$bill] = $this->ensureSppBill($payment->student, $payment->student->academicYear, $item->year, $item->month);
                $bill->allocations()->updateOrCreate(
                    ['payment_type' => 'spp', 'payment_id' => $payment->id],
                    ['amount' => $item->paid_amount],
                );
                $this->refresh($bill);
            }
        });
    }

    public function syncOtherPayment(OtherPayment $payment): void
    {
        $payment->loadMissing(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType']);
        if ($payment->feeType?->payment_group === 'laundry') {
            return;
        }

        $date = CarbonImmutable::parse($payment->transaction_at);
        [$bill] = $this->ensureFeeTypeBill($payment->student, $payment->student->academicYear, $payment->feeType, $date->year, $date->month);
        if ($payment->status === 'Diterima') {
            $bill->allocations()->updateOrCreate(
                ['payment_type' => 'other', 'payment_id' => $payment->id],
                ['amount' => $payment->paid_amount],
            );
        } else {
            $bill->allocations()
                ->where('payment_type', 'other')
                ->where('payment_id', $payment->id)
                ->delete();
        }
        $this->refresh($bill);
    }

    public function removePayment(string $type, int $paymentId): void
    {
        $bills = Bill::whereHas('allocations', fn ($query) => $query->where('payment_type', $type)->where('payment_id', $paymentId))->get();
        foreach ($bills as $bill) {
            $bill->allocations()->where('payment_type', $type)->where('payment_id', $paymentId)->delete();
            $this->refresh($bill);
        }
    }

    public function syncAll(): array
    {
        $result = ['spp' => 0, 'other' => 0];
        SppPayment::with(['student.academicYear', 'student.schoolClass.educationUnit', 'items'])->orderBy('id')->each(function ($payment) use (&$result) {
            $this->syncSppPayment($payment);
            $result['spp']++;
        });
        OtherPayment::with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType'])
            ->whereHas('feeType', function ($query) {
                $query->whereNull('payment_group')->orWhere('payment_group', '!=', 'laundry');
            })
            ->orderBy('id')
            ->each(function ($payment) use (&$result) {
                $this->syncOtherPayment($payment);
                $result['other']++;
            });

        return $result;
    }

    public function cancel(Bill $bill, string $reason): Bill
    {
        if ($bill->paid_amount > 0) {
            throw ValidationException::withMessages(['bill' => 'Tagihan yang sudah memiliki pembayaran tidak dapat dibatalkan.']);
        }
        $bill->update(['status' => 'Dibatalkan', 'cancel_reason' => $reason]);

        return $bill->refresh();
    }

    private function ensureSppBill(Student $student, AcademicYear $academicYear, int $year, int $month): array
    {
        $key = hash('sha256', "spp|{$student->id}|{$year}|{$month}");
        if ($bill = Bill::where('generation_key', $key)->first()) {
            return [$bill, false];
        }

        $charge = $this->calculator->calculateSppMonth($student, $year, $month);
        if ($charge['original_amount'] < 1) {
            throw ValidationException::withMessages(['bill' => 'Kategori pembayaran SPP siswa belum tersedia.']);
        }
        $issueDate = CarbonImmutable::create($year, $month, 1);
        $bill = Bill::create($this->baseBill($student, $academicYear) + [
            'source_type' => 'spp', 'year' => $year, 'month' => $month, 'generation_key' => $key,
            'title' => 'SPP '.$this->monthName($month).' '.$year, 'issue_date' => $issueDate,
            'due_date' => $issueDate->day(10), 'original_amount' => $charge['original_amount'],
            'discount_amount' => $charge['discount_amount'], 'total_amount' => $charge['final_amount'],
            'paid_amount' => 0, 'remaining_amount' => $charge['final_amount'], 'status' => 'Belum Dibayar',
        ]);

        return [$bill, true];
    }

    private function ensureFeeTypeBill(Student $student, AcademicYear $academicYear, FeeType $feeType, ?int $year, ?int $month): array
    {
        $period = $this->feePeriod($feeType);
        $year ??= $academicYear->start_date?->year ?? (int) explode('/', $academicYear->name)[0];
        $month = $period === 'Bulanan' ? ($month ?? now()->month) : null;
        $periodKey = $period === 'Sekali Bayar' ? 'once' : ($period === 'Tahunan' ? $academicYear->id : "{$year}|{$month}");
        $key = hash('sha256', "fee|{$student->id}|{$feeType->id}|{$periodKey}");
        if ($bill = Bill::where('generation_key', $key)->first()) {
            return [$bill, false];
        }

        $issueDate = $month ? CarbonImmutable::create($year, $month, 1) : ($academicYear->start_date?->toImmutable() ?? now()->toImmutable());
        $charge = $this->calculator->calculate($student, 'fee_type', $feeType, $issueDate);
        if ($charge['original_amount'] < 1) {
            throw ValidationException::withMessages(['bill' => 'Kategori pembayaran tidak berlaku untuk siswa.']);
        }
        $suffix = $month ? ' '.$this->monthName($month).' '.$year : ($period === 'Tahunan' ? ' '.$academicYear->name : '');
        $bill = Bill::create($this->baseBill($student, $academicYear) + [
            'source_type' => 'fee_type', 'fee_type_id' => $feeType->id, 'year' => $year, 'month' => $month,
            'generation_key' => $key, 'title' => $feeType->name.$suffix, 'issue_date' => $issueDate,
            'due_date' => $issueDate->addDays(30), 'original_amount' => $charge['original_amount'],
            'discount_amount' => $charge['discount_amount'], 'total_amount' => $charge['final_amount'],
            'paid_amount' => 0, 'remaining_amount' => $charge['final_amount'], 'status' => 'Belum Dibayar',
        ]);

        return [$bill, true];
    }

    private function syncSppBillPayments(Bill $bill): void
    {
        $items = SppPaymentItem::where('student_id', $bill->student_id)->where('year', $bill->year)->where('month', $bill->month)->get();
        foreach ($items as $item) {
            $bill->allocations()->updateOrCreate(['payment_type' => 'spp', 'payment_id' => $item->spp_payment_id], ['amount' => $item->paid_amount]);
        }
        $this->refresh($bill);
    }

    private function syncOtherBillPayments(Bill $bill): void
    {
        $query = OtherPayment::where('student_id', $bill->student_id)->where('fee_type_id', $bill->fee_type_id);
        if ($bill->feeType?->period === 'Bulanan') {
            $query->whereYear('transaction_at', $bill->year)->whereMonth('transaction_at', $bill->month);
        } elseif ($bill->feeType?->period === 'Tahunan' && $bill->academicYear?->start_date && $bill->academicYear?->end_date) {
            $query->whereBetween('transaction_at', [$bill->academicYear->start_date->startOfDay(), $bill->academicYear->end_date->endOfDay()]);
        }
        foreach ($query->get() as $payment) {
            if ($payment->status === 'Diterima') {
                $bill->allocations()->updateOrCreate(['payment_type' => 'other', 'payment_id' => $payment->id], ['amount' => $payment->paid_amount]);
            } else {
                $bill->allocations()->where('payment_type', 'other')->where('payment_id', $payment->id)->delete();
            }
        }
        $this->refresh($bill);
    }

    private function refresh(Bill $bill): void
    {
        if ($bill->status === 'Dibatalkan') {
            return;
        }
        $paid = min($bill->total_amount, (int) $bill->allocations()->sum('amount'));
        $remaining = max(0, $bill->total_amount - $paid);
        $bill->update([
            'paid_amount' => $paid, 'remaining_amount' => $remaining,
            'status' => $remaining === 0 ? 'Lunas' : ($paid > 0 ? 'Sebagian' : 'Belum Dibayar'),
        ]);
    }

    private function baseBill(Student $student, AcademicYear $academicYear): array
    {
        $student->loadMissing('schoolClass.educationUnit');

        return [
            'student_id' => $student->id, 'academic_year_id' => $academicYear->id,
            'unit_name' => $student->schoolClass?->educationUnit?->name, 'class_name' => $student->schoolClass?->name,
        ];
    }

    private function students(AcademicYear $academicYear, array $filters)
    {
        return Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->where('academic_year_id', $academicYear->id)
            ->when($filters['unit_id'] ?? null, fn ($query, $id) => $query->whereHas('schoolClass', fn ($class) => $class->where('education_unit_id', $id)))
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('school_class_id', $id))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('id', $id))
            ->when(($filters['student_search'] ?? null) && ! ($filters['student_id'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['student_search']);
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhereHas('schoolClass.educationUnit', fn ($unit) => $unit->where('code', 'like', "%{$search}%"));
                });
            })
            ->when($filters['student_name'] ?? null, fn ($query, $name) => $query->where('name', 'like', '%'.trim($name).'%'))
            ->when($filters['nis'] ?? null, fn ($query, $nis) => $query->where('nis', 'like', '%'.trim($nis).'%'));
    }

    private function studentBillingStart(Student $student, AcademicYear $academicYear): CarbonImmutable
    {
        $date = $student->entry_date
            ?? $academicYear->start_date
            ?? now();

        return CarbonImmutable::parse($date)->startOfMonth();
    }

    private function eligible(Student $student, CarbonImmutable $month): bool
    {
        return (! $student->entry_date || $student->entry_date->lte($month->endOfMonth()))
            && (! $student->exit_date || $student->exit_date->gte($month->startOfMonth()));
    }

    private function feePeriod(FeeType $feeType): string
    {
        return in_array($feeType->period, ['Bulanan', 'Tahunan', 'Sekali Bayar'], true) ? $feeType->period : 'Sekali Bayar';
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }
}
