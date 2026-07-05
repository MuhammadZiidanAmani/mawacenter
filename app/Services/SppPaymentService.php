<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SppPaymentService
{
    private const DEFAULT_BILLING_START_DATE = '2025-08-01';

    private const JULY_INCLUDED_IN_REGISTRATION_UNITS = ['MTS', 'MA'];

    public function __construct(
        private ChargeCalculator $calculator,
        private BillService $bills,
    ) {}

    public function quote(Student $student, int $year, array $months): array
    {
        $this->ensureSequentialMonths($student, $year, $months);

        return $this->calculateSelection($student, $year, $months);
    }

    public function quoteByMonthCount(Student $student, int $monthCount): array
    {
        $periods = $this->outstandingPeriods($student, $monthCount);

        if (count($periods) < $monthCount) {
            throw ValidationException::withMessages([
                'month_count' => 'Jumlah bulan melebihi tagihan SPP yang tersedia.',
            ]);
        }

        return $this->calculatePeriodSelection($student, $periods);
    }

    public function quoteNextMonths(Student $student, int $monthCount = 12): array
    {
        $current = CarbonImmutable::instance(now())->startOfMonth();
        $arrears = collect($this->outstandingPeriods($student))
            ->first(fn (array $period) => CarbonImmutable::create($period['year'], $period['month'], 1)->lt($current));

        if ($arrears) {
            throw ValidationException::withMessages([
                'payment_modes' => 'Pembayaran SPP 12 bulan tersedia setelah tunggakan sebelum bulan berjalan dilunasi.',
            ]);
        }

        $start = $current;
        while ($start->lt($current->addMonths(24))) {
            if ($this->periodIsPayable($student, $start->year, $start->month)) {
                $charge = $this->calculator->calculateSppMonth($student, $start->year, $start->month);
                $paid = (int) SppPaymentItem::where('student_id', $student->id)
                    ->where('year', $start->year)
                    ->where('month', $start->month)
                    ->sum('paid_amount');
                if ($charge['final_amount'] > $paid) {
                    break;
                }
            }

            $start = $start->addMonth();
        }

        $periods = [];
        for ($period = $start; $period->lt($start->addMonths($monthCount)); $period = $period->addMonth()) {
            if ($this->periodIsPayable($student, $period->year, $period->month)) {
                $periods[] = ['year' => $period->year, 'month' => $period->month];
            }
        }

        if ($periods === []) {
            throw ValidationException::withMessages([
                'payment_modes' => 'Tagihan SPP 12 bulan belum tersedia untuk siswa ini.',
            ]);
        }

        $quote = $this->calculatePeriodSelection($student, $periods);
        $quote['period_start'] = ['year' => $start->year, 'month' => $start->month];
        $end = $start->addMonths($monthCount - 1);
        $quote['period_end'] = ['year' => $end->year, 'month' => $end->month];

        return $quote;
    }

    public function paymentPlan(Student $student): array
    {
        $periods = $this->outstandingPeriods($student);
        $oldest = $periods[0] ?? null;
        $current = now()->startOfMonth();
        $defaultCount = collect($periods)
            ->filter(fn (array $period) => CarbonImmutable::create($period['year'], $period['month'], 1)->lte($current))
            ->count();

        return [
            'oldest_outstanding' => $oldest ? [
                'year' => $oldest['year'],
                'month' => $oldest['month'],
                'month_name' => $this->monthName($oldest['month']),
                'payment_status' => $oldest['payment_status'],
            ] : null,
            'default_month_count' => $defaultCount,
            'max_month_count' => count($periods),
            'periods' => array_map(fn (array $period) => [
                'year' => $period['year'],
                'month' => $period['month'],
                'month_name' => $this->monthName($period['month']),
                'remaining_amount' => $period['remaining_amount'],
                'payment_status' => $period['payment_status'],
            ], $periods),
        ];
    }

    public function outstandingSummaryUntilCurrent(Student $student): array
    {
        $end = now()->startOfMonth();
        if ($student->exit_date && $student->exit_date->lt($end)) {
            $end = $student->exit_date->startOfMonth();
        }

        $periods = $this->outstandingPeriods($student);

        return [
            'remaining_amount' => array_sum(array_column($periods, 'remaining_amount')),
            'label' => $this->monthName((int) $end->month).' '.$end->year,
        ];
    }

    public function monthStatuses(Student $student, int $year): array
    {
        $selection = $this->calculateSelection($student, $year, range(1, 12));
        $items = collect($selection['items'])->map(function (array $item) use ($student) {
            $periodIsApplicable = $this->periodIsApplicable($student, $item['year'], $item['month']);
            $includedInRegistration = $this->sppIsIncludedInRegistration($student, $item['year'], $item['month']);
            $item['applicable'] = $periodIsApplicable && ! $includedInRegistration;
            $item['included_in_registration'] = $includedInRegistration;
            $item['status_label'] = null;
            if ($includedInRegistration) {
                $item['original_amount'] = 0;
                $item['discount_amount'] = 0;
                $item['total_amount'] = 0;
                $item['paid_amount'] = 0;
                $item['remaining_amount'] = 0;
                $item['payment_status'] = 'Termasuk Daftar Ulang';
                $item['status_label'] = 'Termasuk Daftar Ulang';
            } elseif (! $periodIsApplicable) {
                $item['remaining_amount'] = 0;
                $item['payment_status'] = 'Tidak Berlaku';
                $item['status_label'] = 'Belum Aktif';
            }

            return $item;
        });
        $firstPayable = $items->first(fn (array $item) => $item['applicable'] && $item['remaining_amount'] > 0);

        return [
            'first_payable_month' => $firstPayable['month'] ?? null,
            'oldest_outstanding' => $this->oldestOutstandingPeriod($student),
            'months' => $items->values()->all(),
        ];
    }

    public function oldestOutstandingPeriod(Student $student): ?array
    {
        $start = $this->billingStart($student);
        $end = now()->startOfMonth();
        if ($student->exit_date && $student->exit_date->lt($end)) {
            $end = $student->exit_date->startOfMonth();
        }

        for ($period = $start; $period->lte($end); $period = $period->addMonth()) {
            if (! $this->periodIsPayable($student, $period->year, $period->month)) {
                continue;
            }

            $charge = $this->calculator->calculateSppMonth($student, $period->year, $period->month);
            if ($charge['final_amount'] < 1) {
                continue;
            }
            $paid = (int) SppPaymentItem::where('student_id', $student->id)
                ->where('year', $period->year)
                ->where('month', $period->month)
                ->sum('paid_amount');
            if ($paid < $charge['final_amount']) {
                return [
                    'year' => $period->year,
                    'month' => $period->month,
                    'month_name' => $this->monthName($period->month),
                    'payment_status' => $paid > 0 ? 'Belum Lunas' : 'Belum Dibayar',
                ];
            }
        }

        return null;
    }

    public function record(Student $student, array $data): SppPayment
    {
        return DB::transaction(function () use ($data, $student) {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            if (isset($data['advance_month_count'])) {
                $quote = $this->quoteNextMonths($student, (int) $data['advance_month_count']);
            } elseif (isset($data['month_count'])) {
                $quote = $this->quoteByMonthCount($student, (int) $data['month_count']);
            } else {
                $year = (int) $data['year'];
                $this->ensureSequentialMonths($student, $year, $data['months']);
                $quote = $this->calculateSelection($student, $year, $data['months']);
            }

            if ($data['paid_amount'] > $quote['remaining_amount']) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa tagihan Rp '.number_format($quote['remaining_amount'], 0, ',', '.').'.',
                ]);
            }

            $remainingPayment = (int) $data['paid_amount'];
            $remainingAfter = $quote['remaining_amount'] - $remainingPayment;
            $payment = SppPayment::create([
                'student_id' => $student->id,
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'transfer_proof_path' => $data['transfer_proof_path'] ?? null,
                'status' => $data['status'],
                'operator_name' => $data['operator_name'] ?? null,
                'import_source' => $data['import_source'] ?? null,
                'import_key' => $data['import_key'] ?? null,
                'original_amount' => $quote['original_amount'],
                'discount_amount' => $quote['discount_amount'],
                'total_amount' => $quote['total_amount'],
                'paid_amount' => $data['paid_amount'],
                'remaining_amount' => $remainingAfter,
                'payment_status' => $remainingAfter === 0 ? 'Lunas' : 'Belum Lunas',
            ]);

            foreach ($quote['items'] as $item) {
                if ($remainingPayment < 1 || $item['remaining_amount'] < 1) {
                    continue;
                }

                $allocated = min($remainingPayment, $item['remaining_amount']);
                $itemRemaining = $item['remaining_amount'] - $allocated;
                $payment->items()->create([
                    'student_id' => $student->id,
                    'year' => $item['year'],
                    'month' => $item['month'],
                    'original_amount' => $item['original_amount'],
                    'discount_amount' => $item['discount_amount'],
                    'total_amount' => $item['total_amount'],
                    'paid_amount' => $allocated,
                    'remaining_amount' => $itemRemaining,
                    'payment_status' => $itemRemaining === 0 ? 'Lunas' : 'Belum Lunas',
                ]);
                $remainingPayment -= $allocated;
            }

            $this->bills->syncSppPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'items']));

            return $payment;
        });
    }

    public function updateMetadata(SppPayment $payment, array $data): SppPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'transaction_at' => $data['transaction_date'].' '.$data['transaction_time'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
            ]);

            if (array_key_exists('paid_amount', $data)) {
                $this->reallocatePaidAmount($payment->refresh(), (int) $data['paid_amount']);
            }

            $payment = $payment->refresh();
            $this->bills->syncSppPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'items']));

            return $payment;
        });
    }

    public function correctPaidAmount(SppPayment $payment, array $data): SppPayment
    {
        $newPaidAmount = (int) $data['new_paid_amount'];
        $oldPaidAmount = (int) $payment->paid_amount;

        if ($newPaidAmount >= $oldPaidAmount) {
            throw ValidationException::withMessages([
                'new_paid_amount' => 'Nominal koreksi harus lebih kecil dari nominal sebelumnya. Untuk menambah pembayaran, buat transaksi pembayaran baru.',
            ]);
        }

        $payment = DB::transaction(function () use ($payment, $data, $newPaidAmount, $oldPaidAmount) {
            $this->reallocatePaidAmount($payment, $newPaidAmount);
            $payment->corrections()->create([
                'old_paid_amount' => $oldPaidAmount,
                'new_paid_amount' => $newPaidAmount,
                'refund_amount' => $oldPaidAmount - $newPaidAmount,
                'reason' => $data['reason'],
            ]);

            return $payment->refresh();
        });

        $this->bills->syncSppPayment($payment->load(['student.academicYear', 'student.schoolClass.educationUnit', 'items']));

        return $payment;
    }

    private function reallocatePaidAmount(SppPayment $payment, int $newPaidAmount): void
    {
        $items = $payment->items()->orderBy('year')->orderBy('month')->get();
        $maxPayable = $items->sum(function ($item) use ($payment) {
            $paidByOtherTransactions = (int) SppPaymentItem::where('student_id', $payment->student_id)
                ->where('year', $item->year)
                ->where('month', $item->month)
                ->where('spp_payment_id', '!=', $payment->id)
                ->sum('paid_amount');

            return max(0, (int) $item->total_amount - $paidByOtherTransactions);
        });

        if ($newPaidAmount > $maxPayable) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal dibayar tidak boleh melebihi sisa ruang pembayaran transaksi ini Rp '.number_format($maxPayable, 0, ',', '.').'.',
            ]);
        }

        $remainingAllocation = $newPaidAmount;
        $paymentRemainingAmount = 0;
        foreach ($items as $item) {
            $paidByOtherTransactions = (int) SppPaymentItem::where('student_id', $payment->student_id)
                ->where('year', $item->year)
                ->where('month', $item->month)
                ->where('spp_payment_id', '!=', $payment->id)
                ->sum('paid_amount');
            $availableForItem = max(0, (int) $item->total_amount - $paidByOtherTransactions);
            $allocated = min($remainingAllocation, $availableForItem);
            $remaining = max(0, (int) $item->total_amount - $paidByOtherTransactions - $allocated);
            $paymentRemainingAmount += $remaining;

            $item->update([
                'paid_amount' => $allocated,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining === 0 ? 'Lunas' : ($allocated > 0 ? 'Belum Lunas' : 'Belum Dibayar'),
            ]);
            $remainingAllocation -= $allocated;
        }

        $payment->update([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $paymentRemainingAmount,
            'payment_status' => $paymentRemainingAmount === 0 ? 'Lunas' : 'Belum Lunas',
        ]);
    }

    public function delete(SppPayment $payment): void
    {
        if ($payment->corrections()->exists()) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi yang sudah memiliki histori koreksi tidak dapat dihapus.',
            ]);
        }

        DB::transaction(function () use ($payment) {
            $this->bills->removePayment('spp', $payment->id);
            $payment->delete();
        });
    }

    private function calculateSelection(Student $student, int $year, array $months): array
    {
        $months = array_values(array_unique(array_map('intval', $months)));
        sort($months);

        $items = [];
        foreach ($months as $month) {
            $charge = $this->calculator->calculateSppMonth($student, $year, $month);
            if ($charge['original_amount'] < 1) {
                throw ValidationException::withMessages(['student_id' => 'Kategori pembayaran SPP aktif untuk siswa belum tersedia.']);
            }

            $paidAmount = (int) SppPaymentItem::where('student_id', $student->id)
                ->where('year', $year)
                ->where('month', $month)
                ->sum('paid_amount');
            $remainingAmount = max(0, $charge['final_amount'] - $paidAmount);
            $items[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $this->monthName($month),
                'original_amount' => $charge['original_amount'],
                'discount_amount' => $charge['discount_amount'],
                'total_amount' => $charge['final_amount'],
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $remainingAmount === 0 ? 'Lunas' : ($paidAmount > 0 ? 'Belum Lunas' : 'Belum Dibayar'),
            ];
        }

        return [
            'original_amount' => array_sum(array_column($items, 'original_amount')),
            'discount_amount' => array_sum(array_column($items, 'discount_amount')),
            'total_amount' => array_sum(array_column($items, 'total_amount')),
            'paid_amount' => array_sum(array_column($items, 'paid_amount')),
            'remaining_amount' => array_sum(array_column($items, 'remaining_amount')),
            'payment_status' => array_sum(array_column($items, 'remaining_amount')) === 0 ? 'Lunas' : 'Belum Lunas',
            'items' => $items,
        ];
    }

    private function calculatePeriodSelection(Student $student, array $periods): array
    {
        $items = [];
        foreach ($periods as $period) {
            $year = (int) $period['year'];
            $month = (int) $period['month'];
            $charge = $this->calculator->calculateSppMonth($student, $year, $month);
            if ($charge['original_amount'] < 1) {
                throw ValidationException::withMessages(['student_id' => 'Kategori pembayaran SPP aktif untuk siswa belum tersedia.']);
            }

            $paidAmount = (int) SppPaymentItem::where('student_id', $student->id)
                ->where('year', $year)
                ->where('month', $month)
                ->sum('paid_amount');
            $remainingAmount = max(0, $charge['final_amount'] - $paidAmount);
            if ($remainingAmount < 1) {
                continue;
            }

            $items[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $this->monthName($month),
                'original_amount' => $charge['original_amount'],
                'discount_amount' => $charge['discount_amount'],
                'total_amount' => $charge['final_amount'],
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paidAmount > 0 ? 'Belum Lunas' : 'Belum Dibayar',
            ];
        }

        return [
            'original_amount' => array_sum(array_column($items, 'original_amount')),
            'discount_amount' => array_sum(array_column($items, 'discount_amount')),
            'total_amount' => array_sum(array_column($items, 'total_amount')),
            'paid_amount' => array_sum(array_column($items, 'paid_amount')),
            'remaining_amount' => array_sum(array_column($items, 'remaining_amount')),
            'payment_status' => array_sum(array_column($items, 'remaining_amount')) === 0 ? 'Lunas' : 'Belum Lunas',
            'items' => $items,
        ];
    }

    private function outstandingPeriods(Student $student, ?int $limit = null): array
    {
        $start = $this->billingStart($student);
        $end = now()->startOfMonth();
        if ($student->exit_date && $student->exit_date->lt($end)) {
            $end = $student->exit_date->startOfMonth();
        }

        $periods = [];
        for ($period = $start; $period->lte($end); $period = $period->addMonth()) {
            if (! $this->periodIsPayable($student, $period->year, $period->month)) {
                continue;
            }

            $charge = $this->calculator->calculateSppMonth($student, $period->year, $period->month);
            if ($charge['final_amount'] < 1) {
                continue;
            }
            $paid = (int) SppPaymentItem::where('student_id', $student->id)
                ->where('year', $period->year)
                ->where('month', $period->month)
                ->sum('paid_amount');
            $remaining = max(0, $charge['final_amount'] - $paid);
            if ($remaining < 1) {
                continue;
            }

            $periods[] = [
                'year' => $period->year,
                'month' => $period->month,
                'remaining_amount' => $remaining,
                'payment_status' => $paid > 0 ? 'Belum Lunas' : 'Belum Dibayar',
            ];

            if ($limit !== null && count($periods) >= $limit) {
                break;
            }
        }

        return $periods;
    }

    private function ensureSequentialMonths(Student $student, int $year, array $months): void
    {
        $selectedMonths = array_values(array_unique(array_map('intval', $months)));
        sort($selectedMonths);
        $yearSelection = $this->calculateSelection($student, $year, range(1, 12));
        $inapplicableMonth = collect($selectedMonths)->first(fn (int $month) => ! $this->periodIsPayable($student, $year, $month));
        if ($inapplicableMonth) {
            $message = $this->sppIsIncludedInRegistration($student, $year, $inapplicableMonth)
                ? 'SPP bulan Juli '.$year.' untuk unit MTs/MA sudah termasuk Daftar Ulang.'
                : 'SPP bulan '.$this->monthName($inapplicableMonth).' '.$year.' belum termasuk periode tagihan siswa.';

            throw ValidationException::withMessages([
                'months' => $message,
            ]);
        }
        $selectedItems = array_filter(
            $yearSelection['items'],
            fn (array $item) => in_array($item['month'], $selectedMonths, true),
        );
        $paidItem = collect($selectedItems)->firstWhere('remaining_amount', 0);

        if ($paidItem) {
            throw ValidationException::withMessages([
                'months' => 'SPP bulan '.$paidItem['month_name'].' '.$year.' sudah lunas dan tidak dapat dibayar kembali.',
            ]);
        }

        $oldestOutstanding = $this->oldestOutstandingPeriod($student);
        $firstSelectedMonth = $selectedMonths[0] ?? null;
        if ($oldestOutstanding && $firstSelectedMonth) {
            $firstSelectedPeriod = CarbonImmutable::create($year, $firstSelectedMonth, 1);
            $oldestPeriod = CarbonImmutable::create($oldestOutstanding['year'], $oldestOutstanding['month'], 1);

            if (! $firstSelectedPeriod->equalTo($oldestPeriod)) {
                throw ValidationException::withMessages([
                    'months' => 'Pembayaran harus dimulai dari bulan '.$oldestOutstanding['month_name'].' '.$oldestOutstanding['year'].' dan dipilih secara berurutan.',
                ]);
            }
        }

        $payableMonths = array_values(array_map(
            fn (array $item) => $item['month'],
            array_filter($yearSelection['items'], fn (array $item) => $item['remaining_amount'] > 0
                && $this->periodIsPayable($student, $item['year'], $item['month'])),
        ));
        $expectedMonths = array_slice($payableMonths, 0, count($selectedMonths));

        if ($selectedMonths !== $expectedMonths) {
            $firstMonth = $payableMonths[0] ?? null;
            $message = $firstMonth
                ? 'Pembayaran harus dimulai dari bulan '.$this->monthName($firstMonth).' dan dipilih secara berurutan.'
                : 'Seluruh pembayaran SPP pada tahun ini sudah lunas.';
            throw ValidationException::withMessages(['months' => $message]);
        }
    }

    private function billingStart(Student $student): CarbonImmutable
    {
        if ($student->billing_start_date) {
            return CarbonImmutable::parse($student->billing_start_date)->startOfMonth();
        }

        $student->loadMissing('academicYear');
        $academicStart = $this->sppStartForAcademicYear($student->academicYear);
        $entryStart = $student->entry_date
            ? CarbonImmutable::parse($student->entry_date)->startOfMonth()
            : null;

        if ($entryStart && $entryStart->lessThan($academicStart) && $entryStart->year < $academicStart->year) {
            return CarbonImmutable::create($academicStart->year, 1, 1)->startOfMonth();
        }

        if ($entryStart && $entryStart->greaterThan($academicStart)) {
            return $entryStart;
        }

        return $academicStart;
    }

    private function sppStartForAcademicYear(?AcademicYear $academicYear): CarbonImmutable
    {
        if ($academicYear && preg_match('/^(\d{4})\/\d{4}$/', (string) $academicYear->name, $matches)) {
            return CarbonImmutable::create((int) $matches[1], 8, 1)->startOfMonth();
        }

        if ($academicYear?->start_date) {
            return CarbonImmutable::parse($academicYear->start_date)->startOfMonth()->addMonth();
        }

        return CarbonImmutable::parse(self::DEFAULT_BILLING_START_DATE)->startOfMonth();
    }

    private function periodIsApplicable(Student $student, int $year, int $month): bool
    {
        $period = CarbonImmutable::create($year, $month, 1);

        return $this->billingStart($student)->lte($period)
            && (! $student->exit_date || $student->exit_date->gte($period->startOfMonth()));
    }

    private function periodIsPayable(Student $student, int $year, int $month): bool
    {
        return $this->periodIsApplicable($student, $year, $month)
            && ! $this->sppIsIncludedInRegistration($student, $year, $month);
    }

    private function sppIsIncludedInRegistration(Student $student, int $year, int $month): bool
    {
        if ($month !== 7) {
            return false;
        }

        $student->loadMissing('schoolClass.educationUnit');
        $unit = $student->schoolClass?->educationUnit;
        $unitCode = strtoupper(trim((string) $unit?->code));
        $unitName = strtoupper(trim((string) $unit?->name));

        return in_array($unitCode, self::JULY_INCLUDED_IN_REGISTRATION_UNITS, true)
            || str_contains($unitName, 'TSANAWIYAH')
            || str_contains($unitName, 'ALIYAH');
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
    }
}
