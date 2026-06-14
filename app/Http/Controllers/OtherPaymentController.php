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
use Illuminate\Validation\ValidationException;

class OtherPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100]) ? $request->integer('per_page') : 10;
        $search = $request->string('search')->value();
        $section = $this->section($request);

        return view('finance.other', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payments' => OtherPayment::with(['student.schoolClass.educationUnit', 'feeType'])
                ->when($section['key'] !== 'all', fn ($query) => $this->filterPayments($query, $section['key']))
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
                ->latest('transaction_at')->paginate($perPage)->withQueryString(),
            'showCreate' => false,
            'importPreview' => null,
            'importSources' => [],
            'importMappings' => [],
            'importToken' => null,
            'feeTypes' => $this->feeTypes($section),
            'paymentSection' => $section,
        ]);
    }

    public function create(Request $request)
    {
        $section = $this->section($request);

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
            'feeTypes' => $this->feeTypes($section),
            'defaultPaymentMethod' => AppSetting::where('key', 'default_payment_method')->value('value') ?? 'Cash',
            'showCreate' => true,
            'paymentSection' => $section,
        ]);
    }

    public function quote(Request $request, OtherPaymentService $payments): JsonResponse
    {
        $section = $this->section($request);
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
        ]);

        return response()->json($payments->quote(
            Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']),
            $this->feeTypeForSection($validated['fee_type_id'], $section),
        ));
    }

    public function store(StoreOtherPaymentRequest $request, OtherPaymentService $payments): RedirectResponse
    {
        $section = $this->section($request);
        $validated = $request->validated();
        $payments->record(
            Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']),
            $this->feeTypeForSection($validated['fee_type_id'], $section),
            $validated,
        );

        return redirect()->route('finance.other.index', $this->sectionParams($section))->with('success', 'Pembayaran '.$section['title'].' berhasil disimpan.');
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
                    ->when($section['key'] !== 'all', fn ($query) => $this->filterPayments($query, $section['key']))
                    ->latest('transaction_at')->paginate(10),
                'showCreate' => false,
                'importPreview' => $importer->preview(Storage::path($stored['path']), $mappings, $stored['name'], $section['key']),
                'importSources' => $sources,
                'importMappings' => $mappings,
                'importToken' => $token,
                'feeTypes' => $this->feeTypes($section),
                'paymentSection' => $section,
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
            return redirect()->route('finance.other.index', $this->sectionParams($section))->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
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

        return redirect()->route('finance.other.index', $this->sectionParams($section))->with('success', $message);
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
                'fee_type_id' => 'Jenis pembayaran tidak sesuai dengan menu '.$section['title'].'.',
            ]);
        }

        return $feeType;
    }

    private function sectionParams(array $section): array
    {
        return $section['key'] === 'lain-lain' ? [] : ['category' => $section['key']];
    }
}
