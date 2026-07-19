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
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => $section,
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>
        <main class="finance-page student-management-page">
            <section class="hero master-hero">
                <div><p class="eyebrow">Manajemen Siswa</p><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
                <a href="{{ route('student-management.students.index') }}" class="button button-secondary">Data Siswa {!! $icon('arrow') !!}</a>
            </section>
            <section class="student-management-placeholder">
                <div class="student-management-placeholder-icon">{!! $icon('users') !!}</div>
                <div>
                    <strong>{{ $title }}</strong>
                    <p>Menu ini sudah tersedia di sidebar dan siap dipakai untuk pengembangan alur berikutnya.</p>
                </div>
                <div class="student-management-summary">
                    <span>Siswa Aktif <b>{{ number_format($stats['students'], 0, ',', '.') }}</b></span>
                    <span>Kelas <b>{{ number_format($stats['classes'], 0, ',', '.') }}</b></span>
                    <span>Alumni/Nonaktif <b>{{ number_format($stats['alumni'], 0, ',', '.') }}</b></span>
                </div>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
