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
            ...$this->filterOptions(),
        ]);
    }

    public function create(Request $request, SppPaymentService $payments): View|RedirectResponse
    {
        if (! $request->filled('student_id')) {
            return redirect()->route('finance.payments.index')
                ->withErrors(['student_id' => 'Pilih siswa terlebih dahulu dari Pembayaran.']);
        }

        $selectedStudent = Student::with('schoolClass.educationUnit')->findOrFail($request->integer('student_id'));
        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds) && ! in_array((int) $selectedStudent->schoolClass?->education_unit_id, $unitIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        if ($payments->paymentPlan($selectedStudent)['max_month_count'] < 1) {
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
        ]);
    }

    public function quote(Request $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'month_count' => ['required', 'integer', 'min:1', 'max:120'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);

        return response()->json($payments->quoteByMonthCount($student, (int) $validated['month_count']));
    }

    public function months(Request $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);

        return response()->json($payments->paymentPlan($student));
    }

    public function store(StoreSppPaymentRequest $request, SppPaymentService $payments): RedirectResponse
    {
        $validated = $request->validated();
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $payment = $payments->record($student, $validated);

        return redirect()->route('finance.spp.receipt', $payment)
            ->with('success', 'Pembayaran SPP berhasil disimpan.');
    }

    public function previewImport(PreviewSppPaymentImportRequest $request, SppPaymentImportService $importer): View
    {
        $file = $request->file('file');
        $token = (string) Str::uuid();
        $path = $file->storeAs('spp-imports', $token.'.xlsx');
        $request->session()->put("spp_imports.{$token}", [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);

        try {
            return view('finance.payments', [
                'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
                'mode' => 'import-preview',
                'importPreview' => $importer->preview(Storage::path($path), $file->getClientOriginalName()),
                'importToken' => $token,
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
            $result = $importer->import(Storage::path($stored['path']), $stored['name']);
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

        return redirect()->route('finance.payments.import')->with('success', $message);
    }

    public function show(SppPayment $sppPayment): JsonResponse
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items', 'corrections']);

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

    public function receipt(SppPayment $sppPayment, SppPaymentService $payments): View
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items']);
        $outstandingSummary = $payments->outstandingSummaryUntilCurrent($sppPayment->student);
        $rootIdentityId = $sppPayment->student?->identity_student_id ?: $sppPayment->student_id;
        $otherSppStudents = Student::select('students.*')->with('schoolClass.educationUnit')
            ->where('students.is_active', true)
            ->where('students.id', '!=', $sppPayment->student_id)
            ->where(fn ($query) => $query
                ->where('students.id', $rootIdentityId)
                ->orWhere('students.identity_student_id', $rootIdentityId))
            ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
            ->orderByRaw($this->educationUnitOrderExpression())
            ->orderBy('school_classes.name')
            ->select('students.*')
            ->get();

        return view('finance.spp-receipt', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payment' => $sppPayment,
            'otherSppStudents' => $otherSppStudents,
            'outstandingSummary' => $outstandingSummary,
            'receiptNumber' => $this->receiptNumber($sppPayment),
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
            'receiptSettings' => AppSetting::values(),
        ]);
    }

    public function downloadReceipt(SppPayment $sppPayment, SppPaymentService $payments): Response
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items']);
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
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function update(UpdateSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->updateMetadata($sppPayment, $request->validated());

        return $this->redirectAfterMutation($request, route('finance.spp.index'))
            ->with('success', 'Transaksi pembayaran SPP berhasil diperbarui.');
    }

    public function correct(CorrectSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->correctPaidAmount($sppPayment, $request->validated());

        return redirect()->route('finance.spp.index')->with('success', 'Koreksi nominal pembayaran berhasil disimpan dan tercatat dalam histori.');
    }

    public function destroy(Request $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->delete($sppPayment);

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

    private function filterOptions(): array
    {
        return [
            'educationUnits' => EducationUnit::orderByRaw($this->educationUnitOrderExpression())->orderBy('name')->get(),
            'classes' => SchoolClass::with('educationUnit')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->select('school_classes.*')
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('school_classes.name')
                ->get(),
            'studentOptions' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->orderByRaw($this->educationUnitOrderExpression())
                ->orderBy('students.name')
                ->get(),
            'operators' => SppPayment::query()
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
}
