<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewOtherPaymentImportRequest;
use App\Http\Requests\StoreOtherPaymentRequest;
use App\Http\Requests\UpdateOtherPaymentRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Bill;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\LaundryPaymentService;
use App\Services\OtherPaymentImportService;
use App\Services\OtherPaymentService;
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

class OtherPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search = $request->string('search')->value();
        $section = $this->section($request);
        $dateFrom = $this->filterDate($request, 'date_from') ?? now()->startOfMonth()->startOfDay();
        $dateTo = $this->filterDate($request, 'date_to', true) ?? now()->endOfDay();
        $sort = in_array($request->string('sort')->value(), ['nis', 'name', 'unit', 'class', 'method', 'total'], true)
            ? $request->string('sort')->value()
            : 'date';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        return view('finance.other', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payments' => OtherPayment::select('other_payments.*')->with(['student.schoolClass.educationUnit', 'feeType', 'items'])
                ->when($section['key'] !== 'all', fn ($query) => $this->filterPayments($query, $section['key']))
                ->whereBetween('other_payments.transaction_at', [$dateFrom, $dateTo])
                ->when($request->filled('fee_type_id'), fn ($query) => $query->where('other_payments.fee_type_id', $request->integer('fee_type_id')))
                ->when($request->filled('payment_method'), fn ($query) => $query->where('other_payments.payment_method', $request->string('payment_method')->value()))
                ->when($request->filled('status'), fn ($query) => $query->where('other_payments.status', $request->string('status')->value()))
                ->when($request->filled('operator_name'), fn ($query) => $query->where('other_payments.operator_name', $request->string('operator_name')->value()))
                ->when($request->filled('student_id'), fn ($query) => $query->where('other_payments.student_id', $request->integer('student_id')))
                ->when($request->filled('nis'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('nis', 'like', '%'.$request->string('nis')->value().'%')))
                ->when(! $request->filled('student_id') && $request->filled('student_search'), fn ($query) => $query->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', '%'.$request->string('student_search')->value().'%')
                    ->orWhere('name', 'like', '%'.$request->string('student_search')->value().'%')))
                ->when($request->filled('unit_id'), fn ($query) => $query->whereHas('student.schoolClass', fn ($class) => $class->where('education_unit_id', $request->integer('unit_id'))))
                ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $request->integer('class_id'))))
                ->when($search, fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                    ->where('payment_method', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('feeType', fn ($feeType) => $feeType->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('nis', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('schoolClass', fn ($class) => $class
                            ->where('name', 'like', "%{$search}%")
                            ->orWhereHas('educationUnit', fn ($unit) => $unit
                                ->where('name', 'like', "%{$search}%"))))))
                ->when(in_array($sort, ['nis', 'name', 'unit', 'class'], true), fn ($query) => $query
                    ->join('students', 'students.id', '=', 'other_payments.student_id')
                    ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id'))
                ->when($sort === 'unit', fn ($query) => $query
                    ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                    ->orderBy('education_units.name', $direction))
                ->when($sort === 'class', fn ($query) => $query->orderBy('school_classes.name', $direction))
                ->when(in_array($sort, ['nis', 'name'], true), fn ($query) => $query->orderBy('students.'.$sort, $direction))
                ->when($sort === 'method', fn ($query) => $query->orderBy('other_payments.payment_method', $direction))
                ->when($sort === 'total', fn ($query) => $query->orderBy('other_payments.paid_amount', $direction))
                ->when($sort === 'date', fn ($query) => $query->orderBy('other_payments.transaction_at', $direction))
                ->paginate($perPage)->withQueryString(),
            'showCreate' => false,
            'importPreview' => null,
            'importSources' => [],
            'importMappings' => [],
            'importToken' => null,
            'feeTypes' => $this->feeTypes($section),
            'paymentSection' => $section,
            ...$this->filterOptions($section),
        ]);
    }

    public function create(Request $request, OtherPaymentService $payments)
    {
        $section = $this->section($request);
        $feeTypes = $this->feeTypes($section);

        if ($request->filled('student_id')) {
            $selectedStudent = Student::with('schoolClass.educationUnit')->findOrFail($request->integer('student_id'));
            $matchedFeeTypes = $this->matchedFeeTypesForStudent($selectedStudent, $feeTypes, $section);

            if ($matchedFeeTypes->isNotEmpty() && ! $this->hasPayableFeeTypeForStudent($selectedStudent, $matchedFeeTypes, $section, $payments)) {
                return redirect()->route('finance.payments.index')
                    ->withErrors(['student_id' => 'Pembayaran '.$section['title'].' siswa ini sudah lunas dan tidak perlu diproses kembali.']);
            }
        }

        return view('finance.other', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'academicYears' => AcademicYear::orderByDesc('name')->get(),
            'students' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->where('students.is_active', true)
                ->orderByRaw("CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                ->orderBy('education_units.name')
                ->orderBy('students.name')
                ->get(),
            'feeTypes' => $feeTypes,
            'defaultPaymentMethod' => AppSetting::valueFor('default_payment_method', 'Cash'),
            'years' => range(now()->year - 2, now()->year + 2),
            'showCreate' => true,
            'paymentSection' => $section,
        ]);
    }

    public function quote(Request $request, OtherPaymentService $payments, LaundryPaymentService $laundryPayments): JsonResponse
    {
        $section = $this->section($request);
        if (! $request->filled('student_id') && preg_match('/^([^-]+)-\s*([^-]+?)\s*-/', $request->string('student_search')->value(), $matches)) {
            $request->merge([
                'student_id' => Student::where('nis', trim($matches[2]))
                    ->whereHas('schoolClass.educationUnit', fn ($query) => $query->where('code', trim($matches[1])))
                    ->value('id'),
            ]);
        }
        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
        ];
        if ($section['key'] === 'laundry') {
            $rules['year'] = ['required', 'integer', 'between:2000,2100'];
            $rules['months'] = ['required', 'array', 'min:1'];
            $rules['months.*'] = ['integer', 'between:1,12'];
        }
        $validated = $request->validate($rules);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $feeType = $this->feeTypeForSection($validated['fee_type_id'], $section);

        if ($section['key'] === 'laundry') {
            return response()->json($laundryPayments->quote(
                $student,
                $feeType,
                (int) $validated['year'],
                $validated['months'],
            ));
        }

        return response()->json($payments->quote(
            $student,
            $feeType,
        ));
    }

    public function months(Request $request, LaundryPaymentService $payments): JsonResponse
    {
        $section = $this->section($request);
        if ($section['key'] !== 'laundry') {
            abort(404);
        }
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);

        return response()->json($payments->monthStatuses(
            $student,
            $this->feeTypeForSection($validated['fee_type_id'], $section),
            (int) $validated['year'],
        ));
    }

    public function store(StoreOtherPaymentRequest $request, OtherPaymentService $payments, LaundryPaymentService $laundryPayments): RedirectResponse
    {
        $section = $this->section($request);
        $validated = $request->validated();
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $feeType = $this->feeTypeForSection($validated['fee_type_id'], $section);
        $payment = $section['key'] === 'laundry'
            ? $laundryPayments->record($student, $feeType, $validated)
            : $payments->record($student, $feeType, $validated);

        return redirect()->route('finance.other.index', $this->sectionParams($section))
            ->with('success', 'Pembayaran '.$section['title'].' berhasil disimpan.')
            ->with('payment_action', [
                'receipt_url' => route('finance.other.receipt', $payment),
                'download_url' => route('finance.other.receipt.download', $payment),
                'back_url' => route('finance.other.index', $this->sectionParams($section)),
            ]);
    }

    public function receipt(OtherPayment $otherPayment): View
    {
        $otherPayment->load([
            'student.academicYear',
            'student.schoolClass.educationUnit',
            'feeType.academicYear',
            'items',
        ]);

        return view('finance.other-receipt', [
            'payment' => $otherPayment,
            'receiptNumber' => $this->receiptNumber($otherPayment),
            'receiptSettings' => AppSetting::values(),
            'backParams' => $this->paymentSectionParams($otherPayment),
        ]);
    }

    public function downloadReceipt(OtherPayment $otherPayment): Response
    {
        $otherPayment->load([
            'student.academicYear',
            'student.schoolClass.educationUnit',
            'feeType.academicYear',
            'items',
        ]);
        $logoPath = public_path('images/logo-yayasan-mambaul-hikmah.png');
        $html = view('finance.other-receipt-pdf', [
            'payment' => $otherPayment,
            'receiptNumber' => $this->receiptNumber($otherPayment),
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)),
            'receiptSettings' => AppSetting::values(),
        ])->render();

        $dompdf = new Dompdf(new Options(['defaultFont' => 'Arial']));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'kwitansi-'.$this->receiptPrefix($otherPayment).'-'.$otherPayment->student?->nis.'-'.$otherPayment->id.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::lower($filename).'"',
        ]);
    }

    public function show(OtherPayment $otherPayment): JsonResponse
    {
        $otherPayment->load(['student.schoolClass.educationUnit', 'feeType', 'items']);

        return response()->json([
            'id' => $otherPayment->id,
            'student_name' => $otherPayment->student?->name,
            'payment_name' => $otherPayment->feeType?->name,
            'transaction_date' => $otherPayment->transaction_at->format('Y-m-d'),
            'transaction_time' => $otherPayment->transaction_at->format('H:i:s'),
            'payment_method' => $otherPayment->payment_method,
            'status' => $otherPayment->status,
            'original_amount' => $otherPayment->original_amount,
            'discount_amount' => $otherPayment->discount_amount,
            'total_amount' => $otherPayment->total_amount,
            'paid_amount' => $otherPayment->paid_amount,
            'remaining_amount' => $otherPayment->remaining_amount,
            'payment_status' => $otherPayment->payment_status,
            'items' => $otherPayment->items->map(fn ($item) => [
                'year' => $item->year,
                'month' => $item->month,
                'original_amount' => $item->original_amount,
                'discount_amount' => $item->discount_amount,
                'total_amount' => $item->total_amount,
                'paid_amount' => $item->paid_amount,
                'remaining_amount' => $item->remaining_amount,
                'payment_status' => $item->payment_status,
            ]),
        ]);
    }

    public function update(UpdateOtherPaymentRequest $request, OtherPayment $otherPayment, OtherPaymentService $payments): RedirectResponse
    {
        $payments->updateMetadata($otherPayment, $request->validated());

        return $this->redirectAfterMutation($request, route('finance.other.index', $this->paymentSectionParams($otherPayment)))
            ->with('success', 'Transaksi pembayaran berhasil diperbarui.');
    }

    public function destroy(Request $request, OtherPayment $otherPayment, OtherPaymentService $payments, LaundryPaymentService $laundryPayments): RedirectResponse
    {
        $sectionParams = $this->paymentSectionParams($otherPayment);
        $otherPayment->loadMissing('feeType');
        if ($otherPayment->feeType?->payment_group === 'laundry') {
            $laundryPayments->delete($otherPayment);
        } else {
            $payments->delete($otherPayment);
        }

        return $this->redirectAfterMutation($request, route('finance.other.index', $sectionParams))
            ->with('success', 'Transaksi pembayaran berhasil dihapus.');
    }

    private function redirectAfterMutation(Request $request, string $fallbackUrl): RedirectResponse
    {
        $returnUrl = trim($request->string('return_url')->value());

        if ($returnUrl !== '' && (str_starts_with($returnUrl, url('/')) || str_starts_with($returnUrl, '/'))) {
            return redirect()->to($returnUrl);
        }

        return redirect()->to($fallbackUrl);
    }

    public function previewImport(PreviewOtherPaymentImportRequest $request, OtherPaymentImportService $importer): View
    {
        $section = $this->section($request);
        $token = $request->string('token')->value();
        $stored = $token ? $request->session()->get("other_payment_imports.{$token}") : null;

        if (! $stored) {
            $file = $request->file('file');
            $token = (string) Str::uuid();
            $path = $file->storeAs('other-payment-imports', $token.'.xlsx');
            $stored = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mappings' => []];
        }

        try {
            $sources = $importer->sources(Storage::path($stored['path']), $section['key']);
            $mappings = array_replace(
                $stored['mappings'] ?? [],
                is_array($request->input('mappings')) ? $request->input('mappings') : [],
            );
            foreach ($sources as $source) {
                if (! array_key_exists($source['key'], $mappings) && $source['suggested_fee_type_id']) {
                    $mappings[$source['key']] = $source['suggested_fee_type_id'];
                }
            }
            $stored['mappings'] = $mappings;
            $request->session()->put("other_payment_imports.{$token}", $stored);

            $feeTypes = $this->feeTypes($section);
            $feeTypeIds = $feeTypes->modelKeys();
            $unresolvedSources = collect($sources)
                ->filter(fn (array $source) => ! in_array((int) ($mappings[$source['key']] ?? 0), $feeTypeIds, true))
                ->values()
                ->all();

            return view('finance.payments', [
                'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
                'mode' => 'import-preview',
                'importPreview' => $importer->preview(Storage::path($stored['path']), $mappings, $stored['name'], $section['key']),
                'importSources' => $sources,
                'importMappings' => $mappings,
                'importToken' => $token,
                'importFeeTypes' => $feeTypes,
                'importUnresolvedSources' => $unresolvedSources,
                'importSection' => $section,
                'importMappingAction' => route('finance.other.import.preview', $this->sectionParams($section)),
                'importAction' => route('finance.other.import', $this->sectionParams($section)),
                'importPreviewType' => 'other',
            ]);
        } catch (\Throwable $exception) {
            if (! $request->string('token')->value()) {
                $request->session()->forget("other_payment_imports.{$token}");
                Storage::delete($stored['path']);
            }

            throw $exception;
        }
    }

    public function import(Request $request, OtherPaymentImportService $importer): RedirectResponse
    {
        $section = $this->section($request);
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $stored = $request->session()->pull("other_payment_imports.{$validated['token']}");

        if (! $stored || ! Storage::exists($stored['path'])) {
            return redirect()->route('finance.payments.import')->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
        }

        try {
            $result = $importer->import(Storage::path($stored['path']), $stored['mappings'] ?? [], $stored['name'], $section['key']);
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

    private function section(Request $request): array
    {
        return match ($request->string('category')->value()) {
            'daftar-ulang' => [
                'key' => 'daftar-ulang', 'title' => 'Daftar Ulang',
                'description' => 'Kelola seluruh transaksi pembayaran daftar ulang siswa.',
            ],
            'laundry' => [
                'key' => 'laundry', 'title' => 'Laundry',
                'description' => 'Kelola seluruh transaksi pembayaran laundry siswa.',
            ],
            default => [
                'key' => 'lain-lain', 'title' => 'Lain-lain',
                'description' => 'Lihat seluruh transaksi pembayaran selain SPP, daftar ulang, dan laundry.',
            ],
        };
    }

    private function feeTypes(array $section)
    {
        return FeeType::with(['educationUnit', 'schoolClass', 'academicYear'])
            ->where('is_active', true)
            ->paymentGroup($section['key'])
            ->orderBy('name')->get();
    }

    private function matchedFeeTypesForStudent(Student $student, $feeTypes, array $section)
    {
        return $feeTypes->filter(fn (FeeType $feeType) => $feeType->matchesSchoolClass($student->schoolClass)
            && ($section['key'] === 'daftar-ulang' || ! $feeType->academic_year_id || $feeType->academic_year_id === $student->academic_year_id));
    }

    private function hasPayableFeeTypeForStudent(Student $student, $feeTypes, array $section, OtherPaymentService $payments): bool
    {
        if ($feeTypes->contains(fn (FeeType $feeType) => ! $feeType->creates_bill)) {
            return true;
        }

        if ($section['key'] === 'daftar-ulang') {
            foreach ($feeTypes as $feeType) {
                try {
                    if (($payments->quote($student, $feeType)['remaining_amount'] ?? 0) > 0) {
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

    private function filterOptions(array $section): array
    {
        $operatorQuery = OtherPayment::query()
            ->whereNotNull('operator_name')
            ->where('operator_name', '!=', '');
        if ($section['key'] !== 'all') {
            $this->filterPayments($operatorQuery, $section['key']);
        }

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
            'operators' => $operatorQuery->distinct()->orderBy('operator_name')->pluck('operator_name'),
        ];
    }

    private function filterPayments($query, string $section)
    {
        return $query->whereHas('feeType', fn ($feeType) => $feeType
            ->paymentGroup($section));
    }

    private function feeTypeForSection(int $feeTypeId, array $section): FeeType
    {
        $feeType = FeeType::whereKey($feeTypeId)
            ->where('is_active', true)
            ->paymentGroup($section['key'])
            ->first();

        if (! $feeType) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Kategori pembayaran tidak sesuai dengan menu '.$section['title'].'.',
            ]);
        }

        return $feeType;
    }

    private function sectionParams(array $section): array
    {
        return $section['key'] === 'lain-lain' ? [] : ['category' => $section['key']];
    }

    private function paymentSectionParams(OtherPayment $payment): array
    {
        $payment->loadMissing('feeType');
        $group = $payment->feeType?->payment_group;

        return in_array($group, ['daftar-ulang', 'laundry'], true) ? ['category' => $group] : [];
    }

    private function receiptNumber(OtherPayment $payment): string
    {
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
