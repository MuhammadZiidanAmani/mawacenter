<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\Student;
use App\Support\ClassLevel;
use App\Support\PerformanceCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentIntakeStatusCleanupService
{
    private const ENTRY_LEVELS = [
        'MI' => 1,
        'MTS' => 7,
        'MA' => 10,
    ];

    private const FLEXIBLE_ENTRY_UNITS = ['RA', 'PAUD', 'PONPES', 'PONDOK'];

    public function preview(?array $unitIds = null): array
    {
        return $this->analyze($this->studentQuery($unitIds)->get());
    }

    public function apply(?array $unitIds = null): array
    {
        $result = DB::transaction(function () use ($unitIds) {
            $students = $this->studentQuery($unitIds)->lockForUpdate()->get();
            $analysis = $this->analyze($students);
            $changes = collect($analysis['changes']);
            $updatedIds = collect();

            foreach ($changes as $change) {
                $query = Student::query()
                    ->whereKey($change['student_id'])
                    ->where('is_active', true);
                $change['from'] === Student::INTAKE_RETURNING
                    ? $query->where(fn ($status) => $status->where('intake_status', Student::INTAKE_RETURNING)->orWhereNull('intake_status'))
                    : $query->where('intake_status', $change['from']);

                $updated = $query->update([
                    'intake_status' => $change['to'],
                    'updated_at' => now(),
                ]);
                if ($updated === 1) {
                    $updatedIds->push((int) $change['student_id']);
                }
            }

            $appliedChanges = $changes
                ->whereIn('student_id', $updatedIds)
                ->values();

            return array_merge($analysis, [
                'changes' => $appliedChanges->all(),
                'updated_students' => $updatedIds->count(),
                'student_ids' => $updatedIds->values()->all(),
            ]);
        });

        PerformanceCache::invalidate(['dashboard', 'bills', 'reports']);

        return $result;
    }

    private function studentQuery(?array $unitIds): Builder
    {
        return Student::query()
            ->with([
                'academicYear:id,name,start_date,end_date',
                'schoolClass:id,education_unit_id,name,level',
                'schoolClass.educationUnit:id,code,name',
            ])
            ->whereHas('schoolClass.educationUnit', function ($query) use ($unitIds) {
                if (is_array($unitIds)) {
                    $unitIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('education_units.id', $unitIds);
                }
            })
            ->orderBy('students.id');
    }

    private function analyze(Collection $students): array
    {
        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->values();
        $promotionIds = $this->promotionStudentIds($studentIds);
        $earliestHistory = $this->earliestFinancialHistory($studentIds);
        $rows = $students->map(function (Student $student) use ($promotionIds, $earliestHistory) {
            return $this->classify(
                $student,
                $promotionIds->contains((int) $student->id),
                $earliestHistory->get((int) $student->id),
            );
        });

        $changes = $rows
            ->where('action', 'Perbaiki otomatis')
            ->map(fn (array $row) => [
                'student_id' => $row['student_id'],
                'from' => $row['current_value'],
                'to' => $row['suggested_value'],
                'reason' => $row['reason'],
            ])
            ->values()
            ->all();
        $reviewRows = $rows->where('action', 'Tinjau manual');

        $previewRows = $reviewRows
            ->sortBy('student_id')
            ->take(200)
            ->values()
            ->all();

        return [
            'scanned_count' => $students->count(),
            'candidate_count' => count($changes),
            'review_count' => $reviewRows->count(),
            'unchanged_count' => $rows->where('action', 'Tidak berubah')->count(),
            'inactive_count' => $rows->where('reason_code', 'inactive')->count(),
            'new_count' => $rows->where('current_value', Student::INTAKE_NEW)->count(),
            'returning_count' => $rows->where('current_value', Student::INTAKE_RETURNING)->count(),
            'transfer_count' => $rows->where('current_value', Student::INTAKE_TRANSFER)->count(),
            'changes' => $changes,
            'student_ids' => collect($changes)->pluck('student_id')->all(),
            'unit_counts' => $rows
                ->where('action', 'Perbaiki otomatis')
                ->groupBy('unit')
                ->map->count()
                ->sortKeys()
                ->all(),
            'preview_rows' => $previewRows,
            'preview_truncated' => $reviewRows->count() > count($previewRows),
        ];
    }

    private function classify(Student $student, bool $wasPromoted, ?CarbonImmutable $earliestHistory): array
    {
        $current = $student->intake_status ?: Student::INTAKE_RETURNING;
        $unit = strtoupper(trim((string) $student->schoolClass?->educationUnit?->code));
        $classLevel = ClassLevel::key($student->schoolClass?->level ?: $student->schoolClass?->name);
        $academicStart = $this->academicStart($student);
        $admissionStart = $academicStart?->startOfYear();
        $entryDate = $student->entry_date ? CarbonImmutable::instance($student->entry_date)->startOfDay() : null;
        $suggested = $current;
        $action = 'Tidak berubah';
        $reasonCode = 'consistent';
        $reason = 'Status saat ini konsisten dengan data yang tersedia.';

        if (! $student->is_active) {
            $action = 'Tinjau manual';
            $reasonCode = 'inactive';
            $reason = 'Data nonaktif atau alumni tidak diubah otomatis.';
        } elseif ($current === Student::INTAKE_TRANSFER) {
            $action = 'Tinjau manual';
            $reasonCode = 'transfer';
            $reason = 'Status Pindahan dipertahankan karena memerlukan konteks penerimaan siswa.';
        } elseif (! $academicStart || ! $entryDate || ! $student->schoolClass) {
            $action = 'Tinjau manual';
            $reasonCode = 'incomplete';
            $reason = 'Tahun pelajaran, tanggal masuk, atau kelas belum lengkap.';
        } else {
            $returningEvidence = $wasPromoted
                || $entryDate->lt($admissionStart)
                || ($earliestHistory && $earliestHistory->lt($admissionStart));

            if ($returningEvidence) {
                $suggested = Student::INTAKE_RETURNING;
                $reasonCode = 'returning-evidence';
                $reason = $wasPromoted
                    ? 'Riwayat naik kelas menunjukkan siswa melanjutkan dari tahun sebelumnya.'
                    : 'Tanggal masuk atau riwayat kewajiban/pembayaran sudah ada sebelum masa penerimaan tahun ini.';
                $action = $current === $suggested ? 'Tidak berubah' : 'Perbaiki otomatis';
            } elseif ($entryDate->gte($admissionStart) && $entryDate->lte($this->academicEnd($student, $academicStart))) {
                $expectedLevel = self::ENTRY_LEVELS[$unit] ?? null;
                $flexibleEntry = in_array($unit, self::FLEXIBLE_ENTRY_UNITS, true);

                if ($flexibleEntry || ($expectedLevel !== null && (int) $classLevel === $expectedLevel)) {
                    $suggested = Student::INTAKE_NEW;
                    $reasonCode = 'new-evidence';
                    $reason = $flexibleEntry
                        ? 'Tanggal masuk berada pada masa penerimaan tahun pelajaran ini.'
                        : "Tanggal masuk berada pada masa penerimaan dan kelas {$classLevel} adalah tingkat awal unit {$unit}.";
                    $action = $current === $suggested ? 'Tidak berubah' : 'Perbaiki otomatis';
                } else {
                    $action = 'Tinjau manual';
                    $reasonCode = 'possible-transfer';
                    $reason = 'Masuk pada tahun ini di tingkat lanjutan; tentukan Siswa Baru atau Pindahan secara manual.';
                }
            } else {
                $action = 'Tinjau manual';
                $reasonCode = 'outside-window';
                $reason = 'Tanggal masuk berada di luar rentang tahun pelajaran yang dipilih.';
            }
        }

        return [
            'student_id' => (int) $student->id,
            'nis' => (string) $student->nis,
            'name' => (string) $student->name,
            'unit' => $unit ?: '-',
            'class' => $student->schoolClass?->name ?? '-',
            'academic_year' => $student->academicYear?->name ?? '-',
            'entry_date' => $entryDate?->format('d/m/Y') ?? '-',
            'current_value' => $current,
            'current_label' => Student::INTAKE_LABELS[$current] ?? 'Belum ditentukan',
            'suggested_value' => $suggested,
            'suggested_label' => Student::INTAKE_LABELS[$suggested] ?? 'Tinjau',
            'action' => $action,
            'reason_code' => $reasonCode,
            'reason' => $reason,
        ];
    }

    private function academicStart(Student $student): ?CarbonImmutable
    {
        if ($student->academicYear?->start_date) {
            return CarbonImmutable::instance($student->academicYear->start_date)->startOfDay();
        }

        if (preg_match('/(20\d{2})/', (string) $student->academicYear?->name, $matches)) {
            return CarbonImmutable::create((int) $matches[1], 7, 1)->startOfDay();
        }

        return null;
    }

    private function academicEnd(Student $student, CarbonImmutable $academicStart): CarbonImmutable
    {
        return $student->academicYear?->end_date
            ? CarbonImmutable::instance($student->academicYear->end_date)->endOfDay()
            : CarbonImmutable::create($academicStart->year + 1, 6, 30)->endOfDay();
    }

    private function promotionStudentIds(Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return AuditLog::query()
            ->where('action', 'students.promote_class')
            ->get(['student_ids'])
            ->flatMap(fn (AuditLog $log) => $log->student_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($studentIds)
            ->unique()
            ->values();
    }

    private function earliestFinancialHistory(Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $dates = collect();

        Bill::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'Dibatalkan')
            ->get(['student_id', 'issue_date', 'year', 'month'])
            ->each(function (Bill $bill) use ($dates) {
                $date = $bill->issue_date
                    ? CarbonImmutable::instance($bill->issue_date)
                    : ($bill->year && $bill->month ? CarbonImmutable::create((int) $bill->year, (int) $bill->month, 1) : null);
                $this->keepEarliest($dates, (int) $bill->student_id, $date);
            });

        SppPayment::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Diterima')
            ->get(['student_id', 'transaction_at'])
            ->each(fn (SppPayment $payment) => $this->keepEarliest(
                $dates,
                (int) $payment->student_id,
                $payment->transaction_at ? CarbonImmutable::instance($payment->transaction_at) : null,
            ));

        OtherPayment::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Diterima')
            ->get(['student_id', 'transaction_at'])
            ->each(fn (OtherPayment $payment) => $this->keepEarliest(
                $dates,
                (int) $payment->student_id,
                $payment->transaction_at ? CarbonImmutable::instance($payment->transaction_at) : null,
            ));

        return $dates;
    }

    private function keepEarliest(Collection $dates, int $studentId, ?CarbonImmutable $date): void
    {
        if (! $date) {
            return;
        }

        $current = $dates->get($studentId);
        if (! $current || $date->lt($current)) {
            $dates->put($studentId, $date->startOfDay());
        }
    }
}
