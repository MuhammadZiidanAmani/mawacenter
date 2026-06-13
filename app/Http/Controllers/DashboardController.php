<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'activeAcademicYear' => Schema::hasTable('academic_years')
                ? AcademicYear::where('is_active', true)->first()
                : null,
        ]);
    }
}
