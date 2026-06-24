<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 0.7cm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #202020; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.2; }
        .receipt { width: 100%; border-bottom: 1px dashed #333; padding-bottom: 8px; }
        .header { width: 100%; border-collapse: collapse; border-bottom: 1.5px solid #555; }
        .header td { border: 0; padding: 0 0 4px; vertical-align: middle; }
        .logo-cell { width: 55px; }
        .logo { width: 48px; height: 48px; object-fit: contain; }
        .institution h1 { margin: 0 0 1px; font-size: 18px; line-height: 1; }
        .institution p { margin: 0; font-size: 12px; line-height: 1.15; }
        .keep-cell { width: 135px; text-align: right; }
        .keep-note { display: inline-block; padding: 3px 13px; border: 1px solid #333; font-size: 13px; }
        .title { margin: 2px 0 4px; text-align: center; }
        .title h2 { display: inline-block; margin: 0; border-bottom: 1px solid #333; font-size: 16px; line-height: 1.05; }
        .title p { margin: 0; font-size: 12px; }
        .student-info { width: 100%; margin-bottom: 4px; border-collapse: collapse; }
        .student-info td { padding: 1px 2px; border: 0; font-size: 12.5px; vertical-align: top; }
        .student-info .label { width: 9%; font-weight: bold; white-space: nowrap; }
        .student-info .separator { width: 1.5%; padding-right: 5px; text-align: center; }
        .student-info .value-left { width: 39.5%; }
        .student-info .label-right { width: 14%; font-weight: bold; white-space: nowrap; }
        .student-info .value-right { width: 34.5%; }
        .payment-table { width: 100%; border-collapse: collapse; }
        .payment-table th, .payment-table td { padding: 3px 4px 4px; border: 1px solid #555; font-size: 12.5px; line-height: 1.15; }
        .payment-table th { background: #f5f5f5; font-weight: bold; text-align: center; }
        .payment-table .transaction-column { width: 20%; }
        .payment-table .name-column { width: 40%; }
        .payment-table .year-column { width: 13%; }
        .payment-table .method-column { width: 12%; }
        .payment-table .amount-column { width: 15%; }
        .payment-table .number { text-align: right; white-space: nowrap; }
        .payment-table .totals-label { text-align: right; }
        .payment-table .grand-total { font-weight: bold; }
        .notes { width: 100%; margin-top: 4px; border-collapse: collapse; }
        .notes td { width: 50%; padding: 0 4px; border: 0; font-size: 12px; line-height: 1.15; vertical-align: top; }
        .notes .right { text-align: right; }
        .signatures { width: 100%; margin-top: 5px; border-collapse: collapse; }
        .signatures td { width: 50%; padding: 0 8px; border: 0; text-align: center; font-size: 12.5px; vertical-align: top; }
        .signature-label { font-weight: bold; }
        .signature-space { height: 48px; }
        .signature-name { font-weight: bold; }
    </style>
</head>
<body>
<div class="receipt">
    <table class="header">
        <tr>
            <td class="logo-cell"><img class="logo" src="{{ $logo }}" alt="Logo"></td>
            <td class="institution">
                <h1>{{ config('receipt.institution_name') }}</h1>
                <p>{{ config('receipt.address') }}</p>
                <p>{{ config('receipt.phone') }}</p>
            </td>
            <td class="keep-cell"><span class="keep-note">Harap Disimpan</span></td>
        </tr>
    </table>

    <div class="title">
        <h2>Kwitansi Pembayaran</h2>
        <p>No. {{ $receiptNumber }}</p>
    </div>

    <table class="student-info">
        <tr>
            <td class="label">NIS</td>
            <td class="separator">:</td>
            <td class="value-left">{{ $payment->student?->nis ?? '-' }}</td>
            <td class="label-right">Unit Pendidikan</td>
            <td class="separator">:</td>
            <td class="value-right">{{ $payment->student?->schoolClass?->educationUnit?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td class="value-left">{{ $payment->student?->name ?? '-' }}</td>
            <td class="label-right">Kelas</td>
            <td class="separator">:</td>
            <td class="value-right">{{ $payment->student?->schoolClass?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="payment-table">
        <thead>
            <tr><th class="transaction-column">Waktu Transaksi</th><th class="name-column">Kategori Pembayaran</th><th class="year-column">Tahun Pelajaran</th><th class="method-column">Bayar</th><th class="amount-column">Nominal</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->transaction_at->format('d-m-Y H.i') }} WIB</td>
                <td>{{ $payment->feeType?->name ?? 'Pembayaran Lainnya' }}@if($payment->items->isNotEmpty()) · {{ $payment->items->map(fn($item) => ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$item->month].' '.$item->year)->join(', ') }}@endif</td>
                <td>{{ $payment->feeType?->academicYear?->name ?? $payment->student?->academicYear?->name ?? '-' }}</td>
                <td>{{ $payment->payment_method }}</td>
                <td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr><td colspan="4" class="totals-label">Keringanan (Rp)</td><td class="number">{{ number_format($payment->discount_amount, 0, ',', '.') }}</td></tr>
            @if($payment->remaining_amount > 0)
                <tr><td colspan="4" class="totals-label">Sisa Tagihan (Rp)</td><td class="number">{{ number_format($payment->remaining_amount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="grand-total"><td colspan="4" class="totals-label">Total Bayar (Rp)</td><td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <table class="notes">
        <tr>
            <td></td>
            <td class="right">{{ config('receipt.city') }}, {{ now()->format('d-m-Y H.i') }} WIB</td>
        </tr>
        <tr>
            <td>{{ $receiptSettings['receipt_footer'] ?? 'Pendidikan Anak Tanggungjawab Orang Tua' }}</td>
            <td class="right">Infaq yang sudah terbayar mohon diikhlaskan</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="signature-label">Hormat Kami</div><div class="signature-space"></div><div class="signature-name">{{ ($receiptSettings['finance_officer'] ?? null) ?: config('receipt.officer_name') }}</div>@if(config('receipt.officer_phone'))<div>{{ config('receipt.officer_phone') }}</div>@endif</td>
            <td><div class="signature-label">Murid/Walinya</div><div class="signature-space"></div><div class="signature-name">{{ $payment->student?->name ?? '-' }}</div></td>
        </tr>
    </table>
</div>
</body>
</html>
