<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\BillQueryService;
use App\Services\BillService;
use Illuminate\Http\Request;

class BillController extends Controller
{
    private const MONTHS = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

    public function index(Request $request, BillQueryService $bills)
    {
        [$year, $untilMonth] = $this->period($request);
        $status = $this->statusFilter($request);
        $filters = [
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'fee_type_id' => $request->integer('fee_type_id') ?: null,
            'status' => $status,
            'student_search' => $request->string('student_search')->value() ?: null,
            'student_name' => $request->string('student_name')->value() ?: null,
            'nis' => $request->string('nis')->value() ?: null,
            'search' => $request->string('search')->value() ?: null,
        ];
        $this->applyUserScope($request, $filters);
        $sort = in_array($request->string('sort')->value(), ['name', 'nis', 'unit', 'class', 'total'], true)
            ? $request->string('sort')->value()
            : 'name';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);
        $students = $bills->summaries($year, $untilMonth, $filters, $perPage, $sort, $direction);
        $overviewFilters = array_merge($filters, [
            'unit_id' => null,
            'class_id' => null,
            'student_id' => null,
        ]);
        $unitIds = $request->user()?->accessibleUnitIds();

        return view('finance.bills', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'studentsWithBills' => $students,
            'year' => $year,
            'untilMonth' => $untilMonth,
            'overviewStats' => $bills->stats($year, $untilMonth, $overviewFilters),
            'unitSummaries' => $bills->unitBreakdown($year, $untilMonth, $overviewFilters),
            'educationUnits' => EducationUnit::where('is_active', true)
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('id', $unitIds))
                ->orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(),
            'classes' => SchoolClass::select('school_classes.*')
                ->with('educationUnit')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->where('school_classes.is_active', true)
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('school_classes.education_unit_id', $unitIds))
                ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression()))
                ->orderBy('school_classes.name')
                ->get(),
        ]);
    }

    public function show(Request $request, Student $student, BillQueryService $bills)
    {
        [$year, $untilMonth] = $this->period($request);
        $student->load('schoolClass.educationUnit');
        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds) && ! in_array((int) $student->schoolClass?->education_unit_id, $unitIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke tagihan siswa ini.');
        }
        $statement = $bills->statement($student, $year, $untilMonth);
        $issuedAt = now();

        return view('finance.bill-notice', [
            'student' => $student,
            'statement' => $statement,
            'year' => $year,
            'untilMonth' => $untilMonth,
            'issuedDate' => $issuedAt->day.' '.self::MONTHS[(int) $issuedAt->month].' '.$issuedAt->year,
            'amountWords' => $this->terbilangRupiah((int) $statement['total']),
            'backUrl' => route('finance.bills.index', $request->only(['year', 'until_month', 'unit_id', 'class_id', 'student_id', 'student_search', 'per_page', 'sort', 'direction'])),
        ]);
    }

    public function sync(Request $request, BillService $bills)
    {
        $this->extendSyncRuntime();

        [$year, $untilMonth] = $this->period($request);
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();
        $filters = [
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'fee_type_id' => $request->integer('fee_type_id') ?: null,
            'status' => $this->statusFilter($request),
            'student_search' => $request->string('student_search')->value() ?: null,
            'student_name' => $request->string('student_name')->value() ?: null,
            'nis' => $request->string('nis')->value() ?: null,
        ];

        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $this->mergeResult($result, $bills->generateSppFromEntryUntil($academicYear, $year, $untilMonth, $filters));

        $feeTypes = FeeType::where('is_active', true)
            ->where('creates_bill', true)
            ->where(function ($query) {
                $query->whereNull('payment_group')->orWhereNotIn('payment_group', ['spp', 'laundry']);
            })
            ->orderBy('name')
            ->get();

        $this->mergeResult($result, $bills->generateFeeTypes($academicYear, $feeTypes, $year, $untilMonth, $filters));

        return redirect()
            ->route('finance.bills.index', $request->only(['year', 'until_month', 'unit_id', 'class_id', 'student_id', 'fee_type_id', 'status', 'student_search', 'student_name', 'nis', 'search', 'per_page', 'sort', 'direction']))
            ->with('success', 'Sinkron tagihan selesai. Baru: '.number_format($result['created'], 0, ',', '.').', sudah ada: '.number_format($result['existing'], 0, ',', '.').', dilewati: '.number_format($result['skipped'], 0, ',', '.').'.');
    }

    private function applyUserScope(Request $request, array &$filters): void
    {
        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds)) {
            $filters['unit_ids'] = $unitIds;
            if ($filters['unit_id'] && ! in_array((int) $filters['unit_id'], $unitIds, true)) {
                $filters['unit_id'] = null;
            }
        }
    }

    private function period(Request $request): array
    {
        $year = $request->integer('year');
        $year = $year >= 2000 && $year <= 2100 ? $year : now()->year;
        $untilMonth = $request->integer('until_month');
        $untilMonth = $untilMonth >= 1 && $untilMonth <= 12 ? $untilMonth : now()->month;

        return [$year, $untilMonth];
    }

    private function statusFilter(Request $request): string
    {
        $status = $request->string('status')->value();

        return in_array($status, ['all', 'outstanding', 'partial', 'paid', 'overdue'], true)
            ? $status
            : 'outstanding';
    }

    private function mergeResult(array &$base, array $addition): void
    {
        foreach (['created', 'existing', 'skipped'] as $key) {
            $base[$key] += $addition[$key] ?? 0;
        }
    }

    private function extendSyncRuntime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
    }

    private function educationUnitOrderExpression(): string
    {
        return "CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END";
    }

    private function terbilangRupiah(int $amount): string
    {
        return $amount > 0 ? $this->terbilang($amount).' Rupiah' : 'Nol Rupiah';
    }

    private function terbilang(int $number): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($number < 12) {
            return $words[$number];
        }

        if ($number < 20) {
            return $this->terbilang($number - 10).' Belas';
        }

        if ($number < 100) {
            return trim($this->terbilang(intdiv($number, 10)).' Puluh '.$this->terbilang($number % 10));
        }

        if ($number < 200) {
            return trim('Seratus '.$this->terbilang($number - 100));
        }

        if ($number < 1000) {
            return trim($this->terbilang(intdiv($number, 100)).' Ratus '.$this->terbilang($number % 100));
        }

        if ($number < 2000) {
            return trim('Seribu '.$this->terbilang($number - 1000));
        }

        if ($number < 1000000) {
            return trim($this->terbilang(intdiv($number, 1000)).' Ribu '.$this->terbilang($number % 1000));
        }

        if ($number < 1000000000) {
            return trim($this->terbilang(intdiv($number, 1000000)).' Juta '.$this->terbilang($number % 1000000));
        }

        return trim($this->terbilang(intdiv($number, 1000000000)).' Miliar '.$this->terbilang($number % 1000000000));
    }
}
