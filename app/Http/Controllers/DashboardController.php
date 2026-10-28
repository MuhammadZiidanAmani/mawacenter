<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\Student;
use App\Support\PerformanceCache;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $now = CarbonImmutable::now();
        $unitIds = $request->user()?->accessibleUnitIds();
        $studentIds = $request->user()?->accessibleStudentIds();
        if (is_array($unitIds)) {
            sort($unitIds);
        }
        if (is_array($studentIds)) {
            sort($studentIds);
        }

        $payload = PerformanceCache::remember(
            'dashboard',
            [
                'active_academic_year_id' => $activeAcademicYear?->id,
                'date' => $now->toDateString(),
                'unit_ids' => $unitIds ?? 'all',
                'student_ids' => $studentIds ?? 'all',
            ],
            config('performance.query_cache.dashboard_ttl', 120),
            fn () => $this->dashboardPayload($activeAcademicYear, $now, $unitIds, $studentIds),
        );

        return view('welcome', [
            'activeAcademicYear' => $activeAcademicYear,
            'stats' => $payload['stats'],
            'monthlyTrend' => collect($payload['monthlyTrend']),
            'recentPayments' => collect($payload['recentPayments'])->map(fn (array $payment) => [
                ...$payment,
                'date' => CarbonImmutable::parse($payment['date']),
            ]),
            'unitSummaries' => collect($payload['unitSummaries']),
        ]);
    }

    private function dashboardPayload(
        ?AcademicYear $activeAcademicYear,
        CarbonImmutable $now,
        ?array $unitIds,
        ?array $studentIds,
    ): array {
        $monthStart = $now->startOfMonth();
        $previousMonthStart = $monthStart->subMonth();
        $previousMonthEnd = $monthStart->subSecond();

        $incomeThisMonth = $this->incomeBetween($monthStart, $now, $unitIds, $studentIds);
        $incomePreviousMonth = $this->incomeBetween($previousMonthStart, $previousMonthEnd, $unitIds, $studentIds);
        $incomeToday = $this->incomeBetween($now->startOfDay(), $now->endOfDay(), $unitIds, $studentIds);

        $bills = $this->scopeByStudentAccess(Bill::query(), $unitIds, $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->when($activeAcademicYear, fn ($query, $year) => $query->where('academic_year_id', $year->id));
        $totalBilled = (int) (clone $bills)->sum('total_amount');
        $totalPaid = (int) (clone $bills)->sum('paid_amount');
        $outstanding = (int) (clone $bills)->sum('remaining_amount');
        $overdueBills = (clone $bills)->where('remaining_amount', '>', 0)->whereDate('due_date', '<', $now->toDateString());
        $overdueAmount = (int) (clone $overdueBills)->sum('remaining_amount');
        $overdueCount = (clone $overdueBills)->count();

        return [
            'stats' => [
                'income_month' => $incomeThisMonth,
                'income_today' => $incomeToday,
                'income_trend' => $this->percentageChange($incomeThisMonth, $incomePreviousMonth),
                'outstanding' => $outstanding,
                'overdue_amount' => $overdueAmount,
                'overdue_count' => $overdueCount,
                'active_students' => $this->scopeStudents(Student::where('is_active', true), $unitIds, $studentIds)
                    ->when($activeAcademicYear, fn ($query, $year) => $query->where('academic_year_id', $year->id))
                    ->count(),
                'collection_rate' => $totalBilled > 0 ? min(100, (int) round($totalPaid / $totalBilled * 100)) : 0,
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
            ],
            'monthlyTrend' => $this->monthlyTrend($now, $unitIds, $studentIds)->all(),
            'recentPayments' => $this->recentPayments($unitIds, $studentIds)->map(fn (array $payment) => [
                ...$payment,
                'date' => $payment['date']->toIso8601String(),
            ])->all(),
            'unitSummaries' => $this->unitSummaries($bills, $activeAcademicYear, $unitIds, $studentIds)->all(),
        ];
    }

    private function incomeBetween(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?array $unitIds,
        ?array $studentIds,
    ): int {
        $spp = $this->scopeByStudentAccess(SppPayment::query(), $unitIds, $studentIds)
            ->where('status', 'Diterima')
            ->whereBetween('transaction_at', [$start, $end]);
        $other = $this->scopeByStudentAccess(OtherPayment::query(), $unitIds, $studentIds)
            ->where('status', 'Diterima')
            ->whereBetween('transaction_at', [$start, $end]);

        return (int) $spp->sum('paid_amount') + (int) $other->sum('paid_amount');
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    private function monthlyTrend(CarbonImmutable $now, ?array $unitIds, ?array $studentIds): Collection
    {
        $start = $now->startOfMonth()->subMonths(5);
        $spp = $this->scopeByStudentAccess(SppPayment::query(), $unitIds, $studentIds)
            ->where('status', 'Diterima')
            ->whereBetween('transaction_at', [$start, $now])
            ->get(['transaction_at', 'paid_amount']);
        $other = $this->scopeByStudentAccess(OtherPayment::query(), $unitIds, $studentIds)
            ->where('status', 'Diterima')
            ->whereBetween('transaction_at', [$start, $now])
            ->get(['transaction_at', 'paid_amount']);
        $payments = $spp->concat($other);
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

        return collect(range(5, 0))->map(function (int $monthsAgo) use ($now, $payments, $monthNames) {
            $month = $now->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $monthNames[$month->month],
                'amount' => (int) $payments->filter(fn ($payment) => $payment->transaction_at->isSameMonth($month))->sum('paid_amount'),
            ];
        });
    }

    private function recentPayments(?array $unitIds, ?array $studentIds): Collection
    {
        $spp = $this->scopeByStudentAccess(SppPayment::with('student.schoolClass.educationUnit'), $unitIds, $studentIds)
            ->where('status', 'Diterima')->latest('transaction_at')->limit(6)->get()
            ->map(fn ($payment) => [
                'type' => 'SPP', 'date' => $payment->transaction_at, 'amount' => $payment->paid_amount,
                'student' => $payment->student?->name ?? 'Siswa tidak ditemukan',
                'detail' => ($payment->student?->schoolClass?->educationUnit?->name ?? '-').' · '.($payment->student?->schoolClass?->name ?? '-'),
            ]);
        $other = $this->scopeByStudentAccess(OtherPayment::with(['student.schoolClass.educationUnit', 'feeType']), $unitIds, $studentIds)
            ->where('status', 'Diterima')->latest('transaction_at')->limit(6)->get()
            ->map(fn ($payment) => [
                'type' => $payment->feeType?->name ?? 'Lain-lain', 'date' => $payment->transaction_at, 'amount' => $payment->paid_amount,
                'student' => $payment->student?->name ?? 'Siswa tidak ditemukan',
                'detail' => ($payment->student?->schoolClass?->educationUnit?->name ?? '-').' · '.($payment->student?->schoolClass?->name ?? '-'),
            ]);

        return $spp->concat($other)->sortByDesc('date')->take(6)->values();
    }

    private function unitSummaries(
        Builder $bills,
        ?AcademicYear $activeAcademicYear,
        ?array $unitIds,
        ?array $studentIds,
    ): Collection {
        $outstandingByUnit = (clone $bills)->selectRaw('unit_name, SUM(remaining_amount) as outstanding')
            ->groupBy('unit_name')->pluck('outstanding', 'unit_name');

        return EducationUnit::with(['schoolClasses.students' => fn ($query) => $query->where('is_active', true)
            ->when(is_array($studentIds), fn ($studentQuery) => $studentIds === []
                ? $studentQuery->whereRaw('1 = 0')
                : $studentQuery->whereIn('students.id', $studentIds))
            ->when($activeAcademicYear, fn ($studentQuery, $year) => $studentQuery->where('academic_year_id', $year->id))])
            ->when(is_array($unitIds), fn ($query) => $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('education_units.id', $unitIds))
            ->where('is_active', true)->orderBy('name')->get()
            ->map(fn ($unit) => [
                'code' => $unit->code,
                'name' => $unit->name,
                'students' => $unit->schoolClasses->sum(fn ($class) => $class->students->count()),
                'outstanding' => (int) ($outstandingByUnit[$unit->name] ?? 0),
            ])->sortByDesc('outstanding')->take(5)->values();
    }

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
                : $query->whereHas('student.schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds));
        }

        return $query;
    }

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
                : $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds));
        }

        return $query;
    }
}
