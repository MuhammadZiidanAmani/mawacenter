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
        ini_set('memory_limit', '512M');

        $report = $this->normalizeReport($report);
        $data = $this->reportData($request, $report, true);
        $filename = $this->exportFilename($data['definition']['title'], 'pdf');
        $view = 'reports.pdf.'.$report;
        $html = view($view, $data)->render();

        $dompdf = new Dompdf(new Options(['defaultFont' => 'DejaVu Sans']));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', in_array($report, ['monthly-spp', 'yearly-spp'], true) ? 'landscape' : 'portrait');
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
            $options['feeTypes'] = $options['feeTypes']->whereIn('education_unit_id', $unitIds)->values();
        }
        $result = $this->result($report, $filters);
        $columns = $this->columns($report, $result);
        $rows = $this->searchRows($result['rows'], $filters['search'] ?? null);
        $rows = $this->sortRows($rows, $request, $report, $columns);
        $paginatedRows = $allRows ? null : $this->paginateRows($rows, $request);
        $canExport = $request->user()?->hasPermission('reports.export') ?? false;
        $studentSearchEnabled = in_array($report, ['transactions', 'monthly-spp', 'yearly-spp'], true);

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
            'summaryTotals' => $result['summaryTotals'] ?? null,
            'chartData' => $result['chartData'] ?? [],
            'tableTotals' => $result['tableTotals'] ?? [],
            'studentSearchEnabled' => $studentSearchEnabled,
            'selectedReportStudent' => $studentSearchEnabled ? $this->reports->studentForSearch($filters) : null,
            'resetRoute' => route($definition['route']),
            'xlsxUrl' => route('reports.export.xlsx', ['report' => $this->reportSlug($report)] + $request->query()),
            'pdfUrl' => route('reports.export.pdf', ['report' => $this->reportSlug($report)] + $request->query()),
            'canExportReports' => $canExport,
            'sortableColumns' => $this->sortableColumns($report, $columns),
            'options' => $options,
        ];
    }

    private function result(string $report, array $filters): array
    {
        return match ($report) {
            'monthly-spp' => $this->reports->monthlySpp($filters),
            'yearly-spp' => $this->reports->yearlySpp($filters),
            'unit-recap' => $this->reports->unitRecap($filters),
            default => $this->reports->transactions($filters),
        };
    }

    private function definition(string $report): array
    {
        $definitions = [
            'transactions' => [
                'title' => 'Transaksi Pembayaran',
                'description' => 'Rekap pembayaran harian berdasarkan periode, unit, kelas, dan petugas.',
                'route' => 'reports.transactions',
                'view' => 'reports.transactions',
                'menu' => 'transactions',
            ],
            'monthly-spp' => [
                'title' => 'SPP Perbulan',
                'description' => 'Rekap pembayaran SPP siswa yang sudah terjadi pada bulan tertentu.',
                'route' => 'reports.monthly_spp',
                'view' => 'reports.monthly-spp',
                'menu' => 'monthly-spp',
            ],
            'yearly-spp' => [
                'title' => 'SPP Pertahun',
                'description' => 'Rekap tanggal pembayaran SPP siswa dari Juli sampai Juni.',
                'route' => 'reports.yearly_spp',
                'view' => 'reports.yearly-spp',
                'menu' => 'yearly-spp',
            ],
            'unit-recap' => [
                'title' => 'Rekap Per Unit',
                'description' => 'Ringkasan total penerimaan berdasarkan unit pendidikan.',
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
            'spp-tahun-pelajaran' => 'yearly-spp',
            'rekap-unit' => 'unit-recap',
        ][$report] ?? $report;
    }

    private function reportSlug(string $report): string
    {
        return [
            'transactions' => 'transaksi',
            'monthly-spp' => 'spp-perbulan',
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
            ], $monthColumns);
        }

        return match ($report) {
            'monthly-spp' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'date', 'label' => 'Tanggal'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
                ['key' => 'month', 'label' => 'Bulan'],
                ['key' => 'year', 'label' => 'Tahun'],
                ['key' => 'nominal', 'label' => 'Nominal', 'type' => 'money'],
                ['key' => 'method', 'label' => 'Cara Bayar'],
                ['key' => 'operator', 'label' => 'Petugas'],
            ],
            'unit-recap' => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit Pendidikan', 'class' => 'name'],
                ['key' => 'spp', 'label' => 'SPP', 'type' => 'money'],
                ['key' => 'daftar_ulang', 'label' => 'Daftar Ulang', 'type' => 'money'],
                ['key' => 'laundry', 'label' => 'Laundry', 'type' => 'money'],
                ['key' => 'lain_lain', 'label' => 'Lain-lain', 'type' => 'money'],
                ['key' => 'total', 'label' => 'Jumlah Penerimaan', 'type' => 'money'],
            ],
            default => [
                ['key' => 'no', 'label' => 'No', 'type' => 'number'],
                ['key' => 'date', 'label' => 'Tanggal'],
                ['key' => 'nis', 'label' => 'NIS'],
                ['key' => 'student', 'label' => 'Nama Siswa', 'class' => 'name'],
                ['key' => 'unit', 'label' => 'Unit'],
                ['key' => 'class', 'label' => 'Kelas'],
                ['key' => 'type', 'label' => 'Jenis Pembayaran'],
                ['key' => 'method', 'label' => 'Cara Bayar'],
                ['key' => 'operator', 'label' => 'Petugas'],
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
        $yearField = ['name' => 'academic_year_id', 'label' => 'Tahun Pelajaran', 'type' => 'select', 'value' => $filters['academic_year_id'], 'options' => $this->academicYearOptions($options)];
        $calendarYearField = ['name' => 'year', 'label' => 'Tahun', 'type' => 'select', 'value' => $filters['year'], 'options' => $this->yearOptions($options)];

        return match ($report) {
            'monthly-spp' => array_merge([
                $calendarYearField,
                ['name' => 'month', 'label' => 'Bulan', 'type' => 'select', 'value' => $filters['month'], 'options' => $this->monthOptions()],
                ['name' => 'unit_id', 'label' => 'Unit Pendidikan', 'type' => 'select', 'value' => $filters['unit_id'], 'options' => $this->unitOptions($options)],
                ['name' => 'class_id', 'label' => 'Kelas', 'type' => 'select', 'value' => $filters['class_id'], 'options' => $this->classOptions($options), 'classFilter' => true],
            ]),
            'yearly-spp' => [
                $yearField,
                ['name' => 'unit_id', 'label' => 'Unit Pendidikan', 'type' => 'select', 'value' => $filters['unit_id'], 'options' => $this->unitOptions($options)],
                ['name' => 'class_id', 'label' => 'Kelas', 'type' => 'select', 'value' => $filters['class_id'], 'options' => $this->classOptions($options), 'classFilter' => true],
            ],
            'unit-recap' => $dateFields,
            default => array_merge($dateFields, [
                ['name' => 'unit_id', 'label' => 'Unit Pendidikan', 'type' => 'select', 'value' => $filters['unit_id'], 'options' => $this->unitOptions($options)],
                ['name' => 'class_id', 'label' => 'Kelas', 'type' => 'select', 'value' => $filters['class_id'], 'options' => $this->classOptions($options), 'classFilter' => true],
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

    private function sortRows(Collection $rows, Request $request, string $report, array $columns): Collection
    {
        $sort = $request->string('sort')->value();
        $sortableColumns = $this->sortableColumns($report, $columns);

        if (! in_array($sort, $sortableColumns, true)) {
            return $rows->values();
        }

        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        return $rows
            ->sortBy(fn (array $row) => $this->sortValue($row, $sort), SORT_REGULAR, $direction === 'desc')
            ->values();
    }

    private function sortValue(array $row, string $key): mixed
    {
        if ($key === 'date') {
            $date = $row['date_sort'] ?? null;

            return is_object($date) && method_exists($date, 'getTimestamp') ? $date->getTimestamp() : 0;
        }

        return $row[$key] ?? '';
    }

    private function sortableColumns(string $report, array $columns): array
    {
        $safeColumns = collect($columns)
            ->reject(fn (array $column) => ($column['key'] ?? null) === 'no' || ($column['type'] ?? null) === 'actions')
            ->pluck('key')
            ->all();

        return match ($report) {
            'transactions' => array_values(array_intersect($safeColumns, ['date', 'nis', 'student', 'unit', 'class', 'type', 'method', 'operator', 'amount'])),
            'monthly-spp' => array_values(array_intersect($safeColumns, ['date', 'nis', 'student', 'unit', 'class', 'month', 'year', 'nominal', 'method', 'operator'])),
            'yearly-spp' => $safeColumns,
            'unit-recap' => array_values(array_intersect($safeColumns, ['unit', 'spp', 'daftar_ulang', 'laundry', 'lain_lain', 'total'])),
            default => [],
        };
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
        if (($data['reportKey'] ?? null) === 'monthly-spp') {
            return [
                [
                    'name' => 'Sheet1',
                    'styled' => true,
                    'widths' => [6, 14, 12, 31, 10, 14, 14, 10, 14, 14, 16],
                    'mergeCells' => ['A1:K1'],
                    'rows' => $this->monthlySppRowsForExport($data),
                ],
            ];
        }

        if (($data['reportKey'] ?? null) === 'unit-recap') {
            return [
                [
                    'name' => 'Data',
                    'rows' => $this->unitRecapRowsForExport($data),
                ],
            ];
        }

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
            if (! empty($data['summaryTotals'])) {
                $totalValues = $data['summaryTotals']['values'] ?? [];
                $rows[] = collect($data['summaryColumns'])->map(function (array $column) use ($data, $totalValues) {
                    if ($column['key'] === 'no') {
                        return $data['summaryTotals']['label'] ?? 'Total Keseluruhan';
                    }

                    if ($column['key'] === 'unit') {
                        return '';
                    }

                    return $totalValues[$column['key']] ?? '';
                })->all();
            }
        }

        return $rows;
    }

    private function monthlySppRowsForExport(array $data): array
    {
        $rows = [
            [$this->monthlySppExportTitle()],
            ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Unit', 'Kelas', 'Bulan', 'Tahun', 'Nominal', 'Cara Bayar', 'Petugas'],
        ];

        foreach ($data['rows'] as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['date'] ?? '',
                (string) ($row['nis'] ?? ''),
                $row['student'] ?? '',
                $row['unit'] ?? '',
                $row['class'] ?? '',
                $row['month'] ?? '',
                $row['year'] ?? '',
                (int) ($row['nominal'] ?? 0),
                $row['method'] ?? '',
                $row['operator'] ?? '',
            ];
        }

        return $rows;
    }

    private function unitRecapRowsForExport(array $data): array
    {
        $rows = $this->tableRowsForExport($data['columns'], $data['rows']);
        $columns = collect($data['columns']);

        $rows[] = $columns->map(function (array $column, int $index) use ($data) {
            if ($index === 0) {
                return 'Total Keseluruhan';
            }

            if ($index === 1) {
                return '';
            }

            if (in_array($column['type'] ?? null, ['money', 'number'], true)) {
                return (int) $data['rows']->sum($column['key']);
            }

            return '';
        })->all();

        return $rows;
    }

    private function monthlySppExportTitle(): string
    {
        return 'Data_Laporan_SPP_perbulan_'.now()->format('dmY');
    }

    private function exportFilename(string $title, string $extension): string
    {
        if ($title === 'SPP Perbulan') {
            return $this->monthlySppExportTitle().'.'.$extension;
        }

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

    private function yearOptions(array $options): array
    {
        return $options['years']->mapWithKeys(fn (int $year) => [$year => (string) $year])->all();
    }

    private function operatorOptions(array $options): array
    {
        return ['' => 'Semua'] + $options['operators']->mapWithKeys(fn ($operator) => [$operator => $operator])->all();
    }
}
