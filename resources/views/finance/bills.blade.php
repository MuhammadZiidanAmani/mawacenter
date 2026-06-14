<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagihan Siswa - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'grid'=>'<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'database'=>'<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v12c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/>',
        'receipt'=>'<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'wallet'=>'<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'card'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'chevron'=>'<path d="m9 18 6-6-6-6"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'alert'=>'<path d="M10.3 3.5 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.5a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'filter'=>'<path d="M4 5h16M7 12h10M10 19h4"/>',
        'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'chart'=>'<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings'=>'<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
@endphp
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="brand"><div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div><div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div><button class="icon-button sidebar-close" data-sidebar-close>×</button></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item">{!! $icon('grid') !!}<span>Dashboard</span></a>
            <a href="{{ route('master.index') }}" class="nav-item">{!! $icon('database') !!}<span>Data Master</span></a>
            <div class="nav-group nested-nav open"><button type="button" class="nav-item nav-parent" data-nav-toggle aria-expanded="true">{!! $icon('card') !!}<span>Pembayaran</span>{!! $icon('chevron','nav-chevron') !!}</button><div class="nav-submenu"><a href="{{ route('finance.other.index',['category'=>'daftar-ulang']) }}">{!! $icon('receipt') !!}<span>Daftar Ulang</span></a><a href="{{ route('finance.spp.index') }}">{!! $icon('wallet') !!}<span>SPP</span></a><a href="{{ route('finance.other.index',['category'=>'laundry']) }}">{!! $icon('card') !!}<span>Laundry</span></a><a href="{{ route('finance.other.index') }}">{!! $icon('receipt') !!}<span>Lain-lain</span></a></div></div>
            <a href="{{ route('finance.bills.index') }}" class="nav-item active">{!! $icon('receipt') !!}<span>Tagihan</span></a>
            <a href="{{ route('reports.index') }}" class="nav-item">{!! $icon('chart') !!}<span>Laporan</span></a>
            <a href="{{ route('settings.index') }}" class="nav-item">{!! $icon('settings') !!}<span>Pengaturan</span></a>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar"><button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button><div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div><div class="topbar-spacer"></div><button class="icon-button notification-button">{!! $icon('bell') !!}</button><button class="icon-button logout-button">{!! $icon('logout') !!}</button></header>
        <main class="finance-page bill-page">
            <section class="bill-summary-hero">
                <div class="bill-hero-copy"><span class="bill-hero-icon">{!! $icon('receipt') !!}</span><div><p class="eyebrow">Keuangan · Tagihan</p><h1>Tagihan Siswa</h1><p>Pantau seluruh kewajiban siswa secara otomatis berdasarkan pembayaran yang sudah tercatat.</p></div></div>
                <div class="bill-period-note"><span>{!! $icon('calendar') !!}</span><div><small>Periode tagihan aktif</small><strong>Sampai {{ $months[$untilMonth] }} {{ $year }}</strong><em>Dihitung otomatis</em></div></div>
            </section>

            <section class="bill-metrics">
                <div class="students"><span class="bill-metric-icon">{!! $icon('users') !!}</span><p><span>Siswa Belum Lunas</span><strong>{{ number_format($stats['students'], 0, ',', '.') }}</strong><small>perlu ditindaklanjuti</small></p></div>
                <div class="remaining"><span class="bill-metric-icon">{!! $icon('wallet') !!}</span><p><span>Sisa Tagihan SPP</span><strong>{{ $rupiah($stats['spp']) }}</strong><small>hingga periode terpilih</small></p></div>
                <div class="other"><span class="bill-metric-icon">{!! $icon('receipt') !!}</span><p><span>Sisa Lain-lain</span><strong>{{ $rupiah($stats['other']) }}</strong><small>kategori pembayaran aktif</small></p></div>
                <div class="overdue"><span class="bill-metric-icon">{!! $icon('alert') !!}</span><p><span>Total Seluruh Tagihan</span><strong>{{ $rupiah($stats['remaining']) }}</strong><small>akumulasi kewajiban siswa</small></p></div>
            </section>

            <section class="card outstanding-filter-card">
                <div class="outstanding-filter-heading"><span>{!! $icon('filter') !!}</span><div><strong>Filter Daftar Tagihan</strong><span>Cari siswa atau sesuaikan periode, unit, dan kelas.</span></div><a href="{{ route('finance.bills.index') }}">Reset filter</a></div>
                <form method="GET" class="outstanding-filter">
                    <label><b>Tahun</b><select name="year">@for($optionYear = now()->year - 2; $optionYear <= now()->year + 1; $optionYear++)<option value="{{ $optionYear }}" @selected($year === $optionYear)>{{ $optionYear }}</option>@endfor</select></label>
                    <label><b>Periode SPP</b><select name="until_month">@foreach($months as $number => $name)<option value="{{ $number }}" @selected($untilMonth === $number)>Sampai {{ $name }}</option>@endforeach</select></label>
                    <label><b>Unit pendidikan</b><select name="unit_id"><option value="">Semua Unit</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->name }}</option>@endforeach</select></label>
                    <label><b>Kelas</b><select name="class_id"><option value="">Semua Kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->educationUnit?->code }} · {{ $class->name }}</option>@endforeach</select></label>
                    <button class="button button-primary">Tampilkan Data {!! $icon('arrow') !!}</button>
                </form>
            </section>

            <section class="outstanding-list">
                <div class="outstanding-list-heading"><div><p class="eyebrow">Daftar Tagihan</p><strong>Siswa yang Belum Lunas</strong><span>{{ $studentsWithBills->total() }} siswa ditemukan pada periode ini</span></div><span class="outstanding-list-hint">{!! $icon('alert') !!} Klik rincian untuk melihat nominal lengkap</span></div>
                @include('partials.list-toolbar', ['action' => route('finance.bills.index'), 'searchLabel' => 'Cari siswa dalam daftar tagihan'])
                @forelse($studentsWithBills as $summary)
                    @php($student = $summary['student'])
                    <article class="outstanding-student-card">
                        <div class="outstanding-student-head">
                            <div class="student-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</div>
                            <div class="outstanding-student-name"><span class="student-status-dot">Belum lunas</span><strong>{{ $student->name }}</strong><span>NIS {{ $student->nis }} <i></i> {{ $student->schoolClass?->educationUnit?->code }} <i></i> {{ $student->schoolClass?->name }}</span></div>
                            <div class="outstanding-total"><small>Total Tagihan</small><strong>{{ $rupiah($summary['total_remaining']) }}</strong></div>
                        </div>
                        <div class="outstanding-overview">
                            <div class="outstanding-summary-block spp"><span class="outstanding-kind">{!! $icon('wallet') !!}</span><p><small>SPP belum lunas</small><strong>{{ count($summary['spp']) }} bulan</strong><b>{{ $rupiah($summary['spp_remaining']) }}</b></p><div class="outstanding-month-chips">@forelse($summary['spp'] as $item)<span>{{ substr($item['month_name'], 0, 3) }}</span>@empty<span class="clear">Lunas</span>@endforelse</div></div>
                            <div class="outstanding-summary-block other"><span class="outstanding-kind">{!! $icon('receipt') !!}</span><p><small>Pembayaran lain-lain</small><strong>{{ count($summary['other']) }} kategori</strong><b>{{ $rupiah($summary['other_remaining']) }}</b></p></div>
                            <div class="outstanding-actions"><a href="{{ route('finance.spp.create') }}" class="button button-secondary">{!! $icon('wallet') !!} Bayar SPP</a><a href="{{ route('finance.other.create') }}" class="button button-primary">{!! $icon('receipt') !!} Bayar Lain-lain</a></div>
                        </div>
                        <details class="outstanding-details">
                            <summary><span class="details-open">Buka rincian tagihan</span><span class="details-close">Tutup rincian</span> <i>{!! $icon('chevron') !!}</i></summary>
                            <div class="outstanding-detail-grid">
                                <div>
                                    <h3><span>{!! $icon('wallet') !!}</span> SPP yang belum lunas <b>{{ $rupiah($summary['spp_remaining']) }}</b></h3>
                                    @forelse($summary['spp'] as $item)
                                        <div class="outstanding-line"><span><strong>{{ $item['month_name'] }} {{ $year }}</strong><small>Terbayar {{ $rupiah($item['paid']) }} dari {{ $rupiah($item['total']) }}</small></span><b>{{ $rupiah($item['remaining']) }}</b></div>
                                    @empty
                                        <p class="outstanding-clear">{!! $icon('check') !!} SPP sampai periode ini sudah lunas.</p>
                                    @endforelse
                                </div>
                                <div>
                                    <h3><span>{!! $icon('receipt') !!}</span> Pembayaran lain-lain <b>{{ $rupiah($summary['other_remaining']) }}</b></h3>
                                    @forelse($summary['other'] as $item)
                                        <div class="outstanding-line"><span><strong>{{ $item['name'] }}</strong><small>Terbayar {{ $rupiah($item['paid']) }} dari {{ $rupiah($item['total']) }}</small></span><b>{{ $rupiah($item['remaining']) }}</b></div>
                                    @empty
                                        <p class="outstanding-clear">{!! $icon('check') !!} Semua kategori pembayaran sudah lunas.</p>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="outstanding-empty"><span>{!! $icon('check') !!}</span><strong>Tidak ada tagihan yang tersisa</strong><p>Semua siswa pada filter dan periode ini sudah lunas.</p></div>
                @endforelse
                <div class="pagination-wrap">{{ $studentsWithBills->links() }}</div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
