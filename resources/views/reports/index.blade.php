<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'grid'=>'<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>','database'=>'<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v12c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/>','receipt'=>'<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>','wallet'=>'<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>','card'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>','chart'=>'<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>','settings'=>'<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>','menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>','bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>','logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>','chevron'=>'<path d="m9 18 6-6-6-6"/>','download'=>'<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>','users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>','filter'=>'<path d="M4 5h16M7 12h10M10 19h4"/>',
    ];
    $icon=fn($name,$class='')=>'<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $rupiah=fn($value)=>'Rp '.number_format($value,0,',','.');
    $maxDaily=max(1,(int)$daily->max());
@endphp
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="brand"><div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div><div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div><button class="icon-button sidebar-close" data-sidebar-close>×</button></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item">{!! $icon('grid') !!}<span>Dashboard</span></a>
            <a href="{{ route('master.index') }}" class="nav-item">{!! $icon('database') !!}<span>Data Master</span></a>
            <div class="nav-group nested-nav"><button type="button" class="nav-item nav-parent" data-nav-toggle aria-expanded="false">{!! $icon('card') !!}<span>Pembayaran</span>{!! $icon('chevron','nav-chevron') !!}</button><div class="nav-submenu"><a href="{{ route('finance.spp.index') }}">{!! $icon('wallet') !!}<span>SPP</span></a><a href="{{ route('finance.other.index') }}">{!! $icon('receipt') !!}<span>Lain-lain</span></a></div></div>
            <a href="{{ route('finance.bills.index') }}" class="nav-item">{!! $icon('receipt') !!}<span>Tagihan</span></a>
            <a href="{{ route('reports.index') }}" class="nav-item active">{!! $icon('chart') !!}<span>Laporan</span></a>
            <a href="{{ route('settings.index') }}" class="nav-item">{!! $icon('settings') !!}<span>Pengaturan</span></a>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar"><button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button><div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div><div class="topbar-spacer"></div><button class="icon-button notification-button">{!! $icon('bell') !!}</button><button class="icon-button logout-button">{!! $icon('logout') !!}</button></header>
        <main class="finance-page report-page">
            <section class="hero master-hero"><div><p class="eyebrow">Keuangan · Laporan</p><h1>Laporan Pembayaran</h1><p>Pantau seluruh pemasukan SPP dan pembayaran lain-lain dalam satu tempat.</p></div><a href="{{ route('reports.export', request()->query()) }}" class="button button-primary">{!! $icon('download') !!} Export CSV</a></section>
            <section class="report-metrics">
                <div class="total"><span>{!! $icon('chart') !!}</span><p><small>Total Pemasukan</small><strong>{{ $rupiah($stats['total']) }}</strong><b>{{ $stats['transactions'] }} transaksi</b></p></div>
                <div class="spp"><span>{!! $icon('wallet') !!}</span><p><small>Pembayaran SPP</small><strong>{{ $rupiah($stats['spp']) }}</strong><b>periode terpilih</b></p></div>
                <div class="other"><span>{!! $icon('receipt') !!}</span><p><small>Pembayaran Lain-lain</small><strong>{{ $rupiah($stats['other']) }}</strong><b>periode terpilih</b></p></div>
                <div class="students"><span>{!! $icon('users') !!}</span><p><small>Siswa Membayar</small><strong>{{ $stats['students'] }}</strong><b>siswa berbeda</b></p></div>
            </section>
            <section class="card report-filter-card">
                <div class="report-section-head"><span>{!! $icon('filter') !!}</span><div><strong>Filter Laporan</strong><small>Sesuaikan data transaksi yang ingin ditampilkan.</small></div><a href="{{ route('reports.index') }}">Reset</a></div>
                <form method="GET" class="report-filter">
                    <label>Mulai tanggal<input type="date" name="start_date" value="{{ $filters['start_date'] }}"></label>
                    <label>Sampai tanggal<input type="date" name="end_date" value="{{ $filters['end_date'] }}"></label>
                    <label>Jenis pembayaran<select name="type"><option value="">Semua Jenis</option><option value="spp" @selected($filters['type']==='spp')>SPP</option><option value="other" @selected($filters['type']==='other')>Lain-lain</option></select></label>
                    <label>Metode<select name="payment_method"><option value="">Semua Metode</option><option @selected($filters['payment_method']==='Cash')>Cash</option><option @selected($filters['payment_method']==='Transfer')>Transfer</option></select></label>
                    <label>Unit pendidikan<select name="unit_id"><option value="">Semua Unit</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected($filters['unit_id']==$unit->id)>{{ $unit->name }}</option>@endforeach</select></label>
                    <label>Cari siswa<input name="search" value="{{ $filters['search'] }}" placeholder="NIS atau nama"></label>
                    <button class="button button-primary">Terapkan Filter</button>
                </form>
            </section>
            <section class="report-grid">
                <div class="card report-chart-card"><div class="report-section-head"><span>{!! $icon('chart') !!}</span><div><strong>Tren Pemasukan Harian</strong><small>{{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</small></div></div><div class="report-bars">@forelse($daily as $date=>$amount)<div><b style="height:{{ max(8,($amount/$maxDaily)*100) }}%"></b><span>{{ \Carbon\Carbon::parse($date)->format('d M') }}</span><small>{{ $rupiah($amount) }}</small></div>@empty<p>Belum ada transaksi pada periode ini.</p>@endforelse</div></div>
                <div class="card report-composition"><div class="report-section-head"><span>{!! $icon('receipt') !!}</span><div><strong>Komposisi Pemasukan</strong><small>Perbandingan jenis pembayaran</small></div></div><div class="composition-total"><strong>{{ $rupiah($stats['total']) }}</strong><span>total pemasukan</span></div><div class="composition-row"><span><i class="spp"></i>SPP</span><strong>{{ $stats['total'] ? round($stats['spp']/$stats['total']*100) : 0 }}%</strong></div><div class="composition-row"><span><i class="other"></i>Lain-lain</span><strong>{{ $stats['total'] ? round($stats['other']/$stats['total']*100) : 0 }}%</strong></div></div>
            </section>
            <section class="card report-table-card"><div class="report-section-head"><span>{!! $icon('receipt') !!}</span><div><strong>Rincian Transaksi</strong><small>{{ $transactions->total() }} transaksi ditemukan</small></div></div><div class="table-wrap"><table class="data-table report-table"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Jenis</th><th>Keterangan</th><th>Unit / Kelas</th><th>Metode</th><th>Nominal</th></tr></thead><tbody>@forelse($transactions as $item)<tr><td><strong>{{ $item['date']->format('d/m/Y') }}</strong><small>{{ $item['date']->format('H:i') }}</small></td><td><strong>{{ $item['student'] }}</strong><small>{{ $item['nis'] }}</small></td><td><span class="report-type {{ $item['type']==='SPP'?'spp':'other' }}">{{ $item['type'] }}</span></td><td>{{ $item['description'] }}</td><td><strong>{{ $item['unit'] }}</strong><small>{{ $item['class'] }}</small></td><td>{{ $item['method'] }}</td><td><strong class="report-amount">{{ $rupiah($item['amount']) }}</strong></td></tr>@empty<tr><td colspan="7" class="empty-state">Belum ada transaksi pada filter ini.</td></tr>@endforelse</tbody></table></div><div class="pagination-wrap">{{ $transactions->links() }}</div></section>
        </main>
    </div>
</div>
</body>
</html>
