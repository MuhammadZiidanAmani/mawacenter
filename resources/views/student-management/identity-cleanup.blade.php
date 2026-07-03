<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapikan Identitas - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
        'merge' => '<path d="M8 7h8m0 0-3-3m3 3-3 3M8 17h8m-8 0 3-3m-3 3 3 3M4 7h2m12 0h2M4 17h2m12 0h2"/>',
        'split' => '<path d="M6 4v6a4 4 0 0 0 4 4h1m7 6v-6a4 4 0 0 0-4-4h-1m-2 4 3-3-3-3m2 12-3-3 3-3"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'sort' => '<path d="M7 4v16m0 0-3-3m3 3 3-3M17 20V4m0 0-3 3m3-3 3 3"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $hasActiveIdentityFilters = filled($filters['unit_id'] ?? null)
        || filled($filters['class_id'] ?? null)
        || filled($filters['year_id'] ?? null)
        || ($filters['status'] ?? 'active') !== 'active';
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'rapikan-identitas',
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

        <main class="student-page identity-cleanup-page">
            @if (session('success'))
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal success-result">
                        <span class="result-icon">✓</span>
                        <strong>Sukses!</strong>
                        <p>{{ session('success') }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif
            @if (isset($errors) && $errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span>
                        <strong>Data belum bisa digabung</strong>
                        <p>{{ $errors->first() }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            <section class="student-flat-header identity-cleanup-header">
                <div class="student-master-heading">
                    <h1>Rapikan Identitas</h1>
                    <p>Hubungkan data siswa yang sebenarnya satu orang, tetapi terdaftar di beberapa unit atau NIS.</p>
                </div>
            </section>

            <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="identity-cleanup-search-form">
                @if ($filters['per_page'] !== '10')
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                @endif
                @foreach (['unit_id', 'class_id', 'year_id', 'status'] as $filterKey)
                    @if (filled($filters[$filterKey] ?? null))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                    @endif
                @endforeach
                <label>
                    {!! $icon('search') !!}
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cari nama siswa..." aria-label="Cari kandidat identitas">
                </label>
            </form>

            <details class="identity-filter-disclosure" @if($hasActiveIdentityFilters) open @endif>
                <summary>
                    <span>{!! $icon('filter') !!} Filter Data</span>
                    {!! $icon('chevron', 'identity-filter-chevron') !!}
                </summary>
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-filter-panel identity-cleanup-filter">
                @if ($filters['search'] !== '')
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endif
                @if ($filters['per_page'] !== '10')
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                @endif
                <label>
                    <span>Unit Pendidikan</span>
                    <select name="unit_id">
                        <option value="">semua</option>
                        @foreach ($educationUnits as $unit)
                            <option value="{{ $unit->id }}" @selected((string) $filters['unit_id'] === (string) $unit->id)>{{ $unit->code }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Kelas</span>
                    <select name="class_id">
                        <option value="">semua</option>
                        @foreach ($schoolClasses as $class)
                            <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Tahun Pelajaran</span>
                    <select name="year_id">
                        <option value="">semua</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((string) $filters['year_id'] === (string) $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Status Data</span>
                    <select name="status">
                        <option value="all" @selected($filters['status'] === 'all')>Semua</option>
                        <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                    </select>
                </label>
                <div class="student-filter-actions">
                    <button class="button student-search-button" type="submit">Terapkan</button>
                    <a href="{{ route('student-management.identity-cleanup.index') }}" class="button student-filter-reset">Reset</a>
                </div>
                </form>
            </details>

            <section class="identity-cleanup-canvas">
                @php
                    $hasRows = $candidates->count() > 0 || $linkedGroups->isNotEmpty();
                    $shownRows = $candidates->count() + $linkedGroups->count();
                @endphp
                <div class="identity-cleanup-list-head">
                    <span>Menampilkan {{ number_format($shownRows, 0, ',', '.') }} siswa</span>
                    <span class="identity-sort-label">Sortir {!! $icon('sort') !!}</span>
                </div>

                <div class="identity-cleanup-card-list">
                    @if ($hasRows)
                        @foreach ($candidates as $candidate)
                            @php
                                $rowNumber = $candidates->firstItem() + $loop->index;
                                $detailUrl = route('student-management.identity-cleanup.show', [
                                    'candidateKey' => $candidate['key'],
                                    ...request()->query(),
                                ]);
                            @endphp
                            <article class="identity-cleanup-card">
                                <span class="identity-card-number">{{ $rowNumber }}</span>
                                <div class="identity-card-body">
                                    <strong>{{ $candidate['name'] }}</strong>
                                    <span><i></i>{{ $candidate['reason'] }}</span>
                                </div>
                                <a class="identity-card-action identity-card-merge" href="{{ $detailUrl }}" aria-label="Gabung" title="Gabung">{!! $icon('merge') !!}</a>
                            </article>
                        @endforeach
                        @foreach ($linkedGroups as $group)
                            @php
                                $rowNumber = ($candidates->firstItem() ?? 1) + $candidates->count() + $loop->index;
                            @endphp
                            <article class="identity-cleanup-card identity-card-linked">
                                <span class="identity-card-number">{{ $rowNumber }}</span>
                                <div class="identity-card-body">
                                    <strong>{{ $group['name'] }}</strong>
                                    <span><i></i>{{ $group['reason'] }}</span>
                                </div>
                                <form method="POST" action="{{ route('student-management.identity-cleanup.split') }}" class="identity-inline-action">
                                    @csrf
                                    <input type="hidden" name="identity_root_id" value="{{ $group['identity_root_id'] }}">
                                    <button class="identity-card-action identity-card-split" type="submit" aria-label="Pisah" title="Pisah" onclick="return confirm('Pisahkan data identitas ini?')">{!! $icon('split') !!}</button>
                                </form>
                            </article>
                        @endforeach
                    @else
                        <div class="empty-state identity-empty-state">
                            <strong>Belum ada kandidat duplikat</strong>
                            <span>Sistem belum menemukan nama, NISN, tanggal lahir, atau data orang tua yang perlu digabung.</span>
                        </div>
                    @endif
                </div>
                <div class="pagination-wrap">{{ $candidates->links() }}</div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
