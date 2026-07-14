<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportQueryService
{
    public const MONTHS = [
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

    public function options(): array
    {
        return [
            'academicYears' => AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'is_active', 'start_date', 'end_date']),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'educationUnits' => EducationUnit::orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(['id', 'code', 'name']),
            'classes' => SchoolClass::with('educationUnit:id,code,name')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->select('school_classes.*')
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('school_classes.name')
                ->get(),
            'operators' => SppPayment::query()
                ->whereNotNull('operator_name')
                ->where('operator_name', '!=', '')
                ->pluck('operator_name')
                ->concat(OtherPayment::query()->whereNotNull('operator_name')->where('operator_name', '!=', '')->pluck('operator_name'))
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    public function filters(Request $request, string $report): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $academicYearId = $request->integer('academic_year_id') ?: $activeYear?->id;
        $month = $request->integer('month') ?: now()->month;
        $untilMonth = $request->integer('until_month') ?: now()->month;

        return [
            'date_from' => $this->filterDate($request, 'date_from') ?? $this->filterDate($request, 'start_date') ?? CarbonImmutable::now()->startOfMonth()->startOfDay(),
            'date_to' => $this->filterDate($request, 'date_to', true) ?? $this->filterDate($request, 'end_date', true) ?? CarbonImmutable::now()->endOfDay(),
            'academic_year_id' => $academicYearId,
            'month' => $month >= 1 && $month <= 12 ? $month : now()->month,
            'until_month' => $untilMonth >= 1 && $untilMonth <= 12 ? $untilMonth : now()->month,
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'type' => in_array($request->string('type')->value(), ['spp', 'daftar-ulang', 'laundry', 'lain-lain'], true) ? $request->string('type')->value() : null,
            'payment_method' => in_array($request->string('payment_method')->value(), ['Cash', 'Transfer'], true) ? $request->string('payment_method')->value() : null,
            'payment_status' => in_array($request->string('payment_status')->value(), ['Diterima', 'Pending'], true) ? $request->string('payment_status')->value() : null,
            'spp_status' => in_array($request->string('spp_status')->value(), ['paid', 'partial', 'unpaid'], true) ? $request->string('spp_status')->value() : null,
            'operator_name' => $request->string('operator_name')->value() ?: null,
            'student_search' => $request->string('student_search')->value() ?: null,
            'search' => $request->string('search')->value() ?: null,
            'report' => $report,
        ];
    }

    public function transactions(array $filters): array
    {
        $rows = $this->transactionRows($filters);
        $accepted = $rows->where('status', 'Diterima');

        return [
            'rows' => $rows,
            'summaryCards' => [
                ['label' => 'Total Penerimaan', 'value' => (int) $accepted->sum('amount'), 'type' => 'money'],
                ['label' => 'Jumlah Transaksi', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Jumlah Siswa', 'value' => $accepted->pluck('student_id')->filter()->unique()->count(), 'type' => 'number'],
                ['label' => 'Total SPP', 'value' => (int) $accepted->where('group', 'spp')->sum('amount'), 'type' => 'money'],
                ['label' => 'Total Daftar Ulang', 'value' => (int) $accepted->where('group', 'daftar-ulang')->sum('amount'), 'type' => 'money'],
                ['label' => 'Total Laundry', 'value' => (int) $accepted->where('group', 'laundry')->sum('amount'), 'type' => 'money'],
                ['label' => 'Total Lain-lain', 'value' => (int) $accepted->where('group', 'lain-lain')->sum('amount'), 'type' => 'money'],
            ],
            'summaryColumns' => [
                ['key' => 'no', 'label' => 'No'],
                ['key' => 'unit', 'label' => 'Unit Pendidikan'],
                ['key' => 'transactions', 'label' => 'Jumlah Transaksi', 'type' => 'number'],
                ['key' => 'amount', 'label' => 'Total Penerimaan', 'type' => 'money'],
            ],
            'summaryRows' => $this->transactionUnitSummary($accepted),
        ];
    }

    public function monthlySpp(array $filters): array
    {
        $period = $this->selectedSppPeriod($filters);
        $students = $this->filteredStudents($filters)->get();
        $bills = Bill::where('source_type', 'spp')
            ->where('year', $period['year'])
            ->where('month', $period['month'])
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($bills, $period) {
            $bill = $bills->get($student->id);
            $status = $this->sppStatus($bill?->total_amount ?? 0, $bill?->paid_amount ?? 0, $bill?->remaining_amount ?? 0);

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'student' => $student->name,
                'unit' => $student->schoolClass?->educationUnit?->code ?? '-',
                'unit_name' => $student->schoolClass?->educationUnit?->name ?? 'Tanpa Unit',
                'class' => $student->schoolClass?->name ?? '-',
                'month' => self::MONTHS[$period['month']].' '.$period['year'],
                'billed' => (int) ($bill?->total_amount ?? 0),
                'paid' => (int) ($bill?->paid_amount ?? 0),
                'remaining' => (int) ($bill?->remaining_amount ?? 0),
                'status' => $status,
                'status_key' => $this->sppStatusKey($status),
            ];
        })->filter(fn (array $row) => ! $filters['spp_status'] || $row['status_key'] === $filters['spp_status'])->values();

        return [
            'rows' => $rows,
            'summaryCards' => [
                ['label' => 'Jumlah Siswa', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Sudah Bayar', 'value' => $rows->where('status_key', 'paid')->count(), 'type' => 'number'],
                ['label' => 'Sebagian', 'value' => $rows->where('status_key', 'partial')->count(), 'type' => 'number'],
                ['label' => 'Belum Bayar', 'value' => $rows->where('status_key', 'unpaid')->count(), 'type' => 'number'],
                ['label' => 'Total Terbayar', 'value' => (int) $rows->sum('paid'), 'type' => 'money'],
                ['label' => 'Total Tunggakan', 'value' => (int) $rows->sum('remaining'), 'type' => 'money'],
            ],
            'summaryColumns' => [
                ['key' => 'no', 'label' => 'No'],
                ['key' => 'unit', 'label' => 'Unit Pendidikan'],
                ['key' => 'students', 'label' => 'Jumlah Siswa', 'type' => 'number'],
                ['key' => 'paid_count', 'label' => 'Sudah Bayar', 'type' => 'number'],
                ['key' => 'partial_count', 'label' => 'Sebagian', 'type' => 'number'],
                ['key' => 'unpaid_count', 'label' => 'Belum Bayar', 'type' => 'number'],
                ['key' => 'paid', 'label' => 'Total Terbayar', 'type' => 'money'],
                ['key' => 'remaining', 'label' => 'Total Tunggakan', 'type' => 'money'],
            ],
            'summaryRows' => $this->monthlySppUnitSummary($rows),
        ];
    }

    public function outstandingSpp(array $filters): array
    {
        $period = $this->selectedSppPeriod($filters, true);
        $studentIds = $this->filteredStudents($filters)->pluck('students.id')->all();
        $bills = Bill::with('student.schoolClass.educationUnit')
            ->where('source_type', 'spp')
            ->where('remaining_amount', '>', 0)
            ->whereIn('student_id', $studentIds)
            ->where(function ($query) use ($period) {
                $query->where('year', '<', $period['year'])
                    ->orWhere(fn ($query) => $query->where('year', $period['year'])->where('month', '<=', $period['month']));
            })
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $rows = $bills->groupBy('student_id')->map(function (Collection $studentBills) {
            $student = $studentBills->first()->student;

            return [
                'student_id' => $student?->id,
                'nis' => $student?->nis,
                'student' => $student?->name,
                'unit' => $student?->schoolClass?->educationUnit?->code ?? '-',
                'unit_name' => $student?->schoolClass?->educationUnit?->name ?? 'Tanpa Unit',
                'class' => $student?->schoolClass?->name ?? '-',
                'months' => $studentBills->map(fn (Bill $bill) => self::MONTHS[(int) $bill->month].' '.$bill->year)->join(', '),
                'month_count' => $studentBills->count(),
                'remaining' => (int) $studentBills->sum('remaining_amount'),
            ];
        })->values();

        return [
            'rows' => $rows,
            'summaryCards' => [
                ['label' => 'Siswa Menunggak', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Jumlah Bulan Tunggakan', 'value' => (int) $rows->sum('month_count'), 'type' => 'number'],
                ['label' => 'Total Tunggakan', 'value' => (int) $rows->sum('remaining'), 'type' => 'money'],
            ],
            'summaryColumns' => [
                ['key' => 'no', 'label' => 'No'],
                ['key' => 'unit', 'label' => 'Unit Pendidikan'],
                ['key' => 'students', 'label' => 'Siswa Menunggak', 'type' => 'number'],
                ['key' => 'month_count', 'label' => 'Jumlah Bulan Tunggakan', 'type' => 'number'],
                ['key' => 'remaining', 'label' => 'Total Tunggakan', 'type' => 'money'],
            ],
            'summaryRows' => $this->outstandingSppUnitSummary($rows),
        ];
    }

    public function yearlySpp(array $filters): array
    {
        $academicYear = AcademicYear::find($filters['academic_year_id']);
        $months = $this->academicMonths($academicYear);
        $students = $this->filteredStudents($filters)->get();
        $bills = Bill::where('source_type', 'spp')
            ->whereIn('student_id', $students->pluck('id')->all())
            ->where(function ($query) use ($months) {
                foreach ($months as $index => $month) {
                    $query->{$index === 0 ? 'where' : 'orWhere'}(fn ($query) => $query->where('year', $month['year'])->where('month', $month['month']));
                }
            })
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function (Student $student) use ($bills, $months) {
            $studentBills = $bills->get($student->id, collect())->keyBy(fn (Bill $bill) => $bill->year.'-'.$bill->month);
            $row = [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'student' => $student->name,
                'unit' => $student->schoolClass?->educationUnit?->code ?? '-',
                'unit_name' => $student->schoolClass?->educationUnit?->name ?? 'Tanpa Unit',
                'class' => $student->schoolClass?->name ?? '-',
                'total_paid' => 0,
                'remaining' => 0,
                'total_billed' => 0,
            ];

            foreach ($months as $month) {
                $key = 'm_'.$month['month'].'_'.$month['year'];
                $bill = $studentBills->get($month['year'].'-'.$month['month']);
                $row[$key] = $bill ? $this->sppStatus((int) $bill->total_amount, (int) $bill->paid_amount, (int) $bill->remaining_amount) : 'Tidak Ditagih';
                $row['total_paid'] += (int) ($bill?->paid_amount ?? 0);
                $row['remaining'] += (int) ($bill?->remaining_amount ?? 0);
                $row['total_billed'] += (int) ($bill?->total_amount ?? 0);
            }

            return $row;
        })->values();

        return [
            'rows' => $rows,
            'months' => $months,
            'summaryCards' => [
                ['label' => 'Total Tagihan SPP', 'value' => (int) $rows->sum('total_billed'), 'type' => 'money'],
                ['label' => 'Total Terbayar', 'value' => (int) $rows->sum('total_paid'), 'type' => 'money'],
                ['label' => 'Total Tunggakan', 'value' => (int) $rows->sum('remaining'), 'type' => 'money'],
                ['label' => 'Jumlah Siswa', 'value' => $rows->count(), 'type' => 'number'],
            ],
            'summaryColumns' => [],
            'summaryRows' => collect(),
        ];
    }

    public function unitRecap(array $filters): array
    {
        $units = EducationUnit::query()
            ->when($filters['unit_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderByRaw($this->educationUnitOrderExpression())
            ->orderBy('name')
            ->get();
        $transactions = $this->transactionRows($filters)->where('status', 'Diterima');
        $outstanding = $this->outstandingSpp($filters)['rows'];

        $rows = $units->map(function (EducationUnit $unit) use ($transactions, $outstanding) {
            $unitTransactions = $transactions->where('unit_id', $unit->id);
            $unitOutstanding = $outstanding->where('unit_name', $unit->name);

            return [
                'unit_id' => $unit->id,
                'unit' => $unit->name,
                'spp' => (int) $unitTransactions->where('group', 'spp')->sum('amount'),
                'daftar_ulang' => (int) $unitTransactions->where('group', 'daftar-ulang')->sum('amount'),
                'laundry' => (int) $unitTransactions->where('group', 'laundry')->sum('amount'),
                'lain_lain' => (int) $unitTransactions->where('group', 'lain-lain')->sum('amount'),
                'total' => (int) $unitTransactions->sum('amount'),
                'outstanding_spp' => (int) $unitOutstanding->sum('remaining'),
            ];
        })->values();

        return [
            'rows' => $rows,
            'summaryCards' => [
                ['label' => 'Total Penerimaan', 'value' => (int) $rows->sum('total'), 'type' => 'money'],
                ['label' => 'Total SPP', 'value' => (int) $rows->sum('spp'), 'type' => 'money'],
                ['label' => 'Total Lain-lain', 'value' => (int) $rows->sum('lain_lain'), 'type' => 'money'],
                ['label' => 'Total Tunggakan SPP', 'value' => (int) $rows->sum('outstanding_spp'), 'type' => 'money'],
            ],
            'summaryColumns' => [],
            'summaryRows' => collect(),
        ];
    }

    public function academicMonths(?AcademicYear $academicYear): array
    {
        $startYear = now()->year;
        if ($academicYear && preg_match('/^(\d{4})\/(\d{4})$/', (string) $academicYear->name, $matches)) {
            $startYear = (int) $matches[1];
        } elseif ($academicYear?->start_date) {
            $startYear = (int) $academicYear->start_date->year;
        }

        return collect([7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6])
            ->map(fn (int $month) => [
                'month' => $month,
                'year' => $month >= 7 ? $startYear : $startYear + 1,
                'label' => mb_substr(self::MONTHS[$month], 0, 3),
            ])
            ->all();
    }

    private function transactionRows(array $filters): Collection
    {
        $filterPayment = function ($query) use ($filters) {
            return $query->whereBetween('transaction_at', [$filters['date_from'], $filters['date_to']])
                ->when($filters['payment_method'], fn ($query, $method) => $query->where('payment_method', $method))
                ->when($filters['payment_status'], fn ($query, $status) => $query->where('status', $status))
                ->when($filters['operator_name'], fn ($query, $operator) => $query->where('operator_name', $operator))
                ->when($filters['unit_id'], fn ($query, $unit) => $query->whereHas('student.schoolClass', fn ($query) => $query->where('education_unit_id', $unit)))
                ->when($filters['unit_ids'] ?? null, fn ($query, $units) => $query->whereHas('student.schoolClass', fn ($query) => $query->whereIn('education_unit_id', $units)))
                ->when($filters['class_id'], fn ($query, $class) => $query->whereHas('student', fn ($query) => $query->where('school_class_id', $class)))
                ->when($filters['student_search'], fn ($query, $search) => $query->whereHas('student', fn ($query) => $query->where('nis', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                ->when($filters['search'], fn ($query, $search) => $query->whereHas('student', fn ($query) => $query->where('nis', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        };

        $spp = collect();
        if (! $filters['type'] || $filters['type'] === 'spp') {
            $spp = $filterPayment(SppPayment::with('student.schoolClass.educationUnit'))->get()->map(fn (SppPayment $payment) => $this->paymentRow(
                'spp-'.$payment->id,
                $payment->student,
                $payment->transaction_at,
                'SPP',
                'spp',
                'Pembayaran SPP',
                $payment->payment_method,
                $payment->status,
                $payment->operator_name,
                (int) $payment->paid_amount,
            ));
        }

        $other = collect();
        if ($filters['type'] !== 'spp') {
            $query = $filterPayment(OtherPayment::with(['student.schoolClass.educationUnit', 'feeType']));
            if ($filters['type']) {
                $query->whereHas('feeType', fn ($query) => $query->where('payment_group', $filters['type']));
            }
            $other = $query->get()->map(function (OtherPayment $payment) {
                $group = $this->otherPaymentGroup($payment);

                return $this->paymentRow(
                    'other-'.$payment->id,
                    $payment->student,
                    $payment->transaction_at,
                    $this->paymentGroupLabel($group),
                    $group,
                    $payment->feeType?->name ?? 'Pembayaran lain-lain',
                    $payment->payment_method,
                    $payment->status,
                    $payment->operator_name,
                    (int) $payment->paid_amount,
                );
            });
        }

        return $spp->concat($other)->sortByDesc('date_sort')->values();
    }

    private function paymentRow(
        string $id,
        ?Student $student,
        $date,
        string $type,
        string $group,
        string $description,
        ?string $method,
        ?string $status,
        ?string $operator,
        int $amount,
    ): array {
        return [
            'id' => $id,
            'student_id' => $student?->id,
            'date_sort' => $date,
            'date' => $date?->format('d/m/Y H:i') ?? '-',
            'nis' => $student?->nis ?? '-',
            'student' => $student?->name ?? '-',
            'unit_id' => $student?->schoolClass?->educationUnit?->id,
            'unit' => $student?->schoolClass?->educationUnit?->code ?? '-',
            'unit_name' => $student?->schoolClass?->educationUnit?->name ?? 'Tanpa Unit',
            'class' => $student?->schoolClass?->name ?? '-',
            'type' => $type,
            'group' => $group,
            'description' => $description,
            'method' => $method ?: '-',
            'status' => $status ?: '-',
            'operator' => $operator ?: '-',
            'amount' => $amount,
        ];
    }

    private function filteredStudents(array $filters)
    {
        return Student::with('schoolClass.educationUnit')
            ->when($filters['academic_year_id'], fn ($query, $year) => $query->where('academic_year_id', $year))
            ->when($filters['unit_id'], fn ($query, $unit) => $query->whereHas('schoolClass', fn ($query) => $query->where('education_unit_id', $unit)))
            ->when($filters['unit_ids'] ?? null, fn ($query, $units) => $query->whereHas('schoolClass', fn ($query) => $query->whereIn('education_unit_id', $units)))
            ->when($filters['class_id'], fn ($query, $class) => $query->where('school_class_id', $class))
            ->when($filters['student_search'] ?: $filters['search'], function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nis', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }

    private function selectedSppPeriod(array $filters, bool $until = false): array
    {
        $academicYear = AcademicYear::find($filters['academic_year_id']);
        $month = $until ? $filters['until_month'] : $filters['month'];
        $months = collect($this->academicMonths($academicYear))->keyBy('month');
        $period = $months->get($month) ?? ['month' => $month, 'year' => now()->year];

        return ['month' => (int) $period['month'], 'year' => (int) $period['year']];
    }

    private function sppStatus(int $billed, int $paid, int $remaining): string
    {
        if ($billed > 0 && $remaining <= 0) {
            return 'Sudah Bayar';
        }

        if ($paid > 0 && $remaining > 0) {
            return 'Sebagian';
        }

        return 'Belum Bayar';
    }

    private function sppStatusKey(string $status): string
    {
        return match ($status) {
            'Sudah Bayar' => 'paid',
            'Sebagian' => 'partial',
            default => 'unpaid',
        };
    }

    private function transactionUnitSummary(Collection $rows): Collection
    {
        return $rows->groupBy('unit_name')->map(fn (Collection $unitRows, string $unitName) => [
            'unit' => $unitName,
            'transactions' => $unitRows->count(),
            'amount' => (int) $unitRows->sum('amount'),
        ])->values();
    }

    private function monthlySppUnitSummary(Collection $rows): Collection
    {
        return $rows->groupBy('unit_name')->map(fn (Collection $unitRows, string $unitName) => [
            'unit' => $unitName,
            'students' => $unitRows->count(),
            'paid_count' => $unitRows->where('status_key', 'paid')->count(),
            'partial_count' => $unitRows->where('status_key', 'partial')->count(),
            'unpaid_count' => $unitRows->where('status_key', 'unpaid')->count(),
            'paid' => (int) $unitRows->sum('paid'),
            'remaining' => (int) $unitRows->sum('remaining'),
        ])->values();
    }

    private function outstandingSppUnitSummary(Collection $rows): Collection
    {
        return $rows->groupBy('unit_name')->map(fn (Collection $unitRows, string $unitName) => [
            'unit' => $unitName,
            'students' => $unitRows->count(),
            'month_count' => (int) $unitRows->sum('month_count'),
            'remaining' => (int) $unitRows->sum('remaining'),
        ])->values();
    }

    private function filterDate(Request $request, string $key, bool $endOfDay = false): ?CarbonImmutable
    {
        $value = trim($request->string($key)->value());
        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function otherPaymentGroup(OtherPayment $payment): string
    {
        $group = $payment->feeType?->payment_group;
        if (in_array($group, ['daftar-ulang', 'laundry', 'lain-lain'], true)) {
            return $group;
        }

        $name = strtolower((string) $payment->feeType?->name);
        $code = strtoupper((string) $payment->feeType?->code);

        return match (true) {
            str_contains($code, 'DAFTAR-ULANG') => 'daftar-ulang',
            str_contains($name, 'laundry') => 'laundry',
            default => 'lain-lain',
        };
    }

    private function paymentGroupLabel(string $group): string
    {
        return [
            'spp' => 'SPP',
            'daftar-ulang' => 'Daftar Ulang',
            'laundry' => 'Laundry',
            'lain-lain' => 'Lain-lain',
        ][$group] ?? 'Lain-lain';
    }

    private function educationUnitOrderExpression(): string
    {
        return "CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END";
    }
}
