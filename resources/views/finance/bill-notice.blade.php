<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Tagihan {{ $student->nis }} - {{ $student->name }}</title>
    <style>
        :root {
            --primary: #004528;
            --primary-soft: #f3fbf6;
            --text: #020617;
            --muted: #707971;
            --border: #d1d5db;
            --paper: #ffffff;
            --canvas: #f5f7f4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000000;
            background: #f5f7f4;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .notice-actions {
            width: min(165mm, calc(100% - 24px));
            margin: 16px auto 10px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .notice-actions a,
        .notice-actions button {
            min-width: 88px;
            min-height: 40px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .notice-actions .primary {
            color: #ffffff;
            background: #004528;
            border-color: #004528;
        }

        .notice-page {
            width: 165mm;
            min-height: 210mm;
            margin: 0 auto 14mm;
            padding: 8mm 9mm 7mm;
            display: block;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .10);
        }

        .notice-header {
            padding-bottom: 2.4mm;
            border-bottom: 2px solid #004528;
            text-align: left;
        }

        .notice-header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .notice-header-logo-cell {
            width: 16mm;
            vertical-align: middle;
        }

        .notice-header-spacer-cell {
            width: 0;
            padding: 0;
            vertical-align: middle;
        }

        .notice-header-brand-cell {
            vertical-align: middle;
            text-align: left;
        }

        .notice-logo {
            width: 13mm;
            height: 13mm;
            object-fit: contain;
        }

        .notice-brand h1 {
            margin: 0;
            color: #000000;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .notice-brand p {
            margin: 1mm 0 0;
            color: #000000;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.25;
        }

        .notice-brand .notice-address {
            white-space: nowrap;
        }

        .notice-title {
            margin: 3mm 0 4mm;
            text-align: center;
        }

        .notice-title h2 {
            display: inline-block;
            width: auto;
            max-width: 100%;
            margin: 0 auto;
            color: #000000;
            border-bottom: 1px solid #020617;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .notice-title p {
            margin: 1mm 0 0;
            color: #000000;
            font-size: 14px;
            font-weight: 500;
        }

        .notice-info {
            margin: 0 0 2.4mm;
            padding: 0;
            background: transparent;
            border: 0;
        }

        .notice-info-layout {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .notice-info-layout > tbody > tr > td {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }

        .notice-info-left {
            padding-right: 3mm !important;
        }

        .notice-info-right {
            padding-left: 3mm !important;
        }

        .notice-info-table {
            width: auto;
            border-collapse: collapse;
        }

        .notice-info-table td {
            padding: .55mm 0;
            border: 0;
            font-size: 14px;
            line-height: 1.25;
            vertical-align: top;
        }

        .notice-info-table td:first-child {
            width: 28mm;
            color: #000000;
            font-weight: 500;
            white-space: nowrap;
        }

        .notice-info-table td:nth-child(2) {
            width: 4mm;
            color: #000000;
            text-align: left;
        }

        .notice-info strong {
            color: #000000;
            font-weight: 700;
        }

        .notice-info strong.notice-status-outstanding {
            color: #dc2626;
        }

        .notice-info strong.notice-status-paid {
            color: #004528;
        }

        .notice-copy {
            margin: 0 0 2.5mm;
            color: #000000;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.45;
            text-align: justify;
        }

        .notice-table-wrap {
            min-width: 0;
        }

        table.notice-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .notice-table th,
        .notice-table td {
            border: 1px solid #9ca3af;
            padding: 1.05mm 1.15mm;
            color: #000000;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.2;
            vertical-align: middle;
        }

        .notice-table th {
            color: #000000;
            background: #ffffff;
            font-weight: 700;
            text-align: center;
        }

        .notice-table .col-no {
            width: 8mm;
            text-align: center;
        }

        .notice-table td.col-title {
            width: auto;
            text-align: left;
        }

        .notice-table th.col-title {
            width: auto;
            text-align: center;
        }

        .notice-table .col-year {
            width: 16mm;
            text-align: center;
        }

        .notice-table .col-amount {
            width: 24mm;
            text-align: right;
            white-space: nowrap;
        }

        .notice-table .col-months {
            width: 15mm;
            text-align: center;
        }

        .notice-table .col-total {
            width: 28mm;
            text-align: right;
            white-space: nowrap;
        }

        .notice-table th.col-no,
        .notice-table th.col-title,
        .notice-table th.col-year,
        .notice-table th.col-amount,
        .notice-table th.col-months,
        .notice-table th.col-total {
            text-align: center;
        }

        .notice-table .total-row td {
            color: #000000;
            background: #ffffff;
            font-weight: 700;
        }

        .notice-table .total-label {
            text-align: center;
        }

        .amount-words {
            margin: 2mm 0 0;
            color: #000000;
            font-size: 14px;
            font-weight: 400;
            font-style: italic;
            line-height: 1.35;
        }

        .amount-words strong {
            color: inherit;
            font-weight: 400;
            text-decoration: none;
        }

        .notice-footer {
            margin-top: 6mm;
            padding-top: 2mm;
            border-top: 0;
        }

        .notice-footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .notice-footer-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .notice-footer-table td:first-child {
            width: 56%;
        }

        .notice-footer-table td:last-child {
            width: 44%;
        }

        .notice-footer-left {
            padding-right: 3mm !important;
        }

        .notice-footer-right {
            padding-left: 3mm !important;
        }

        .signature {
            text-align: center;
            color: #000000;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.35;
        }

        .signature p {
            margin: 0;
        }

        .signature-space {
            height: 11mm;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .empty-row {
            height: 13mm;
            text-align: center;
            color: #000000;
        }

        .notice-pdf-mode {
            width: 165mm;
            min-height: 210mm;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .notice-pdf-mode .notice-page {
            width: 165mm;
            height: 210mm;
            min-height: 210mm;
            margin: 0;
            padding: 8mm 9mm 7mm;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
        }

        .notice-pdf-mode .notice-header-logo-cell {
            width: 16mm;
            text-align: left;
            vertical-align: middle;
        }

        .notice-pdf-mode .notice-header-spacer-cell {
            width: 0;
            padding: 0;
        }

        .notice-pdf-mode .notice-header-brand-cell {
            text-align: left;
            vertical-align: middle;
        }

        .notice-pdf-mode .notice-logo {
            width: 13mm;
            height: 13mm;
        }

        .notice-pdf-mode .notice-info-table {
            width: auto;
            border-collapse: collapse;
            table-layout: auto;
        }

        .notice-pdf-mode .notice-info-table td:first-child {
            width: 28mm;
        }

        .notice-pdf-mode .notice-info-table td:nth-child(2) {
            width: 4mm;
        }

        .notice-pdf-mode .notice-footer-table td:first-child {
            width: 56%;
        }

        .notice-pdf-mode .notice-footer-table td:last-child {
            width: 44%;
        }

        .notice-web-mode {
            background: #ffffff;
        }

        .notice-web-mode .notice-actions {
            width: min(1120px, calc(100% - 48px));
            margin: 24px auto 16px;
        }

        .notice-web-mode .notice-page {
            width: min(1120px, calc(100% - 48px));
            min-height: auto;
            margin: 0 auto 40px;
            padding: 24px;
            border-radius: 8px;
            box-shadow: none;
        }

        .notice-web-mode .notice-header {
            padding-bottom: 16px;
        }

        .notice-web-mode .notice-header-logo-cell {
            width: 72px;
        }

        .notice-web-mode .notice-header-spacer-cell {
            width: 0;
            padding: 0;
        }

        .notice-web-mode .notice-logo {
            width: 56px;
            height: 56px;
        }

        .notice-web-mode .notice-title {
            margin: 20px 0 30px;
        }

        .notice-web-mode .notice-info {
            margin-bottom: 18px;
        }

        .notice-web-mode .notice-info-left {
            padding-right: 24px !important;
        }

        .notice-web-mode .notice-info-right {
            padding-left: 24px !important;
        }

        .notice-web-mode .notice-info-table td {
            padding: 5px 0;
        }

        .notice-web-mode .notice-copy {
            margin-bottom: 18px;
        }

        .notice-web-mode .notice-table th,
        .notice-web-mode .notice-table td {
            padding: 10px 12px;
        }

        .notice-web-mode .notice-footer {
            margin-top: 32px;
            padding-top: 20px;
        }

        .notice-web-mode .signature-space {
            height: 56px;
        }

        body.notice-web-mode {
            min-height: 100vh;
            padding-top: 76px;
            background: #f5f7f4;
            overflow-x: auto;
        }

        .notice-web-mode .notice-actions {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 20;
            width: 100%;
            height: 56px;
            margin: 0;
            padding: 0 24px;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 14px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        }

        .notice-web-mode .notice-toolbar-title {
            min-width: 0;
            max-width: 420px;
            overflow: hidden;
            color: #020617;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .notice-web-mode .notice-toolbar-spacer {
            flex: 1 1 auto;
        }

        .notice-web-mode .notice-toolbar-meta,
        .notice-web-mode .notice-toolbar-zoom {
            min-height: 32px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
            background: #f5f7f4;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .notice-web-mode .notice-toolbar-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notice-web-mode .notice-actions a,
        .notice-web-mode .notice-actions button {
            min-width: 88px;
            min-height: 40px;
            padding: 0 14px;
            color: #0d5f36;
            background: #f3fbf6;
            border-color: #b9dcc7;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
            transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease, color .16s ease;
        }

        .notice-web-mode .notice-actions a:hover,
        .notice-web-mode .notice-actions button:hover {
            color: #004528;
            background: #e9f8ef;
            border-color: #157144;
            box-shadow: 0 10px 20px rgba(0, 69, 40, .16);
            transform: translateY(-2px);
        }

        .notice-web-mode .notice-actions .primary {
            color: #ffffff;
            background: #004528;
            border-color: #004528;
        }

        .notice-web-mode .notice-actions .primary:hover {
            color: #ffffff;
            background: #0d5f36;
            border-color: #0d5f36;
        }

        .notice-web-mode .notice-page {
            width: 165mm;
            min-height: 210mm;
            margin: 0 auto 32px;
            padding: 8mm 9mm 7mm;
            overflow: hidden;
            border: 0;
            border-radius: 0;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .28);
        }

        .notice-web-mode .notice-header {
            padding-bottom: 2.4mm;
        }

        .notice-web-mode .notice-header-logo-cell {
            width: 16mm;
        }

        .notice-web-mode .notice-header-spacer-cell {
            width: 0;
            padding: 0;
        }

        .notice-web-mode .notice-logo {
            width: 13mm;
            height: 13mm;
        }

        .notice-web-mode .notice-title {
            margin: 3mm 0 4mm;
        }

        .notice-web-mode .notice-info {
            margin-bottom: 2.4mm;
        }

        .notice-web-mode .notice-info-left {
            padding-right: 3mm !important;
        }

        .notice-web-mode .notice-info-right {
            padding-left: 3mm !important;
        }

        .notice-web-mode .notice-info-table td {
            padding: .55mm 0;
        }

        .notice-web-mode .notice-copy {
            margin-bottom: 2.5mm;
        }

        .notice-web-mode .notice-table th,
        .notice-web-mode .notice-table td {
            padding: 1.05mm 1.15mm;
        }

        .notice-web-mode .notice-footer {
            margin-top: 6mm;
            padding-top: 2mm;
        }

        .notice-web-mode .notice-footer-left {
            padding-right: 7mm !important;
        }

        .notice-web-mode .notice-footer-right {
            padding-left: 7mm !important;
        }

        .notice-web-mode .signature-space {
            height: 11mm;
        }

        @media screen and (max-width: 760px) {
            body {
                background: #ffffff;
            }

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
            }

            .notice-header-table,
            .notice-header-table tbody,
            .notice-header-table tr,
            .notice-header-table td,
            .notice-info-layout,
            .notice-info-layout tbody,
            .notice-info-layout tr,
            .notice-info-layout td,
            .notice-footer-table,
            .notice-footer-table tbody,
            .notice-footer-table tr,
            .notice-footer-table td {
                display: block;
                width: 100%;
            }

            .notice-header-logo-cell {
                width: 100%;
                text-align: center;
            }

            .notice-header-spacer-cell {
                display: none;
            }

            .notice-header-brand-cell {
                text-align: center;
            }

            .notice-info-left,
            .notice-info-right,
            .notice-footer-left,
            .notice-footer-right {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }

            .notice-info-right,
            .notice-footer-right {
                margin-top: 12px;
            }

            .notice-web-mode .notice-actions {
                width: 100%;
                margin: 0;
            }

            .notice-web-mode .notice-page {
                width: 100%;
                margin: 0;
                border-radius: 0;
            }

            .notice-web-mode .notice-title {
                margin: 18px 0 24px;
            }

            .notice-web-mode .notice-info-left,
            .notice-web-mode .notice-info-right,
            .notice-web-mode .notice-footer-left,
            .notice-web-mode .notice-footer-right {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }

            .notice-table-wrap {
                overflow-x: auto;
            }

            .notice-table {
                min-width: 680px;
            }
        }

        @media print {
            @page {
                size: 165mm 210mm;
                margin: 0;
            }

            html,
            body {
                width: 165mm;
                min-height: 210mm;
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            body.notice-web-mode {
                padding-top: 0;
                overflow: visible;
            }

            .notice-actions {
                display: none !important;
            }

            .notice-header-table,
            .notice-info-layout,
            .notice-footer-table {
                display: table !important;
                width: 100% !important;
                border-collapse: collapse;
                table-layout: fixed;
            }

            .notice-info-table {
                display: table !important;
                width: auto !important;
                border-collapse: collapse;
                table-layout: auto;
            }

            .notice-header-table tbody,
            .notice-info-layout tbody,
            .notice-info-table tbody,
            .notice-footer-table tbody {
                display: table-row-group !important;
            }

            .notice-header-table tr,
            .notice-info-layout tr,
            .notice-info-table tr,
            .notice-footer-table tr {
                display: table-row !important;
            }

            .notice-header-table td,
            .notice-info-layout td,
            .notice-info-table td,
            .notice-footer-table td {
                display: table-cell !important;
            }

            .notice-web-mode .notice-page,
            .notice-pdf-mode .notice-page,
            .notice-page {
                width: 165mm;
                height: 210mm;
                min-height: 210mm;
                margin: 0;
                padding: 8mm 9mm 7mm;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                page-break-after: avoid;
            }

            .notice-web-mode .notice-header-logo-cell,
            .notice-header-logo-cell {
                width: 16mm;
                text-align: left;
                vertical-align: middle;
            }

            .notice-web-mode .notice-header-spacer-cell,
            .notice-header-spacer-cell {
                width: 0;
                padding: 0;
                text-align: left;
                vertical-align: middle;
            }

            .notice-header-brand-cell {
                text-align: left !important;
                vertical-align: middle;
            }

            .notice-web-mode .notice-logo {
                width: 13mm;
                height: 13mm;
            }

            .notice-web-mode .notice-title {
                margin: 3mm 0 4mm;
            }

            .notice-web-mode .notice-info {
                margin-bottom: 2.4mm;
            }

            .notice-web-mode .notice-info-left,
            .notice-info-left {
                padding-right: 3mm !important;
            }

            .notice-web-mode .notice-info-right,
            .notice-info-right {
                padding-left: 3mm !important;
                margin-top: 0 !important;
            }

            .notice-web-mode .notice-info-table td,
            .notice-info-table td {
                padding: .55mm 0;
            }

            .notice-info-layout > tbody > tr > td {
                width: 50% !important;
                vertical-align: top;
            }

            .notice-info-table td:first-child {
                width: 28mm !important;
            }

            .notice-info-table td:nth-child(2) {
                width: 4mm !important;
                text-align: left;
            }

            .notice-web-mode .notice-copy {
                margin-bottom: 2.5mm;
            }

            .notice-web-mode .notice-table th,
            .notice-web-mode .notice-table td {
                padding: 1.05mm 1.15mm;
            }

            .notice-web-mode .notice-footer {
                margin-top: 6mm;
                padding-top: 2mm;
            }

            .notice-web-mode .notice-footer-left,
            .notice-footer-left {
                padding-right: 3mm !important;
            }

            .notice-web-mode .notice-footer-right,
            .notice-footer-right {
                padding-left: 3mm !important;
                margin-top: 0 !important;
            }

            .notice-footer-table td {
                vertical-align: top;
            }

            .notice-footer-table td:first-child {
                width: 56% !important;
            }

            .notice-footer-table td:last-child {
                width: 44% !important;
            }

            .notice-web-mode .signature-space,
            .signature-space {
                height: 11mm;
            }
        }
    </style>
</head>
<body class="{{ ($isPdf ?? false) ? 'notice-pdf-mode' : 'notice-web-mode' }}">
    @unless($isPdf ?? false)
        <div class="notice-actions">
            <div class="notice-toolbar-title">Detail Tagihan {{ $student->nis }} - {{ strtoupper($student->name) }}</div>
            <div class="notice-toolbar-spacer"></div>
            <div class="notice-toolbar-meta">1 / 1</div>
            <div class="notice-toolbar-zoom">100%</div>
            <div class="notice-toolbar-controls">
                <a href="{{ $backUrl }}">Kembali</a>
                <a href="{{ route('finance.payments.index', ['student_id' => $student->id, 'search' => $student->nis]) }}">Bayar</a>
                <a href="{{ $downloadUrl }}">Unduh</a>
                <button type="button" class="primary" onclick="window.print()">Cetak</button>
            </div>
        </div>
    @endunless

    <main class="notice-page">
        <header class="notice-header">
            <table class="notice-header-table">
                <tr>
                    <td class="notice-header-logo-cell">
                        <img class="notice-logo" src="{{ $logoSrc }}" alt="">
                    </td>
                    <td class="notice-header-brand-cell">
                        <div class="notice-brand">
                            <h1>Yayasan Mambaul Hikmah Waddawah</h1>
                            <p class="notice-address">Jl. Raya Teglawangi, RT. 13/05 Kec. Talang, Kab. Tegal, Jawa Tengah, 52193</p>
                            <p>Telp. 0813-9094-9994</p>
                        </div>
                    </td>
                    <td class="notice-header-spacer-cell"></td>
                </tr>
            </table>
        </header>

        <section class="notice-title">
            <h2>Surat Tagihan Administrasi</h2>
        </section>

        <section class="notice-info">
            <table class="notice-info-table">
                <tbody>
                    <tr>
                        <td>NIS</td>
                        <td>:</td>
                        <td>{{ $student->nis ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td>Nama Siswa</td>
                        <td>:</td>
                        <td><strong>{{ strtoupper($student->name) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Unit Pendidikan</td>
                        <td>:</td>
                        <td>{{ $student->schoolClass?->educationUnit?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Kelas</td>
                        <td>:</td>
                        <td>{{ $student->schoolClass?->name ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <p class="notice-copy">
            Disampaikan kepada orang tua/wali siswa bahwa berdasarkan data administrasi keuangan, masih terdapat kewajiban yang perlu diselesaikan sebagai berikut:
        </p>

        <div class="notice-table-wrap">
            <table class="notice-table">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-title">Uraian</th>
                        <th class="col-year">Tahun</th>
                        <th class="col-amount">Nominal</th>
                        <th class="col-months">Jml<br>Bulan</th>
                        <th class="col-total">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['lines'] as $line)
                        <tr>
                            <td class="col-no">{{ $loop->iteration }}</td>
                            <td class="col-title">{{ $line['title'] }}</td>
                            <td class="col-year">{{ $line['year'] }}</td>
                            <td class="col-amount">Rp. {{ number_format($line['unit_amount'], 0, ',', '.') }},-</td>
                            <td class="col-months">{{ $line['month_count'] }}</td>
                            <td class="col-total">Rp. {{ number_format($line['total'], 0, ',', '.') }},-</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">Tidak ada tagihan aktif.</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="5" class="total-label">Total Keseluruhan</td>
                        <td class="col-total">Rp. {{ number_format($statement['total'], 0, ',', '.') }},-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="amount-words">Terbilang: <strong>{{ $amountWords }}</strong></p>

        <footer class="notice-footer">
            <table class="notice-footer-table">
                <tr>
                    <td class="notice-footer-left">
                        <section class="signature">
                            <p>Mengetahui,</p>
                            <p>Mudiru Ma'had</p>
                            <div class="signature-space"></div>
                            <p class="signature-name">Dr. KH. Muhammad Sulton Barmawi, M.Pd.</p>
                        </section>
                    </td>
                    <td class="notice-footer-right">
                        <section class="signature">
                            <p>{{ config('receipt.city') }}, {{ $issuedDate }}</p>
                            <p>Petugas Keuangan</p>
                            <div class="signature-space"></div>
                            <p class="signature-name">{{ config('receipt.officer_name') }}</p>
                        </section>
                    </td>
                </tr>
            </table>
        </footer>
    </main>
    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
