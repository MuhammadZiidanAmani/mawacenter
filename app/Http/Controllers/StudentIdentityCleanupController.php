<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Support\PerformanceCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentIdentityCleanupController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $rows = $this->sortIdentityRows(
            $this->filterIdentityDefinitions($this->identityRowDefinitions($request), $filters),
            $filters
        );
        $candidates = $this->paginateCandidates($rows, $request, $filters['per_page']);
        $candidates->setCollection($this->hydrateIdentityDefinitions($candidates->getCollection()));

        return view('student-management.identity-cleanup', [
            ...$this->sharedViewData(),
            'candidates' => $candidates,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $candidateKey): View
    {
        $filters = $this->filtersFromRequest($request);
        $candidate = $this->filterIdentityDefinitions($this->identityRowDefinitions($request), $filters)
            ->where('row_type', 'candidate')
            ->firstWhere('key', $candidateKey);

        abort_if(! $candidate, 404);
        $candidate = $this->hydrateIdentityDefinitions(collect([$candidate]))->first();

        return view('student-management.identity-cleanup-show', [
            ...$this->sharedViewData(),
            'candidate' => $candidate,
            'filters' => $filters,
        ]);
    }

    public function merge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:2'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $selected = Student::whereIn('id', $validated['student_ids'])->get();
        $this->authorizeStudentsAccess($request, $selected);
        $before = [];
        $after = [];
        $affectedIds = [];

        DB::transaction(function () use ($selected, &$before, &$after, &$affectedIds) {
            $rootIds = $selected
                ->map(fn (Student $student) => $student->identity_student_id ?: $student->id)
                ->push(...$selected->pluck('id'))
                ->filter()
                ->unique()
                ->values();

            $groupStudents = Student::whereIn('id', $rootIds)
                ->orWhereIn('identity_student_id', $rootIds)
                ->get();

            $primary = $groupStudents
                ->whereNull('identity_student_id')
                ->sortBy('id')
                ->first() ?? $selected->sortBy('id')->first();
            $affectedIds = $groupStudents->pluck('id')->values()->all();
            $before = $groupStudents
                ->map(fn (Student $student) => $this->identityAuditSnapshot($student))
                ->values()
                ->all();

            foreach ($groupStudents as $student) {
                $student->forceFill([
                    'identity_student_id' => $student->is($primary) ? null : $primary->id,
                ])->save();
            }
            $after = Student::whereIn('id', $affectedIds)
                ->get()
                ->map(fn (Student $student) => $this->identityAuditSnapshot($student))
                ->values()
                ->all();
        });
        app(AuditLogService::class)->recordStudentOperation(
            'students.identity_merge',
            $affectedIds,
            ['selected_student_ids' => collect($validated['student_ids'])->map(fn ($id) => (int) $id)->values()->all()],
            ['students' => $before],
            ['students' => $after],
            $request,
        );

        return redirect()
            ->route('student-management.identity-cleanup.index')
            ->with('success', 'Identitas siswa berhasil digabung. Transaksi tiap unit tetap aman.');
    }

    public function split(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity_root_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $linkedStudents = Student::where('id', $validated['identity_root_id'])
            ->orWhere('identity_student_id', $validated['identity_root_id'])
            ->get();
        $this->authorizeStudentsAccess($request, $linkedStudents);
        $before = [];
        $after = [];
        $affectedIds = [];
        DB::transaction(function () use ($validated, &$before, &$after, &$affectedIds) {
            $students = Student::where('id', $validated['identity_root_id'])
                ->orWhere('identity_student_id', $validated['identity_root_id'])
                ->get();
            $affectedIds = $students->pluck('id')->values()->all();
            $before = $students
                ->map(fn (Student $student) => $this->identityAuditSnapshot($student))
                ->values()
                ->all();

            Student::whereIn('id', $affectedIds)->update(['identity_student_id' => null]);
            $after = Student::whereIn('id', $affectedIds)
                ->get()
                ->map(fn (Student $student) => $this->identityAuditSnapshot($student))
                ->values()
                ->all();
        });
        app(AuditLogService::class)->recordStudentOperation(
            'students.identity_split',
            $affectedIds,
            ['identity_root_id' => (int) $validated['identity_root_id']],
            ['students' => $before],
            ['students' => $after],
            $request,
            Student::class,
            (int) $validated['identity_root_id'],
        );

        return back()->with('success', 'Identitas siswa berhasil dipisahkan kembali.');
    }

    private function duplicateCandidates(Collection $students, array $filters): Collection
    {
        $candidateMap = collect();

        $this->pushDuplicateGroups(
            $candidateMap,
            $students->filter(fn (Student $student) => filled($student->nisn))->groupBy(fn (Student $student) => 'nisn:'.$this->normalize($student->nisn)),
            'NISN sama',
            'Kuat'
        );

        $this->pushDuplicateGroups(
            $candidateMap,
            $students
                ->filter(fn (Student $student) => filled($student->birth_date))
                ->groupBy(fn (Student $student) => 'birth:'.$this->normalize($student->name).':'.$student->birth_date?->format('Y-m-d')),
            'Nama dan tanggal lahir sama',
            'Kuat'
        );

        $this->pushDuplicateGroups(
            $candidateMap,
            $students
                ->filter(fn (Student $student) => filled($student->father_name) || filled($student->mother_name))
                ->groupBy(fn (Student $student) => 'parents:'.$this->normalize($student->name).':'.$this->normalize($student->father_name).':'.$this->normalize($student->mother_name)),
            'Nama dan orang tua sama',
            'Sedang'
        );

        $this->pushDuplicateGroups(
            $candidateMap,
            $students->groupBy(fn (Student $student) => 'name:'.$this->normalize($student->name)),
            'Nama sama, perlu dicek admin',
            'Perlu cek'
        );

        $candidates = $candidateMap
            ->values()
            ->sortBy(fn (array $candidate) => ['Kuat' => 1, 'Sedang' => 2, 'Perlu cek' => 3][$candidate['confidence']] ?? 4);

        if (($filters['search'] ?? '') !== '') {
            $needle = $this->normalize($filters['search']);

            $candidates = $candidates
                ->filter(fn (array $candidate) => $this->candidateMatchesSearch($candidate, $needle))
                ->values();
        }

        return $candidates
            ->filter(fn (array $candidate) => $this->candidateMatchesFilters($candidate, $filters))
            ->values();
    }

    private function identityRowDefinitions(Request $request): Collection
    {
        $unitIds = $request->user()?->accessibleUnitIds();
        $rows = PerformanceCache::remember(
            'identity-cleanup-row-definitions-v3',
            ['unit_ids' => $unitIds],
            300,
            function () use ($request) {
                $students = $this->studentsForCleanup($request);
                $unfiltered = [
                    'unit_id' => null,
                    'class_id' => null,
                    'year_id' => null,
                    'search' => '',
                ];

                return $this->identityRows(
                    $this->duplicateCandidates($students, $unfiltered),
                    $this->linkedIdentityGroups($students, $unfiltered),
                )->map(function (array $row) {
                    $studentRows = $row['students']->map(fn (Student $student) => [
                        'id' => (int) $student->id,
                        'nis' => $student->nis,
                        'nisn' => $student->nisn,
                        'name' => $student->name,
                        'unit_id' => $student->schoolClass?->education_unit_id,
                        'unit' => $student->schoolClass?->educationUnit?->code,
                        'class_id' => $student->school_class_id,
                        'class' => $student->schoolClass?->name,
                        'year_id' => $student->academic_year_id,
                        'year' => $student->academicYear?->name,
                    ]);
                    $row['student_ids'] = $studentRows->pluck('id')->all();
                    $row['unit_ids'] = $studentRows->pluck('unit_id')->filter()->unique()->values()->all();
                    $row['class_ids'] = $studentRows->pluck('class_id')->filter()->unique()->values()->all();
                    $row['year_ids'] = $studentRows->pluck('year_id')->filter()->unique()->values()->all();
                    $row['search_text'] = $this->normalize(collect([
                        $row['name'],
                        $row['reason'],
                        $row['confidence'],
                        ...$studentRows->flatMap(fn (array $student) => [
                            $student['nis'],
                            $student['nisn'],
                            $student['name'],
                            $student['unit'],
                            $student['class'],
                            $student['year'],
                        ])->filter()->all(),
                    ])->filter()->implode(' '));
                    unset($row['students']);

                    return $row;
                })->all();
            },
        );

        return collect($rows);
    }

    private function filterIdentityDefinitions(Collection $rows, array $filters): Collection
    {
        $needle = ($filters['search'] ?? '') !== '' ? $this->normalize($filters['search']) : null;

        return $rows
            ->filter(function (array $row) use ($needle) {
                if (! $needle) {
                    return true;
                }

                return Str::of($row['search_text'])->contains($needle);
            })
            ->filter(function (array $row) use ($filters) {
                return (! filled($filters['unit_id'] ?? null) || in_array((int) $filters['unit_id'], $row['unit_ids'], true))
                    && (! filled($filters['class_id'] ?? null) || in_array((int) $filters['class_id'], $row['class_ids'], true))
                    && (! filled($filters['year_id'] ?? null) || in_array((int) $filters['year_id'], $row['year_ids'], true));
            })
            ->values();
    }

    private function hydrateIdentityDefinitions(Collection $rows): Collection
    {
        $studentIds = $rows
            ->flatMap(fn (array $row) => $row['student_ids'])
            ->unique()
            ->values();

        $students = Student::query()
            ->select([
                'id', 'identity_student_id', 'nis', 'nisn', 'name', 'birth_date',
                'father_name', 'mother_name', 'school_class_id', 'academic_year_id', 'is_active',
            ])
            ->with([
                'schoolClass:id,education_unit_id,name',
                'schoolClass.educationUnit:id,code,name',
                'academicYear:id,name',
            ])
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        return $rows->map(function (array $row) use ($students) {
            $row['students'] = collect($row['student_ids'])
                ->map(fn (int $studentId) => $students->get($studentId))
                ->filter()
                ->values();

            return $row;
        })->values();
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'unit_id' => $request->query('unit_id'),
            'class_id' => $request->query('class_id'),
            'year_id' => $request->query('year_id'),
            'search' => trim((string) $request->query('search', '')),
            'per_page' => (string) $request->query('per_page', '10'),
            'sort' => in_array($request->query('sort'), ['name', 'reason', 'confidence', 'count'], true)
                ? $request->query('sort')
                : null,
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function studentsForCleanup(Request $request): Collection
    {
        $unitIds = $request->user()?->accessibleUnitIds();

        $candidateIds = PerformanceCache::remember(
            'identity-cleanup-candidate-ids',
            ['unit_ids' => $unitIds],
            300,
            fn () => $this->candidateStudentIds($unitIds),
        );

        if ($candidateIds === []) {
            return collect();
        }

        return Student::query()
            ->select([
                'id', 'identity_student_id', 'nis', 'nisn', 'name', 'birth_date',
                'father_name', 'mother_name', 'school_class_id', 'academic_year_id', 'is_active',
            ])
            ->with([
                'schoolClass:id,education_unit_id,name',
                'schoolClass.educationUnit:id,code,name',
                'academicYear:id,name',
            ])
            ->where('is_active', true)
            ->whereIn('id', $candidateIds)
            ->orderBy('name')
            ->orderBy('nis')
            ->get();
    }

    private function candidateStudentIds(?array $unitIds): array
    {
        $students = Student::query()
            ->select(['id', 'identity_student_id', 'nisn', 'name'])
            ->where('is_active', true)
            ->when(is_array($unitIds), function ($query) use ($unitIds) {
                $unitIds === []
                    ? $query->whereRaw('1 = 0')
                    : $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds));
            })
            ->get();

        $duplicateNisnIds = $students
            ->filter(fn (Student $student) => filled($student->nisn))
            ->groupBy(fn (Student $student) => $this->normalize($student->nisn))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->flatten(1)
            ->pluck('id');

        $duplicateNameIds = $students
            ->groupBy(fn (Student $student) => $this->normalize($student->name))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->flatten(1)
            ->pluck('id');

        $linkedIdentityIds = $students
            ->groupBy(fn (Student $student) => $student->identity_student_id ?: $student->id)
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->flatten(1)
            ->pluck('id');

        return $duplicateNisnIds
            ->concat($duplicateNameIds)
            ->concat($linkedIdentityIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function sharedViewData(): array
    {
        $unitIds = request()->user()?->accessibleUnitIds();

        return [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->orderByDesc('id')->get(),
            'educationUnits' => EducationUnit::where('is_active', true)
                ->when(is_array($unitIds), fn ($query) => $unitIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $unitIds))
                ->orderBy('name')
                ->get(),
            'schoolClasses' => SchoolClass::with('educationUnit')
                ->where('is_active', true)
                ->when(is_array($unitIds), fn ($query) => $unitIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('education_unit_id', $unitIds))
                ->orderBy('name')
                ->get(),
        ];
    }

    private function pushDuplicateGroups(Collection $candidateMap, Collection $groups, string $reason, string $confidence): void
    {
        foreach ($groups as $group) {
            $uniquePeople = $group
                ->map(fn (Student $student) => $student->identity_student_id ?: $student->id)
                ->unique();

            if ($group->count() < 2 || $uniquePeople->count() < 2) {
                continue;
            }

            $key = $group->pluck('id')->sort()->implode('-');
            if ($candidateMap->has($key)) {
                continue;
            }

            $candidateMap->put($key, [
                'key' => $key,
                'reason' => $reason,
                'confidence' => $confidence,
                'name' => $group->first()->name,
                'students' => $group->sortBy([
                    ['name', 'asc'],
                    ['nis', 'asc'],
                ])->values(),
            ]);
        }
    }

    private function linkedIdentityGroups(Collection $students, array $filters): Collection
    {
        $groups = $students
            ->groupBy(fn (Student $student) => $student->identity_student_id ?: $student->id)
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(function (Collection $group) {
                $root = $group->firstWhere('identity_student_id', null) ?? $group->first();

                return [
                    'key' => 'linked-'.$root->id,
                    'identity_root_id' => $root->id,
                    'reason' => 'Sudah digabung',
                    'confidence' => 'Gabungan',
                    'name' => $root->name,
                    'students' => $group->sortBy([
                        ['name', 'asc'],
                        ['nis', 'asc'],
                    ])->values(),
                ];
            })
            ->sortBy(fn (array $group) => $group['name'])
            ->values();

        if (($filters['search'] ?? '') !== '') {
            $needle = $this->normalize($filters['search']);

            $groups = $groups
                ->filter(fn (array $group) => $this->candidateMatchesSearch($group, $needle))
                ->values();
        }

        return $groups
            ->filter(fn (array $group) => $this->candidateMatchesFilters($group, $filters))
            ->values();
    }

    private function identityRows(Collection $candidates, Collection $linkedGroups): Collection
    {
        return $candidates
            ->map(fn (array $candidate) => [...$candidate, 'row_type' => 'candidate'])
            ->concat($linkedGroups->map(fn (array $group) => [...$group, 'row_type' => 'linked']))
            ->values();
    }

    private function sortIdentityRows(Collection $rows, array $filters): Collection
    {
        if (! $filters['sort']) {
            return $rows;
        }

        return $rows
            ->sortBy(
                fn (array $row) => $this->identityRowSortValue($row, $filters['sort']),
                SORT_REGULAR,
                $filters['direction'] === 'desc'
            )
            ->values();
    }

    private function identityRowSortValue(array $row, string $sort): mixed
    {
        return match ($sort) {
            'name' => Str::lower($row['name'] ?? ''),
            'reason' => Str::lower($row['reason'] ?? ''),
            'confidence' => $this->confidenceRank($row['confidence'] ?? ''),
            'count' => count($row['student_ids'] ?? $row['students'] ?? []),
            default => Str::lower($row['name'] ?? ''),
        };
    }

    private function confidenceRank(string $confidence): int
    {
        return match ($confidence) {
            'Kuat', 'Gabungan' => 1,
            'Sedang' => 2,
            'Perlu cek' => 3,
            default => 4,
        };
    }

    private function candidateMatchesSearch(array $candidate, string $needle): bool
    {
        $haystack = collect([
            $candidate['name'],
            $candidate['reason'],
            $candidate['confidence'],
        ]);

        foreach ($candidate['students'] as $student) {
            $haystack->push(
                $student->nis,
                $student->nisn,
                $student->name,
                $student->schoolClass?->educationUnit?->code,
                $student->schoolClass?->name,
                $student->academicYear?->name,
            );
        }

        return Str::of($this->normalize($haystack->filter()->implode(' ')))->contains($needle);
    }

    private function candidateMatchesFilters(array $candidate, array $filters): bool
    {
        return $candidate['students']->contains(function (Student $student) use ($filters) {
            if (filled($filters['unit_id'] ?? null) && (string) $student->schoolClass?->education_unit_id !== (string) $filters['unit_id']) {
                return false;
            }

            if (filled($filters['class_id'] ?? null) && (string) $student->school_class_id !== (string) $filters['class_id']) {
                return false;
            }

            if (filled($filters['year_id'] ?? null) && (string) $student->academic_year_id !== (string) $filters['year_id']) {
                return false;
            }

            return true;
        });
    }

    private function paginateCandidates(Collection $candidates, Request $request, string $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $size = $perPage === 'all' ? max(1, $candidates->count()) : max(1, (int) $perPage);

        return new LengthAwarePaginator(
            $candidates->forPage($page, $size)->values(),
            $candidates->count(),
            $size,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('status'),
            ],
        );
    }

    private function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }

    private function identityAuditSnapshot(Student $student): array
    {
        $student->loadMissing(['schoolClass.educationUnit', 'academicYear']);

        return [
            'id' => $student->id,
            'identity_student_id' => $student->identity_student_id,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'name' => $student->name,
            'unit' => $student->schoolClass?->educationUnit?->code,
            'class' => $student->schoolClass?->name,
            'academic_year' => $student->academicYear?->name,
        ];
    }

    private function authorizeStudentsAccess(Request $request, Collection $students): void
    {
        $unitIds = $request->user()?->accessibleUnitIds();
        if (! is_array($unitIds)) {
            return;
        }

        $students->loadMissing('schoolClass');
        abort_if($students->contains(fn (Student $student) => ! in_array((int) $student->schoolClass?->education_unit_id, $unitIds, true)), 403);
    }
}
