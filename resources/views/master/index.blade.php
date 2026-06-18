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
        'print' => '<path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7zM17 12h.01"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>',
        'role' => '<path d="M12 3 5 6v5c0 4.5 3 8.1 7 10 4-1.9 7-5.5 7-10V6l-7-3Z"/><path d="M9 12l2 2 4-5"/>',
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
        'fee-types' => ['Kategori Pembayaran', 'receipt', $stats['fee_types']],
        'data-roles' => ['Data Role', 'role', $stats['roles'] ?? 0],
        'data-users' => ['Data User', 'users', $stats['users'] ?? 0],
    ];
    $labels = [
        'students' => ['Data Siswa', 'Kelola identitas, kelas, wali, dan status siswa.', 'Tambah Siswa'],
        'education-units' => ['Unit Pendidikan', 'Daftar unit pendidikan yang tersedia.', 'Tambah Unit Pendidikan'],
        'classes' => ['Kelas', 'Daftar kelas yang tersedia.', 'Tambah Kelas'],
        'academic-years' => ['Tahun Pelajaran', 'Daftar tahun pelajaran yang tersedia.', 'Tambah Tahun Pelajaran'],
        'fee-types' => ['Kategori Pembayaran', 'Atur SPP, daftar ulang, laundry, dan pembayaran lainnya.', 'Tambah Kategori Pembayaran'],
        'fee-discounts' => ['Keringanan Biaya', 'Atur potongan SPP atau pembayaran lainnya untuk siswa.', 'Tambah Keringanan'],
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
                    <a href="{{ $tab === 'students' ? route('student-management.students.index') : route('master.index', ['tab' => $tab]) }}" class="button button-secondary">Kembali ke Daftar</a>
                </section>
                <section class="card master-create-card">
                    <div class="master-create-heading"><div><strong>Informasi {{ $labels[$tab][0] }}</strong><span>Pastikan data yang dimasukkan sudah benar.</span></div></div>
                    <form method="POST" action="{{ route('master.'.$tab.'.store') }}" class="master-form master-create-form">
                        @csrf
                        @include('master.partials.form-fields')
                        <div class="form-actions span-2"><a href="{{ $tab === 'students' ? route('student-management.students.index') : route('master.index', ['tab' => $tab]) }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Data</button></div>
                    </form>
                </section>
            @else
            @if ($tab !== 'students')
            <section class="hero master-hero">
                <div><p class="eyebrow">Pengelolaan Data</p><h1>{{ $labels[$tab][0] }}</h1><p>{{ $labels[$tab][1] }}</p></div>
                <div class="hero-actions">@if ($tab !== 'students')
                    <a href="{{ route('master.create', ['tab' => $tab]) }}" class="button button-primary">{!! $icon('plus') !!} {{ $labels[$tab][2] }}</a>
                @endif</div>
            </section>
            @endif

            @if ($tab === 'students')
                <section class="student-workspace">
                    @php($studentExportQuery = array_filter([
                        'unit_id' => request('unit_id'),
                        'class_id' => request('class_id'),
                        'year_id' => $studentYearId,
                        'status' => $studentStatus,
                        'search' => request('search'),
                    ], fn ($value) => filled($value)))
                    <div class="student-flat-header">
                        <h1>Data Siswa</h1>
                    </div>
                    <div class="student-action-bar">
                        <a href="{{ route('student-management.students.create') }}" class="button student-add-button">Tambah</a>
                        <a href="{{ route('master.students.template') }}" class="button action-orange">{!! $icon('download') !!} Download Template</a>
                        <button type="button" class="button action-purple {{ $studentImportPreview || $errors->has('file') ? 'active' : '' }}" data-spp-import-toggle aria-expanded="{{ $errors->has('file') ? 'true' : 'false' }}">{!! $icon('upload') !!} Import</button>
                        <a href="{{ route('master.students.export', $studentExportQuery) }}" class="button action-green">{!! $icon('download') !!} Export</a>
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

                    <form method="GET" action="{{ route('student-management.students.index') }}" class="student-filter-panel" data-student-filter-panel>
                        <label><span>Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach ($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                        <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach ($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                        <label><span>Tahun Pelajaran</span><select name="year_id">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($studentYearId == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
                        <label><span>Status Data</span><select name="status"><option value="">Semua Status</option><option value="active" @selected($studentStatus === 'active')>Aktif</option><option value="inactive" @selected($studentStatus === 'inactive')>Nonaktif</option></select></label>
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <div class="student-filter-actions">
                            <button class="button student-search-button" aria-label="Tampilkan data">{!! $icon('search') !!}</button>
                        </div>
                    </form>
                </section>
                @if($studentImportPreview)
                <section class="card spp-import-preview student-import-preview">
                    <div class="spp-preview-header">
                        <div class="spp-preview-title">
                            <span class="spp-preview-icon">{!! $icon('database') !!}</span>
                            <div><span class="spp-import-kicker">Hasil Validasi Import</span><strong>Preview Import Data Siswa</strong><span>{{ $studentImportPreview['valid'] > 0 ? 'Periksa seluruh hasil sebelum menyimpan data valid.' : 'Belum ada data siswa yang dapat diimpor.' }}</span></div>
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
                    <div class="spp-preview-table-head student-import-table-heading">
                        <div><strong>Rincian Hasil Pemeriksaan</strong><span>Gagal dan Duplikat tidak disimpan. Periksa keterangannya pada kolom Alasan.</span></div>
                        <div class="student-import-heading-badges"><span class="student-import-failed-count">{{ number_format(count($studentImportPreview['failures']), 0, ',', '.') }} perlu diperbaiki</span><span class="spp-preview-count">{{ number_format(count($studentImportPreview['rows']), 0, ',', '.') }} baris</span></div>
                    </div>
                    <div class="student-import-toolbar" data-student-import-toolbar>
                        <label class="student-import-show">Show
                            <select data-student-import-limit>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="500">500</option>
                                <option value="all">All</option>
                            </select>
                            entries
                        </label>
                        <div class="student-import-status-filter" role="group" aria-label="Filter status import">
                            <button type="button" class="active" data-student-import-status="all">Semua <b>{{ $studentImportPreview['total'] }}</b></button>
                            <button type="button" data-student-import-status="valid">Valid <b>{{ $studentImportPreview['valid'] }}</b></button>
                            <button type="button" data-student-import-status="duplikat">Duplikat <b>{{ $studentImportPreview['duplicates'] }}</b></button>
                            <button type="button" data-student-import-status="gagal">Gagal <b>{{ count($studentImportPreview['failures']) }}</b></button>
                        </div>
                        <label class="student-import-search">
                            {!! $icon('search') !!}
                            <input type="search" placeholder="Cari NIS, nama, unit, kelas, atau alasan..." data-student-import-search>
                        </label>
                    </div>
                    <div class="table-wrap spp-import-table-wrap"><table class="data-table spp-import-table student-import-preview-table"><thead><tr><th>Baris</th><th>Unit</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
                        @foreach($studentImportPreview['rows'] as $row)
                        <tr class="spp-import-row {{ strtolower($row['status']) }}" data-student-import-row data-status="{{ strtolower($row['status']) }}" data-search="{{ strtolower(implode(' ', [$row['line'], $row['unit'], $row['nis'], $row['name'], $row['class'], $row['status'], $row['message']])) }}"><td><span class="spp-line-number">{{ $row['line'] }}</span></td><td><span class="education-code">{{ $row['unit'] ?: '-' }}</span></td><td><strong class="spp-import-nis">{{ $row['nis'] ?: '-' }}</strong></td><td><strong>{{ $row['name'] ?: '-' }}</strong></td><td>{{ $row['class'] ?: '-' }}</td><td><span class="status {{ $row['status']==='Valid'?'success':($row['status']==='Duplikat'?'warning':'danger') }}">{{ $row['status'] }}</span></td><td><span class="spp-import-message"><b>{{ $row['status'] === 'Valid' ? 'Siap' : 'Alasan' }}</b>{{ $row['message'] }}</span></td></tr>
                        @endforeach
                    </tbody></table></div>
                    <div class="student-import-footer"><span data-student-import-summary></span><button type="button" data-student-import-show-all>Tampilkan Semua</button></div>
                </section>
                @endif
            @elseif (! in_array($tab, ['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users']))
            <section class="master-stats">
                <div><span class="metric-icon blue">{!! $icon('users') !!}</span><p>Total Siswa<strong>{{ number_format($stats['students'], 0, ',', '.') }}</strong><small>{{ $stats['active_students'] }} aktif</small></p></div>
                <div><span class="metric-icon green">{!! $icon('database') !!}</span><p>Unit Pendidikan<strong>{{ $stats['education_units'] }}</strong><small>Unit aktif</small></p></div>
                <div><span class="metric-icon indigo">{!! $icon('database') !!}</span><p>Total Kelas<strong>{{ $stats['classes'] }}</strong><small>Seluruh jenjang</small></p></div>
                <div><span class="metric-icon blue">{!! $icon('receipt') !!}</span><p>Kategori Pembayaran<strong>{{ $stats['fee_types'] }}</strong><small>Kategori aktif</small></p></div>
            </section>
            @endif

            <section class="card master-card {{ $tab === 'students' ? 'student-data-card' : '' }}">
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
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
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
                            @foreach(request()->except(['search', 'page']) as $key => $value)
                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari siswa berdasarkan nama, NIS, atau NISN..."></label>
                        </form>
                    </div>
                @else
                    @include('partials.list-toolbar', [
                        'action' => route('master.index'),
                        'searchLabel' => 'Cari data master',
                        'unitFilter' => $tab === 'classes' ? $educationUnits : null,
                    ])
                @endif
                <div class="table-wrap"><table class="data-table {{ $tab === 'students' ? 'student-flat-table' : '' }}">
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
                                'unit' => 'Pendidikan',
                                'class' => 'Kelas',
                            ] as $sortColumn => $sortLabel)
                                <th>
                                    @include('partials.sortable-heading', ['column' => $sortColumn, 'label' => $sortLabel])
                                </th>
                            @endforeach
                            <th class="student-action-column">Aksi</th>
                        </tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td>{{ $row->nis }}</td><td>{{ $row->name }}</td><td>{{ $row->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td><td>{{ $row->schoolClass->educationUnit?->name ?? '-' }}</td><td>{{ $row->schoolClass->name }}</td><td>@include('master.partials.actions', ['type' => 'students', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
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
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Kategori Pembayaran'])</th><th>Kelompok</th><th>@include('partials.sortable-heading', ['column' => 'unit', 'label' => 'Unit Pendidikan'])</th><th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'amount', 'label' => 'Nominal'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td><span class="status neutral">{{ ['spp' => 'SPP', 'daftar-ulang' => 'Daftar Ulang', 'laundry' => 'Laundry', 'lain-lain' => 'Lain-lain'][$row->payment_group] ?? ucfirst($row->payment_group) }}</span></td><td><strong>{{ $row->educationUnit?->code ?? '-' }}</strong></td><td><strong>{{ $row->schoolClass?->name ?? 'Semua Kelas' }}</strong></td><td><strong>Rp {{ number_format($row->amount, 0, ',', '.') }}</strong></td><td>@include('master.partials.actions', ['type' => 'fee-types', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'data-roles')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama Role'])</th><th>@include('partials.sortable-heading', ['column' => 'key', 'label' => 'Kode'])</th><th>Hak Akses</th><th>@include('partials.sortable-heading', ['column' => 'users_count', 'label' => 'Jumlah User'])</th><th>@include('partials.sortable-heading', ['column' => 'is_active', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong><small>{{ $row->description ?: '-' }}</small></td><td><span class="code-badge">{{ $row->key }}</span></td><td><span class="role-permission-summary">{{ count($row->permissions ?? []) }} akses</span><small>{{ implode(', ', array_slice($row->permissionLabels(), 0, 3)) }}{{ count($row->permissionLabels()) > 3 ? ', ...' : '' }}</small></td><td><strong>{{ $row->users_count }}</strong></td><td><span class="status {{ $row->is_active ? 'success' : 'neutral' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>@include('master.partials.actions', ['type' => 'data-roles', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
                    @elseif ($tab === 'data-users')
                        <thead><tr><th>No.</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama User'])</th><th>@include('partials.sortable-heading', ['column' => 'username', 'label' => 'Username'])</th><th>@include('partials.sortable-heading', ['column' => 'email', 'label' => 'Email'])</th><th>@include('partials.sortable-heading', ['column' => 'role', 'label' => 'Role'])</th><th>Aksi</th></tr></thead>
                        <tbody>@forelse ($data as $row)<tr><td>{{ $data->firstItem() + $loop->index }}</td><td><strong>{{ $row->name }}</strong></td><td>{{ $row->username }}</td><td>{{ $row->email }}</td><td><span class="status success">{{ $row->roleLabel() }}</span></td><td>@include('master.partials.actions', ['type' => 'data-users', 'row' => $row])</td></tr>@empty @include('master.partials.empty') @endforelse</tbody>
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
