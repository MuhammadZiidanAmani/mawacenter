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
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'upload' => '<path d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'finance' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M7 5V3h10v2M3 10h18M7 15h3"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $tabs = [
        'academic-years' => ['Tahun Pelajaran', 'calendar', $academicYears->count()],
        'education-units' => ['Unit Pendidikan', 'database', $stats['education_units']],
        'classes' => ['Kelas', 'database', $stats['classes']],
        'students' => ['Siswa', 'users', $stats['students']],
        'spp-settings' => ['Set SPP', 'wallet', $stats['spp_settings']],
        'fee-types' => ['Jenis Pembayaran', 'receipt', $stats['fee_types']],
    ];
    $labels = [
        'students' => ['Data Siswa', 'Kelola identitas, kelas, wali, dan status siswa.', 'Tambah Siswa'],
        'education-units' => ['Unit Pendidikan', 'Daftar unit pendidikan yang tersedia.', 'Tambah Unit Pendidikan'],
        'classes' => ['Kelas', 'Daftar kelas yang tersedia.', 'Tambah Kelas'],
        'academic-years' => ['Tahun Pelajaran', 'Daftar tahun pelajaran yang tersedia.', 'Tambah Tahun Pelajaran'],
        'fee-types' => ['Jenis Pembayaran', 'Atur daftar ulang, laundry, dan pembayaran lainnya.', 'Tambah Jenis Pembayaran'],
        'spp-settings' => ['Set SPP', 'Atur nominal SPP untuk setiap unit pendidikan.', 'Tambah Set SPP'],
        'fee-discounts' => ['Keringanan Biaya', 'Atur potongan SPP atau pembayaran lainnya untuk siswa.', 'Tambah Keringanan'],
    ];
