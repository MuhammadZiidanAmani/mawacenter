<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Services\OutstandingBillService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BillController extends Controller
{
    public function index(Request $request, OutstandingBillService $outstanding)
    {
        $year = $request->integer('year');
        $year = $year >= 2000 && $year <= 2100 ? $year : now()->year;
        $untilMonth = $request->integer('until_month');
        $untilMonth = $untilMonth >= 1 && $untilMonth <= 12 ? $untilMonth : now()->month;
        $summaries = $outstanding->summary($year, $untilMonth, [
            'unit_id' => $request->integer('unit_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'search' => $request->string('search')->value() ?: null,
        ]);
        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100]) ? $request->integer('per_page') : 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $students = new LengthAwarePaginator(
            $summaries->forPage($page, $perPage)->values(),
            $summaries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('finance.bills', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'studentsWithBills' => $students,
            'year' => $year,
            'untilMonth' => $untilMonth,
            'stats' => [
                'students' => $summaries->count(),
                'spp' => $summaries->sum('spp_remaining'),
                'other' => $summaries->sum('other_remaining'),
                'remaining' => $summaries->sum('total_remaining'),
            ],
            'educationUnits' => EducationUnit::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::with('educationUnit')->where('is_active', true)->orderBy('name')->get(),
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
        ]);
    }
}
