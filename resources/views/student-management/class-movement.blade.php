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
                        <button class="button student-search-button">Cari</button>
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

            <form method="POST" action="{{ $actionRoute }}" class="card master-card student-data-card student-list-table-card class-movement-card class-movement-v6-card" data-class-movement-form data-class-movement-action-label="{{ $isPromotion ? 'naikkan kelas' : 'pindahkan kelas' }}">
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $filters['year_id'] }}">
                <input type="hidden" name="unit_id" value="{{ $filters['unit_id'] }}">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'active' }}">

                <div class="student-table-toolbar class-movement-table-toolbar">
                    <div class="student-table-length">
                        <label>Show
                            <select name="per_page" form="classMovementQueryForm" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)<option value="{{ $size }}" @selected(($filters['per_page'] ?? '10') == (string) $size)>{{ $size }}</option>@endforeach
                                <option value="all" @selected(($filters['per_page'] ?? '10') === 'all')>All</option>
                            </select>
                            entries
                        </label>
                    </div>
                    <div class="student-table-search">
                        <label>Search: <input name="search" form="classMovementQueryForm" value="{{ $filters['search'] }}" aria-label="Cari siswa berdasarkan nama, NIS, unit, kelas, atau tahun pelajaran"></label>
                    </div>
                </div>

                <div class="class-movement-target">
                    <label class="class-movement-selected-field">Siswa Dipilih
                        <output data-class-movement-count aria-live="polite">0</output>
                    </label>
                    @if ($isPromotion)
                        <label>Tahun Pelajaran
                            <select name="target_year_id" required>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(old('target_year_id', $targetYearId) == $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="target_year_id" value="{{ $filters['year_id'] }}">
                    @endif
                    <label>Kelas Tujuan
                        <select name="target_class_id" required data-class-movement-target>
                            <option value="">Pilih kelas tujuan</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('target_class_id') == $class->id)>{{ $class->educationUnit?->code }} - {{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="button button-primary class-movement-submit" data-class-movement-submit disabled>{!! $isPromotion ? $icon('arrow-up') : $icon('switch') !!} {{ $isPromotion ? 'Naikkan Kelas' : 'Pindahkan Kelas' }}</button>
                </div>

                <div class="table-wrap">
                    <table class="data-table student-flat-table class-movement-table class-movement-v6-table" style="table-layout:fixed;width:100%;min-width:0;border-collapse:collapse;border-spacing:0;">
                        <colgroup>
                            <col class="class-movement-col-check" style="width:44px !important">
                            <col class="class-movement-col-no" style="width:39px !important">
                            <col class="class-movement-col-nis" style="width:90px !important">
                            <col class="class-movement-col-name">
                            <col class="class-movement-col-unit" style="width:110px !important">
                            <col class="class-movement-col-class" style="width:160px !important">
                            <col class="class-movement-col-year" style="width:116px !important">
                        </colgroup>
                        <thead><tr>
                            <th class="cm-check-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;"><input type="checkbox" aria-label="Pilih semua siswa" data-class-movement-check-all></th>
                            <th class="cm-no-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">No</th>
                            <th class="cm-nis-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">@include('partials.class-movement-sort-heading', ['column' => 'nis', 'label' => 'NIS'])</th>
                            <th class="cm-name-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">@include('partials.class-movement-sort-heading', ['column' => 'name', 'label' => 'Nama'])</th>
                            <th class="cm-unit-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">@include('partials.class-movement-sort-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th>
                            <th class="cm-class-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">@include('partials.class-movement-sort-heading', ['column' => 'class', 'label' => 'Kelas'])</th>
                            <th class="cm-year-cell" style="font-size:14px!important;font-weight:600!important;line-height:1.25!important;text-align:center!important;text-transform:none!important;">@include('partials.class-movement-sort-heading', ['column' => 'year', 'label' => 'Tahun Pelajaran'])</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr data-class-movement-row data-search="{{ strtolower(implode(' ', [$student->nis, $student->name, $student->schoolClass?->educationUnit?->code ?? '-', $student->schoolClass?->name ?? '-', $student->academicYear?->name ?? '-'])) }}">
                                    <td class="cm-check-cell"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" data-class-movement-student></td>
                                    <td class="cm-no-cell">{{ method_exists($students, 'firstItem') ? $students->firstItem() + $loop->index : $loop->iteration }}</td>
                                    <td class="cm-nis-cell">{{ $student->nis }}</td>
                                    <td class="cm-name-cell" style="text-align:left !important;white-space:normal !important;overflow-wrap:anywhere !important;"><strong>{{ $student->name }}</strong></td>
                                    <td class="cm-unit-cell">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                    <td class="cm-class-cell">{{ $student->schoolClass?->name ?? '-' }}</td>
                                    <td class="cm-year-cell">{{ $student->academicYear?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr data-class-movement-empty><td colspan="7"><div class="empty-state"><strong>Tidak ada siswa</strong><span>Sesuaikan filter sumber untuk menampilkan siswa yang akan diproses.</span></div></td></tr>
                            @endforelse
                            @if($students->isNotEmpty())
                                <tr data-class-movement-empty hidden><td colspan="7"><div class="empty-state"><strong>Tidak ada siswa</strong><span>Sesuaikan pencarian di canvas tabel.</span></div></td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(method_exists($students, 'links'))
                    <div class="pagination-wrap">{{ $students->links() }}</div>
                @endif
            </form>
        </main>
    </div>
</div>
</body>
</html>
