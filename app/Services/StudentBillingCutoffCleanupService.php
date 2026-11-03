<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\SppPayment;
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
            $analysis = $this->analyze($students);

            $updatedStudents = $analysis['student_ids'] === []
                ? 0
                : Student::query()
                    ->whereIn('id', $analysis['student_ids'])
                    ->update([
                        'billing_start_date' => self::CUTOFF_DATE,
                        'updated_at' => now(),
                    ]);

            $cancelledBills = $analysis['cancelable_bill_count'] < 1
                ? 0
                : $this->cancelableBillQuery($students->pluck('id'))
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

    private function analyze(Collection $students): array
    {
        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->values();
        $cancelableBillQuery = $this->cancelableBillQuery($studentIds);
        $paidBillQuery = $this->paidBillQuery($studentIds);
        $cancelableBillCount = (clone $cancelableBillQuery)->count();
        $cancelableBillAmount = (int) (clone $cancelableBillQuery)->sum('remaining_amount');
        $paidBillsPreserved = (clone $paidBillQuery)->count();
        $paidBillAmountPreserved = (int) (clone $paidBillQuery)->sum('total_amount');
        $partiallyPaidBills = (clone $paidBillQuery)->where('remaining_amount', '>', 0)->count();

        $unitCounts = $students
            ->groupBy(fn (Student $student) => $student->schoolClass?->educationUnit?->code ?? '-')
            ->map->count()
            ->sortKeys()
            ->all();

        return [
            'cutoff_date' => self::CUTOFF_DATE,
            'candidate_count' => $students->count(),
            'cancelable_bill_count' => $cancelableBillCount,
            'cancelable_bill_amount' => $cancelableBillAmount,
            'paid_bills_preserved' => $paidBillsPreserved,
            'paid_bill_amount_preserved' => $paidBillAmountPreserved,
            'partially_paid_bills' => $partiallyPaidBills,
            'historical_transactions_preserved' => $this->historicalTransactionCount($studentIds),
            'unit_counts' => $unitCounts,
            'student_ids' => $studentIds->all(),
            'previous_billing_start_dates' => $students->mapWithKeys(fn (Student $student) => [
                (string) $student->id => $student->billing_start_date?->format('Y-m-d'),
            ])->all(),
        ];
    }

    private function baseHistoricalBillQuery(Collection $studentIds): Builder
    {
        return Bill::query()
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
            });
    }

    private function paidBillQuery(Collection $studentIds): Builder
    {
        return $this->baseHistoricalBillQuery($studentIds)
            ->where(function ($query) {
                $query->where('paid_amount', '>', 0)
                    ->orWhereHas('allocations', fn ($query) => $query->where('amount', '>', 0))
                    ->orWhereHas('manualPayments', fn ($query) => $query->where('paid_amount', '>', 0))
                    ->orWhereExists(fn ($query) => $this->acceptedPaymentItemSubquery($query));
            });
    }

    private function cancelableBillQuery(Collection $studentIds): Builder
    {
        return $this->baseHistoricalBillQuery($studentIds)
            ->where(fn ($query) => $query->whereNull('paid_amount')->orWhere('paid_amount', '<=', 0))
            ->whereDoesntHave('allocations', fn ($query) => $query->where('amount', '>', 0))
            ->whereDoesntHave('manualPayments', fn ($query) => $query->where('paid_amount', '>', 0))
            ->whereNotExists(fn ($query) => $this->acceptedPaymentItemSubquery($query));
    }

    private function acceptedPaymentItemSubquery($query): void
    {
        $query->selectRaw('1')
            ->from('spp_payment_items as cutoff_items')
            ->join('spp_payments as cutoff_payments', 'cutoff_payments.id', '=', 'cutoff_items.spp_payment_id')
            ->whereColumn('cutoff_items.student_id', 'bills.student_id')
            ->whereColumn('cutoff_items.year', 'bills.year')
            ->whereColumn('cutoff_items.month', 'bills.month')
            ->where('cutoff_items.paid_amount', '>', 0)
            ->where('cutoff_payments.status', 'Diterima');
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
}
