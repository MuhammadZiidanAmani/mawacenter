<?php

namespace App\Http\Controllers;

use App\Http\Requests\SppSelectionRequest;
use App\Http\Requests\CorrectSppPaymentRequest;
use App\Http\Requests\PreviewSppPaymentImportRequest;
use App\Http\Requests\StoreSppPaymentRequest;
use App\Http\Requests\UpdateSppPaymentRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\SppPayment;
use App\Models\Student;
use App\Services\SppPaymentService;
use App\Services\SppPaymentImportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SppPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array($request->integer('per_page'), [10, 25, 50, 100]) ? $request->integer('per_page') : 10;
        $search = $request->string('search')->value();

        return view('finance.spp', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'payments' => SppPayment::with(['student.schoolClass.educationUnit', 'items', 'corrections'])
                ->when($search, fn ($query) => $query->whereHas('student', fn ($student) => $student
                    ->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('schoolClass', fn ($class) => $class
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('educationUnit', fn ($unit) => $unit->where('name', 'like', "%{$search}%")))))
                ->latest('transaction_at')->paginate($perPage)->withQueryString(),
            'showCreate' => false,
            'importPreview' => null,
            'importToken' => null,
        ]);
    }

    public function create(): View
    {
        return view('finance.spp', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'students' => Student::with('schoolClass.educationUnit')->where('is_active', true)->orderBy('name')->get(),
            'years' => range(now()->year - 2, now()->year + 2),
            'defaultPaymentMethod' => AppSetting::where('key', 'default_payment_method')->value('value') ?? 'Cash',
            'showCreate' => true,
        ]);
    }

    public function quote(SppSelectionRequest $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validated();
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);

        return response()->json($payments->quote($student, (int) $validated['year'], $validated['months']));
    }

    public function months(Request $request, SppPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);

        return response()->json($payments->monthStatuses($student, (int) $validated['year']));
    }

    public function store(StoreSppPaymentRequest $request, SppPaymentService $payments): RedirectResponse
    {
        $validated = $request->validated();
        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $payments->record($student, $validated);

        return redirect()->route('finance.spp.index')->with('success', 'Pembayaran SPP berhasil disimpan.');
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
            return view('finance.spp', [
                'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
                'payments' => SppPayment::with(['student.schoolClass.educationUnit', 'items', 'corrections'])
                    ->latest('transaction_at')->paginate(10),
                'showCreate' => false,
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
            return redirect()->route('finance.spp.index')->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
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

        return redirect()->route('finance.spp.index')->with('success', $message);
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
            'transaction_at' => $sppPayment->transaction_at->format('d/m/Y H:i:s'),
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
                'corrected_at' => $correction->created_at->format('d/m/Y H:i:s'),
            ]),
        ]);
    }

    public function receipt(SppPayment $sppPayment): Response
    {
        $sppPayment->load(['student.schoolClass.educationUnit', 'items']);

        $receiptNumber = 'SPP-'.$sppPayment->transaction_at->format('Ymd').'-'.str_pad((string) $sppPayment->id, 6, '0', STR_PAD_LEFT);
        $logoPath = public_path('images/logo-yayasan-mambaul-hikmah.png');
        $logo = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        $html = view('finance.spp-receipt-pdf', [
            'payment' => $sppPayment,
            'receiptNumber' => $receiptNumber,
            'logo' => $logo,
            'months' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
            'receiptSettings' => AppSetting::values(),
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'kwitansi_spp_'.$sppPayment->student?->nis.'_'.$sppPayment->id.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function update(UpdateSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->updateMetadata($sppPayment, $request->validated());

        return redirect()->route('finance.spp.index')->with('success', 'Transaksi pembayaran SPP berhasil diperbarui.');
    }

    public function correct(CorrectSppPaymentRequest $request, SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->correctPaidAmount($sppPayment, $request->validated());

        return redirect()->route('finance.spp.index')->with('success', 'Koreksi nominal pembayaran berhasil disimpan dan tercatat dalam histori.');
    }

    public function destroy(SppPayment $sppPayment, SppPaymentService $payments): RedirectResponse
    {
        $payments->delete($sppPayment);

        return redirect()->route('finance.spp.index')->with('success', 'Transaksi pembayaran SPP berhasil dihapus.');
    }

}
