<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $definition['title'] }}</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#111827; font-size:11px; }
        h1 { margin:0; font-size:18px; text-align:center; text-transform:uppercase; }
        .subtitle { margin:4px 0 14px; text-align:center; color:#374151; }
        .meta { margin-bottom:12px; line-height:1.5; }
        .summary { width:100%; border-collapse:collapse; margin-bottom:12px; }
        .summary td { border:1px solid #d1d5db; padding:6px 8px; }
        .summary strong { display:block; font-size:13px; color:#004528; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th, table.data td { border:1px solid #d1d5db; padding:5px 6px; vertical-align:top; }
        table.data th { background:#f3f4f6; text-align:center; font-weight:bold; }
        .money { text-align:right; white-space:nowrap; }
        .center { text-align:center; }
        .footer { margin-top:18px; text-align:right; color:#374151; }
    </style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $shownColumns = collect($columns)->reject(fn ($column) => ($column['type'] ?? null) === 'actions')->values();
@endphp
<h1>{{ $definition['title'] }}</h1>
<div class="subtitle">MA'WA CENTER</div>
<div class="meta">
    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
    Tahun Pelajaran Aktif: {{ $activeAcademicYear?->name ?? 'Belum diatur' }}
</div>
<table class="summary">
    <tr>
        @foreach($summaryCards as $card)
            <td>
                {{ $card['label'] }}
                <strong>{{ ($card['type'] ?? '') === 'money' ? $rupiah($card['value']) : $number($card['value']) }}</strong>
            </td>
        @endforeach
    </tr>
</table>
<table class="data">
    <thead>
        <tr>
            @foreach($shownColumns as $column)
                <th>{{ $column['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($shownColumns as $column)
                    <td class="{{ ($column['type'] ?? '') === 'money' ? 'money' : ($column['key'] === 'no' ? 'center' : '') }}">
                        @if($column['key'] === 'no')
                            {{ $loop->parent->iteration }}
                        @elseif(($column['type'] ?? '') === 'money')
                            {{ $rupiah($row[$column['key']] ?? 0) }}
                        @elseif(($column['type'] ?? '') === 'number')
                            {{ $number($row[$column['key']] ?? 0) }}
                        @else
                            {{ $row[$column['key']] ?? '-' }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ $shownColumns->count() }}" class="center">Belum ada data pada filter ini.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">MA'WA CENTER</div>
</body>
</html>
