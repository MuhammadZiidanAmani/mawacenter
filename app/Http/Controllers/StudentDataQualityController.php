<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentDataQualityController extends Controller
{
    private const DEFAULT_BILLING_START_DATE = '2025-07-01';

    public function index(Request $request): View
    {
        $students = Student::query()
            ->with(['schoolClass.educationUnit', 'academicYear'])
            ->orderBy('name')
            ->orderBy('nis')
            ->get();

        $activeStudents = $students->where('is_active', true)->values();
        $inactiveStudents = $students->where('is_active', false)->values();
        $duplicateCandidates = $this->duplicateCandidates($activeStudents);

        $activeWithoutNisn = $activeStudents
            ->filter(fn (Student $student) => blank($student->nisn))
            ->values();
        $activeWithoutEntryDate = $activeStudents
            ->filter(fn (Student $student) => blank($student->entry_date))
            ->values();
        $activeWithoutSpecificBillingStart = $this->activeStudentsMissingSpecificBillingStart($activeStudents);
        $inactiveWithoutExitData = $inactiveStudents
            ->filter(fn (Student $student) => blank($student->exit_date) || blank($student->inactive_reason))
            ->values();

        $canUpdateStudents = auth()->user()?->hasPermission('students.update') ?? false;
        $canManageIdentityCleanup = auth()->user()?->hasPermission('students.identity_cleanup') ?? false;
        $indicatorOptions = $this->indicatorOptions();
        $selectedIndicator = array_key_exists((string) $request->query('indicator'), $indicatorOptions)
            ? (string) $request->query('indicator')
            : 'duplicate-identities';
        $perPage = in_array((string) $request->query('per_page', '10'), ['10', '25', '50', '100'], true)
            ? (string) $request->query('per_page', '10')
            : '10';
        $detailRows = match ($selectedIndicator) {
            'active-without-nisn' => $activeWithoutNisn,
            'active-without-entry-date' => $activeWithoutEntryDate,
            'active-without-billing-start' => $activeWithoutSpecificBillingStart,
            'inactive-without-exit-data' => $inactiveWithoutExitData,
            default => $duplicateCandidates,
        };
        $selectedDetail = [
            ...$indicatorOptions[$selectedIndicator],
            'key' => $selectedIndicator,
            'rows' => $this->paginateCollection($detailRows, $request, $perPage),
        ];

        return view('student-management.data-quality', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'indicatorOptions' => $indicatorOptions,
            'selectedIndicator' => $selectedIndicator,
            'selectedDetail' => $selectedDetail,
            'perPage' => $perPage,
            'summary' => [
                'active_students' => $activeStudents->count(),
                'inactive_students' => $inactiveStudents->count(),
                'total_issues' => $duplicateCandidates->count()
                    + $activeWithoutNisn->count()
                    + $activeWithoutEntryDate->count()
                    + $activeWithoutSpecificBillingStart->count()
                    + $inactiveWithoutExitData->count(),
            ],
            'qualityCards' => [
                [
                    'key' => 'duplicate-identities',
                    'title' => 'Kandidat duplikat identitas',
                    'count' => $duplicateCandidates->count(),
                    'tone' => 'warning',
                    'description' => 'Nama, NISN, tanggal lahir, atau orang tua mirip dan perlu ditinjau.',
                    'action_label' => 'Lihat Detail',
                    'action_url' => route('student-management.data-quality.index', ['indicator' => 'duplicate-identities']),
                ],
                [
                    'key' => 'active-without-nisn',
                    'title' => 'Siswa aktif tanpa NISN',
                    'count' => $activeWithoutNisn->count(),
                    'tone' => 'neutral',
                    'description' => 'Data identitas nasional belum lengkap untuk siswa aktif.',
                    'action_label' => 'Lihat Detail',
                    'action_url' => route('student-management.data-quality.index', ['indicator' => 'active-without-nisn']),
                ],
                [
                    'key' => 'active-without-entry-date',
                    'title' => 'Siswa aktif tanpa tanggal masuk',
                    'count' => $activeWithoutEntryDate->count(),
                    'tone' => 'warning',
                    'description' => 'Tanggal masuk penting untuk audit akademik dan awal tagihan.',
                    'action_label' => 'Lihat Detail',
                    'action_url' => route('student-management.data-quality.index', ['indicator' => 'active-without-entry-date']),
                ],
                [
                    'key' => 'active-without-billing-start',
                    'title' => 'Mulai tagihan perlu dicek',
                    'count' => $activeWithoutSpecificBillingStart->count(),
                    'tone' => 'info',
                    'description' => 'Siswa masuk setelah awal tahun tanpa mulai tagihan khusus.',
                    'action_label' => 'Lihat Detail',
                    'action_url' => route('student-management.data-quality.index', ['indicator' => 'active-without-billing-start']),
                ],
                [
                    'key' => 'inactive-without-exit-data',
                    'title' => 'Nonaktif belum lengkap',
                    'count' => $inactiveWithoutExitData->count(),
                    'tone' => 'danger',
                    'description' => 'Siswa nonaktif/alumni tanpa tanggal keluar atau alasan nonaktif.',
                    'action_label' => 'Lihat Detail',
                    'action_url' => route('student-management.data-quality.index', ['indicator' => 'inactive-without-exit-data']),
                ],
            ],
            'canUpdateStudents' => $canUpdateStudents,
            'canManageIdentityCleanup' => $canManageIdentityCleanup,
        ]);
    }

    private function indicatorOptions(): array
    {
        return [
            'duplicate-identities' => [
                'title' => 'Kandidat Duplikat Identitas',
                'description' => 'Kandidat dari pencocokan identitas aktif. Tinjau sebelum digabungkan.',
                'type' => 'duplicates',
            ],
            'active-without-nisn' => [
                'title' => 'Siswa Aktif Tanpa NISN',
                'description' => 'Lengkapi NISN jika sudah tersedia agar pencarian dan rekonsiliasi lintas unit lebih rapi.',
                'type' => 'students',
            ],
            'active-without-entry-date' => [
                'title' => 'Siswa Aktif Tanpa Tanggal Masuk',
                'description' => 'Isi tanggal masuk untuk menjaga riwayat akademik dan batas awal tagihan tetap dapat diaudit.',
                'type' => 'students',
            ],
            'active-without-billing-start' => [
                'title' => 'Mulai Tagihan Perlu Dicek',
                'description' => 'Cek siswa pindahan atau masuk tengah tahun. Jika perlu, isi mulai tagihan khusus lewat edit siswa.',
                'type' => 'students',
            ],
            'inactive-without-exit-data' => [
                'title' => 'Siswa Nonaktif/Alumni Belum Lengkap',
                'description' => 'Lengkapi tanggal keluar dan alasan nonaktif supaya arsip alumni bisa dipertanggungjawabkan.',
                'type' => 'students',
            ],
        ];
    }

    private function paginateCollection(Collection $rows, Request $request, string $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $size = max(1, (int) $perPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $size)->values(),
            $rows->count(),
            $size,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ],
        );
    }

    private function activeStudentsMissingSpecificBillingStart(Collection $students): Collection
    {
        $defaultStart = CarbonImmutable::parse(self::DEFAULT_BILLING_START_DATE)->startOfMonth();

        return $students
            ->filter(function (Student $student) use ($defaultStart) {
                if (blank($student->entry_date) || filled($student->billing_start_date)) {
                    return false;
                }

                return CarbonImmutable::parse($student->entry_date)->startOfMonth()->gt($defaultStart);
            })
            ->values();
    }

    private function duplicateCandidates(Collection $students): Collection
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

        return $candidateMap
            ->values()
            ->sortBy(fn (array $candidate) => ['Kuat' => 1, 'Sedang' => 2, 'Perlu cek' => 3][$candidate['confidence']] ?? 4)
            ->values();
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

    private function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
