<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Tagihan {{ $student->nis }} - {{ $student->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111;
            background: #f3f4f6;
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            line-height: 1.25;
        }
        .notice-actions {
            width: min(210mm, calc(100% - 24px));
            margin: 18px auto 10px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .notice-actions a,
        .notice-actions button {
            min-width: 88px;
            height: 40px;
            min-height: 40px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #334155;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            cursor: pointer;
        }
        .notice-actions .primary {
            color: #ffffff;
            background: #004528;
            border-color: #004528;
        }
        .notice-actions .primary:hover {
            background: #0d5f36;
            border-color: #0d5f36;
        }
        .notice-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 12mm;
            padding: 14mm 12mm 18mm;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 8px 30px rgba(17, 24, 39, .10);
        }
        .notice-head {
            text-align: center;
            line-height: 1.25;
        }
        .notice-head p {
            margin: 0 0 2px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .notice-head h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .notice-intro {
            margin: 18px 18px 8px;
        }
        .notice-intro p {
            margin: 0 0 6px;
        }
        .student-lines {
            display: grid;
            gap: 5px;
            margin: 0 0 8px;
        }
        .student-line {
            display: grid;
            grid-template-columns: 46px 8px 1fr;
            align-items: start;
        }
        .student-line strong {
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        th,
        td {
            border: 1px solid #111;
            padding: 4px 5px;
            font-size: 13px;
            line-height: 1.2;
            vertical-align: middle;
        }
        th {
            background: #d9d9d9;
            text-align: center;
            font-weight: 700;
        }
        .col-no { width: 30px; text-align: center; }
        .col-year { width: 58px; text-align: center; }
        .col-amount { width: 74px; text-align: right; white-space: nowrap; }
        .col-months { width: 48px; text-align: center; }
        .col-total { width: 118px; text-align: right; white-space: nowrap; }
        .total-row td {
            background: #d9d9d9;
            font-weight: 700;
        }
        .total-label {
            text-align: center;
            text-transform: uppercase;
        }
        .amount-words {
            margin: 8px 18px 0;
            font-style: italic;
            text-decoration: underline;
        }
        .signature {
            width: 48%;
            margin: 38px 0 0 auto;
            text-align: center;
        }
        .signature p {
            margin: 0;
        }
        .signature-space {
            height: 54px;
        }
        .empty-row {
            height: 40px;
            text-align: center;
            color: #555;
        }
        @media (max-width: 760px) {
            body { background: #ffffff; }
            .notice-actions {
                width: 100%;
                margin: 0;
                padding: 12px;
                justify-content: stretch;
            }
            .notice-actions a,
            .notice-actions button {
                flex: 1 1 0;
                min-width: 0;
            }
            .notice-page {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 16px;
                border: 0;
                box-shadow: none;
                overflow-x: auto;
            }
            .notice-table-wrap {
                min-width: 620px;
            }
        }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #ffffff; }
            .notice-actions { display: none; }
            .notice-page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 14mm 12mm 18mm;
                border: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="notice-actions">
        <a href="{{ $backUrl }}">Kembali</a>
        <a href="{{ route('finance.payments.index', ['student_id' => $student->id, 'search' => $student->nis]) }}">Bayar</a>
        <button type="button" class="primary" onclick="window.print()">Cetak</button>
    </div>

    <main class="notice-page">
        <header class="notice-head">
            <p>Penertiban Administrasi Keuangan</p>
            <h1>Yayasan {{ strtoupper(config('receipt.institution_name')) }}</h1>
            <p>Teglawangi - Talang - Tegal</p>
        </header>

        <section class="notice-intro">
            <p>Disampaikan kepada orang tua/wali murid dari :</p>
            <div class="student-lines">
                <div class="student-line"><span>NAMA</span><span>:</span><strong>{{ strtoupper($student->name) }}</strong></div>
                <div class="student-line"><span>KELAS</span><span>:</span><strong>{{ strtoupper($student->schoolClass?->name ?? '-') }}</strong></div>
            </div>
            <p>Untuk segera <strong>MELUNASI</strong> kewajiban sebagai berikut :</p>
        </section>

        <div class="notice-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Uraian</th>
                        <th class="col-year">Tahun</th>
                        <th class="col-amount">Rp.</th>
                        <th class="col-months">Jml<br>Bulan</th>
                        <th class="col-total">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['lines'] as $line)
                        <tr>
                            <td class="col-no">{{ $loop->iteration }}</td>
                            <td>{{ $line['title'] }}</td>
                            <td class="col-year">{{ $line['year'] }}</td>
                            <td class="col-amount">{{ number_format($line['unit_amount'], 0, ',', '.') }}</td>
                            <td class="col-months">{{ $line['month_count'] }}</td>
                            <td class="col-total">Rp. {{ number_format($line['total'], 0, ',', '.') }},-</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">Tidak ada tagihan aktif.</td></tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="5" class="total-label">Total Keseluruhan</td>
                        <td class="col-total">Rp. {{ number_format($statement['total'], 0, ',', '.') }},-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="amount-words">Terbilang : {{ $amountWords }}</p>

        <section class="signature">
            <p>{{ config('receipt.city') }}, {{ $issuedDate }}</p>
            <p>Mudiru Ma'had</p>
            <div class="signature-space"></div>
            <p>{{ config('receipt.officer_name') }}</p>
        </section>
    </main>
</body>
</html>
