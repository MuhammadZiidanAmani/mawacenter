<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Student;
use App\Support\PerformanceCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillQueryService
{
    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function summaries(
        int $year,
        int $untilMonth,
        array $filters,
        int $perPage,
        string $sort,
        string $direction,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($year, $untilMonth, $filters)
            ->select([
                'students.id as student_id',
                'students.nis',
                'students.name',
                'school_classes.name as class_name',
                'education_units.code as unit_code',
                'education_units.name as unit_name',
            ])
            ->selectRaw("SUM(CASE WHEN bills.source_type = 'spp' THEN bills.remaining_amount ELSE 0 END) as spp_remaining")
            ->selectRaw("SUM(CASE WHEN bills.source_type <> 'spp' THEN bills.remaining_amount ELSE 0 END) as other_remaining")
            ->selectRaw('SUM(bills.remaining_amount) as total_remaining')
            ->groupBy(
                'students.id',
                'students.nis',
                'students.name',
                'school_classes.name',
                'education_units.code',
                'education_units.name',
            );

        $this->applySorting($query, $sort, $direction);

        $students = $query->paginate($perPage)->withQueryString();
        $studentIds = $students->getCollection()->pluck('student_id')->all();
        $studentModels = Student::with('schoolClass.educationUnit')
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');
        $details = $this->details($year, $untilMonth, $studentIds);

        $students->setCollection($students->getCollection()->map(function ($row) use ($studentModels, $details) {
            $studentDetails = $details->get($row->student_id, ['spp' => collect(), 'other' => collect()]);

            return [
                'student' => $studentModels->get($row->student_id),
                'spp' => $studentDetails['spp']->values()->all(),
                'other' => $studentDetails['other']->values()->all(),
                'spp_remaining' => (int) $row->spp_remaining,
                'other_remaining' => (int) $row->other_remaining,
                'total_remaining' => (int) $row->total_remaining,
            ];
        }));

        return $students;
    }

    public function stats(int $year, int $untilMonth, array $filters): array
    {
        return PerformanceCache::remember(
            'bill-stats',
            ['year' => $year, 'until_month' => $untilMonth, 'filters' => $filters],
            config('performance.query_cache.bill_stats_ttl', 120),
            function () use ($year, $untilMonth, $filters) {
                $row = $this->baseQuery($year, $untilMonth, $filters)
                    ->selectRaw('COUNT(DISTINCT bills.student_id) as students')
                    ->selectRaw("SUM(CASE WHEN bills.source_type = 'spp' THEN bills.remaining_amount ELSE 0 END) as spp")
                    ->selectRaw("SUM(CASE WHEN bills.source_type <> 'spp' THEN bills.remaining_amount ELSE 0 END) as other")
                    ->selectRaw('SUM(bills.remaining_amount) as remaining')
                    ->first();

                return [
                    'students' => (int) ($row->students ?? 0),
                    'spp' => (int) ($row->spp ?? 0),
                    'other' => (int) ($row->other ?? 0),
                    'remaining' => (int) ($row->remaining ?? 0),
                ];
            },
        );
    }

    private function baseQuery(int $year, int $untilMonth, array $filters)
    {
        $query = Bill::query()
            ->join('students', 'students.id', '=', 'bills.student_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->leftJoin('education_units', 'education_units.id', '=', 'school_classes.education_unit_id');

        $this->applyOutstandingScope($query, $year, $untilMonth, 'bills');

        return $query
            ->when($filters['unit_id'] ?? null, fn ($query, $id) => $query->where('school_classes.education_unit_id', $id))
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('students.school_class_id', $id))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('students.id', $id))
            ->when(($filters['student_search'] ?? null) && ! ($filters['student_id'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['student_search']);
                $query->where(function ($query) use ($search) {
                    $query->where('students.name', 'like', "%{$search}%")
                        ->orWhere('students.nis', 'like', "%{$search}%")
                        ->orWhere('education_units.code', 'like', "%{$search}%");
                });
            })
            ->when($filters['student_name'] ?? null, function ($query, string $name) {
                $query->where('students.name', 'like', '%'.trim($name).'%');
            })
            ->when($filters['nis'] ?? null, function ($query, string $nis) {
                $query->where('students.nis', 'like', '%'.trim($nis).'%');
            })
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $search = trim($search);
                $query->where(function ($query) use ($search) {
                    $query->where('students.name', 'like', "%{$search}%")
                        ->orWhere('students.nis', 'like', "%{$search}%");
                });
            });
    }

    private function details(int $year, int $untilMonth, array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        $query = Bill::with('feeType:id,name,payment_group')
            ->whereIn('student_id', $studentIds)
            ->orderByRaw("CASE WHEN source_type = 'spp' THEN 0 ELSE 1 END")
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('title');
        $this->applyOutstandingScope($query, $year, $untilMonth, 'bills');

        return $query->get()
            ->groupBy('student_id')
            ->map(function (Collection $bills) {
                return [
                    'spp' => $bills->where('source_type', 'spp')->map(fn (Bill $bill) => [
                        'year' => $bill->year,
                        'month' => $bill->month,
                        'month_name' => self::MONTHS[$bill->month] ?? '-',
                        'total' => (int) $bill->total_amount,
                        'paid' => (int) $bill->paid_amount,
                        'remaining' => (int) $bill->remaining_amount,
                    ]),
                    'other' => $bills->where('source_type', '!=', 'spp')->map(fn (Bill $bill) => [
                        'name' => $bill->title,
                        'total' => (int) $bill->total_amount,
                        'paid' => (int) $bill->paid_amount,
                        'remaining' => (int) $bill->remaining_amount,
                    ]),
                ];
            });
    }

    private function applyOutstandingScope($query, int $year, int $untilMonth, string $table): void
    {
        $query
            ->where($table.'.remaining_amount', '>', 0)
            ->where($table.'.status', '!=', 'Dibatalkan')
            ->where(function ($query) use ($table, $year, $untilMonth) {
                $query->where(function ($query) use ($table, $year, $untilMonth) {
                    $query->where($table.'.source_type', 'spp')
                        ->where(function ($query) use ($table, $year, $untilMonth) {
                            $query->where($table.'.year', '<', $year)
                                ->orWhere(function ($query) use ($table, $year, $untilMonth) {
                                    $query->where($table.'.year', $year)
                                        ->where($table.'.month', '<=', $untilMonth);
                                });
                        });
                })->orWhere(function ($query) use ($table, $year, $untilMonth) {
                    $query->where($table.'.source_type', '!=', 'spp')
                        ->where(function ($query) use ($table, $year) {
                            $query->whereNull($table.'.year')->orWhere($table.'.year', $year);
                        })
                        ->where(function ($query) use ($table, $untilMonth) {
                            $query->whereNull($table.'.month')->orWhere($table.'.month', '<=', $untilMonth);
                        });
                });
            })
            ->where(function ($query) use ($table) {
                $query->where($table.'.source_type', '!=', 'fee_type')
                    ->orWhereNull($table.'.fee_type_id')
                    ->orWhereExists(function ($query) use ($table) {
                        $query->select(DB::raw(1))
                            ->from('fee_types')
                            ->whereColumn('fee_types.id', $table.'.fee_type_id')
                            ->where(function ($query) {
                                $query->whereNull('fee_types.payment_group')
                                    ->orWhere('fee_types.payment_group', '!=', 'laundry');
                            });
                    });
            });
    }

    private function applySorting($query, string $sort, string $direction): void
    {
        match ($sort) {
            'nis' => $query->orderBy('students.nis', $direction),
            'unit' => $query->orderBy('education_units.code', $direction)->orderBy('students.name'),
            'class' => $query->orderBy('school_classes.name', $direction)->orderBy('students.name'),
            'total' => $query->orderBy('total_remaining', $direction)->orderBy('students.name'),
            default => $query->orderBy('students.name', $direction),
        };
    }
}
