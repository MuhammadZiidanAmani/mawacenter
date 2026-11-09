<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mutasi Tabungan {{ $account['name'] }} - MA'WA CENTER</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icon = fn (string $name) => match ($name) {
        'menu' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'bell' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
        'logout' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/></svg>',
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

        <main class="student-savings-page student-savings-detail-page">
            <header class="student-savings-detail-heading">
                <div><a href="{{ route('finance.student-savings.index') }}" class="student-savings-back">← Kembali ke daftar</a><span class="student-savings-mock-badge">Data Simulasi</span><h1>Mutasi Saldo Santri</h1><p>{{ $account['name'] }} · NIS {{ $account['nis'] }} · {{ $account['unit'] }} / {{ $account['class'] }}</p></div>
                <div class="student-savings-total"><span>Saldo Saat Ini</span><strong>{{ $rupiah($account['balance']) }}</strong><small>per {{ $account['last_activity'] }}</small></div>
            </header>

            <section class="student-savings-detail-stats" aria-label="Ringkasan mutasi">
                <article><span>Saldo Awal</span><strong>{{ $rupiah($openingBalance) }}</strong></article>
                <article class="is-deposit"><span>Total Setoran</span><strong>+{{ $rupiah($totalDeposits) }}</strong></article>
                <article class="is-withdrawal"><span>Total Penarikan</span><strong>-{{ $rupiah($totalWithdrawals) }}</strong></article>
            </section>

            <section class="student-savings-panel">
                <div class="student-savings-toolbar"><div><h2>Riwayat Mutasi</h2><span>{{ $mutations->count() }} mutasi simulasi</span></div></div>
                <div class="app-table-scroll table-wrap">
                    <table class="app-data-table student-savings-mutation-table">
                        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th class="student-savings-money">Nominal</th><th class="student-savings-money">Saldo</th></tr></thead>
                        <tbody>
                            @foreach($mutations as $mutation)
                                <tr>
                                    <td>{{ $mutation['date'] }}</td>
                                    <td><span @class(['app-status', 'success' => $mutation['amount'] > 0, 'warning' => $mutation['amount'] < 0])>{{ $mutation['type'] }}</span></td>
                                    <td>{{ $mutation['description'] }}</td>
                                    <td @class(['student-savings-money', 'is-deposit' => $mutation['amount'] > 0, 'is-withdrawal' => $mutation['amount'] < 0])>{{ $mutation['amount'] > 0 ? '+' : '-' }}{{ $rupiah(abs($mutation['amount'])) }}</td>
                                    <td class="student-savings-money"><strong>{{ $rupiah($mutation['balance']) }}</strong></td>
                                </tr>
                            @endforeach
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
