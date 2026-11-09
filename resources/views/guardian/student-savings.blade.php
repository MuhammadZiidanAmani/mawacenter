<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tabungan Anak - MA'WA CENTER</title>
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
    @include('partials.sidebar', ['activeMenu' => 'guardian-savings'])
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

        <main class="student-savings-page guardian-savings-page">
            <header class="guardian-savings-heading">
                <div>
                    <span class="student-savings-mock-badge">Data Simulasi</span>
                    <h1>Tabungan Anak</h1>
                    <p>Pantau saldo titipan setiap anak yang terhubung ke akun wali Anda.</p>
                </div>
                @if($accounts->isNotEmpty())
                    <div class="student-savings-total"><span>Total Saldo Anak</span><strong>{{ $rupiah($totalBalance) }}</strong><small>{{ $accounts->count() }} rekening terhubung</small></div>
                @endif
            </header>

            @include('finance.partials.student-savings-classification')

            @if($accounts->isEmpty())
                <section class="guardian-savings-empty">
                    <span class="guardian-savings-empty-icon">{!! $icon('wallet') !!}</span>
                    <h2>Belum ada data tabungan anak</h2>
                    <p>Akun wali ini belum terhubung ke data santri aktif. Hubungi administrator sekolah jika data anak belum muncul.</p>
                    <a href="{{ route('finance.bills.index') }}" class="button button-secondary">Lihat Tagihan Anak</a>
                </section>
            @else
                <section class="guardian-savings-grid" aria-label="Daftar saldo titipan anak">
                    @foreach($accounts as $account)
                        <article class="guardian-savings-card" data-guardian-savings-account>
                            <header>
                                <span class="guardian-savings-avatar">{{ collect(explode(' ', $account['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                                <div><h2>{{ $account['name'] }}</h2><p>NIS {{ $account['nis'] }} · {{ $account['unit'] }} / {{ $account['class'] }}</p></div>
                            </header>
                            <div class="guardian-savings-balance"><span>Saldo Titipan</span><strong>{{ $rupiah($account['balance']) }}</strong></div>
                            <footer>
                                <span>Aktivitas terakhir <strong>{{ $account['last_activity'] }}</strong></span>
                                <a href="{{ route('guardian.savings.show', $account['id']) }}" class="app-table-action">Lihat Mutasi</a>
                            </footer>
                        </article>
                    @endforeach
                </section>
                <p class="guardian-savings-notice">Saldo di halaman ini adalah data simulasi frontend dan belum menjadi bukti transaksi resmi.</p>
            @endif
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
