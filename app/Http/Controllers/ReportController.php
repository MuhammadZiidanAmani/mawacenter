<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\ReportQueryService;
use App\Support\SimpleXlsxWriter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private ReportQueryService $reports) {}

    public function index(Request $request): View
    {
        return $this->render($request, 'transactions');
    }

    public function redirectIndex(): RedirectResponse
    {
        return redirect()->route('reports.transactions');
    }

    public function transactions(Request $request): View
    {
        return $this->render($request, 'transactions');
    }

    public function monthlySpp(Request $request): View
    {
        return $this->render($request, 'monthly-spp');
    }

    public function outstandingSpp(Request $request): View
    {
        return $this->render($request, 'outstanding-spp');
    }

    public function yearlySpp(Request $request): View
    {
        return $this->render($request, 'yearly-spp');
    }

    public function unitRecap(Request $request): View
    {
        return $this->render($request, 'unit-recap');
    }

    public function exportXlsx(Request $request, string $report): BinaryFileResponse
    {
        $report = $this->normalizeReport($report);
        $data = $this->reportData($request, $report, true);
        $filename = $this->exportFilename($data['definition']['title'], 'xlsx');
        $path = storage_path('app/reports/'.$filename);

        (new SimpleXlsxWriter)->write($path, $this->xlsxSheets($data));

        return response()
            ->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request, string $report): Response
    {
        $report = $this->normalizeReport($report);
        $data = $this->reportData($request, $report, true);
        $filename = $this->exportFilename($data['definition']['title'], 'pdf');
        $view = 'reports.pdf.'.$report;
        $html = view($view, $data)->render();

        $dompdf = new Dompdf(new Options(['defaultFont' => 'DejaVu Sans']));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $report === 'yearly-spp' ? 'landscape' : 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function legacyExport(Request $request): BinaryFileResponse
    {
        return $this->exportXlsx($request, 'transactions');
    }

    private function render(Request $request, string $report): View
    {
        return view($this->definition($report)['view'], $this->reportData($request, $report));
    }

    private function reportData(Request $request, string $report, bool $allRows = false): array
    {
        $definition = $this->definition($report);
        $filters = $this->reports->filters($request, $report);
        $options = $this->reports->options();
        $unitIds = $request->user()?->accessibleUnitIds();
        if (is_array($unitIds)) {
            $filters['unit_ids'] = $unitIds;
            if ($filters['unit_id'] && ! in_array((int) $filters['unit_id'], $unitIds, true)) {
                $filters['unit_id'] = null;
            }
            $options['educationUnits'] = $options['educationUnits']->whereIn('id', $unitIds)->values();
            $options['classes'] = $options['classes']->whereIn('education_unit_id', $unitIds)->values();
        }
        $result = $this->result($report, $filters);
        $columns = $this->columns($report, $result);
        $rows = $this->searchRows($result['rows'], $filters['search'] ?? null);
        $paginatedRows = $allRows ? null : $this->paginateRows($rows, $request);

        return [
            'activeAcademicYear' => $options['activeAcademicYear'],
            'definition' => $definition,
            'reportKey' => $report,
            'activeReportMenu' => $definition['menu'],
            'filters' => $filters,
            'filterFields' => $this->filterFields($report, $filters, $options),
            'columns' => $columns,
            'rows' => $allRows ? $rows->values() : collect($paginatedRows->items()),
            'rowsPaginator' => $paginatedRows,
            'summaryCards' => $result['summaryCards'],
            'summaryColumns' => $result['summaryColumns'],
            'summaryRows' => $result['summaryRows'],
            'resetRoute' => route($definition['route']),
            'xlsxUrl' => route('reports.export.xlsx', ['report' => $this->reportSlug($report)] + $request->query()),
            'pdfUrl' => route('reports.export.pdf', ['report' => $this->reportSlug($report)] + $request->query()),
            'options' => $options,
        ];
    }

    private function result(string $report, array $filters): array
    {
        return match ($report) {
            'monthly-spp' => $this->reports->monthlySpp($filters),
            'outstanding-spp' => $this->reports->outstandingSpp($filters),
            'yearly-spp' => $this->reports->yearlySpp($filters),
            'unit-recap' => $this->reports->unitRecap($filters),
            default => $this->reports->transactions($filters),
        };
    }

    private function definition(string $report): array
    {
        $definitions = [
            'transactions' => [
                'title' => 'Laporan Transaksi',
                'description' => 'Rekap penerimaan dan riwayat transaksi berdasarkan periode, unit, kategori, metode bayar, dan siswa.',
                'route' => 'reports.transactions',
                'view' => 'reports.transactions',
                'menu' => 'transactions',
            ],
            'monthly-spp' => [
                'title' => 'SPP Perbulan',
                'description' => 'Pantau status SPP siswa pada bulan tertentu: sudah bayar, sebagian, atau belum bayar.',
                'route' => 'reports.monthly_spp',
                'view' => 'reports.monthly-spp',
                'menu' => 'monthly-spp',
            ],
            'outstanding-spp' => [
                'title' => 'SPP Belum Bayar',
                'description' => 'Daftar tunggakan SPP lintas bulan untuk kebutuhan penagihan per unit, kelas, dan siswa.',
                'route' => 'reports.outstanding_spp',
                'view' => 'reports.outstanding-spp',
                'menu' => 'outstanding-spp',
            ],
            'yearly-spp' => [
                'title' => 'SPP Tahun Pelajaran',
                'description' => 'Rekap status SPP satu tahun pelajaran dari Juli sampai Juni.',
                'route' => 'reports.yearly_spp',
                'view' => 'reports.yearly-spp',
                'menu' => 'yearly-spp',
            ],
            'unit-recap' => [
                'title' => 'Rekap Per Unit',
                'description' => 'Ringkasan total penerimaan dan tunggakan SPP berdasarkan unit pendidikan.',
                'route' => 'reports.unit_recap',
                'view' => 'reports.unit-recap',
                'menu' => 'unit-recap',
            ],
        ];

        abort_unless(isset($definitions[$report]), 404);

        return $definitions[$report];
    }

    private function normalizeReport(string $report): string
    {
        return [
            'transaksi' => 'transactions',
            'spp-perbulan' => 'monthly-spp',
            'spp-belum-bayar' => 'outstanding-spp',
            'spp-tahun-pelajaran' => 'yearly-spp',
            'rekap-unit' => 'unit-recap',
        ][$report] ?? $report;
    }

    private function reportSlug(string $report): string
    {
        return [
            'transactions' => 'transaksi',
            'monthly-spp' => 'spp-perbulan',
            'outstanding-spp' => 'spp-belum-bayar',
            'yearly-spp' => 'spp-tahun-pelajaran',
            'unit-recap' => 'rekap-unit',
        ][$report] ?? $report;
    }

    private function columns(string $report, array $result): array
    {
        if ($report === 'yearly-spp') {
            $monthColumns = collect($result['months'] ?? [])->map(fn (array $month) => [
                'key' => 'm_'.$month['month'].'_'.$month['year'],
                'label' => $month['label'],
            ])->all();

            return array_merge([
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
            ], $monthColumns, [
                ['key' => 'total_paid', 'label' => 'Total Terbayar', 'type' => 'money'],
                ['key' => 'remaining', 'label' => 'Sisa', 'type' => 'money'],
            ]);
        }

        return match ($report) {
            'monthly-spp' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
                ['key' => 'month', 'label' => 'Bulan'],
                ['key' => 'billed', 'label' => 'Tagihan SPP', 'type' => 'money'],
                ['key' => 'paid', 'label' => 'Terbayar', 'type' => 'money'],
                ['key' => 'remaining', 'label' => 'Sisa', 'type' => 'money'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'outstanding-spp' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
                ['key' => 'months', 'label' => 'Bulan Tunggakan'],
                ['key' => 'remaining', 'label' => 'Total Tunggakan', 'type' => 'money'],
                ['key' => 'actions', 'label' => 'Aksi', 'type' => 'actions'],
            ],
            'unit-recap' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit Pendidikan', 'class' => 'name'],
                ['key' => 'spp', 'label' => 'SPP', 'type' => 'money'],
                ['key' => 'daftar_ulang', 'label' => 'Daftar Ulang', 'type' => 'money'],
                ['key' => 'laundry', 'label' => 'Laundry', 'type' => 'money'],
                ['key' => 'lain_lain', 'label' => 'Lain-lain', 'type' => 'money'],
                ['key' => 'total', 'label' => 'Total Penerimaan', 'type' => 'money'],
                ['key' => 'outstanding_spp', 'label' => 'Total Tunggakan SPP', 'type' => 'money'],
            ],
            default => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'date', 'label' => 'Tanggal'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
                ['key' => 'type', 'label' => 'Kategori'],
                ['key' => 'method', 'label' => 'Cara Bayar'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['key' => 'amount', 'label' => 'Nominal', 'type' => 'money'],
            ],
        };
    }

    private function filterFields(string $report, array $filters, array $options): array
    {
        $dateFields = [
            ['name' => 'date_from', 'label' => 'Tanggal Dari', 'type' => 'date', 'value' => $filters['date_from']->format('Y-m-d')],
            ['name' => 'date_to', 'label' => 'Tanggal Sampai', 'type' => 'date', 'value' => $filters['date_to']->format('Y-m-d')],
        ];
        $unitClassSearch = [
            ['name' => 'unit_id', 'label' => 'Unit Pendidikan', 'type' => 'select', 'value' => $filters['unit_id'], 'options' => $this->unitOptions($options)],
            ['name' => 'class_id', 'label' => 'Kelas', 'type' => 'select', 'value' => $filters['class_id'], 'options' => $this->classOptions($options), 'classFilter' => true],
            ['name' => 'student_search', 'label' => 'Cari Siswa', 'type' => 'search', 'value' => $filters['student_search'], 'placeholder' => 'Nama atau NIS...'],
        ];
        $yearField = ['name' => 'academic_year_id', 'label' => 'Tahun Pelajaran', 'type' => 'select', 'value' => $filters['academic_year_id'], 'options' => $this->academicYearOptions($options)];

        return match ($report) {
            'monthly-spp' => array_merge([
                $yearField,
                ['name' => 'month', 'label' => 'Bulan', 'type' => 'select', 'value' => $filters['month'], 'options' => $this->monthOptions()],
            ], $unitClassSearch, [
                ['name' => 'spp_status', 'label' => 'Status SPP', 'type' => 'select', 'value' => $filters['spp_status'], 'options' => [
                    '' => 'Semua',
                    'paid' => 'Sudah Bayar',
                    'partial' => 'Sebagian',
                    'unpaid' => 'Belum Bayar',
                ]],
            ]),
            'outstanding-spp' => array_merge([
                $yearField,
                ['name' => 'until_month', 'label' => 'Sampai Bulan', 'type' => 'select', 'value' => $filters['until_month'], 'options' => $this->monthOptions()],
            ], $unitClassSearch),
            'yearly-spp' => array_merge([$yearField], $unitClassSearch),
            'unit-recap' => array_merge($dateFields, [$yearField]),
            default => array_merge($dateFields, $unitClassSearch, [
                ['name' => 'type', 'label' => 'Kategori Pembayaran', 'type' => 'select', 'value' => $filters['type'], 'options' => [
                    '' => 'Semua',
                    'spp' => 'SPP',
                    'daftar-ulang' => 'Daftar Ulang',
                    'laundry' => 'Laundry',
                    'lain-lain' => 'Lain-lain',
                ]],
                ['name' => 'payment_method', 'label' => 'Cara Bayar', 'type' => 'select', 'value' => $filters['payment_method'], 'options' => [
                    '' => 'Semua',
                    'Cash' => 'Cash',
                    'Transfer' => 'Transfer',
                ]],
                ['name' => 'payment_status', 'label' => 'Status', 'type' => 'select', 'value' => $filters['payment_status'], 'options' => [
                    '' => 'Semua',
                    'Diterima' => 'Diterima',
                    'Pending' => 'Pending',
                ]],
                ['name' => 'operator_name', 'label' => 'Petugas', 'type' => 'select', 'value' => $filters['operator_name'], 'options' => $this->operatorOptions($options)],
            ]),
        };
    }

    private function paginateRows(Collection $rows, Request $request): LengthAwarePaginator
    {
        $perPage = $this->reportPerPage($request);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $perPage === 'all' ? $rows->values() : $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage === 'all' ? max(1, $rows->count()) : $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    private function reportPerPage(Request $request): int|string
    {
        $value = $request->string('per_page')->value();
        if ($value === 'all') {
            return 'all';
        }

        return in_array((int) $value, [10, 25, 50, 100, 500], true) ? (int) $value : 10;
    }

    private function searchRows(Collection $rows, ?string $search): Collection
    {
        if (! $search) {
            return $rows->values();
        }

        $needle = Str::lower($search);

        return $rows->filter(fn (array $row) => Str::contains(Str::lower(implode(' ', array_filter($row, 'is_scalar'))), $needle))->values();
    }

    private function xlsxSheets(array $data): array
    {
        return [
            [
                'name' => 'Data',
                'rows' => $this->tableRowsForExport($data['columns'], $data['rows']),
            ],
            [
                'name' => 'Ringkasan',
                'rows' => $this->summaryRowsForExport($data),
            ],
        ];
    }

    private function tableRowsForExport(array $columns, Collection $rows): array
    {
        $exportColumns = collect($columns)->reject(fn (array $column) => ($column['type'] ?? null) === 'actions')->values();
        $result = [$exportColumns->pluck('label')->all()];

        foreach ($rows as $index => $row) {
            $result[] = $exportColumns->map(function (array $column) use ($row, $index) {
                if ($column['key'] === 'no') {
                    return $index + 1;
                }

                return $row[$column['key']] ?? '';
            })->all();
        }

        return $result;
    }

    private function summaryRowsForExport(array $data): array
    {
        $rows = [['Ringkasan', 'Nilai']];
        foreach ($data['summaryCards'] as $card) {
            $rows[] = [$card['label'], $card['value']];
        }

        if ($data['summaryColumns'] && $data['summaryRows']->isNotEmpty()) {
            $rows[] = [];
            $rows[] = collect($data['summaryColumns'])->pluck('label')->all();
            foreach ($data['summaryRows'] as $index => $row) {
                $rows[] = collect($data['summaryColumns'])->map(fn (array $column) => $column['key'] === 'no' ? $index + 1 : ($row[$column['key']] ?? ''))->all();
            }
        }

        return $rows;
    }

    private function exportFilename(string $title, string $extension): string
    {
        return Str::slug($title).'-'.now()->format('Ymd-His').'.'.$extension;
    }

    private function unitOptions(array $options): array
    {
        return ['' => 'Semua'] + $options['educationUnits']->mapWithKeys(fn ($unit) => [$unit->id => $unit->code])->all();
    }

    private function classOptions(array $options): array
    {
        return ['' => 'Semua'] + $options['classes']->mapWithKeys(fn ($class) => [$class->id => [
            'label' => $class->name,
            'unit_id' => $class->education_unit_id,
        ]])->all();
    }

    private function academicYearOptions(array $options): array
    {
        return $options['academicYears']->mapWithKeys(fn (AcademicYear $year) => [$year->id => $year->name])->all();
    }

    private function monthOptions(): array
    {
        return collect(ReportQueryService::MONTHS)->mapWithKeys(fn ($label, $month) => [$month => $label])->all();
    }

    private function operatorOptions(array $options): array
    {
        return ['' => 'Semua'] + $options['operators']->mapWithKeys(fn ($operator) => [$operator => $operator])->all();
    }
}
