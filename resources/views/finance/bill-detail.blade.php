<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Tagihan - {{ $student->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .bill-detail-web {
            padding: 28px 48px 48px;
        }

        .bill-detail-web-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .bill-detail-web-title h1 {
            margin: 0 0 6px;
            color: #020617;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .bill-detail-web-title p {
            margin: 0;
            color: #707971;
            font-size: 16px;
            font-weight: 400;
        }

        .bill-detail-web-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .bill-detail-web-actions .button {
            min-width: 112px;
            min-height: 40px;
            border-radius: 8px;
        }

        .bill-detail-web-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 16px;
            margin-bottom: 24px;
        }

        .bill-detail-web-panel {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            padding: 20px;
        }

        .bill-detail-web-panel h2 {
            margin: 0 0 16px;
            color: #020617;
            font-size: 20px;
            font-weight: 700;
        }

        .bill-detail-web-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 24px;
        }

        .bill-detail-web-field span {
            display: block;
            margin-bottom: 4px;
            color: #707971;
            font-size: 14px;
            font-weight: 500;
        }

        .bill-detail-web-field strong,
        .bill-detail-web-field b {
            color: #020617;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .bill-detail-web-status {
            color: #dc2626 !important;
        }

        .bill-detail-web-total {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 100%;
            background: #f3fbf6;
        }

        .bill-detail-web-total span {
            color: #707971;
            font-size: 14px;
            font-weight: 500;
        }

        .bill-detail-web-total strong {
            margin-top: 8px;
            color: #004528;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .bill-detail-web-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .bill-detail-web-table th,
        .bill-detail-web-table td {
            height: 40px;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #020617;
            font-size: 14px;
            font-weight: 400;
            vertical-align: middle;
        }

        .bill-detail-web-table th {
            background: #fbfdf8;
            color: #334155;
            font-weight: 700;
            text-align: center;
        }

        .bill-detail-web-table td {
            text-align: center;
        }

        .bill-detail-web-table td:nth-child(2) {
            text-align: left;
        }

        .bill-detail-web-table .is-money {
            color: #004528;
            white-space: nowrap;
        }

        .bill-detail-web-table tfoot td {
            background: #f3fbf6;
            color: #004528;
            font-weight: 700;
        }

        .bill-detail-web-words {
            margin: 14px 0 0;
            color: #334155;
            font-size: 14px;
            font-style: italic;
        }

        @media (max-width: 900px) {
            .bill-detail-web {
                padding: 20px 16px 32px;
            }

            .bill-detail-web-head,
            .bill-detail-web-summary {
                grid-template-columns: 1fr;
            }

            .bill-detail-web-head {
                display: grid;
            }

            .bill-detail-web-actions {
                justify-content: flex-start;
            }

            .bill-detail-web-grid {
                grid-template-columns: 1fr;
            }

            .bill-detail-web-table-wrap {
                overflow-x: auto;
            }

            .bill-detail-web-table {
                min-width: 760px;
            }
        }
    </style>
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'download' => '<path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19h16"/>',
        'print' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp. '.number_format($amount, 0, ',', '.').',-';
    $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'bills'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page bill-detail-web">
            <div class="bill-detail-web-head">
                <div class="bill-detail-web-title">
                    <h1>Detail Tagihan</h1>
                    <p>Rincian kewajiban administrasi siswa.</p>
                </div>
                <div class="bill-detail-web-actions">
                    <a href="{{ $backUrl }}" class="button button-secondary">Kembali</a>
                    <a href="{{ route('finance.payments.index', ['student_id' => $student->id, 'search' => $student->nis]) }}" class="button button-primary">Bayar</a>
                    <a href="{{ $downloadUrl }}" class="button button-primary">{!! $icon('download') !!} Unduh</a>
                    <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="button button-secondary">{!! $icon('print') !!} Cetak</a>
                </div>
            </div>

            <section class="bill-detail-web-summary">
                <article class="bill-detail-web-panel">
                    <h2>Data Siswa</h2>
                    <div class="bill-detail-web-grid">
                        <div class="bill-detail-web-field"><span>Nama Siswa</span><strong>{{ strtoupper($student->name) }}</strong></div>
                        <div class="bill-detail-web-field"><span>NIS</span><b>{{ $student->nis ?: '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Kelas</span><b>{{ $student->schoolClass?->name ?? '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Unit</span><b>{{ $student->schoolClass?->educationUnit?->name ?? '-' }}</b></div>
                        <div class="bill-detail-web-field"><span>Status</span><strong class="{{ $statement['total'] > 0 ? 'bill-detail-web-status' : '' }}">{{ $statement['total'] > 0 ? 'Belum Lunas' : 'Lunas' }}</strong></div>
                        <div class="bill-detail-web-field"><span>Tgl Cetak</span><b>{{ $issuedDate }}</b></div>
                    </div>
                </article>
                <article class="bill-detail-web-panel bill-detail-web-total">
                    <span>Total Tagihan</span>
                    <strong>{{ $rupiah($statement['total']) }}</strong>
                </article>
            </section>

            <section class="bill-detail-web-panel">
                <h2>Rincian Tagihan</h2>
                <div class="bill-detail-web-table-wrap">
                    <table class="bill-detail-web-table">
                        <thead>
                            <tr>
                                <th style="width: 64px">No</th>
                                <th>Uraian</th>
                                <th style="width: 110px">Tahun</th>
                                <th style="width: 150px">Nominal</th>
                                <th style="width: 100px">Jml Bulan</th>
                                <th style="width: 160px">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statement['lines'] as $line)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $line['title'] }}</td>
                                    <td>{{ $line['year'] }}</td>
                                    <td class="is-money">{{ $rupiah($line['unit_amount']) }}</td>
                                    <td>{{ $line['month_count'] }}</td>
                                    <td class="is-money">{{ $rupiah($line['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">Tidak ada tagihan aktif.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">Total Keseluruhan</td>
                                <td class="is-money">{{ $rupiah($statement['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="bill-detail-web-words">Terbilang: {{ $amountWords }}</p>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
