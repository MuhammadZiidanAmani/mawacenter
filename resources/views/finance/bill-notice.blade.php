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
            --canvas: #ffffff;
            --notice-paper-width: 165mm;
            --notice-paper-height: 215mm;
            --notice-paper-padding-y: 7mm;
            --notice-paper-padding-x: 9mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000000;
            background: #ffffff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .notice-actions {
            width: min(var(--notice-paper-width), calc(100% - 24px));
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
            width: var(--notice-paper-width);
            min-height: var(--notice-paper-height);
            margin: 0 auto 14mm;
            padding: var(--notice-paper-padding-y) var(--notice-paper-padding-x);
            display: block;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .10);
            font-size: 12.5px;
        }

        .notice-header {
            padding-bottom: 1.6mm;
            border-bottom: 2px solid #004528;
            text-align: left;
        }

        .notice-header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .notice-header-logo-cell {
            width: 13mm;
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
            width: 11mm;
            height: 11mm;
            object-fit: contain;
        }

        .notice-brand h1 {
            margin: 0;
            color: #000000;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .notice-brand p {
            margin: .5mm 0 0;
            color: #000000;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.2;
        }

        .notice-brand .notice-address {
            white-space: nowrap;
        }

        .notice-title {
            margin: 2mm 0 2.2mm;
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
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .notice-title p {
            margin: .6mm 0 0;
            color: #000000;
            font-size: 12px;
            font-weight: 500;
        }

        .notice-info {
            margin: 0 0 1.8mm;
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
            padding-right: 2.5mm !important;
        }

        .notice-info-right {
            padding-left: 2.5mm !important;
        }

        .notice-info-table {
            width: auto;
            border-collapse: collapse;
        }

        .notice-info-table td {
            padding: .3mm 0;
            border: 0;
            font-size: 12.5px;
            line-height: 1.25;
            vertical-align: top;
        }

        .notice-info-table td:first-child {
            width: 23mm;
            color: #000000;
            font-weight: 500;
            white-space: nowrap;
        }

        .notice-info-table td:nth-child(2) {
            width: 3mm;
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
            margin: 0 0 1.8mm;
            color: #000000;
            font-size: 12.5px;
            font-weight: 400;
            line-height: 1.35;
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
            padding: .65mm .9mm;
            color: #000000;
            font-size: 12.5px;
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
            width: 7mm;
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
            width: 13mm;
            text-align: center;
        }

        .notice-table .col-amount {
            width: 24mm;
            text-align: right;
            white-space: nowrap;
        }

        .notice-table .col-months {
            width: 14mm;
            text-align: center;
        }

        .notice-table .col-total {
            width: 27mm;
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
            margin: 1.5mm 0 0;
            color: #000000;
            font-size: 12.5px;
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
            margin-top: 3.5mm;
            padding-top: 1mm;
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
            font-size: 12.5px;
            font-weight: 400;
            line-height: 1.25;
        }

        .signature p {
            margin: 0;
        }

        .signature-space {
            height: 10mm;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .empty-row {
            height: 9mm;
            text-align: center;
            color: #000000;
        }

        .notice-pdf-mode {
            width: var(--notice-paper-width);
            min-height: var(--notice-paper-height);
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .notice-pdf-mode .notice-page {
            width: var(--notice-paper-width);
            min-height: var(--notice-paper-height);
            margin: 0;
            padding: var(--notice-paper-padding-y) var(--notice-paper-padding-x);
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
        }

        .notice-pdf-mode .notice-header-logo-cell {
            width: 13mm;
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
            width: 11mm;
            height: 11mm;
        }

        .notice-pdf-mode .notice-info-table {
            width: auto;
            border-collapse: collapse;
            table-layout: auto;
        }

        .notice-pdf-mode .notice-info-table td:first-child {
            width: 23mm;
        }

        .notice-pdf-mode .notice-info-table td:nth-child(2) {
            width: 3mm;
        }

        .notice-pdf-mode .notice-footer-table td:first-child {
            width: 56%;
        }

        .notice-pdf-mode .notice-footer-table td:last-child {
            width: 44%;
        }

        body.notice-web-mode {
            min-height: 100vh;
            padding-top: 76px;
            background: #ffffff;
            overflow-x: hidden;
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
            box-shadow: none;
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
            color: #334155;
            background: #ffffff;
            border-color: #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            box-shadow: none;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease;
        }

        .notice-web-mode .notice-actions a:hover,
        .notice-web-mode .notice-actions button:hover {
            color: #004528;
            background: #f3fbf6;
            border-color: #004528;
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
            width: var(--notice-paper-width);
            min-height: var(--notice-paper-height);
            margin: 0 auto 32px;
            padding: var(--notice-paper-padding-y) var(--notice-paper-padding-x);
            overflow: hidden;
            border: 0;
            border-radius: 0;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .28);
        }

        .notice-web-mode .notice-header {
            padding-bottom: 1.6mm;
        }

        .notice-web-mode .notice-header-logo-cell {
            width: 13mm;
        }

        .notice-web-mode .notice-header-spacer-cell {
            width: 0;
            padding: 0;
        }

        .notice-web-mode .notice-logo {
            width: 11mm;
            height: 11mm;
        }

        .notice-web-mode .notice-title {
            margin: 2mm 0 2.2mm;
        }

        .notice-web-mode .notice-info {
            margin-bottom: 1.8mm;
        }

        .notice-web-mode .notice-info-left {
            padding-right: 2.5mm !important;
        }

        .notice-web-mode .notice-info-right {
            padding-left: 2.5mm !important;
        }

        .notice-web-mode .notice-info-table td {
            padding: .3mm 0;
        }

        .notice-web-mode .notice-copy {
            margin-bottom: 1.8mm;
        }

        .notice-web-mode .notice-table th,
        .notice-web-mode .notice-table td {
            padding: .65mm .9mm;
        }

        .notice-web-mode .notice-footer {
            margin-top: 3.5mm;
            padding-top: 1mm;
        }

        .notice-web-mode .notice-footer-left {
            padding-right: 3mm !important;
        }

        .notice-web-mode .notice-footer-right {
            padding-left: 3mm !important;
        }

        .notice-web-mode .signature-space {
            height: 10mm;
        }

        @media screen and (max-width: 760px) {
            html,
            body {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: hidden;
            }

            body.notice-web-mode {
                position: relative;
                padding-top: 0;
                overflow-x: hidden;
            }

            .notice-web-mode .notice-actions {
                position: sticky;
                top: 0;
                right: auto;
                left: auto;
                width: min(100%, 390px);
                max-width: 390px;
                min-width: 0;
                height: auto;
                min-height: 56px;
                margin: 0 auto 0 0;
                padding: 12px 16px;
                display: block;
                overflow: hidden;
            }

            .notice-web-mode .notice-toolbar-title {
                display: block;
                width: 100%;
                max-width: none;
                white-space: normal;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .notice-web-mode .notice-toolbar-spacer {
                display: none;
            }

            .notice-web-mode .notice-toolbar-controls {
                width: 100%;
                max-width: 100%;
                margin-top: 8px;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .notice-web-mode .notice-actions a,
            .notice-web-mode .notice-actions button {
                width: 100%;
                min-width: 0;
                min-height: 40px;
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

            .notice-brand h1,
            .notice-brand p,
            .notice-brand .notice-address {
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .notice-brand h1 {
                font-size: 15px;
                line-height: 1.25;
            }

            .notice-brand p {
                font-size: 14px;
                line-height: 1.35;
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

            .notice-web-mode .notice-page {
                display: flow-root;
                width: min(100%, 390px);
                max-width: 390px;
                min-width: 0;
                min-height: auto;
                margin: 0 auto 0 0;
                padding: 16px;
                overflow-x: hidden;
                overflow-y: visible;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .notice-header,
            .notice-title,
            .notice-info,
            .notice-copy,
            .amount-words,
            .notice-footer {
                max-width: 100%;
            }

            .notice-web-mode .notice-title {
                margin: 18px 0 16px;
            }

            .notice-web-mode .notice-title h2 {
                display: block;
                border-bottom: 0;
                font-size: 15px;
                line-height: 1.25;
                white-space: normal;
                overflow-wrap: anywhere;
                text-decoration: underline;
                text-underline-offset: 2px;
            }

            .notice-info-table {
                width: 100%;
            }

            .notice-info-table td:first-child {
                width: 104px;
            }

            .notice-info-table td:nth-child(2) {
                width: 14px;
            }

            .notice-info-table td:last-child {
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .notice-web-mode .notice-copy {
                text-align: left;
                overflow-wrap: anywhere;
            }

            .notice-web-mode .notice-info-left,
            .notice-web-mode .notice-info-right,
            .notice-web-mode .notice-footer-left,
            .notice-web-mode .notice-footer-right {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }

            .notice-table-wrap {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                contain: inline-size;
            }

            table.notice-table {
                width: 680px;
                min-width: 680px;
                max-width: none;
            }

            .notice-web-mode .notice-footer {
                margin-top: 20px;
                padding-top: 0;
            }

            .notice-web-mode .signature-space {
                height: 28px;
            }

            .signature-name {
                white-space: normal;
                overflow-wrap: anywhere;
            }
        }

        @media print {
            @page {
                size: 165mm 215mm;
                margin: 0;
            }

            html,
            body {
                width: var(--notice-paper-width);
                min-height: var(--notice-paper-height);
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
                width: var(--notice-paper-width);
                min-height: var(--notice-paper-height);
                margin: 0;
                padding: var(--notice-paper-padding-y) var(--notice-paper-padding-x);
                border: 0;
                border-radius: 0;
                box-shadow: none;
                page-break-after: avoid;
            }

            .notice-web-mode .notice-header-logo-cell,
            .notice-header-logo-cell {
                width: 13mm;
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
                width: 11mm;
                height: 11mm;
            }

            .notice-web-mode .notice-title {
                margin: 2mm 0 2.2mm;
            }

            .notice-web-mode .notice-info {
                margin-bottom: 1.8mm;
            }

            .notice-web-mode .notice-info-left,
            .notice-info-left {
                padding-right: 2.5mm !important;
            }

            .notice-web-mode .notice-info-right,
            .notice-info-right {
                padding-left: 2.5mm !important;
                margin-top: 0 !important;
            }

            .notice-web-mode .notice-info-table td,
            .notice-info-table td {
                padding: .3mm 0;
            }

            .notice-info-layout > tbody > tr > td {
                width: 50% !important;
                vertical-align: top;
            }

            .notice-info-table td:first-child {
                width: 23mm !important;
            }

            .notice-info-table td:nth-child(2) {
                width: 3mm !important;
                text-align: left;
            }

            .notice-web-mode .notice-copy {
                margin-bottom: 1.8mm;
            }

            .notice-web-mode .notice-table th,
            .notice-web-mode .notice-table td {
                padding: .65mm .9mm;
            }

            .notice-web-mode .notice-footer {
                margin-top: 3.5mm;
                padding-top: 1mm;
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
                height: 10mm;
            }
        }
    </style>
</head>
<body class="{{ ($isPdf ?? false) ? 'notice-pdf-mode' : 'notice-web-mode' }}">
    @unless($isPdf ?? false)
        <div class="notice-actions">
            <div class="notice-toolbar-title">Detail Tagihan {{ $student->nis }} - {{ strtoupper($student->name) }}</div>
            <div class="notice-toolbar-spacer"></div>
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
            <h2>Penertiban Administrasi Keuangan</h2>
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
                        <th class="col-amount">Rp.</th>
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
