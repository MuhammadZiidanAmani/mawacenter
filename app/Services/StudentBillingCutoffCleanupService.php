<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillManualPayment;
use App\Models\BillPaymentAllocation;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Support\PerformanceCache;
use App\Support\StudentBillingStart;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentBillingCutoffCleanupService
{
    public const CUTOFF_DATE = '2026-01-01';

    public function preview(?array $unitIds = null): array
    {
        return $this->analyze($this->candidateQuery($unitIds)->get());
    }

    public function apply(?array $unitIds = null): array
    {
        $result = DB::transaction(function () use ($unitIds) {
            $students = $this->candidateQuery($unitIds)->lockForUpdate()->get();
            $analysis = $this->analyze($students, true);

            $updatedStudents = $analysis['student_ids'] === []
                ? 0
                : Student::query()
                    ->whereIn('id', $analysis['student_ids'])
                    ->update([
                        'billing_start_date' => self::CUTOFF_DATE,
                        'updated_at' => now(),
                    ]);

            $cancelledBills = $analysis['cancelable_bill_ids'] === []
                ? 0
                : Bill::query()
                    ->whereIn('id', $analysis['cancelable_bill_ids'])
                    ->where('source_type', 'spp')
                    ->where('status', '!=', 'Dibatalkan')
                    ->where(fn ($query) => $query->whereNull('paid_amount')->orWhere('paid_amount', '<=', 0))
                    ->whereDoesntHave('allocations', fn ($query) => $query->where('amount', '>', 0))
                    ->whereDoesntHave('manualPayments', fn ($query) => $query->where('paid_amount', '>', 0))
                    ->update([
                        'remaining_amount' => 0,
                        'status' => 'Dibatalkan',
                        'cancel_reason' => 'Tagihan SPP sebelum cut-off 1 Januari 2026 tidak dimigrasikan.',
                        'updated_at' => now(),
                    ]);

            return $analysis + [
                'updated_students' => $updatedStudents,
                'cancelled_bills' => $cancelledBills,
            ];
        });

        PerformanceCache::invalidate(['dashboard', 'bills', 'reports']);

        return $result;
    }

    private function candidateQuery(?array $unitIds): Builder
    {
        return Student::query()
            ->with(['schoolClass.educationUnit:id,code,name'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereDate('billing_start_date', '<', self::CUTOFF_DATE)
                    ->orWhere(function ($query) {
                        $query->whereNull('billing_start_date')
                            ->whereDate('entry_date', '<', self::CUTOFF_DATE);
                    });
            })
            ->whereHas('schoolClass.educationUnit', function ($query) use ($unitIds) {
                $query->whereIn(DB::raw('UPPER(code)'), StudentBillingStart::unitCodes());

                if (is_array($unitIds)) {
                    $unitIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('education_units.id', $unitIds);
                }
            })
            ->orderBy('students.id');
    }

    private function analyze(Collection $students, bool $lockBills = false): array
    {
        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->values();
        $billQuery = Bill::query()
            ->whereIn('student_id', $studentIds)
            ->where('source_type', 'spp')
            ->where('status', '!=', 'Dibatalkan')
            ->whereNotNull('year')
            ->whereNotNull('month')
            ->where(function ($query) {
                $cutoff = CarbonImmutable::parse(self::CUTOFF_DATE);
                $query->where('year', '<', $cutoff->year)
                    ->orWhere(fn ($query) => $query
                        ->where('year', $cutoff->year)
                        ->where('month', '<', $cutoff->month));
            })
            ->orderBy('id');

        if ($lockBills) {
            $billQuery->lockForUpdate();
        }

        $bills = $billQuery->get([
            'id', 'student_id', 'year', 'month', 'total_amount', 'paid_amount', 'remaining_amount',
        ]);
        $billIds = $bills->pluck('id');
        $allocationAmounts = BillPaymentAllocation::query()
            ->whereIn('bill_id', $billIds)
            ->groupBy('bill_id')
            ->selectRaw('bill_id, SUM(amount) AS aggregate')
            ->pluck('aggregate', 'bill_id');
        $manualAmounts = BillManualPayment::query()
            ->whereIn('bill_id', $billIds)
            ->groupBy('bill_id')
            ->selectRaw('bill_id, SUM(paid_amount) AS aggregate')
            ->pluck('aggregate', 'bill_id');
        $acceptedPaymentPeriods = $this->acceptedPaymentPeriods($studentIds);

        $hasPayment = fn (Bill $bill): bool => (int) $bill->paid_amount > 0
            || (int) $allocationAmounts->get($bill->id, 0) > 0
            || (int) $manualAmounts->get($bill->id, 0) > 0
            || $acceptedPaymentPeriods->has($this->periodKey($bill->student_id, $bill->year, $bill->month));
        $preservedBills = $bills->filter($hasPayment);
        $cancelableBills = $bills->reject($hasPayment);
        $reviewBills = $preservedBills->filter(fn (Bill $bill) => (int) $bill->remaining_amount > 0);

        $unitCounts = $students
            ->groupBy(fn (Student $student) => $student->schoolClass?->educationUnit?->code ?? '-')
            ->map->count()
            ->sortKeys()
            ->all();

        return [
            'cutoff_date' => self::CUTOFF_DATE,
            'candidate_count' => $students->count(),
            'cancelable_bill_count' => $cancelableBills->count(),
            'cancelable_bill_amount' => (int) $cancelableBills->sum('remaining_amount'),
            'paid_bills_preserved' => $preservedBills->count(),
            'paid_bill_amount_preserved' => (int) $preservedBills->sum('total_amount'),
            'partially_paid_bills' => $reviewBills->count(),
            'historical_transactions_preserved' => $this->historicalTransactionCount($studentIds),
            'unit_counts' => $unitCounts,
            'student_ids' => $studentIds->all(),
            'cancelable_bill_ids' => $cancelableBills->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    private function acceptedPaymentPeriods(Collection $studentIds): Collection
    {
        return SppPaymentItem::query()
            ->whereIn('student_id', $studentIds)
            ->where('paid_amount', '>', 0)
            ->where(function ($query) {
                $cutoff = CarbonImmutable::parse(self::CUTOFF_DATE);
                $query->where('year', '<', $cutoff->year)
                    ->orWhere(fn ($query) => $query
                        ->where('year', $cutoff->year)
                        ->where('month', '<', $cutoff->month));
            })
            ->whereHas('payment', fn ($query) => $query->where('status', 'Diterima'))
            ->get(['student_id', 'year', 'month'])
            ->mapWithKeys(fn (SppPaymentItem $item) => [
                $this->periodKey($item->student_id, $item->year, $item->month) => true,
            ]);
    }

    private function historicalTransactionCount(Collection $studentIds): int
    {
        return SppPayment::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('items', function ($query) {
                $cutoff = CarbonImmutable::parse(self::CUTOFF_DATE);
                $query->where('year', '<', $cutoff->year)
                    ->orWhere(fn ($query) => $query
                        ->where('year', $cutoff->year)
                        ->where('month', '<', $cutoff->month));
            })
            ->count();
    }

    private function periodKey(int $studentId, int $year, int $month): string
    {
        return $studentId.'-'.$year.'-'.$month;
    }
}
