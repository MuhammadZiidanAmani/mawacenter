<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Titipan per Unit - MA'WA CENTER</title>
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

        <main class="student-savings-page student-savings-recap-page">
            <header class="student-savings-deposit-heading">
                <a href="{{ route('finance.student-savings.index') }}" class="student-savings-back">← Kembali ke daftar</a>
                <span class="student-savings-mock-badge">Data Simulasi</span>
                <h1>Rekap Titipan per Unit</h1>
                <p>Bandingkan jumlah rekening dan saldo titipan pada unit yang berada dalam cakupan akses Anda.</p>
            </header>

            @include('finance.partials.student-savings-classification')

            <section class="student-savings-recap-stats" aria-label="Ringkasan rekap titipan">
                <article><span>Unit Tercakup</span><strong>{{ $unitCount }}</strong></article>
                <article><span>Rekening Santri</span><strong>{{ $accountCount }}</strong></article>
                <article class="is-total"><span>Total Saldo Titipan</span><strong>{{ $rupiah($grandTotal) }}</strong></article>
            </section>

            <section class="student-savings-panel">
                <div class="student-savings-toolbar"><div><h2>Rincian per Unit</h2><span>Diurutkan dari total saldo terbesar</span></div></div>
                @if($rows->isEmpty())
                    <div class="student-savings-recap-empty"><strong>Belum ada data unit</strong><span>Hubungkan akun ke unit pendidikan yang sesuai untuk melihat rekap.</span></div>
                @else
                    <div class="app-table-scroll table-wrap">
                        <table class="app-data-table student-savings-recap-table">
                            <thead><tr><th>Unit</th><th>Rekening</th><th class="student-savings-money">Rata-rata Saldo</th><th class="student-savings-money">Total Saldo</th><th>Proporsi</th></tr></thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr data-student-savings-recap-row>
                                        <td><strong>{{ $row['unit'] }}</strong><small>{{ $row['name'] }}</small></td>
                                        <td>{{ $row['accounts'] }} santri</td>
                                        <td class="student-savings-money">{{ $rupiah($row['average']) }}</td>
                                        <td class="student-savings-money"><strong>{{ $rupiah($row['total']) }}</strong></td>
                                        <td>
                                            <div class="student-savings-recap-progress"><span style="--app-chart-progress: {{ $row['percentage'] }}"></span></div>
                                            <small>{{ number_format($row['percentage'], 1, ',', '.') }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr><th>Total</th><th>{{ $accountCount }} santri</th><th></th><th class="student-savings-money">{{ $rupiah($grandTotal) }}</th><th>100,0%</th></tr></tfoot>
                        </table>
                    </div>
                @endif
            </section>
            <p class="guardian-savings-notice">Rekap ini memakai data simulasi frontend dan belum menjadi laporan keuangan resmi.</p>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
