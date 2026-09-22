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
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'merge' => '<path d="M8 7h8m0 0-3-3m3 3-3 3M8 17h8m-8 0 3-3m-3 3 3 3M4 7h2m12 0h2M4 17h2m12 0h2"/>',
        'split' => '<path d="M6 4v6a4 4 0 0 0 4 4h1m7 6v-6a4 4 0 0 0-4-4h-1m-2 4 3-3-3-3m2 12-3-3 3-3"/>',
        'sort' => '<path d="m7 15 5 5 5-5M7 9l5-5 5 5"/>',
        'sort-up' => '<path d="m7 10 5-5 5 5M12 5v14"/>',
        'sort-down' => '<path d="M12 5v14m-5-5 5 5 5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $hasRows = $candidates->count() > 0;
    $totalRows = $candidates->total() ?? 0;
    $firstRow = $totalRows > 0 ? ($candidates->firstItem() ?? 1) : 0;
    $lastRow = $totalRows > 0 ? ($candidates->lastItem() ?? 0) : 0;
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'rapikan-identitas',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar" title="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main id="identity-standard-page" class="student-page identity-standard-page">
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

            <section class="student-workspace student-list-filter-card">
                <div class="student-flat-header identity-cleanup-header">
                    <div class="student-master-heading">
                        <h1>Rapikan Identitas</h1>
                        <p>Hubungkan data siswa yang sebenarnya satu orang, tetapi terdaftar di beberapa unit atau NIS.</p>
                    </div>
                </div>

                <form id="identity-cleanup-filter" method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-filter-panel student-reference-filter student-fee-card-filter" data-student-filter-panel>
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    <div class="student-reference-filter-grid student-fee-card-filter-grid">
                        <label>
                            <span>Unit Pendidikan</span>
                            <select name="unit_id" data-student-filter-unit>
                                <option value="">Semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) $filters['unit_id'] === (string) $unit->id)>{{ $unit->code }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Kelas</span>
                            <select name="class_id" data-student-filter-class>
                                <option value="">Semua</option>
                                @foreach ($schoolClasses as $class)
                                    <option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Tahun Pelajaran</span>
                            <select name="year_id">
                                <option value="">Semua</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected((string) $filters['year_id'] === (string) $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @if($filters['search'] !== '')<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                        <button class="button student-fee-card-search-button fee-type-card-search-button" type="submit">Terapkan</button>
                        <a class="button student-fee-card-reset-button fee-type-card-reset-button" href="{{ route('student-management.identity-cleanup.index') }}">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card student-list-table-card identity-cleanup-table-card">
                <div class="student-reference-card-count student-management-table-toolbar">
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-reference-card-length">
                    @foreach(request()->except(['per_page', 'page', 'status', 'search']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>Tampilkan
                        <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah siswa yang ditampilkan">
                            @foreach([10, 25, 50, 100, 500] as $size)
                                <option value="{{ $size }}" @selected((string) $filters['per_page'] === (string) $size)>{{ $size }}</option>
                            @endforeach
                            <option value="all" @selected($filters['per_page'] === 'all')>Semua</option>
                        </select>
                        kandidat
                    </label>
                </form>
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="report-student-search-card student-management-search-card" role="search">
                    @foreach(request()->except(['search', 'page']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>
                        <span>Cari kandidat</span>
                        <span class="master-table-search-input">
                            {!! $icon('search') !!}
                            <input name="search" value="{{ $filters['search'] }}" placeholder="Nama, NIS, NISN, unit, kelas..." aria-label="Cari kandidat identitas">
                        </span>
                    </label>
                </form>
                </div>

                <div class="table-wrap identity-cleanup-table-wrap">
                    <table class="student-hybrid-table identity-cleanup-table">
                        <colgroup>
                            <col class="identity-col-no">
                            <col class="identity-col-name">
                            <col class="identity-col-reason">
                            <col class="identity-col-unit">
                            <col class="identity-col-confidence">
                            <col class="identity-col-count">
                            <col class="identity-col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="identity-center-column">No</th>
                                @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Nama Kandidat', 'icon' => $icon, 'thClass' => 'identity-name-column'])
                                @include('partials.master-sort-heading', ['column' => 'reason', 'label' => 'Alasan', 'icon' => $icon, 'thClass' => 'identity-reason-column'])
                                <th>Unit/Kelas</th>
                                @include('partials.master-sort-heading', ['column' => 'confidence', 'label' => 'Keyakinan', 'icon' => $icon, 'thClass' => 'identity-center-column'])
                                @include('partials.master-sort-heading', ['column' => 'count', 'label' => 'Jumlah Data', 'icon' => $icon, 'thClass' => 'identity-center-column'])
                                <th class="identity-center-column">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidates as $candidate)
                                @php
                                    $rowNumber = $candidates->firstItem() + $loop->index;
                                    $isLinkedRow = ($candidate['row_type'] ?? 'candidate') === 'linked';
                                    $confidenceClass = match ($candidate['confidence']) {
                                        'Kuat', 'Gabungan' => 'strong',
                                        'Sedang' => 'medium',
                                        default => 'check',
                                    };
                                    $unitSummary = $candidate['students']
                                        ->map(fn ($student) => $student->schoolClass?->educationUnit?->code)
                                        ->filter()
                                        ->unique()
                                        ->implode(' / ');
                                    $classSummary = $candidate['students']
                                        ->map(fn ($student) => $student->schoolClass?->name)
                                        ->filter()
                                        ->unique()
                                        ->take(3)
                                        ->implode(' / ');
                                    $detailUrl = $isLinkedRow ? null : route('student-management.identity-cleanup.show', [
                                        'candidateKey' => $candidate['key'],
                                        ...request()->query(),
                                    ]);
                                @endphp
                                <tr class="identity-cleanup-row">
                                    <td class="identity-cell-no" data-label="No">{{ $rowNumber }}</td>
                                    <td class="identity-cell-main" data-label="Nama Kandidat"><strong>{{ $candidate['name'] }}</strong></td>
                                    <td class="identity-cell-support" data-label="Alasan">{{ $candidate['reason'] }}</td>
                                    <td class="identity-cell-support" data-label="Unit/Kelas">
                                        <span>{{ $unitSummary ?: '-' }}</span>
                                        <small>{{ $classSummary ?: '-' }}</small>
                                    </td>
                                    <td class="identity-cell-center" data-label="Keyakinan"><span class="identity-reset-badge {{ $confidenceClass }}">{{ $candidate['confidence'] }}</span></td>
                                    <td class="identity-cell-center" data-label="Jumlah Data"><span class="identity-reset-mini">{{ $candidate['students']->count() }} data</span></td>
                                    <td class="identity-cell-action" data-label="Aksi">
                                        @if ($isLinkedRow)
                                            <form method="POST" action="{{ route('student-management.identity-cleanup.split') }}">
                                                @csrf
                                                <input type="hidden" name="identity_root_id" value="{{ $candidate['identity_root_id'] }}">
                                                <button class="identity-reset-action danger" type="submit" aria-label="Pisahkan identitas" title="Pisahkan identitas" onclick="return confirm('Pisahkan data identitas ini?')">{!! $icon('split') !!}</button>
                                            </form>
                                        @else
                                            <a class="identity-reset-action" href="{{ $detailUrl }}" aria-label="Tinjau kandidat identitas" title="Tinjau kandidat">{!! $icon('merge') !!}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="identity-cleanup-empty identity-reset-empty">
                                        <strong>Belum ada kandidat identitas pada filter ini.</strong>
                                        <span>Sistem belum menemukan nama, NISN, tanggal lahir, atau data orang tua yang perlu ditinjau.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @php
                    $identityCurrentPage = $candidates->currentPage();
                    $identityLastPage = $candidates->lastPage();
                    $identityStartPage = max(1, $identityCurrentPage - 1);
                    $identityEndPage = min($identityLastPage, $identityCurrentPage + 1);
                    if ($identityCurrentPage <= 2) {
                        $identityEndPage = min($identityLastPage, 3);
                    }
                    if ($identityCurrentPage >= $identityLastPage - 1) {
                        $identityStartPage = max(1, $identityLastPage - 2);
                    }
                    $identityPageUrl = fn ($page) => route('student-management.identity-cleanup.index', array_merge(request()->except('page'), ['page' => $page]));
                    $identityResultSummary = $totalRows > 0
                        ? 'Menampilkan '.number_format($firstRow, 0, ',', '.').'-'.number_format($lastRow, 0, ',', '.').' dari '.number_format($totalRows, 0, ',', '.').' kandidat'
                        : 'Menampilkan 0 dari 0 kandidat';
                @endphp
                <nav class="identity-reset-pagination student-report-pagination" aria-label="Navigasi halaman kandidat identitas">
                    <p>{{ $identityResultSummary }}</p>
                    @if($candidates->hasPages())
                        <div class="student-pagination-links">
                            @if($candidates->onFirstPage())
                                <span class="student-page-button is-disabled" aria-disabled="true">Sebelumnya</span>
                            @else
                                <a class="student-page-button" href="{{ $identityPageUrl($identityCurrentPage - 1) }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if($identityStartPage > 1)
                                <a class="student-page-button is-number" href="{{ $identityPageUrl(1) }}" aria-label="Halaman 1">1</a>
                                @if($identityStartPage > 2)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                            @endif

                            @for($page = $identityStartPage; $page <= $identityEndPage; $page++)
                                @if($page === $identityCurrentPage)
                                    <span class="student-page-button is-number is-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="student-page-button is-number" href="{{ $identityPageUrl($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($identityEndPage < $identityLastPage)
                                @if($identityEndPage < $identityLastPage - 1)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                                <a class="student-page-button is-number" href="{{ $identityPageUrl($identityLastPage) }}" aria-label="Halaman {{ $identityLastPage }}">{{ $identityLastPage }}</a>
                            @endif

                            @if($candidates->hasMorePages())
                                <a class="student-page-button" href="{{ $identityPageUrl($identityCurrentPage + 1) }}" rel="next">Berikutnya</a>
                            @else
                                <span class="student-page-button is-disabled" aria-disabled="true">Berikutnya</span>
                            @endif
                        </div>
                    @endif
                </nav>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
