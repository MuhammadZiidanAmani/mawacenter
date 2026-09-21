<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectSppPaymentRequest;
use App\Http\Requests\PreviewSppPaymentImportRequest;
use App\Http\Requests\StoreSppPaymentRequest;
use App\Http\Requests\UpdateSppPaymentRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\SppPaymentImportService;
use App\Services\SppPaymentService;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SppPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search = $request->string('search')->value();
        $dateFrom = $this->filterDate($request, 'date_from') ?? now()->startOfDay();
        $dateTo = $this->filterDate($request, 'date_to', true) ?? now()->endOfDay();
        $sort = in_array($request->string('sort')->value(), ['nis', 'name', 'unit', 'class', 'method', 'total'], true)
            ? $request->string('sort')->value()
            : 'date';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';
        $unitIds = $request->user()?->accessibleUnitIds();

        return view('finance.spp', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payments' => SppPayment::select('spp_payments.*')->with(['student.schoolClass.educationUnit', 'items', 'corrections'])
                ->whereBetween('spp_payments.transaction_at', [$dateFrom, $dateTo])
                ->when($request->filled('payment_method'), fn ($query) => $query->where('spp_payments.payment_method', $request->string('payment_method')->value()))
                ->when($request->filled('status'), fn ($query) => $query->where('spp_payments.status', $request->string('status')->value()))
                ->when($request->filled('operator_name'), fn ($query) => $query->where('spp_payments.operator_name', $request->string('operator_name')->value()))
                ->when($request->filled('student_id'), fn ($query) => $query->where('spp_payments.student_id', $request->integer('student_id')))
                ->when(is_array($unitIds), fn ($query) => $query->whereHas('student.schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
                ->when($request->filled('nis'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('nis', 'like', '%'.$request->string('nis')->value().'%')))
                ->when(! $request->filled('student_id') && $request->filled('student_search'), fn ($query) => $query->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', '%'.$request->string('student_search')->value().'%')
                    ->orWhere('name', 'like', '%'.$request->string('student_search')->value().'%')))
                ->when($request->filled('unit_id'), fn ($query) => $query->whereHas('student.schoolClass', fn ($class) => $class->where('education_unit_id', $request->integer('unit_id'))))
                ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
                ->when($search, fn ($query) => $query->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('schoolClass', fn ($class) => $class
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('educationUnit', fn ($unit) => $unit->where('name', 'like', "%{$search}%")))))
                ->when(in_array($sort, ['nis', 'name', 'unit', 'class'], true), fn ($query) => $query
                    ->join('students', 'students.id', '=', 'spp_payments.student_id')
                    ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id'))
                ->when($sort === 'unit', fn ($query) => $query
                    ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                    ->orderBy('education_units.name', $direction))
                ->when($sort === 'class', fn ($query) => $query->orderBy('school_classes.name', $direction))
                ->when(in_array($sort, ['nis', 'name'], true), fn ($query) => $query->orderBy('students.'.$sort, $direction))
                ->when($sort === 'method', fn ($query) => $query->orderBy('spp_payments.payment_method', $direction))
                ->when($sort === 'total', fn ($query) => $query->orderBy('spp_payments.paid_amount', $direction))
                ->when($sort === 'date', fn ($query) => $query->orderBy('spp_payments.transaction_at', $direction))
                ->paginate($perPage)->withQueryString(),
            'showCreate' => false,
            ...$this->filterOptions($request),
        ]);
    }

    public function create(Request $request, SppPaymentService $payments): View|RedirectResponse
    {
        $editPayment = null;
        if ($request->filled('edit_payment')) {
            $editPayment = SppPayment::with(['student.schoolClass.educationUnit', 'items'])
                ->findOrFail($request->integer('edit_payment'));
            $selectedStudent = $editPayment->student;
        } elseif (! $request->filled('student_id')) {
            return redirect()->route('finance.payments.index')
                ->withErrors(['student_id' => 'Pilih siswa terlebih dahulu dari Pembayaran.']);
        } else {
            $selectedStudent = Student::with('schoolClass.educationUnit')->findOrFail($request->integer('student_id'));
        }

        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds) && ! in_array((int) $selectedStudent->schoolClass?->education_unit_id, $unitIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        if (! $editPayment && $payments->paymentPlan($selectedStudent)['max_month_count'] < 1) {
            return redirect()->route('finance.payments.index')
                ->withErrors(['student_id' => 'SPP siswa ini sudah lunas dan tidak perlu diproses kembali.']);
        }

        return view('finance.spp', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'students' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->where('students.is_active', true)
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('school_classes.education_unit_id', $unitIds))
                ->orderByRaw("CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                ->orderBy('education_units.name')
                ->orderBy('students.name')
                ->get(),
            'selectedStudent' => $selectedStudent,
            'years' => range(now()->year - 2, now()->year + 2),
            'defaultPaymentMethod' => AppSetting::valueFor('default_payment_method', 'Cash'),
            'showCreate' => true,
            'editPayment' => $editPayment,
            'returnUrl' => $request->string('return_url')->value(),
        ]);
    }

    public function quote(Request $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'month_count' => ['required', 'integer', 'min:1', 'max:120'],
            'edit_payment' => ['nullable', 'exists:spp_payments,id'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $editPayment = ! empty($validated['edit_payment'])
            ? SppPayment::findOrFail((int) $validated['edit_payment'])
            : null;
        $this->authorizeStudentAccess($request, $student);
        if ($editPayment) {
            $this->authorizePaymentAccess($request, $editPayment);
        }
        if ($editPayment && (int) $editPayment->student_id !== (int) $student->id) {
            abort(422, 'Transaksi edit tidak sesuai dengan siswa yang dipilih.');
        }

        return response()->json($editPayment
            ? $payments->quoteFromPaymentStart($editPayment, (int) $validated['month_count'])
            : $payments->quoteByMonthCount($student, (int) $validated['month_count']));
    }

    public function months(Request $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'edit_payment' => ['nullable', 'exists:spp_payments,id'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $editPayment = ! empty($validated['edit_payment'])
            ? SppPayment::findOrFail((int) $validated['edit_payment'])
            : null;
        $this->authorizeStudentAccess($request, $student);
        if ($editPayment) {
            $this->authorizePaymentAccess($request, $editPayment);
        }
        if ($editPayment && (int) $editPayment->student_id !== (int) $student->id) {
            abort(422, 'Transaksi edit tidak sesuai dengan siswa yang dipilih.');
        }

        return response()->json($editPayment
            ? $payments->paymentPlanFromPayment($editPayment)
            : $payments->paymentPlan($student));
    }

    public function store(StoreSppPaymentRequest $request, SppPaymentService $payments): RedirectResponse
    {
        $validated = $request->validated();
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $this->authorizeStudentAccess($request, $student);
        $payment = $payments->record($student, $validated);
        app(AuditLogService::class)->recordOperation(
            'payments.spp.create',
            $this->paymentAuditMetadata($payment),
            afterValues: $this->paymentAuditSnapshot($payment),
            request: $request,
            subjectType: SppPayment::class,
            subjectId: $payment->id,
            studentIds: [$student->id],
        );

        return redirect()->route('finance.spp.receipt', $payment)
            ->with('success', 'Pembayaran SPP berhasil disimpan.');
    }

    public function previewImport(PreviewSppPaymentImportRequest $request, SppPaymentImportService $importer): View
    {
        $validated = $request->validated();
        $context = [
            'unit_id' => (int) $validated['unit_id'],
            'month' => (int) $validated['month'],
            'year' => (int) $validated['year'],
        ];
        $this->authorizeUnitAccess($request, $context['unit_id']);
        $file = $request->file('file');
        $token = (string) Str::uuid();
        $path = $file->storeAs('spp-imports', $token.'.xlsx');
        $request->session()->put("spp_imports.{$token}", [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            ...$context,
        ]);

        try {
            $unit = EducationUnit::find($context['unit_id']);

            return view('finance.payments', [
                'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
                'mode' => 'import-preview',
                'importPreview' => $importer->preview(Storage::path($path), $file->getClientOriginalName(), $context),
                'importToken' => $token,
                'importContext' => [
                    'unit' => $unit?->name ?? $unit?->code,
                    'month' => $this->monthName($context['month']),
                    'year' => $context['year'],
                ],
                'importFileName' => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $exception) {
            $request->session()->forget("spp_imports.{$token}");
            Storage::delete($path);

            throw $exception;
        }
    }

    public function import(Request $request, SppPaymentImportService $importer): RedirectResponse
    {
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $stored = $request->session()->pull("spp_imports.{$validated['token']}");

        if (! $stored || ! Storage::exists($stored['path'])) {
            return redirect()->route('finance.payments.import')->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
        }

        try {
            $this->authorizeUnitAccess($request, (int) $stored['unit_id']);
            $result = $importer->import(Storage::path($stored['path']), $stored['name'], [
                'unit_id' => (int) $stored['unit_id'],
                'month' => (int) $stored['month'],
                'year' => (int) $stored['year'],
            ]);
        } finally {
            Storage::delete($stored['path']);
        }

        $message = "{$result['imported']} transaksi berhasil diimpor.";
        if ($result['duplicates']) {
            $message .= " {$result['duplicates']} transaksi duplikat dilewati.";
        }
        if ($result['failures']) {
            $message .= ' '.count($result['failures']).' transaksi gagal: '.collect($result['failures'])->pluck('message')->take(3)->implode(' ');
        }
        $unit = EducationUnit::find((int) $stored['unit_id']);
        $importResult = [
            'context_label' => collect([
                'SPP',
                $unit?->name ?? $unit?->code,
                $this->monthName((int) $stored['month']).' '.(int) $stored['year'],
            ])->filter()->implode(' • '),
            'file_name' => $stored['name'] ?? null,
            'imported' => (int) $result['imported'],
            'failed' => count($result['failures']),
            'skipped' => (int) $result['duplicates'],
        ];
        app(AuditLogService::class)->recordOperation(
            'payments.spp.import',
            [
                'file_name' => $stored['name'] ?? null,
                'unit_id' => (int) $stored['unit_id'],
                'month' => (int) $stored['month'],
                'year' => (int) $stored['year'],
                'imported' => (int) $result['imported'],
                'duplicates' => (int) $result['duplicates'],
                'failed_rows' => count($result['failures']),
            ],
            afterValues: ['result' => collect($result)->except('rows')->all()],
            request: $request,
        );

        return redirect()->route('finance.payments.import')
            ->with('success', $message)
            ->with('import_result', $importResult);
    }

    public function show(Request $request, SppPayment $sppPayment): JsonResponse
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items', 'corrections']);
        $this->authorizePaymentAccess($request, $sppPayment);

        return response()->json([
            'id' => $sppPayment->id,
            'student' => [
                'nis' => $sppPayment->student?->nis,
                'name' => $sppPayment->student?->name,
                'unit' => $sppPayment->student?->schoolClass?->educationUnit?->name,
                'class' => $sppPayment->student?->schoolClass?->name,
            ],
            'transaction_date' => $sppPayment->transaction_at->format('Y-m-d'),
            'transaction_time' => $sppPayment->transaction_at->format('H:i:s'),
            'transaction_at' => $sppPayment->transaction_at->format('d/m/Y H.i').' WIB',
            'payment_method' => $sppPayment->payment_method,
            'status' => $sppPayment->status,
            'original_amount' => $sppPayment->original_amount,
            'discount_amount' => $sppPayment->discount_amount,
            'total_amount' => $sppPayment->total_amount,
            'paid_amount' => $sppPayment->paid_amount,
            'remaining_amount' => $sppPayment->remaining_amount,
            'payment_status' => $sppPayment->payment_status,
            'items' => $sppPayment->items->map(fn ($item) => [
                'year' => $item->year,
                'month' => $item->month,
                'original_amount' => $item->original_amount,
                'discount_amount' => $item->discount_amount,
                'total_amount' => $item->total_amount,
                'paid_amount' => $item->paid_amount,
                'remaining_amount' => $item->remaining_amount,
                'payment_status' => $item->payment_status,
            ]),
            'corrections' => $sppPayment->corrections->sortByDesc('created_at')->values()->map(fn ($correction) => [
                'old_paid_amount' => $correction->old_paid_amount,
                'new_paid_amount' => $correction->new_paid_amount,
                'refund_amount' => $correction->refund_amount,
                'reason' => $correction->reason,
                'corrected_at' => $correction->created_at->format('d/m/Y H.i').' WIB',
            ]),
        ]);
    }

    public function receipt(Request $request, SppPayment $sppPayment, SppPaymentService $payments): Response
    {
        return $this->receiptPdfResponse($request, $sppPayment, $payments, 'inline');
    }

    public function downloadReceipt(Request $request, SppPayment $sppPayment, SppPaymentService $payments): Response
    {
        return $this->receiptPdfResponse($request, $sppPayment, $payments, 'attachment');
    }

    private function receiptPdfResponse(Request $request, SppPayment $sppPayment, SppPaymentService $payments, string $disposition): Response
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items']);
        $this->authorizePaymentAccess($request, $sppPayment);
        $outstandingSummary = $payments->outstandingSummaryUntilCurrent($sppPayment->student);
        $logoPath = public_path('images/logo-yayasan-mambaul-hikmah.png');
        $html = view('finance.spp-receipt-pdf', [
            'payment' => $sppPayment,
            'receiptNumber' => $this->receiptNumber($sppPayment),
            'outstandingSummary' => $outstandingSummary,
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)),
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
            'receiptSettings' => AppSetting::values(),
        ])->render();

        $dompdf = new Dompdf(new Options(['defaultFont' => 'Arial']));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'kwitansi-spp-'.$sppPayment->student?->nis.'-'.$sppPayment->id.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function proof(Request $request, SppPayment $sppPayment): StreamedResponse
    {
        $this->authorizePaymentAccess($request, $sppPayment);

        return $this->proofResponse($sppPayment->transfer_proof_path);
    }

    public function update(UpdateSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $validated = $request->validated();
        $this->authorizePaymentAccess($request, $sppPayment);
        if (array_key_exists('student_id', $validated) && (int) $validated['student_id'] !== (int) $sppPayment->student_id) {
            abort(422, 'Transaksi edit tidak sesuai dengan siswa yang dipilih.');
        }
        if ($validated['payment_method'] === 'Transfer' && ! $sppPayment->transfer_proof_path && ! $request->hasFile('transfer_proof')) {
            throw ValidationException::withMessages([
                'transfer_proof' => 'Bukti transfer wajib diunggah saat metode pembayaran diubah menjadi Transfer.',
            ]);
        }
        if ($request->hasFile('transfer_proof')) {
            $validated['transfer_proof_path'] = $request->file('transfer_proof')->store('payment-proofs', 'local');
        }
        $before = $this->paymentAuditSnapshot($sppPayment->loadMissing('items'));

        $payment = array_key_exists('month_count', $validated)
            ? $payments->updatePayment($sppPayment, $validated)
            : $payments->updateMetadata($sppPayment, $validated);
        app(AuditLogService::class)->recordOperation(
            'payments.spp.update',
            $this->paymentAuditMetadata($payment),
            beforeValues: $before,
            afterValues: $this->paymentAuditSnapshot($payment),
            request: $request,
            subjectType: SppPayment::class,
            subjectId: $payment->id,
            studentIds: [$payment->student_id],
        );

        return $this->redirectAfterMutation($request, route('finance.spp.index'))
            ->with('success', 'Transaksi pembayaran SPP berhasil diperbarui.');
    }

    public function correct(CorrectSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $this->authorizePaymentCorrectionRole($request);
        $this->authorizePaymentAccess($request, $sppPayment);
        $before = $this->paymentAuditSnapshot($sppPayment->loadMissing('items'));
        $payment = $payments->correctTransaction($sppPayment, $request->validated());
        app(AuditLogService::class)->recordOperation(
            'payments.spp.correct',
            $this->paymentAuditMetadata($payment) + ['reason' => $request->validated('reason')],
            beforeValues: $before,
            afterValues: $this->paymentAuditSnapshot($payment),
            request: $request,
            subjectType: SppPayment::class,
            subjectId: $payment->id,
            studentIds: [$payment->student_id],
        );

        return $this->redirectAfterMutation($request, route('finance.spp.index'))
            ->with('success', 'Koreksi transaksi pembayaran SPP berhasil disimpan dan tercatat dalam audit.');
    }

    public function cancel(Request $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'return_url' => ['nullable', 'string'],
        ]);
        $this->authorizePaymentCorrectionRole($request);
        $this->authorizePaymentAccess($request, $sppPayment);
        $before = $this->paymentAuditSnapshot($sppPayment->loadMissing('items'));
        $payment = $payments->cancel($sppPayment, $validated['reason']);
        app(AuditLogService::class)->recordOperation(
            'payments.spp.cancel',
            $this->paymentAuditMetadata($payment) + ['reason' => $validated['reason']],
            beforeValues: $before,
            afterValues: $this->paymentAuditSnapshot($payment),
            request: $request,
            subjectType: SppPayment::class,
            subjectId: $payment->id,
            studentIds: [$payment->student_id],
        );

        return $this->redirectAfterMutation($request, route('finance.spp.index'))
            ->with('success', 'Transaksi pembayaran SPP berhasil dibatalkan dan tagihan diperbarui.');
    }

    public function destroy(Request $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $this->authorizePaymentAccess($request, $sppPayment);
        $before = $this->paymentAuditSnapshot($sppPayment->loadMissing('items'));
        $paymentId = $sppPayment->id;
        $studentId = $sppPayment->student_id;
        $payments->delete($sppPayment);
        app(AuditLogService::class)->recordOperation(
            'payments.spp.delete',
            $this->paymentAuditMetadata($sppPayment),
            beforeValues: $before,
            request: $request,
            subjectType: SppPayment::class,
            subjectId: $paymentId,
            studentIds: [$studentId],
        );

        return $this->redirectAfterMutation($request, route('finance.spp.index'))
            ->with('success', 'Transaksi pembayaran SPP berhasil dihapus.');
    }

    private function redirectAfterMutation(Request $request, string $fallbackUrl): RedirectResponse
    {
        $returnUrl = trim($request->string('return_url')->value());

        if ($returnUrl !== '' && (str_starts_with($returnUrl, url('/')) || str_starts_with($returnUrl, '/'))) {
            return redirect()->to($returnUrl);
        }

        return redirect()->to($fallbackUrl);
    }

    private function receiptNumber(SppPayment $payment): string
    {
        return 'SPP-'.$payment->transaction_at->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '-';
    }

    private function filterOptions(Request $request): array
    {
        $unitIds = $request->user()?->accessibleUnitIds();

        return [
            'educationUnits' => EducationUnit::query()
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('id', $unitIds))
                ->orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(),
            'classes' => SchoolClass::with('educationUnit')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->select('school_classes.*')
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('school_classes.education_unit_id', $unitIds))
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('school_classes.name')
                ->get(),
            'studentOptions' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->when(is_array($unitIds), fn ($query) => $query->whereIn('school_classes.education_unit_id', $unitIds))
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('students.name')
                ->get(),
            'operators' => SppPayment::query()
                ->when(is_array($unitIds), fn ($query) => $query->whereHas('student.schoolClass', fn ($class) => $class->whereIn('education_unit_id', $unitIds)))
                ->whereNotNull('operator_name')
                ->where('operator_name', '!=', '')
                ->distinct()
                ->orderBy('operator_name')
                ->pluck('operator_name'),
        ];
    }

    private function filterDate(Request $request, string $key, bool $endOfDay = false): ?CarbonImmutable
    {
        $value = trim($request->string($key)->value());
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    return $endOfDay ? $date->endOfDay() : $date->startOfDay();
                }
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }

    private function educationUnitOrderExpression(): string
    {
        return "CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END";
    }

    private function authorizePaymentAccess(Request $request, SppPayment $payment): void
    {
        $payment->loadMissing('student.schoolClass.educationUnit');

        if (! $payment->student) {
            abort(404);
        }

        $this->authorizeStudentAccess($request, $payment->student);
    }

    private function authorizePaymentCorrectionRole(Request $request): void
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin() || $user?->isBendaharaUnit(), 403);
    }

    private function paymentAuditMetadata(SppPayment $payment): array
    {
        $payment->loadMissing('student.schoolClass.educationUnit');

        return [
            'student_id' => $payment->student_id,
            'unit_id' => $payment->student?->schoolClass?->education_unit_id,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'paid_amount' => (int) $payment->paid_amount,
        ];
    }

    private function paymentAuditSnapshot(SppPayment $payment): array
    {
        $payment->loadMissing('items');

        return [
            'id' => $payment->id,
            'student_id' => $payment->student_id,
            'transaction_at' => $payment->transaction_at?->format('Y-m-d H:i:s'),
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'paid_amount' => (int) $payment->paid_amount,
            'remaining_amount' => (int) $payment->remaining_amount,
            'payment_status' => $payment->payment_status,
            'items' => $payment->items->map(fn ($item) => [
                'year' => (int) $item->year,
                'month' => (int) $item->month,
                'paid_amount' => (int) $item->paid_amount,
                'remaining_amount' => (int) $item->remaining_amount,
            ])->values()->all(),
        ];
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        $student->loadMissing('schoolClass.educationUnit');
        $this->authorizeUnitAccess($request, $student->schoolClass?->education_unit_id);
    }

    private function authorizeUnitAccess(Request $request, ?int $unitId): void
    {
        $unitIds = $request->user()?->accessibleUnitIds();

        if (is_array($unitIds) && ! in_array((int) $unitId, $unitIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke siswa atau transaksi ini.');
        }
    }

    private function proofResponse(?string $path): StreamedResponse
    {
        if (! $path) {
            abort(404);
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        abort(404);
    }
}
