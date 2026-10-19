<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kwitansi {{ $receiptNumber }}</title>
    @include('finance.partials.receipt-screen-styles')
    <style>
        .transaction-time { width: 18%; }
        .month { width: 27%; }
        .year { width: 8%; text-align: center; }
        .month-count { width: 10%; text-align: center; }
        .monthly-rate { width: 11%; text-align: right; }
        .payment-method { width: 10%; }
        .amount { width: 16%; }
        th.monthly-rate { text-align: center; }
    </style>
</head>
<body>
@php
    $paymentMethodLabel = match ($payment->payment_method) {
        'Cash' => 'Tunai',
        'Transfer' => 'Transfer Bank',
        default => $payment->payment_method,
    };
    $sppRows = $payment->items
        ->sortBy(fn ($item) => ((int) $item->year * 100) + (int) $item->month)
        ->values()
        ->reduce(function ($groups, $item) {
            $year = (int) $item->year;
            $month = (int) $item->month;
            $monthlyRate = (int) ($item->original_amount ?? $item->total_amount ?? 0);
            $last = $groups->last();
            $lastItem = $last ? $last['items']->last() : null;
            $isNextMonth = $lastItem
                && $year === (int) $lastItem->year
                && $month === ((int) $lastItem->month + 1);

            if ($last && $isNextMonth && $last['monthly_rate'] === $monthlyRate) {
                $last['items']->push($item);
                $last['subtotal'] += (int) $item->paid_amount;
                $groups->put($groups->count() - 1, $last);
            } else {
                $groups->push([
                    'items' => collect([$item]),
                    'monthly_rate' => $monthlyRate,
                    'subtotal' => (int) $item->paid_amount,
                ]);
            }

            return $groups;
        }, collect())
        ->map(function (array $group) use ($months) {
            $first = $group['items']->first();
            $last = $group['items']->last();
            $firstMonth = (int) $first->month;
            $lastMonth = (int) $last->month;
            $year = (int) $first->year;
            $firstMonthName = $months[$firstMonth] ?? 'Bulan';
            $lastMonthName = $months[$lastMonth] ?? 'Bulan';

            return [
                'period' => $firstMonth === $lastMonth
                    ? $firstMonthName
                    : $firstMonthName.' - '.$lastMonthName,
                'year' => $year,
                'month_count' => $group['items']->count(),
                'monthly_rate' => $group['monthly_rate'],
                'subtotal' => $group['subtotal'],
            ];
        });
@endphp
<div class="receipt-actions">
    <button type="button" class="print" onclick="window.print()">Cetak</button>
    <a href="{{ route('finance.spp.receipt.download', $payment) }}">Unduh PDF</a>
    <button type="button" data-receipt-jpg>Unduh JPG</button>
    @if($otherSppStudents->count() === 1)
        <a class="primary" href="{{ route('finance.spp.create', ['student_id' => $otherSppStudents->first()->id]) }}">Bayar SPP Unit Lain</a>
    @elseif($otherSppStudents->count() > 1)
        <details class="next-unit">
            <summary class="primary">Bayar SPP Unit Lain</summary>
            <div>
                @foreach($otherSppStudents as $nextStudent)
                    <a href="{{ route('finance.spp.create', ['student_id' => $nextStudent->id]) }}">
                        {{ $nextStudent->schoolClass?->educationUnit?->code ?? '-' }} · {{ $nextStudent->schoolClass?->name ?? '-' }}
                    </a>
                @endforeach
            </div>
        </details>
    @endif
</div>
<div class="receipt-sheet-viewport" data-receipt-preview-viewport>
<div class="receipt-sheet-paper" data-receipt-paper>
<div class="receipt-print-wrapper">
<main class="page" data-receipt-page>
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
        <thead><tr><th class="transaction-time">Waktu Transaksi</th><th class="month">Bulan</th><th class="year">Tahun</th><th class="month-count">Jumlah Bulan</th><th class="monthly-rate number">Rp.</th><th class="payment-method">Bayar</th><th class="amount number">Jumlah</th></tr></thead>
        <tbody>
            @foreach($sppRows as $row)
                <tr>
                    <td class="transaction-time">{{ $payment->transaction_at->format('d-m-Y H:i:s') }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td class="year">{{ $row['year'] }}</td>
                    <td class="month-count">{{ $row['month_count'] }}</td>
                    <td class="monthly-rate number">{{ number_format($row['monthly_rate'], 0, ',', '.') }}</td>
                    <td class="payment-method">{{ $paymentMethodLabel }}</td>
                    <td class="number">{{ number_format($row['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="totals"><td colspan="6" class="totals-label">Keringanan (Rp)</td><td class="number">{{ number_format($payment->discount_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals grand-total"><td colspan="6" class="totals-label">Total Bayar (Rp)</td><td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals"><td colspan="6" class="totals-label">Sisa Tagihan s/d {{ $outstandingSummary['label'] }} (Rp)</td><td class="number">{{ number_format($outstandingSummary['remaining_amount'], 0, ',', '.') }}</td></tr>
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
document.querySelector('[data-receipt-jpg]')?.addEventListener('click', async () => {
    const page = document.querySelector('[data-receipt-page]');
    if (!page) return;
    const width = page.offsetWidth;
    const height = page.offsetHeight;
    const styles = Array.from(document.querySelectorAll('style')).map((style) => style.textContent ?? '').join('
');
    const markup = new XMLSerializer().serializeToString(page.cloneNode(true));
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width * 2}" height="${height * 2}" viewBox="0 0 ${width} ${height}"><foreignObject width="100%" height="100%"><div xmlns="http://www.w3.org/1999/xhtml"><style>${styles}</style>${markup}</div></foreignObject></svg>`;
    const image = new Image();
    const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
    image.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = width * 2;
        canvas.height = height * 2;
        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(url);
        const link = document.createElement('a');
        link.download = 'struk-spp-{{ $payment->student?->nis }}-{{ $payment->id }}.jpg';
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();
    };
    image.src = url;
});
</script>
</body>
</html>