@endphp
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="brand"><div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div><div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div><button class="icon-button sidebar-close" data-sidebar-close>×</button></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item">{!! $icon('grid') !!}<span>Dashboard</span></a>
            <div class="nav-group master-nav open">
                <button type="button" class="nav-item nav-parent active" data-master-nav-toggle aria-expanded="true">{!! $icon('database') !!}<span>Data Master</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    @foreach ([
                        'academic-years' => ['Tahun Pelajaran', 'calendar'],
                        'education-units' => ['Unit Pendidikan', 'grid'],
                        'classes' => ['Kelas', 'database'],
                        'students' => ['Siswa', 'users'],
                        'spp-settings' => ['Set SPP', 'wallet'],
                        'fee-types' => ['Jenis Pembayaran', 'receipt'],
                        'fee-discounts' => ['Keringanan Biaya', 'wallet'],
                    ] as $key => $item)
                        <a href="{{ route('master.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{!! $icon($item[1]) !!}<span>{{ $item[0] }}</span></a>
                    @endforeach
                </div>
            </div>
            <div class="nav-group nested-nav">
                <button type="button" class="nav-item nav-parent" data-nav-toggle aria-expanded="false">{!! $icon('card') !!}<span>Pembayaran</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    <a href="{{ route('finance.other.index', ['category' => 'daftar-ulang']) }}">{!! $icon('receipt') !!}<span>Daftar Ulang</span></a>
                    <a href="{{ route('finance.spp.index') }}">{!! $icon('wallet') !!}<span>SPP</span></a>
                    <a href="{{ route('finance.other.index', ['category' => 'laundry']) }}">{!! $icon('card') !!}<span>Laundry</span></a>
                    <a href="{{ route('finance.other.index') }}">{!! $icon('receipt') !!}<span>Lain-lain</span></a>
                </div>
            </div>
            <a href="{{ route('finance.bills.index') }}" class="nav-item">{!! $icon('receipt') !!}<span>Tagihan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
            <a href="{{ route('reports.index') }}" class="nav-item">{!! $icon('chart') !!}<span>Laporan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
            <a href="{{ route('settings.index') }}" class="nav-item">{!! $icon('settings') !!}<span>Pengaturan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ optional($academicYears->firstWhere('is_active', true))->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}<span></span></button>
            <button class="icon-button logout-button" type="button" aria-label="Keluar" title="Keluar">{!! $icon('logout') !!}</button>
        </header>
        <main class="{{ $tab === 'students' ? 'student-page' : '' }} {{ $showCreate ? 'master-create-page' : '' }}">
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
                <section class="hero master-hero">
                    <div><p class="eyebrow">Pengelolaan Data · Tambah</p><h1>{{ $labels[$tab][2] }}</h1><p>Lengkapi formulir berikut untuk menambahkan data baru.</p></div>
                    <a href="{{ route('master.index', ['tab' => $tab]) }}" class="button button-secondary">Kembali ke Daftar</a>
                </section>
                <section class="card master-create-card">
                    <div class="master-create-heading"><div><strong>Informasi {{ $labels[$tab][0] }}</strong><span>Pastikan data yang dimasukkan sudah benar.</span></div></div>
                    <form method="POST" action="{{ route('master.'.$tab.'.store') }}" class="master-form master-create-form">
                        @csrf
                        @include('master.partials.form-fields')
                        <div class="form-actions span-2"><a href="{{ route('master.index', ['tab' => $tab]) }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Data</button></div>
                    </form>
                </section>
            @else
            <section class="hero master-hero {{ $tab === 'students' ? 'student-page-hero' : '' }}">
                <div><p class="eyebrow">Pengelolaan Data</p><h1>{{ $labels[$tab][0] }}</h1><p>{{ $labels[$tab][1] }}</p></div>
                <div class="hero-actions">@if ($tab !== 'students')
                    <a href="{{ route('master.create', ['tab' => $tab]) }}" class="button button-primary">{!! $icon('plus') !!} {{ $labels[$tab][2] }}</a>
                @endif</div>
            </section>

            @if ($tab === 'students')
                <section class="student-workspace">
                    @php($activeStudentFilters = collect(['unit_id', 'class_id', 'year_id', 'status'])->filter(fn ($key) => request()->filled($key))->count())
                    <div class="student-action-bar">
                        <a href="{{ route('master.create', ['tab' => 'students']) }}" class="button button-primary">{!! $icon('plus') !!} Tambah Siswa</a>
                        <a href="{{ route('master.students.template') }}" class="button action-orange">{!! $icon('download') !!} Download Template</a>
                        <button type="button" class="button action-purple {{ $studentImportPreview || $errors->has('file') ? 'active' : '' }}" data-spp-import-toggle aria-expanded="{{ $errors->has('file') ? 'true' : 'false' }}">{!! $icon('upload') !!} Import Data</button>
                        <button type="button" class="button action-green" data-student-export-toggle aria-expanded="false">{!! $icon('download') !!} Export Data</button>
                        <button type="button" class="button student-filter-toggle {{ $activeStudentFilters ? 'active' : '' }}" data-student-filter-toggle aria-expanded="{{ $activeStudentFilters ? 'true' : 'false' }}">{!! $icon('search') !!} Filter Siswa @if($activeStudentFilters)<b>{{ $activeStudentFilters }}</b>@endif</button>
                    </div>

                    <div class="spp-import-modal-backdrop {{ $errors->has('file') ? 'show' : '' }}" data-spp-import-panel @if(! $errors->has('file')) hidden @endif>
                        <section class="spp-import-modal" role="dialog" aria-modal="true" aria-labelledby="student-import-title">
                            <header class="spp-import-modal-head">
                                <div><span class="spp-import-kicker">Pengelolaan Data · Siswa</span><h2 id="student-import-title">Import Data Siswa</h2><p>Unggah data siswa untuk diperiksa sebelum disimpan.</p></div>
                                <button type="button" class="spp-import-close" data-spp-import-close aria-label="Tutup modal import">×</button>
                            </header>
                            <div class="spp-import-progress">
                                <div class="active"><b>1</b><span>Pilih file</span></div>
                                <div><b>2</b><span>Preview data</span></div>
                                <div><b>3</b><span>Konfirmasi</span></div>
                            </div>
                            <div class="spp-import-info">
                                <span><svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg></span>
                                <div><strong>Validasi Sistem</strong><p>Sistem memeriksa <b>NIS per unit pendidikan, NISN, kelas, dan kelengkapan data</b> sebelum siswa disimpan.</p></div>
                            </div>
                            <form method="POST" action="{{ route('master.students.import.preview') }}" enctype="multipart/form-data" class="spp-import-modal-form">
                                @csrf
                                <label class="spp-import-dropzone">
                                    <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required data-spp-import-file>
                                    <span class="spp-import-drop-icon">{!! $icon('upload') !!}</span>
                                    <strong data-spp-import-filename>Ketuk untuk pilih berkas</strong>
                                    <small>Format XLSX · Maksimal 5 MB</small>
                                    <span class="spp-import-browse">Cari di Dokumen Saya</span>
                                </label>
                                <div class="spp-import-modal-actions">
                                    <button class="button button-primary spp-preview-button">{!! $icon('upload') !!} Preview Data</button>
                                    <button type="button" class="button button-secondary" data-spp-import-close>Batal</button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <form method="GET" action="{{ route('master.index') }}" class="student-filter-panel" data-student-filter-panel @if(! $activeStudentFilters) hidden @endif>
                        <input type="hidden" name="tab" value="students">
                        <div class="student-filter-heading">
                            <span class="student-filter-icon">{!! $icon('search') !!}</span>
                            <div><strong>Filter Data Siswa</strong><small>Pilih kriteria untuk mempersempit daftar siswa.</small></div>
                            @if ($activeStudentFilters)
                                <b>{{ $activeStudentFilters }} filter aktif</b>
                            @endif
                        </div>
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit required><option value="">Pilih Unit Pendidikan</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class @disabled(! request()->filled('unit_id'))><option value="">{{ request()->filled('unit_id') ? 'Semua Kelas' : 'Pilih Unit Pendidikan Dahulu' }}</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($studentYearId == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        <label><span>Status Data</span><select name="status"><option value="">Semua Status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
                        <div class="student-filter-actions">
                            <a href="{{ route('master.index', ['tab' => 'students']) }}" class="button student-filter-reset">Reset</a>
                            <button class="button student-search-button">{!! $icon('search') !!} Tampilkan Data</button>
                        </div>
                    </form>

                    <form method="GET" action="{{ route('master.students.export') }}" class="student-filter-panel student-export-panel" data-student-export-panel hidden>
                        <div class="student-filter-heading">
                            <span class="student-filter-icon">{!! $icon('download') !!}</span>
                            <div><strong>Spesifikasi Export Data</strong><small>Pilih data siswa yang ingin dimasukkan ke file Excel.</small></div>
                        </div>
                        <label><span>Unit Pendidikan</span><select name="unit_id" data-student-export-unit required><option value="">Pilih Unit Pendidikan</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-export-class disabled><option value="">Pilih Unit Pendidikan Dahulu</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}">{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($activeAcademicYear?->id === $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        <label><span>Status Data</span><select name="status"><option value="">Semua Status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                        <div class="student-filter-actions">
                            <button type="button" class="button student-filter-reset" data-student-export-close>Batal</button>
                            <button class="button student-search-button">{!! $icon('download') !!} Download Data</button>
                        </div>
                    </form>
                </section>
                @if($studentImportPreview)
                <section class="card spp-import-preview student-import-preview">
                    <div class="spp-preview-header">
                        <div class="spp-preview-title">
                            <span class="spp-preview-icon">✓</span>
                            <div><span class="spp-import-kicker">Hasil Validasi</span><strong>Preview Import Data Siswa</strong><span>{{ $studentImportPreview['valid'] > 0 ? 'Data valid siap disimpan setelah hasil pemeriksaan dikonfirmasi.' : 'Belum ada data siswa yang dapat diimpor.' }}</span></div>
                        </div>
                        <form method="POST" action="{{ route('master.students.import') }}">@csrf<input type="hidden" name="token" value="{{ $studentImportToken }}"><button class="button button-primary spp-confirm-button" @disabled($studentImportPreview['valid'] < 1)><span>Konfirmasi Import</span><b>{{ $studentImportPreview['valid'] }} Siswa</b></button></form>
                    </div>
                    <div class="spp-import-stats">
                        <div class="total"><span class="spp-stat-icon">Σ</span><p><span>Total Baris</span><strong>{{ $studentImportPreview['total'] }}</strong><small>data diperiksa</small></p></div>
                        <div class="valid"><span class="spp-stat-icon">✓</span><p><span>Valid</span><strong>{{ $studentImportPreview['valid'] }}</strong><small>siap diimpor</small></p></div>
                        <div class="duplicate"><span class="spp-stat-icon">↻</span><p><span>Duplikat</span><strong>{{ $studentImportPreview['duplicates'] }}</strong><small>akan dilewati</small></p></div>
                        <div class="failed"><span class="spp-stat-icon">!</span><p><span>Gagal</span><strong>{{ count($studentImportPreview['failures']) }}</strong><small>perlu diperiksa</small></p></div>
                    </div>
                    <div class="spp-validation-bar"><span style="width: {{ $studentImportPreview['total'] > 0 ? ($studentImportPreview['valid'] / $studentImportPreview['total']) * 100 : 0 }}%"></span></div>
                    <div class="spp-preview-table-head"><div><strong>Rincian Hasil Pemeriksaan</strong><span>Data belum disimpan sampai tombol konfirmasi ditekan.</span></div><span class="spp-preview-count">{{ count($studentImportPreview['rows']) }} baris</span></div>
                    <div class="table-wrap spp-import-table-wrap"><table class="data-table spp-import-table student-import-preview-table"><thead><tr><th>Baris</th><th>Unit</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
                        @foreach(array_slice($studentImportPreview['rows'], 0, 100) as $row)
                        <tr class="spp-import-row {{ strtolower($row['status']) }}"><td><span class="spp-line-number">{{ $row['line'] }}</span></td><td><span class="education-code">{{ $row['unit'] ?: '-' }}</span></td><td><strong class="spp-import-nis">{{ $row['nis'] ?: '-' }}</strong></td><td><strong>{{ $row['name'] ?: '-' }}</strong></td><td>{{ $row['class'] ?: '-' }}</td><td><span class="status {{ $row['status']==='Valid'?'success':($row['status']==='Duplikat'?'warning':'danger') }}">{{ $row['status'] }}</span></td><td><span class="spp-import-message">{{ $row['message'] }}</span></td></tr>
                        @endforeach
                    </tbody></table></div>
                    @if(count($studentImportPreview['rows']) > 100)<p class="spp-import-note"><span>i</span> Menampilkan 100 dari {{ count($studentImportPreview['rows']) }} baris hasil validasi.</p>@endif
                </section>
                @endif
            @elseif (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'spp-settings', 'fee-discounts']))
            <section class="master-stats">
                <div><span class="metric-icon blue">{!! $icon('users') !!}</span><p>Total Siswa<strong>{{ number_format($stats['students'], 0, ',', '.') }}</strong><small>{{ $stats['active_students'] }} aktif</small></p></div>
                <div><span class="metric-icon green">{!! $icon('database') !!}</span><p>Unit Pendidikan<strong>{{ $stats['education_units'] }}</strong><small>Unit aktif</small></p></div>
                <div><span class="metric-icon indigo">{!! $icon('database') !!}</span><p>Total Kelas<strong>{{ $stats['classes'] }}</strong><small>Seluruh jenjang</small></p></div>
                <div><span class="metric-icon blue">{!! $icon('receipt') !!}</span><p>Jenis Pembayaran<strong>{{ $stats['fee_types'] }}</strong><small>Jenis aktif</small></p></div>
            </section>
            @endif

            <section class="card master-card {{ $tab === 'students' ? 'student-data-card' : '' }}">
                @if (! in_array($tab, ['students', 'academic-years', 'education-units', 'classes', 'fee-types', 'spp-settings', 'fee-discounts']))
                <div class="master-tabs">
                    @foreach ($tabs as $key => $item)
                        <a href="{{ route('master.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{!! $icon($item[1]) !!}<span>{{ $item[0] }}</span><b>{{ $item[2] }}</b></a>
                    @endforeach
                </div>
                @endif
                @if (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'spp-settings', 'fee-discounts']))
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

                @include('partials.list-toolbar', [
                    'action' => route('master.index'),
                    'searchLabel' => 'Cari data master',
                    'unitFilter' => $tab === 'classes' ? $educationUnits : null,
                ])
                <div class="table-wrap"><table class="data-table">
                    @if ($tab === 'students')
                        <thead><tr>
                            <th>No</th>
                            @foreach ([
                                'nis' => 'NIS',
                                'name' => 'Nama',
                                'gender' => 'Jenis Kelamin',
                                'unit' => 'Unit Pendidikan',
                                'class' => 'Kelas',
                            ] as $sortColumn => $sortLabel)
                                <th>@include('partials.sortable-heading', ['column' => $sortColumn, 'label' => $sortLabel])</th>
                            @endforeach
                            <th class="student-action-column">Aksi</th>
                        </tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->nis }}</strong></td><td><div class="table-person"><div><strong>{{ $row->name }}</strong></div></div></td><td>{{ $row->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td><td><span class="code-badge">{{ $row->schoolClass->educationUnit?->code ?? '-' }}</span></td><td><strong>{{ $row->schoolClass->name }}</strong></td><td>@include('master.partials.actions', ['type' => 'students', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'education-units')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'code', 'label' => 'Kode'])</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'school_classes_count', 'label' => 'Jumlah Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><span class="code-badge">{{ $row->code }}</span></td><td><strong>{{ $row->name }}</strong></td><td><strong>{{ $row->school_classes_count }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'education-units', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'classes')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'students_count', 'label' => 'Jumlah Siswa'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td><strong>{{ $row->educationUnit?->name ?? '-' }}</strong></td><td><strong>{{ $row->students_count }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'classes', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'academic-years')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Tahun Pelajaran'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'academic-years', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'fee-types')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Jenis Pembayaran'])</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'amount', 'label' => 'Nominal'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td><strong>{{ $row->educationUnit?->code ?? '-' }}</strong></td><td><strong>{{ $row->schoolClass?->name ?? 'Semua Kelas' }}</strong></td><td><strong>Rp {{ number_format($row->amount, 0, ',', '.') }}</strong></td><td>@include('master.partials.actions', ['type' => 'fee-types', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'spp-settings')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'amount', 'label' => 'Nominal'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->educationUnit?->name ?? '-' }}</strong></td><td><strong>Rp {{ number_format($row->amount, 0, ',', '.') }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'spp-settings', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @else
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'student', 'label' => 'Nama'])</th><th>Unit Pendidikan</th><th>Kelas</th><th>@include('partials.sortable-heading', ['column' => 'payment', 'label' => 'Pembayaran'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)
                            <tr class="fee-discount-main-row">
                                <td>{{ $data->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->student?->name ?? '-' }}</strong></td>
                                <td><span class="code-badge">{{ $row->student?->schoolClass?->educationUnit?->code ?? '-' }}</span></td>
                                <td><strong>{{ $row->student?->schoolClass?->name ?? '-' }}</strong></td>
                                <td><strong>{{ $row->source_type === 'spp' ? 'SPP' : ($row->feeType?->name ?? '-') }}</strong></td>
                                <td><div class="fee-discount-actions">@include('master.partials.actions', ['type' => 'fee-discounts', 'row' => $row])<button type="button" class="spp-expand-button" data-spp-row-toggle="fee-discount-{{ $row->id }}" aria-expanded="false" title="Lihat rincian" aria-label="Lihat rincian keringanan">+</button></div></td>
                            </tr>
                            <tr class="spp-expanded-row fee-discount-detail-row" data-spp-row-detail="fee-discount-{{ $row->id }}" hidden><td colspan="6">
                                <div class="fee-discount-detail">
                                    <div><span>Set Biaya</span><strong>Rp {{ number_format($row->original_amount, 0, ',', '.') }}</strong></div>
                                    <div><span>Keringanan</span><strong>{{ $row->discount_type === 'percentage' ? $row->discount_value.'%' : 'Rp '.number_format($row->discount_amount, 0, ',', '.') }}</strong></div>
                                    <div><span>Yang Dibayarkan</span><strong>Rp {{ number_format($row->final_amount, 0, ',', '.') }}</strong></div>
                                    <div><span>Alasan</span><strong>{{ $row->reason ?: '-' }}</strong></div>
                                </div>
                            </td></tr>
                        @empty @include('master.partials.empty') @endforelse</tbody>
                    @endif
                </table></div>
                <div class="pagination-wrap">{{ $data->links() }}</div>
            </section>
            @endif
        </main>
    </div>
</div>

@unless ($showCreate)
<div class="modal-backdrop {{ $errors->any() && ! $errors->has('file') ? 'show' : '' }}" data-modal>
    <div class="form-modal">
        <div class="form-modal-header"><div><p class="eyebrow">Master Data</p><h2 data-modal-title>Edit Data</h2></div><button class="icon-button" data-modal-close>×</button></div>
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
