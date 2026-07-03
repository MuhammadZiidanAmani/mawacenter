<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Models\Student;
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

        $students = $this->studentsForCleanup();
        $candidates = $this->duplicateCandidates($students, $filters);
        $linkedGroups = $this->linkedIdentityGroups($students, $filters);

        return view('student-management.identity-cleanup', [
            ...$this->sharedViewData(),
            'candidates' => $this->paginateCandidates(
                $this->identityRows($candidates, $linkedGroups),
                $request,
                $filters['per_page']
            ),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $candidateKey): View
    {
        $filters = $this->filtersFromRequest($request);
        $students = $this->studentsForCleanup();
        $candidate = $this->duplicateCandidates($students, $filters)
            ->firstWhere('key', $candidateKey);

        abort_if(! $candidate, 404);

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

        DB::transaction(function () use ($selected) {
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

            foreach ($groupStudents as $student) {
                $student->forceFill([
                    'identity_student_id' => $student->is($primary) ? null : $primary->id,
                ])->save();
            }
        });

        return redirect()
            ->route('student-management.identity-cleanup.index')
            ->with('success', 'Identitas siswa berhasil digabung. Transaksi tiap unit tetap aman.');
    }

    public function split(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity_root_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        DB::transaction(function () use ($validated) {
            Student::where('id', $validated['identity_root_id'])
                ->orWhere('identity_student_id', $validated['identity_root_id'])
                ->update(['identity_student_id' => null]);
        });

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

    private function filtersFromRequest(Request $request): array
    {
        return [
            'unit_id' => $request->query('unit_id'),
            'class_id' => $request->query('class_id'),
            'year_id' => $request->query('year_id'),
            'search' => trim((string) $request->query('search', '')),
            'per_page' => (string) $request->query('per_page', '10'),
        ];
    }

    private function studentsForCleanup(): Collection
    {
        return Student::query()
            ->with(['schoolClass.educationUnit', 'academicYear'])
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('nis')
            ->get();
    }

    private function sharedViewData(): array
    {
        return [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->orderByDesc('id')->get(),
            'educationUnits' => EducationUnit::where('is_active', true)->orderBy('name')->get(),
            'schoolClasses' => SchoolClass::with('educationUnit')->where('is_active', true)->orderBy('name')->get(),
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
}
