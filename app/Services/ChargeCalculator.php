<?php

namespace App\Services;

use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ChargeCalculator
{
    public function baseAmount(Student $student, string $sourceType, ?FeeType $feeType = null): int
    {
        $student->loadMissing('schoolClass.educationUnit');

        if ($sourceType === 'spp') {
            $student->loadMissing('academicYear');

            return (int) (FeeType::query()
                ->paymentGroup('spp')
                ->where('is_active', true)
                ->where('education_unit_id', $student->schoolClass?->education_unit_id)
                ->where(fn ($query) => $query->whereNull('academic_year_id')->orWhere('academic_year_id', $student->academic_year_id))
                ->where(fn ($query) => $query->whereNull('school_class_id')->orWhere('school_class_id', $student->school_class_id))
                ->orderByRaw('CASE WHEN school_class_id IS NULL THEN 1 ELSE 0 END')
                ->orderByRaw('CASE WHEN academic_year_id IS NULL THEN 1 ELSE 0 END')
                ->value('amount') ?? 0);
        }

        $student->loadMissing('academicYear');

        if (! $feeType || ! $feeType->is_active
            || $feeType->education_unit_id !== $student->schoolClass?->education_unit_id
            || ($feeType->academic_year_id !== null && $feeType->academic_year_id !== $student->academic_year_id)
            || ($feeType->school_class_id !== null && $feeType->school_class_id !== $student->school_class_id)) {
            return 0;
        }

        return (int) $feeType->amount;
    }

    public function calculate(Student $student, string $sourceType, ?FeeType $feeType = null, ?CarbonInterface $date = null): array
    {
        $date ??= now();
        $originalAmount = $this->baseAmount($student, $sourceType, $feeType);
        $discount = $this->discountQuery($student, $sourceType, $feeType)
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->first();

        return $this->result($originalAmount, $discount);
    }

    public function calculateSppMonth(Student $student, int $year, int $month): array
    {
        $originalAmount = $this->baseAmount($student, 'spp');
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $discount = $this->discountQuery($student, 'spp')
            ->whereDate('start_date', '<=', $monthEnd)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $monthStart))
            ->first();

        return $this->result($originalAmount, $discount);
    }

    private function discountQuery(Student $student, string $sourceType, ?FeeType $feeType = null)
    {
        return FeeDiscount::query()
            ->where('student_id', $student->id)
            ->where('source_type', $sourceType)
            ->where('fee_type_id', $sourceType === 'fee_type' ? $feeType?->id : null)
            ->where('is_active', true);
    }

    private function result(int $originalAmount, ?FeeDiscount $discount): array
    {
        $discountAmount = $discount?->discountAmount($originalAmount) ?? 0;

        return [
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => max(0, $originalAmount - $discountAmount),
            'discount' => $discount,
        ];
    }

    public function ensureBaseAmountExists(Student $student, string $sourceType, ?FeeType $feeType = null): int
    {
        $amount = $this->baseAmount($student, $sourceType, $feeType);
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'source_type' => $sourceType === 'spp'
                    ? 'Kategori pembayaran SPP aktif untuk siswa belum tersedia.'
                    : 'Kategori pembayaran tidak aktif atau tidak berlaku untuk kelas siswa.',
            ]);
        }

        return $amount;
    }
}
