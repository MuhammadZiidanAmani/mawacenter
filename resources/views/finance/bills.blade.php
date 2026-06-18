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
        'database' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v12c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $billQuery = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))
        ->filter(fn ($value) => is_scalar($value))
        ->all();
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

            <section class="student-workspace">
                <div class="student-flat-header">
                    <h1>Tagihan Siswa</h1>
                </div>
                <div class="student-action-bar">
                    <form method="POST" action="{{ route('finance.bills.sync') }}" class="bill-sync-form-flat">
                        @csrf
                        @foreach($billQuery(['year', 'until_month']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="until_month" value="{{ $untilMonth }}">
                        <button class="button student-add-button">{!! $icon('database') !!} Sinkronkan</button>
                    </form>
                    <a href="{{ route('finance.bills.index') }}" class="button action-orange">Reset</a>
                    <a href="{{ route('finance.spp.create') }}" class="button action-purple">{!! $icon('wallet') !!} Bayar SPP</a>
                    <a href="{{ route('finance.other.create', ['category' => 'daftar-ulang']) }}" class="button action-purple">{!! $icon('receipt') !!} Bayar Daftar Ulang</a>
                    <a href="{{ route('finance.other.create') }}" class="button action-purple">{!! $icon('receipt') !!} Bayar Lain-lain</a>
                </div>

                <form method="GET" action="{{ route('finance.bills.index') }}" class="student-filter-panel bills-filter-panel">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="until_month" value="{{ $untilMonth }}">
                    <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                    <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    <span class="bill-filter-field-label">Siswa</span>
                    <div class="student-search-picker bill-student-picker" data-student-picker data-student-optional>
                        <input type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" data-student-search>
                        <select name="student_id" data-student-source>
                            <option value="">Semua siswa</option>
                            @foreach($studentOptions as $student)
                                <option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                            @endforeach
                        </select>
                        <div class="student-search-results" data-student-results hidden></div>
                    </div>
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                    <span class="bill-filter-total-label">Jumlah Tagihan</span>
                    <div class="bill-filter-total"><span class="bill-filter-value">{{ $rupiah($stats['remaining']) }}</span><small>{{ number_format($stats['students'], 0, ',', '.') }} siswa</small></div>
                    <div class="student-filter-actions">
                        <button class="button student-search-button" aria-label="Tampilkan data">{!! $icon('search') !!}</button>
                    </div>
                </form>
            </section>

            <section class="card student-data-card bills-data-card">
                <div class="student-table-toolbar">
                    <form method="GET" action="{{ route('finance.bills.index') }}" class="student-table-length">
                        @foreach($billQuery(['per_page']) as $key => $value)
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
                    <form method="GET" action="{{ route('finance.bills.index') }}" class="student-table-search">
                        @foreach($billQuery(['search']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari tagihan berdasarkan nama atau NIS"></label>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="data-table student-flat-table bill-flat-table">
                        <colgroup>
                            <col class="bill-col-no">
                            <col class="bill-col-nis">
                            <col class="bill-col-name">
                            <col class="bill-col-class">
                            <col class="bill-col-total">
                            <col class="bill-col-detail">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>@include('partials.sortable-heading', ['column' => 'nis', 'label' => 'NIS'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'total', 'label' => 'Total'])</th>
                                <th>Rincian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studentsWithBills as $summary)
                                @php($student = $summary['student'])
                                <tr>
                                    <td>{{ $studentsWithBills->firstItem() + $loop->index }}</td>
                                    <td>{{ $student->nis }}</td>
                                    <td><span class="bill-student-name">{{ $student->name }}</span><small class="bill-student-unit">Unit Pendidikan: {{ $student->schoolClass?->educationUnit?->code ?? '-' }}</small></td>
                                    <td>{{ $student->schoolClass?->name ?? '-' }}</td>
                                    <td><span class="bill-total">{{ $rupiah($summary['total_remaining']) }}</span></td>
                                    <td>
                                        <details class="bill-row-detail">
                                            <summary>Lihat</summary>
                                            <div class="bill-detail-panel">
                                                <div class="bill-detail-block">
                                                    <span class="bill-detail-title">SPP</span>
                                                    @forelse($summary['spp'] as $item)
                                                        <span>{{ $item['month_name'] }} {{ $item['year'] }} <span class="bill-detail-amount">{{ $rupiah($item['remaining']) }}</span></span>
                                                    @empty
                                                        <span class="bill-clear">{!! $icon('check') !!} Lunas</span>
                                                    @endforelse
                                                </div>
                                                <div class="bill-detail-block">
                                                    <span class="bill-detail-title">Lain-lain</span>
                                                    @forelse($summary['other'] as $item)
                                                        <span>{{ $item['name'] }} <span class="bill-detail-amount">{{ $rupiah($item['remaining']) }}</span></span>
                                                    @empty
                                                        <span class="bill-clear">{!! $icon('check') !!} Lunas</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-state">Belum ada tagihan pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">{{ $studentsWithBills->links() }}</div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
