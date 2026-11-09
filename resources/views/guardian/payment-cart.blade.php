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
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
        'alert' => '<path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 3h.01"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $total = $subtotal + $serviceFee;
    $unitCount = $cartStudents->sum(fn ($student) => count($student['units']));
    $itemCount = $cartStudents->sum(fn ($student) => collect($student['units'])->sum(fn ($unit) => count($unit['items'])));
    $studentSummaries = $cartStudents->map(fn ($student) => [
        'name' => $student['student_name'],
        'unit_count' => count($student['units']),
        'item_count' => collect($student['units'])->sum(fn ($unit) => count($unit['items'])),
        'subtotal' => collect($student['units'])->sum(fn ($unit) => collect($unit['items'])->sum('amount')),
    ]);
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

        <main class="payment-cart-page" data-payment-cart>
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
                        <div><h2 id="payment-cart-items-title">Tagihan Dipilih</h2><span>{{ $itemCount }} tagihan dari {{ $cartStudents->count() }} anak di {{ $unitCount }} unit</span></div>
                        <span class="payment-cart-identity-chip">{{ $cartStudents->count() }} anak</span>
                    </div>

                    @forelse($cartStudents as $student)
                        @php
                            $studentItemCount = collect($student['units'])->sum(fn ($unit) => count($unit['items']));
                        @endphp
                        <article class="payment-cart-child">
                            <header class="payment-cart-student">
                                <span class="payment-cart-student-icon">{!! $icon('user') !!}</span>
                                <div class="payment-cart-student-copy">
                                    <span>Anak {{ $loop->iteration }}</span>
                                    <strong>{{ $student['student_name'] }}</strong>
                                </div>
                                <span class="payment-cart-child-count">{{ count($student['units']) }} unit · {{ $studentItemCount }} tagihan</span>
                            </header>

                            @foreach($student['units'] as $unit)
                                <section class="payment-cart-unit" aria-label="Tagihan {{ $student['student_name'] }} di {{ $unit['unit_name'] }}">
                                    <header class="payment-cart-unit-head">
                                        <span class="payment-cart-unit-icon">{!! $icon('building') !!}</span>
                                        <div>
                                            <strong>{{ $unit['unit_name'] }}</strong>
                                            <span>NIS {{ $unit['nis'] }} · Kelas {{ $unit['class_name'] }}</span>
                                        </div>
                                        <span class="payment-cart-unit-chip">{{ $unit['unit_code'] }}</span>
                                    </header>
                                    <div class="payment-cart-list">
                                        @foreach($unit['items'] as $item)
                                            <div class="payment-cart-item">
                                                <input type="checkbox" checked disabled data-payment-cart-bill aria-label="{{ $item['title'] }} dipilih pada data simulasi">
                                                <span class="payment-cart-item-copy"><strong>{{ $item['title'] }}</strong><small>{{ $item['period'] }}</small></span>
                                                <strong class="payment-cart-item-amount">{{ $rupiah($item['amount']) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                    <footer class="payment-cart-group-total"><span>Subtotal {{ $unit['unit_code'] }}</span><strong>{{ $rupiah(collect($unit['items'])->sum('amount')) }}</strong></footer>
                                </section>
                            @endforeach
                        </article>
                    @empty
                        <div class="payment-cart-empty">
                            <span class="payment-cart-empty-icon">{!! $icon('cart') !!}</span>
                            <h3>Keranjang pembayaran masih kosong</h3>
                            <p>Pilih tagihan anak terlebih dahulu agar ringkasan dan kanal pembayaran dapat ditampilkan.</p>
                            <a href="{{ route('finance.bills.index') }}" class="button button-primary">Pilih Tagihan Anak</a>
                        </div>
                    @endforelse
                </section>

                <aside class="payment-cart-summary" aria-labelledby="payment-cart-summary-title">
                    <div class="payment-cart-section-head"><div><h2 id="payment-cart-summary-title">Ringkasan Pembayaran</h2><span>Periksa nominal sebelum melanjutkan</span></div></div>
                    <div class="payment-cart-overview" aria-label="Isi keranjang">
                        <span><strong>{{ $cartStudents->count() }}</strong><small>Anak</small></span>
                        <span><strong>{{ $unitCount }}</strong><small>Unit</small></span>
                        <span><strong>{{ $itemCount }}</strong><small>Tagihan</small></span>
                    </div>

                    <div class="payment-cart-breakdown">
                        <h3>Rincian per Anak</h3>
                        <ul>
                            @forelse($studentSummaries as $studentSummary)
                                <li>
                                    <span><strong>{{ $studentSummary['name'] }}</strong><small>{{ $studentSummary['item_count'] }} tagihan · {{ $studentSummary['unit_count'] }} unit</small></span>
                                    <strong>{{ $rupiah($studentSummary['subtotal']) }}</strong>
                                </li>
                            @empty
                                <li class="payment-cart-breakdown-empty">Belum ada tagihan dalam ringkasan.</li>
                            @endforelse
                        </ul>
                    </div>

                    <dl class="payment-cart-totals">
                        <div><dt>Subtotal tagihan</dt><dd>{{ $rupiah($subtotal) }}</dd></div>
                        <div class="payment-cart-service-fee">
                            <dt><span>Biaya layanan</span><small>Dikenakan satu kali per transaksi</small></dt>
                            <dd>{{ $rupiah($serviceFee) }}</dd>
                        </div>
                        <div class="payment-cart-grand-total"><dt>Total pembayaran</dt><dd>{{ $rupiah($total) }}</dd></div>
                    </dl>

                    @if($itemCount > 0)
                        <fieldset class="payment-cart-methods" id="payment-cart-channel-options">
                            <legend>Kanal Pembayaran</legend>
                            <label class="is-selected" data-payment-cart-channel-label>
                                <input type="radio" name="mock_payment_channel" value="bni_va" data-payment-cart-channel checked>
                                <span><strong>BNI Virtual Account</strong><small>Transfer dari ATM atau mobile banking</small></span>
                            </label>
                            <label data-payment-cart-channel-label>
                                <input type="radio" name="mock_payment_channel" value="qris" data-payment-cart-channel>
                                <span><strong>QRIS</strong><small>Bayar dari aplikasi pilihan Anda</small></span>
                            </label>
                        </fieldset>

                        <div class="payment-cart-instructions" data-payment-cart-instructions="bni_va" aria-live="polite">
                            <div class="payment-cart-instructions-head">
                                <strong>Instruksi BNI Virtual Account</strong>
                                <span>Nomor berikut hanya contoh tampilan.</span>
                            </div>
                            <div class="payment-cart-va-number"><span>Nomor VA Simulasi</span><strong>8808 2601 8424 5000</strong></div>
                            <ol>
                                <li>Buka menu transfer atau pembayaran Virtual Account.</li>
                                <li>Masukkan nomor VA simulasi dan pastikan totalnya Rp 744.000.</li>
                                <li>Selesaikan pembayaran sebelum batas waktu yang ditampilkan.</li>
                            </ol>
                        </div>
                        <div class="payment-cart-instructions" data-payment-cart-instructions="qris" aria-live="polite" hidden>
                            <div class="payment-cart-instructions-head">
                                <strong>Instruksi QRIS</strong>
                                <span>Kode berikut hanya contoh tampilan.</span>
                            </div>
                            <div class="payment-cart-qris-mock" aria-hidden="true"><span>QRIS</span><strong>MOCK</strong></div>
                            <ol>
                                <li>Buka aplikasi pembayaran yang mendukung QRIS.</li>
                                <li>Pindai kode simulasi dan periksa total Rp 744.000.</li>
                                <li>Konfirmasi pembayaran sebelum kode kedaluwarsa.</li>
                            </ol>
                        </div>
                    @else
                        <div class="payment-cart-validation is-error" role="status" data-payment-cart-validation>Tambahkan minimal satu tagihan untuk memilih kanal pembayaran.</div>
                    @endif

                    <button type="button" class="button button-primary payment-cart-checkout" data-payment-cart-checkout @disabled($itemCount === 0)>Periksa Pembayaran</button>
                    <p class="payment-cart-helper" data-payment-cart-helper>{{ $itemCount > 0 ? 'Validasi ini hanya simulasi dan tidak membuat transaksi.' : 'Pilih tagihan untuk mengaktifkan pemeriksaan pembayaran.' }}</p>
                    @if($itemCount > 0)
                        <div class="payment-cart-validation" role="status" data-payment-cart-validation hidden></div>
                    @endif
                    <div class="payment-cart-security">{!! $icon('shield') !!}<span><strong>Pembayaran aman</strong><small>Status tagihan baru berubah setelah pembayaran terverifikasi.</small></span></div>
                </aside>
            </div>

            @if($itemCount > 0)
            <section class="payment-cart-result-preview" data-payment-cart-status-preview aria-labelledby="payment-cart-result-title">
                <header class="payment-cart-result-heading">
                    <div>
                        <span class="payment-cart-mock-badge">Pratinjau Status</span>
                        <h2 id="payment-cart-result-title">Hasil Pembayaran</h2>
                        <p>Pilih status untuk melihat tindak lanjut pada alur simulasi.</p>
                    </div>
                    <div class="payment-cart-status-switcher" aria-label="Pilih status simulasi">
                        <button type="button" class="is-active" data-payment-cart-status="pending" aria-pressed="true">Menunggu</button>
                        <button type="button" data-payment-cart-status="success" aria-pressed="false">Berhasil</button>
                        <button type="button" data-payment-cart-status="expired" aria-pressed="false">Kedaluwarsa</button>
                    </div>
                </header>

                <div class="payment-cart-result is-pending" data-payment-cart-result="pending">
                    <span class="payment-cart-result-icon">{!! $icon('clock') !!}</span>
                    <div class="payment-cart-result-copy">
                        <span class="payment-cart-result-badge">Menunggu Pembayaran</span>
                        <h3>Selesaikan pembayaran sebelum 9 November 2026, 23.59 WIB</h3>
                        <p>Tagihan belum berubah dan belum dianggap lunas sampai pembayaran diterima serta terverifikasi.</p>
                        <small>Referensi simulasi: MWC-MOCK-20261109-001</small>
                    </div>
                    <div class="payment-cart-result-actions">
                        <a href="#payment-cart-channel-options" class="button button-primary">Lihat Instruksi</a>
                        <a href="{{ route('finance.bills.index') }}" class="button button-secondary">Kembali ke Tagihan</a>
                    </div>
                </div>

                <div class="payment-cart-result is-success" data-payment-cart-result="success" hidden>
                    <span class="payment-cart-result-icon">{!! $icon('check') !!}</span>
                    <div class="payment-cart-result-copy">
                        <span class="payment-cart-result-badge">Pembayaran Berhasil</span>
                        <h3>Pembayaran Rp 744.000 telah terverifikasi</h3>
                        <p>Lima tagihan pada tiga unit telah diperbarui. Simpan referensi simulasi untuk pengecekan.</p>
                        <small>Referensi simulasi: MWC-MOCK-20261109-001</small>
                    </div>
                    <div class="payment-cart-result-actions">
                        <a href="{{ route('finance.bills.index') }}" class="button button-primary">Lihat Tagihan</a>
                        <button type="button" class="button button-secondary" disabled>Unduh Bukti</button>
                    </div>
                </div>

                <div class="payment-cart-result is-expired" data-payment-cart-result="expired" hidden>
                    <span class="payment-cart-result-icon">{!! $icon('alert') !!}</span>
                    <div class="payment-cart-result-copy">
                        <span class="payment-cart-result-badge">Pembayaran Kedaluwarsa</span>
                        <h3>Waktu pembayaran simulasi telah habis</h3>
                        <p>Tidak ada pembayaran yang dicatat dan seluruh tagihan tetap memiliki saldo semula.</p>
                        <small>Referensi simulasi: MWC-MOCK-20261109-001</small>
                    </div>
                    <div class="payment-cart-result-actions">
                        <a href="#payment-cart-channel-options" class="button button-primary">Pilih Kanal Lain</a>
                        <a href="{{ route('finance.bills.index') }}" class="button button-secondary">Kembali ke Tagihan</a>
                    </div>
                </div>
            </section>
            @endif
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
