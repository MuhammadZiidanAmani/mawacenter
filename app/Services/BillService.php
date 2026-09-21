<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillSyncRun;
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
use Throwable;

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

    public function refreshCurrentBillScope(AcademicYear $academicYear, array $filters = []): array
    {
        $studentIds = $this->students($academicYear, $filters)->pluck('id');

        return $this->refreshBillsForStudents($studentIds)->all();
    }

    public function activeSyncRun(): ?BillSyncRun
    {
        return BillSyncRun::whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();
    }

    private function syncPlan(AcademicYear $academicYear, Collection $studentIds, Collection $feeTypeIds, CarbonImmutable $endPeriod): array
    {
        $plan = ['candidates' => 0, 'missing' => 0, 'existing' => 0, 'skipped' => 0];
        if ($studentIds->isEmpty()) {
            return $plan;
        }

        $feeTypesById = FeeType::with('academicYear')
            ->whereIn('id', $feeTypeIds)
            ->get()
            ->keyBy('id');
        $feeTypes = $feeTypeIds
            ->map(fn ($id) => $feeTypesById->get($id))
            ->filter()
            ->values();
        $sppFeeTypes = FeeType::query()
            ->paymentGroup('spp')
            ->where('is_active', true)
            ->where('creates_bill', true)
            ->get();

        Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->whereIn('id', $studentIds)
            ->orderBy('id')
            ->chunkById(100, function (Collection $students) use ($academicYear, $feeTypes, $sppFeeTypes, $endPeriod, &$plan): void {
                $keys = [];

                foreach ($students as $student) {
                    $period = $this->studentBillingStart($student);

                    if ($period->gt($endPeriod)) {
                        $plan['candidates']++;
                        $plan['skipped']++;
                    } else {
                        while ($period->lte($endPeriod)) {
                            $plan['candidates']++;

                            if (
                                ! $this->eligible($student, $period)
                                || $this->sppIsIncludedInRegistration($student, $period->year, $period->month)
                                || ! $this->hasSppFeeForStudent($student, $sppFeeTypes)
                            ) {
                                $plan['skipped']++;
                                $period = $period->addMonth();

                                continue;
                            }

                            $keys[] = $this->sppGenerationKey($student->id, $period->year, $period->month);
                            $period = $period->addMonth();
                        }
                    }

                    foreach ($feeTypes as $feeType) {
                        $plan['candidates']++;

                        if (! $this->feeTypeAppliesToStudentForSync($feeType, $student)) {
                            $plan['skipped']++;

                            continue;
                        }

                        $keys[] = $this->feeTypeGenerationKey($student->id, $feeType, $academicYear, (int) $endPeriod->year, (int) $endPeriod->month);
                    }
                }

                if ($keys === []) {
                    return;
                }

                $existingKeys = [];
                foreach (array_chunk(array_values(array_unique($keys)), 1000) as $chunk) {
                    $existingKeys += Bill::whereIn('generation_key', $chunk)
                        ->pluck('generation_key')
                        ->flip()
                        ->all();
                }

                foreach ($keys as $key) {
                    isset($existingKeys[$key])
                        ? $plan['existing']++
                        : $plan['missing']++;
                }
            });

        return $plan;
    }

    public function createSyncRun(AcademicYear $academicYear, int $year, int $untilMonth, array $filters, ?int $userId = null): BillSyncRun
    {
        if ($active = $this->activeSyncRun()) {
            return $active;
        }

        $studentIds = $this->students($academicYear, $filters)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $feeTypeIds = $this->syncFeeTypes($filters)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $endPeriod = CarbonImmutable::create($year, $untilMonth, 1)->startOfMonth();
        $plan = $this->syncPlan($academicYear, $studentIds, $feeTypeIds, $endPeriod);

        if ($plan['missing'] === 0) {
            return BillSyncRun::create([
                'user_id' => $userId,
                'academic_year_id' => $academicYear->id,
                'year' => $year,
                'until_month' => $untilMonth,
                'status' => 'completed',
                'phase' => 'completed',
                'filters' => $filters,
                'student_ids' => $studentIds->all(),
                'fee_type_ids' => $feeTypeIds->all(),
                'phase_data' => ['no_new_bills' => true],
                'total_items' => 0,
                'processed_items' => 0,
                'created_items' => 0,
                'existing_items' => $plan['existing'],
                'skipped_items' => $plan['skipped'],
                'refreshed_items' => 0,
                'failed_items' => 0,
                'percent' => 100,
                'message' => 'Semua tagihan sudah tersinkron. Tidak ada tagihan baru untuk dibuat.',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        $refreshQuery = Bill::whereIn('student_id', $studentIds)->where('status', '!=', 'Dibatalkan');
        $refreshTotal = $studentIds->isEmpty() ? 0 : (clone $refreshQuery)->count();
        $maxExistingBillId = $studentIds->isEmpty() ? 0 : (int) (clone $refreshQuery)->max('id');
        $total = max(1, $plan['candidates'] + $refreshTotal);

        return BillSyncRun::create([
            'user_id' => $userId,
            'academic_year_id' => $academicYear->id,
            'year' => $year,
            'until_month' => $untilMonth,
            'status' => 'pending',
            'phase' => 'spp',
            'filters' => $filters,
            'student_ids' => $studentIds->all(),
            'fee_type_ids' => $feeTypeIds->all(),
            'phase_data' => ['fee_type_index' => 0, 'max_existing_bill_id' => $maxExistingBillId],
            'total_items' => $total,
            'message' => 'Menunggu proses sinkron dimulai.',
        ]);
    }

    public function processSyncRun(BillSyncRun $run): BillSyncRun
    {
        if (! $run->isActive()) {
            return $run->refresh();
        }

        try {
            if ($run->status === 'pending') {
                $run->forceFill([
                    'status' => 'processing',
                    'started_at' => $run->started_at ?? now(),
                    'message' => 'Memulai sinkron tagihan.',
                ])->save();
            }

            $processedBefore = (int) $run->processed_items;
            $guard = 0;

            while ($run->isActive() && (int) $run->processed_items - $processedBefore < 250 && $guard < 8) {
                $guard++;

                match ($run->phase) {
                    'spp' => $this->processSyncRunSpp($run),
                    'fee_types' => $this->processSyncRunFeeTypes($run),
                    'refresh' => $this->processSyncRunRefresh($run),
                    default => $this->completeSyncRun($run),
                };

                $run->refresh();
            }
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => 'failed',
                'failed_items' => (int) $run->failed_items + 1,
                'error_message' => $exception->getMessage(),
                'message' => 'Sinkron tagihan gagal. '.$exception->getMessage(),
                'finished_at' => now(),
            ])->save();
        }

        return $run->refresh();
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

        if ($payment->status !== 'Diterima') {
            $this->removePayment('spp', $payment->id);

            return;
        }

        DB::transaction(function () use ($payment) {
            foreach ($payment->items as $item) {
                $period = CarbonImmutable::create((int) $item->year, (int) $item->month, 1)->startOfMonth();
                if (! $this->eligible($payment->student, $period)) {
                    continue;
                }

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

    public function refreshFeeTypeBills(FeeType $feeType): array
    {
        $result = ['updated' => 0, 'skipped' => 0];

        Bill::with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType.academicYear'])
            ->where('source_type', 'fee_type')
            ->where('fee_type_id', $feeType->id)
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

        return $result;
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

    private function processSyncRunSpp(BillSyncRun $run): void
    {
        $academicYear = $run->academicYear;
        $studentIds = collect($run->student_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if (! $academicYear || $studentIds->isEmpty()) {
            $this->advanceSyncRunPhase($run, 'fee_types', 'Memeriksa kategori pembayaran lain.');

            return;
        }

        $students = Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->whereIn('id', $studentIds)
            ->where('id', '>', (int) $run->cursor_id)
            ->orderBy('id')
            ->limit(25)
            ->get();

        if ($students->isEmpty()) {
            $this->advanceSyncRunPhase($run, 'fee_types', 'Memeriksa kategori pembayaran lain.');

            return;
        }

        $endPeriod = CarbonImmutable::create((int) $run->year, (int) $run->until_month, 1)->startOfMonth();
        $existingKeys = $this->existingSppKeysUntil($students, $endPeriod);
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0, 'refreshed' => 0];
        $processed = 0;

        DB::transaction(function () use ($academicYear, $endPeriod, $students, $existingKeys, &$result, &$processed) {
            foreach ($students as $student) {
                $period = $this->studentBillingStart($student);

                if ($period->gt($endPeriod)) {
                    $result['skipped']++;
                    $processed++;

                    continue;
                }

                while ($period->lte($endPeriod)) {
                    $processed++;

                    if (! $this->eligible($student, $period) || $this->sppIsIncludedInRegistration($student, $period->year, $period->month)) {
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

        $run->cursor_id = (int) $students->last()->id;
        $this->applySyncRunProgress($run, $result, $processed, 'Membuat tagihan SPP.');
    }

    private function processSyncRunFeeTypes(BillSyncRun $run): void
    {
        $academicYear = $run->academicYear;
        $studentIds = collect($run->student_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $feeTypeIds = collect($run->fee_type_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $phaseData = $run->phase_data ?? [];
        $feeTypeIndex = (int) ($phaseData['fee_type_index'] ?? 0);

        if (! $academicYear || $studentIds->isEmpty() || $feeTypeIndex >= $feeTypeIds->count()) {
            $this->advanceSyncRunPhase($run, 'refresh', 'Memperbarui nominal dan sisa tagihan.');

            return;
        }

        $feeType = FeeType::find($feeTypeIds[$feeTypeIndex]);
        if (! $feeType) {
            $phaseData['fee_type_index'] = $feeTypeIndex + 1;
            $run->forceFill(['phase_data' => $phaseData, 'cursor_id' => 0])->save();

            return;
        }

        $students = Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->whereIn('id', $studentIds)
            ->where('id', '>', (int) $run->cursor_id)
            ->orderBy('id')
            ->limit(60)
            ->get();

        if ($students->isEmpty()) {
            $phaseData['fee_type_index'] = $feeTypeIndex + 1;
            $run->forceFill([
                'phase_data' => $phaseData,
                'cursor_id' => 0,
                'message' => 'Melanjutkan kategori pembayaran berikutnya.',
            ])->save();

            return;
        }

        $existingKeys = $this->existingFeeTypeKeys($students, $feeType);
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0, 'refreshed' => 0];
        $processed = 0;

        DB::transaction(function () use ($academicYear, $feeType, $run, $students, $existingKeys, &$result, &$processed) {
            foreach ($students as $student) {
                $processed++;

                if (isset($existingKeys[$this->feeTypeGenerationKey($student->id, $feeType, $academicYear, (int) $run->year, (int) $run->until_month)])) {
                    $result['existing']++;

                    continue;
                }

                try {
                    [$bill, $created] = $this->ensureFeeTypeBill($student, $academicYear, $feeType, (int) $run->year, (int) $run->until_month);
                    $result[$created ? 'created' : 'existing']++;
                    if ($created) {
                        $this->syncOtherBillPayments($bill);
                    }
                } catch (ValidationException) {
                    $result['skipped']++;
                }
            }
        });

        $run->cursor_id = (int) $students->last()->id;
        $this->applySyncRunProgress($run, $result, $processed, 'Membuat tagihan kategori pembayaran.');
    }

    private function processSyncRunRefresh(BillSyncRun $run): void
    {
        $studentIds = collect($run->student_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        $maxExistingBillId = (int) (($run->phase_data ?? [])['max_existing_bill_id'] ?? 0);
        if ($studentIds->isEmpty() || $maxExistingBillId < 1) {
            $this->completeSyncRun($run);

            return;
        }

        $bills = Bill::with(['student.academicYear', 'student.schoolClass.educationUnit', 'feeType.academicYear'])
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->where('id', '>', (int) $run->cursor_id)
            ->where('id', '<=', $maxExistingBillId)
            ->orderBy('id')
            ->limit(100)
            ->get();

        if ($bills->isEmpty()) {
            $this->completeSyncRun($run);

            return;
        }

        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0, 'refreshed' => 0];
        foreach ($bills as $bill) {
            $this->refreshBillAmount($bill) ? $result['refreshed']++ : $result['skipped']++;
        }

        $run->cursor_id = (int) $bills->last()->id;
        $this->applySyncRunProgress($run, $result, $bills->count(), 'Memperbarui tagihan aktif.');
    }

    private function advanceSyncRunPhase(BillSyncRun $run, string $phase, string $message): void
    {
        $run->forceFill([
            'phase' => $phase,
            'cursor_id' => 0,
            'message' => $message,
        ])->save();
    }

    private function applySyncRunProgress(BillSyncRun $run, array $result, int $processed, string $message): void
    {
        $processedItems = min((int) $run->total_items, (int) $run->processed_items + $processed);
        $percent = (int) floor(($processedItems / max(1, (int) $run->total_items)) * 100);

        $run->forceFill([
            'processed_items' => $processedItems,
            'created_items' => (int) $run->created_items + (int) ($result['created'] ?? 0),
            'existing_items' => (int) $run->existing_items + (int) ($result['existing'] ?? 0),
            'skipped_items' => (int) $run->skipped_items + (int) ($result['skipped'] ?? 0),
            'refreshed_items' => (int) $run->refreshed_items + (int) ($result['refreshed'] ?? $result['updated'] ?? 0),
            'percent' => min(99, $percent),
            'message' => $message,
        ])->save();
    }

    private function completeSyncRun(BillSyncRun $run): void
    {
        $run->forceFill([
            'status' => 'completed',
            'phase' => 'completed',
            'processed_items' => max((int) $run->processed_items, (int) $run->total_items),
            'percent' => 100,
            'message' => 'Sinkron tagihan selesai.',
            'finished_at' => now(),
        ])->save();

        PerformanceCache::bust();
    }

    private function syncFeeTypes(array $filters): Collection
    {
        return FeeType::where('is_active', true)
            ->where('creates_bill', true)
            ->when(isset($filters['unit_ids']) && is_array($filters['unit_ids']), fn ($query) => $query->whereIn('education_unit_id', $filters['unit_ids']))
            ->when(isset($filters['unit_id']) && $filters['unit_id'], fn ($query) => $query->where('education_unit_id', $filters['unit_id']))
            ->when(isset($filters['fee_type_id']) && $filters['fee_type_id'], fn ($query) => $query->where('id', $filters['fee_type_id']))
            ->where(function ($query) {
                $query->whereNull('payment_group')->orWhereNotIn('payment_group', ['spp', 'laundry']);
            })
            ->orderBy('name')
            ->get();
    }

    private function hasSppFeeForStudent(Student $student, Collection $sppFeeTypes): bool
    {
        $student->loadMissing(['academicYear', 'schoolClass']);

        return $sppFeeTypes->contains(fn (FeeType $feeType) => $feeType->matchesSchoolClass($student->schoolClass)
            && ($feeType->academic_year_id === null || (int) $feeType->academic_year_id === (int) $student->academic_year_id));
    }

    private function feeTypeAppliesToStudentForSync(FeeType $feeType, Student $student): bool
    {
        $student->loadMissing(['academicYear', 'schoolClass']);
        if (! $feeType->creates_bill || $feeType->payment_group === 'laundry' || ! $feeType->matchesStudent($student)) {
            return false;
        }

        if ($this->isRegistrationFee($feeType)) {
            return true;
        }

        return $feeType->academic_year_id === null || (int) $feeType->academic_year_id === (int) $student->academic_year_id;
    }

    private function isRegistrationFee(FeeType $feeType): bool
    {
        return $feeType->payment_group === 'daftar-ulang'
            || $feeType->code === 'DAFTAR-ULANG'
            || str_starts_with((string) $feeType->code, 'DAFTAR-ULANG-');
    }

    private function ensureSppBill(Student $student, AcademicYear $academicYear, int $year, int $month): array
    {
        $period = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        if (! $this->eligible($student, $period)) {
            throw ValidationException::withMessages(['bill' => 'Periode SPP tidak berlaku untuk siswa ini.']);
        }

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
        $items = SppPaymentItem::where('student_id', $bill->student_id)
            ->where('year', $bill->year)
            ->where('month', $bill->month)
            ->whereHas('payment', fn ($query) => $query->where('status', 'Diterima'))
            ->get();
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
                if ((int) $bill->paid_amount > 0) {
                    return false;
                }

                $bill->update([
                    'remaining_amount' => 0,
                    'status' => 'Dibatalkan',
                    'cancel_reason' => 'Tagihan tidak sesuai sasaran siswa setelah kategori pembayaran diperbarui.',
                ]);

                return true;
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
            ->when(array_key_exists('unit_ids', $filters), function ($query) use ($filters) {
                $unitIds = array_values(array_filter((array) $filters['unit_ids'], fn ($id) => $id !== null && $id !== ''));

                $unitIds === []
                    ? $query->whereRaw('1 = 0')
                    : $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds));
            })
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('school_class_id', $id))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('id', $id))
            ->when(($filters['student_search'] ?? null) && ! ($filters['student_id'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['student_search']);
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhereHas('schoolClass.educationUnit', fn ($unit) => $unit->where('code', 'like', "%{$search}%"));
                });
            })
            ->when($filters['student_name'] ?? null, fn ($query, $name) => $query->where('name', 'like', '%'.trim($name).'%'))
            ->when($filters['nis'] ?? null, fn ($query, $nis) => $query->where('nis', 'like', '%'.trim($nis).'%'));
    }

    private function studentBillingStart(Student $student): CarbonImmutable
    {
        $defaultStart = CarbonImmutable::parse(self::DEFAULT_BILLING_START_DATE)->startOfMonth();

        if ($student->billing_start_date) {
            return CarbonImmutable::parse($student->billing_start_date)->startOfMonth();
        }

        $entryStart = $student->entry_date
            ? CarbonImmutable::parse($student->entry_date)->startOfMonth()
            : null;

        return $entryStart && $entryStart->gt($defaultStart) ? $entryStart : $defaultStart;
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
