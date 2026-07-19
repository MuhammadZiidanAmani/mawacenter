<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pembayaran - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $rupiah = fn ($value) => 'Rp '.number_format($value, 0, ',', '.');
    $reportQuery = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))
        ->filter(fn ($value) => is_scalar($value))
        ->all();
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'reports', 'activeReportMenu' => 'report'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page report-flat-page">
            <section class="student-workspace report-workspace">
                <div class="student-flat-header">
                    <h1>Laporan Pembayaran</h1>
                    <div class="student-title-actions">
                        <a href="{{ route('reports.export', request()->query()) }}" class="button student-add-button">{!! $icon('download') !!} Export CSV</a>
                        <a href="{{ route('reports.index') }}" class="button action-orange">Reset</a>
                    </div>
                </div>

                <form method="GET" action="{{ route('reports.index') }}" class="student-filter-panel report-filter-panel">
                    <label class="report-date-filter">
                        <span>Waktu</span>
                        <span class="report-date-range">
                            <input type="date" name="date_from" value="{{ $filters['date_from']->format('Y-m-d') }}" aria-label="Tanggal awal">
                            <b>-</b>
                            <input type="date" name="date_to" value="{{ $filters['date_to']->format('Y-m-d') }}" aria-label="Tanggal akhir">
                        </span>
                    </label>
                    <label><span>Kategori Pembayaran</span><select name="type"><option value="">-- semua --</option><option value="spp" @selected($filters['type'] === 'spp')>SPP</option><option value="daftar-ulang" @selected($filters['type'] === 'daftar-ulang')>Daftar Ulang</option><option value="laundry" @selected($filters['type'] === 'laundry')>Laundry</option><option value="lain-lain" @selected($filters['type'] === 'lain-lain')>Lain-lain</option></select></label>
                    <label><span>Cara Bayar</span><select name="payment_method"><option value="">-- semua --</option><option value="Cash" @selected($filters['payment_method'] === 'Cash')>Cash</option><option value="Transfer" @selected($filters['payment_method'] === 'Transfer')>Transfer</option></select></label>
                    <label><span>Status</span><select name="status"><option value="">-- semua --</option><option value="Diterima" @selected($filters['status'] === 'Diterima')>Diterima</option><option value="Pending" @selected($filters['status'] === 'Pending')>Pending</option></select></label>
                    <label><span>Petugas</span><select name="operator_name"><option value="">-- semua --</option>@foreach($operators as $operator)<option value="{{ $operator }}" @selected($filters['operator_name'] === $operator)>{{ $operator }}</option>@endforeach</select></label>
                    <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                    <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">-- semua --</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    <span class="report-filter-label">Siswa</span>
                    <div class="student-search-picker report-student-filter" data-student-picker data-student-optional>
                        <input type="search" name="student_search" value="{{ $filters['student_search'] }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" data-student-search>
                        <select name="student_id" data-student-source>
                            <option value="">Semua siswa</option>
                            @foreach($studentOptions as $student)
                                <option value="{{ $student->id }}" @selected($filters['student_id'] == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                            @endforeach
                        </select>
                        <div class="student-search-results" data-student-results hidden></div>
                    </div>
                    @foreach($reportQuery(['date_from', 'date_to', 'start_date', 'end_date', 'type', 'payment_method', 'status', 'operator_name', 'unit_id', 'class_id', 'student_id', 'student_search']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <span class="report-summary-label">Total Penerimaan</span>
                    <div class="report-summary-strip">
                        <div class="total"><span>{{ $rupiah($stats['total']) }}</span><small>{{ number_format($stats['accepted_transactions'], 0, ',', '.') }} diterima</small></div>
                        <div><span>SPP</span><small>{{ $rupiah($stats['spp']) }}</small></div>
                        <div><span>Daftar Ulang</span><small>{{ $rupiah($stats['daftar_ulang']) }}</small></div>
                        <div><span>Laundry</span><small>{{ $rupiah($stats['laundry']) }}</small></div>
                        <div><span>Lain-lain</span><small>{{ $rupiah($stats['lain_lain']) }}</small></div>
                        <div><span>Siswa</span><small>{{ number_format($stats['students'], 0, ',', '.') }}</small></div>
                    </div>
                    <div class="student-filter-actions report-filter-actions">
                        <button class="button student-search-button" aria-label="Tampilkan laporan">{!! $icon('search') !!}</button>
                    </div>
                </form>

                <section class="card student-data-card report-data-card">
                <div class="student-table-toolbar">
                    <form method="GET" action="{{ route('reports.index') }}" class="student-table-length">
                        @foreach($reportQuery(['per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label>Show
                            <select name="per_page" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)<option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>@endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            entries
                        </label>
                    </form>
                    <form method="GET" action="{{ route('reports.index') }}" class="student-table-search">
                        @foreach($reportQuery(['search']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari laporan berdasarkan nama atau NIS"></label>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="data-table student-flat-table report-flat-table">
                        <colgroup>
                            <col class="report-col-no">
                            <col class="report-col-date">
                            <col class="report-col-nis">
                            <col class="report-col-name">
                            <col class="report-col-unit">
                            <col class="report-col-class">
                            <col class="report-col-type">
                            <col class="report-col-method">
                            <col class="report-col-status">
                            <col class="report-col-total">
                            <col class="report-col-detail">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>@include('partials.sortable-heading', ['column' => 'date', 'label' => 'Tanggal'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'nis', 'label' => 'NIS'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'student', 'label' => 'Nama'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'type', 'label' => 'Kategori'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'method', 'label' => 'Cara Bayar'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'status', 'label' => 'Status'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'amount', 'label' => 'Nominal'])</th>
                                <th>Rincian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $item)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $loop->index }}</td>
                                    <td><span class="report-date-main">{{ $item['date']->format('d/m/Y') }}</span><small>{{ $item['date']->format('H:i') }}</small></td>
                                    <td>{{ $item['nis'] }}</td>
                                    <td><span class="report-student-name">{{ $item['student'] }}</span></td>
                                    <td><span class="education-code">{{ $item['unit'] }}</span></td>
                                    <td>{{ $item['class'] }}</td>
                                    <td><span class="report-type {{ $item['group'] }}">{{ $item['type'] }}</span></td>
                                    <td>{{ $item['method'] }}</td>
                                    <td><span class="status {{ $item['status'] === 'Diterima' ? 'success' : 'warning' }}">{{ $item['status'] }}</span></td>
                                    <td><span class="report-amount">{{ $rupiah($item['amount']) }}</span></td>
                                    <td>
                                        <details class="report-row-detail">
                                            <summary>Lihat</summary>
                                            <div class="report-detail-panel">
                                                <span><b>Kategori</b>{{ $item['description'] }}</span>
                                                <span><b>Unit</b>{{ $item['unit'] }}</span>
                                                <span><b>Kelas</b>{{ $item['class'] }}</span>
                                                <span><b>Petugas</b>{{ $item['operator'] }}</span>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="empty-state">Belum ada transaksi pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </section>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
