<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->value());
        $people = collect();

        if ($search !== '') {
            $matches = Student::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->where('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))
                ->limit(30)
                ->get(['id', 'identity_student_id']);

            $identityIds = $matches
                ->map(fn (Student $student) => $student->identity_student_id ?: $student->id)
                ->unique()
                ->values();

            if ($identityIds->isNotEmpty()) {
                $registrations = Student::with(['schoolClass.educationUnit'])
                    ->where('is_active', true)
                    ->where(fn ($query) => $query
                        ->whereIn('id', $identityIds)
                        ->orWhereIn('identity_student_id', $identityIds))
                    ->orderBy('name')
                    ->get();
                $feeTypes = FeeType::where('is_active', true)->get();

                $registrations->each(function (Student $student) use ($feeTypes) {
                    $unitId = $student->schoolClass?->education_unit_id;
                    $student->setAttribute('payment_options', $feeTypes
                        ->filter(fn (FeeType $feeType) => $feeType->education_unit_id === $unitId
                            && (! $feeType->school_class_id || $feeType->school_class_id === $student->school_class_id)
                            && ($this->paymentGroup($feeType) === 'daftar-ulang' || ! $feeType->academic_year_id || $feeType->academic_year_id === $student->academic_year_id))
                        ->groupBy(fn (FeeType $feeType) => $this->paymentGroup($feeType))
                        ->keys()
                        ->all());
                });

                $people = $registrations->groupBy(fn (Student $student) => $student->identity_student_id ?: $student->id);
            }
        }

        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'search' => $search,
            'people' => $people,
            'mode' => 'payment',
        ]);
    }

    public function import(): View
    {
        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'mode' => 'import',
        ]);
    }

    private function paymentGroup(FeeType $feeType): string
    {
        if ($feeType->payment_group) {
            return $feeType->payment_group;
        }

        if ($feeType->code === 'DAFTAR-ULANG' || str_starts_with($feeType->code, 'DAFTAR-ULANG-')) {
            return 'daftar-ulang';
        }

        return str_contains(strtolower($feeType->name), 'laundry') ? 'laundry' : 'lain-lain';
    }
}
