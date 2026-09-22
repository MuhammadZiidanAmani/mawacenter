<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Tagihan - {{ $student->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'print' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp. '.number_format($amount, 0, ',', '.').',-';
    $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
    $canCreateCashPayment = auth()->user()?->hasPermission('payments.cash.create') ?? false;
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'bills'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button type="button" class="icon-button menu-toggle always-visible" data-sidebar-toggle aria-label="Buka menu" title="Buka menu">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button type="button" class="icon-button notification-button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main class="student-page bill-detail-web">
            <div class="bill-detail-web-head">
                <div class="bill-detail-web-title">
                    <h1>Detail Tagihan</h1>
                    <p>Rincian kewajiban administrasi siswa.</p>
                </div>
                <div class="bill-detail-web-actions">
                    <a href="{{ $backUrl }}" class="button bill-detail-action-secondary">Kembali</a>
                    @if($canCreateCashPayment)
                    <a href="{{ route('finance.payments.index', ['student_id' => $student->id, 'search' => $student->nis]) }}" class="button bill-detail-action-primary">Bayar</a>
                    @endif
                    <a href="{{ $downloadUrl }}" class="button bill-detail-action-primary">{!! $icon('download') !!} Unduh</a>
                    <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="button bill-detail-action-secondary">{!! $icon('print') !!} Cetak</a>
                </div>
            </div>

            <section class="bill-detail-web-summary">
                <article class="bill-detail-web-panel">
                    <h2>Data Siswa</h2>
                    <div class="bill-detail-web-grid">
                        <div class="bill-detail-web-field"><span>Nama Siswa</span><strong>{{ strtoupper($student->name) }}</strong></div>
                        <div class="bill-detail-web-field"><span>NIS</span><b>{{ $student->nis ?: '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Kelas</span><b>{{ $student->schoolClass?->name ?? '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Unit</span><b>{{ $student->schoolClass?->educationUnit?->name ?? '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Status</span><strong class="{{ $statement['total'] > 0 ? 'bill-detail-web-status' : '' }}">{{ $statement['total'] > 0 ? 'Belum Lunas' : 'Lunas' }}</strong></div>
                        <div class="bill-detail-web-field"><span>Tgl Cetak</span><b>{{ $issuedDate }}</b></div>
                    </div>
                </article>
                <article class="bill-detail-web-panel bill-detail-web-total">
                    <span>Total Tagihan</span>
                    <strong>{{ $rupiah($statement['total']) }}</strong>
                </article>
            </section>

            <section class="bill-detail-web-panel">
                <h2>Rincian Tagihan</h2>
                <div class="bill-detail-web-table-wrap">
                    <table class="bill-detail-web-table">
                        <colgroup>
                            <col class="bill-detail-col-number">
                            <col>
                            <col class="bill-detail-col-year">
                            <col class="bill-detail-col-amount">
                            <col class="bill-detail-col-months">
                            <col class="bill-detail-col-total">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Uraian</th>
                                <th>Tahun</th>
                                <th>Nominal</th>
                                <th>Jml Bulan</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statement['lines'] as $line)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $line['title'] }}</td>
                                    <td>{{ $line['year'] }}</td>
                                    <td class="is-money">{{ $rupiah($line['unit_amount']) }}</td>
                                    <td>{{ $line['month_count'] }}</td>
                                    <td class="is-money">{{ $rupiah($line['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">Tidak ada tagihan aktif.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">Total Keseluruhan</td>
                                <td class="is-money">{{ $rupiah($statement['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="bill-detail-web-words">Terbilang: {{ $amountWords }}</p>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
