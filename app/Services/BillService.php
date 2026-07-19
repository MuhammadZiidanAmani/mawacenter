<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Support\PerformanceCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillService
{
    private const DEFAULT_BILLING_START_DATE = '2025-07-01';

    private const JULY_INCLUDED_IN_REGISTRATION_UNITS = ['MTS', 'MA'];

    public function __construct(private ChargeCalculator $calculator) {}

    public function generateSpp(AcademicYear $academicYear, int $year, array $months, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $students = $this->students($academicYear, $filters)->get();
        $months = array_unique(array_map('intval', $months));
        $existingKeys = $this->existingSppKeys($students, $year, $months);

        DB::transaction(function () use ($academicYear, $year, $months, $students, $existingKeys, &$result) {
            foreach ($students as $student) {
                foreach ($months as $month) {
                    $periodStart = CarbonImmutable::create($year, $month, 1);
                    if (! $this->eligible($student, $periodStart)) {
                        $result['skipped']++;

                        continue;
                    }
                    if ($this->sppIsIncludedInRegistration($student, $year, $month)) {
                        $result['skipped']++;

                        continue;
                    }

                    if (isset($existingKeys[$this->sppGenerationKey($student->id, $year, $month)])) {
                        $result['existing']++;

                        continue;
                    }

                    try {
                        [$bill, $created] = $this->ensureSppBill($student, $academicYear, $year, $month);
                        $result[$created ? 'created' : 'existing']++;
                        if ($created) {
                            $this->syncSppBillPayments($bill);
                        }
                    } catch (ValidationException) {
                        $result['skipped']++;
                    }
                }
            }
        });
        PerformanceCache::bust();

        return $result;
    }

    public function generateSppFromEntryUntil(AcademicYear $academicYear, int $endYear, int $endMonth, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $students = $this->students($academicYear, $filters)->get();
        $endPeriod = CarbonImmutable::create($endYear, $endMonth, 1)->startOfMonth();
        $existingKeys = $this->existingSppKeysUntil($students, $endPeriod);

        DB::transaction(function () use ($academicYear, $endPeriod, $students, $existingKeys, &$result) {
            foreach ($students as $student) {
                $period = $this->studentBillingStart($student);

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
                    if ($this->sppIsIncludedInRegistration($student, $period->year, $period->month)) {
                        $result['skipped']++;
                        $period = $period->addMonth();

                        continue;
                    }

                    if (isset($existingKeys[$this->sppGenerationKey($student->id, $period->year, $period->month)])) {
                        $result['existing']++;
                        $period = $period->addMonth();

                        continue;
                    }

                    try {
                        [$bill, $created] = $this->ensureSppBill($student, $academicYear, $period->year, $period->month);
                        $result[$created ? 'created' : 'existing']++;
                        if ($created) {
                            $this->syncSppBillPayments($bill);
                        }
                    } catch (ValidationException) {
                        $result['skipped']++;
                    }

                    $period = $period->addMonth();
                }
            }
        });
        PerformanceCache::bust();

        return $result;
    }

    public function syncStudentCurrentBills(Student $student, ?int $endYear = null, ?int $endMonth = null): array
    {
        $student->loadMissing(['academicYear', 'schoolClass.educationUnit']);
        $academicYear = $student->academicYear ?? AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            return ['created' => 0, 'existing' => 0, 'skipped' => 1, 'refreshed' => 0];
        }

        return $this->syncStudentsCurrentBills($academicYear, [$student->id], $endYear, $endMonth);
    }

    public function syncStudentsCurrentBills(AcademicYear $academicYear, array|Collection $students, ?int $endYear = null, ?int $endMonth = null): array
    {
        $studentIds = collect($students)
            ->map(fn ($student) => $student instanceof Student ? $student->id : (int) $student)
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return ['created' => 0, 'existing' => 0, 'skipped' => 0, 'refreshed' => 0];
        }

        $endYear ??= now()->year;
        $endMonth ??= now()->month;
        $filters = ['student_ids' => $studentIds->all()];
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0, 'refreshed' => 0];

        $this->mergeResult($result, $this->generateSppFromEntryUntil($academicYear, $endYear, $endMonth, $filters));

        $feeTypes = FeeType::where('is_active', true)
            ->where('creates_bill', true)
            ->where(function ($query) {
                $query->whereNull('payment_group')->orWhereNotIn('payment_group', ['spp', 'laundry']);
            })
            ->orderBy('name')
            ->get();

        $this->mergeResult($result, $this->generateFeeTypes($academicYear, $feeTypes, $endYear, $endMonth, $filters));
        $result['refreshed'] += $this->refreshBillsForStudents($studentIds)->get('updated', 0);
        PerformanceCache::bust();

        return $result;
    }

    public function generateFeeType(AcademicYear $academicYear, FeeType $feeType, ?int $year, ?int $month, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];

        if (! $feeType->creates_bill || $feeType->payment_group === 'laundry') {
            return $result;
        }

        $students = $this->students($academicYear, $filters)->get();
        $existingKeys = $this->existingFeeTypeKeys($students, $feeType);

        DB::transaction(function () use ($academicYear, $feeType, $year, $month, $students, $existingKeys, &$result) {
            foreach ($students as $student) {
                if (isset($existingKeys[$this->feeTypeGenerationKey($student->id, $feeType, $academicYear, $year, $month)])) {
                    $result['existing']++;

                    continue;
                }

                try {
                    [$bill, $created] = $this->ensureFeeTypeBill($student, $academicYear, $feeType, $year, $month);
                    $result[$created ? 'created' : 'existing']++;
                    if ($created) {
                        $this->syncOtherBillPayments($bill);
                    }
                } catch (ValidationException) {
                    $result['skipped']++;
                }
            }
        });
        PerformanceCache::bust();

        return $result;
    }

    public function generateFeeTypes(AcademicYear $academicYear, Collection $feeTypes, ?int $year, ?int $month, array $filters = []): array
    {
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $feeTypes = $feeTypes
            ->filter(fn (FeeType $feeType) => $feeType->creates_bill && $feeType->payment_group !== 'laundry')
            ->values();

        if ($feeTypes->isEmpty()) {
            return $result;
        }

        $students = $this->students($academicYear, $filters)->get();
        $existingKeys = $this->existingFeeTypeKeysForTypes($students, $feeTypes);

        DB::transaction(function () use ($academicYear, $feeTypes, $year, $month, $students, $existingKeys, &$result) {
            foreach ($feeTypes as $feeType) {
                foreach ($students as $student) {
                    if (isset($existingKeys[$this->feeTypeGenerationKey($student->id, $feeType, $academicYear, $year, $month)])) {
                        $result['existing']++;

                        continue;
                    }

                    try {
                        [$bill, $created] = $this->ensureFeeTypeBill($student, $academicYear, $feeType, $year, $month);
                        $result[$created ? 'created' : 'existing']++;
                        if ($created) {
                            $this->syncOtherBillPayments($bill);
                        }
                    } catch (ValidationException) {
                        $result['skipped']++;
                    }
                }
            }
        });
        PerformanceCache::bust();

        return $result;
    }

    public function createManual(Student $student, array $data): Bill
    {
        $total = (int) $data['amount'];

        $bill = Bill::create([
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
        PerformanceCache::bust();

        return $bill;
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
        PerformanceCache::bust();

        return $bill->refresh();
    }

    public function syncSppPayment(SppPayment $payment): void
    {
        $payment->loadMissing(['student.academicYear', 'student.schoolClass.educationUnit', 'items']);

        DB::transaction(function () use ($payment) {
            foreach ($payment->items as $item) {
                if ($this->sppIsIncludedInRegistration($payment->student, $item->year, $item->month)) {
                    continue;
                }

                [$bill] = $this->ensureSppBill($payment->student, $payment->student->academicYear, $item->year, $item->month);
                $bill->allocations()->updateOrCreate(
                    ['payment_type' => 'spp', 'payment_id' => $payment->id],
                    ['amount' => $item->paid_amount],
                );
                $this->refresh($bill);
            }
        });
        PerformanceCache::bust();
    }

    public function syncOtherPayment(OtherPayment $payment): void
    {
        $payment->loadMissing(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType.academicYear']);
        if (! $payment->feeType?->creates_bill || $payment->feeType?->payment_group === 'laundry') {
            return;
        }

        $date = CarbonImmutable::parse($payment->transaction_at);
        $academicYear = $payment->feeType->academicYear ?? $payment->student->academicYear;
        [$bill] = $this->ensureFeeTypeBill($payment->student, $academicYear, $payment->feeType, $date->year, $date->month);
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
        PerformanceCache::bust();
    }

    public function removePayment(string $type, int $paymentId): void
    {
        $bills = Bill::whereHas('allocations', fn ($query) => $query->where('payment_type', $type)->where('payment_id', $paymentId))->get();
        foreach ($bills as $bill) {
            $bill->allocations()->where('payment_type', $type)->where('payment_id', $paymentId)->delete();
            $this->refresh($bill);
        }
        PerformanceCache::bust();
    }

    public function refreshDiscountBills(FeeDiscount $discount): array
    {
        $discount->loadMissing('student.academicYear', 'student.schoolClass.educationUnit');
        if (! $discount->student) {
            return ['updated' => 0, 'skipped' => 1];
        }

        $result = $this->refreshStudentBills($discount->student);
        $this->syncStudentCurrentBills($discount->student);

        return $result;
    }

    public function refreshStudentBills(Student $student): array
    {
        return $this->refreshBillsForStudents([$student->id])->all();
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
                $query->where('creates_bill', true)
                    ->where(function ($query) {
                        $query->whereNull('payment_group')->orWhere('payment_group', '!=', 'laundry');
                    });
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
        PerformanceCache::bust();

        return $bill->refresh();
    }

    private function ensureSppBill(Student $student, AcademicYear $academicYear, int $year, int $month): array
    {
        if ($this->sppIsIncludedInRegistration($student, $year, $month)) {
            throw ValidationException::withMessages(['bill' => 'SPP bulan Juli untuk unit MTs/MA sudah termasuk Daftar Ulang.']);
        }

        $key = $this->sppGenerationKey($student->id, $year, $month);
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
        $periodKey = $this->feeTypePeriodKey($feeType, $academicYear, $year, $month);
        $key = $this->feeTypeGenerationKey($student->id, $feeType, $academicYear, $year, $month);
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

    private function refreshBillsForStudents(array|Collection $studentIds): Collection
    {
        $result = ['updated' => 0, 'skipped' => 0];
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($studentIds->isEmpty()) {
            return collect($result);
        }

        Bill::with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType.academicYear'])
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->orderBy('id')
            ->chunkById(100, function (Collection $bills) use (&$result) {
                foreach ($bills as $bill) {
                    if ($this->refreshBillAmount($bill)) {
                        $result['updated']++;
                    } else {
                        $result['skipped']++;
                    }
                }
            });

        PerformanceCache::bust();

        return collect($result);
    }

    private function refreshBillAmount(Bill $bill): bool
    {
        $student = $bill->student;
        if (! $student) {
            return false;
        }

        if ($bill->source_type === 'spp') {
            if (! $bill->year || ! $bill->month) {
                return false;
            }
            $period = CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1)->startOfMonth();
            if (! $this->eligible($student, $period) || $this->sppIsIncludedInRegistration($student, (int) $bill->year, (int) $bill->month)) {
                if ((int) $bill->paid_amount > 0) {
                    return false;
                }

                $bill->update([
                    'remaining_amount' => 0,
                    'status' => 'Dibatalkan',
                    'cancel_reason' => 'Tagihan tidak berlaku setelah data siswa diperbarui.',
                ]);

                return true;
            }

            $charge = $this->calculator->calculateSppMonth($student, (int) $bill->year, (int) $bill->month);
            if ($charge['original_amount'] < 1) {
                return false;
            }

            $academicYear = $bill->academicYear ?? $student->academicYear;
            if (! $academicYear) {
                return false;
            }

            $bill->update($this->baseBill($student, $academicYear) + [
                'title' => 'SPP '.$this->monthName((int) $bill->month).' '.$bill->year,
                'original_amount' => $charge['original_amount'],
                'discount_amount' => $charge['discount_amount'],
                'total_amount' => $charge['final_amount'],
            ]);
            $this->syncSppBillPayments($bill->refresh());

            return true;
        }

        if ($bill->source_type === 'fee_type') {
            $feeType = $bill->feeType;
            if (! $feeType) {
                return false;
            }

            $date = $bill->issue_date?->toImmutable()
                ?? ($bill->year && $bill->month ? CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1) : now()->toImmutable());
            $charge = $this->calculator->calculate($student, 'fee_type', $feeType, $date);
            if ($charge['original_amount'] < 1) {
                return false;
            }

            $academicYear = $bill->academicYear ?? $feeType->academicYear ?? $student->academicYear;
            if (! $academicYear) {
                return false;
            }

            $suffix = $bill->month
                ? ' '.$this->monthName((int) $bill->month).' '.$bill->year
                : ($this->feePeriod($feeType) === 'Tahunan' ? ' '.$academicYear->name : '');
            $bill->update($this->baseBill($student, $academicYear) + [
                'title' => $feeType->name.$suffix,
                'original_amount' => $charge['original_amount'],
                'discount_amount' => $charge['discount_amount'],
                'total_amount' => $charge['final_amount'],
            ]);
            $this->syncOtherBillPayments($bill->refresh());

            return true;
        }

        $academicYear = $bill->academicYear ?? $student->academicYear;
        if (! $academicYear) {
            return false;
        }

        $bill->update($this->baseBill($student, $academicYear));
        $this->refresh($bill->refresh());

        return true;
    }

    private function baseBill(Student $student, AcademicYear $academicYear): array
    {
        $student->loadMissing('schoolClass.educationUnit');

        return [
            'student_id' => $student->id, 'academic_year_id' => $academicYear->id,
            'unit_name' => $student->schoolClass?->educationUnit?->name, 'class_name' => $student->schoolClass?->name,
        ];
    }

    private function existingSppKeys(Collection $students, int $year, array $months): array
    {
        $studentIds = $students->pluck('id')->all();
        if ($studentIds === [] || $months === []) {
            return [];
        }

        return Bill::where('source_type', 'spp')
            ->whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->whereIn('month', $months)
            ->pluck('generation_key')
            ->flip()
            ->all();
    }

    private function existingSppKeysUntil(Collection $students, CarbonImmutable $endPeriod): array
    {
        $studentIds = $students->pluck('id')->all();
        if ($studentIds === []) {
            return [];
        }

        return Bill::where('source_type', 'spp')
            ->whereIn('student_id', $studentIds)
            ->where(function ($query) use ($endPeriod) {
                $query->where('year', '<', $endPeriod->year)
                    ->orWhere(function ($query) use ($endPeriod) {
                        $query->where('year', $endPeriod->year)
                            ->where('month', '<=', $endPeriod->month);
                    });
            })
            ->pluck('generation_key')
            ->flip()
            ->all();
    }

    private function existingFeeTypeKeys(Collection $students, FeeType $feeType): array
    {
        $studentIds = $students->pluck('id')->all();
        if ($studentIds === []) {
            return [];
        }

        return Bill::where('source_type', 'fee_type')
            ->where('fee_type_id', $feeType->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('generation_key')
            ->flip()
            ->all();
    }

    private function existingFeeTypeKeysForTypes(Collection $students, Collection $feeTypes): array
    {
        $studentIds = $students->pluck('id')->all();
        $feeTypeIds = $feeTypes->pluck('id')->all();
        if ($studentIds === [] || $feeTypeIds === []) {
            return [];
        }

        return Bill::where('source_type', 'fee_type')
            ->whereIn('fee_type_id', $feeTypeIds)
            ->whereIn('student_id', $studentIds)
            ->pluck('generation_key')
            ->flip()
            ->all();
    }

    private function sppGenerationKey(int $studentId, int $year, int $month): string
    {
        return hash('sha256', "spp|{$studentId}|{$year}|{$month}");
    }

    private function feeTypeGenerationKey(int $studentId, FeeType $feeType, AcademicYear $academicYear, ?int $year, ?int $month): string
    {
        $period = $this->feePeriod($feeType);
        $year ??= $academicYear->start_date?->year ?? (int) explode('/', $academicYear->name)[0];
        $month = $period === 'Bulanan' ? ($month ?? now()->month) : null;
        $periodKey = $this->feeTypePeriodKey($feeType, $academicYear, $year, $month);

        return hash('sha256', "fee|{$studentId}|{$feeType->id}|{$periodKey}");
    }

    private function feeTypePeriodKey(FeeType $feeType, AcademicYear $academicYear, int $year, ?int $month): string|int
    {
        $period = $this->feePeriod($feeType);

        return $period === 'Sekali Bayar' ? 'once' : ($period === 'Tahunan' ? $academicYear->id : "{$year}|{$month}");
    }

    private function students(AcademicYear $academicYear, array $filters)
    {
        return Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->where('academic_year_id', $academicYear->id)
            ->when($filters['student_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))
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

    private function studentBillingStart(Student $student): CarbonImmutable
    {
        if ($student->billing_start_date) {
            return CarbonImmutable::parse($student->billing_start_date)->startOfMonth();
        }

        return CarbonImmutable::parse(self::DEFAULT_BILLING_START_DATE)->startOfMonth();
    }

    private function eligible(Student $student, CarbonImmutable $month): bool
    {
        $startDate = $this->studentBillingStart($student);

        return $startDate->lte($month->endOfMonth())
            && (! $student->exit_date || $student->exit_date->gte($month->startOfMonth()));
    }

    private function sppIsIncludedInRegistration(Student $student, int $year, int $month): bool
    {
        if ($month !== 7) {
            return false;
        }

        $student->loadMissing('schoolClass.educationUnit');
        $unit = $student->schoolClass?->educationUnit;
        $unitCode = strtoupper(trim((string) $unit?->code));
        $unitName = strtoupper(trim((string) $unit?->name));

        return in_array($unitCode, self::JULY_INCLUDED_IN_REGISTRATION_UNITS, true)
            || str_contains($unitName, 'TSANAWIYAH')
            || str_contains($unitName, 'ALIYAH');
    }

    private function feePeriod(FeeType $feeType): string
    {
        return in_array($feeType->period, ['Bulanan', 'Tahunan', 'Sekali Bayar'], true) ? $feeType->period : 'Sekali Bayar';
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }

    private function mergeResult(array &$base, array $addition): void
    {
        foreach (['created', 'existing', 'skipped'] as $key) {
            $base[$key] += $addition[$key] ?? 0;
        }
    }
}
