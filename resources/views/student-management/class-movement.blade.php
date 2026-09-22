<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'switch' => '<path d="M7 7h11m0 0-4-4m4 4-4 4M17 17H6m0 0 4-4m-4 4 4 4"/>',
        'arrow-up' => '<path d="M12 19V5m0 0-5 5m5-5 5 5"/>',
        'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7Z"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'sort' => '<path d="m7 15 5 5 5-5M7 9l5-5 5 5"/>',
        'sort-up' => '<path d="m7 10 5-5 5 5M12 5v14"/>',
        'sort-down' => '<path d="M12 5v14m-5-5 5 5 5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $isPromotion = $mode === 'promotion';
    $actionRoute = $isPromotion ? route('student-management.class-promotion.store') : route('student-management.class-transfer.store');
    $indexRoute = $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index');
    $studentTotal = method_exists($students, 'total') ? $students->total() : $students->count();
    $studentFirst = $studentTotal > 0 ? (method_exists($students, 'firstItem') ? $students->firstItem() : 1) : 0;
    $studentLast = $studentTotal > 0 ? (method_exists($students, 'lastItem') ? $students->lastItem() : $students->count()) : 0;
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => $section,
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

        <main class="class-movement-standard-page {{ $isPromotion ? 'class-promotion-screen' : 'class-transfer-standard-screen' }}">
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
            @if ($errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span>
                        <strong>Data tidak dapat dipindahkan</strong>
                        <p>{{ $errors->first() }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            <section class="student-workspace student-list-filter-card class-movement-v6-filter">
                <div class="student-flat-header">
                    <div class="student-master-heading">
                        <h1>{{ $title }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                </div>

                <form id="class-movement-filter" method="GET" action="{{ $indexRoute }}" class="class-movement-filter-panel" data-student-filter-panel>
                    <div class="class-movement-filter-grid">
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">Semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">Semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    </div>
                    @if($filters['search'] !== '')<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="class-movement-filter-actions">
                        <button class="button class-movement-apply-button">Terapkan</button>
                        <a href="{{ $indexRoute }}" class="button class-movement-reset-button">Reset</a>
                    </div>
                </form>
            </section>

            <form id="classMovementQueryForm" method="GET" action="{{ $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index') }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="year_id" value="{{ $filters['year_id'] }}">
                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
            </form>

            <section class="card master-card student-data-card student-list-table-card class-movement-data-card">
            <div class="student-reference-card-count student-management-table-toolbar">
                <form method="GET" action="{{ $indexRoute }}" class="student-reference-card-length">
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
                        siswa
                    </label>
                </form>
                <form method="GET" action="{{ $indexRoute }}" class="report-student-search-card student-management-search-card" role="search">
                    @foreach(request()->except(['search', 'page']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>
                        <span>Cari siswa</span>
                        <span class="master-table-search-input">
                            {!! $icon('search') !!}
                            <input name="search" value="{{ $filters['search'] }}" placeholder="Nama atau NIS..." aria-label="Cari siswa">
                        </span>
                    </label>
                </form>
            </div>

            <form method="POST" action="{{ $actionRoute }}" class="class-movement-card class-movement-v6-card {{ $isPromotion ? '' : 'class-transfer-card-mode' }}" data-class-movement-form data-class-movement-action-label="{{ $isPromotion ? 'naikkan kelas' : 'pindahkan kelas' }}">
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $filters['year_id'] }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">

                @if (! $isPromotion)
                <section class="class-movement-list-card">
                    <div class="class-transfer-list-head">
                        <strong>Daftar Siswa</strong>
                    </div>

                    <div class="table-wrap class-movement-table-wrap">
                        <table class="student-hybrid-table class-movement-student-table class-transfer-table">
                            <colgroup>
                                <col class="movement-col-check">
                                <col class="movement-col-nis">
                                <col class="movement-col-name">
                                <col class="movement-col-unit">
                                <col class="movement-col-class">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="movement-check-column">
                                        <label class="class-transfer-check-all">
                                            <span>Pilih</span>
                                            <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                                        </label>
                                    </th>
                                    @include('partials.master-sort-heading', ['column' => 'nis', 'label' => 'NIS', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                    @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Nama Siswa', 'icon' => $icon, 'thClass' => 'movement-name-column'])
                                    @include('partials.master-sort-heading', ['column' => 'unit', 'label' => 'Unit', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                    @include('partials.master-sort-heading', ['column' => 'class', 'label' => 'Kelas Saat Ini', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $student)
                                    <tr class="class-movement-student-row" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                                        <td class="movement-cell-check" data-label="Pilih"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" aria-label="Pilih {{ $student->name }}" data-class-movement-student></td>
                                        <td class="movement-cell-nis" data-label="NIS">{{ $student->nis }}</td>
                                        <td class="movement-cell-main" data-label="Nama Siswa"><strong>{{ $student->name }}</strong></td>
                                        <td class="movement-cell-support" data-label="Unit">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                        <td class="movement-cell-support" data-label="Kelas Saat Ini">{{ $student->schoolClass?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr data-class-movement-empty>
                                        <td colspan="5" class="empty-state class-transfer-empty"><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></td>
                                    </tr>
                                @endforelse
                                @if($students->isNotEmpty())
                                    <tr data-class-movement-empty hidden>
                                        <td colspan="5" class="empty-state class-transfer-empty"><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($students, 'links'))
                        <div class="pagination-wrap class-transfer-pagination">{{ $students->links() }}</div>
                    @endif
                </section>

                <div class="class-transfer-action-panel">
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($targetClasses as $class)
                                <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->educationUnit?->code }} - {{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="class-transfer-selected-count">
                        <span>Terpilih</span>
                        <output data-class-movement-count aria-live="polite">0</output>
                    </div>
                    <input type="hidden" name="target_year_id" value="{{ $filters['year_id'] }}">
                    <button class="button button-primary class-movement-submit" data-class-movement-submit disabled>{!! $icon('switch') !!} Proses Pindah Kelas</button>
                </div>
                @else
                <section class="class-movement-list-card">
                    <div class="class-promotion-list-head">
                        <strong>Daftar Siswa</strong>
                    </div>

                    <div class="table-wrap class-movement-table-wrap">
                        <table class="student-hybrid-table class-movement-student-table class-promotion-table">
                            <colgroup>
                                <col class="movement-col-check">
                                <col class="movement-col-nis">
                                <col class="movement-col-name">
                                <col class="movement-col-unit">
                                <col class="movement-col-class">
                                <col class="movement-col-year">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="movement-check-column">
                                        <label class="class-promotion-check-all">
                                            <span>Pilih</span>
                                            <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                                        </label>
                                    </th>
                                    @include('partials.master-sort-heading', ['column' => 'nis', 'label' => 'NIS', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                    @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Nama Siswa', 'icon' => $icon, 'thClass' => 'movement-name-column'])
                                    @include('partials.master-sort-heading', ['column' => 'unit', 'label' => 'Unit', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                    @include('partials.master-sort-heading', ['column' => 'class', 'label' => 'Kelas Saat Ini', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                    @include('partials.master-sort-heading', ['column' => 'year', 'label' => 'Tahun Pelajaran', 'icon' => $icon, 'thClass' => 'movement-center-column'])
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $student)
                                    <tr class="class-movement-student-row" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                                        <td class="movement-cell-check" data-label="Pilih"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" aria-label="Pilih {{ $student->name }}" data-class-movement-student></td>
                                        <td class="movement-cell-nis" data-label="NIS">{{ $student->nis }}</td>
                                        <td class="movement-cell-main" data-label="Nama Siswa"><strong>{{ $student->name }}</strong></td>
                                        <td class="movement-cell-support" data-label="Unit">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                        <td class="movement-cell-support" data-label="Kelas Saat Ini">{{ $student->schoolClass?->name ?? '-' }}</td>
                                        <td class="movement-cell-support" data-label="Tahun Pelajaran">{{ $student->academicYear?->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr data-class-movement-empty>
                                        <td colspan="6" class="empty-state class-promotion-empty"><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></td>
                                    </tr>
                                @endforelse
                                @if($students->isNotEmpty())
                                    <tr data-class-movement-empty hidden>
                                        <td colspan="6" class="empty-state class-promotion-empty"><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($students, 'links'))
                        <div class="pagination-wrap class-promotion-pagination">{{ $students->links() }}</div>
                    @endif
                </section>

                <div class="class-promotion-action-panel">
                    <label>Tahun Pelajaran Tujuan
                        <select name="target_year_id" required>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('target_year_id', $targetYearId) == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($targetClasses as $class)
                                <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->educationUnit?->code }} - {{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="class-promotion-selected-count">
                        <span>Terpilih</span>
                        <output data-class-movement-count aria-live="polite">0</output>
                    </div>
                    <button class="button button-primary class-movement-submit" data-class-movement-submit disabled>{!! $icon('arrow-up') !!} Proses Naik Kelas</button>
                </div>
                @endif
            </form>
            @php
                $movementCurrentPage = method_exists($students, 'currentPage') ? $students->currentPage() : 1;
                $movementLastPage = method_exists($students, 'lastPage') ? $students->lastPage() : 1;
                $movementStartPage = max(1, $movementCurrentPage - 1);
                $movementEndPage = min($movementLastPage, $movementCurrentPage + 1);
                if ($movementCurrentPage <= 2) {
                    $movementEndPage = min($movementLastPage, 3);
                }
                if ($movementCurrentPage >= $movementLastPage - 1) {
                    $movementStartPage = max(1, $movementLastPage - 2);
                }
                $movementPageUrl = fn ($page) => $indexRoute.'?'.http_build_query(array_merge(request()->except('page'), ['page' => $page]));
                $movementResultSummary = $studentTotal > 0
                    ? 'Menampilkan '.number_format($studentFirst, 0, ',', '.').'-'.number_format($studentLast, 0, ',', '.').' dari '.number_format($studentTotal, 0, ',', '.').' siswa'
                    : 'Menampilkan 0 dari 0 siswa';
            @endphp
            <nav class="class-movement-pagination student-report-pagination" aria-label="Navigasi halaman {{ strtolower($title) }}">
                <p>{{ $movementResultSummary }}</p>
                @if(method_exists($students, 'hasPages') && $students->hasPages())
                    <div class="student-pagination-links">
                        @if($students->onFirstPage())
                            <span class="student-page-button is-disabled" aria-disabled="true">Sebelumnya</span>
                        @else
                            <a class="student-page-button" href="{{ $movementPageUrl($movementCurrentPage - 1) }}" rel="prev">Sebelumnya</a>
                        @endif

                        @if($movementStartPage > 1)
                            <a class="student-page-button is-number" href="{{ $movementPageUrl(1) }}" aria-label="Halaman 1">1</a>
                            @if($movementStartPage > 2)
                                <span class="student-page-ellipsis" aria-hidden="true">...</span>
                            @endif
                        @endif

                        @for($page = $movementStartPage; $page <= $movementEndPage; $page++)
                            @if($page === $movementCurrentPage)
                                <span class="student-page-button is-number is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="student-page-button is-number" href="{{ $movementPageUrl($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if($movementEndPage < $movementLastPage)
                            @if($movementEndPage < $movementLastPage - 1)
                                <span class="student-page-ellipsis" aria-hidden="true">...</span>
                            @endif
                            <a class="student-page-button is-number" href="{{ $movementPageUrl($movementLastPage) }}" aria-label="Halaman {{ $movementLastPage }}">{{ $movementLastPage }}</a>
                        @endif

                        @if($students->hasMorePages())
                            <a class="student-page-button" href="{{ $movementPageUrl($movementCurrentPage + 1) }}" rel="next">Berikutnya</a>
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
