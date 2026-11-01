<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nonaktifkan Siswa - MA'WA CENTER</title>
    @include('partials.theme-init')
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
    @include('partials.sidebar', ['activeMenu' => 'students', 'activeStudentMenu' => 'data-siswa'])
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
                    <h1>Nonaktifkan Siswa</h1>
                    <p>Catat tanggal keluar dan alasan agar tagihan siswa dapat disesuaikan dengan aman.</p>
                </div>
            </div>

            <section class="student-workspace student-create-canvas student-class-alumni-canvas">
                <form method="POST" action="{{ route('student-management.students.deactivate.store', $student) }}" class="student-class-alumni-page-form">
                    @csrf

                    <div class="student-class-alumni-page-summary">
                        <div><span>NIS</span><strong>{{ $student->nis ?: '-' }}</strong></div>
                        <div><span>Nama Siswa</span><strong>{{ $student->name }}</strong></div>
                        <div><span>Unit Pendidikan</span><strong>{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</strong></div>
                        <div><span>Kelas</span><strong>{{ $student->schoolClass?->name ?? '-' }}</strong></div>
                    </div>

                    <div class="student-class-alumni-page-fields">
                        <label><span>Tanggal Keluar</span><input type="date" name="exit_date" value="{{ old('exit_date', now()->toDateString()) }}" min="{{ $student->entry_date?->format('Y-m-d') }}" required></label>
                        <label><span>Alasan</span><select name="inactive_reason" required><option value="Lulus" @selected(old('inactive_reason') === 'Lulus')>Lulus</option><option value="Pindah Sekolah" @selected(old('inactive_reason') === 'Pindah Sekolah')>Pindah Sekolah</option><option value="Mengundurkan Diri" @selected(old('inactive_reason') === 'Mengundurkan Diri')>Mengundurkan Diri</option><option value="Lainnya" @selected(old('inactive_reason') === 'Lainnya')>Lainnya</option></select></label>
                    </div>

                    <p class="student-class-alumni-page-warning">Tagihan setelah periode keluar yang belum dibayar akan dibatalkan. Tagihan periode keluar dan sebelumnya, cicilan, pembayaran lunas, serta riwayat transaksi tetap dipertahankan.</p>

                    <div class="student-class-alumni-page-actions">
                        <a href="{{ route('student-management.students.index', request()->query()) }}" class="button button-secondary">Batal</a>
                        <button class="button button-primary" type="submit">{!! $icon('check') !!} Konfirmasi Nonaktif</button>
                    </div>
                </form>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
