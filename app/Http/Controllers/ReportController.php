<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $transactions = $this->transactions($filters);
        $sort = in_array($request->string('sort')->value(), ['date', 'nis', 'student', 'type', 'description', 'unit', 'class', 'method', 'status', 'amount'], true)
            ? $request->string('sort')->value()
            : 'date';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';
        $transactions = $transactions->sortBy($sort, SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $this->perPage($request);

        return view('reports.index', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            ...$this->filterOptions(),
            'filters' => $filters,
            'transactions' => new LengthAwarePaginator(
                $transactions->forPage($page, $perPage)->values(),
                $transactions->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            ),
            'stats' => $this->stats($transactions),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $transactions = $this->transactions($filters);
        $filename = 'laporan-pembayaran-'.$filters['date_from']->format('Ymd').'-'.$filters['date_to']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Tanggal', 'Jenis', 'Kategori', 'NIS', 'Nama Siswa', 'Unit Pendidikan', 'Kelas', 'Cara Bayar', 'Status', 'Petugas', 'Nominal']);
            foreach ($transactions as $item) {
                fputcsv($file, [
                    $item['date']->format('d/m/Y H:i'),
                    $item['type'],
                    $item['description'],
                    $item['nis'],
                    $item['student'],
                    $item['unit'],
                    $item['class'],
                    $item['method'],
                    $item['status'],
                    $item['operator'],
                    $item['amount'],
                ]);
            }
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $dateFrom = $this->filterDate($request, 'date_from')
            ?? $this->filterDate($request, 'start_date')
            ?? CarbonImmutable::now()->startOfMonth()->startOfDay();
        $dateTo = $this->filterDate($request, 'date_to', true)
            ?? $this->filterDate($request, 'end_date', true)
            ?? CarbonImmutable::now()->endOfDay();

        if ($dateTo->lt($dateFrom)) {
            $dateTo = $dateFrom->endOfDay();
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => in_array($request->string('type')->value(), ['spp', 'daftar-ulang', 'laundry', 'lain-lain'], true)
                ? $request->string('type')->value()
                : null,
            'payment_method' => in_array($request->string('payment_method')->value(), ['Cash', 'Transfer'], true)
                ? $request->string('payment_method')->value()
                : null,
            'status' => in_array($request->string('status')->value(), ['Diterima', 'Pending'], true)
                ? $request->string('status')->value()
                : null,
            'operator_name' => $request->string('operator_name')->value() ?: null,
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'student_search' => $request->string('student_search')->value() ?: null,
            'search' => $request->string('search')->value() ?: null,
        ];
    }

    private function transactions(array $filters): Collection
    {
        $filterQuery = function ($query) use ($filters) {
            return $query->whereBetween('transaction_at', [$filters['date_from'], $filters['date_to']])
                ->when($filters['payment_method'], fn ($q, $method) => $q->where('payment_method', $method))
                ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
                ->when($filters['operator_name'], fn ($q, $operator) => $q->where('operator_name', $operator))
                ->when($filters['unit_id'], fn ($q, $unit) => $q->whereHas('student.schoolClass', fn ($class) => $class->where('education_unit_id', $unit)))
                ->when($filters['class_id'], fn ($q, $classId) => $q->whereHas('student', fn ($student) => $student->where('school_class_id', $classId)))
                ->when($filters['student_id'], fn ($q, $studentId) => $q->where('student_id', $studentId))
                ->when(! $filters['student_id'] && $filters['student_search'], fn ($q, $search) => $q->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
                ->when($filters['search'], fn ($q, $search) => $q->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")));
        };

        $spp = collect();
        if (! $filters['type'] || $filters['type'] === 'spp') {
            $spp = $filterQuery(SppPayment::with('student.schoolClass.educationUnit'))->get()->map(fn ($payment) => [
                'id' => 'spp-'.$payment->id, 'student_id' => $payment->student_id, 'date' => $payment->transaction_at,
                'type' => 'SPP', 'group' => 'spp', 'nis' => $payment->student?->nis, 'student' => $payment->student?->name,
                'unit' => $payment->student?->schoolClass?->educationUnit?->code ?? '-', 'class' => $payment->student?->schoolClass?->name ?? '-',
                'description' => 'Pembayaran SPP', 'method' => $payment->payment_method, 'status' => $payment->status,
                'operator' => $payment->operator_name ?: '-', 'amount' => (int) $payment->paid_amount,
            ]);
        }

        $other = collect();
        if ($filters['type'] !== 'spp') {
            $otherQuery = $filterQuery(OtherPayment::with(['student.schoolClass.educationUnit', 'feeType']));
            if ($filters['type']) {
                $otherQuery->whereHas('feeType', fn ($feeType) => $feeType->paymentGroup($filters['type']));
            }
            $other = $otherQuery->get()->map(function ($payment) {
                $group = $this->otherPaymentGroup($payment);

                return [
                    'id' => 'other-'.$payment->id, 'student_id' => $payment->student_id, 'date' => $payment->transaction_at,
                    'type' => $this->paymentGroupLabel($group), 'group' => $group, 'nis' => $payment->student?->nis, 'student' => $payment->student?->name,
                    'unit' => $payment->student?->schoolClass?->educationUnit?->code ?? '-', 'class' => $payment->student?->schoolClass?->name ?? '-',
                    'description' => $payment->feeType?->name ?? 'Pembayaran lain-lain', 'method' => $payment->payment_method, 'status' => $payment->status,
                    'operator' => $payment->operator_name ?: '-', 'amount' => (int) $payment->paid_amount,
                ];
            });
        }

        return $spp->concat($other)->sortByDesc('date')->values();
    }

    private function stats(Collection $transactions): array
    {
        $accepted = $transactions->where('status', 'Diterima');

        return [
            'total' => $accepted->sum('amount'),
            'spp' => $accepted->where('group', 'spp')->sum('amount'),
            'daftar_ulang' => $accepted->where('group', 'daftar-ulang')->sum('amount'),
            'laundry' => $accepted->where('group', 'laundry')->sum('amount'),
            'lain_lain' => $accepted->where('group', 'lain-lain')->sum('amount'),
            'transactions' => $transactions->count(),
            'accepted_transactions' => $accepted->count(),
            'students' => $accepted->pluck('student_id')->unique()->count(),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'educationUnits' => EducationUnit::orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(),
            'classes' => SchoolClass::with('educationUnit')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->select('school_classes.*')
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('school_classes.name')
                ->get(),
            'studentOptions' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('students.name')
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

    private function filterDate(Request $request, string $key, bool $endOfDay = false): ?CarbonImmutable
    {
        $value = trim($request->string($key)->value());
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    return $endOfDay ? $date->endOfDay() : $date->startOfDay();
                }
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }

    private function otherPaymentGroup(OtherPayment $payment): string
    {
        $group = $payment->feeType?->payment_group;
        if (in_array($group, ['daftar-ulang', 'laundry', 'lain-lain'], true)) {
            return $group;
        }

        $name = (string) $payment->feeType?->name;
        $code = (string) $payment->feeType?->code;

        return match (true) {
            str_contains($code, 'DAFTAR-ULANG') => 'daftar-ulang',
            str_contains(strtolower($name), 'laundry') => 'laundry',
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
