<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\FeeType;
use App\Models\Student;
use App\Services\OtherPaymentService;
use App\Services\SppPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request, SppPaymentService $sppPayments, OtherPaymentService $otherPayments): View
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

                $registrations->each(function (Student $student) use ($feeTypes, $sppPayments, $otherPayments) {
                    $student->setAttribute('payment_options', $this->paymentOptions($student, $feeTypes, $sppPayments, $otherPayments));
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

    public function history(): View
    {
        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'mode' => 'history',
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

    private function paymentOptions(Student $student, Collection $feeTypes, SppPaymentService $sppPayments, OtherPaymentService $otherPayments): array
    {
        $matchedFeeTypes = $this->matchedFeeTypes($student, $feeTypes);
        $grouped = $matchedFeeTypes->groupBy(fn (FeeType $feeType) => $this->paymentGroup($feeType));
        $definitions = [
            'spp' => ['label' => 'SPP', 'url' => route('finance.spp.create', ['student_id' => $student->id])],
            'daftar-ulang' => ['label' => 'Daftar Ulang', 'url' => route('finance.other.create', ['category' => 'daftar-ulang', 'student_id' => $student->id])],
            'laundry' => ['label' => 'Laundry', 'url' => route('finance.other.create', ['category' => 'laundry', 'student_id' => $student->id])],
            'lain-lain' => ['label' => 'Lainnya', 'url' => route('finance.other.create', ['student_id' => $student->id])],
        ];

        return collect($definitions)
            ->filter(fn (array $definition, string $group) => $grouped->has($group))
            ->map(function (array $definition, string $group) use ($student, $grouped, $sppPayments, $otherPayments) {
                $feeTypes = $grouped->get($group);
                $payable = $group === 'spp'
                    ? ($sppPayments->paymentPlan($student)['max_month_count'] > 0)
                    : $this->hasPayableFeeType($student, $feeTypes, $group, $otherPayments);

                return [
                    'key' => $group,
                    'label' => $definition['label'],
                    'url' => $definition['url'],
                    'status' => $payable ? 'payable' : 'paid',
                ];
            })
            ->values()
            ->all();
    }

    private function matchedFeeTypes(Student $student, Collection $feeTypes): Collection
    {
        $unitId = $student->schoolClass?->education_unit_id;

        return $feeTypes->filter(fn (FeeType $feeType) => $feeType->education_unit_id === $unitId
            && (! $feeType->school_class_id || $feeType->school_class_id === $student->school_class_id)
            && ($this->paymentGroup($feeType) === 'daftar-ulang' || ! $feeType->academic_year_id || $feeType->academic_year_id === $student->academic_year_id));
    }

    private function hasPayableFeeType(Student $student, Collection $feeTypes, string $group, OtherPaymentService $otherPayments): bool
    {
        if ($feeTypes->contains(fn (FeeType $feeType) => ! $feeType->creates_bill)) {
            return true;
        }

        if ($group === 'daftar-ulang') {
            foreach ($feeTypes as $feeType) {
                try {
                    if (($otherPayments->quote($student, $feeType)['remaining_amount'] ?? 0) > 0) {
                        return true;
                    }
                } catch (ValidationException) {
                    continue;
                }
            }

            return false;
        }

        return Bill::where('student_id', $student->id)
            ->where('source_type', 'fee_type')
            ->whereIn('fee_type_id', $feeTypes->pluck('id'))
            ->where('remaining_amount', '>', 0)
            ->where('status', '!=', 'Dibatalkan')
            ->exists();
    }
}
