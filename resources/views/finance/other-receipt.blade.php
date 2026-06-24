<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kwitansi {{ $receiptNumber }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; background: #eef1f5; font-family: Arial, sans-serif; font-size: 12.5px; line-height: 1.25; }
        .receipt-actions { width: min(210mm, calc(100% - 24px)); margin: 18px auto 10px; display: flex; justify-content: flex-end; gap: 8px; }
        .receipt-actions button, .receipt-actions a { min-height: 40px; padding: 0 16px; display: inline-flex; align-items: center; color: #0d5f36; background: white; border: 1px solid #cfd7e3; border-radius: 7px; cursor: pointer; font: inherit; font-weight: 700; text-decoration: none; }
        .receipt-actions .print { color: white; background: #0d5f36; border-color: #0d5f36; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto 12mm; padding: 5mm 10mm 10mm; background: white; border: 1px solid #d5d9df; box-shadow: 0 8px 30px #17203314; }
        .receipt-header { padding: 0 1mm 1.3mm; display: grid; grid-template-columns: 14mm 1fr 36mm; align-items: center; gap: 2.5mm; border-bottom: .6mm solid #999; }
        .receipt-logo { width: 12mm; height: 12mm; display: block; object-fit: contain; }
        .institution h1 { margin: 0 0 .5mm; font-size: 18px; line-height: 1.05; }
        .institution p { margin: 0; font-size: 11.5px; line-height: 1.18; }
        .keep-note { padding: .9mm 3mm; border: 1px solid #333; font-size: 11.5px; line-height: 1.1; text-align: center; white-space: nowrap; }
        .receipt-title { margin: 1mm 0 1.5mm; text-align: center; }
        .receipt-title h2 { width: max-content; margin: 0 auto; border-bottom: 1px solid #333; font-size: 14px; line-height: 1.1; }
        .receipt-title p { margin: .4mm 0 0; font-size: 11.5px; line-height: 1.15; }
        .student-info { margin: 0 0 2mm; display: grid; grid-template-columns: .88fr 1.12fr; gap: .7mm 7mm; }
        .info-line { display: grid; grid-template-columns: 30mm 3mm 1fr; align-items: start; font-size: 12.5px; line-height: 1.25; }
        .student-info .info-line:nth-child(odd) { grid-template-columns: 15mm 3mm 1fr; }
        .info-line strong { white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { height: 6.2mm; padding: .9mm 1.4mm; border: 1px solid #555; text-align: left; font-size: 12.5px; line-height: 1.22; vertical-align: middle; }
        th { height: 5.2mm; font-size: 12.5px; font-weight: 700; text-align: center; }
        th.number { text-align: center; }
        .transaction-time { width: 20%; white-space: nowrap; }
        .payment-name { width: 48%; }
        .academic-year { width: 8%; text-align: center; }
        .payment-method { width: 9%; text-align: center; }
        .amount { width: 15%; }
        .number { text-align: right; white-space: nowrap; }
        .totals td { height: 4.8mm; border-top: 0; }
        .totals-label { text-align: right; }
        .grand-total { font-weight: 700; }
        .receipt-notes { margin: 2mm 2mm 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8mm; font-size: 12.5px; line-height: 1.22; }
        .receipt-notes div:last-child { text-align: right; }
        .signatures { margin: 2.2mm 2mm 0; display: grid; grid-template-columns: 1fr 1fr; gap: 18mm; text-align: center; font-size: 12.5px; line-height: 1.22; }
        .signatures p { margin: 0; }
        .signature-space { height: 9mm; }
        .signature-name { font-weight: 700; }
        .receipt-footer { margin: 3mm 2mm 0; border-bottom: 1px dashed #333; }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: white; }
            .receipt-actions { display: none; }
            .page { width: 210mm; min-height: 297mm; margin: 0; padding: 5mm 10mm 10mm; border: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="receipt-actions">
    <a href="{{ route('finance.other.index', $backParams ?? []) }}">Kembali</a>
    <button type="button" class="print" onclick="window.print()">Cetak Struk</button>
</div>
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
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
