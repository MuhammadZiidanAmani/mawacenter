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
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $rupiah = fn ($value) => 'Rp '.number_format($value, 0, ',', '.');
    $maxTrend = max(1, (int) $monthlyTrend->max('amount'));
@endphp

<div class="app-shell" data-app>
    @include('partials.sidebar', ['activeMenu' => 'dashboard'])

    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}@if($stats['overdue_count'])<span></span>@endif</button>
            <button class="icon-button logout-button" type="button" aria-label="Keluar" title="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main class="dashboard-page">
            <section class="dashboard-hero">
                <div>
                    <h1>Selamat datang, {{ auth()->user()->name }}</h1>
                    <p>Pantau pemasukan, tagihan, dan aktivitas pembayaran terbaru dalam satu tampilan.</p>
                </div>
                <div class="dashboard-hero-actions">
                    <a href="{{ route('finance.spp.index') }}" class="button dashboard-action-primary">{!! $icon('wallet') !!} Catat Pembayaran</a>
                    <a href="{{ route('reports.index') }}" class="button dashboard-action-secondary">{!! $icon('chart') !!} Lihat Laporan</a>
                </div>
            </section>

            <section class="metric-grid">
                <article class="metric-card paid">
                    <div class="metric-head"><span class="metric-icon green">{!! $icon('wallet') !!}</span><span class="trend {{ $stats['income_trend'] >= 0 ? 'up' : 'down' }}">{{ $stats['income_trend'] >= 0 ? '+' : '' }}{{ $stats['income_trend'] }}%</span></div>
                    <p>Pemasukan Bulan Ini</p><h2>{{ $rupiah($stats['income_month']) }}</h2>
                    <small>Hari ini <strong>{{ $rupiah($stats['income_today']) }}</strong></small>
                </article>
                <article class="metric-card">
                    <div class="metric-head"><span class="metric-icon indigo">{!! $icon('receipt') !!}</span><span class="period">Tahun aktif</span></div>
                    <p>Sisa Tagihan</p><h2>{{ $rupiah($stats['outstanding']) }}</h2>
                    <small>Dari total tagihan <strong>{{ $rupiah($stats['total_billed']) }}</strong></small>
                </article>
                <article class="metric-card overdue">
                    <div class="metric-head"><span class="metric-icon red">{!! $icon('alert') !!}</span><span class="status danger">{{ $stats['overdue_count'] }} tagihan</span></div>
                    <p>Lewat Jatuh Tempo</p><h2>{{ $rupiah($stats['overdue_amount']) }}</h2>
                    <small>Perlu tindak lanjut penagihan</small>
                </article>
                <article class="metric-card">
                    <div class="metric-head"><span class="metric-icon blue">{!! $icon('users') !!}</span><span class="status success">Aktif</span></div>
                    <p>Siswa Aktif</p><h2>{{ number_format($stats['active_students'], 0, ',', '.') }}</h2>
                    <small>Tahun pelajaran <strong>{{ $activeAcademicYear?->name ?? 'belum diatur' }}</strong></small>
                </article>
            </section>

            <section class="dashboard-grid">
                <article class="card chart-card dashboard-chart-card">
                    <div class="card-header"><div><h3>Tren Pemasukan</h3><p>Akumulasi pembayaran diterima selama enam bulan terakhir</p></div><a href="{{ route('reports.index') }}">Laporan lengkap {!! $icon('arrow') !!}</a></div>
                    <div class="chart-summary"><div><small>Total enam bulan</small><strong>{{ $rupiah($monthlyTrend->sum('amount')) }}</strong></div><span class="dashboard-chart-note">{!! $icon('clock') !!} Diperbarui {{ now()->format('d/m/Y H.i') }}</span></div>
                    <div class="bar-chart">
                        @foreach($monthlyTrend as $month)
                            <div class="bar-column" title="{{ $month['label'] }}: {{ $rupiah($month['amount']) }}"><div class="bar-track"><span style="height: {{ max(4, ($month['amount'] / $maxTrend) * 100) }}%"></span></div><small>{{ $month['label'] }}</small></div>
                        @endforeach
                    </div>
                </article>

                <article class="card target-card">
                    <div class="card-header"><div><h3>Rasio Penagihan</h3><p>Tagihan yang telah berhasil diterima</p></div></div>
                    <div class="donut" style="--progress: {{ $stats['collection_rate'] }}"><div><strong>{{ $stats['collection_rate'] }}%</strong><small>tertagih</small></div></div>
                    <div class="target-value"><strong>{{ $rupiah($stats['total_paid']) }}</strong><span>dari {{ $rupiah($stats['total_billed']) }}</span></div>
                    <div class="target-details"><span><i class="dot blue-dot"></i> Sudah diterima<strong>{{ $rupiah($stats['total_paid']) }}</strong></span><span><i class="dot pale-dot"></i> Belum diterima<strong>{{ $rupiah($stats['outstanding']) }}</strong></span></div>
                </article>
            </section>

            <section class="dashboard-lower-grid">
                <article class="card">
                    <div class="card-header"><div><h3>Transaksi Terbaru</h3><p>Pembayaran yang baru saja diterima</p></div><a href="{{ route('reports.index') }}">Lihat semua {!! $icon('arrow') !!}</a></div>
                    <div class="payment-list">
                        @forelse($recentPayments as $payment)
                            <div class="payment-item">
                                <span class="student-avatar">{{ strtoupper(substr($payment['student'], 0, 2)) }}</span>
                                <div class="payment-name"><strong>{{ $payment['student'] }}</strong><small>{{ $payment['type'] }} · {{ $payment['detail'] }}</small></div>
                                <div class="payment-amount"><strong>{{ $rupiah($payment['amount']) }}</strong><small>{{ $payment['date']->format('d M, H.i') }}</small></div>
                                <span class="check-circle">{!! $icon('check') !!}</span>
                            </div>
                        @empty
                            <div class="dashboard-list-empty">{!! $icon('wallet') !!}<span>Belum ada transaksi diterima.</span></div>
                        @endforelse
                    </div>
                </article>

                <article class="card">
                    <div class="card-header"><div><h3>Kondisi per Unit</h3><p>Siswa aktif dan sisa tagihan per unit</p></div><a href="{{ route('finance.bills.index') }}">Tagihan {!! $icon('arrow') !!}</a></div>
                    <div class="class-list">
                        @forelse($unitSummaries as $unit)
                            <div class="dashboard-unit-item">
                                <div class="class-row"><span class="class-badge blue">{{ strtoupper(substr($unit['code'], 0, 3)) }}</span><div><strong>{{ $unit['name'] }}</strong><small>{{ $unit['students'] }} siswa aktif</small></div><b>{{ $rupiah($unit['outstanding']) }}</b></div>
                                <div class="progress"><span class="blue" style="width: {{ $stats['outstanding'] ? max(3, ($unit['outstanding'] / $stats['outstanding']) * 100) : 3 }}%"></span></div>
                            </div>
                        @empty
                            <div class="dashboard-list-empty">{!! $icon('database') !!}<span>Belum ada unit pendidikan aktif.</span></div>
                        @endforelse
                    </div>
                </article>
            </section>
        </main>
    </div>

</div>
</body>
</html>
