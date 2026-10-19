<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kwitansi {{ $receiptNumber }}</title>
    @include('finance.partials.receipt-screen-styles')
    <style>
        .transaction-time { width: 20%; }
        .payment-name { width: 48%; }
        .academic-year { width: 8%; text-align: center; }
        .payment-method { width: 9%; }
        .amount { width: 15%; }
    </style>
</head>
<body>
<div class="receipt-actions">
    <a href="{{ route('finance.other.index', $backParams ?? []) }}">Kembali</a>
    <button type="button" class="print" onclick="window.print()">Cetak Struk</button>
</div>
<div class="receipt-sheet-viewport" data-receipt-preview-viewport>
<div class="receipt-sheet-paper" data-receipt-paper>
<div class="receipt-print-wrapper">
<main class="page">
    <header class="receipt-header">
        <img class="receipt-logo" src="{{ asset('images/logo-yayasan-mambaul-hikmah.png') }}" alt="Logo Yayasan Mambaul Hikmah">
        <div class="institution">
            <h1>{{ config('receipt.institution_name') }}</h1>
            <p>{{ config('receipt.address') }}</p>
            <p>{{ config('receipt.phone') }}</p>
        </div>
        <div class="keep-note">Harap Disimpan</div>
    </header>

    <section class="receipt-title">
        <h2>Kwitansi Pembayaran</h2>
        <p>No. {{ $receiptNumber }}</p>
    </section>

    <section class="student-info">
        <div class="info-line"><strong>NIS</strong><span>:</span><span>{{ $payment->student?->nis ?? '-' }}</span></div>
        <div class="info-line"><strong>Unit Pendidikan</strong><span>:</span><span>{{ $payment->student?->schoolClass?->educationUnit?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Nama</strong><span>:</span><span>{{ $payment->student?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Kelas</strong><span>:</span><span>{{ $payment->student?->schoolClass?->name ?? '-' }}</span></div>
    </section>

    <table>
        <thead><tr><th class="transaction-time">Waktu Transaksi</th><th class="payment-name">Kategori Pembayaran</th><th class="academic-year">Tahun Pelajaran</th><th class="payment-method">Bayar</th><th class="amount number">Nominal</th></tr></thead>
        <tbody>
            <tr>
                <td class="transaction-time">{{ $payment->transaction_at->format('d-m-Y H:i:s') }}</td>
                <td>{{ $payment->feeType?->name ?? 'Pembayaran Lainnya' }}@if($payment->items->isNotEmpty()) · {{ $payment->items->map(fn($item) => ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$item->month].' '.$item->year)->join(', ') }}@endif</td>
                <td>{{ $payment->feeType?->academicYear?->name ?? $payment->student?->academicYear?->name ?? '-' }}</td>
                <td>{{ $payment->payment_method }}</td>
                <td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="totals"><td colspan="4" class="totals-label">Keringanan (Rp)</td><td class="number">{{ number_format($payment->discount_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals grand-total"><td colspan="4" class="totals-label">Total Bayar (Rp)</td><td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="receipt-notes">
        <div>Pendidikan Anak Tanggungjawab Orang Tua</div>
        <div>{{ config('receipt.city') }}, {{ now()->format('d-m-Y H:i:s') }}<br>Infaq yang sudah terbayar mohon diikhlaskan</div>
    </div>

    <section class="signatures">
        <div><p><strong>Hormat Kami</strong></p><div class="signature-space"></div><p class="signature-name">{{ ($receiptSettings['finance_officer'] ?? null) ?: ($payment->operator_name ?: config('receipt.officer_name')) }}</p>@if(config('receipt.officer_phone'))<p>{{ config('receipt.officer_phone') }}</p>@endif</div>
        <div><p><strong>Murid/Walinya</strong></p><div class="signature-space"></div><p class="signature-name">{{ $payment->student?->name ?? '-' }}</p></div>
    </section>
    <div class="receipt-footer"></div>
</main>
</div>
</div>
</div>
<script>
const resizeReceiptPreview = () => {
    const viewport = document.querySelector('[data-receipt-preview-viewport]');
    const paper = viewport?.querySelector('[data-receipt-paper]');
    if (!viewport || !paper || window.matchMedia('print').matches) return;
    const scale = Math.min(1, viewport.clientWidth / paper.offsetWidth);
    paper.style.setProperty('--receipt-screen-scale', String(scale));
    viewport.style.height = `${paper.offsetHeight * scale}px`;
};
window.addEventListener('resize', resizeReceiptPreview);
resizeReceiptPreview();
window.addEventListener('load', () => window.print());
</script>
</body>
</html>
