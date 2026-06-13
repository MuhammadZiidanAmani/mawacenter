<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d5f36">
    <title>Dashboard - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'grid' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'database' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'upload' => '<path d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'finance' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M7 5V3h10v2M3 10h18M7 15h3"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'person' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'message' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3 1.7-5A8 8 0 1 1 21 15Z"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';

@endphp

<div class="app-shell" data-app>
    <aside class="sidebar" data-sidebar>
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div>
            <div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div>
            <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu">×</button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item active">{!! $icon('grid') !!}<span>Dashboard</span></a>
            <div class="nav-group master-nav">
                <button type="button" class="nav-item nav-parent" data-master-nav-toggle aria-expanded="false">{!! $icon('database') !!}<span>Data Master</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    <a href="{{ route('master.index', ['tab' => 'academic-years']) }}">{!! $icon('calendar') !!}<span>Tahun Pelajaran</span></a>
                    <a href="{{ route('master.index', ['tab' => 'education-units']) }}">{!! $icon('grid') !!}<span>Unit Pendidikan</span></a>
                    <a href="{{ route('master.index', ['tab' => 'classes']) }}">{!! $icon('database') !!}<span>Kelas</span></a>
                    <a href="{{ route('master.index', ['tab' => 'students']) }}">{!! $icon('users') !!}<span>Siswa</span></a>
                    <a href="{{ route('master.index', ['tab' => 'fee-types']) }}">{!! $icon('receipt') !!}<span>Kategori Pembayaran</span></a>
                    <a href="{{ route('master.index', ['tab' => 'spp-settings']) }}">{!! $icon('wallet') !!}<span>Set SPP</span></a>
                    <a href="{{ route('master.index', ['tab' => 'fee-discounts']) }}">{!! $icon('wallet') !!}<span>Keringanan Biaya</span></a>
                </div>
            </div>
            <div class="nav-group nested-nav">
                <button type="button" class="nav-item nav-parent" data-nav-toggle aria-expanded="false">{!! $icon('card') !!}<span>Pembayaran</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    <a href="{{ route('finance.spp.index') }}">{!! $icon('wallet') !!}<span>SPP</span></a>
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
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button" type="button" aria-label="Keluar" title="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main>
            <section class="dashboard-empty card">
                <span class="dashboard-empty-icon">{!! $icon('grid') !!}</span>
                <h1>Dashboard Belum Memiliki Data</h1>
                <p>Mulai dengan menambahkan Tahun Pelajaran, Unit Pendidikan, Kelas, dan Siswa melalui Data Master.</p>
                <a href="{{ route('master.index', ['tab' => 'academic-years']) }}" class="button button-primary">{!! $icon('database') !!} Buka Data Master</a>
            </section>
        </main>
    </div>

</div>
</body>
</html>
