<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Student;
use App\Support\PerformanceCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlumniBillCleanupService
{
    public function preview(?array $unitIds = null): array
    {
        return $this->analyze($this->candidateStudents($unitIds)->get());
    }

    public function apply(?array $unitIds = null): array
    {
        $result = DB::transaction(function () use ($unitIds) {
            $students = $this->candidateStudents($unitIds)->lockForUpdate()->get();
            $analysis = $this->analyze($students, true);

            $cancelledBills = $analysis['cancelable_bill_ids'] === []
                ? 0
                : Bill::query()
                    ->whereIn('id', $analysis['cancelable_bill_ids'])
                    ->whereIn('source_type', ['spp', 'fee_type'])
                    ->where('status', '!=', 'Dibatalkan')
                    ->where(fn ($query) => $query->whereNull('paid_amount')->orWhere('paid_amount', '<=', 0))
                    ->whereDoesntHave('allocations', fn ($allocation) => $allocation->where('amount', '>', 0))
                    ->update([
                        'remaining_amount' => 0,
                        'status' => 'Dibatalkan',
                        'cancel_reason' => 'Tagihan tidak berlaku setelah siswa keluar atau menjadi alumni.',
                        'updated_at' => now(),
                    ]);

            return $analysis + ['cancelled_bills' => $cancelledBills];
        });

        PerformanceCache::invalidate(['dashboard', 'bills', 'reports']);

        return $result;
    }

    private function candidateStudents(?array $unitIds): Builder
    {
        return Student::query()
            ->with(['schoolClass.educationUnit:id,code,name'])
            ->where('is_active', false)
            ->whereHas('schoolClass', function ($class) use ($unitIds) {
                if (is_array($unitIds)) {
                    $unitIds === []
                        ? $class->whereRaw('1 = 0')
                        : $class->whereIn('education_unit_id', $unitIds);
                }
            })
            ->orderBy('students.id');
    }

    private function analyze(Collection $students, bool $lockBills = false): array
    {
        $studentsById = $students->keyBy('id');
        $billQuery = Bill::query()
            ->withSum(['allocations as allocated_amount' => fn ($allocation) => $allocation->where('amount', '>', 0)], 'amount')
            ->whereIn('student_id', $studentsById->keys())
            ->whereIn('source_type', ['spp', 'fee_type'])
            ->where('status', '!=', 'Dibatalkan')
            ->orderBy('id');

        if ($lockBills) {
            $billQuery->lockForUpdate();
        }

        $postExitBills = $billQuery->get()->filter(function (Bill $bill) use ($studentsById) {
            $student = $studentsById->get($bill->student_id);

            return $student && $this->fallsAfterExit($bill, $student);
        });
        $paidBills = $postExitBills->filter(fn (Bill $bill) => $this->hasPayment($bill));
        $cancelableBills = $postExitBills->reject(fn (Bill $bill) => $this->hasPayment($bill));
        $affectedStudentIds = $postExitBills->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->values();
        $missingExitStudentIds = $students->filter(fn (Student $student) => ! $student->exit_date)
            ->pluck('id')->map(fn ($id) => (int) $id)->values();
        $reviewStudentIds = $paidBills->pluck('student_id')
            ->concat($missingExitStudentIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $affectedStudentIds = $affectedStudentIds->concat($missingExitStudentIds)->unique()->values();
        $affectedStudents = $students->whereIn('id', $affectedStudentIds);
        $previewRows = $postExitBills->take(100)->map(function (Bill $bill) use ($studentsById) {
            $student = $studentsById->get($bill->student_id);
            $hasPayment = $this->hasPayment($bill);
            $period = $bill->year && $bill->month
                ? CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1)->translatedFormat('F Y')
                : ($bill->issue_date?->format('d/m/Y') ?? '-');

            return [
                'student_name' => $student?->name ?? '-',
                'nis' => $student?->nis ?? '-',
                'unit' => $student?->schoolClass?->educationUnit?->code ?? '-',
                'title' => $bill->title,
                'period' => $period,
                'remaining_amount' => (int) $bill->remaining_amount,
                'action' => $hasPayment ? 'Tinjau' : 'Batalkan',
            ];
        });
        $missingExitRows = $students->whereIn('id', $missingExitStudentIds)->map(fn (Student $student) => [
            'student_name' => $student->name,
            'nis' => $student->nis ?? '-',
            'unit' => $student->schoolClass?->educationUnit?->code ?? '-',
            'title' => 'Tanggal keluar belum diisi',
            'period' => '-',
            'remaining_amount' => 0,
            'action' => 'Tinjau',
        ]);
        $previewRows = $previewRows->concat($missingExitRows)->take(100)->values()->all();

        return [
            'alumni_count' => $affectedStudents->count(),
            'cancelable_bill_count' => $cancelableBills->count(),
            'paid_bills_preserved' => $paidBills->count(),
            'review_alumni_count' => $reviewStudentIds->count(),
            'missing_exit_date_count' => $missingExitStudentIds->count(),
            'unit_counts' => $affectedStudents
                ->groupBy(fn (Student $student) => $student->schoolClass?->educationUnit?->code ?? '-')
                ->map->count()
                ->sortKeys()
                ->all(),
            'preview_rows' => $previewRows,
            'preview_truncated' => $postExitBills->count() + $missingExitRows->count() > count($previewRows),
            'student_ids' => $affectedStudentIds->all(),
            'review_student_ids' => $reviewStudentIds->all(),
            'cancelable_bill_ids' => $cancelableBills->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    private function fallsAfterExit(Bill $bill, Student $student): bool
    {
        $exitDate = $student->exit_date?->toImmutable();
        if (! $exitDate) {
            return false;
        }

        if ($bill->year && $bill->month) {
            return CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1)
                ->startOfMonth()
                ->gt($exitDate->startOfMonth());
        }

        return $bill->issue_date?->gt($exitDate) ?? false;
    }

    private function hasPayment(Bill $bill): bool
    {
        return (int) $bill->paid_amount > 0 || (int) $bill->allocated_amount > 0;
    }
}
