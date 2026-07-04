<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Bill;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use App\Models\Student;
use App\Services\LaundryPaymentService;
use App\Services\OtherPaymentService;
use App\Services\SppPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): View
    {
        $search = trim($request->string('search')->value());
        $selectedStudentId = (int) $request->integer('student_id');
        $people = collect();
        $paymentHistory = collect();

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
                $registrations = Student::with(['academicYear', 'schoolClass.educationUnit'])
                    ->where('is_active', true)
                    ->where(fn ($query) => $query
                        ->whereIn('id', $identityIds)
                        ->orWhereIn('identity_student_id', $identityIds))
                    ->orderBy('name')
                    ->get();
                $feeTypes = FeeType::where('is_active', true)->get();

                $registrations->each(function (Student $student) use ($feeTypes, $sppPayments, $otherPayments, $laundryPayments) {
                    $student->setAttribute('payment_options', $this->paymentOptions($student, $feeTypes, $sppPayments, $otherPayments, $laundryPayments));
                    $student->setAttribute('optional_payment_options', $this->optionalPaymentOptions($student, $feeTypes, $otherPayments, $laundryPayments));
                });

                $people = $registrations->groupBy(fn (Student $student) => $student->identity_student_id ?: $student->id);
            }
        }

        if ($selectedStudentId > 0) {
            $selectedStudent = Student::where('is_active', true)->find($selectedStudentId);
            if ($selectedStudent) {
                $identityId = $selectedStudent->identity_student_id ?: $selectedStudent->id;
                $studentIds = Student::where('is_active', true)
                    ->where(fn ($query) => $query
                        ->where('id', $identityId)
                        ->orWhere('identity_student_id', $identityId))
                    ->pluck('id');

                $sppHistory = SppPayment::with(['student.schoolClass.educationUnit', 'items'])
                    ->whereIn('student_id', $studentIds)
                    ->latest('transaction_at')
                    ->limit(10)
                    ->get()
                    ->map(fn (SppPayment $payment) => [
                        'type' => 'spp',
                        'id' => $payment->id,
                        'title' => 'SPP '.$payment->student?->schoolClass?->educationUnit?->code,
                        'detail' => $this->sppHistoryPeriod($payment),
                        'student' => $payment->student?->name,
                        'date' => $payment->transaction_at->format('d/m/Y H.i').' WIB',
                        'timestamp' => $payment->transaction_at->timestamp,
                        'method' => $payment->payment_method,
                        'status' => $payment->status,
                        'amount' => $payment->paid_amount,
                        'amount_label' => number_format($payment->paid_amount, 0, ',', '.').',-',
                        'receipt_url' => route('finance.spp.receipt', $payment),
                        'download_url' => route('finance.spp.receipt.download', $payment),
                        'delete_url' => route('finance.spp.destroy', $payment),
                    ]);

                $otherHistory = OtherPayment::with(['student.schoolClass.educationUnit', 'feeType', 'items'])
                    ->whereIn('student_id', $studentIds)
                    ->latest('transaction_at')
                    ->limit(10)
                    ->get()
                    ->map(fn (OtherPayment $payment) => [
                        'type' => 'other',
                        'id' => $payment->id,
                        'title' => $payment->feeType?->name ?? 'Pembayaran Lainnya',
                        'detail' => $this->otherHistoryPeriod($payment),
                        'student' => $payment->student?->name,
                        'date' => $payment->transaction_at->format('d/m/Y H.i').' WIB',
                        'timestamp' => $payment->transaction_at->timestamp,
                        'method' => $payment->payment_method,
                        'status' => $payment->status,
                        'amount' => $payment->paid_amount,
                        'amount_label' => number_format($payment->paid_amount, 0, ',', '.').',-',
                        'receipt_url' => route('finance.other.receipt', $payment),
                        'download_url' => route('finance.other.receipt.download', $payment),
                        'delete_url' => route('finance.other.destroy', $payment),
                    ]);

                $paymentHistory = $sppHistory
                    ->concat($otherHistory)
                    ->sortByDesc('timestamp')
                    ->take(10)
                    ->values();
            }
        }

        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'search' => $search,
            'selectedStudentId' => $selectedStudentId,
            'people' => $people,
            'paymentHistory' => $paymentHistory,
            'transferAccount' => $this->transferAccount(),
            'mode' => 'payment',
        ]);
    }

    public function store(Request $request, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'search' => ['nullable', 'string'],
            'bill_keys' => ['nullable', 'array'],
            'bill_keys.*' => ['string'],
            'optional_keys' => ['nullable', 'array'],
            'optional_keys.*' => ['string'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
            'transfer_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $selectedStudent = Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->where('is_active', true)
            ->findOrFail($validated['student_id']);
        $identityId = $selectedStudent->identity_student_id ?: $selectedStudent->id;
        $registrations = Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('id', $identityId)
                ->orWhere('identity_student_id', $identityId))
            ->get()
            ->keyBy('id');
        $feeTypes = FeeType::where('is_active', true)->get();
        $selectedKeys = collect($validated['bill_keys'] ?? [])
            ->concat($validated['optional_keys'] ?? [])
            ->unique()
            ->values();
        if ($selectedKeys->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['bill_keys' => 'Pilih minimal satu tagihan atau pembayaran opsional.']);
        }
        $selectedTotal = $this->selectedBillTotal($selectedKeys, $registrations, $feeTypes, $sppPayments, $otherPayments, $laundryPayments);
        if ($selectedTotal < 1) {
            return back()
                ->withInput()
                ->withErrors(['bill_keys' => 'Tidak ada tagihan terpilih yang dapat dibayar.']);
        }

        if ((int) $validated['paid_amount'] > $selectedTotal) {
            return back()
                ->withInput()
                ->withErrors(['paid_amount' => 'Nominal dibayar tidak boleh melebihi total tagihan terpilih Rp '.number_format($selectedTotal, 0, ',', '.').'.']);
        }

        $remainingPayment = (int) $validated['paid_amount'];
        $now = now();
        $baseData = [
            'transaction_date' => $now->toDateString(),
            'transaction_time' => $now->format('H:i:s'),
            'payment_method' => $validated['payment_method'],
            'status' => 'Diterima',
            'operator_name' => auth()->user()?->name,
        ];
        if ($validated['payment_method'] === 'Transfer' && $request->hasFile('transfer_proof')) {
            $baseData['transfer_proof_path'] = $request->file('transfer_proof')->store('payment-proofs', 'public');
        }
        $createdPayments = collect();

        foreach ($selectedKeys as $billKey) {
            if ($remainingPayment < 1) {
                break;
            }

            [$studentId, $group, $feeTypeId] = array_pad(explode(':', (string) $billKey, 3), 3, null);
            $student = $registrations->get((int) $studentId);
            if (! $student || ! $group) {
                continue;
            }

            if ($group === 'optional') {
                $feeType = $feeTypes->firstWhere('id', (int) $feeTypeId);
                if (! $feeType || $remainingPayment < 1) {
                    continue;
                }

                $payment = $this->recordOptionalPayment($student, $feeType, $baseData, $remainingPayment, $otherPayments, $laundryPayments);
                if (! $payment) {
                    continue;
                }

                $createdPayments->push([
                    'type' => 'other',
                    'id' => $payment->id,
                    'label' => $feeType->name,
                    'receipt_url' => route('finance.other.receipt', $payment),
                    'download_url' => route('finance.other.receipt.download', $payment),
                ]);
                $remainingPayment -= (int) $payment->paid_amount;

                continue;
            }

            if ($group === 'spp') {
                $plan = $sppPayments->paymentPlan($student);
                $monthCount = max(0, (int) ($plan['default_month_count'] ?: $plan['max_month_count']));
                if ($monthCount < 1) {
                    continue;
                }

                $quote = $sppPayments->quoteByMonthCount($student, $monthCount);
                $paidAmount = min($remainingPayment, (int) $quote['remaining_amount']);
                if ($paidAmount < 1) {
                    continue;
                }

                $payment = $sppPayments->record($student, $baseData + [
                    'month_count' => $monthCount,
                    'paid_amount' => $paidAmount,
                ]);
                $createdPayments->push([
                    'type' => 'spp',
                    'id' => $payment->id,
                    'label' => 'SPP '.$student->schoolClass?->educationUnit?->code,
                    'receipt_url' => route('finance.spp.receipt', $payment),
                    'download_url' => route('finance.spp.receipt.download', $payment),
                ]);
                $remainingPayment -= $paidAmount;

                continue;
            }

            $matchedFeeTypes = $this->matchedFeeTypes($student, $feeTypes)
                ->filter(fn (FeeType $feeType) => $this->paymentGroup($feeType) === $group)
                ->values();

            foreach ($matchedFeeTypes as $feeType) {
                if ($remainingPayment < 1) {
                    break;
                }

                if ($group === 'laundry') {
                    $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) $now->year);
                    $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);
                    if (! $firstPayable) {
                        continue;
                    }

                    $paidAmount = min($remainingPayment, (int) $firstPayable['remaining_amount']);
                    $payment = $laundryPayments->record($student, $feeType, $baseData + [
                        'year' => (int) $firstPayable['year'],
                        'months' => [(int) $firstPayable['month']],
                        'paid_amount' => $paidAmount,
                    ]);
                } else {
                    $quote = $otherPayments->quote($student, $feeType, $now);
                    $paidAmount = min($remainingPayment, (int) $quote['remaining_amount']);
                    if ($paidAmount < 1) {
                        continue;
                    }

                    $payment = $otherPayments->record($student, $feeType, $baseData + [
                        'paid_amount' => $paidAmount,
                    ]);
                }

                $createdPayments->push([
                    'type' => 'other',
                    'id' => $payment->id,
                    'label' => $feeType->name,
                    'receipt_url' => route('finance.other.receipt', $payment),
                    'download_url' => route('finance.other.receipt.download', $payment),
                ]);
                $remainingPayment -= $paidAmount;
            }
        }

        if ($createdPayments->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['bill_keys' => 'Tidak ada tagihan terpilih yang dapat dibayar.']);
        }

        return redirect()
            ->route('finance.payments.index', [
                'search' => $validated['search'] ?: $selectedStudent->name,
                'student_id' => $selectedStudent->id,
            ])
            ->with('success', $createdPayments->count() > 1
                ? $createdPayments->count().' transaksi berhasil disimpan. Struk dibuka sesuai jenis pembayaran.'
                : 'Transaksi berhasil disimpan. Struk dibuka sesuai jenis pembayaran.')
            ->with('payment_receipts', $createdPayments
                ->map(fn (array $payment) => [
                    'label' => $payment['label'],
                    'receipt_url' => $payment['receipt_url'],
                    'download_url' => $payment['download_url'],
                ])
                ->values()
                ->all());
    }

    public function receiptBatch(Request $request, SppPaymentService $sppPayments): View|RedirectResponse
    {
        $batch = collect(session('unified_receipt_batch.payments', []));

        if ($batch->isEmpty()) {
            return redirect()->route('finance.payments.index');
        }

        $receipts = $batch
            ->map(function (array $item) use ($sppPayments) {
                if (($item['type'] ?? null) === 'spp') {
                    $payment = SppPayment::with(['student.schoolClass.educationUnit', 'items'])->find($item['id'] ?? null);

                    if (! $payment) {
                        return null;
                    }

                    return [
                        'type' => 'spp',
                        'payment' => $payment,
                        'receiptNumber' => $this->receiptNumber($payment),
                        'outstandingSummary' => $sppPayments->outstandingSummaryUntilCurrent($payment->student),
                    ];
                }

                if (($item['type'] ?? null) === 'other') {
                    $payment = OtherPayment::with([
                        'student.academicYear',
                        'student.schoolClass.educationUnit',
                        'feeType.academicYear',
                        'items',
                    ])->find($item['id'] ?? null);

                    if (! $payment) {
                        return null;
                    }

                    return [
                        'type' => 'other',
                        'payment' => $payment,
                        'receiptNumber' => $this->receiptNumber($payment),
                        'outstandingSummary' => null,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();

        if ($receipts->isEmpty()) {
            return redirect()->route('finance.payments.index');
        }

        return view('finance.unified-receipt', [
            'receipts' => $receipts,
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
            'receiptSettings' => AppSetting::values(),
        ]);
    }

    public function import(): View
    {
        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'mode' => 'import',
        ]);
    }

    private function selectedBillTotal(Collection $selectedKeys, Collection $registrations, Collection $feeTypes, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): int
    {
        $total = 0;

        foreach ($selectedKeys as $billKey) {
            [$studentId, $group, $feeTypeId] = array_pad(explode(':', (string) $billKey, 3), 3, null);
            $student = $registrations->get((int) $studentId);
            if (! $student || ! $group) {
                continue;
            }

            if ($group === 'optional') {
                $feeType = $feeTypes->firstWhere('id', (int) $feeTypeId);
                if (! $feeType) {
                    continue;
                }

                $total += $this->optionalPaymentTotal($student, $feeType, $otherPayments, $laundryPayments);

                continue;
            }

            if ($group === 'spp') {
                $plan = $sppPayments->paymentPlan($student);
                $monthCount = max(0, (int) ($plan['default_month_count'] ?: $plan['max_month_count']));
                if ($monthCount > 0) {
                    $total += (int) $sppPayments->quoteByMonthCount($student, $monthCount)['remaining_amount'];
                }

                continue;
            }

            $matchedFeeTypes = $this->matchedFeeTypes($student, $feeTypes)
                ->filter(fn (FeeType $feeType) => $this->paymentGroup($feeType) === $group)
                ->values();

            foreach ($matchedFeeTypes as $feeType) {
                if ($group === 'laundry') {
                    $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) now()->year);
                    $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);
                    $total += (int) ($firstPayable['remaining_amount'] ?? 0);
                } else {
                    $total += (int) $otherPayments->quote($student, $feeType, now())['remaining_amount'];
                }
            }
        }

        return $total;
    }

    public function history(): View
    {
        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'mode' => 'history',
        ]);
    }

    private function receiptNumber(SppPayment|OtherPayment $payment): string
    {
        if ($payment instanceof SppPayment) {
            return 'SPP-'.$payment->transaction_at->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
        }

        return $this->receiptPrefix($payment).'-'.$payment->transaction_at->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    private function receiptPrefix(OtherPayment $payment): string
    {
        $payment->loadMissing('feeType');

        return match ($payment->feeType?->payment_group) {
            'daftar-ulang' => 'DU',
            'laundry' => 'LD',
            default => 'LL',
        };
    }

    private function sppHistoryPeriod(SppPayment $payment): string
    {
        $periods = $payment->items
            ->map(fn ($item) => ['month' => $item->month, 'year' => $item->year])
            ->all();

        return $this->sppPeriodLabel($periods);
    }

    private function otherHistoryPeriod(OtherPayment $payment): string
    {
        if ($payment->items->isEmpty()) {
            return $payment->feeType?->academicYear?->name ?? $payment->student?->academicYear?->name ?? '-';
        }

        return $payment->items
            ->sortBy(fn ($item) => ((int) $item->year * 100) + (int) $item->month)
            ->map(fn ($item) => $this->monthName((int) $item->month).' '.$item->year)
            ->unique()
            ->join(', ');
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

    private function paymentOptions(Student $student, Collection $feeTypes, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): array
    {
        $matchedFeeTypes = $this->matchedFeeTypes($student, $feeTypes);
        $grouped = $matchedFeeTypes->groupBy(fn (FeeType $feeType) => $this->paymentGroup($feeType));
        $definitions = [
            'spp' => ['label' => 'SPP', 'url' => route('finance.spp.create', ['student_id' => $student->id])],
            'daftar-ulang' => ['label' => 'Daftar Ulang', 'url' => route('finance.other.create', ['category' => 'daftar-ulang', 'student_id' => $student->id])],
            'lain-lain' => ['label' => 'Lainnya', 'url' => route('finance.other.create', ['student_id' => $student->id])],
        ];

        return collect($definitions)
            ->filter(fn (array $definition, string $group) => $grouped->has($group))
            ->map(function (array $definition, string $group) use ($student, $grouped, $sppPayments, $otherPayments, $laundryPayments) {
                $feeTypes = $grouped->get($group);
                $payable = $group === 'spp'
                    ? ($sppPayments->paymentPlan($student)['max_month_count'] > 0)
                    : $this->hasPayableFeeType($student, $feeTypes, $group, $otherPayments);
                $summary = $this->paymentOptionSummary($student, $feeTypes, $group, $sppPayments, $otherPayments, $laundryPayments);

                return [
                    'key' => $group,
                    'bill_key' => $student->id.':'.$group,
                    'student_id' => $student->id,
                    'label' => $definition['label'],
                    'url' => $definition['url'],
                    'status' => $payable ? 'payable' : 'paid',
                    'amount_label' => $summary['amount_label'],
                    'remaining_amount' => $summary['remaining_amount'],
                    'detail_label' => $summary['detail_label'],
                ];
            })
            ->filter(fn (array $option) => (int) $option['remaining_amount'] > 0)
            ->values()
            ->all();
    }

    private function paymentOptionSummary(Student $student, Collection $feeTypes, string $group, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): array
    {
        if ($group === 'spp') {
            $plan = $sppPayments->paymentPlan($student);
            $monthCount = max(0, (int) ($plan['default_month_count'] ?: $plan['max_month_count']));
            $remainingAmount = 0;
            $periods = [];

            if ($monthCount > 0) {
                try {
                    $quote = $sppPayments->quoteByMonthCount($student, $monthCount);
                    $remainingAmount = (int) ($quote['remaining_amount'] ?? 0);
                    $periods = $quote['items'] ?? [];
                } catch (ValidationException) {
                    $periods = array_slice($plan['periods'] ?? [], 0, $monthCount);
                    $remainingAmount = (int) collect($periods)->sum('remaining_amount');
                }
            }

            return [
                'amount_label' => $this->rupiah($remainingAmount),
                'remaining_amount' => $remainingAmount,
                'detail_label' => $monthCount > 0 ? $this->sppPeriodLabel($periods) : 'Tidak ada tagihan',
            ];
        }

        if ($group === 'laundry') {
            $remainingAmount = 0;
            $detailLabel = 'Per bulan';

            foreach ($feeTypes as $feeType) {
                try {
                    $statuses = $laundryPayments->monthStatuses($student, $feeType, now()->year);
                    $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);
                    if ($firstPayable) {
                        $remainingAmount += (int) $firstPayable['remaining_amount'];
                        $detailLabel = ($firstPayable['month_name'] ?? 'Bulan ini').' '.$firstPayable['year'];
                    }
                } catch (ValidationException) {
                    continue;
                }
            }

            return [
                'amount_label' => $this->rupiah($remainingAmount),
                'remaining_amount' => $remainingAmount,
                'detail_label' => $detailLabel,
            ];
        }

        $remainingAmount = 0;
        $detailLabel = $feeTypes->pluck('name')->filter()->unique()->join(', ');

        foreach ($feeTypes as $feeType) {
            try {
                $quote = $otherPayments->quote($student, $feeType);
                $remainingAmount += (int) ($quote['remaining_amount'] ?? 0);
            } catch (ValidationException) {
                continue;
            }
        }

        return [
            'amount_label' => $this->rupiah($remainingAmount),
            'remaining_amount' => $remainingAmount,
            'detail_label' => $detailLabel ?: 'Nama Kategori Pembayaran',
        ];
    }

    private function rupiah(int $amount): string
    {
        return 'Rp '.number_format(max(0, $amount), 0, ',', '.');
    }

    private function sppPeriodLabel(array $periods): string
    {
        $periods = collect($periods)
            ->filter(fn (array $period) => isset($period['year'], $period['month']))
            ->sortBy(fn (array $period) => ((int) $period['year'] * 100) + (int) $period['month'])
            ->values();

        if ($periods->isEmpty()) {
            return 'Tidak ada tagihan';
        }

        $first = $periods->first();
        $last = $periods->last();
        $firstLabel = $this->monthName((int) $first['month']).' '.$first['year'];
        $lastLabel = $this->monthName((int) $last['month']).' '.$last['year'];

        return $firstLabel === $lastLabel ? $firstLabel : $firstLabel.' - '.$lastLabel;
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month] ?? 'Bulan';
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
        if ($group === 'laundry') {
            return false;
        }

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

    private function optionalPaymentOptions(Student $student, Collection $feeTypes, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): array
    {
        return $this->matchedFeeTypes($student, $feeTypes)
            ->filter(fn (FeeType $feeType) => ! $feeType->creates_bill || $this->paymentGroup($feeType) === 'laundry')
            ->map(function (FeeType $feeType) use ($student, $otherPayments, $laundryPayments) {
                $amount = $this->optionalPaymentTotal($student, $feeType, $otherPayments, $laundryPayments);
                if ($amount < 1) {
                    return null;
                }

                return [
                    'key' => 'optional',
                    'bill_key' => $student->id.':optional:'.$feeType->id,
                    'student_id' => $student->id,
                    'fee_type_id' => $feeType->id,
                    'label' => $feeType->name,
                    'title' => $feeType->name,
                    'url' => route('finance.other.create', [
                        'category' => $this->paymentGroup($feeType),
                        'student_id' => $student->id,
                        'fee_type_id' => $feeType->id,
                    ]),
                    'amount_label' => $this->rupiah($amount),
                    'detail' => $this->optionalPaymentDetail($student, $feeType, $laundryPayments),
                    'detail_label' => $this->optionalPaymentDetail($student, $feeType, $laundryPayments),
                    'amount_value' => $amount,
                    'remaining_amount' => $amount,
                    'amount_number' => number_format($amount, 0, ',', '.').',-',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function optionalPaymentTotal(Student $student, FeeType $feeType, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): int
    {
        try {
            if ($this->paymentGroup($feeType) === 'laundry') {
                $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) now()->year);
                $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);

                return (int) ($firstPayable['remaining_amount'] ?? 0);
            }

            return (int) ($otherPayments->quote($student, $feeType, now())['remaining_amount'] ?? 0);
        } catch (ValidationException) {
            return 0;
        }
    }

    private function optionalPaymentDetail(Student $student, FeeType $feeType, LaundryPaymentService $laundryPayments): string
    {
        if ($this->paymentGroup($feeType) !== 'laundry') {
            return $feeType->period ?: 'Pembayaran opsional';
        }

        try {
            $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) now()->year);
            $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);
        } catch (ValidationException) {
            $firstPayable = null;
        }

        return $firstPayable
            ? ($firstPayable['month_name'] ?? $this->monthName((int) $firstPayable['month'])).' '.$firstPayable['year']
            : 'Pembayaran opsional';
    }

    private function recordOptionalPayment(Student $student, FeeType $feeType, array $baseData, int $remainingPayment, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): ?OtherPayment
    {
        $group = $this->paymentGroup($feeType);

        if ($group === 'laundry') {
            $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) now()->year);
            $firstPayable = collect($statuses['months'] ?? [])->first(fn (array $item) => ($item['remaining_amount'] ?? 0) > 0);
            if (! $firstPayable) {
                return null;
            }

            return $laundryPayments->record($student, $feeType, $baseData + [
                'year' => (int) $firstPayable['year'],
                'months' => [(int) $firstPayable['month']],
                'paid_amount' => min($remainingPayment, (int) $firstPayable['remaining_amount']),
            ]);
        }

        $quote = $otherPayments->quote($student, $feeType, now());
        $paidAmount = min($remainingPayment, (int) $quote['remaining_amount']);
        if ($paidAmount < 1) {
            return null;
        }

        return $otherPayments->record($student, $feeType, $baseData + [
            'paid_amount' => $paidAmount,
        ]);
    }

    private function transferAccount(): array
    {
        $settings = AppSetting::values([
            'transfer_bank_name' => 'Bank belum diatur',
            'transfer_account_number' => '-',
            'transfer_account_name' => "MA'WA CENTER",
        ]);

        return [
            'bank_name' => $settings['transfer_bank_name'] ?: 'Bank belum diatur',
            'account_number' => $settings['transfer_account_number'] ?: '-',
            'account_name' => $settings['transfer_account_name'] ?: "MA'WA CENTER",
        ];
    }
}
