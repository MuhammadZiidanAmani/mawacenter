<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Bill;
use App\Models\EducationUnit;
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
        $editSppPayment = null;
        $editPaymentId = (int) $request->integer('edit_payment');
        if ($editPaymentId > 0) {
            $editSppPayment = SppPayment::with(['student.schoolClass.educationUnit', 'items'])
                ->findOrFail($editPaymentId);
            $unitIds = $request->user()?->accessibleUnitIds();
            if (is_array($unitIds) && ! in_array((int) $editSppPayment->student?->schoolClass?->education_unit_id, $unitIds, true)) {
                abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
            }
            $selectedStudentId = (int) $editSppPayment->student_id;
            if ($search === '') {
                $search = $editSppPayment->student?->nis ?: $editSppPayment->student?->name ?: '';
            }
        }
        $historyPeriod = trim($request->string('history_period')->value());
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $historyPeriod)) {
            $historyPeriod = now()->format('Y-m');
        }
        [$historyYear, $historyMonth] = array_map('intval', explode('-', $historyPeriod));
        $historyPeriodLabel = $this->monthName($historyMonth).' '.$historyYear;
        $people = collect();
        $paymentHistory = collect();
        $unitIds = $request->user()?->accessibleUnitIds();

        if ($search !== '') {
            $needle = $this->escapeLike($search);
            $matches = $this->paymentStudentBaseQuery($unitIds)
                ->where(fn ($query) => $query
                    ->where('nis', 'like', "{$needle}%")
                    ->orWhere('nisn', 'like', "{$needle}%")
                    ->orWhere('name', 'like', "{$needle}%"))
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'identity_student_id']);

            if ($matches->count() < 30 && mb_strlen($search) >= 3) {
                $fallbackMatches = $this->paymentStudentBaseQuery($unitIds)
                    ->whereNotIn('id', $matches->pluck('id'))
                    ->where('name', 'like', "%{$needle}%")
                    ->orderBy('name')
                    ->limit(30 - $matches->count())
                    ->get(['id', 'identity_student_id']);

                $matches = $matches->concat($fallbackMatches);
            }

            $identityIds = $matches
                ->map(fn (Student $student) => $student->identity_student_id ?: $student->id)
                ->unique()
                ->values();

            if ($identityIds->isNotEmpty()) {
                $registrations = Student::with(['academicYear', 'schoolClass.educationUnit'])
                    ->where('is_active', true)
                    ->when(is_array($unitIds), fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
                    ->where(fn ($query) => $query
                        ->whereIn('id', $identityIds)
                        ->orWhereIn('identity_student_id', $identityIds))
                    ->orderBy('name')
                    ->get();

                $people = $registrations->groupBy(fn (Student $student) => $student->identity_student_id ?: $student->id);
            }
        }

        if ($selectedStudentId < 1 && $people->count() === 1) {
            $registrations = $people->first();
            $identity = $registrations->firstWhere('identity_student_id', null) ?? $registrations->first();
            $selectedStudentId = (int) $identity->id;
        }

        if ($selectedStudentId > 0) {
            $selectedStudent = Student::where('is_active', true)
                ->when(is_array($unitIds), fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
                ->find($selectedStudentId);
            if ($selectedStudent) {
                $identityId = $selectedStudent->identity_student_id ?: $selectedStudent->id;
                $studentIds = Student::where('is_active', true)
                    ->where(fn ($query) => $query
                        ->where('id', $identityId)
                        ->orWhere('identity_student_id', $identityId))
                    ->pluck('id');

                $selectedRegistrations = $people->get($identityId);
                if (! $selectedRegistrations) {
                    $selectedRegistrations = Student::with(['academicYear', 'schoolClass.educationUnit'])
                        ->where('is_active', true)
                        ->when(is_array($unitIds), fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
                        ->where(fn ($query) => $query
                            ->where('id', $identityId)
                            ->orWhere('identity_student_id', $identityId))
                        ->orderBy('name')
                        ->get();

                    if ($selectedRegistrations->isNotEmpty()) {
                        $people->put($identityId, $selectedRegistrations);
                    }
                }

                if ($selectedRegistrations && $selectedRegistrations->isNotEmpty()) {
                    $feeTypes = FeeType::where('is_active', true)->get();
                    $selectedRegistrations->each(function (Student $student) use ($feeTypes, $sppPayments, $otherPayments, $laundryPayments, $editSppPayment) {
                        $student->setAttribute('payment_options', $this->paymentOptions($student, $feeTypes, $sppPayments, $otherPayments, $laundryPayments, $editSppPayment));
                        $student->setAttribute('optional_payment_options', $this->optionalPaymentOptions($student, $feeTypes, $otherPayments, $laundryPayments));
                    });
                }

                $sppHistory = SppPayment::with(['student.schoolClass.educationUnit', 'items'])
                    ->whereIn('student_id', $studentIds)
                    ->whereYear('transaction_at', $historyYear)
                    ->whereMonth('transaction_at', $historyMonth)
                    ->latest('transaction_at')
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
                    ->whereYear('transaction_at', $historyYear)
                    ->whereMonth('transaction_at', $historyMonth)
                    ->latest('transaction_at')
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
                    ->values();
            }
        }

        return view('finance.payments', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'search' => $search,
            'selectedStudentId' => $selectedStudentId,
            'people' => $people,
            'paymentHistory' => $paymentHistory,
            'historyPeriod' => $historyPeriod,
            'historyPeriodLabel' => $historyPeriodLabel,
            'transferAccount' => $this->transferAccount(),
            'cashOnly' => $request->user()?->isPetugas() ?? false,
            'editSppPayment' => $editSppPayment,
            'returnUrl' => trim($request->string('return_url')->value()),
            'mode' => 'payment',
        ]);
    }

    public function store(Request $request, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('payments.cash.create'), 403);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'search' => ['nullable', 'string'],
            'bill_keys' => ['nullable', 'array'],
            'bill_keys.*' => ['string'],
            'optional_keys' => ['nullable', 'array'],
            'optional_keys.*' => ['string'],
            'payment_month_counts' => ['nullable', 'array'],
            'payment_month_counts.*' => ['integer', 'min:1', 'max:120'],
            'payment_method' => ['required', Rule::in(['Cash', 'Transfer'])],
            'paid_amount' => ['required', 'integer', 'min:1'],
            'transfer_proof' => ['required_if:payment_method,Transfer', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'transfer_proof.required_if' => 'Bukti transfer wajib diunggah untuk metode pembayaran Transfer.',
        ]);

        if (($request->user()?->isPetugas() ?? false) && $validated['payment_method'] !== 'Cash') {
            throw ValidationException::withMessages([
                'payment_method' => 'Petugas hanya dapat menerima transaksi cash di kantor.',
            ]);
        }

        $selectedStudent = Student::with(['academicYear', 'schoolClass.educationUnit'])
            ->where('is_active', true)
            ->findOrFail($validated['student_id']);
        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds) && ! in_array((int) $selectedStudent->schoolClass?->education_unit_id, $unitIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
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
        $paymentMonthCounts = collect($validated['payment_month_counts'] ?? []);
        if ($selectedKeys->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['bill_keys' => 'Pilih minimal satu tagihan atau pembayaran opsional.']);
        }
        $selectedTotal = $this->selectedBillTotal($selectedKeys, $paymentMonthCounts, $registrations, $feeTypes, $sppPayments, $otherPayments, $laundryPayments);
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
            'operator_user_id' => auth()->id(),
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
            $selectedMonthCount = (int) $paymentMonthCounts->get($this->paymentModeKey((string) $billKey), 0);
            $student = $registrations->get((int) $studentId);
            if (! $student || ! $group) {
                continue;
            }

            if ($group === 'optional') {
                $feeType = $feeTypes->firstWhere('id', (int) $feeTypeId);
                if (! $feeType || $remainingPayment < 1) {
                    continue;
                }

                $payment = $this->recordOptionalPayment($student, $feeType, $baseData, $remainingPayment, $selectedMonthCount, $otherPayments, $laundryPayments);
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
                if ($selectedMonthCount > 0) {
                    $quote = $sppPayments->quoteNextMonths($student, $selectedMonthCount);
                    $paidAmount = min($remainingPayment, (int) $quote['remaining_amount']);
                    if ($paidAmount < 1) {
                        continue;
                    }

                    $payment = $sppPayments->record($student, $baseData + [
                        'advance_month_count' => $selectedMonthCount,
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
                    $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);
                    if (! $currentLaundry) {
                        continue;
                    }

                    $paidAmount = min($remainingPayment, (int) $currentLaundry['remaining_amount']);
                    $payment = $laundryPayments->record($student, $feeType, $baseData + [
                        'year' => (int) $currentLaundry['year'],
                        'months' => [(int) $currentLaundry['month']],
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
            'educationUnits' => EducationUnit::orderByRaw("CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                ->orderBy('name')
                ->get(),
            'mode' => 'import',
        ]);
    }

    private function selectedBillTotal(Collection $selectedKeys, Collection $paymentMonthCounts, Collection $registrations, Collection $feeTypes, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): int
    {
        $total = 0;

        foreach ($selectedKeys as $billKey) {
            [$studentId, $group, $feeTypeId] = array_pad(explode(':', (string) $billKey, 3), 3, null);
            $selectedMonthCount = (int) $paymentMonthCounts->get($this->paymentModeKey((string) $billKey), 0);
            $student = $registrations->get((int) $studentId);
            if (! $student || ! $group) {
                continue;
            }

            if ($group === 'optional') {
                $feeType = $feeTypes->firstWhere('id', (int) $feeTypeId);
                if (! $feeType) {
                    continue;
                }

                $total += $selectedMonthCount > 0 && $this->paymentGroup($feeType) === 'laundry'
                    ? (int) $laundryPayments->quoteByMonthCount($student, $feeType, $selectedMonthCount)['remaining_amount']
                    : $this->optionalPaymentTotal($student, $feeType, $otherPayments, $laundryPayments);

                continue;
            }

            if ($group === 'spp') {
                if ($selectedMonthCount > 0) {
                    $total += (int) $sppPayments->quoteNextMonths($student, $selectedMonthCount)['remaining_amount'];

                    continue;
                }

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
                    $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);
                    $total += (int) ($currentLaundry['remaining_amount'] ?? 0);
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

    private function paymentStudentBaseQuery(?array $unitIds)
    {
        return Student::query()
            ->where('is_active', true)
            ->when(is_array($unitIds), fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
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

    private function paymentOptions(Student $student, Collection $feeTypes, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments, ?SppPayment $editSppPayment = null): array
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
            ->map(function (array $definition, string $group) use ($student, $grouped, $sppPayments, $otherPayments, $laundryPayments, $editSppPayment) {
                $feeTypes = $grouped->get($group);
                $ignoredPayment = $group === 'spp' && $editSppPayment && (int) $editSppPayment->student_id === (int) $student->id
                    ? $editSppPayment
                    : null;
                $payable = $group === 'spp'
                    ? (($ignoredPayment ? $sppPayments->paymentPlanFromPayment($ignoredPayment) : $sppPayments->paymentPlan($student))['max_month_count'] > 0)
                    : $this->hasPayableFeeType($student, $feeTypes, $group, $otherPayments);
                $summary = $this->paymentOptionSummary($student, $feeTypes, $group, $sppPayments, $otherPayments, $laundryPayments, $ignoredPayment);

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
                    'period_options' => $summary['period_options'] ?? [],
                    'default_period_count' => (int) ($summary['default_period_count'] ?? 1),
                ];
            })
            ->filter(fn (array $option) => (int) $option['remaining_amount'] > 0 || $option['period_options'] !== [])
            ->values()
            ->all();
    }

    private function paymentOptionSummary(Student $student, Collection $feeTypes, string $group, SppPaymentService $sppPayments, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments, ?SppPayment $ignoredPayment = null): array
    {
        if ($group === 'spp') {
            $plan = $ignoredPayment ? $sppPayments->paymentPlanFromPayment($ignoredPayment) : $sppPayments->paymentPlan($student);
            $monthCount = max(0, (int) ($plan['default_month_count'] ?: $plan['max_month_count']));
            if ($ignoredPayment) {
                $monthCount = max(1, min((int) $ignoredPayment->items->count(), (int) $plan['max_month_count']));
            }
            $remainingAmount = 0;
            $periods = [];

            if ($monthCount > 0) {
                try {
                    $quote = $ignoredPayment
                        ? $sppPayments->quoteFromPaymentStart($ignoredPayment, $monthCount)
                        : $sppPayments->quoteByMonthCount($student, $monthCount);
                    $remainingAmount = (int) ($quote['remaining_amount'] ?? 0);
                    $periods = $quote['items'] ?? [];
                } catch (ValidationException) {
                    $periods = array_slice($plan['periods'] ?? [], 0, $monthCount);
                    $remainingAmount = (int) collect($periods)->sum('remaining_amount');
                }
            }

            $periodOptions = [];
            $defaultPeriodCount = 1;
            if ($ignoredPayment) {
                $runningAmount = 0;
                foreach ($plan['periods'] as $index => $item) {
                    $runningAmount += (int) $item['remaining_amount'];
                    $periodOptions[] = $this->periodOption($index + 1, [
                        'remaining_amount' => $runningAmount,
                        'period_start' => $plan['periods'][0] ?? null,
                        'period_end' => ['year' => $item['year'], 'month' => $item['month']],
                    ], true);
                }
                $defaultPeriodCount = $monthCount;
            } else {
                try {
                $window = $sppPayments->paymentWindow($student);
                $defaultPeriodCount = (int) $window['default_month_count'];
                $runningAmount = 0;
                foreach ($window['items'] as $index => $item) {
                    $runningAmount += (int) $item['remaining_amount'];
                    $periodOptions[] = $this->periodOption($index + 1, [
                        'remaining_amount' => $runningAmount,
                        'period_start' => $window['period_start'],
                        'period_end' => ['year' => $item['year'], 'month' => $item['month']],
                    ], true);
                }
                } catch (ValidationException) {
                    // Tidak ada periode SPP yang dapat ditagihkan.
                }
            }
            if ($periodOptions !== []) {
                $defaultOption = $periodOptions[$defaultPeriodCount - 1] ?? $periodOptions[0];
                $remainingAmount = (int) $defaultOption['amount'];
                $periods = array_slice($ignoredPayment ? ($plan['periods'] ?? []) : ($window['items'] ?? []), 0, $defaultPeriodCount);
            }

            return [
                'amount_label' => $this->rupiah($remainingAmount),
                'remaining_amount' => $remainingAmount,
                'detail_label' => $periods !== [] ? $this->sppPeriodLabel($periods) : 'Tidak ada tagihan',
                'period_options' => count($periodOptions) > 1 ? $periodOptions : [],
                'default_period_count' => $defaultPeriodCount,
            ];
        }

        if ($group === 'laundry') {
            $remainingAmount = 0;
            $detailLabel = 'Per bulan';

            foreach ($feeTypes as $feeType) {
                try {
                    $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);
                    if ($currentLaundry) {
                        $remainingAmount += (int) $currentLaundry['remaining_amount'];
                        $detailLabel = ($currentLaundry['month_name'] ?? 'Bulan ini').' '.$currentLaundry['year'];
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

        return $this->formatPeriodRange($first, $last);
    }

    private function monthName(int $month): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month] ?? 'Bulan';
    }

    private function matchedFeeTypes(Student $student, Collection $feeTypes): Collection
    {
        return $feeTypes->filter(fn (FeeType $feeType) => $feeType->matchesSchoolClass($student->schoolClass)
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
                $detail = $this->optionalPaymentDetail($student, $feeType, $laundryPayments);
                $periodOptions = [];
                if ($this->paymentGroup($feeType) === 'laundry') {
                    for ($count = 1; $count <= 12; $count++) {
                        try {
                            $periodOptions[] = $this->periodOption($count, $laundryPayments->quoteByMonthCount($student, $feeType, $count));
                        } catch (ValidationException) {
                            break;
                        }
                    }
                }

                if ($periodOptions !== []) {
                    $amount = (int) $periodOptions[0]['amount'];
                    $detail = (string) $periodOptions[0]['detail'];
                }

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
                    'detail' => $detail,
                    'detail_label' => $detail,
                    'amount_value' => $amount,
                    'remaining_amount' => $amount,
                    'amount_number' => number_format($amount, 0, ',', '.').',-',
                    'period_options' => count($periodOptions) > 1 ? $periodOptions : [],
                    'default_period_count' => 1,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function currentLaundryItem(Student $student, FeeType $feeType, LaundryPaymentService $laundryPayments): ?array
    {
        $current = now();
        $statuses = $laundryPayments->monthStatuses($student, $feeType, (int) $current->year);

        return collect($statuses['months'] ?? [])->first(fn (array $item) => (int) ($item['year'] ?? 0) === (int) $current->year
            && (int) ($item['month'] ?? 0) === (int) $current->month
            && (int) ($item['remaining_amount'] ?? 0) > 0
        );
    }

    private function optionalPaymentTotal(Student $student, FeeType $feeType, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): int
    {
        try {
            if ($this->paymentGroup($feeType) === 'laundry') {
                $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);

                return (int) ($currentLaundry['remaining_amount'] ?? 0);
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
            $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);
        } catch (ValidationException) {
            $currentLaundry = null;
        }

        return $currentLaundry
            ? ($currentLaundry['month_name'] ?? $this->monthName((int) $currentLaundry['month'])).' '.$currentLaundry['year']
            : 'Pembayaran opsional';
    }

    private function recordOptionalPayment(Student $student, FeeType $feeType, array $baseData, int $remainingPayment, int $selectedMonthCount, OtherPaymentService $otherPayments, LaundryPaymentService $laundryPayments): ?OtherPayment
    {
        $group = $this->paymentGroup($feeType);

        if ($group === 'laundry') {
            if ($selectedMonthCount > 0) {
                $quote = $laundryPayments->quoteByMonthCount($student, $feeType, $selectedMonthCount);

                return $laundryPayments->record($student, $feeType, $baseData + [
                    'month_count' => $selectedMonthCount,
                    'paid_amount' => min($remainingPayment, (int) $quote['remaining_amount']),
                ]);
            }

            $currentLaundry = $this->currentLaundryItem($student, $feeType, $laundryPayments);
            if (! $currentLaundry) {
                return null;
            }

            return $laundryPayments->record($student, $feeType, $baseData + [
                'year' => (int) $currentLaundry['year'],
                'months' => [(int) $currentLaundry['month']],
                'paid_amount' => min($remainingPayment, (int) $currentLaundry['remaining_amount']),
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

    private function paymentModeKey(string $billKey): string
    {
        return str_replace(':', '_', $billKey);
    }

    private function periodOption(int $count, array $quote, bool $showFullRangeOnCard = false): array
    {
        $end = $quote['period_end'] ?? collect($quote['items'] ?? [])->last();

        return [
            'count' => $count,
            'amount' => (int) ($quote['remaining_amount'] ?? 0),
            'detail' => $end
                ? $this->monthName((int) $end['month']).' '.$end['year']
                : $this->periodRangeLabel($quote),
            'card_detail' => $showFullRangeOnCard
                ? $this->periodRangeLabel($quote)
                : ($end ? $this->monthName((int) $end['month']).' '.$end['year'] : $this->periodRangeLabel($quote)),
        ];
    }

    private function periodRangeLabel(array $quote): string
    {
        $start = $quote['period_start'] ?? null;
        $end = $quote['period_end'] ?? null;
        if (! $start || ! $end) {
            return $this->sppPeriodLabel($quote['items'] ?? []);
        }

        return $this->formatPeriodRange($start, $end);
    }

    private function formatPeriodRange(array $start, array $end): string
    {
        $startMonth = $this->monthName((int) $start['month']);
        $endMonth = $this->monthName((int) $end['month']);
        $startYear = (int) $start['year'];
        $endYear = (int) $end['year'];

        if ($startMonth === $endMonth && $startYear === $endYear) {
            return $startMonth.' '.$startYear;
        }

        if ($startYear === $endYear) {
            return $startMonth.' - '.$endMonth.' '.$endYear;
        }

        return $startMonth.' '.$startYear.' - '.$endMonth.' '.$endYear;
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
