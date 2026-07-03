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
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $isPromotion = $mode === 'promotion';
    $actionRoute = $isPromotion ? route('student-management.class-promotion.store') : route('student-management.class-transfer.store');
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => $section,
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

        <main class="student-page student-management-page class-movement-page {{ $isPromotion ? 'class-promotion-page' : 'class-transfer-page' }}">
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

            <section class="student-workspace student-list-filter-card class-movement-filter-card class-movement-v6-filter">
                <div class="student-flat-header">
                    <div class="student-master-heading">
                        <h1>{{ $title }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                </div>

                <form method="GET" action="{{ $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index') }}" class="student-filter-panel class-movement-filter" data-student-filter-panel>
                    <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                    <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected($filters['class_id'] == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($filters['year_id'] == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                    <label><span>Status Data</span><select name="status"><option value="all" @selected(($filters['status'] ?? 'active') === 'all')>Semua</option><option value="active" @selected(($filters['status'] ?? 'active') === 'active')>Aktif</option><option value="inactive" @selected(($filters['status'] ?? 'active') === 'inactive')>Nonaktif</option></select></label>
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="student-filter-actions">
                        <button class="button student-search-button">{{ $isPromotion ? 'Tampilkan Siswa' : 'Cari' }}</button>
                        <a href="{{ $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index') }}" class="button student-filter-reset">Reset</a>
                    </div>
                </form>
            </section>

            <form id="classMovementQueryForm" method="GET" action="{{ $isPromotion ? route('student-management.class-promotion.index') : route('student-management.class-transfer.index') }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="year_id" value="{{ $filters['year_id'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'active' }}">
                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
            </form>

            <form method="POST" action="{{ $actionRoute }}" class="card master-card student-data-card student-list-table-card class-movement-card class-movement-v6-card {{ $isPromotion ? '' : 'class-transfer-card-mode' }}" data-class-movement-form data-class-movement-action-label="{{ $isPromotion ? 'naikkan kelas' : 'pindahkan kelas' }}">
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $filters['year_id'] }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'active' }}">

                @if (! $isPromotion)
                <div class="class-transfer-list-head">
                    <strong>Daftar Siswa</strong>
                    <label class="class-transfer-check-all">
                        <span>Pilih Semua</span>
                        <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                    </label>
                </div>

                <div class="class-transfer-local-search">
                    {!! $icon('search') !!}
                    <input type="search" placeholder="Cari siswa..." value="{{ $filters['search'] }}" data-class-movement-search aria-label="Cari siswa berdasarkan nama, NIS, unit, kelas, atau tahun pelajaran">
                </div>

                <div class="class-transfer-student-list">
                    @forelse ($students as $student)
                        <label class="class-transfer-student-card" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" data-class-movement-student>
                            <span class="class-transfer-student-main">
                                <strong>{{ $student->name }}</strong>
                                <span class="class-transfer-student-meta">
                                    <span><small>Unit</small><b>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                    <span><small>Kelas Saat Ini</small><b>{{ $student->schoolClass?->name ?? '-' }}</b></span>
                                </span>
                            </span>
                            <span class="class-transfer-nis">NIS: {{ $student->nis }}</span>
                        </label>
                    @empty
                        <div class="empty-state class-transfer-empty" data-class-movement-empty><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                    @endforelse
                    @if($students->isNotEmpty())
                        <div class="empty-state class-transfer-empty" data-class-movement-empty hidden><strong>Tidak ada siswa</strong><span>Sesuaikan pencarian di daftar siswa.</span></div>
                    @endif
                </div>

                @if(method_exists($students, 'links'))
                    <div class="pagination-wrap class-transfer-pagination">{{ $students->links() }}</div>
                @endif

                <div class="class-transfer-action-panel">
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($classes as $class)
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
                <div class="class-promotion-list-head">
                    <strong>Daftar Siswa</strong>
                    <label class="class-promotion-check-all">
                        <span>Pilih Semua</span>
                        <input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all>
                    </label>
                </div>

                <div class="class-promotion-local-search">
                    {!! $icon('search') !!}
                    <input type="search" placeholder="Cari siswa..." value="{{ $filters['search'] }}" data-class-movement-search aria-label="Cari siswa berdasarkan nama, NIS, unit, kelas, atau tahun pelajaran">
                </div>

                <div class="class-promotion-student-list">
                    @forelse ($students as $student)
                        <label class="class-promotion-student-card" data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" data-class-movement-student>
                            <span class="class-promotion-student-main">
                                <strong>{{ $student->name }}</strong>
                                <span class="class-promotion-student-meta">
                                    <span><small>Unit</small><b>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                    <span><small>Kelas Saat Ini</small><b>{{ $student->schoolClass?->name ?? '-' }}</b></span>
                                    <span><small>Tahun Pelajaran</small><b>{{ $student->academicYear?->name ?? '-' }}</b></span>
                                </span>
                            </span>
                            <span class="class-promotion-nis">NIS: {{ $student->nis }}</span>
                        </label>
                    @empty
                        <div class="empty-state class-promotion-empty" data-class-movement-empty><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div>
                    @endforelse
                    @if($students->isNotEmpty())
                        <div class="empty-state class-promotion-empty" data-class-movement-empty hidden><strong>Tidak ada siswa</strong><span>Sesuaikan pencarian di daftar siswa.</span></div>
                    @endif
                </div>

                @if(method_exists($students, 'links'))
                    <div class="pagination-wrap class-promotion-pagination">{{ $students->links() }}</div>
                @endif

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
                            @foreach ($classes as $class)
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
        </main>
    </div>
</div>
</body>
</html>
