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
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; background: #eef1f5; font-family: Arial, sans-serif; font-size: 12.5px; line-height: 1.25; }
        .receipt-actions { width: min(210mm, calc(100% - 24px)); margin: 18px auto 10px; display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; }
        .receipt-actions button, .receipt-actions a { min-height: 40px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; color: #1f2f46; background: white; border: 1px solid #cfd7e3; border-radius: 8px; cursor: pointer; font: inherit; font-weight: 700; text-decoration: none; }
        .receipt-actions .print { color: #fff; background: #157144; border-color: #157144; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto 12mm; padding: 5mm 10mm 10mm; background: white; border: 1px solid #d5d9df; box-shadow: 0 8px 30px #17203314; }
        .receipt-header { padding: 0 1mm 1.3mm; display: grid; grid-template-columns: 14mm 1fr 36mm; align-items: center; gap: 2.5mm; border-bottom: .6mm solid #999; }
        .receipt-logo { width: 12mm; height: 12mm; display: block; object-fit: contain; }
        .institution h1 { margin: 0 0 .5mm; font-size: 18px; line-height: 1.05; }
        .institution p { margin: 0; font-size: 11.5px; line-height: 1.18; }
        .keep-note { padding: .9mm 3mm; border: 1px solid #333; font-size: 11.5px; line-height: 1.1; text-align: center; white-space: nowrap; }
        .receipt-title { margin: 1mm 0 1.5mm; text-align: center; }
        .receipt-title h2 { width: max-content; margin: 0 auto; border-bottom: 1px solid #333; font-size: 14px; line-height: 1.1; }
        .receipt-title p { margin: .4mm auto 0; max-width: 160mm; font-size: 11.5px; line-height: 1.25; word-break: break-word; }
        .student-info { margin: 0 0 2mm; display: grid; grid-template-columns: .88fr 1.12fr; gap: .7mm 7mm; }
        .info-line { display: grid; grid-template-columns: 30mm 3mm 1fr; align-items: start; font-size: 12.5px; line-height: 1.25; }
        .student-info .info-line:nth-child(odd) { grid-template-columns: 15mm 3mm 1fr; }
        .info-line strong { white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { min-height: 6.2mm; padding: .9mm 1.4mm; border: 1px solid #555; text-align: left; font-size: 12.5px; line-height: 1.22; vertical-align: middle; }
        th { height: 5.2mm; font-weight: 700; text-align: center; }
        .transaction-time { width: 20%; white-space: nowrap; text-align: center; }
        .payment-name { width: 32%; }
        .period { width: 24%; }
        .payment-method { width: 9%; text-align: center; }
        .amount { width: 15%; }
        .number { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
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
        @media (max-width: 820px) {
            .receipt-actions { justify-content: flex-start; }
            .page { width: calc(100% - 16px); min-height: 0; padding: 12px; overflow-x: auto; }
        }
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
    <a href="{{ route('finance.payments.index') }}">Transaksi Baru</a>
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
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
