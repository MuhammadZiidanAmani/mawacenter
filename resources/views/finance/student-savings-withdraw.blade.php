<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemakaian dan Penarikan Titipan - MA'WA CENTER</title>
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
    $canManageSavings = auth()->user()?->hasPermission('payments.manage') ?? false;
    $backUrl = $canManageSavings ? route('finance.student-savings.index') : route('finance.payments.index');
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

        <main class="student-savings-page student-savings-deposit-page" data-student-savings-withdrawal>
            <header class="student-savings-deposit-heading">
                <a href="{{ $backUrl }}" class="student-savings-back">← Kembali</a>
                <span class="student-savings-mock-badge">Data Simulasi</span>
                <h1>Pemakaian dan Penarikan Titipan</h1>
                <p>Catat penggunaan saldo untuk kebutuhan sekolah atau penarikan tunai oleh pihak yang berhak.</p>
                <nav class="student-savings-transaction-nav" aria-label="Jenis transaksi titipan">
                    <a href="{{ route('finance.student-savings.create') }}">Setoran</a>
                    <a href="{{ route('finance.student-savings.withdraw') }}" class="is-active" aria-current="page">Pemakaian / Penarikan</a>
                </nav>
            </header>

            @include('finance.partials.student-savings-classification')

            <form class="student-savings-deposit-layout" action="#" data-student-savings-withdrawal-form>
                <section class="student-savings-deposit-form" aria-labelledby="student-savings-withdrawal-title">
                    <div class="student-savings-deposit-section-head">
                        <div><h2 id="student-savings-withdrawal-title">Data Pengeluaran Titipan</h2><span>Pilih jenis transaksi, santri, dan nominal yang digunakan.</span></div>
                        <span class="student-savings-withdrawal-badge">Mengurangi Saldo</span>
                    </div>

                    @if($accounts->isEmpty())
                        <div class="student-savings-deposit-empty">
                            <strong>Belum ada santri pada unit tugas Anda</strong>
                            <span>Hubungi administrator untuk memastikan kasir sudah terhubung ke unit pendidikan.</span>
                        </div>
                    @else
                        <div class="student-savings-deposit-fields">
                            <fieldset class="student-savings-withdrawal-types student-savings-deposit-field-wide">
                                <legend>Jenis Transaksi</legend>
                                <label><input type="radio" name="type" value="usage" checked data-student-savings-withdrawal-type><span><strong>Pemakaian Sekolah</strong><small>Untuk pembelian atau kebutuhan santri di sekolah</small></span></label>
                                <label><input type="radio" name="type" value="withdrawal" data-student-savings-withdrawal-type><span><strong>Penarikan Tunai</strong><small>Dana diserahkan kepada pihak yang berhak</small></span></label>
                            </fieldset>

                            <label class="student-savings-deposit-field student-savings-deposit-field-wide">
                                <span>Pilih Santri</span>
                                <select name="nis" required data-student-savings-withdrawal-account>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account['nis'] }}" data-name="{{ $account['name'] }}" data-unit="{{ $account['unit'] }}" data-class="{{ $account['class'] }}" data-balance="{{ $account['balance'] }}">{{ $account['name'] }} · {{ $account['nis'] }} · {{ $account['unit'] }} / {{ $account['class'] }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="student-savings-deposit-field student-savings-deposit-field-wide">
                                <span>Nominal</span>
                                <span class="student-savings-deposit-money"><b>Rp</b><input type="number" name="amount" min="1000" step="1000" inputmode="numeric" placeholder="0" required data-student-savings-withdrawal-amount></span>
                                <small>Nominal tidak boleh melebihi saldo titipan santri.</small>
                            </label>

                            <label class="student-savings-deposit-field">
                                <span>Tanggal Transaksi</span>
                                <input type="date" name="transaction_date" value="{{ $transactionAt->format('Y-m-d') }}" required>
                            </label>
                            <label class="student-savings-deposit-field">
                                <span>Jam Transaksi</span>
                                <input type="time" name="transaction_time" value="{{ $transactionAt->format('H:i') }}" required>
                            </label>
                            <label class="student-savings-deposit-field student-savings-deposit-field-wide">
                                <span>Tujuan / Keterangan</span>
                                <textarea name="notes" maxlength="255" rows="3" placeholder="Contoh: pembelian buku latihan" required></textarea>
                            </label>
                        </div>
                    @endif
                </section>

                <aside class="student-savings-deposit-summary" aria-label="Ringkasan pengeluaran titipan">
                    <div class="student-savings-deposit-summary-head"><h2>Ringkasan</h2><span>Pratinjau saldo</span></div>
                    @if($accounts->isNotEmpty())
                        <div class="student-savings-deposit-student">
                            <strong data-student-savings-withdrawal-name>{{ $accounts->first()['name'] }}</strong>
                            <span data-student-savings-withdrawal-identity>NIS {{ $accounts->first()['nis'] }} · {{ $accounts->first()['unit'] }} / {{ $accounts->first()['class'] }}</span>
                        </div>
                        <dl class="student-savings-deposit-totals">
                            <div><dt>Saldo Sekarang</dt><dd data-student-savings-withdrawal-balance>Rp {{ number_format($accounts->first()['balance'], 0, ',', '.') }}</dd></div>
                            <div><dt data-student-savings-withdrawal-label>Pemakaian</dt><dd class="is-withdrawal" data-student-savings-withdrawal-value>-Rp 0</dd></div>
                            <div class="student-savings-deposit-new-balance"><dt>Estimasi Saldo</dt><dd data-student-savings-withdrawal-new-balance>Rp {{ number_format($accounts->first()['balance'], 0, ',', '.') }}</dd></div>
                        </dl>
                        <p class="student-savings-deposit-note" data-student-savings-withdrawal-feedback aria-live="polite">Simulasi frontend: belum ada saldo yang dikurangi.</p>
                        <button type="submit" class="button button-danger student-savings-deposit-submit" data-student-savings-withdrawal-submit>Simpan Pengeluaran Simulasi</button>
                    @else
                        <p class="student-savings-deposit-note">Ringkasan akan tersedia setelah ada santri dalam unit tugas kasir.</p>
                        <button type="button" class="button button-danger student-savings-deposit-submit" disabled>Simpan Pengeluaran Simulasi</button>
                    @endif
                </aside>
            </form>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
