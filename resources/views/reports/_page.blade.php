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

        <main class="report-page-v2">
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

                <form method="GET" action="{{ route($definition['route']) }}" class="report-filter-card-v2">
                    @foreach($filterFields as $field)
                        <label @class(['report-search-field' => ($field['type'] ?? '') === 'search'])>
                            <span>{{ $field['label'] }}</span>
                            @if(($field['type'] ?? '') === 'select')
                                <select name="{{ $field['name'] }}" @if($field['name'] === 'unit_id') data-student-filter-unit @endif @if($field['name'] === 'class_id') data-student-filter-class @endif>
                                    @foreach($field['options'] as $value => $option)
                                        @php
                                            $label = is_array($option) ? $option['label'] : $option;
                                            $unitId = is_array($option) ? ($option['unit_id'] ?? '') : '';
                                        @endphp
                                        <option value="{{ $value }}" @if($unitId !== '') data-unit-id="{{ $unitId }}" @endif @selected((string) ($field['value'] ?? '') === (string) $value)>{{ $label }}</option>
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

                    @foreach($query(collect($filterFields)->pluck('name')->all()) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="report-filter-actions-v2">
                        <button class="button report-apply-button">Terapkan</button>
                        <a class="button report-reset-button" href="{{ $resetRoute }}">Reset</a>
                    </div>
                </form>

                @if($summaryCards)
                    <section class="report-summary-grid-v2" aria-label="Ringkasan laporan">
                        @foreach($summaryCards as $card)
                            <div>
                                <span>{{ $card['label'] }}</span>
                                <strong>{{ ($card['type'] ?? '') === 'money' ? $rupiah($card['value']) : $number($card['value']) }}</strong>
                            </div>
                        @endforeach
                    </section>
                @endif

                @if($summaryColumns && $summaryRows->isNotEmpty())
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
                                                        @if(! empty($row['student_id']))
                                                            <a href="{{ route('finance.bills.show', ['student' => $row['student_id']]) }}" aria-label="Detail tagihan" title="Detail tagihan">{!! $icon('eye') !!}</a>
                                                            <a href="{{ route('finance.payments.index', ['student_id' => $row['student_id'], 'search' => $row['nis'] ?? null]) }}" aria-label="Bayar" title="Bayar">{!! $icon('wallet') !!}</a>
                                                        @endif
                                                    </div>
                                                @else
                                                    {{ $display($row, $column) }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ count($columns) }}" class="empty-state">Belum ada data pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if($rowsPaginator && $rowsPaginator->hasPages())
                    <div class="pagination-wrap">{{ $rowsPaginator->links() }}</div>
                @endif
            </section>
        </main>
    </div>
</div>
</body>
</html>
