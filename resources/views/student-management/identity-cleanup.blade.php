<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapikan Identitas - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .identity-reset-page {
            display: grid;
            gap:20px;
            min-height: calc(100vh - 64px);
            padding:24px 32px;
            background: #ffffff;
            color: #020617;
        }

        .identity-reset-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap:24px;
        }

        .identity-reset-heading {
            display: grid;
            gap:4px;
        }

        .identity-reset-heading h1 {
            margin:0;
            color: #020617;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .identity-reset-heading p {
            margin:0;
            color: #707971;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.35;
        }

        .identity-reset-filter {
            display: grid;
            grid-template-columns: minmax(260px, 285px) minmax(200px, 225px) minmax(200px, 225px) minmax(180px, 200px) minmax(300px, 1fr);
            grid-template-rows: auto auto;
            align-items: end;
            gap:16px;
            width: 100%;
            padding:16px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: none;
        }

        .identity-reset-filter-grid {
            display: contents;
        }

        .identity-reset-filter label {
            display: grid;
            gap:8px;
            min-width: 0;
            margin:0;
            color: #334155;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.25;
        }

        .identity-reset-filter-grid label:nth-child(1) { grid-column: 1; grid-row: 1; }
        .identity-reset-filter-grid label:nth-child(2) { grid-column: 2; grid-row: 1; }
        .identity-reset-filter-grid label:nth-child(3) { grid-column: 3; grid-row: 1; }
        .identity-reset-filter-grid label:nth-child(4) { grid-column: 4; grid-row: 1; }

        .identity-reset-search {
            position: relative;
            grid-column: 5;
            grid-row: 1;
        }

        .identity-reset-filter select,
        .identity-reset-filter input {
            box-sizing: border-box;
            width: 100%;
            height: 40px;
            min-height: 40px;
            margin:0;
            color: #020617;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: none;
            font-size: 14px;
            font-weight: 400;
            line-height: 40px;
            outline: 0;
        }

        .identity-reset-filter select {
            padding:0 12px;
        }

        .identity-reset-filter input {
            padding:0 12px 0 32px;
        }

        .identity-reset-search .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            width: 18px;
            height: 18px;
            color: #707971;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .identity-reset-actions {
            grid-column: 1;
            grid-row: 2;
            display: grid;
            grid-template-columns: 120px 120px;
            gap:8px;
            width: 248px;
        }

        .identity-reset-button,
        .identity-reset-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 120px;
            height: 40px;
            min-height: 40px;
            margin:0;
            padding:0 16px;
            border-radius: 8px;
            box-shadow: none;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
        }

        .identity-reset-button {
            color: #ffffff;
            background: #157144;
            border: 1px solid #157144;
        }

        .identity-reset-link {
            color: #334155;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }

        .identity-reset-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap:16px;
            width: 100%;
            color: #707971;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.35;
        }

        .identity-reset-length,
        .identity-reset-length label {
            display: inline-flex;
            align-items: center;
            gap:12px;
            margin:0;
            padding:0;
            color: #707971;
            font-size: 14px;
            font-weight: 400;
        }

        .identity-reset-length select {
            width: 88px;
            min-width: 88px;
            height: 40px;
            min-height: 40px;
            padding:0 12px;
            color: #020617;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .identity-reset-list {
            display: grid;
            gap:12px;
        }

        .identity-reset-card {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) 48px;
            align-items: center;
            gap:14px;
            min-height: 78px;
            padding:16px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .identity-reset-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: #157144;
            background: #e9f8ef;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .identity-reset-card-body {
            display: grid;
            gap:4px;
            min-width: 0;
        }

        .identity-reset-card-body strong {
            overflow: hidden;
            color: #004528;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .identity-reset-card-body span {
            color: #707971;
            font-size: 14px;
            line-height: 1.35;
        }

        .identity-reset-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            padding:0;
            color: #157144;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .identity-reset-action .icon {
            width: 20px;
            height: 20px;
        }

        .identity-reset-action.danger {
            color: #ef1f2d;
        }

        .identity-reset-empty {
            display: grid;
            place-items: center;
            gap:4px;
            min-height: 140px;
            padding:24px;
            color: #707971;
            text-align: center;
            background: #ffffff;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
        }

        .identity-reset-empty strong {
            color: #020617;
            font-size: 16px;
            font-weight: 700;
        }

        .identity-reset-pagination {
            display: flex;
            justify-content: center;
            margin-top:4px;
        }

        @media (width <= 1180px) {
            .identity-reset-page {
                padding:20px 24px;
            }

            .identity-reset-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .identity-reset-filter-grid label:nth-child(1),
            .identity-reset-filter-grid label:nth-child(2),
            .identity-reset-filter-grid label:nth-child(3),
            .identity-reset-filter-grid label:nth-child(4),
            .identity-reset-search,
            .identity-reset-actions {
                grid-column: auto;
                grid-row: auto;
            }
        }

        @media (width <= 640px) {
            .identity-reset-page {
                padding:16px;
            }

            .identity-reset-filter {
                grid-template-columns: 1fr;
                padding:16px;
            }

            .identity-reset-actions {
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .identity-reset-button,
            .identity-reset-link {
                width: 100%;
            }

            .identity-reset-card {
                grid-template-columns: 36px minmax(0, 1fr) 44px;
                gap:10px;
                padding:12px;
            }
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-filter .student-search-button::before,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-filter .student-search-button::after,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-filter .student-filter-reset::before,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-filter .student-filter-reset::after {
            content: none !important;
            display: none !important;
        }

        html body .identity-standard-page .student-reference-filter .student-search-button,
        html body .identity-standard-page .student-reference-filter .student-filter-reset {
            overflow: hidden !important;
            white-space: nowrap !important;
        }

        html body .identity-standard-page .identity-reset-list {
            gap:12px !important;
            margin:0 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page {
            display: grid !important;
            gap:16px !important;
            padding:24px 32px 32px !important;
            background: #ffffff !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page > .student-list-filter-card,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page > .identity-cleanup-table-card {
            width: min(100%, 1200px) !important;
            max-width: 1200px !important;
            margin:0 auto !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page > .student-list-filter-card {
            display: grid !important;
            gap:16px !important;
            padding:0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-header {
            margin:0 !important;
            padding:0 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-master-heading {
            display: grid !important;
            gap:4px !important;
            margin:0 !important;
            padding:0 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
            grid-template-columns: 160px 150px 180px minmax(220px, 1fr) max-content !important;
            grid-template-rows: auto !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1) {
            grid-column: 1 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2) {
            grid-column: 2 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(3) {
            grid-column: 3 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search {
            grid-column: 4 !important;
            grid-row: 1 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
            grid-column: 5 !important;
            grid-row: 1 !important;
            align-self: end !important;
            width: auto !important;
            min-width: 0 !important;
        }

        html body .identity-standard-page .identity-reset-card {
            grid-template-columns:40px minmax(0, 1fr) minmax(220px, auto) 48px !important;
        }

        html body .identity-standard-page .identity-reset-card-meta {
            display:flex !important;
            flex-wrap:wrap !important;
            justify-content:flex-end !important;
            gap:6px !important;
            min-width:0 !important;
        }

        html body .identity-standard-page .identity-reset-badge,
        html body .identity-standard-page .identity-reset-mini {
            display:inline-flex !important;
            align-items:center !important;
            min-height:28px !important;
            padding:0 10px !important;
            border-radius:8px !important;
            border:1px solid #d1d5db !important;
            color:#334155 !important;
            background:#ffffff !important;
            font-size:14px !important;
            line-height:1 !important;
            white-space:nowrap !important;
        }

        html body .identity-standard-page .identity-reset-badge.strong {
            color:#004528 !important;
            background:#e9f8ef !important;
            border-color:#b9e3ca !important;
            font-weight:700 !important;
        }

        html body .identity-standard-page .identity-reset-badge.medium {
            color:#725414 !important;
            background:#fff8e9 !important;
            border-color:#f0ddb0 !important;
            font-weight:700 !important;
        }

        html body .identity-standard-page .identity-reset-badge.check {
            color:#334155 !important;
            background:#f9fafb !important;
            font-weight:700 !important;
        }

        @media (width <= 760px) {
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page {
                padding:16px !important;
                overflow-x:hidden !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page > .student-list-filter-card,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page > .identity-cleanup-table-card {
                width: calc(100vw - 32px) !important;
                max-width: none !important;
                min-width:0 !important;
                overflow:hidden !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-master-heading,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-master-heading p {
                width:calc(100vw - 32px) !important;
                max-width:calc(100vw - 32px) !important;
                min-width:0 !important;
                overflow-wrap:break-word !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
                grid-template-columns: 1fr !important;
                width:calc(100vw - 32px) !important;
                max-width:calc(100vw - 32px) !important;
                min-width:0 !important;
                box-sizing:border-box !important;
                overflow:hidden !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(3),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                grid-column: auto !important;
                grid-row: auto !important;
            }

            html body .identity-standard-page .identity-reset-card {
                grid-template-columns:36px minmax(0, 1fr) 44px !important;
            }

            html body .identity-standard-page .identity-reset-card-meta {
                grid-column:2 / 3 !important;
                grid-row:2 !important;
                justify-content:flex-start !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: calc(100vw - 64px) !important;
                max-width:calc(100vw - 64px) !important;
                min-width:0 !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions .button {
                width:calc(100vw - 64px) !important;
                max-width:none !important;
                min-width:0 !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-card-count,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-card {
                width:100% !important;
                max-width:calc(100vw - 32px) !important;
                min-width:0 !important;
                box-sizing:border-box !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-card-count {
                display:grid !important;
                grid-template-columns:1fr !important;
                gap:10px !important;
                overflow:hidden !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .student-reference-card-count > span {
                max-width:100% !important;
                overflow-wrap:anywhere !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-card {
                grid-template-columns:36px minmax(0, 1fr) !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-card > form,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-card > .identity-reset-action {
                grid-column:2 / 3 !important;
                grid-row:3 !important;
                width:100% !important;
                max-width:100% !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-action {
                width:100% !important;
                height:40px !important;
            }
        }
    </style>
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'merge' => '<path d="M8 7h8m0 0-3-3m3 3-3 3M8 17h8m-8 0 3-3m-3 3 3 3M4 7h2m12 0h2M4 17h2m12 0h2"/>',
        'split' => '<path d="M6 4v6a4 4 0 0 0 4 4h1m7 6v-6a4 4 0 0 0-4-4h-1m-2 4 3-3-3-3m2 12-3-3 3-3"/>',
        'sort' => '<path d="m7 15 5 5 5-5M7 9l5-5 5 5"/>',
        'sort-up' => '<path d="m7 10 5-5 5 5M12 5v14"/>',
        'sort-down' => '<path d="M12 5v14m-5-5 5 5 5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $hasRows = $candidates->count() > 0;
    $totalRows = $candidates->total() ?? 0;
    $firstRow = $totalRows > 0 ? ($candidates->firstItem() ?? 1) : 0;
    $lastRow = $totalRows > 0 ? ($candidates->lastItem() ?? 0) : 0;
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'rapikan-identitas',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar" title="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main id="identity-standard-page" class="student-page identity-standard-page">
            @if (session('success'))
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal success-result">
                        <span class="result-icon">✓</span>
                        <strong>Sukses!</strong>
                        <p>{{ session('success') }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif
            @if (isset($errors) && $errors->any())
                <div class="result-modal-backdrop show" data-alert>
                    <div class="result-modal error-result">
                        <span class="result-icon">!</span>
                        <strong>Data belum bisa digabung</strong>
                        <p>{{ $errors->first() }}</p>
                        <button type="button" class="button button-primary" data-alert-close>OK</button>
                    </div>
                </div>
            @endif

            <section class="student-workspace student-list-filter-card">
                <div class="student-flat-header identity-cleanup-header">
                    <div class="student-master-heading">
                        <h1>Rapikan Identitas</h1>
                        <p>Hubungkan data siswa yang sebenarnya satu orang, tetapi terdaftar di beberapa unit atau NIS.</p>
                    </div>
                </div>

                <form id="identity-cleanup-filter" method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-filter-panel student-reference-filter student-fee-card-filter" data-student-filter-panel>
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    <div class="student-reference-filter-grid student-fee-card-filter-grid">
                        <label>
                            <span>Unit Pendidikan</span>
                            <select name="unit_id" data-student-filter-unit>
                                <option value="">Semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) $filters['unit_id'] === (string) $unit->id)>{{ $unit->code }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Kelas</span>
                            <select name="class_id" data-student-filter-class>
                                <option value="">Semua</option>
                                @foreach ($schoolClasses as $class)
                                    <option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Tahun Pelajaran</span>
                            <select name="year_id">
                                <option value="">Semua</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected((string) $filters['year_id'] === (string) $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @if($filters['search'] !== '')<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                        <button class="button student-fee-card-search-button fee-type-card-search-button" type="submit">Terapkan</button>
                        <a class="button student-fee-card-reset-button fee-type-card-reset-button" href="{{ route('student-management.identity-cleanup.index') }}">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card student-list-table-card identity-cleanup-table-card">
                <div class="student-reference-card-count student-management-table-toolbar">
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-reference-card-length">
                    @foreach(request()->except(['per_page', 'page', 'status', 'search']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>Tampilkan
                        <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah siswa yang ditampilkan">
                            @foreach([10, 25, 50, 100, 500] as $size)
                                <option value="{{ $size }}" @selected((string) $filters['per_page'] === (string) $size)>{{ $size }}</option>
                            @endforeach
                            <option value="all" @selected($filters['per_page'] === 'all')>Semua</option>
                        </select>
                        kandidat
                    </label>
                </form>
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="report-student-search-card student-management-search-card" role="search">
                    @foreach(request()->except(['search', 'page']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>
                        <span>Cari kandidat</span>
                        <span class="master-table-search-input">
                            {!! $icon('search') !!}
                            <input name="search" value="{{ $filters['search'] }}" placeholder="Nama, NIS, NISN, unit, kelas..." aria-label="Cari kandidat identitas">
                        </span>
                    </label>
                </form>
                </div>

                <div class="table-wrap identity-cleanup-table-wrap">
                    <table class="student-hybrid-table identity-cleanup-table">
                        <colgroup>
                            <col class="identity-col-no">
                            <col class="identity-col-name">
                            <col class="identity-col-reason">
                            <col class="identity-col-unit">
                            <col class="identity-col-confidence">
                            <col class="identity-col-count">
                            <col class="identity-col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="identity-center-column">No</th>
                                @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Nama Kandidat', 'icon' => $icon, 'thClass' => 'identity-name-column'])
                                @include('partials.master-sort-heading', ['column' => 'reason', 'label' => 'Alasan', 'icon' => $icon, 'thClass' => 'identity-reason-column'])
                                <th>Unit/Kelas</th>
                                @include('partials.master-sort-heading', ['column' => 'confidence', 'label' => 'Keyakinan', 'icon' => $icon, 'thClass' => 'identity-center-column'])
                                @include('partials.master-sort-heading', ['column' => 'count', 'label' => 'Jumlah Data', 'icon' => $icon, 'thClass' => 'identity-center-column'])
                                <th class="identity-center-column">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidates as $candidate)
                                @php
                                    $rowNumber = $candidates->firstItem() + $loop->index;
                                    $isLinkedRow = ($candidate['row_type'] ?? 'candidate') === 'linked';
                                    $confidenceClass = match ($candidate['confidence']) {
                                        'Kuat', 'Gabungan' => 'strong',
                                        'Sedang' => 'medium',
                                        default => 'check',
                                    };
                                    $unitSummary = $candidate['students']
                                        ->map(fn ($student) => $student->schoolClass?->educationUnit?->code)
                                        ->filter()
                                        ->unique()
                                        ->implode(' / ');
                                    $classSummary = $candidate['students']
                                        ->map(fn ($student) => $student->schoolClass?->name)
                                        ->filter()
                                        ->unique()
                                        ->take(3)
                                        ->implode(' / ');
                                    $detailUrl = $isLinkedRow ? null : route('student-management.identity-cleanup.show', [
                                        'candidateKey' => $candidate['key'],
                                        ...request()->query(),
                                    ]);
                                @endphp
                                <tr class="identity-cleanup-row">
                                    <td class="identity-cell-no" data-label="No">{{ $rowNumber }}</td>
                                    <td class="identity-cell-main" data-label="Nama Kandidat"><strong>{{ $candidate['name'] }}</strong></td>
                                    <td class="identity-cell-support" data-label="Alasan">{{ $candidate['reason'] }}</td>
                                    <td class="identity-cell-support" data-label="Unit/Kelas">
                                        <span>{{ $unitSummary ?: '-' }}</span>
                                        <small>{{ $classSummary ?: '-' }}</small>
                                    </td>
                                    <td class="identity-cell-center" data-label="Keyakinan"><span class="identity-reset-badge {{ $confidenceClass }}">{{ $candidate['confidence'] }}</span></td>
                                    <td class="identity-cell-center" data-label="Jumlah Data"><span class="identity-reset-mini">{{ $candidate['students']->count() }} data</span></td>
                                    <td class="identity-cell-action" data-label="Aksi">
                                        @if ($isLinkedRow)
                                            <form method="POST" action="{{ route('student-management.identity-cleanup.split') }}">
                                                @csrf
                                                <input type="hidden" name="identity_root_id" value="{{ $candidate['identity_root_id'] }}">
                                                <button class="identity-reset-action danger" type="submit" aria-label="Pisahkan identitas" title="Pisahkan identitas" onclick="return confirm('Pisahkan data identitas ini?')">{!! $icon('split') !!}</button>
                                            </form>
                                        @else
                                            <a class="identity-reset-action" href="{{ $detailUrl }}" aria-label="Tinjau kandidat identitas" title="Tinjau kandidat">{!! $icon('merge') !!}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="identity-cleanup-empty identity-reset-empty">
                                        <strong>Belum ada kandidat identitas pada filter ini.</strong>
                                        <span>Sistem belum menemukan nama, NISN, tanggal lahir, atau data orang tua yang perlu ditinjau.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @php
                    $identityCurrentPage = $candidates->currentPage();
                    $identityLastPage = $candidates->lastPage();
                    $identityStartPage = max(1, $identityCurrentPage - 1);
                    $identityEndPage = min($identityLastPage, $identityCurrentPage + 1);
                    if ($identityCurrentPage <= 2) {
                        $identityEndPage = min($identityLastPage, 3);
                    }
                    if ($identityCurrentPage >= $identityLastPage - 1) {
                        $identityStartPage = max(1, $identityLastPage - 2);
                    }
                    $identityPageUrl = fn ($page) => route('student-management.identity-cleanup.index', array_merge(request()->except('page'), ['page' => $page]));
                    $identityResultSummary = $totalRows > 0
                        ? 'Menampilkan '.number_format($firstRow, 0, ',', '.').'-'.number_format($lastRow, 0, ',', '.').' dari '.number_format($totalRows, 0, ',', '.').' kandidat'
                        : 'Menampilkan 0 dari 0 kandidat';
                @endphp
                <nav class="identity-reset-pagination student-report-pagination" aria-label="Navigasi halaman kandidat identitas">
                    <p>{{ $identityResultSummary }}</p>
                    @if($candidates->hasPages())
                        <div class="student-pagination-links">
                            @if($candidates->onFirstPage())
                                <span class="student-page-button is-disabled" aria-disabled="true">Sebelumnya</span>
                            @else
                                <a class="student-page-button" href="{{ $identityPageUrl($identityCurrentPage - 1) }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if($identityStartPage > 1)
                                <a class="student-page-button is-number" href="{{ $identityPageUrl(1) }}" aria-label="Halaman 1">1</a>
                                @if($identityStartPage > 2)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                            @endif

                            @for($page = $identityStartPage; $page <= $identityEndPage; $page++)
                                @if($page === $identityCurrentPage)
                                    <span class="student-page-button is-number is-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="student-page-button is-number" href="{{ $identityPageUrl($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($identityEndPage < $identityLastPage)
                                @if($identityEndPage < $identityLastPage - 1)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                                <a class="student-page-button is-number" href="{{ $identityPageUrl($identityLastPage) }}" aria-label="Halaman {{ $identityLastPage }}">{{ $identityLastPage }}</a>
                            @endif

                            @if($candidates->hasMorePages())
                                <a class="student-page-button" href="{{ $identityPageUrl($identityCurrentPage + 1) }}" rel="next">Berikutnya</a>
                            @else
                                <span class="student-page-button is-disabled" aria-disabled="true">Berikutnya</span>
                            @endif
                        </div>
                    @endif
                </nav>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
<style data-identity-hybrid-lock>
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
        box-sizing:border-box !important;
        display:grid !important;
        grid-template-columns:minmax(170px, 1fr) minmax(150px, 1fr) minmax(170px, 1fr) max-content !important;
        align-items:end !important;
        gap:12px !important;
        width:100% !important;
        min-width:0 !important;
        margin:0 !important;
        padding:16px !important;
        background:#ffffff !important;
        border:1px solid #d1d5db !important;
        border-radius:12px !important;
        box-shadow:none !important;
        overflow:visible !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid {
        display:contents !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label,
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions {
        margin:0 !important;
        padding:0 !important;
        min-width:0 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label {
        display:grid !important;
        gap:6px !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(1),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(2),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(3),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions {
        grid-row:1 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(1) { grid-column:1 !important; }
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(2) { grid-column:2 !important; }
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(3) { grid-column:3 !important; }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions {
        grid-column:4 !important;
        display:inline-flex !important;
        align-items:end !important;
        justify-content:flex-end !important;
        gap:8px !important;
        width:auto !important;
        min-width:max-content !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter :is(label, label > span) {
        color:#334155 !important;
        font-size:14px !important;
        font-weight:400 !important;
        line-height:1.25 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter select {
        box-sizing:border-box !important;
        width:100% !important;
        min-width:0 !important;
        height:40px !important;
        min-height:40px !important;
        margin:0 !important;
        color:#020617 !important;
        background:#ffffff !important;
        border:1px solid #d1d5db !important;
        border-radius:8px !important;
        box-shadow:none !important;
        font-size:14px !important;
        font-weight:400 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions .button {
        box-sizing:border-box !important;
        display:inline-flex !important;
        align-items:center !important;
        justify-content:center !important;
        width:96px !important;
        min-width:96px !important;
        max-width:96px !important;
        height:40px !important;
        min-height:40px !important;
        max-height:40px !important;
        margin:0 !important;
        padding:0 14px !important;
        border-radius:8px !important;
        box-shadow:none !important;
        transform:none !important;
        font-size:14px !important;
        font-weight:700 !important;
        line-height:1 !important;
        white-space:nowrap !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table-wrap {
        display:block !important;
        width:100% !important;
        overflow-x:auto !important;
        background:#ffffff !important;
        border:1px solid #d1d5db !important;
        border-radius:8px !important;
        box-shadow:none !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table {
        width:100% !important;
        min-width:960px !important;
        border-collapse:separate !important;
        border-spacing:0 !important;
        table-layout:fixed !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table th,
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table td {
        height:40px !important;
        padding:10px 12px !important;
        border:0 !important;
        border-bottom:1px solid #e5e7eb !important;
        color:#020617 !important;
        background:#ffffff !important;
        font-size:14px !important;
        font-weight:400 !important;
        line-height:1.35 !important;
        vertical-align:middle !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table th {
        color:#334155 !important;
        background:#fbfdf8 !important;
        font-weight:500 !important;
        text-align:center !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table .master-sort-link {
        display:inline-flex !important;
        align-items:center !important;
        justify-content:center !important;
        gap:6px !important;
        color:#334155 !important;
        font-size:14px !important;
        font-weight:500 !important;
        line-height:1.35 !important;
        text-decoration:none !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table .master-sort-indicator {
        display:inline-flex !important;
        width:16px !important;
        height:16px !important;
        align-items:center !important;
        justify-content:center !important;
        color:#707971 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-main strong {
        display:block !important;
        color:#020617 !important;
        font-size:14px !important;
        font-weight:700 !important;
        line-height:1.35 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-support,
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-support span,
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-support small {
        color:#404942 !important;
        font-size:14px !important;
        font-weight:400 !important;
        line-height:1.35 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-support small {
        display:block !important;
        margin-top:2px !important;
        color:#707971 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page :is(.identity-cell-no, .identity-cell-center, .identity-cell-action) {
        text-align:center !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-action {
        width:32px !important;
        height:32px !important;
    }

    @media (width <= 760px) {
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table-wrap {
            overflow:visible !important;
            background:transparent !important;
            border:0 !important;
            border-radius:0 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table tbody {
            display:block !important;
            min-width:0 !important;
            background:transparent !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table colgroup,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-table thead {
            display:none !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-row {
            display:grid !important;
            grid-template-columns:36px minmax(0, 1fr) 44px !important;
            gap:6px 10px !important;
            width:100% !important;
            margin:0 0 10px !important;
            padding:12px !important;
            background:#ffffff !important;
            border:1px solid #d1d5db !important;
            border-radius:8px !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cleanup-row td {
            display:block !important;
            width:auto !important;
            height:auto !important;
            min-height:0 !important;
            padding:0 !important;
            border:0 !important;
            background:transparent !important;
            text-align:left !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-no {
            grid-column:1 !important;
            grid-row:1 / span 2 !important;
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            width:36px !important;
            height:36px !important;
            color:#004528 !important;
            background:#e9f8ef !important;
            border-radius:8px !important;
            font-weight:700 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-main {
            grid-column:2 !important;
            grid-row:1 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-action {
            grid-column:3 !important;
            grid-row:1 / span 2 !important;
            display:flex !important;
            align-items:flex-start !important;
            justify-content:flex-end !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-support,
        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-center {
            grid-column:2 / 4 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-cell-action::before {
            content:none !important;
        }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page .identity-reset-action {
        width:40px !important;
        height:40px !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
        grid-template-columns:1fr !important;
        width:100% !important;
        max-width:100% !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(1),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(2),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-fee-card-filter-grid label:nth-child(3),
    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions {
        grid-column:auto !important;
        grid-row:auto !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions {
        display:grid !important;
        grid-template-columns:1fr 1fr !important;
        width:100% !important;
        min-width:0 !important;
    }

    html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter .student-filter-actions .button {
        width:100% !important;
        min-width:0 !important;
        max-width:none !important;
    }
}
</style>
</body>
</html>
