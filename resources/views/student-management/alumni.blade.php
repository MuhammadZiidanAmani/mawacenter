<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alumni - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter {
            grid-template-columns: 160px 150px minmax(220px, 260px) max-content !important;
            grid-template-rows: auto !important;
        }

        html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1) {
            grid-column: 1 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2) {
            grid-column: 2 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search {
            grid-column: 3 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
            grid-column: 4 !important;
            grid-row: 1 !important;
            align-self: end !important;
            width: auto !important;
            min-width: 0 !important;
        }

        @media (width <= 760px) {
            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter {
                grid-template-columns: 1fr !important;
            }

            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1),
            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2),
            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search,
            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                grid-column: auto !important;
                grid-row: auto !important;
            }

            html body .app-shell .main-panel main#student-alumni-page.student-alumni-page form.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
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

        <main id="student-alumni-page" class="student-page student-alumni-page">
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
                                <option value="">semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>
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
                    </div>
                    <label class="student-reference-search student-fee-filter-search">
                        <span>Cari alumni</span>
                        {!! $icon('search') !!}
                        <input name="search" value="{{ $filters['search'] }}" placeholder="Nama atau NIS..." aria-label="Cari alumni berdasarkan nama atau NIS">
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
                    @foreach(request()->except(['per_page', 'page', 'class_id', 'reason', 'sort', 'direction']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah alumni yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(($filters['per_page'] ?? '10') == (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(($filters['per_page'] ?? '10') === 'all')>All</option>
                            </select>
                            alumni
                        </label>
                    </form>
                    <span>
                        {{ ($alumni->total() ?? 0) > 0 ? 'Menampilkan '.number_format($alumni->firstItem(), 0, ',', '.').'-'.number_format($alumni->lastItem(), 0, ',', '.').' dari '.number_format($alumni->total(), 0, ',', '.').' alumni' : 'Menampilkan 0 dari 0 alumni' }}
                    </span>
                </div>

                <div class="student-reference-card-list">
                    @forelse ($alumni as $student)
                        <article class="student-reference-card">
                            <div class="student-reference-card-top">
                                <div class="student-reference-card-title">
                                    <strong>{{ $student->name }}</strong>
                                    <div class="student-reference-card-meta">
                                        <span>{!! $icon('card') !!}<b>{{ $student->nis ?: '-' }}</b></span>
                                        <span>{!! $icon('school') !!}<b>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                        <span>{!! $icon('info') !!}<b>{{ $student->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</b></span>
                                        <span>{!! $icon('calendar') !!}<b>{{ $student->exit_date?->format('d/m/Y') ?? '-' }}</b></span>
                                        <span>{!! $icon('info') !!}<b>{{ $student->inactive_reason ?: '-' }}</b></span>
                                    </div>
                                </div>
                                <div class="student-reference-card-side">
                                    <span class="student-reference-status is-inactive">Alumni</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="student-reference-empty">
                            <strong>Belum ada data alumni</strong>
                            <span>Data akan muncul setelah siswa dijadikan nonaktif/alumni.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $alumni->links() }}</div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
