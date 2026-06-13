<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\OtherPayment;
use App\Models\SppPayment;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $transactions = $this->transactions($filters);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;

        return view('reports.index', [
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'educationUnits' => EducationUnit::where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
            'transactions' => new LengthAwarePaginator(
                $transactions->forPage($page, $perPage)->values(),
                $transactions->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            ),
            'stats' => [
                'total' => $transactions->sum('amount'),
                'spp' => $transactions->where('type', 'SPP')->sum('amount'),
                'other' => $transactions->where('type', 'Lain-lain')->sum('amount'),
                'transactions' => $transactions->count(),
                'students' => $transactions->pluck('student_id')->unique()->count(),
            ],
            'daily' => $transactions->groupBy(fn ($item) => $item['date']->format('Y-m-d'))
                ->map(fn ($items) => $items->sum('amount'))->sortKeys(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $transactions = $this->transactions($filters);
        $filename = 'laporan-pembayaran-'.Carbon::parse($filters['start_date'])->format('Ymd').'-'.Carbon::parse($filters['end_date'])->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Tanggal', 'Jenis', 'NIS', 'Nama Siswa', 'Unit', 'Kelas', 'Keterangan', 'Metode', 'Nominal']);
            foreach ($transactions as $item) {
                fputcsv($file, [
                    $item['date']->format('d/m/Y H:i'), $item['type'], $item['nis'], $item['student'],
                    $item['unit'], $item['class'], $item['description'], $item['method'], $item['amount'],
                ]);
            }
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'type' => ['nullable', 'in:spp,other'],
            'payment_method' => ['nullable', 'in:Cash,Transfer'],
            'unit_id' => ['nullable', 'exists:education_units,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->toDateString(),
            'type' => $validated['type'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ];
    }

    private function transactions(array $filters): Collection
    {
        $filterQuery = function ($query) use ($filters) {
            return $query->whereBetween('transaction_at', [$filters['start_date'].' 00:00:00', $filters['end_date'].' 23:59:59'])
                ->when($filters['payment_method'], fn ($q, $method) => $q->where('payment_method', $method))
                ->when($filters['unit_id'], fn ($q, $unit) => $q->whereHas('student.schoolClass', fn ($class) => $class->where('education_unit_id', $unit)))
                ->when($filters['search'], fn ($q, $search) => $q->whereHas('student', fn ($student) => $student->where('nis', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        };

        $spp = collect();
        if ($filters['type'] !== 'other') {
            $spp = $filterQuery(SppPayment::with('student.schoolClass.educationUnit'))->get()->map(fn ($payment) => [
                'id' => 'spp-'.$payment->id, 'student_id' => $payment->student_id, 'date' => $payment->transaction_at,
                'type' => 'SPP', 'nis' => $payment->student?->nis, 'student' => $payment->student?->name,
                'unit' => $payment->student?->schoolClass?->educationUnit?->name ?? '-', 'class' => $payment->student?->schoolClass?->name ?? '-',
                'description' => 'Pembayaran SPP', 'method' => $payment->payment_method, 'amount' => $payment->paid_amount,
            ]);
        }

        $other = collect();
        if ($filters['type'] !== 'spp') {
            $other = $filterQuery(OtherPayment::with(['student.schoolClass.educationUnit', 'feeType']))->get()->map(fn ($payment) => [
                'id' => 'other-'.$payment->id, 'student_id' => $payment->student_id, 'date' => $payment->transaction_at,
                'type' => 'Lain-lain', 'nis' => $payment->student?->nis, 'student' => $payment->student?->name,
                'unit' => $payment->student?->schoolClass?->educationUnit?->name ?? '-', 'class' => $payment->student?->schoolClass?->name ?? '-',
                'description' => $payment->feeType?->name ?? 'Pembayaran lain-lain', 'method' => $payment->payment_method, 'amount' => $payment->paid_amount,
            ]);
        }

        return $spp->concat($other)->sortByDesc('date')->values();
    }
}
