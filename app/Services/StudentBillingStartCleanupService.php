<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Student;
use App\Support\PerformanceCache;
use App\Support\StudentBillingStart;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentBillingStartCleanupService
{
    public function preview(?array $unitIds = null): array
    {
        return $this->analyze($this->candidateQuery($unitIds)->get());
    }

    public function apply(?array $unitIds = null): array
    {
        $result = DB::transaction(function () use ($unitIds) {
            $students = $this->candidateQuery($unitIds)->lockForUpdate()->get();
            $analysis = $this->analyze($students, true);

            foreach ($students->whereIn('id', $analysis['student_ids']) as $student) {
                $student->update([
                    'billing_start_date' => StudentBillingStart::defaultFor(
                        $student->schoolClass?->educationUnit?->code,
                        $student->entry_date,
                    ),
                ]);
            }

            $cancelledBills = $analysis['cancelable_bill_ids'] === []
                ? 0
                : Bill::query()
                    ->whereIn('id', $analysis['cancelable_bill_ids'])
                    ->where('source_type', 'spp')
                    ->where('status', '!=', 'Dibatalkan')
                    ->where(fn ($query) => $query->whereNull('paid_amount')->orWhere('paid_amount', '<=', 0))
                    ->update([
                        'remaining_amount' => 0,
                        'status' => 'Dibatalkan',
                        'cancel_reason' => 'Tagihan tidak berlaku setelah data siswa diperbarui.',
                        'updated_at' => now(),
                    ]);

            return $analysis + [
                'updated_students' => count($analysis['student_ids']),
                'cancelled_bills' => $cancelledBills,
            ];
        });

        PerformanceCache::invalidate(['dashboard', 'bills', 'reports']);

        return $result;
    }

    private function candidateQuery(?array $unitIds): Builder
    {
        return Student::query()
            ->with(['schoolClass.educationUnit:id,code,name'])
            ->whereNull('billing_start_date')
            ->whereNotNull('entry_date')
            ->whereHas('schoolClass.educationUnit', function ($query) use ($unitIds) {
                $query->whereIn(DB::raw('UPPER(code)'), StudentBillingStart::unitCodes());

                if (is_array($unitIds)) {
                    $unitIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('education_units.id', $unitIds);
                }
            })
            ->orderBy('students.id');
    }

    private function analyze(Collection $students, bool $lockBills = false): array
    {
        $studentsById = $students->keyBy('id');
        $billQuery = Bill::query()
            ->whereIn('student_id', $studentsById->keys())
            ->where('source_type', 'spp')
            ->where('status', '!=', 'Dibatalkan')
            ->whereNotNull('year')
            ->whereNotNull('month')
            ->orderBy('id');

        if ($lockBills) {
            $billQuery->lockForUpdate();
        }

        $preStartBills = collect();

        foreach ($billQuery->get(['id', 'student_id', 'year', 'month', 'paid_amount', 'remaining_amount']) as $bill) {
            $student = $studentsById->get($bill->student_id);
            $billingStart = $student
                ? StudentBillingStart::defaultFor($student->schoolClass?->educationUnit?->code, $student->entry_date)
                : null;
            if (! $billingStart) {
                continue;
            }

            $period = CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1)->startOfMonth();
            if ($period->gte(CarbonImmutable::parse($billingStart)->startOfMonth())) {
                continue;
            }

            $preStartBills->push($bill);
        }

        $reviewStudentIds = $preStartBills
            ->filter(fn (Bill $bill) => (int) $bill->paid_amount > 0 && (int) $bill->remaining_amount > 0)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $studentsToUpdate = $students->reject(fn (Student $student) => $reviewStudentIds->contains((int) $student->id));
        $cancelableBillIds = $preStartBills
            ->reject(fn (Bill $bill) => $reviewStudentIds->contains((int) $bill->student_id))
            ->filter(fn (Bill $bill) => (int) $bill->paid_amount <= 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $paidBillsPreserved = $preStartBills->filter(fn (Bill $bill) => (int) $bill->paid_amount > 0)->count();

        $unitCounts = $studentsToUpdate
            ->groupBy(fn (Student $student) => $student->schoolClass?->educationUnit?->code ?? '-')
            ->map->count()
            ->sortKeys()
            ->all();

        return [
            'candidate_count' => $studentsToUpdate->count(),
            'total_candidate_count' => $students->count(),
            'cancelable_bill_count' => count($cancelableBillIds),
            'paid_bills_preserved' => $paidBillsPreserved,
            'partially_paid_bills' => $reviewStudentIds->count(),
            'unit_counts' => $unitCounts,
            'student_ids' => $studentsToUpdate->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'review_student_ids' => $reviewStudentIds->all(),
            'cancelable_bill_ids' => $cancelableBillIds,
        ];
    }
}
