<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardDataService
{
    public function __construct(
        private DashboardSummaryService $summaryService,
        private DashboardActivityService $activityService,
    ) {}

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return array{
     *     stats: array<string, int>,
     *     monthlyTrend: array<int, array{label: string, amount: int}>,
     *     recentActivities: array<int, array<string, mixed>>,
     *     unitSummaries: array<int, array{code: string, name: string, students: int, outstanding: int}>
     * }
     */
    public function viewData(
        ?AcademicYear $selectedAcademicYear,
        CarbonImmutable $now,
        ?array $unitIds = null,
        ?array $studentIds = null,
    ): array {
        $bills = $this->scopeByStudentAccess(Bill::query(), $unitIds, $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->when($selectedAcademicYear, fn (Builder $query, AcademicYear $year) => $query->where('academic_year_id', $year->id));

        return [
            'stats' => $this->summaryService->summary($selectedAcademicYear, $now, $unitIds, $studentIds),
            'monthlyTrend' => $this->summaryService->monthlyTrend($now, $selectedAcademicYear, $unitIds, $studentIds)->all(),
            'recentActivities' => $this->activityService->recent($selectedAcademicYear, $unitIds, $studentIds)->map(fn (array $activity): array => [
                ...$activity,
                'date' => $activity['date']->toIso8601String(),
            ])->all(),
            'unitSummaries' => $this->unitSummaries($bills, $selectedAcademicYear, $unitIds, $studentIds)->all(),
        ];
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return Collection<int, array{code: string, name: string, students: int, outstanding: int}>
     */
    private function unitSummaries(
        Builder $bills,
        ?AcademicYear $selectedAcademicYear,
        ?array $unitIds,
        ?array $studentIds,
    ): Collection {
        $outstandingByUnit = (clone $bills)->selectRaw('unit_name, SUM(remaining_amount) as outstanding')
            ->groupBy('unit_name')
            ->pluck('outstanding', 'unit_name');

        return EducationUnit::with(['schoolClasses.students' => fn ($query) => $query->where('is_active', true)
            ->when(is_array($studentIds), fn ($studentQuery) => $studentIds === []
                ? $studentQuery->whereRaw('1 = 0')
                : $studentQuery->whereIn('students.id', $studentIds))
            ->when($selectedAcademicYear, fn ($studentQuery, AcademicYear $year) => $studentQuery->where('academic_year_id', $year->id))])
            ->when(is_array($unitIds), fn (Builder $query) => $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('education_units.id', $unitIds))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (EducationUnit $unit): array => [
                'code' => $unit->code,
                'name' => $unit->name,
                'students' => $unit->schoolClasses->sum(fn ($class) => $class->students->count()),
                'outstanding' => (int) ($outstandingByUnit[$unit->name] ?? 0),
            ])
            ->sortByDesc('outstanding')
            ->take(5)
            ->values();
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
}
