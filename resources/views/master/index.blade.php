<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master Data - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'grid' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'database' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'school' => '<path d="m3 10 9-5 9 5-9 5-9-5Z"/><path d="M6 12v5c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-5"/><path d="M21 10v6"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'upload' => '<path d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'print' => '<path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7zM17 12h.01"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>',
        'role' => '<path d="M12 3 5 6v5c0 4.5 3 8.1 7 10 4-1.9 7-5.5 7-10V6l-7-3Z"/><path d="M9 12l2 2 4-5"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'finance' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M7 5V3h10v2M3 10h18M7 15h3"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5M12 7v5l3 2"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $tabs = [
        'academic-years' => ['Tahun Pelajaran', 'calendar', $academicYears->count()],
        'education-units' => ['Unit Pendidikan', 'database', $stats['education_units']],
        'classes' => ['Kelas', 'database', $stats['classes']],
        'fee-types' => ['Kategori Pembayaran', 'receipt', $stats['fee_types']],
        'data-roles' => ['Data Role', 'role', $stats['roles'] ?? 0],
        'data-users' => ['Data User', 'users', $stats['users'] ?? 0],
    ];
    $labels = [
        'students' => ['Data Siswa', 'Kelola identitas, kelas, wali, dan status siswa.', 'Tambah Data Siswa'],
        'education-units' => ['Unit Pendidikan', 'Daftar unit pendidikan yang tersedia.', 'Tambah Unit Pendidikan'],
        'classes' => ['Kelas', 'Daftar kelas yang tersedia.', 'Tambah Kelas'],
        'academic-years' => ['Tahun Pelajaran', 'Daftar tahun pelajaran yang tersedia.', 'Tambah Tahun Pelajaran'],
        'fee-types' => ['Kategori Pembayaran', 'Atur SPP, daftar ulang, laundry, dan pembayaran lainnya.', 'Tambah Kategori Pembayaran'],
        'fee-discounts' => ['Keringanan Biaya', 'Atur potongan SPP atau pembayaran lainnya untuk siswa.', 'Tambah Keringanan Biaya'],
        'data-roles' => ['Data Role', 'Kelola role pengguna sistem.', 'Tambah Role'],
        'data-users' => ['Data User', 'Kelola akun pengguna dan role aksesnya.', 'Tambah User'],
    ];
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => $tab === 'students' ? 'students' : 'master',
        'activeStudentMenu' => $tab === 'students' ? 'data-siswa' : '',
        'activeMasterMenu' => $tab !== 'students' ? $tab : '',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ optional($academicYears->firstWhere('is_active', true))->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}<span></span></button>
            <button class="icon-button logout-button" type="button" aria-label="Keluar" title="Keluar">{!! $icon('logout') !!}</button>
        </header>
        <main class="{{ $showCreate ? ($tab === 'students' ? 'student-page student-create-page' : 'master-create-page'.($tab === 'academic-years' ? ' academic-year-create-page' : '').($tab === 'education-units' ? ' education-unit-create-page' : '').($tab === 'classes' ? ' class-create-page' : '').($tab === 'fee-types' ? ' fee-type-create-page' : '').($tab === 'fee-discounts' ? ' fee-discount-create-page' : '').($tab === 'data-roles' ? ' data-role-create-page' : '').($tab === 'data-users' ? ' data-user-create-page' : '')) : ($tab === 'students' ? ('student-page'.($showStudentImport ? ' student-import-page' : '')) : 'student-page master-flat-page'.($tab === 'academic-years' ? ' academic-year-page' : '').($tab === 'education-units' ? ' education-unit-page' : '').($tab === 'classes' ? ' class-page' : '').($tab === 'fee-types' ? ' fee-type-page' : '').($tab === 'fee-discounts' ? ' fee-discount-page' : '').($tab === 'data-roles' ? ' data-role-page' : '').($tab === 'data-users' ? ' data-user-page' : '')) }}">
            @if (session('success'))
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal success-result">
                        <span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ session('error') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span><strong>Data Belum Dapat Disimpan</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            @if ($showCreate)
                @if ($tab === 'students')
                <section class="student-workspace student-create-canvas">
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>{{ $labels[$tab][2] }}</h1>
                            <p>Lengkapi identitas, kelas, wali, dan status siswa.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('master.'.$tab.'.store') }}" class="master-form master-create-form student-create-form">
                        @csrf
                        @include('master.partials.form-fields')
                        <div class="form-actions span-2"><a href="{{ route('student-management.students.index') }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Data</button></div>
                    </form>
                </section>
                @else
                @php
                    $createDescriptions = [
                        'classes' => 'Pastikan unit pendidikan, nama kelas, dan status aktif sudah sesuai.',
                        'fee-types' => 'Pastikan kategori, unit, kelas, nominal, dan status aktif sudah sesuai.',
                        'fee-discounts' => 'Pastikan siswa, jenis pembayaran, nilai keringanan, tanggal, dan status aktif sudah sesuai.',
                        'data-roles' => 'Pastikan nama role, kode, dan hak akses sudah sesuai.',
                        'data-users' => 'Pastikan identitas user, email, dan role akses sudah sesuai.',
                    ];

                    $createDescription = $createDescriptions[$tab] ?? 'Pastikan data yang dimasukkan sudah benar.';
                @endphp
                <section class="hero master-hero">
                    <div>@if (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true))<p class="eyebrow">Pengelolaan Data · Tambah</p>@endif<h1>{{ $labels[$tab][2] }}</h1><p>Lengkapi formulir berikut untuk menambahkan data baru.</p></div>
                </section>
                <section class="card master-create-card">
                    @if (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true))
                    <div class="master-create-heading"><div><strong>Informasi {{ $labels[$tab][0] }}</strong><span>{{ $createDescription }}</span></div></div>
                    @endif
                    <form method="POST" action="{{ route('master.'.$tab.'.store') }}" class="master-form master-create-form">
                        @csrf
                        @include('master.partials.form-fields')
                        <div class="form-actions span-2"><a href="{{ $tab === 'students' ? route('student-management.students.index') : route('master.index', ['tab' => $tab]) }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Data</button></div>
                    </form>
                </section>
                @endif
            @else
            @if ($tab === 'students' && $showStudentImport)
                <section class="student-workspace student-import-page-canvas">
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>Import Data Siswa</h1>
                            <p>Unggah file Excel, periksa preview, lalu konfirmasi data yang valid.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('master.students.import.preview') }}" enctype="multipart/form-data" class="student-import-page-form">
                        @csrf
                        <label class="student-import-page-upload">
                            <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required data-spp-import-file>
                            <span class="student-import-upload-icon">{!! $icon('upload') !!}</span>
                            <strong data-spp-import-filename>Pilih file Excel data siswa</strong>
                            <small>Format XLSX, maksimal 5 MB. Gunakan template agar kolom sesuai.</small>
                        </label>
                        <div class="student-import-page-actions">
                            <a href="{{ route('master.students.template') }}" class="button button-secondary">{!! $icon('download') !!} Download Template</a>
                            <button class="button button-primary">{!! $icon('upload') !!} Preview Data</button>
                        </div>
                    </form>
                </section>

                @if($studentImportPreview)
                    @include('master.partials.student-import-preview')
                @endif
            @else
            @if ($tab !== 'students')
            <section class="student-workspace master-flat-workspace {{ in_array($tab, ['fee-types', 'fee-discounts', 'data-roles', 'data-users'], true) ? 'master-filter-card' : '' }} {{ $tab === 'fee-types' ? 'master-fee-filter-card' : '' }} {{ $tab === 'fee-discounts' ? 'master-discount-filter-card' : '' }} {{ $tab === 'data-roles' ? 'master-role-filter-card' : '' }} {{ $tab === 'data-users' ? 'master-user-filter-card' : '' }}">
                <div class="student-flat-header">
                    @if (in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true))
                    <div class="master-sample-heading">
                        <h1>{{ $labels[$tab][0] }}</h1>
                        <p>{{ $labels[$tab][1] }}</p>
                    </div>
                    @else
                    <h1>{{ $labels[$tab][0] }}</h1>
                    @endif
                    <div class="student-title-actions">
                        <a href="{{ route('master.create', ['tab' => $tab]) }}" class="button student-add-button {{ in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true) ? 'master-primary-add-button' : '' }} {{ in_array($tab, ['academic-years', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true) ? 'academic-year-add-button' : '' }}">{!! $icon('plus') !!} {{ in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true) ? 'Tambah' : $labels[$tab][2] }}</a>
                    </div>
                </div>
            </section>
            @endif

            @if ($tab === 'students')
                <section class="student-workspace student-list-filter-card student-reference-align-lock">
                    @php
                        $studentExportQuery = array_filter([
                            'unit_id' => request('unit_id'),
                            'class_id' => request('class_id'),
                            'year_id' => $studentYearId,
                            'search' => request('search'),
                        ], fn ($value) => filled($value));
                        $canClassAlumni = request()->filled('unit_id')
                            && request()->filled('class_id')
                            && $studentStatus === 'active'
                            && ($studentClassAlumniCount ?? 0) > 0
                            && $studentClassAlumniClass
                            && $studentClassAlumniYear;
                    @endphp
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>Data Siswa</h1>
                            <p>Kelola identitas, kelas, wali, dan status siswa.</p>
                        </div>
                        <div class="student-title-actions">
                            <a href="{{ route('student-management.students.create') }}" class="button student-add-button">{!! $icon('plus') !!} Tambah</a>
                            @if ($canClassAlumni)
                                <a href="{{ route('student-management.students.class-alumni.create', ['unit_id' => request('unit_id'), 'class_id' => request('class_id'), 'year_id' => $studentYearId]) }}" class="button student-add-button">{!! $icon('check') !!} Jadikan Alumni</a>
                            @endif
                            <a href="{{ route('student-management.students.import') }}" class="button action-purple">{!! $icon('upload') !!} Import</a>
                            <a href="{{ route('master.students.export', $studentExportQuery) }}" class="button action-green">{!! $icon('download') !!} Export</a>
                        </div>
                    </div>

                    <form id="student-data-filter" method="GET" action="{{ route('student-management.students.index') }}" class="student-filter-panel student-reference-filter student-fee-card-filter" data-student-filter-panel>
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                        <div class="student-reference-filter-grid student-fee-card-filter-grid">
                            <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                            <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                            <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($studentYearId == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        </div>
                        <label class="student-reference-search student-fee-filter-search">
                            <span>Cari siswa</span>
                            {!! $icon('search') !!}
                            <input name="search" value="{{ request('search') }}" placeholder="Nama atau NIS..." aria-label="Cari nama atau NIS">
                        </label>
                        <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                            <button class="button student-fee-card-search-button fee-type-card-search-button" aria-label="Tampilkan data">Terapkan</button>
                            <a href="{{ route('student-management.students.index') }}" class="button student-fee-card-reset-button fee-type-card-reset-button">Reset</a>
                        </div>
                    </form>
                </section>
            @endif
            @if ($tab !== 'students' && ! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users']))
            <section class="master-stats">
                <div><span class="metric-icon blue">{!! $icon('users') !!}</span><p>Total Siswa<strong>{{ number_format($stats['students'], 0, ',', '.') }}</strong><small>{{ $stats['active_students'] }} aktif</small></p></div>
                <div><span class="metric-icon green">{!! $icon('database') !!}</span><p>Unit Pendidikan<strong>{{ $stats['education_units'] }}</strong><small>Unit aktif</small></p></div>
                <div><span class="metric-icon indigo">{!! $icon('database') !!}</span><p>Total Kelas<strong>{{ $stats['classes'] }}</strong><small>Seluruh jenjang</small></p></div>
                <div><span class="metric-icon blue">{!! $icon('receipt') !!}</span><p>Kategori Pembayaran<strong>{{ $stats['fee_types'] }}</strong><small>Kategori aktif</small></p></div>
            </section>
            @endif

            @if ($tab === 'academic-years')
            <section class="academic-year-card-section">
                <div class="academic-year-card-grid">
                    @forelse ($data as $row)
                        @php
                            $periodLabel = $row->is_active
                                ? 'Periode Berjalan'
                                : ($row->start_date && $row->start_date->isFuture()
                                    ? 'Periode Mendatang'
                                    : ($row->end_date && $row->end_date->isPast() ? 'Periode Selesai' : 'Arsip Data'));
                        @endphp
                        <article class="academic-year-card {{ $row->is_active ? 'is-active' : '' }}">
                            <div class="academic-year-card-top">
                                <div class="academic-year-card-main">
                                    <span class="academic-year-card-icon" aria-hidden="true">
                                        {!! $row->is_active ? $icon('calendar') : $icon($periodLabel === 'Periode Mendatang' ? 'clock' : 'history') !!}
                                    </span>
                                    <span>
                                        <strong>{{ $row->name }}</strong>
                                        <small>{{ $periodLabel }}</small>
                                    </span>
                                </div>
                                <span class="academic-year-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">
                                    @if($row->is_active)<i></i>@endif
                                    {{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </div>
                            <div class="academic-year-card-dates">
                                <span>Mulai: <strong>{{ $row->start_date ? $row->start_date->translatedFormat('d M Y') : '-' }}</strong></span>
                                <span>Sampai: <strong>{{ $row->end_date ? $row->end_date->translatedFormat('d M Y') : '-' }}</strong></span>
                            </div>
                            <div class="academic-year-card-footer">
                                @include('master.partials.actions', ['type' => 'academic-years', 'row' => $row])
                            </div>
                        </article>
                    @empty
                        <div class="academic-year-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan tahun pelajaran baru untuk mulai mengatur periode akademik.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @elseif ($tab === 'education-units')
            <section class="education-unit-card-section">
                <div class="education-unit-card-grid">
                    @forelse ($data as $row)
                        <article class="education-unit-card {{ $row->is_active ? 'is-active' : '' }}">
                            <div class="education-unit-card-top">
                                <div class="education-unit-card-badges">
                                    <span class="education-unit-code">{{ $row->code }}</span>
                                    <span class="education-unit-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                                </div>
                                @include('master.partials.actions', ['type' => 'education-units', 'row' => $row])
                            </div>
                            <div class="education-unit-card-name">
                                <span>{!! $icon('school') !!}</span>
                                <strong>{{ $row->name }}</strong>
                            </div>
                            <div class="education-unit-card-meta">
                                <span>Jumlah Kelas</span>
                                <strong>{{ $row->school_classes_count }}</strong>
                            </div>
                        </article>
                    @empty
                        <div class="education-unit-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan unit pendidikan untuk mulai mengelola kelas dan data siswa.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @elseif ($tab === 'classes')
            <section class="class-card-section">
                <form method="GET" action="{{ route('master.index') }}" class="class-card-filter">
                    <input type="hidden" name="tab" value="classes">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                    <div class="class-card-filter-grid">
                        <label>
                            <span>Unit Pendidikan</span>
                            <select name="unit_id" aria-label="Filter unit pendidikan">
                                <option value="">semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Tahun Pelajaran</span>
                            <select name="year_id" aria-label="Filter tahun pelajaran">
                                <option value="">semua</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected($classYearId == $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Status Data</span>
                            <select name="status" aria-label="Filter status data">
                                <option value="">Semua Status</option>
                                <option value="active" @selected($classStatus === 'active')>Aktif</option>
                                <option value="inactive" @selected($classStatus === 'inactive')>Tidak Aktif</option>
                            </select>
                        </label>
                    </div>
                    <label class="class-card-search">
                        {!! $icon('search') !!}
                        <input name="search" value="{{ request('search') }}" placeholder="Cari kelas..." aria-label="Cari kelas">
                    </label>

                    <div class="class-card-filter-actions">
                        <button class="button class-card-search-button">Terapkan</button>
                        <a href="{{ route('master.index', ['tab' => 'classes']) }}" class="button class-card-reset-button">Reset</a>
                    </div>
                </form>

                <div class="class-card-count">
                    <form method="GET" action="{{ route('master.index') }}" class="class-card-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="hidden" name="tab" value="classes">
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah kelas yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', '25') === (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            kelas
                        </label>
                    </form>
                    <span>
                        {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' kelas' : 'Menampilkan 0 dari 0 kelas' }}
                    </span>
                </div>

                <div class="class-card-list">
                    @forelse ($data as $row)
                        <article class="class-card-item {{ $row->is_active ? 'is-active' : '' }}">
                            <div class="class-card-top">
                                <div class="class-card-title">
                                    <span class="class-card-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                                    <strong>{{ $row->name }}</strong>
                                </div>
                                @include('master.partials.actions', ['type' => 'classes', 'row' => $row])
                            </div>
                            <div class="class-card-meta">
                                <span>{!! $icon('school') !!}<b>{{ $row->educationUnit?->name ?? '-' }}</b></span>
                                <span>{!! $icon('users') !!}<b>{{ $row->students_count }} Siswa</b></span>
                            </div>
                        </article>
                    @empty
                        <div class="class-card-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan kelas baru untuk mulai mengatur rombel dan siswa.</span>
                        </div>
                    @endforelse
                </div>

            </section>
            @elseif ($tab === 'fee-types')
            <section class="fee-type-card-section">
                <form method="GET" action="{{ route('master.index') }}" class="fee-type-card-filter">
                    <input type="hidden" name="tab" value="fee-types">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                    <div class="fee-type-card-filter-grid">
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit aria-label="Filter unit pendidikan"><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class aria-label="Filter kelas"><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id" aria-label="Filter tahun pelajaran"><option value="">semua</option>@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($feeTypeYearId == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        <label><span>Status Data</span><select name="status" aria-label="Filter status data"><option value="">Semua Status</option><option value="active" @selected($feeTypeStatus === 'active')>Aktif</option><option value="inactive" @selected($feeTypeStatus === 'inactive')>Nonaktif</option></select></label>
                    </div>
                    <label class="fee-type-filter-search">
                        {!! $icon('search') !!}
                        <input name="search" value="{{ request('search') }}" placeholder="Cari kategori pembayaran..." aria-label="Cari kategori pembayaran">
                    </label>

                    <div class="fee-type-card-filter-actions">
                        <button class="button fee-type-card-search-button">Terapkan</button>
                        <a href="{{ route('master.index', ['tab' => 'fee-types']) }}" class="button fee-type-card-reset-button">Reset</a>
                    </div>
                </form>

                <div class="fee-type-card-count">
                    <form method="GET" action="{{ route('master.index') }}" class="fee-type-card-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="hidden" name="tab" value="fee-types">
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah kategori pembayaran yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', '25') === (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            kategori
                        </label>
                    </form>
                    <span>
                        {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' kategori' : 'Menampilkan 0 dari 0 kategori' }}
                    </span>
                </div>

                <div class="fee-type-card-list">
                    @forelse ($data as $row)
                        @php
                            $behaviorLabel = $row->creates_bill ? 'Tagihan Wajib' : 'Pembayaran Opsional';
                        @endphp
                        <article class="fee-type-card-item {{ $row->is_active ? 'is-active' : 'is-inactive' }}">
                            <div class="fee-type-card-top">
                                <div class="fee-type-card-title">
                                    <strong>{{ $row->name }}</strong>
                                    <span>{{ $behaviorLabel }}</span>
                                </div>
                                <span class="fee-type-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </div>
                            <div class="fee-type-card-meta">
                                <div class="fee-type-card-year">
                                    <span>Tahun Pelajaran</span>
                                    <strong>{{ $row->academicYear?->name ?? '-' }}</strong>
                                </div>
                                <div class="fee-type-card-amount">
                                    <span>Nominal</span>
                                    <strong>Rp {{ number_format($row->amount, 0, ',', '.') }}</strong>
                                </div>
                                <div class="fee-type-card-unit">
                                    <span>Unit / Tingkat</span>
                                    <strong>{{ $row->educationUnit?->code ?? '-' }} · {{ $row->class_level ? \App\Support\ClassLevel::label($row->class_level) : ($row->schoolClass?->name ?? 'Semua Tingkat') }}</strong>
                                </div>
                                <div class="fee-type-card-actions">
                                    @include('master.partials.actions', ['type' => 'fee-types', 'row' => $row])
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="fee-type-card-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan kategori pembayaran untuk mulai mengatur tagihan dan transaksi.</span>
                        </div>
                    @endforelse
                </div>
            </section>
            @elseif ($tab === 'fee-discounts')
            <section class="fee-discount-card-section">
                <form method="GET" action="{{ route('master.index') }}" class="fee-discount-card-filter">
                    <input type="hidden" name="tab" value="fee-discounts">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                    <div class="fee-discount-card-filter-grid">
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit aria-label="Filter unit pendidikan"><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class aria-label="Filter kelas"><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id" aria-label="Filter tahun pelajaran"><option value="">semua</option>@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($feeDiscountYearId == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        <label><span>Status Data</span><select name="status" aria-label="Filter status data"><option value="">Semua Status</option><option value="active" @selected($feeDiscountStatus === 'active')>Aktif</option><option value="inactive" @selected($feeDiscountStatus === 'inactive')>Nonaktif</option></select></label>
                    </div>
                    <label class="fee-discount-filter-search">
                        {!! $icon('search') !!}
                        <input name="search" value="{{ request('search') }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
                    </label>

                    <div class="fee-discount-card-filter-actions">
                        <button class="button fee-discount-card-search-button">Terapkan</button>
                        <a href="{{ route('master.index', ['tab' => 'fee-discounts']) }}" class="button fee-discount-card-reset-button">Reset</a>
                    </div>
                </form>

                <div class="fee-discount-card-count">
                    <form method="GET" action="{{ route('master.index') }}" class="fee-discount-card-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="hidden" name="tab" value="fee-discounts">
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah keringanan biaya yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', '25') === (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            keringanan
                        </label>
                    </form>
                    <span>
                        {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' keringanan' : 'Menampilkan 0 dari 0 keringanan' }}
                    </span>
                </div>

                <div class="fee-discount-card-list">
                    @forelse ($data as $row)
                        @php
                            $student = $row->student;
                            $studentClass = $student?->schoolClass;
                            $studentUnit = $studentClass?->educationUnit;
                            $paymentLabel = $row->source_type === 'spp' ? 'SPP' : ($row->feeType?->name ?? '-');
                            $discountLabel = $row->discount_type === 'percentage'
                                ? number_format($row->discount_value, 0, ',', '.').'%'
                                : 'Rp '.number_format($row->discount_value, 0, ',', '.');
                            $effectiveLabel = $row->end_date
                                ? 's/d '.$row->end_date->translatedFormat('M Y')
                                : ($row->start_date ? 'Mulai '.$row->start_date->translatedFormat('M Y') : '-');
                        @endphp
                        <article class="fee-discount-card-item {{ $row->is_active ? 'is-active' : 'is-inactive' }}">
                            <div class="fee-discount-card-top">
                                <div class="fee-discount-card-title">
                                    <span class="fee-discount-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                                    <strong>{{ $student?->name ?? '-' }}</strong>
                                    <small>{{ $paymentLabel }} · {{ $studentUnit?->code ?? '-' }} · {{ $studentClass?->name ?? '-' }}</small>
                                </div>
                                <div class="fee-discount-card-actions">
                                    @include('master.partials.actions', ['type' => 'fee-discounts', 'row' => $row])
                                </div>
                            </div>
                            <div class="fee-discount-card-meta">
                                <div>
                                    <span>Potongan</span>
                                    <strong>{{ $discountLabel }}</strong>
                                    @if ($row->discount_amount)
                                        <small>Setara Rp {{ number_format($row->discount_amount, 0, ',', '.') }}</small>
                                    @endif
                                </div>
                                <div>
                                    <span>{{ $row->end_date ? 'Berlaku' : 'Periode' }}</span>
                                    <strong>{{ $effectiveLabel }}</strong>
                                    <small>{{ $row->reason ?: 'Tanpa keterangan' }}</small>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="fee-discount-card-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan keringanan biaya untuk siswa yang memenuhi ketentuan.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @elseif ($tab === 'data-roles')
            <section class="data-role-card-section">
                <form method="GET" action="{{ route('master.index') }}" class="data-role-card-filter">
                    <input type="hidden" name="tab" value="data-roles">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                    <label class="data-role-filter-search">
                        {!! $icon('search') !!}
                        <input name="search" value="{{ request('search') }}" placeholder="Cari role..." aria-label="Cari role">
                    </label>

                    <div class="data-role-card-filter-actions">
                        <button class="button data-role-card-search-button">Terapkan</button>
                        <a href="{{ route('master.index', ['tab' => 'data-roles']) }}" class="button data-role-card-reset-button">Reset</a>
                    </div>
                </form>

                <div class="data-role-card-count">
                    <form method="GET" action="{{ route('master.index') }}" class="data-role-card-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="hidden" name="tab" value="data-roles">
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah role yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', '25') === (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            role
                        </label>
                    </form>
                    <span>
                        {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' role' : 'Menampilkan 0 dari 0 role' }}
                    </span>
                </div>

                <div class="data-role-card-list">
                    @forelse ($data as $row)
                        @php
                            $permissionLabels = $row->permissionLabels();
                            $permissionCount = count($row->permissions ?? []);
                            $permissionPreview = $permissionLabels
                                ? implode(', ', array_slice($permissionLabels, 0, 4)).(count($permissionLabels) > 4 ? ', ...' : '')
                                : 'Belum ada hak akses';
                        @endphp
                        <article class="data-role-card-item {{ $row->is_active ? 'is-active' : 'is-inactive' }}">
                            <div class="data-role-card-top">
                                <div class="data-role-card-title">
                                    <div class="data-role-card-heading">
                                        <strong>{{ $row->name }}</strong>
                                        <span class="data-role-code">{{ $row->key }}</span>
                                    </div>
                                    <small>{{ $row->description ?: 'Tanpa deskripsi' }}</small>
                                </div>
                                <div class="data-role-card-actions">
                                    @include('master.partials.actions', ['type' => 'data-roles', 'row' => $row])
                                </div>
                            </div>
                            <div class="data-role-card-access">
                                <span class="data-role-access-count">{{ $permissionCount }} akses</span>
                                <span>Hak Akses:</span>
                                <p>{{ $permissionPreview }}</p>
                            </div>
                            <div class="data-role-card-footer">
                                <div>
                                    <span>Jumlah User</span>
                                    <strong>{{ $row->users_count }} Personel</strong>
                                </div>
                                <span class="data-role-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="data-role-card-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan role untuk mulai mengatur hak akses pengguna sistem.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @elseif ($tab === 'data-users')
            <section class="data-user-card-section">
                <form method="GET" action="{{ route('master.index') }}" class="data-user-card-filter">
                    <input type="hidden" name="tab" value="data-users">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                    <label class="data-user-filter-search">
                        {!! $icon('search') !!}
                        <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." aria-label="Cari nama atau email">
                    </label>
                    <label class="data-user-filter-role"><span>Role</span><select name="role" aria-label="Filter role"><option value="">Semua Role</option>@foreach ($roleOptions as $key => $name)<option value="{{ $key }}" @selected(request('role') === $key)>{{ $name }}</option>@endforeach</select></label>

                    <div class="data-user-card-filter-actions">
                        <button class="button data-user-card-search-button">Terapkan</button>
                        <a href="{{ route('master.index', ['tab' => 'data-users']) }}" class="button data-user-card-reset-button">Reset</a>
                    </div>
                </form>

                <div class="data-user-card-count">
                    <form method="GET" action="{{ route('master.index') }}" class="data-user-card-length">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="hidden" name="tab" value="data-users">
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah user yang ditampilkan">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', '25') === (string) $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            user
                        </label>
                    </form>
                    <span>
                        {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' user' : 'Menampilkan 0 dari 0 user' }}
                    </span>
                </div>

                <div class="data-user-card-list">
                    @forelse ($data as $row)
                        @php
                            $roleLabel = $row->roleLabel();
                            $roleKey = $row->role ?: 'belum-diatur';
                            $initials = collect(explode(' ', trim($row->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                                ->implode('') ?: 'U';
                        @endphp
                        <article class="data-user-card-item">
                            <div class="data-user-card-top">
                                <div class="data-user-card-profile">
                                    <span class="data-user-avatar">{{ $initials }}</span>
                                    <span>
                                        <strong>{{ $row->name }}</strong>
                                        <small>{{ '@'.$row->username }}</small>
                                    </span>
                                </div>
                                <span class="data-user-role">{{ $roleLabel }}</span>
                            </div>
                            <div class="data-user-card-meta">
                                <div>
                                    <span>Email</span>
                                    <strong>{{ $row->email ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Kode Role</span>
                                    <strong>{{ $roleKey }}</strong>
                                </div>
                            </div>
                            <div class="data-user-card-footer">
                                <span>Akun pengguna sistem</span>
                                <div class="data-user-card-actions">
                                    @include('master.partials.actions', ['type' => 'data-users', 'row' => $row])
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="data-user-card-empty">
                            <strong>Data belum tersedia</strong>
                            <span>Tambahkan user untuk mulai mengatur akun dan role akses.</span>
                        </div>
                    @endforelse
                </div>

                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @else
            <section class="card master-card student-data-card {{ $tab === 'students' ? 'student-list-table-card student-reference-align-lock' : 'master-flat-card' }} {{ $tab === 'education-units' ? 'education-unit-table-card' : '' }} {{ $tab === 'classes' ? 'class-table-card' : '' }} {{ $tab === 'fee-types' ? 'fee-type-table-card' : '' }} {{ $tab === 'fee-discounts' ? 'fee-discount-table-card' : '' }} {{ $tab === 'data-roles' ? 'data-role-table-card' : '' }} {{ $tab === 'data-users' ? 'data-user-table-card' : '' }}">
                @if (! in_array($tab, ['students', 'academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users']))
                <div class="master-tabs">
                    @foreach ($tabs as $key => $item)
                        <a href="{{ route('master.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{!! $icon($item[1]) !!}<span>{{ $item[0] }}</span><b>{{ $item[2] }}</b></a>
                    @endforeach
                </div>
                @endif
                @if (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users']))
                @if ($tab !== 'students')
                <div class="table-toolbar">
                    <form method="GET" action="{{ route('master.index') }}" class="table-search">
                        <input type="hidden" name="tab" value="{{ $tab }}">{!! $icon('search') !!}<input name="search" value="{{ request('search') }}" placeholder="Cari {{ strtolower($labels[$tab][0]) }}...">
                        @if (in_array($tab, ['students', 'classes']))
                            <select name="unit_id" onchange="this.form.submit()"><option value="">Semua Unit</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select>
                        @endif
                        @if ($tab === 'students')
                            <select name="class_id" onchange="this.form.submit()"><option value="">Semua Kelas</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select>
                        @endif
                    </form>
                    <span>Menampilkan {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data</span>
                </div>
                @endif
                @endif

                @if($tab === 'students')
                    <div class="student-table-toolbar">
                        <form method="GET" action="{{ route('student-management.students.index') }}" class="student-table-length">
                            @foreach(request()->except(['per_page', 'page', 'status']) as $key => $value)
                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <label>Show
                                <select name="per_page" onchange="this.form.submit()">
                                    @foreach([10, 25, 50, 100, 500] as $size)<option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>@endforeach
                                    <option value="all" @selected(request('per_page') === 'all')>All</option>
                                </select>
                                entries
                            </label>
                        </form>
                        <form method="GET" action="{{ route('student-management.students.index') }}" class="student-table-search">
                            @foreach(request()->except(['search', 'page', 'status']) as $key => $value)
                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari siswa berdasarkan nama, NIS, atau NISN..."></label>
                        </form>
                    </div>
                    <div class="student-reference-card-count">
                        <form method="GET" action="{{ route('student-management.students.index') }}" class="student-reference-card-length">
                            @foreach(request()->except(['per_page', 'page', 'status']) as $key => $value)
                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <label>Tampilkan
                                <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah siswa yang ditampilkan">
                                    @foreach([10, 25, 50, 100, 500] as $size)
                                        <option value="{{ $size }}" @selected((string) request('per_page', '10') === (string) $size)>{{ $size }}</option>
                                    @endforeach
                                    <option value="all" @selected(request('per_page') === 'all')>All</option>
                                </select>
                                siswa
                            </label>
                        </form>
                        <span>
                            {{ ($data->total() ?? 0) > 0 ? 'Menampilkan '.number_format($data->firstItem(), 0, ',', '.').'-'.number_format($data->lastItem(), 0, ',', '.').' dari '.number_format($data->total(), 0, ',', '.').' siswa' : 'Menampilkan 0 dari 0 siswa' }}
                        </span>
                    </div>

                    <div class="student-reference-card-list">
                        @forelse ($data as $row)
                            <article class="student-reference-card">
                                <div class="student-reference-card-top">
                                    <div class="student-reference-card-title">
                                        <strong>{{ $row->name }}</strong>
                                        <div class="student-reference-card-meta">
                                            <span>{!! $icon('card') !!}<b>{{ $row->nis ?: '-' }}</b></span>
                                            <span>{!! $icon('school') !!}<b>{{ $row->schoolClass?->educationUnit?->code ?? '-' }}</b></span>
                                            <span>{!! $icon('users') !!}<b>{{ $row->schoolClass?->name ?? '-' }}</b></span>
                                            <span>{!! $icon('info') !!}<b>{{ $row->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</b></span>
                                        </div>
                                    </div>
                                    <div class="student-reference-card-side">
                                        <span class="student-reference-status {{ $row->is_active ? 'is-active' : 'is-inactive' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                                        <div class="student-reference-card-actions">
                                            @include('master.partials.actions', ['type' => 'students', 'row' => $row, 'studentCardAction' => true])
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="student-reference-empty">
                                <strong>Data belum tersedia</strong>
                                <span>Belum ada siswa yang sesuai dengan filter saat ini.</span>
                            </div>
                        @endforelse
                    </div>
                @else
                    @include('partials.list-toolbar', [
                        'action' => route('master.index'),
                        'searchLabel' => 'Cari data master',
                        'unitFilter' => null,
                    ])
                @endif
                <div class="table-wrap"><table class="data-table {{ $tab === 'students' ? 'student-flat-table student-master-table' : '' }} {{ $tab === 'academic-years' ? 'academic-year-table' : '' }} {{ $tab === 'education-units' ? 'education-unit-table' : '' }} {{ $tab === 'classes' ? 'class-table' : '' }} {{ $tab === 'fee-types' ? 'fee-type-table' : '' }} {{ $tab === 'fee-discounts' ? 'fee-discount-table' : '' }} {{ $tab === 'data-roles' ? 'data-role-table' : '' }} {{ $tab === 'data-users' ? 'data-user-table' : '' }}">
                    @if ($tab === 'students')
                        <colgroup>
                            <col class="student-col-no">
                            <col class="student-col-nis">
                            <col class="student-col-name">
                            <col class="student-col-gender">
                            <col class="student-col-unit">
                            <col class="student-col-class">
                            <col class="student-col-actions">
                        </colgroup>
                        <thead><tr>
                            <th>No</th>
                            @foreach ([
                                'nis' => 'NIS',
                                'name' => 'Nama',
                                'gender' => 'Jenis Kelamin',
                                'unit' => 'Unit Pendidikan',
                                'class' => 'Kelas',
                            ] as $sortColumn => $sortLabel)
                                <th class="{{ $sortColumn === 'class' ? 'student-class-column' : '' }}">
                                    @include('partials.sortable-heading', ['column' => $sortColumn, 'label' => $sortLabel])
                                </th>
                            @endforeach
                            <th class="student-action-column">Aksi</th>
                        </tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td>{{ $row->nis }}</td><td>{{ $row->name }}</td><td>{{ $row->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td><td>{{ $row->schoolClass->educationUnit?->code ?? '-' }}</td><td class="student-class-cell">{{ $row->schoolClass->name }}</td><td class="student-actions-cell">@include('master.partials.actions', ['type' => 'students', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'education-units')
                        <colgroup>
                            <col class="education-unit-col-no">
                            <col class="education-unit-col-code">
                            <col class="education-unit-col-name">
                            <col class="education-unit-col-count">
                            <col class="education-unit-col-status">
                            <col class="education-unit-col-actions">
                        </colgroup>
                        <thead><tr><th class="table-col-no">No.</th><th class="table-col-code">@include('partials.sortable-heading', ['column' => 'code', 'label' => 'Kode'])</th><th class="table-col-main">@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Unit Pendidikan'])</th><th class="table-col-count">@include('partials.sortable-heading', ['column' => 'school_classes_count', 'label' => 'Jumlah Kelas'])</th><th class="table-col-status">@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th class="table-col-actions">Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td class="table-col-no">{{ $data->firstItem() + $loop->index }}</td><td class="table-col-code"><span class="code-badge">{{ $row->code }}</span></td><td class="table-col-main"><strong>{{ $row->name }}</strong></td><td class="table-col-count"><strong>{{ $row->school_classes_count }}</strong></td><td class="table-col-status"><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="table-col-actions">@include('master.partials.actions', ['type' => 'education-units', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'classes')
                        <colgroup>
                            <col class="class-col-no">
                            <col class="class-col-name">
                            <col class="class-col-unit">
                            <col class="class-col-count">
                            <col class="class-col-status">
                            <col class="class-col-actions">
                        </colgroup>
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'students_count', 'label' => 'Jumlah Siswa'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td><strong>{{ $row->educationUnit?->name ?? '-' }}</strong></td><td><strong>{{ $row->students_count }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'classes', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'fee-types')
                        <colgroup>
                            <col class="fee-type-col-no">
                            <col class="fee-type-col-name">
                            <col class="fee-type-col-unit">
                            <col class="fee-type-col-class">
                            <col class="fee-type-col-amount">
                            <col class="fee-type-col-actions">
                        </colgroup>
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Kategori Pembayaran'])</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Tingkat'])</th><th>@include('partials.sortable-heading', ['column' => 'amount', 'label' => 'Nominal'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong><small>{{ $row->creates_bill ? 'Tagihan Wajib' : 'Pembayaran Opsional' }}</small></td><td><strong>{{ $row->educationUnit?->code ?? '-' }}</strong></td><td><strong>{{ $row->class_level ? \App\Support\ClassLevel::label($row->class_level) : ($row->schoolClass?->name ?? 'Semua Tingkat') }}</strong></td><td><strong>Rp {{ number_format($row->amount, 0, ',', '.') }}</strong></td><td>@include('master.partials.actions', ['type' => 'fee-types', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'data-roles')
                        <colgroup>
                            <col class="data-role-col-no">
                            <col class="data-role-col-name">
                            <col class="data-role-col-key">
                            <col class="data-role-col-permissions">
                            <col class="data-role-col-count">
                            <col class="data-role-col-status">
                            <col class="data-role-col-actions">
                        </colgroup>
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Role'])</th><th>@include('partials.sortable-heading', ['column' => 'key', 'label' => 'Kode'])</th><th>Hak Akses</th><th>@include('partials.sortable-heading', ['column' => 'users_count', 'label' => 'Jumlah User'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong><small>{{ $row->description ?: '-' }}</small></td><td><span class="code-badge">{{ $row->key }}</span></td><td><span class="role-permission-summary">{{ count($row->permissions ?? []) }} akses</span><small>{{ implode(', ', array_slice($row->permissionLabels(), 0, 3)) }}{{ count($row->permissionLabels()) > 3 ? ', ...' : '' }}</small></td><td><strong>{{ $row->users_count }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'data-roles', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'data-users')
                        <colgroup>
                            <col class="data-user-col-no">
                            <col class="data-user-col-name">
                            <col class="data-user-col-username">
                            <col class="data-user-col-email">
                            <col class="data-user-col-role">
                            <col class="data-user-col-actions">
                        </colgroup>
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama User'])</th><th>@include('partials.sortable-heading', ['column' => 'username', 'label' => 'Username'])</th><th>@include('partials.sortable-heading', ['column' => 'email', 'label' => 'Email'])</th><th>@include('partials.sortable-heading', ['column' => 'role', 'label' => 'Role'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td>{{ $row->username }}</td><td>{{ $row->email }}</td><td><span class="status success">{{ $row->roleLabel() }}</span></td><td>@include('master.partials.actions', ['type' => 'data-users', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @else
                        <colgroup>
                            <col class="fee-discount-col-no">
                            <col class="fee-discount-col-student">
                            <col class="fee-discount-col-unit">
                            <col class="fee-discount-col-class">
                            <col class="fee-discount-col-payment">
                            <col class="fee-discount-col-actions">
                        </colgroup>
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'student', 'label' => 'Nama Siswa'])</th><th>Unit Pendidikan</th><th>Kelas</th><th>@include('partials.sortable-heading', ['column' => 'payment', 'label' => 'Pembayaran'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)
                            <tr class="fee-discount-main-row">
                                <td>{{ $data->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->student?->name ?? '-' }}</strong></td>
                                <td><span class="code-badge">{{ $row->student?->schoolClass?->educationUnit?->code ?? '-' }}</span></td>
                                <td><strong>{{ $row->student?->schoolClass?->name ?? '-' }}</strong></td>
                                <td><strong>{{ $row->source_type === 'spp' ? 'SPP' : ($row->feeType?->name ?? '-') }}</strong></td>
                                <td><div class="fee-discount-actions">@include('master.partials.actions', ['type' => 'fee-discounts', 'row' => $row])</div></td>
                            </tr>
                        @empty @include('master.partials.empty') @endforelse</tbody>
                    @endif
                </table></div>
                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @endif
            @endif
            @endif
        </main>
    </div>
</div>

@unless ($showCreate || $showStudentImport)
<div class="modal-backdrop {{ $errors->any() && ! $errors->has('file') ? 'show' : '' }} {{ $tab === 'academic-years' ? 'academic-year-edit-modal' : '' }} {{ $tab === 'education-units' ? 'education-unit-edit-modal' : '' }} {{ $tab === 'classes' ? 'class-edit-modal' : '' }} {{ $tab === 'fee-types' ? 'fee-type-edit-modal' : '' }} {{ $tab === 'fee-discounts' ? 'fee-discount-edit-modal' : '' }} {{ $tab === 'data-roles' ? 'data-role-edit-modal' : '' }} {{ $tab === 'data-users' ? 'data-user-edit-modal' : '' }}" data-modal>
    <div class="form-modal">
        <div class="form-modal-header"><div>@if (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'], true))<p class="eyebrow">Master Data</p>@endif<h2 data-modal-title>Edit Data</h2></div><button class="icon-button" data-modal-close>×</button></div>
        <form method="POST" action="{{ route('master.'.$tab.'.store') }}" data-master-form data-store-action="{{ route('master.'.$tab.'.store') }}" class="master-form">
            @csrf <input type="hidden" name="_method" value="POST" data-form-method>
            @include('master.partials.form-fields')
            <div class="form-actions span-2"><button type="button" class="button button-secondary" data-modal-close>Batal</button><button class="button button-primary">Simpan Data</button></div>
        </form>
    </div>
</div>
@endunless
</body>
</html>
