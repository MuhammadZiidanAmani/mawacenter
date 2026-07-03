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
            gap: 24px;
            min-height: calc(100vh - 64px);
            padding: 44px 48px 56px;
            background: #ffffff;
            color: #020617;
        }

        .identity-reset-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        .identity-reset-heading {
            display: grid;
            gap: 2px;
        }

        .identity-reset-heading h1 {
            margin: 0;
            color: #020617;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .identity-reset-heading p {
            margin: 0;
            color: #5b6f92;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.35;
        }

        .identity-reset-filter {
            display: grid;
            grid-template-columns: minmax(260px, 285px) minmax(200px, 225px) minmax(200px, 225px) minmax(180px, 200px) minmax(300px, 1fr);
            grid-template-rows: auto auto;
            align-items: end;
            gap: 18px;
            width: 100%;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            box-shadow: none;
        }

        .identity-reset-filter-grid {
            display: contents;
        }

        .identity-reset-filter label {
            display: grid;
            gap: 8px;
            min-width: 0;
            margin: 0;
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
            height: 58px;
            min-height: 58px;
            margin: 0;
            color: #020617;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: none;
            font-size: 14px;
            font-weight: 400;
            line-height: 58px;
            outline: 0;
        }

        .identity-reset-filter select {
            padding: 0 20px;
        }

        .identity-reset-filter input {
            padding: 0 16px 0 52px;
        }

        .identity-reset-search .icon {
            position: absolute;
            left: 20px;
            top: calc(50% + 13px);
            width: 22px;
            height: 22px;
            color: #707971;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .identity-reset-actions {
            grid-column: 1;
            grid-row: 2;
            display: grid;
            grid-template-columns: 135px 135px;
            gap: 14px;
            width: 285px;
        }

        .identity-reset-button,
        .identity-reset-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 135px;
            height: 60px;
            min-height: 60px;
            margin: 0;
            padding: 0 18px;
            border-radius: 10px;
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
            gap: 16px;
            width: 100%;
            color: #5b6f92;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.35;
        }

        .identity-reset-length,
        .identity-reset-length label {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding: 0;
            color: #5b6f92;
            font-size: 14px;
            font-weight: 400;
        }

        .identity-reset-length select {
            width: 118px;
            min-width: 118px;
            height: 52px;
            min-height: 52px;
            padding: 0 18px;
            color: #020617;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .identity-reset-list {
            display: grid;
            gap: 12px;
        }

        .identity-reset-card {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) 48px;
            align-items: center;
            gap: 14px;
            min-height: 78px;
            padding: 16px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 14px;
        }

        .identity-reset-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: #157144;
            background: #e6f4ea;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
        }

        .identity-reset-card-body {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .identity-reset-card-body strong {
            overflow: hidden;
            color: #004528;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .identity-reset-card-body span {
            color: #64748b;
            font-size: 14px;
            line-height: 1.35;
        }

        .identity-reset-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            padding: 0;
            color: #157144;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
        }

        .identity-reset-action .icon {
            width: 20px;
            height: 20px;
        }

        .identity-reset-action.danger {
            color: #e11d48;
        }

        .identity-reset-empty {
            display: grid;
            place-items: center;
            gap: 4px;
            min-height: 140px;
            padding: 28px;
            color: #64748b;
            text-align: center;
            background: #ffffff;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
        }

        .identity-reset-empty strong {
            color: #020617;
            font-size: 16px;
            font-weight: 800;
        }

        .identity-reset-pagination {
            display: flex;
            justify-content: center;
            margin-top: 4px;
        }

        @media (width <= 1180px) {
            .identity-reset-page {
                padding: 32px 24px 44px;
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
                padding: 24px 18px 36px;
            }

            .identity-reset-filter {
                grid-template-columns: 1fr;
                padding: 18px;
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
                gap: 10px;
                padding: 14px;
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
            gap: 18px !important;
            margin: 0 !important;
        }

        html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
            grid-template-columns: 160px 150px 120px minmax(220px, 300px) max-content !important;
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

        @media (width <= 760px) {
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter {
                grid-template-columns: 1fr !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(1),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(2),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-card-filter-grid label:nth-child(3),
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-fee-filter-search,
            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                grid-column: auto !important;
                grid-row: auto !important;
            }

            html body .app-shell .main-panel main#identity-standard-page.identity-standard-page form#identity-cleanup-filter.student-filter-panel.student-reference-filter.student-fee-card-filter .student-filter-actions.student-fee-card-filter-actions.fee-type-card-filter-actions {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                width: 100% !important;
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
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $selectedYearId = $filters['year_id'] ?: $activeAcademicYear?->id;
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
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button" aria-label="Keluar">{!! $icon('logout') !!}</button>
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
                                <option value="">semua</option>
                                @foreach ($educationUnits as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) $filters['unit_id'] === (string) $unit->id)>{{ $unit->code }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Kelas</span>
                            <select name="class_id" data-student-filter-class>
                                <option value="">semua</option>
                                @foreach ($schoolClasses as $class)
                                    <option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Tahun Pelajaran</span>
                            <select name="year_id">
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected((string) $selectedYearId === (string) $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="student-reference-search student-fee-filter-search">
                        <span>Cari siswa</span>
                        {!! $icon('search') !!}
                        <input name="search" value="{{ $filters['search'] }}" placeholder="Nama atau NIS..." aria-label="Cari kandidat identitas">
                    </label>
                    <div class="student-filter-actions student-fee-card-filter-actions fee-type-card-filter-actions">
                        <button class="button student-fee-card-search-button fee-type-card-search-button" type="submit">Terapkan</button>
                        <a class="button student-fee-card-reset-button fee-type-card-reset-button" href="{{ route('student-management.identity-cleanup.index') }}">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card student-list-table-card identity-cleanup-table-card">
                <div class="student-reference-card-count">
                <form method="GET" action="{{ route('student-management.identity-cleanup.index') }}" class="student-reference-card-length">
                    @foreach(request()->except(['per_page', 'page', 'status']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label>Tampilkan
                        <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah siswa yang ditampilkan">
                            @foreach([10, 25, 50, 100, 500] as $size)
                                <option value="{{ $size }}" @selected((string) $filters['per_page'] === (string) $size)>{{ $size }}</option>
                            @endforeach
                            <option value="all" @selected($filters['per_page'] === 'all')>All</option>
                        </select>
                        siswa
                    </label>
                </form>
                <span>
                    {{ $totalRows > 0 ? 'Menampilkan '.number_format($firstRow, 0, ',', '.').'-'.number_format($lastRow, 0, ',', '.').' dari '.number_format($totalRows, 0, ',', '.').' siswa' : 'Menampilkan 0 dari 0 siswa' }}
                </span>
                </div>

                <section class="identity-reset-list">
                @if ($hasRows)
                    @foreach ($candidates as $candidate)
                        @php
                            $rowNumber = $candidates->firstItem() + $loop->index;
                            $isLinkedRow = ($candidate['row_type'] ?? 'candidate') === 'linked';
                            $detailUrl = $isLinkedRow ? null : route('student-management.identity-cleanup.show', [
                                'candidateKey' => $candidate['key'],
                                ...request()->query(),
                            ]);
                        @endphp
                        <article class="identity-reset-card">
                            <span class="identity-reset-number">{{ $rowNumber }}</span>
                            <div class="identity-reset-card-body">
                                <strong>{{ $candidate['name'] }}</strong>
                                <span>{{ $candidate['reason'] }}</span>
                            </div>
                            @if ($isLinkedRow)
                                <form method="POST" action="{{ route('student-management.identity-cleanup.split') }}">
                                    @csrf
                                    <input type="hidden" name="identity_root_id" value="{{ $candidate['identity_root_id'] }}">
                                    <button class="identity-reset-action danger" type="submit" aria-label="Pisah" title="Pisah" onclick="return confirm('Pisahkan data identitas ini?')">{!! $icon('split') !!}</button>
                                </form>
                            @else
                                <a class="identity-reset-action" href="{{ $detailUrl }}" aria-label="Gabung" title="Gabung">{!! $icon('merge') !!}</a>
                            @endif
                        </article>
                    @endforeach
                @else
                    <div class="identity-reset-empty">
                        <strong>Belum ada kandidat duplikat</strong>
                        <span>Sistem belum menemukan nama, NISN, tanggal lahir, atau data orang tua yang perlu digabung.</span>
                    </div>
                @endif
                </section>

            </section>
        </main>
    </div>
</div>
</body>
</html>
