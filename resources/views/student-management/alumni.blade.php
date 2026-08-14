<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alumni - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="student-alumni-body">
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'school' => '<path d="m3 10 9-5 9 5-9 5-9-5Z"/><path d="M7 12v5c3 2 7 2 10 0v-5"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $selectedUnitId = $filters['unit_id'] ?? null;
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'alumni',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar" title="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main id="student-alumni-page" class="student-page student-alumni-page student-alumni-v7">
            <section class="student-workspace student-list-filter-card">
                <div class="student-flat-header">
                    <div class="student-master-heading">
                        <h1>Data Alumni</h1>
                        <p>Kelola data siswa yang sudah lulus atau berstatus nonaktif/alumni.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('student-management.alumni.index') }}" class="student-filter-panel student-reference-filter student-fee-card-filter" data-student-filter-panel>
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    <div class="student-reference-filter-grid student-fee-card-filter-grid">
                        <label>
                            <span>Unit Pendidikan</span>
                            <select name="unit_id" data-student-filter-unit>
                                <option value="">Semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected($selectedUnitId == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Kelas</span>
                            <select name="class_id" data-student-filter-class>
                                <option value="">Semua</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(($filters['class_id'] ?? null) == $class->id)>{{ $class->educationUnit?->code ? $class->educationUnit->code.' - ' : '' }}{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Tahun Pelajaran</span>
                            <select name="year_id">
                                <option value="">Semua</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(($filters['year_id'] ?? null) == $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="student-reference-search student-fee-filter-search">
                        <span>Cari alumni</span>
                        {!! $icon('search') !!}
                        <input name="search" value="{{ $filters['search'] }}" placeholder="Nama, NIS, NISN, unit, kelas..." aria-label="Cari alumni berdasarkan nama, NIS, NISN, unit, kelas, tahun, atau alasan nonaktif">
                    </label>
                    <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                        <button class="button student-fee-card-search-button fee-type-card-search-button" type="submit" aria-label="Tampilkan data alumni">Terapkan</button>
                        <a href="{{ route('student-management.alumni.index') }}" class="button student-fee-card-reset-button fee-type-card-reset-button">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card student-list-table-card">
                <div class="student-reference-card-count">
                    <form method="GET" action="{{ route('student-management.alumni.index') }}" class="student-reference-card-length">
                    @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah alumni yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(($filters['per_page'] ?? '10') == (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(($filters['per_page'] ?? '10') === 'all')>Semua</option>
                            </select>
                            alumni
                        </label>
                    </form>
                    <span>
                        {{ ($alumni->total() ?? 0) > 0 ? 'Menampilkan '.number_format($alumni->firstItem(), 0, ',', '.').'-'.number_format($alumni->lastItem(), 0, ',', '.').' dari '.number_format($alumni->total(), 0, ',', '.').' alumni' : 'Menampilkan 0 dari 0 alumni' }}
                    </span>
                </div>

                <div class="table-wrap alumni-table-wrap">
                    <table class="data-table student-flat-table alumni-table">
                        <colgroup>
                            <col class="alumni-col-no">
                            <col class="alumni-col-nis">
                            <col class="alumni-col-nisn">
                            <col class="alumni-col-name">
                            <col class="alumni-col-gender">
                            <col class="alumni-col-unit">
                            <col class="alumni-col-class">
                            <col class="alumni-col-year">
                            <col class="alumni-col-date">
                            <col class="alumni-col-reason">
                            <col class="alumni-col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>JK</th>
                                <th>Unit</th>
                                <th>Kelas</th>
                                <th>Tahun</th>
                                <th>Tanggal Keluar</th>
                                <th>Alasan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alumni as $student)
                                <tr>
                                    <td class="alumni-cell-center">{{ $alumni->firstItem() + $loop->index }}</td>
                                    <td class="alumni-cell-center">{{ $student->nis ?: '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->nisn ?: '-' }}</td>
                                    <td class="alumni-cell-main"><strong>{{ $student->name }}</strong></td>
                                    <td class="alumni-cell-center">{{ $student->gender === 'L' ? 'L' : 'P' }}</td>
                                    <td class="alumni-cell-center">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->schoolClass?->name ?? '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->academicYear?->name ?? '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->exit_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="alumni-cell-center">{{ $student->inactive_reason ?: '-' }}</td>
                                    <td class="alumni-actions-cell">
                                        <a class="icon-button alumni-action-edit" href="{{ route('student-management.students.edit', array_merge([$student], request()->query())) }}" title="Edit / aktifkan kembali" aria-label="Edit atau aktifkan kembali {{ $student->name }}">
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="alumni-empty-cell">
                                        <strong>Belum ada data alumni</strong>
                                        <span>Data akan muncul setelah siswa dijadikan nonaktif/alumni.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($alumni->hasPages())
                    @php
                        $alumniPageUrl = fn ($page) => route('student-management.alumni.index', array_merge(request()->except('page'), ['page' => $page]));
                    @endphp
                    <nav class="pagination-wrap alumni-pagination" aria-label="Navigasi halaman alumni">
                        @if($alumni->onFirstPage())
                            <span class="student-page-button is-disabled" aria-disabled="true">Sebelumnya</span>
                        @else
                            <a class="student-page-button" href="{{ $alumniPageUrl($alumni->currentPage() - 1) }}" rel="prev">Sebelumnya</a>
                        @endif
                        @if($alumni->hasMorePages())
                            <a class="student-page-button" href="{{ $alumniPageUrl($alumni->currentPage() + 1) }}" rel="next">Berikutnya</a>
                        @else
                            <span class="student-page-button is-disabled" aria-disabled="true">Berikutnya</span>
                        @endif
                    </nav>
                @endif
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
