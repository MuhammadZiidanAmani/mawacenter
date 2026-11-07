<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekonsiliasi Pembayaran - MA'WA CENTER</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icon = fn (string $name) => match ($name) {
        'menu' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'bell' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
        'logout' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/></svg>',
        'eye' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
        default => '',
    };
    $rupiah = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'payment-reconciliation'])
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

        <main class="payment-reconciliation-page" data-payment-reconciliation>
            <header class="payment-reconciliation-heading">
                <div>
                    <p class="eyebrow">Kontrol Keuangan</p>
                    <h1>Rekonsiliasi Pembayaran</h1>
                    <p>Tinjau transaksi yang memiliki selisih nominal, alokasi, atau status sebelum dilakukan perbaikan.</p>
                </div>
                <span class="payment-reconciliation-mock-badge">Data Simulasi</span>
            </header>

            <div class="payment-reconciliation-notice" role="note">
                Halaman ini masih menggunakan data simulasi. Tombol tinjau hanya menampilkan detail dan tidak mengubah transaksi.
            </div>

            <section class="payment-reconciliation-stats" aria-label="Ringkasan transaksi bermasalah">
                <article><span>Perlu Ditinjau</span><strong>{{ $transactions->count() }}</strong><small>transaksi simulasi</small></article>
                <article class="is-critical"><span>Prioritas Kritis</span><strong>{{ $criticalCount }}</strong><small>perlu pemeriksaan segera</small></article>
                <article><span>Total Selisih</span><strong>{{ $rupiah($totalDifference) }}</strong><small>nilai absolut seluruh anomali</small></article>
                <article><span>Belum Teralokasi</span><strong>{{ $unallocatedCount }}</strong><small>transaksi tanpa tujuan tagihan</small></article>
            </section>

            <section class="payment-reconciliation-panel">
                <div class="payment-reconciliation-filters">
                    <label class="payment-reconciliation-search">
                        <span>Cari Transaksi</span>
                        <input type="search" placeholder="Nomor transaksi, siswa, atau NIS" data-reconciliation-search>
                    </label>
                    <label>
                        <span>Jenis Masalah</span>
                        <select data-reconciliation-issue>
                            <option value="all">Semua masalah</option>
                            <option value="allocation">Alokasi tidak sesuai</option>
                            <option value="unallocated">Belum teralokasi</option>
                            <option value="status">Status tertahan</option>
                        </select>
                    </label>
                    <label>
                        <span>Prioritas</span>
                        <select data-reconciliation-severity>
                            <option value="all">Semua prioritas</option>
                            <option value="critical">Kritis</option>
                            <option value="warning">Peringatan</option>
                        </select>
                    </label>
                    <button type="button" class="button button-secondary" data-reconciliation-reset>Reset</button>
                </div>

                <div class="app-table-scroll table-wrap">
                    <table class="app-data-table payment-reconciliation-table">
                        <thead>
                            <tr><th>Transaksi</th><th>Siswa</th><th>Masalah</th><th>Nominal Tercatat</th><th>Alokasi</th><th>Selisih</th><th>Prioritas</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @php
                                    $searchText = strtolower(collect([$transaction['reference'], $transaction['student'], $transaction['nis'], $transaction['unit'], $transaction['source']])->join(' '));
                                @endphp
                                <tr data-reconciliation-row data-search="{{ $searchText }}" data-issue="{{ $transaction['issue_key'] }}" data-severity="{{ $transaction['severity_key'] }}">
                                    <td><strong>{{ $transaction['reference'] }}</strong><small>{{ $transaction['date'] }} · {{ $transaction['source'] }}</small></td>
                                    <td><strong>{{ $transaction['student'] }}</strong><small>{{ $transaction['nis'] }} · {{ $transaction['unit'] }} · {{ $transaction['class'] }}</small></td>
                                    <td><strong>{{ $transaction['issue'] }}</strong><small>{{ $transaction['method'] }}</small></td>
                                    <td class="payment-reconciliation-money">{{ $rupiah($transaction['recorded_amount']) }}</td>
                                    <td class="payment-reconciliation-money">{{ $rupiah($transaction['allocated_amount']) }}</td>
                                    <td @class(['payment-reconciliation-money', 'is-balanced' => $transaction['difference'] === 0, 'is-mismatch' => $transaction['difference'] !== 0])>{{ $transaction['difference'] < 0 ? '-' : '' }}{{ $rupiah(abs($transaction['difference'])) }}</td>
                                    <td><span @class(['app-status', 'danger' => $transaction['severity_key'] === 'critical', 'warning' => $transaction['severity_key'] === 'warning'])>{{ $transaction['severity'] }}</span></td>
                                    <td>
                                        <button type="button" class="app-table-action is-icon-only" title="Tinjau transaksi" aria-label="Tinjau transaksi {{ $transaction['reference'] }}" data-reconciliation-detail
                                            data-reference="{{ $transaction['reference'] }}"
                                            data-student="{{ $transaction['student'] }}"
                                            data-student-meta="{{ $transaction['nis'] }} · {{ $transaction['unit'] }} · {{ $transaction['class'] }}"
                                            data-issue-label="{{ $transaction['issue'] }}"
                                            data-description="{{ $transaction['description'] }}"
                                            data-recommendation="{{ $transaction['recommendation'] }}"
                                            data-recorded="{{ $rupiah($transaction['recorded_amount']) }}"
                                            data-allocated="{{ $rupiah($transaction['allocated_amount']) }}"
                                            data-difference="{{ $transaction['difference'] < 0 ? '-' : '' }}{{ $rupiah(abs($transaction['difference'])) }}">{!! $icon('eye') !!}</button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr data-reconciliation-empty hidden><td colspan="8" class="app-table-empty empty-state">Tidak ada transaksi yang cocok dengan filter.</td></tr>
                        </tbody>
                    </table>
                </div>
                <footer class="payment-reconciliation-footer"><span data-reconciliation-count>{{ $transactions->count() }}</span> transaksi ditampilkan</footer>
            </section>

            <div class="modal-backdrop payment-reconciliation-detail-backdrop" data-reconciliation-modal role="dialog" aria-modal="true" aria-labelledby="reconciliation-detail-title" hidden>
                <div class="form-modal payment-reconciliation-detail-modal" role="document">
                    <div class="form-modal-header">
                        <div><p class="eyebrow">Detail Anomali</p><h2 id="reconciliation-detail-title" data-reconciliation-modal-reference>-</h2></div>
                        <button type="button" class="icon-button" data-reconciliation-close aria-label="Tutup dialog">×</button>
                    </div>
                    <div class="payment-reconciliation-detail-content">
                        <div class="payment-reconciliation-detail-student"><strong data-reconciliation-modal-student>-</strong><span data-reconciliation-modal-student-meta>-</span></div>
                        <dl>
                            <div><dt>Nominal Tercatat</dt><dd data-reconciliation-modal-recorded>-</dd></div>
                            <div><dt>Total Alokasi</dt><dd data-reconciliation-modal-allocated>-</dd></div>
                            <div><dt>Selisih</dt><dd data-reconciliation-modal-difference>-</dd></div>
                        </dl>
                        <section><span>Masalah</span><strong data-reconciliation-modal-issue>-</strong><p data-reconciliation-modal-description>-</p></section>
                        <section class="is-recommendation"><span>Rekomendasi Pemeriksaan</span><p data-reconciliation-modal-recommendation>-</p></section>
                    </div>
                    <div class="form-actions payment-reconciliation-detail-actions"><button type="button" class="button button-primary" data-reconciliation-close>Selesai</button></div>
                </div>
            </div>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
