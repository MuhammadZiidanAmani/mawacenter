<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabungan Santri - MA'WA CENTER</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icon = fn (string $name) => match ($name) {
        'menu' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'bell' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
        'logout' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/></svg>',
        'wallet' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/></svg>',
        default => '',
    };
    $rupiah = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'student-savings'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main class="student-savings-page" data-student-savings>
            <header class="student-savings-heading">
                <div>
                    <span class="student-savings-mock-badge">Data Simulasi</span>
                    <h1>Tabungan Santri</h1>
                    <p>Pantau saldo titipan santri dari seluruh unit dalam satu daftar.</p>
                </div>
                <div class="student-savings-heading-actions">
                    <a href="{{ route('finance.student-savings.create') }}" class="button button-primary">Setor Titipan</a>
                    <div class="student-savings-total"><span>Total Saldo Titipan</span><strong>{{ $rupiah($totalBalance) }}</strong><small>{{ $accounts->count() }} rekening simulasi</small></div>
                </div>
            </header>

            <section class="student-savings-panel" aria-labelledby="student-savings-list-title">
                <div class="student-savings-toolbar">
                    <div><h2 id="student-savings-list-title">Daftar Saldo Titipan</h2><span><strong data-student-savings-count>{{ $accounts->count() }}</strong> santri ditampilkan</span></div>
                    <label class="student-savings-search"><span class="sr-only">Cari santri</span><input type="search" placeholder="Cari nama, NIS, atau unit" data-student-savings-search></label>
                </div>
                <div class="app-table-scroll table-wrap">
                    <table class="app-data-table student-savings-table">
                        <thead><tr><th>Santri</th><th>Unit / Kelas</th><th>Aktivitas Terakhir</th><th class="student-savings-money">Saldo Titipan</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach($accounts as $account)
                                <tr data-student-savings-row data-search="{{ strtolower($account['name'].' '.$account['nis'].' '.$account['unit'].' '.$account['class']) }}">
                                    <td><strong>{{ $account['name'] }}</strong><small>NIS {{ $account['nis'] }}</small></td>
                                    <td><strong>{{ $account['unit'] }}</strong><small>Kelas {{ $account['class'] }}</small></td>
                                    <td>{{ $account['last_activity'] }}</td>
                                    <td class="student-savings-money"><strong>{{ $rupiah($account['balance']) }}</strong></td>
                                    <td><a href="{{ route('finance.student-savings.show', $account['nis']) }}" class="app-table-action">Lihat Mutasi</a></td>
                                </tr>
                            @endforeach
                            <tr data-student-savings-empty hidden><td colspan="5" class="app-table-empty student-savings-empty">Tidak ada santri yang cocok dengan pencarian.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
