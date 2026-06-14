<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $previousMonthStart = $monthStart->subMonth();
        $previousMonthEnd = $monthStart->subSecond();

        $incomeThisMonth = $this->incomeBetween($monthStart, $now);
        $incomePreviousMonth = $this->incomeBetween($previousMonthStart, $previousMonthEnd);
        $incomeToday = $this->incomeBetween($now->startOfDay(), $now->endOfDay());

        $bills = Bill::query()
            ->where('status', '!=', 'Dibatalkan')
            ->when($activeAcademicYear, fn ($query, $year) => $query->where('academic_year_id', $year->id));
        $totalBilled = (int) (clone $bills)->sum('total_amount');
        $totalPaid = (int) (clone $bills)->sum('paid_amount');
        $outstanding = (int) (clone $bills)->sum('remaining_amount');
        $overdueBills = (clone $bills)->where('remaining_amount', '>', 0)->whereDate('due_date', '<', $now->toDateString());
        $overdueAmount = (int) (clone $overdueBills)->sum('remaining_amount');
        $overdueCount = (clone $overdueBills)->count();

        return view('welcome', [
            'activeAcademicYear' => $activeAcademicYear,
            'stats' => [
                'income_month' => $incomeThisMonth,
                'income_today' => $incomeToday,
                'income_trend' => $this->percentageChange($incomeThisMonth, $incomePreviousMonth),
                'outstanding' => $outstanding,
                'overdue_amount' => $overdueAmount,
                'overdue_count' => $overdueCount,
                'active_students' => Student::where('is_active', true)
                    ->when($activeAcademicYear, fn ($query, $year) => $query->where('academic_year_id', $year->id))
                    ->count(),
                'collection_rate' => $totalBilled > 0 ? min(100, (int) round($totalPaid / $totalBilled * 100)) : 0,
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
            ],
            'monthlyTrend' => $this->monthlyTrend($now),
            'recentPayments' => $this->recentPayments(),
            'unitSummaries' => $this->unitSummaries($bills, $activeAcademicYear),
        ]);
    }

    private function incomeBetween(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (int) SppPayment::where('status', 'Diterima')->whereBetween('transaction_at', [$start, $end])->sum('paid_amount')
            + (int) OtherPayment::where('status', 'Diterima')->whereBetween('transaction_at', [$start, $end])->sum('paid_amount');
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    private function monthlyTrend(CarbonImmutable $now): Collection
    {
        $start = $now->startOfMonth()->subMonths(5);
        $payments = SppPayment::where('status', 'Diterima')->whereBetween('transaction_at', [$start, $now])->get(['transaction_at', 'paid_amount'])
            ->concat(OtherPayment::where('status', 'Diterima')->whereBetween('transaction_at', [$start, $now])->get(['transaction_at', 'paid_amount']));
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];

        return collect(range(5, 0))->map(function (int $monthsAgo) use ($now, $payments, $monthNames) {
            $month = $now->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $monthNames[$month->month],
                'amount' => (int) $payments->filter(fn ($payment) => $payment->transaction_at->isSameMonth($month))->sum('paid_amount'),
            ];
        });
    }

    private function recentPayments(): Collection
    {
        $spp = SppPayment::with('student.schoolClass.educationUnit')->where('status', 'Diterima')->latest('transaction_at')->limit(6)->get()
            ->map(fn ($payment) => [
                'type' => 'SPP', 'date' => $payment->transaction_at, 'amount' => $payment->paid_amount,
                'student' => $payment->student?->name ?? 'Siswa tidak ditemukan',
                'detail' => ($payment->student?->schoolClass?->educationUnit?->name ?? '-').' · '.($payment->student?->schoolClass?->name ?? '-'),
            ]);
        $other = OtherPayment::with(['student.schoolClass.educationUnit', 'feeType'])->where('status', 'Diterima')->latest('transaction_at')->limit(6)->get()
            ->map(fn ($payment) => [
                'type' => $payment->feeType?->name ?? 'Lain-lain', 'date' => $payment->transaction_at, 'amount' => $payment->paid_amount,
                'student' => $payment->student?->name ?? 'Siswa tidak ditemukan',
                'detail' => ($payment->student?->schoolClass?->educationUnit?->name ?? '-').' · '.($payment->student?->schoolClass?->name ?? '-'),
            ]);

        return $spp->concat($other)->sortByDesc('date')->take(6)->values();
    }

    private function unitSummaries(Builder $bills, ?AcademicYear $activeAcademicYear): Collection
    {
        $outstandingByUnit = (clone $bills)->selectRaw('unit_name, SUM(remaining_amount) as outstanding')
            ->groupBy('unit_name')->pluck('outstanding', 'unit_name');

        return EducationUnit::with(['schoolClasses.students' => fn ($query) => $query->where('is_active', true)
            ->when($activeAcademicYear, fn ($studentQuery, $year) => $studentQuery->where('academic_year_id', $year->id))])
            ->where('is_active', true)->orderBy('name')->get()
            ->map(fn ($unit) => [
                'code' => $unit->code,
                'name' => $unit->name,
                'students' => $unit->schoolClasses->sum(fn ($class) => $class->students->count()),
                'outstanding' => (int) ($outstandingByUnit[$unit->name] ?? 0),
            ])->sortByDesc('outstanding')->take(5)->values();
    }
}
