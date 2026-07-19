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
        $studentModels = Student::with([
            'schoolClass:id,name,education_unit_id',
            'schoolClass.educationUnit:id,code,name',
        ])
            ->whereIn('id', $studentIds)
            ->get(['id', 'nis', 'name', 'school_class_id'])
            ->keyBy('id');

        $students->setCollection($students->getCollection()->map(function ($row) use ($studentModels) {
            return [
                'student' => $studentModels->get($row->student_id),
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
                    ->selectRaw('SUM(bills.total_amount) as billed')
                    ->selectRaw('SUM(bills.paid_amount) as paid')
                    ->selectRaw('SUM(bills.remaining_amount) as remaining')
                    ->selectRaw('COUNT(DISTINCT CASE WHEN bills.remaining_amount > 0 AND bills.due_date IS NOT NULL AND bills.due_date < ? THEN bills.student_id END) as overdue_students', [now()->toDateString()])
                    ->selectRaw('SUM(CASE WHEN bills.remaining_amount > 0 AND bills.due_date IS NOT NULL AND bills.due_date < ? THEN bills.remaining_amount ELSE 0 END) as overdue', [now()->toDateString()])
                    ->first();

                return [
                    'students' => (int) ($row->students ?? 0),
                    'spp' => (int) ($row->spp ?? 0),
                    'other' => (int) ($row->other ?? 0),
                    'billed' => (int) ($row->billed ?? 0),
                    'paid' => (int) ($row->paid ?? 0),
                    'remaining' => (int) ($row->remaining ?? 0),
                    'overdue_students' => (int) ($row->overdue_students ?? 0),
                    'overdue' => (int) ($row->overdue ?? 0),
                ];
            },
        );
    }

    public function unitBreakdown(int $year, int $untilMonth, array $filters): Collection
    {
        $rows = PerformanceCache::remember(
            'bill-unit-breakdown-v3',
            ['year' => $year, 'until_month' => $untilMonth, 'filters' => $filters],
            config('performance.query_cache.bill_stats_ttl', 120),
            function () use ($year, $untilMonth, $filters) {
                return $this->baseQuery($year, $untilMonth, $filters)
                    ->select([
                        'education_units.id as unit_id',
                        'education_units.code as unit_code',
                        'education_units.name as unit_name',
                    ])
                    ->selectRaw('COUNT(DISTINCT bills.student_id) as students')
                    ->selectRaw("SUM(CASE WHEN bills.source_type = 'spp' THEN bills.remaining_amount ELSE 0 END) as spp")
                    ->selectRaw("SUM(CASE WHEN bills.source_type <> 'spp' THEN bills.remaining_amount ELSE 0 END) as other")
                    ->selectRaw('SUM(bills.total_amount) as billed')
                    ->selectRaw('SUM(bills.paid_amount) as paid')
                    ->selectRaw('SUM(bills.remaining_amount) as remaining')
                    ->selectRaw('COUNT(DISTINCT CASE WHEN bills.remaining_amount > 0 AND bills.due_date IS NOT NULL AND bills.due_date < ? THEN bills.student_id END) as overdue_students', [now()->toDateString()])
                    ->groupBy('education_units.id', 'education_units.code', 'education_units.name')
                    ->orderByRaw("CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                    ->orderBy('education_units.name')
                    ->get()
                    ->map(fn ($row) => [
                        'unit_id' => $row->unit_id,
                        'unit_code' => $row->unit_code ?? '-',
                        'unit_name' => $row->unit_name ?? 'Tanpa Unit',
                        'students' => (int) ($row->students ?? 0),
                        'spp' => (int) ($row->spp ?? 0),
                        'other' => (int) ($row->other ?? 0),
                        'billed' => (int) ($row->billed ?? 0),
                        'paid' => (int) ($row->paid ?? 0),
                        'remaining' => (int) ($row->remaining ?? 0),
                        'overdue_students' => (int) ($row->overdue_students ?? 0),
                    ])
                    ->values()
                    ->all();
            },
        );

        return collect(is_array($rows) ? $rows : []);
    }

    public function statement(Student $student, int $year, int $untilMonth): array
    {
        $query = Bill::with(['academicYear:id,name', 'feeType:id,name,payment_group'])
            ->where('student_id', $student->id)
            ->orderByRaw("CASE WHEN source_type = 'spp' THEN 0 ELSE 1 END")
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('title');

        $this->applyBillScope($query, $year, $untilMonth, 'bills', 'outstanding');

        $bills = $query->get();
        $lines = $this->statementLines($bills);

        return [
            'lines' => $lines,
            'total' => (int) $lines->sum('total'),
            'bill_count' => $bills->count(),
        ];
    }

    private function baseQuery(int $year, int $untilMonth, array $filters)
    {
        $query = Bill::query()
            ->join('students', 'students.id', '=', 'bills.student_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->leftJoin('education_units', 'education_units.id', '=', 'school_classes.education_unit_id');

        $this->applyBillScope($query, $year, $untilMonth, 'bills', $filters['status'] ?? 'outstanding');

        $query
            ->when($filters['unit_id'] ?? null, fn ($query, $id) => $query->where('school_classes.education_unit_id', $id))
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('students.school_class_id', $id))
            ->when($filters['student_id'] ?? null, fn ($query, $id) => $query->where('students.id', $id))
            ->when($filters['fee_type_id'] ?? null, fn ($query, $id) => $query->where('bills.fee_type_id', $id))
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

        if (array_key_exists('unit_ids', $filters)) {
            $ids = array_values(array_filter((array) $filters['unit_ids'], fn ($id) => $id !== null && $id !== ''));
            $ids === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('school_classes.education_unit_id', $ids);
        }

        if (array_key_exists('student_ids', $filters)) {
            $ids = array_values(array_filter((array) $filters['student_ids'], fn ($id) => $id !== null && $id !== ''));
            $ids === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('students.id', $ids);
        }

        return $query;
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
        $this->applyBillScope($query, $year, $untilMonth, 'bills', 'all');

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
                        'status' => $bill->displayStatus(),
                    ]),
                    'other' => $bills->where('source_type', '!=', 'spp')->map(fn (Bill $bill) => [
                        'name' => $bill->title,
                        'total' => (int) $bill->total_amount,
                        'paid' => (int) $bill->paid_amount,
                        'remaining' => (int) $bill->remaining_amount,
                        'status' => $bill->displayStatus(),
                    ]),
                ];
            });
    }

    private function statementLines(Collection $bills): Collection
    {
        $lines = collect();
        $current = null;

        foreach ($bills as $bill) {
            if ($bill->month === null) {
                $lines->push($this->statementLineFromBills(collect([$bill])));

                continue;
            }

            $key = implode('|', [
                $this->statementBaseTitle($bill),
                $this->statementYearLabel($bill),
                (int) $bill->remaining_amount,
            ]);
            $isSequential = $current !== null
                && $current['key'] === $key
                && $current['last_month'] !== null
                && (int) $bill->month === $current['last_month'] + 1;

            if (! $isSequential) {
                if ($current !== null) {
                    $lines->push($this->statementLineFromBills($current['bills']));
                }

                $current = [
                    'key' => $key,
                    'last_month' => (int) $bill->month,
                    'bills' => collect([$bill]),
                ];

                continue;
            }

            $current['last_month'] = (int) $bill->month;
            $current['bills']->push($bill);
        }

        if ($current !== null) {
            $lines->push($this->statementLineFromBills($current['bills']));
        }

        return $lines->values();
    }

    private function statementLineFromBills(Collection $bills): array
    {
        /** @var Bill $first */
        $first = $bills->first();
        $months = $bills->pluck('month')->filter()->map(fn ($month) => (int) $month)->values();
        $monthCount = max(1, $months->count());
        $title = $this->statementBaseTitle($first);

        if ($months->isNotEmpty()) {
            $title = trim($title.' '.$this->statementMonthRange($months));
        }

        return [
            'title' => $title,
            'year' => $this->statementYearLabel($first),
            'month_count' => $monthCount,
            'unit_amount' => (int) $first->remaining_amount,
            'total' => (int) $bills->sum('remaining_amount'),
        ];
    }

    private function statementBaseTitle(Bill $bill): string
    {
        if ($bill->source_type === 'spp') {
            return 'SPP';
        }

        $title = trim($bill->feeType?->name ?: $bill->title);
        if ($bill->month !== null && $bill->year !== null) {
            $month = self::MONTHS[(int) $bill->month] ?? null;
            if ($month) {
                $title = preg_replace('/\s+'.preg_quote($month, '/').'\s+'.preg_quote((string) $bill->year, '/').'$/i', '', $title) ?: $title;
            }
        }

        return trim($title);
    }

    private function statementYearLabel(Bill $bill): string
    {
        return $bill->year ? (string) $bill->year : ($bill->academicYear?->name ?? '-');
    }

    private function statementMonthRange(Collection $months): string
    {
        $names = $months->map(fn (int $month) => self::MONTHS[$month] ?? (string) $month)->values();

        if ($names->count() === 1) {
            return $names->first();
        }

        return $names->first().' - '.$names->last();
    }

    private function applyBillScope($query, int $year, int $untilMonth, string $table, string $status = 'outstanding'): void
    {
        $query
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

        $this->applyStatusFilter($query, $table, $status);
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

    private function applyStatusFilter($query, string $table, string $status): void
    {
        match ($status) {
            'all' => null,
            'paid' => $query->where($table.'.remaining_amount', '<=', 0),
            'partial' => $query->where($table.'.remaining_amount', '>', 0)->where($table.'.paid_amount', '>', 0),
            'overdue' => $query->where($table.'.remaining_amount', '>', 0)
                ->whereNotNull($table.'.due_date')
                ->where($table.'.due_date', '<', now()->toDateString()),
            default => $query->where($table.'.remaining_amount', '>', 0),
        };
    }

    private function summaryStatus(int $remaining, int $paid, int $overdue): string
    {
        if ($remaining <= 0) {
            return 'Lunas';
        }

        if ($overdue > 0) {
            return 'Jatuh Tempo';
        }

        return $paid > 0 ? 'Sebagian' : 'Belum Bayar';
    }
}
