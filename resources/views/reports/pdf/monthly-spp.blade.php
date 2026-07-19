<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $definition['title'] }}</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#111827; font-size:9px; }
        h1 { margin:0; color:#004528; font-size:16px; text-align:center; font-weight:700; }
        .subtitle { margin:4px 0 10px; text-align:center; color:#66736d; font-size:9px; }
        .meta { width:100%; margin-bottom:10px; border-collapse:collapse; }
        .meta td { padding:3px 6px; color:#374151; border:0; }
        .meta .label { color:#66736d; width:72px; }
        table.data { width:100%; border-collapse:collapse; table-layout:fixed; }
        table.data th,
        table.data td { border:1px solid #d1d5db; padding:4px 5px; vertical-align:middle; word-wrap:break-word; }
        table.data th { background:#f8faf7; color:#1f2937; font-weight:700; text-align:center; }
        .page-break { page-break-before: always; }
        .center { text-align:center; }
        .right { text-align:right; }
        .footer { margin-top:12px; text-align:center; color:#66736d; font-size:8px; }
    </style>
</head>
<body>
@php
    $filterLabel = function (string $field) use ($filterFields) {
        $filter = collect($filterFields)->firstWhere('name', $field);
        $value = (string) ($filter['value'] ?? '');

        if ($value === '') {
            return 'Semua';
        }

        $options = $filter['options'] ?? [];
        $option = $options[$value] ?? $options[(int) $value] ?? null;

        return is_array($option) ? ($option['label'] ?? $value) : (string) ($option ?: $value);
    };
    $chunks = collect($rows)->values()->chunk(28)->values();
@endphp
<h1>Data Laporan SPP Perbulan</h1>
<div class="subtitle">MA'WA CENTER</div>
<table class="meta">
    <tr>
        <td class="label">Bulan</td>
        <td>{{ $filterLabel('month') }}</td>
        <td class="label">Tahun</td>
        <td>{{ $filterLabel('year') }}</td>
        <td class="label">Dicetak</td>
        <td>{{ now()->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td class="label">Unit</td>
        <td>{{ $filterLabel('unit_id') }}</td>
        <td class="label">Kelas</td>
        <td>{{ $filterLabel('class_id') }}</td>
        <td class="label">Status</td>
        <td>{{ $filterLabel('spp_status') }}</td>
    </tr>
</table>
@forelse($chunks as $chunkIndex => $chunk)
    @if($chunkIndex > 0)
        <div class="page-break"></div>
    @endif
    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:7%;">NIS</th>
                <th style="width:19%;">Nama</th>
                <th style="width:15%;">Jenis Pendidikan</th>
                <th style="width:11%;">Kelas</th>
                <th style="width:11%;">Petugas</th>
                <th style="width:8%;">Cara bayar</th>
                <th style="width:7%;">Bulan</th>
                <th style="width:5%;">Tahun</th>
                <th style="width:10%;">Waktu</th>
                <th style="width:8%;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $row)
                <tr>
                    <td class="center">{{ ($chunkIndex * 28) + $loop->iteration }}</td>
                    <td class="center">{{ $row['nis'] ?? '-' }}</td>
                    <td>{{ $row['student'] ?? '-' }}</td>
                    <td>{{ $row['unit_name'] ?? '-' }}</td>
                    <td>{{ $row['class'] ?? '-' }}</td>
                    <td>{{ $row['operator'] ?? '-' }}</td>
                    <td class="center">{{ mb_strtolower($row['method'] ?? '-', 'UTF-8') }}</td>
                    <td class="center">{{ mb_strtolower($row['month'] ?? '-', 'UTF-8') }}</td>
                    <td class="center">{{ $row['year'] ?? '-' }}</td>
                    <td class="center">{{ $row['payment_time'] ?? '-' }}</td>
                    <td class="right">{{ number_format((int) ($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <table class="data">
        <tr>
            <td class="center">Belum ada data pada filter ini.</td>
        </tr>
    </table>
@endforelse
<div class="footer">&copy; 2026 Ma'wa Center</div>
</body>
</html>
