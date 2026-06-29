<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alumni - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'alumni',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button" aria-label="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page student-alumni-v7">
            <section class="student-workspace student-list-filter-card">
                <div class="student-flat-header">
                    <div class="student-master-heading">
                        <h1>Data Alumni</h1>
                        <p>Kelola data siswa yang sudah lulus atau berstatus nonaktif/alumni.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('student-management.alumni.index') }}" class="student-filter-panel" data-student-filter-panel>
                    <label>
                        <span>Unit Pendidikan</span>
                        <select name="unit_id" data-student-filter-unit>
                            <option value="">semua</option>
                            @foreach ($educationUnits as $unit)
                                <option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Kelas</span>
                        <select name="class_id" data-student-filter-class>
                            <option value="">semua</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Tahun Pelajaran</span>
                        <select name="year_id">
                            <option value="">semua</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" @selected($filters['year_id'] == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Alasan</span>
                        <select name="reason">
                            <option value="">semua</option>
                            @foreach ($reasonOptions as $reason)
                                <option value="{{ $reason }}" @selected($filters['reason'] === $reason)>{{ $reason }}</option>
                            @endforeach
                        </select>
                    </label>

                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request('direction'))
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                    @endif

                    <div class="student-filter-actions">
                        <button class="button student-search-button" type="submit" aria-label="Tampilkan data alumni">Cari</button>
                        <a href="{{ route('student-management.alumni.index') }}" class="button student-filter-reset">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card student-list-table-card">
                <div class="student-table-toolbar">
                    <form method="GET" action="{{ route('student-management.alumni.index') }}" class="student-table-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Show
                            <select name="per_page" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(($filters['per_page'] ?? '10') == (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(($filters['per_page'] ?? '10') === 'all')>All</option>
                            </select>
                            entries
                        </label>
                    </form>

                    <form method="GET" action="{{ route('student-management.alumni.index') }}" class="student-table-search">
                        @foreach(request()->except(['search', 'page']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Search: <input name="search" value="{{ $filters['search'] }}" aria-label="Cari alumni berdasarkan nama, NIS, NISN, unit, kelas, tahun, atau alasan"></label>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="data-table student-flat-table">
                        <colgroup>
                            <col class="alumni-col-no">
                            <col class="alumni-col-nis">
                            <col class="alumni-col-name">
                            <col class="alumni-col-gender">
                            <col class="alumni-col-unit">
                            <col class="alumni-col-date">
                            <col class="alumni-col-reason">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>@include('partials.sortable-heading', ['column' => 'nis', 'label' => 'NIS'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'gender', 'label' => 'Jenis Kelamin'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'exit_date', 'label' => 'Tanggal Alumni'])</th>
                                <th>@include('partials.sortable-heading', ['column' => 'reason', 'label' => 'Alasan'])</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alumni as $student)
                                <tr>
                                    <td class="alumni-cell-center">{{ $alumni->firstItem() + $loop->index }}</td>
                                    <td class="alumni-cell-center">{{ $student->nis }}</td>
                                    <td class="alumni-cell-main">{{ $student->name }}</td>
                                    <td class="alumni-cell-center">{{ $student->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
                                    <td class="alumni-cell-center">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->exit_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="alumni-cell-main">{{ $student->inactive_reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <strong>Belum ada data alumni</strong>
                                            <span>Data akan muncul setelah siswa dijadikan nonaktif/alumni.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrap">{{ $alumni->links() }}</div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
