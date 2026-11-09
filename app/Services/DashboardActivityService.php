<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardActivityService
{
    private const CHANGE_LABELS = [
        'payments.spp.update' => 'SPP diperbarui',
        'payments.spp.correct' => 'Koreksi SPP',
        'payments.spp.cancel' => 'SPP dibatalkan',
        'payments.other.update' => 'Pembayaran diperbarui',
        'payments.other.correct' => 'Koreksi pembayaran',
        'payments.other.cancel' => 'Pembayaran dibatalkan',
    ];

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     * @return Collection<int, array{kind: string, type: string, date: mixed, amount: ?int, student: string, detail: string}>
     */
    public function recent(
        ?AcademicYear $academicYear,
        ?array $unitIds = null,
        ?array $studentIds = null,
        int $limit = 6,
    ): Collection {
        $academicYearId = $academicYear?->id;
        $sppScope = $this->scopeByAcademicYear(
            $this->scopeByStudentAccess(SppPayment::query(), $unitIds, $studentIds),
            $academicYearId,
        );
        $otherScope = $this->scopeByAcademicYear(
            $this->scopeByStudentAccess(OtherPayment::query(), $unitIds, $studentIds),
            $academicYearId,
        );

        $sppPayments = (clone $sppScope)
            ->with('student.schoolClass.educationUnit')
            ->where('status', 'Diterima')
            ->latest('transaction_at')
            ->limit($limit)
            ->get()
            ->map(fn (SppPayment $payment): array => $this->paymentActivity($payment, 'SPP'));
        $otherPayments = (clone $otherScope)
            ->with(['student.schoolClass.educationUnit', 'feeType'])
            ->where('status', 'Diterima')
            ->latest('transaction_at')
            ->limit($limit)
            ->get()
            ->map(fn (OtherPayment $payment): array => $this->paymentActivity($payment, $payment->feeType?->name ?? 'Lain-lain'));
        $changes = $this->recentPaymentChanges($sppScope, $otherScope, $limit);

        return $sppPayments
            ->concat($otherPayments)
            ->concat($changes)
            ->sortByDesc('date')
            ->take($limit)
            ->values();
    }

    /** @return array{kind: string, type: string, date: mixed, amount: int, student: string, detail: string} */
    private function paymentActivity(SppPayment|OtherPayment $payment, string $type): array
    {
        return [
            'kind' => 'payment',
            'type' => $type,
            'date' => $payment->transaction_at,
            'amount' => (int) $payment->paid_amount,
            'student' => $payment->student?->name ?? 'Siswa tidak ditemukan',
            'detail' => $this->studentContext($payment),
        ];
    }

    /** @return Collection<int, array{kind: string, type: string, date: mixed, amount: null, student: string, detail: string}> */
    private function recentPaymentChanges(Builder $sppScope, Builder $otherScope, int $limit): Collection
    {
        $audits = AuditLog::query()
            ->whereIn('action', array_keys(self::CHANGE_LABELS))
            ->where(function (Builder $query) use ($sppScope, $otherScope): void {
                $query->where(function (Builder $sppQuery) use ($sppScope): void {
                    $sppQuery->where('subject_type', SppPayment::class)
                        ->whereIn('subject_id', (clone $sppScope)->select('spp_payments.id'));
                })->orWhere(function (Builder $otherQuery) use ($otherScope): void {
                    $otherQuery->where('subject_type', OtherPayment::class)
                        ->whereIn('subject_id', (clone $otherScope)->select('other_payments.id'));
                });
            })
            ->latest()
            ->limit($limit)
            ->get();

        $sppPayments = SppPayment::with('student.schoolClass.educationUnit')
            ->whereIn('id', $audits->where('subject_type', SppPayment::class)->pluck('subject_id'))
            ->get()
            ->keyBy('id');
        $otherPayments = OtherPayment::with('student.schoolClass.educationUnit')
            ->whereIn('id', $audits->where('subject_type', OtherPayment::class)->pluck('subject_id'))
            ->get()
            ->keyBy('id');

        return $audits->map(function (AuditLog $audit) use ($sppPayments, $otherPayments): array {
            $payment = $audit->subject_type === SppPayment::class
                ? $sppPayments->get($audit->subject_id)
                : $otherPayments->get($audit->subject_id);

            return [
                'kind' => 'change',
                'type' => self::CHANGE_LABELS[$audit->action],
                'date' => $audit->created_at,
                'amount' => null,
                'student' => $payment?->student?->name ?? 'Siswa tidak ditemukan',
                'detail' => $payment ? $this->studentContext($payment) : 'Data pembayaran',
            ];
        });
    }

    private function studentContext(SppPayment|OtherPayment $payment): string
    {
        return ($payment->student?->schoolClass?->educationUnit?->name ?? '-')
            .' · '.($payment->student?->schoolClass?->name ?? '-');
    }

    /**
     * @param  array<int, int>|null  $unitIds
     * @param  array<int, int>|null  $studentIds
     */
    private function scopeByStudentAccess(Builder $query, ?array $unitIds, ?array $studentIds): Builder
    {
        if (is_array($studentIds)) {
            return $studentIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('student_id', $studentIds);
        }

        if (is_array($unitIds)) {
            return $unitIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('student.schoolClass', fn (Builder $class) => $class->whereIn('education_unit_id', $unitIds));
        }

        return $query;
    }

    private function scopeByAcademicYear(Builder $query, ?int $academicYearId): Builder
    {
        return $query->when($academicYearId, fn (Builder $paymentQuery, int $yearId) => $paymentQuery
            ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('academic_year_id', $yearId)));
    }
}
