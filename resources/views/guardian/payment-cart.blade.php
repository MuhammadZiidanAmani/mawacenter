<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang Pembayaran - MA'WA CENTER</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'cart' => '<circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M3 4h2l2.7 10.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7"/>',
        'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        'building' => '<path d="M4 21V7l8-4 8 4v14M9 21v-6h6v6M8 9h1m6 0h1M8 12h1m6 0h1"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8.1 7 10 4-1.9 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-5"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $total = $subtotal + $serviceFee;
    $itemCount = $cartGroups->sum(fn ($group) => count($group['items']));
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'payment-cart'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button type="button" class="icon-button menu-toggle always-visible" data-sidebar-toggle aria-label="Buka menu" title="Buka menu">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Portal</small><strong>Wali Santri</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button type="button" class="icon-button notification-button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main class="payment-cart-page">
            <header class="payment-cart-heading">
                <div>
                    <span class="payment-cart-mock-badge">Data Simulasi</span>
                    <h1>Keranjang Pembayaran</h1>
                    <p>Gabungkan tagihan santri dari beberapa unit dalam satu pembayaran.</p>
                </div>
                <a href="{{ route('finance.bills.index') }}" class="button button-secondary">Tambah Tagihan</a>
            </header>

            <section class="payment-cart-notice" aria-label="Informasi simulasi">
                {!! $icon('cart') !!}
                <div><strong>Pratinjau keranjang lintas unit</strong><span>Nominal dan identitas di halaman ini bukan data santri sebenarnya.</span></div>
            </section>

            <div class="payment-cart-layout">
                <section class="payment-cart-items" aria-labelledby="payment-cart-items-title">
                    <div class="payment-cart-section-head">
                        <div><h2 id="payment-cart-items-title">Tagihan Dipilih</h2><span>{{ $itemCount }} tagihan dari {{ $cartGroups->count() }} unit</span></div>
                        <span class="payment-cart-identity-chip">1 identitas santri</span>
                    </div>

                    @foreach($cartGroups as $group)
                        <article class="payment-cart-group">
                            <header class="payment-cart-student">
                                <span class="payment-cart-student-icon">{!! $icon('user') !!}</span>
                                <div class="payment-cart-student-copy">
                                    <strong>{{ $group['student_name'] }}</strong>
                                    <span>{{ $group['nis'] }} · {{ $group['class_name'] }}</span>
                                </div>
                                <span class="payment-cart-unit-chip">{!! $icon('building') !!} {{ $group['unit_code'] }}</span>
                            </header>
                            <div class="payment-cart-unit-name">{{ $group['unit_name'] }}</div>
                            <div class="payment-cart-list">
                                @foreach($group['items'] as $item)
                                    <div class="payment-cart-item">
                                        <input type="checkbox" checked disabled aria-label="{{ $item['title'] }} dipilih pada data simulasi">
                                        <span class="payment-cart-item-copy"><strong>{{ $item['title'] }}</strong><small>{{ $item['period'] }}</small></span>
                                        <strong class="payment-cart-item-amount">{{ $rupiah($item['amount']) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                            <footer class="payment-cart-group-total"><span>Subtotal {{ $group['unit_code'] }}</span><strong>{{ $rupiah(collect($group['items'])->sum('amount')) }}</strong></footer>
                        </article>
                    @endforeach
                </section>

                <aside class="payment-cart-summary" aria-labelledby="payment-cart-summary-title">
                    <div class="payment-cart-section-head"><div><h2 id="payment-cart-summary-title">Ringkasan Pembayaran</h2><span>Periksa nominal sebelum melanjutkan</span></div></div>
                    <dl class="payment-cart-totals">
                        <div><dt>Subtotal tagihan</dt><dd>{{ $rupiah($subtotal) }}</dd></div>
                        <div><dt>Biaya layanan</dt><dd>{{ $rupiah($serviceFee) }}</dd></div>
                        <div class="payment-cart-grand-total"><dt>Total pembayaran</dt><dd>{{ $rupiah($total) }}</dd></div>
                    </dl>

                    <fieldset class="payment-cart-methods" disabled>
                        <legend>Metode Pembayaran</legend>
                        <label class="is-selected"><input type="radio" name="mock_payment_method" checked><span><strong>Virtual Account</strong><small>BNI Virtual Account</small></span></label>
                        <label><input type="radio" name="mock_payment_method"><span><strong>QRIS</strong><small>Bayar dari aplikasi pilihan Anda</small></span></label>
                    </fieldset>

                    <button type="button" class="button button-primary payment-cart-checkout" disabled>Lanjutkan Pembayaran</button>
                    <p class="payment-cart-helper">Checkout dinonaktifkan selama halaman menampilkan data simulasi.</p>
                    <div class="payment-cart-security">{!! $icon('shield') !!}<span><strong>Pembayaran aman</strong><small>Status tagihan baru berubah setelah pembayaran terverifikasi.</small></span></div>
                </aside>
            </div>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
