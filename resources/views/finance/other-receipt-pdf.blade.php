<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @include('finance.partials.receipt-pdf-styles')
    <style>
        .payment-table .transaction-column { width: 20%; white-space: nowrap; }
        .payment-table .name-column { width: 48%; }
        .payment-table .year-column { width: 8%; }
        .payment-table .method-column { width: 9%; }
        .payment-table .amount-column { width: 15%; }
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
                <td class="transaction-column">{{ $payment->transaction_at->format('d-m-Y H.i') }} WIB</td>
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
            <td>Pendidikan Anak Tanggungjawab Orang Tua</td>
            <td class="right">Infaq yang sudah terbayar mohon diikhlaskan</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="signature-label">Hormat Kami</div><div class="signature-space"></div><div class="signature-name">{{ ($receiptSettings['finance_officer'] ?? null) ?: ($payment->operator_name ?: config('receipt.officer_name')) }}</div>@if(config('receipt.officer_phone'))<div>{{ config('receipt.officer_phone') }}</div>@endif</td>
            <td><div class="signature-label">Murid/Walinya</div><div class="signature-space"></div><div class="signature-name">{{ $payment->student?->name ?? '-' }}</div></td>
        </tr>
    </table>
</div>
</body>
</html>
