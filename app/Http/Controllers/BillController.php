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
    public function index(Request $request, BillQueryService $bills)
    {
        [$year, $untilMonth] = $this->period($request);
        $filters = [
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'student_search' => $request->string('student_search')->value() ?: null,
            'student_name' => $request->string('student_name')->value() ?: null,
            'nis' => $request->string('nis')->value() ?: null,
            'search' => $request->string('search')->value() ?: null,
        ];
        $sort = in_array($request->string('sort')->value(), ['name', 'nis', 'unit', 'class', 'total'], true)
            ? $request->string('sort')->value()
            : 'name';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';
        $perPage = $this->perPage($request);
        $students = $bills->summaries($year, $untilMonth, $filters, $perPage, $sort, $direction);

        return view('finance.bills', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'studentsWithBills' => $students,
            'year' => $year,
            'untilMonth' => $untilMonth,
            'stats' => $bills->stats($year, $untilMonth, $filters),
            'educationUnits' => EducationUnit::where('is_active', true)->orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(),
            'classes' => SchoolClass::select('school_classes.*')
                ->with('educationUnit')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->where('school_classes.is_active', true)
                ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression()))
                ->orderBy('school_classes.name')
                ->get(),
            'studentOptions' => Student::select(['id', 'nis', 'name', 'school_class_id'])
                ->with('schoolClass.educationUnit:id,code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
        ]);
    }

    public function sync(Request $request, BillService $bills)
    {
        [$year, $untilMonth] = $this->period($request);
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();
        $filters = [
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'student_id' => $request->integer('student_id') ?: null,
            'student_search' => $request->string('student_search')->value() ?: null,
            'student_name' => $request->string('student_name')->value() ?: null,
            'nis' => $request->string('nis')->value() ?: null,
        ];

        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $this->mergeResult($result, $bills->generateSppFromEntryUntil($academicYear, $year, $untilMonth, $filters));

        FeeType::where('is_active', true)
            ->where('creates_bill', true)
            ->where(function ($query) {
                $query->whereNull('payment_group')->orWhereNotIn('payment_group', ['spp', 'laundry']);
            })
            ->orderBy('name')
            ->get()
            ->each(function (FeeType $feeType) use ($academicYear, $year, $untilMonth, $filters, $bills, &$result) {
                $this->mergeResult($result, $bills->generateFeeType($academicYear, $feeType, $year, $untilMonth, $filters));
            });

        return redirect()
            ->route('finance.bills.index', $request->only(['year', 'until_month', 'unit_id', 'class_id', 'student_id', 'student_search', 'student_name', 'nis', 'search', 'per_page', 'sort', 'direction']))
            ->with('success', 'Sinkron tagihan selesai. Baru: '.number_format($result['created'], 0, ',', '.').', sudah ada: '.number_format($result['existing'], 0, ',', '.').', dilewati: '.number_format($result['skipped'], 0, ',', '.').'.');
    }

    private function period(Request $request): array
    {
        $year = $request->integer('year');
        $year = $year >= 2000 && $year <= 2100 ? $year : now()->year;
        $untilMonth = $request->integer('until_month');
        $untilMonth = $untilMonth >= 1 && $untilMonth <= 12 ? $untilMonth : now()->month;

        return [$year, $untilMonth];
    }

    private function mergeResult(array &$base, array $addition): void
    {
        foreach (['created', 'existing', 'skipped'] as $key) {
            $base[$key] += $addition[$key] ?? 0;
        }
    }

    private function educationUnitOrderExpression(): string
    {
        return "CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END";
    }
}
