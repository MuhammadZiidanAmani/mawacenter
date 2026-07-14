<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagihan Siswa - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'refresh' => '<path d="M21 12a9 9 0 0 1-15.5 6.2M3 12A9 9 0 0 1 18.5 5.8"/><path d="M21 4v6h-6M3 20v-6h6"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $billQuery = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))
        ->filter(fn ($value) => is_scalar($value))
        ->all();
    $showingFrom = $studentsWithBills->total() > 0 ? $studentsWithBills->firstItem() : 0;
    $showingTo = $studentsWithBills->total() > 0 ? $studentsWithBills->lastItem() : 0;
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'bills'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page bill-page bill-flat-page">
            @if(session('success'))
                <div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif

            <section class="bill-workspace">
                <div class="bill-page-heading">
                    <div>
                        <h1>Tagihan Siswa</h1>
                        <p>Pantau tagihan semua siswa, rincian SPP, pembayaran lain-lain, dan sisa kewajiban.</p>
                    </div>
                    <form method="POST" action="{{ route('finance.bills.sync') }}" class="bill-sync-form">
                        @csrf
                        @foreach($billQuery(['year', 'until_month']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="until_month" value="{{ $untilMonth }}">
                        <button class="button bill-sync-button">{!! $icon('refresh') !!} Perbarui Tagihan</button>
                    </form>
                </div>

                <section class="bill-unit-summary" aria-label="Ringkasan tagihan per unit">
                    <div class="table-wrap bill-unit-table-wrap">
                        <table class="bill-unit-table">
                            <colgroup>
                                <col class="bill-unit-col-no">
                                <col class="bill-unit-col-unit">
                                <col class="bill-unit-col-students">
                                <col class="bill-unit-col-total">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Unit Pendidikan</th>
                                    <th>Siswa</th>
                                    <th>Jumlah Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unitSummaries as $unitSummary)
                                    <tr @class(['is-active' => (string) request('unit_id') === (string) $unitSummary['unit_id']])>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $unitSummary['unit_name'] }}</td>
                                        <td>{{ number_format($unitSummary['students'], 0, ',', '.') }}</td>
                                        <td><span class="bill-money remaining">{{ $rupiah($unitSummary['remaining']) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-state">Belum ada ringkasan tagihan per unit.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="bill-unit-total-caption">Total Keseluruhan</td>
                                    <td><strong>{{ number_format($overviewStats['students'], 0, ',', '.') }}</strong></td>
                                    <td><strong>{{ $rupiah($overviewStats['remaining']) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <form method="GET" action="{{ route('finance.bills.index') }}" class="bills-filter-panel">
                    <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                    <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                    <label class="bill-search-field"><span>Cari Siswa</span><span class="bill-search-input">{!! $icon('search') !!}<input type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Nama atau NIS..."></span></label>
                    <div class="bill-filter-actions">
                        <button class="button bill-filter-apply">Terapkan</button>
                        <a href="{{ route('finance.bills.index') }}" class="button bill-filter-reset">Reset</a>
                    </div>
                </form>

                <div class="bill-table-toolbar">
                    <form method="GET" action="{{ route('finance.bills.index') }}" class="bill-page-size-form">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Tampilkan
                            <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            data
                        </label>
                    </form>
                    <span>Menampilkan {{ number_format($showingFrom, 0, ',', '.') }}-{{ number_format($showingTo, 0, ',', '.') }} dari {{ number_format($studentsWithBills->total(), 0, ',', '.') }} siswa</span>
                </div>

                <section class="bills-data-card">
                    <div class="table-wrap">
                        <table class="bill-flat-table">
                            <colgroup>
                                <col class="bill-col-no">
                                <col class="bill-col-nis">
                                <col class="bill-col-name">
                                <col class="bill-col-unit">
                                <col class="bill-col-class">
                                <col class="bill-col-total">
                                <col class="bill-col-action">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Unit</th>
                                    <th>Kelas</th>
                                    <th>Total Tagihan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentsWithBills as $summary)
                                    @php($student = $summary['student'])
                                    <tr class="bill-main-row">
                                        <td>{{ $studentsWithBills->firstItem() + $loop->index }}</td>
                                        <td>{{ $student?->nis }}</td>
                                        <td><span class="bill-student-name">{{ $student?->name }}</span></td>
                                        <td>{{ $student?->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                        <td>{{ $student?->schoolClass?->name ?? '-' }}</td>
                                        <td><span class="bill-money remaining">{{ $rupiah($summary['total_remaining']) }}</span></td>
                                        <td>
                                            <div class="bill-table-actions">
                                                <a href="{{ $student ? route('finance.bills.show', array_merge(request()->only(['unit_id', 'class_id', 'student_id', 'student_search', 'per_page', 'sort', 'direction']), ['student' => $student->id, 'year' => $year, 'until_month' => $untilMonth])) : '#' }}" class="button bill-detail-trigger" aria-label="Detail tagihan" title="Detail">{!! $icon('eye') !!}</a>
                                                <a href="{{ route('finance.payments.index', ['student_id' => $student?->id, 'search' => $student?->nis]) }}" class="bill-pay-short" aria-label="Bayar tagihan" title="Bayar">{!! $icon('wallet') !!}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="empty-state">Belum ada tagihan pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>
</div>
</body>
</html>
