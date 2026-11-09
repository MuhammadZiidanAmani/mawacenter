<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Services\DashboardDataService;
use App\Support\PerformanceCache;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardDataService $dashboardData) {}

    public function __invoke(DashboardFilterRequest $request): View
    {
        $filters = $request->validated();
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();
        $now = CarbonImmutable::now();
        $accessibleUnitIds = $request->user()?->accessibleUnitIds();
        $studentIds = $request->user()?->accessibleStudentIds();
        $educationUnits = EducationUnit::query()
            ->where('is_active', true)
            ->when(is_array($accessibleUnitIds), fn ($query) => $accessibleUnitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('id', $accessibleUnitIds))
            ->orderBy('name')
            ->get();

        $requestedAcademicYearId = isset($filters['academic_year_id']) ? (int) $filters['academic_year_id'] : null;
        $selectedAcademicYear = $academicYears->firstWhere('id', $requestedAcademicYearId)
            ?? $activeAcademicYear;
        $requestedUnitId = isset($filters['unit_id']) ? (int) $filters['unit_id'] : null;
        $selectedUnitId = $educationUnits->contains('id', $requestedUnitId)
            ? $requestedUnitId
            : null;
        $unitIds = $this->filteredUnitIds($accessibleUnitIds, $selectedUnitId);

        if (is_array($unitIds)) {
            sort($unitIds);
        }
        if (is_array($studentIds)) {
            sort($studentIds);
        }

        $payload = PerformanceCache::remember(
            'dashboard',
            [
                'academic_year_id' => $selectedAcademicYear?->id,
                'date' => $now->toDateString(),
                'unit_ids' => $unitIds ?? 'all',
                'student_ids' => $studentIds ?? 'all',
            ],
            config('performance.query_cache.dashboard_ttl', 120),
            fn () => $this->dashboardData->viewData($selectedAcademicYear, $now, $unitIds, $studentIds),
        );

        return view('welcome', [
            'activeAcademicYear' => $activeAcademicYear,
            'academicYears' => $academicYears,
            'educationUnits' => $educationUnits,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedUnitId' => $selectedUnitId,
            'stats' => $payload['stats'],
            'monthlyTrend' => collect($payload['monthlyTrend']),
            'recentActivities' => collect($payload['recentActivities'])->map(fn (array $activity) => [
                ...$activity,
                'date' => CarbonImmutable::parse($activity['date']),
            ]),
            'unitSummaries' => collect($payload['unitSummaries']),
        ]);
    }

    private function filteredUnitIds(?array $accessibleUnitIds, ?int $selectedUnitId): ?array
    {
        if ($selectedUnitId !== null) {
            return [$selectedUnitId];
        }

        return $accessibleUnitIds;
    }
}
