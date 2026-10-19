<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $firstReceipt = $receipts->first();
        $firstPayment = $firstReceipt['payment'] ?? null;
        $firstStudent = $firstPayment?->student;
        $receiptNumbers = $receipts->pluck('receiptNumber')->filter()->values();
        $totalDiscount = $receipts->sum(fn ($receipt) => (int) ($receipt['payment']?->discount_amount ?? 0));
        $totalPaid = $receipts->sum(fn ($receipt) => (int) ($receipt['payment']?->paid_amount ?? 0));
        $operatorName = $receipts
            ->map(fn ($receipt) => $receipt['payment']?->operator_name)
            ->filter()
            ->first();
        $lineRows = $receipts->map(function ($receipt) use ($months) {
            $payment = $receipt['payment'];

            if (($receipt['type'] ?? null) === 'spp') {
                $periods = $payment->items
                    ->sortBy(fn ($item) => ((int) $item->year * 100) + (int) $item->month)
                    ->map(fn ($item) => ($months[$item->month] ?? 'Bulan').' '.$item->year)
                    ->join(', ');

                return [
                    'time' => $payment->transaction_at->format('d-m-Y H:i:s'),
                    'name' => 'SPP '.$payment->student?->schoolClass?->educationUnit?->code,
                    'period' => $periods ?: '-',
                    'method' => $payment->payment_method,
                    'amount' => (int) $payment->paid_amount,
                ];
            }

            $periods = $payment->items
                ->sortBy(fn ($item) => ((int) $item->year * 100) + (int) $item->month)
                ->map(fn ($item) => ($months[$item->month] ?? 'Bulan').' '.$item->year)
                ->join(', ');

            return [
                'time' => $payment->transaction_at->format('d-m-Y H:i:s'),
                'name' => $payment->feeType?->name ?? 'Pembayaran Lainnya',
                'period' => $periods ?: ($payment->feeType?->academicYear?->name ?? $payment->student?->academicYear?->name ?? '-'),
                'method' => $payment->payment_method,
                'amount' => (int) $payment->paid_amount,
            ];
        });
        $outstanding = $receipts
            ->map(fn ($receipt) => $receipt['outstandingSummary'] ?? null)
            ->filter()
            ->last();
    @endphp
    <title>Kwitansi {{ $receiptNumbers->join(' / ') }}</title>
    @include('finance.partials.receipt-screen-styles')
    <style>
        .transaction-time { width: 20%; }
        .payment-name { width: 32%; }
        .period { width: 24%; }
        .payment-method { width: 9%; }
        .amount { width: 15%; }
    </style>
</head>
<body>
<div class="receipt-actions">
    <a href="{{ route('finance.payments.index') }}">Pembayaran</a>
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
        <p>No. {{ $receiptNumbers->join(' / ') }}</p>
    </section>

    <section class="student-info">
        <div class="info-line"><strong>NIS</strong><span>:</span><span>{{ $firstStudent?->nis ?? '-' }}</span></div>
        <div class="info-line"><strong>Unit Pendidikan</strong><span>:</span><span>{{ $firstStudent?->schoolClass?->educationUnit?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Nama</strong><span>:</span><span>{{ $firstStudent?->name ?? '-' }}</span></div>
        <div class="info-line"><strong>Kelas</strong><span>:</span><span>{{ $firstStudent?->schoolClass?->name ?? '-' }}</span></div>
    </section>

    <table>
        <thead>
            <tr>
                <th class="transaction-time">Waktu Transaksi</th>
                <th class="payment-name">Pembayaran</th>
                <th class="period">Periode</th>
                <th class="payment-method">Bayar</th>
                <th class="amount number">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineRows as $row)
                <tr>
                    <td class="transaction-time">{{ $row['time'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td class="payment-method">{{ $row['method'] }}</td>
                    <td class="number">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="totals"><td colspan="4" class="totals-label">Keringanan (Rp)</td><td class="number">{{ number_format($totalDiscount, 0, ',', '.') }}</td></tr>
            <tr class="totals grand-total"><td colspan="4" class="totals-label">Total Bayar (Rp)</td><td class="number">{{ number_format($totalPaid, 0, ',', '.') }}</td></tr>
            @if($outstanding)
                <tr class="totals"><td colspan="4" class="totals-label">Sisa Tagihan s/d {{ $outstanding['label'] }} (Rp)</td><td class="number">{{ number_format($outstanding['remaining_amount'], 0, ',', '.') }}</td></tr>
            @endif
        </tbody>
    </table>

    <div class="receipt-notes">
        <div>Pendidikan Anak Tanggungjawab Orang Tua</div>
        <div>{{ config('receipt.city') }}, {{ now()->format('d-m-Y H:i:s') }}<br>Infaq yang sudah terbayar mohon diikhlaskan</div>
    </div>

    <section class="signatures">
        <div><p><strong>Hormat Kami</strong></p><div class="signature-space"></div><p class="signature-name">{{ ($receiptSettings['finance_officer'] ?? null) ?: ($operatorName ?: config('receipt.officer_name')) }}</p>@if(config('receipt.officer_phone'))<p>{{ config('receipt.officer_phone') }}</p>@endif</div>
        <div><p><strong>Murid/Walinya</strong></p><div class="signature-space"></div><p class="signature-name">{{ $firstStudent?->name ?? '-' }}</p></div>
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
