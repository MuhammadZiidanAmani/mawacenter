<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewOtherPaymentImportRequest;
use App\Http\Requests\StoreOtherPaymentRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Student;
use App\Services\OtherPaymentImportService;
use App\Services\OtherPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OtherPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100]) ? $request->integer('per_page') : 10;
        $search = $request->string('search')->value();

        return view('finance.other', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payments' => OtherPayment::with(['student.schoolClass.educationUnit', 'feeType'])
                ->when($search, fn ($query) => $query
                    ->where('payment_method', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('feeType', fn ($feeType) => $feeType->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('nis', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('schoolClass', fn ($class) => $class
                            ->where('name', 'like', "%{$search}%")
                            ->orWhereHas('educationUnit', fn ($unit) => $unit
                                ->where('name', 'like', "%{$search}%")))))
                ->latest('transaction_at')->paginate($perPage)->withQueryString(),
            'showCreate' => false,
            'importPreview' => null,
            'importSources' => [],
            'importMappings' => [],
            'importToken' => null,
            'feeTypes' => FeeType::with(['educationUnit', 'schoolClass'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('finance.other', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'students' => Student::select('students.*')->with('schoolClass.educationUnit')
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                ->where('students.is_active', true)
                ->orderByRaw("CASE education_units.code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                ->orderBy('education_units.name')
                ->orderBy('students.name')
                ->get(),
            'feeTypes' => FeeType::with(['educationUnit', 'schoolClass'])->where('is_active', true)->orderBy('name')->get(),
            'defaultPaymentMethod' => AppSetting::where('key', 'default_payment_method')->value('value') ?? 'Cash',
            'showCreate' => true,
        ]);
    }

    public function quote(Request $request, OtherPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
        ]);

        return response()->json($payments->quote(
            Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']),
            FeeType::findOrFail($validated['fee_type_id']),
        ));
    }

    public function store(StoreOtherPaymentRequest $request, OtherPaymentService $payments): RedirectResponse
    {
        $validated = $request->validated();
        $payments->record(
            Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']),
            FeeType::findOrFail($validated['fee_type_id']),
            $validated,
        );

        return redirect()->route('finance.other.index')->with('success', 'Pembayaran lain-lain berhasil disimpan.');
    }

    public function previewImport(PreviewOtherPaymentImportRequest $request, OtherPaymentImportService $importer): View
    {
        $token = $request->string('token')->value();
        $stored = $token ? $request->session()->get("other_payment_imports.{$token}") : null;

        if (! $stored) {
            $file = $request->file('file');
            $token = (string) Str::uuid();
            $path = $file->storeAs('other-payment-imports', $token.'.xlsx');
            $stored = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mappings' => []];
        }

        try {
            $sources = $importer->sources(Storage::path($stored['path']));
            $mappings = $request->input('mappings', $stored['mappings'] ?? []);
            foreach ($sources as $source) {
                if (! array_key_exists($source['key'], $mappings) && $source['suggested_fee_type_id']) {
                    $mappings[$source['key']] = $source['suggested_fee_type_id'];
                }
            }
            $stored['mappings'] = $mappings;
            $request->session()->put("other_payment_imports.{$token}", $stored);

            return view('finance.other', [
                'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
                'payments' => OtherPayment::with(['student.schoolClass.educationUnit', 'feeType'])
                    ->latest('transaction_at')->paginate(10),
                'showCreate' => false,
                'importPreview' => $importer->preview(Storage::path($stored['path']), $mappings, $stored['name']),
                'importSources' => $sources,
                'importMappings' => $mappings,
                'importToken' => $token,
                'feeTypes' => FeeType::with(['educationUnit', 'schoolClass'])->where('is_active', true)->orderBy('name')->get(),
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
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $stored = $request->session()->pull("other_payment_imports.{$validated['token']}");

        if (! $stored || ! Storage::exists($stored['path'])) {
            return redirect()->route('finance.other.index')->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
        }

        try {
            $result = $importer->import(Storage::path($stored['path']), $stored['mappings'] ?? [], $stored['name']);
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

        return redirect()->route('finance.other.index')->with('success', $message);
    }
}
