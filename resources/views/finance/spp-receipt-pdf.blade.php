<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @include('finance.partials.receipt-pdf-styles')
    <style>
        .payment-table .transaction-column { width: 18%; white-space: nowrap; text-align: center; }
        .payment-table .month-column { width: 27%; }
        .payment-table .year-column { width: 8%; }
        .payment-table .month-count-column { width: 10%; text-align: center; }
        .payment-table .rate-column { width: 11%; text-align: right; }
        .payment-table .method-column { width: 10%; }
        .payment-table .amount-column { width: 16%; }
        .payment-table th.rate-column { text-align: center; }
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
            <tr><th class="transaction-column">Waktu Transaksi</th><th class="month-column">Bulan</th><th class="year-column">Tahun</th><th class="month-count-column">Jumlah Bulan</th><th class="rate-column">Rp.</th><th class="method-column">Bayar</th><th class="amount-column">Jumlah</th></tr>
        </thead>
        <tbody>
            @foreach($sppRows as $row)
                <tr>
                    <td class="transaction-column">{{ $payment->transaction_at->format('d-m-Y H.i').' WIB' }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td class="year-column center">{{ $row['year'] }}</td>
                    <td class="month-count-column center">{{ $row['month_count'] }}</td>
                    <td class="rate-column number">{{ number_format($row['monthly_rate'], 0, ',', '.') }}</td>
                    <td class="method-column center">{{ $paymentMethodLabel }}</td>
                    <td class="number">{{ number_format($row['subtotal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr><td colspan="6" class="totals-label">Keringanan (Rp)</td><td class="number">{{ number_format($payment->discount_amount, 0, ',', '.') }}</td></tr>
            <tr class="grand-total"><td colspan="6" class="totals-label">Total Bayar (Rp)</td><td class="number">{{ number_format($payment->paid_amount, 0, ',', '.') }}</td></tr>
            <tr><td colspan="6" class="totals-label">Sisa Tagihan s/d {{ $outstandingSummary['label'] }} (Rp)</td><td class="number">{{ number_format($outstandingSummary['remaining_amount'], 0, ',', '.') }}</td></tr>
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
            <td><div class="signature-label">Hormat Kami</div><div class="signature-space"></div><div class="signature-name">{{ config('receipt.officer_name') }}</div>@if(config('receipt.officer_phone'))<div>{{ config('receipt.officer_phone') }}</div>@endif</td>
            <td><div class="signature-label">Murid/Walinya</div><div class="signature-space"></div><div class="signature-name">{{ $payment->student?->name ?? '-' }}</div></td>
        </tr>
    </table>
</div>
</body>
</html>
