<?php

namespace App\Services;

use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OutstandingBillService
{
    public function __construct(private ChargeCalculator $calculator) {}

    public function summary(int $year, int $untilMonth, array $filters = []): Collection
    {
        $feeTypes = FeeType::where('is_active', true)
            ->where('payment_group', '!=', 'spp')
            ->get();

        return Student::with('schoolClass.educationUnit')
            ->where('is_active', true)
            ->when($filters['unit_id'] ?? null, fn ($query, $id) => $query->whereHas('schoolClass', fn ($class) => $class->where('education_unit_id', $id)))
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('school_class_id', $id))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($student) => $student->where('nis', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student) => $this->studentSummary($student, $feeTypes, $year, $untilMonth))
            ->filter(fn (array $summary) => $summary['total_remaining'] > 0)
            ->values();
    }

    private function studentSummary(Student $student, Collection $feeTypes, int $year, int $untilMonth): array
    {
        $spp = [];
        foreach (range(1, $untilMonth) as $month) {
            $charge = $this->calculator->calculateSppMonth($student, $year, $month);
            if ($charge['original_amount'] < 1) {
                continue;
            }
            $paid = (int) SppPaymentItem::where('student_id', $student->id)->where('year', $year)->where('month', $month)->sum('paid_amount');
            $remaining = max(0, $charge['final_amount'] - $paid);
            if ($remaining > 0) {
                $spp[] = [
                    'month' => $month, 'month_name' => $this->monthName($month),
                    'total' => $charge['final_amount'], 'paid' => $paid, 'remaining' => $remaining,
                ];
            }
        }

        $other = [];
        $periodDate = CarbonImmutable::create($year, $untilMonth, 1);
        foreach ($feeTypes as $feeType) {
            $charge = $this->calculator->calculate($student, 'fee_type', $feeType, $periodDate);
            if ($charge['original_amount'] < 1) {
                continue;
            }
            $paid = (int) OtherPayment::where('student_id', $student->id)
                ->where('fee_type_id', $feeType->id)
                ->where('status', 'Diterima')
                ->when($feeType->period === 'Bulanan', fn ($query) => $query->whereYear('transaction_at', $year)->whereMonth('transaction_at', $untilMonth))
                ->when($feeType->period === 'Tahunan', function ($query) use ($student, $year) {
                    $student->loadMissing('academicYear');
                    if ($student->academicYear?->start_date && $student->academicYear?->end_date) {
                        $query->whereBetween('transaction_at', [$student->academicYear->start_date->startOfDay(), $student->academicYear->end_date->endOfDay()]);
                    } else {
                        $query->whereYear('transaction_at', $year);
                    }
                })
                ->sum('paid_amount');
            $remaining = max(0, $charge['final_amount'] - $paid);
            if ($remaining > 0) {
                $other[] = [
                    'fee_type_id' => $feeType->id,
                    'name' => $feeType->name.($feeType->period === 'Bulanan' ? ' · '.$this->monthName($untilMonth).' '.$year : ''),
                    'total' => $charge['final_amount'], 'paid' => $paid, 'remaining' => $remaining,
                ];
            }
        }

        return [
            'student' => $student,
            'spp' => $spp,
            'other' => $other,
            'spp_remaining' => array_sum(array_column($spp, 'remaining')),
            'other_remaining' => array_sum(array_column($other, 'remaining')),
            'total_remaining' => array_sum(array_column($spp, 'remaining')) + array_sum(array_column($other, 'remaining')),
        ];
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }
}
