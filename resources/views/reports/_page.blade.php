<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $definition['title'] }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m7 6 1 15h8l1-15"/><path d="M10 11v5M14 11v5"/>',
        'printer' => '<path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $query = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))->filter(fn ($value) => is_scalar($value))->all();
    $display = function (array $row, array $column) use ($rupiah, $number) {
        if ($column['key'] === 'no' || ($column['type'] ?? null) === 'actions') {
            return '';
        }
        $value = $row[$column['key']] ?? '';
        return match ($column['type'] ?? 'text') {
            'money' => $rupiah($value),
            'number' => $number($value),
            default => $value,
        };
    };
    $statusClass = fn ($status) => match ($status) {
        'Diterima', 'Sudah Bayar', 'Lunas' => 'success',
        'Sebagian', 'Pending' => 'warning',
        'Belum Bayar', 'Jatuh Tempo' => 'danger',
        default => 'neutral',
    };
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'reports', 'activeReportMenu' => $activeReportMenu])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <a class="icon-button logout-button" href="{{ route('logout') }}">{!! $icon('logout') !!}</a>
        </header>

        <main class="report-page-v2 report-page-{{ $reportKey }}">
            <section class="report-workspace-v2">
                <div class="report-heading-v2">
                    <div>
                        <h1>{{ $definition['title'] }}</h1>
                        <p>{{ $definition['description'] }}</p>
                    </div>
                    <div class="report-export-actions">
                        <a href="{{ $xlsxUrl }}" class="button report-export-button">{!! $icon('download') !!} XLSX</a>
                        <a href="{{ $pdfUrl }}" class="button report-export-button secondary">{!! $icon('file') !!} PDF</a>
                    </div>
                </div>

                <form method="GET" action="{{ route($definition['route']) }}" class="report-filter-card-v2 report-filter-{{ $reportKey }}">
                    @foreach($filterFields as $field)
                        <label @class(['report-search-field' => ($field['type'] ?? '') === 'search'])>
                            <span>{{ $field['label'] }}</span>
                            @if(($field['type'] ?? '') === 'select')
                                <select name="{{ $field['name'] }}" @if($field['name'] === 'unit_id') data-student-filter-unit @endif @if($field['name'] === 'class_id') data-student-filter-class data-class-requires-unit @endif @if($field['name'] === 'type') data-report-payment-type @endif @if($field['name'] === 'fee_type_id') data-report-fee-type @endif>
                                    @foreach($field['options'] as $value => $option)
                                        @php
                                            $label = is_array($option) ? $option['label'] : $option;
                                            $unitId = is_array($option) ? ($option['unit_id'] ?? '') : '';
                                            $paymentGroup = is_array($option) ? ($option['payment_group'] ?? '') : '';
                                        @endphp
                                        <option value="{{ $value }}" @if($unitId !== '') data-unit-id="{{ $unitId }}" @endif @if($paymentGroup !== '') data-payment-group="{{ $paymentGroup }}" @endif @selected((string) ($field['value'] ?? '') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif(($field['type'] ?? '') === 'date')
                                <input type="date" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
                            @elseif(($field['type'] ?? '') === 'search')
                                <span class="report-search-input">
                                    {!! $icon('search') !!}
                                    <input type="search" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="{{ $field['placeholder'] ?? 'Cari...' }}" autocomplete="off">
                                </span>
                            @else
                                <input type="text" name="{{ $field['name'] }}" value="{{ $field['value'] }}">
                            @endif
                        </label>
                    @endforeach

                    @foreach($query(collect($filterFields)->pluck('name')->push('payment_status')->push('spp_status')->push('academic_year_id')->all()) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="report-filter-actions-v2">
                        <button class="button report-apply-button">Terapkan</button>
                        <a class="button report-reset-button" href="{{ $resetRoute }}">Reset</a>
                    </div>
                </form>

                @if($summaryCards && ! in_array($reportKey, ['transactions', 'monthly-spp'], true))
                    <section class="report-summary-grid-v2" aria-label="Ringkasan laporan">
                        @foreach($summaryCards as $card)
                            <div>
                                <span>{{ $card['label'] }}</span>
                                <strong>{{ ($card['type'] ?? '') === 'money' ? $rupiah($card['value']) : $number($card['value']) }}</strong>
                            </div>
                        @endforeach
                    </section>
                @endif

                @if($reportKey === 'monthly-spp')
                    @php
                        $monthlyStatusRows = collect($chartData['units'] ?? []);
                        $monthlyPaymentRows = collect($chartData['payments'] ?? []);
                        $monthlyTotals = $chartData['totals'] ?? ['students' => 0, 'paid' => 0];
                        $maxMonthlyPayment = max(1, (int) $monthlyPaymentRows->max('paid'));
                    @endphp
                    @if($monthlyStatusRows->isNotEmpty())
                        <section class="monthly-spp-chart-grid" aria-label="Diagram SPP perbulan">
                            <div class="monthly-spp-chart-card monthly-spp-status-card">
                                <div class="monthly-spp-chart-head">
                                    <div>
                                        <h2>Status Pembayaran per Unit</h2>
                                        <p>Perbandingan siswa lunas, sebagian, dan belum bayar.</p>
                                    </div>
                                    <strong><span>Total Siswa</span>{{ $number($monthlyTotals['students'] ?? 0) }} siswa</strong>
                                </div>

                                <div class="monthly-spp-status-legend" aria-label="Legenda status SPP">
                                    <span class="paid">Lunas</span>
                                    <span class="partial">Sebagian</span>
                                    <span class="unpaid">Belum Bayar</span>
                                </div>

                                <div class="monthly-spp-status-list">
                                    @foreach($monthlyStatusRows as $row)
                                        <div class="monthly-spp-status-row">
                                            <div>
                                                <strong title="{{ $row['unit'] ?? '-' }}">{{ $row['unit_code'] ?? '-' }}</strong>
                                                <span>{{ $number($row['students'] ?? 0) }} siswa</span>
                                            </div>
                                            <i aria-hidden="true">
                                                @if(($row['paid_count'] ?? 0) > 0)
                                                    <b class="paid" style="--segment-width: {{ $row['paid_percent'] }}%;"></b>
                                                @endif
                                                @if(($row['partial_count'] ?? 0) > 0)
                                                    <b class="partial" style="--segment-width: {{ $row['partial_percent'] }}%;"></b>
                                                @endif
                                                @if(($row['unpaid_count'] ?? 0) > 0)
                                                    <b class="unpaid" style="--segment-width: {{ $row['unpaid_percent'] }}%;"></b>
                                                @endif
                                            </i>
                                            <small>
                                                {{ $number($row['paid_count'] ?? 0) }} / {{ $number($row['partial_count'] ?? 0) }} / {{ $number($row['unpaid_count'] ?? 0) }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="monthly-spp-chart-card monthly-spp-arrears-card">
                                <div class="monthly-spp-chart-head">
                                    <div>
                                        <h2>Uang Masuk per Unit</h2>
                                        <p>Total pembayaran SPP yang diterima pada bulan terpilih.</p>
                                    </div>
                                    <strong><span>Total Uang Masuk</span>{{ $rupiah($monthlyTotals['paid'] ?? 0) }}</strong>
                                </div>

                                <div class="monthly-spp-arrears-list">
                                    @foreach($monthlyPaymentRows as $row)
                                        @php
                                            $paymentWidth = max(3, min(100, round(((int) ($row['paid'] ?? 0) / $maxMonthlyPayment) * 100)));
                                        @endphp
                                        <div class="monthly-spp-arrears-row" style="--bar-width: {{ $paymentWidth }}%;">
                                            <div>
                                                <strong title="{{ $row['unit'] ?? '-' }}">{{ $row['unit_code'] ?? '-' }}</strong>
                                                <span>{{ $rupiah($row['paid'] ?? 0) }}</span>
                                            </div>
                                            <i><b></b></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </section>
                    @endif
                @endif

                @if($reportKey === 'transactions' && $summaryCards)
                    @php
                        $summaryByLabel = collect($summaryCards)->keyBy('label');
                        $totalReceivedValue = (int) ($summaryByLabel->get('Total Penerimaan')['value'] ?? 0);
                        $unitChartRows = collect($chartData['units'] ?? []);
                        $classChartRows = collect($chartData['classes'] ?? []);
                        $methodChartRows = collect($chartData['methods'] ?? []);
                        $methodTotalTransactions = (int) $methodChartRows->sum('transactions');
                        $maxUnitAmount = max(1, (int) $unitChartRows->max('amount'));
                        $maxClassAmount = max(1, (int) $classChartRows->max('amount'));
                        $compactRupiah = function (int $value) {
                            if ($value >= 1000000000) {
                                return 'Rp'.rtrim(rtrim(number_format($value / 1000000000, 1, ',', '.'), '0'), ',').' M';
                            }
                            if ($value >= 1000000) {
                                return 'Rp'.rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').' jt';
                            }
                            if ($value >= 1000) {
                                return 'Rp'.rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',').' rb';
                            }

                            return 'Rp'.number_format($value, 0, ',', '.');
                        };
                        $rawUnitStep = max(1, $maxUnitAmount / 4);
                        $unitMagnitude = 10 ** floor(log10($rawUnitStep));
                        $unitRatio = $rawUnitStep / $unitMagnitude;
                        $unitMultiplier = $unitRatio <= 1 ? 1 : ($unitRatio <= 2 ? 2 : ($unitRatio <= 5 ? 5 : 10));
                        $unitStep = (int) ($unitMultiplier * $unitMagnitude);
                        $unitAxisMax = max($unitStep * 4, $maxUnitAmount);
                        $unitAxisTicks = collect(range(4, 0))->map(fn ($index) => $unitStep * $index);
                    @endphp
                    @if($totalReceivedValue > 0 || $unitChartRows->isNotEmpty() || $classChartRows->isNotEmpty())
                        <section class="report-chart-dashboard-v3" aria-label="Diagram semua transaksi">
                            <div class="report-chart-card-v3 report-chart-unit-v3">
                                <div class="report-chart-card-head-v3">
                                    <div>
                                        <h2>Pembayaran per Unit Pendidikan</h2>
                                        <p>Total pembayaran yang diterima dari setiap unit pendidikan.</p>
                                    </div>
                                </div>

                                <div class="report-unit-chart-v4">
                                    @if($unitChartRows->isNotEmpty())
                                        <div class="report-unit-axis-v4">
                                            @foreach($unitAxisTicks as $tick)
                                                <span>{{ $compactRupiah((int) $tick) }}</span>
                                            @endforeach
                                        </div>
                                        <div class="report-unit-plot-v4">
                                            <div class="report-unit-grid-v4" aria-hidden="true">
                                                @foreach($unitAxisTicks as $tick)
                                                    <i></i>
                                                @endforeach
                                            </div>
                                            <div class="report-unit-bars-v4" style="--unit-count: {{ max(1, $unitChartRows->count()) }};">
                                                @foreach($unitChartRows as $row)
                                                    @php
                                                        $height = max(2, min(100, round(((int) ($row['amount'] ?? 0) / $unitAxisMax) * 100)));
                                                    @endphp
                                                    <div style="--bar-height: {{ $height }}%;" title="{{ ($row['unit'] ?? '-').' '.$rupiah($row['amount'] ?? 0) }}">
                                                        <i></i>
                                                        <span>{{ $row['unit_code'] ?? $row['unit'] ?? '-' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="report-chart-empty-v3">Belum ada penerimaan unit.</div>
                                    @endif
                                </div>

                                <div class="report-payment-method-summary-v3" aria-label="Ringkasan cara bayar">
                                    <div class="report-payment-method-total-v3">
                                        <span>Total Penerimaan</span>
                                        <strong>{{ $rupiah($totalReceivedValue) }}</strong>
                                        <small>{{ $number($methodTotalTransactions) }} transaksi diterima</small>
                                    </div>
                                    <div class="report-payment-method-split-v3">
                                        @foreach($methodChartRows as $methodRow)
                                            <div>
                                                <span>{{ $methodRow['method'] }}</span>
                                                <strong>{{ $rupiah($methodRow['amount'] ?? 0) }}</strong>
                                                <small>{{ $number($methodRow['transactions'] ?? 0) }} transaksi</small>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="report-chart-card-v3 report-chart-class-v3">
                                <div class="report-chart-card-head-v3">
                                    <div>
                                        <h2>Pembayaran per Kelas</h2>
                                        <p>Rincian pendapatan per kelas.</p>
                                    </div>
                                </div>

                                <div class="report-class-progress-v3">
                                    @forelse($classChartRows as $row)
                                        @php
                                            $width = max(4, min(100, round(((int) ($row['amount'] ?? 0) / $maxClassAmount) * 100)));
                                        @endphp
                                        <div style="--bar-width: {{ $width }}%;">
                                            <div>
                                                <span>{{ $row['class'] ?? '-' }}</span>
                                                <strong>{{ $rupiah($row['amount'] ?? 0) }}</strong>
                                            </div>
                                            <i><b></b></i>
                                        </div>
                                    @empty
                                        <div class="report-chart-empty-v3">Belum ada penerimaan kelas.</div>
                                    @endforelse
                                </div>
                            </div>
                        </section>
                    @endif
                @endif

                @if($summaryColumns && $summaryRows->isNotEmpty() && ! in_array($reportKey, ['transactions', 'monthly-spp'], true))
                    <section class="report-summary-table-section">
                        <div class="table-wrap">
                            <table class="report-table-v2 report-summary-table-v2">
                                <thead>
                                    <tr>
                                        @foreach($summaryColumns as $column)
                                            <th>{{ $column['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summaryRows as $row)
                                        <tr>
                                            @foreach($summaryColumns as $column)
                                                <td @class(['report-money-cell' => ($column['type'] ?? '') === 'money'])>
                                                    @if($column['key'] === 'no')
                                                        {{ $loop->parent->iteration }}
                                                    @elseif(($column['type'] ?? '') === 'money')
                                                        {{ $rupiah($row[$column['key']] ?? 0) }}
                                                    @elseif(($column['type'] ?? '') === 'number')
                                                        {{ $number($row[$column['key']] ?? 0) }}
                                                    @else
                                                        {{ $row[$column['key']] ?? '-' }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <div class="report-table-toolbar-v2">
                    <form method="GET" action="{{ route($definition['route']) }}" class="report-page-size-form">
                        @foreach($query(['per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            data
                        </label>
                    </form>
                    <span>
                        @if($rowsPaginator && $rowsPaginator->total() > 0)
                            Menampilkan {{ $rowsPaginator->firstItem() }}-{{ $rowsPaginator->lastItem() }} dari {{ number_format($rowsPaginator->total(), 0, ',', '.') }} data
                        @else
                            Menampilkan 0 data
                        @endif
                    </span>
                </div>

                <section class="report-table-section-v2">
                    <div class="table-wrap">
                        <table class="report-table-v2 report-main-table-v2">
                            <thead>
                                <tr>
                                    @foreach($columns as $column)
                                        <th>{{ $column['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    @php
                                        $isSppPayment = ($row['source'] ?? '') === 'spp';
                                        $sourceId = $row['source_id'] ?? null;
                                        $paymentCategory = $row['group'] ?? null;
                                        $transactionAt = $row['date_sort'] ?? null;
                                        $dateText = is_object($transactionAt) && method_exists($transactionAt, 'format') ? $transactionAt->format('d/m/Y') : '-';
                                        $timeText = is_object($transactionAt) && method_exists($transactionAt, 'format') ? $transactionAt->format('H:i') : '-';
                                        $rowDetailId = 'report-detail-'.$reportKey.'-'.md5((string) ($row['id'] ?? $loop->index));
                                        $receiptUrl = $sourceId ? ($isSppPayment
                                            ? route('finance.spp.receipt', ['sppPayment' => $sourceId])
                                            : route('finance.other.receipt', ['otherPayment' => $sourceId])) : null;
                                        $deleteUrl = $sourceId ? ($isSppPayment
                                            ? route('finance.spp.destroy', ['sppPayment' => $sourceId])
                                            : route('finance.other.destroy', ['otherPayment' => $sourceId])) : null;
                                        $editUrl = $sourceId ? ($isSppPayment
                                            ? route('finance.payments.index', ['search' => $row['nis'] ?? $row['name'] ?? '', 'student_id' => $row['student_id'] ?? null, 'edit_payment' => $sourceId, 'return_url' => url()->full()])
                                            : route('finance.other.show', ['otherPayment' => $sourceId])) : null;
                                        $updateUrl = $sourceId && ! $isSppPayment
                                            ? route('finance.other.update', ['otherPayment' => $sourceId])
                                            : null;
                                    @endphp
                                    <tr>
                                        @foreach($columns as $column)
                                            <td @class([
                                                'report-name-cell' => ($column['class'] ?? '') === 'name',
                                                'report-money-cell' => ($column['type'] ?? '') === 'money',
                                                'report-action-cell' => ($column['type'] ?? '') === 'actions',
                                            ])>
                                                @if($column['key'] === 'no')
                                                    {{ ($rowsPaginator?->firstItem() ?? 1) + $loop->parent->index }}
                                                @elseif(($column['type'] ?? '') === 'status')
                                                    <span class="report-status {{ $statusClass($row[$column['key']] ?? '') }}">{{ $row[$column['key']] ?? '-' }}</span>
                                                @elseif(($column['type'] ?? '') === 'actions')
                                                    <div class="report-row-actions">
                                                        <button type="button" data-report-row-toggle="{{ $rowDetailId }}" aria-expanded="false" aria-controls="{{ $rowDetailId }}" aria-label="Detail transaksi" title="Detail transaksi">{!! $icon('eye') !!}</button>
                                                        @if($editUrl)
                                                            @if($isSppPayment)
                                                                <a href="{{ $editUrl }}" aria-label="Edit transaksi" title="Edit transaksi">{!! $icon('edit') !!}</a>
                                                            @elseif($updateUrl)
                                                                <button type="button" data-other-edit-url="{{ $editUrl }}" data-other-update-url="{{ $updateUrl }}" aria-label="Edit transaksi" title="Edit transaksi">{!! $icon('edit') !!}</button>
                                                            @endif
                                                        @endif
                                                        @if($deleteUrl)
                                                            <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Hapus transaksi ini?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="return_url" value="{{ url()->full() }}">
                                                                <button type="submit" aria-label="Hapus transaksi" title="Hapus transaksi">{!! $icon('trash') !!}</button>
                                                            </form>
                                                        @endif
                                                        @if($receiptUrl)
                                                            <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" aria-label="Cetak transaksi" title="Cetak transaksi">{!! $icon('printer') !!}</a>
                                                        @endif
                                                    </div>
                                                @else
                                                    {{ $display($row, $column) }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                    @if($reportKey === 'transactions')
                                        <tr id="{{ $rowDetailId }}" class="report-detail-row-v2" hidden>
                                            <td colspan="{{ count($columns) }}">
                                                <table class="report-detail-table-v2">
                                                    <tbody>
                                                        <tr>
                                                            <th>Waktu Transaksi</th>
                                                            <td>{{ $dateText }} {{ $timeText }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kategori</th>
                                                            <td>{{ $row['type'] ?? '-' }}</td>
                                                        </tr>
                                                        @if(! empty($row['period_label']) && ! empty($row['period']))
                                                            <tr>
                                                                <th>{{ $row['period_label'] }}</th>
                                                                <td>{{ $row['period'] }}</td>
                                                            </tr>
                                                        @endif
                                                        <tr>
                                                            <th>Cara Bayar</th>
                                                            <td>{{ $row['method'] ?? '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Petugas</th>
                                                            <td>{{ $row['operator'] ?? '-' }}</td>
                                                        </tr>
                                                        @if(in_array(($row['group'] ?? null), ['daftar-ulang', 'lain-lain'], true))
                                                            <tr>
                                                                <th>Nama Pembayaran</th>
                                                                <td>{{ $row['description'] ?? '-' }}</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr><td colspan="{{ count($columns) }}" class="empty-state">Belum ada data pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if($rowsPaginator && $rowsPaginator->hasPages() && ! in_array($reportKey, ['transactions', 'monthly-spp'], true))
                    <div class="pagination-wrap">{{ $rowsPaginator->links() }}</div>
                @endif
            </section>

            @if($reportKey === 'transactions')
                <div class="modal-backdrop" data-other-edit-modal>
                    <div class="form-modal spp-edit-modal">
                        <div class="form-modal-header">
                            <div>
                                <p class="eyebrow">Pembayaran</p>
                                <h2>Edit Transaksi</h2>
                            </div>
                            <button type="button" class="icon-button" data-other-crud-close>&times;</button>
                        </div>
                        <form method="POST" data-other-edit-form class="master-form spp-edit-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return_url" value="{{ url()->full() }}">
                            <div class="spp-edit-readonly"><span data-other-edit-summary>Data siswa dan kategori pembayaran tidak dapat diubah.</span></div>
                            <label>Tanggal Transaksi<input type="date" name="transaction_date" required></label>
                            <label>Jam Transaksi (WIB)<input type="text" name="transaction_time" inputmode="numeric" placeholder="Contoh: 18.00" pattern="(?:[01]\d|2[0-3])[.:][0-5]\d" required></label>
                            <label>Cara Bayar<select name="payment_method" required><option>Cash</option><option>Transfer</option></select></label>
                            <label>Status Penerimaan<select name="status" required><option>Diterima</option><option>Pending</option></select></label>
                            <div class="form-actions span-2">
                                <button type="button" class="button button-secondary" data-other-crud-close>Batal</button>
                                <button class="button button-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
