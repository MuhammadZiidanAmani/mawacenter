<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardSummaryService
{
    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return Collection<int, array{label: string, amount: int}>
     */
    public function monthlyTrend(
        CarbonImmutable $now,
        ?AcademicYear $academicYear,
        ?array $unitIds = null,
        ?array $studentIds = null,
    ): Collection {
        $start = $now->startOfMonth()->subMonths(5);
        $academicYearId = $academicYear?->id;
        $spp = $this->aggregateIncomeByMonth(SppPayment::query(), $start, $now, $unitIds, $studentIds, $academicYearId);
        $other = $this->aggregateIncomeByMonth(OtherPayment::query(), $start, $now, $unitIds, $studentIds, $academicYearId);
        $totals = $spp->map(fn ($amount) => (int) $amount);

        $other->each(function ($amount, string $period) use ($totals): void {
            $totals->put($period, (int) $totals->get($period, 0) + (int) $amount);
        });

        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

        return collect(range(5, 0))->map(function (int $monthsAgo) use ($now, $totals, $monthNames): array {
            $month = $now->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $monthNames[$month->month],
                'amount' => (int) $totals->get($month->format('Y-m'), 0),
            ];
        });
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return array<string, int>
     */
    public function summary(
        ?AcademicYear $academicYear,
        CarbonImmutable $now,
        ?array $unitIds = null,
        ?array $studentIds = null,
    ): array {
        $monthStart = $now->startOfMonth();
        $previousMonthStart = $monthStart->subMonth();
        $previousMonthEnd = $monthStart->subSecond();
        $academicYearId = $academicYear?->id;

        $incomeThisMonth = $this->incomeBetween($monthStart, $now, $unitIds, $studentIds, $academicYearId);
        $incomePreviousMonth = $this->incomeBetween($previousMonthStart, $previousMonthEnd, $unitIds, $studentIds, $academicYearId);
        $incomeToday = $this->incomeBetween($now->startOfDay(), $now->endOfDay(), $unitIds, $studentIds, $academicYearId);

        $bills = $this->scopeByStudentAccess(Bill::query(), $unitIds, $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->when($academicYearId, fn (Builder $query, int $yearId) => $query->where('academic_year_id', $yearId));
        $totalBilled = (int) (clone $bills)->sum('total_amount');
        $totalPaid = (int) (clone $bills)->sum('paid_amount');
        $outstanding = (int) (clone $bills)->sum('remaining_amount');
        $overdueBills = (clone $bills)
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', $now->toDateString());

        return [
            'income_month' => $incomeThisMonth,
            'income_today' => $incomeToday,
            'income_trend' => $this->percentageChange($incomeThisMonth, $incomePreviousMonth),
            'outstanding' => $outstanding,
            'overdue_amount' => (int) (clone $overdueBills)->sum('remaining_amount'),
            'overdue_count' => (clone $overdueBills)->count(),
            'active_students' => $this->scopeStudents(Student::where('is_active', true), $unitIds, $studentIds)
                ->when($academicYearId, fn (Builder $query, int $yearId) => $query->where('academic_year_id', $yearId))
                ->count(),
            'collection_rate' => $totalBilled > 0 ? min(100, (int) round($totalPaid / $totalBilled * 100)) : 0,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
        ];
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     */
    private function incomeBetween(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?array $unitIds,
        ?array $studentIds,
        ?int $academicYearId,
    ): int {
        $spp = $this->scopeByAcademicYear(
            $this->scopeByStudentAccess(SppPayment::query(), $unitIds, $studentIds),
            $academicYearId,
        )->where('status', 'Diterima')->whereBetween('transaction_at', [$start, $end]);
        $other = $this->scopeByAcademicYear(
            $this->scopeByStudentAccess(OtherPayment::query(), $unitIds, $studentIds),
            $academicYearId,
        )->where('status', 'Diterima')->whereBetween('transaction_at', [$start, $end]);

        return (int) $spp->sum('paid_amount') + (int) $other->sum('paid_amount');
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return Collection<string, int|string>
     */
    private function aggregateIncomeByMonth(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?array $unitIds,
        ?array $studentIds,
        ?int $academicYearId,
    ): Collection {
        $periodExpression = $this->monthPeriodExpression($query);

        return $this->scopeByAcademicYear(
            $this->scopeByStudentAccess($query, $unitIds, $studentIds),
            $academicYearId,
        )
            ->where('status', 'Diterima')
            ->whereBetween('transaction_at', [$start, $end])
            ->selectRaw("{$periodExpression} as period, SUM(paid_amount) as amount")
            ->groupByRaw($periodExpression)
            ->pluck('amount', 'period');
    }

    private function monthPeriodExpression(Builder $query): string
    {
        return match ($query->getModel()->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', transaction_at)",
            'pgsql' => "to_char(transaction_at, 'YYYY-MM')",
            default => "DATE_FORMAT(transaction_at, '%Y-%m')",
        };
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     */
    private function scopeByStudentAccess(Builder $query, ?array $unitIds, ?array $studentIds): Builder
    {
        if (is_array($studentIds)) {
            return $studentIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('student_id', $studentIds);
        }

        if (is_array($unitIds)) {
            return $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('student.schoolClass', fn (Builder $class) => $class->whereIn('education_unit_id', $unitIds));
        }

        return $query;
    }

    private function scopeByAcademicYear(Builder $query, ?int $academicYearId): Builder
    {
        return $query->when($academicYearId, fn (Builder $paymentQuery, int $yearId) => $paymentQuery
            ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('academic_year_id', $yearId)));
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     */
    private function scopeStudents(Builder $query, ?array $unitIds, ?array $studentIds): Builder
    {
        if (is_array($studentIds)) {
            return $studentIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('students.id', $studentIds);
        }

        if (is_array($unitIds)) {
            return $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('schoolClass', fn (Builder $class) => $class->whereIn('education_unit_id', $unitIds));
        }

        return $query;
    }
}
