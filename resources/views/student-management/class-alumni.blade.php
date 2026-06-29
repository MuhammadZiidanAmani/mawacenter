<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadikan Alumni - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'data-siswa',
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

        <main class="student-page student-create-page student-class-alumni-page">
            @if ($errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span>
                        <strong>Data belum dapat diproses</strong>
                        <p>{{ $errors->first() }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            <div class="student-flat-header student-class-alumni-page-header">
                <div class="student-master-heading">
                    <h1>Jadikan Alumni</h1>
                    <p>Periksa kelas, tanggal, dan alasan sebelum siswa dipindahkan ke status alumni.</p>
                </div>
            </div>

            <section class="student-workspace student-create-canvas student-class-alumni-canvas">
                <form method="POST" action="{{ route('student-management.students.class-alumni.store') }}" class="student-class-alumni-page-form">
                    @csrf
                    <input type="hidden" name="unit_id" value="{{ $class->education_unit_id }}">
                    <input type="hidden" name="class_id" value="{{ $class->id }}">
                    <input type="hidden" name="year_id" value="{{ $year->id }}">

                    <div class="student-class-alumni-page-summary">
                        <div><span>Unit Pendidikan</span><strong>{{ $class->educationUnit?->code ?? '-' }}</strong></div>
                        <div><span>Kelas</span><strong>{{ $class->name }}</strong></div>
                        <div><span>Tahun Pelajaran</span><strong>{{ $year->name }}</strong></div>
                        <div><span>Jumlah Siswa</span><strong>{{ number_format($studentCount, 0, ',', '.') }} siswa aktif</strong></div>
                    </div>

                    <div class="student-class-alumni-page-fields">
                        <label><span>Tanggal Alumni</span><input type="date" name="exit_date" value="{{ now()->toDateString() }}" required></label>
                        <label><span>Alasan</span><select name="inactive_reason" required><option value="Lulus">Lulus</option><option value="Pindah Sekolah">Pindah Sekolah</option><option value="Mengundurkan Diri">Mengundurkan Diri</option></select></label>
                    </div>

                    <p class="student-class-alumni-page-warning">Setelah dikonfirmasi, seluruh siswa aktif pada kelas ini akan berubah menjadi nonaktif/alumni dan tetap bisa dilihat melalui filter Status Data: Nonaktif.</p>

                    <div class="student-class-alumni-page-actions">
                        <a href="{{ route('student-management.students.index', ['unit_id' => $class->education_unit_id, 'class_id' => $class->id, 'year_id' => $year->id, 'status' => 'active']) }}" class="button student-filter-reset">Batal</a>
                        <button class="button button-primary">{!! $icon('check') !!} Konfirmasi Alumni</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
</body>
</html>
