<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kwitansi {{ $receiptNumber }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #222; background: #eef1f5; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.2; }
        .receipt-actions { width: min(21.5cm, calc(100% - 24px)); margin: 18px auto 10px; display: flex; justify-content: flex-end; gap: 8px; }
        .receipt-actions button, .receipt-actions a { min-height: 40px; padding: 0 16px; display: inline-flex; align-items: center; color: #0d5f36; background: white; border: 1px solid #cfd7e3; border-radius: 7px; cursor: pointer; font: inherit; font-weight: 700; text-decoration: none; }
        .receipt-actions .print { color: white; background: #0d5f36; border-color: #0d5f36; }
        .receipt { width: 21.5cm; height: 11cm; margin: 0 auto 1cm; padding: .42cm .62cm .25cm; overflow: hidden; background: white; border: 1px solid #d5d9df; box-shadow: 0 8px 30px #17203314; }
        .receipt-header { padding-bottom: 3px; display: grid; grid-template-columns: 58px 1fr auto; align-items: center; gap: 8px; border-bottom: 1.5px solid #555; }
        .receipt-logo { width: 52px; height: 52px; display: block; object-fit: contain; }
        .institution h1 { margin: 0 0 1px; font-size: 20px; line-height: 1.05; }
        .institution p { margin: 0; font-size: 12px; line-height: 1.15; }
        .keep-note { padding: 3px 13px; border: 1px solid #333; font-size: 12px; white-space: nowrap; }
        .receipt-title { margin: 2px 0 4px; text-align: center; }
        .receipt-title h2 { width: max-content; margin: 0 auto; border-bottom: 1px solid #333; font-size: 15px; line-height: 1.1; }
        .receipt-title p { margin: 0; font-size: 12px; }
        .student-info { margin-bottom: 4px; display: grid; grid-template-columns: .82fr 1.18fr; gap: 1px 25px; }
        .info-line { display: grid; grid-template-columns: 96px 8px 1fr; font-size: 12px; line-height: 1.25; }
        .info-line strong { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 4px; border: 1px solid #555; text-align: left; font-size: 11px; line-height: 1.15; }
        th { font-size: 11px; font-weight: 700; background: #fafafa; }
        .number { text-align: right; white-space: nowrap; }
        .totals td { border-top: 0; }
        .totals-label { text-align: right; }
        .grand-total { font-weight: 700; }
        .receipt-notes { margin-top: 3px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 10.5px; line-height: 1.15; }
        .receipt-notes div:last-child { text-align: right; }
        .receipt-meta { text-align: right; }
        .signatures { margin-top: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: center; font-size: 11px; }
        .signatures p { margin: 0; }
        .signature-space { height: 27px; }
        .signature-name { font-weight: 700; }
        .receipt-footer { margin-top: 5px; border-bottom: 1px dashed #555; }
        @media (max-width: 720px) {
            .receipt { width: 21.5cm; height: 11cm; padding: .42cm .62cm .25cm; }
        }
        @media print {
            @page { size: 21.5cm 11cm; margin: 0; }
            body { background: white; }
            .receipt-actions { display: none; }
            .receipt { width: 21.5cm; height: 11cm; margin: 0; padding: .42cm .62cm .25cm; border: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="receipt-actions">
    <a href="{{ route('finance.spp.index') }}">Kembali</a>
    <button type="button" class="print" onclick="window.print()">Cetak Struk</button>
</div>
<main class="receipt">
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
        <h2>Kwitansi Pembayaran SPP</h2>
        <p>No. {{ $receiptNumber }}</p>
    </section>

    <section class="student-info">
        <div class="info-line"><strong>NIS</strong><span>:</span><span>{{ $payment->student?->nis ?? '-' }}</span></div>
        <div class="info-line"><strong>Jenis Pendidikan</strong><span>:</span><span>{{ $payment->student?->schoolClass?->educationUnit?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Nama</strong><span>:</span><span>{{ $payment->student?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Kelas</strong><span>:</span><span>{{ $payment->student?->schoolClass?->name ?? '-' }}</span></div>
    </section>

    <table>
        <thead><tr><th>Waktu Transaksi</th><th>Bulan</th><th>Tahun</th><th>Cara Bayar</th><th class="number">Nominal Dibayar</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $payment->transaction_at->format('d-m-Y H.i') }} WIB</td>
                <td>{{ $payment->items->map(fn ($item) => $months[$item->month])->join(', ') }}</td>
                <td>{{ $payment->items->pluck('year')->unique()->join(', ') }}</td>
                <td>{{ $payment->payment_method }}</td>
                <td class="number">Rp {{ number_format($payment->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="totals"><td colspan="4" class="totals-label">Keringanan (Rp)</td><td class="number">Rp {{ number_format($payment->discount_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals"><td colspan="4" class="totals-label">Sisa Tagihan (Rp)</td><td class="number">Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals grand-total"><td colspan="4" class="totals-label">Total Bayar (Rp)</td><td class="number">Rp {{ number_format($payment->paid_amount, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="receipt-notes">
        <div>{{ config('receipt.footer_note') }}</div>
        <div><span class="receipt-meta">{{ config('receipt.city') }}, {{ now()->format('d-m-Y H.i') }} WIB</span><br>Infaq yang sudah terbayar mohon diikhlaskan</div>
    </div>

    <section class="signatures">
        <div><p>Hormat Kami</p><div class="signature-space"></div><p class="signature-name">{{ config('receipt.officer_name') }}</p>@if(config('receipt.officer_phone'))<p>{{ config('receipt.officer_phone') }}</p>@endif</div>
        <div><p>Murid/Walinya</p><div class="signature-space"></div><p class="signature-name">{{ $payment->student?->name ?? '-' }}</p></div>
    </section>
    <div class="receipt-footer"></div>
</main>
</body>
</html>
