<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'import-preview' ? 'Preview Import Pembayaran' : ($mode === 'import-result' ? 'Import Pembayaran Selesai' : ($mode === 'import' ? 'Import Pembayaran' : ($mode === 'history' ? 'Riwayat Pembayaran' : 'Pembayaran'))) }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'payment',
        'activeReportMenu' => '',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        @php
            $topbarIcon = fn (string $path) => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
        @endphp
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">☰</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi">{!! $topbarIcon('<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path>') !!}<span></span></button>
            @include('partials.logout-button', ['icon' => $topbarIcon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path>')])
        </header>

        <main @class(['payment-hub-page', 'payment-import-page' => in_array($mode, ['import', 'import-preview', 'import-result'], true), 'payment-import-preview-page' => $mode === 'import-preview', 'payment-import-result-page' => $mode === 'import-result', 'payment-transaction-page' => $mode === 'payment', 'student-page payment-flat-page' => in_array($mode, ['payment', 'history'], true)])>
            <section @class(['payment-hub-heading payment-import-page-heading' => in_array($mode, ['import', 'import-preview', 'import-result'], true), 'student-workspace payment-transaction-workspace' => $mode === 'payment', 'student-workspace payment-history-workspace' => $mode === 'history'])>
                @php
                    $icon = function (string $name) {
                        return match ($name) {
                            'search' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>',
                            'x' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>',
                            'check' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m20 6-11 11-5-5"></path></svg>',
                            'check-circle' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>',
                            'upload' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 8 5-5 5 5"></path><path d="M5 19h14"></path></svg>',
                            'chart' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"></path><path d="M4 19h16"></path><path d="M8 16v-5"></path><path d="M12 16V8"></path><path d="M16 16v-7"></path></svg>',
                            'file' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l4 4v16H6z"></path><path d="M14 2v5h5"></path><path d="M9 13h6"></path><path d="M9 17h6"></path></svg>',
                            'arrow-left' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path><path d="M9 12h10"></path></svg>',
                            'arrow-right' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path><path d="M5 12h10"></path></svg>',
                            'copy' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
                            'receipt' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path></svg>',
                            'printer' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v8H6z"></path><path d="M18 13h.01"></path></svg>',
                            'download' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>',
                            'trash' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 15H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
                            'chevron-down' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>',
                            default => '',
                        };
                    };
                @endphp
                @if($mode === 'payment')
                    @php
                        $selectedStudentId = (int) ($selectedStudentId ?? 0);
                        $selectedRegistrationId = (int) ($selectedRegistrationId ?? $selectedStudentId);
                        $selectedRegistrations = $selectedRegistrationId
                            ? $people->first(fn ($registrations) => $registrations->contains('id', $selectedRegistrationId))
                            : ($people->count() === 1 ? $people->first() : null);
                        $selectedIdentity = $selectedRegistrations
                            ? ($selectedRegistrations->firstWhere('identity_student_id', null) ?? $selectedRegistrations->first())
                            : null;
                        $activeRegistration = $activeRegistration
                            ?? ($selectedRegistrations?->firstWhere('id', $selectedRegistrationId) ?? $selectedRegistrations?->first());
                        $createdReceipts = collect(session('payment_receipts', []));
                        $paymentConfirmation = collect(session('payment_confirmation', []));
                        $successMessage = (string) session('success', '');
                        $isDeleteSuccess = str_contains(strtolower($successMessage), 'dihapus');
                        $canCreateCashPayment = auth()->user()?->hasPermission('payments.cash.create') ?? false;
                        $canImportPayments = auth()->user()?->hasPermission('payments.verify_transfer') ?? false;
                    @endphp
                    @if($isDeleteSuccess)
                        <div class="result-modal-backdrop show payment-delete-success-modal" data-alert data-payment-delete-success-modal role="dialog" aria-modal="true" aria-labelledby="payment-delete-success-title">
                            <div class="result-modal success-result payment-delete-success-card" role="document">
                                <span class="result-icon" aria-hidden="true">{!! $icon('check-circle') !!}</span>
                                <strong id="payment-delete-success-title">Transaksi Dihapus</strong>
                                <p>Transaksi pembayaran berhasil dihapus dan sisa tagihan telah diperbarui.</p>
                                <button type="button" class="button button-primary" data-alert-close>Tutup</button>
                            </div>
                        </div>
                    @elseif(session('success') && $createdReceipts->isNotEmpty() && $paymentConfirmation->isNotEmpty())
                        <div data-auto-receipts>
                            <script type="application/json" data-receipt-urls>@json($createdReceipts->pluck('receipt_url')->filter()->values())</script>
                            <script type="application/json" data-receipt-download-urls>@json($createdReceipts->pluck('download_url')->filter()->values())</script>
                            <div class="result-modal-backdrop payment-receipt-fallback-modal payment-success-modal show" data-auto-receipt-modal data-payment-success-modal role="dialog" aria-modal="true" aria-labelledby="payment-success-title">
                                <div class="result-modal success-result payment-success-card" role="document">
                                    <span class="result-icon" aria-hidden="true">{!! $icon('check-circle') !!}</span>
                                    <strong id="payment-success-title">Pembayaran Berhasil</strong>
                                    <p class="payment-success-message">Transaksi pembayaran berhasil disimpan dan tagihan telah diperbarui.</p>
                                    <div class="payment-success-actions">
                                        <button type="button" class="button button-primary" data-open-receipts>Cetak Struk</button>
                                        <button type="button" class="button button-secondary payment-success-close" data-payment-success-close>Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(session('success'))
                        <div class="result-modal-backdrop show" data-alert>
                            <div class="result-modal success-result">
                                <span class="result-icon">✓</span>
                                <strong>Pembayaran Berhasil</strong>
                                <p>{{ session('success') }}</p>
                                <button type="button" class="button button-primary" data-alert-close>Tutup</button>
                            </div>
                        </div>
                    @endif
                    @php
                        $selectedPreviewIdentity = $selectedRegistrations
                            ? ($selectedRegistrations->firstWhere('identity_student_id', null) ?? $selectedRegistrations->first())
                            : null;
                        $hasMultipleRegistrations = $selectedRegistrations && $selectedRegistrations->count() > 1;
                        $activeUnitLabel = $activeRegistration?->schoolClass?->educationUnit?->code
                            ?? $activeRegistration?->schoolClass?->educationUnit?->name
                            ?? 'Unit belum tersedia';
                        $activeClassLabel = $activeRegistration?->schoolClass?->name ?? 'Kelas belum tersedia';
                        $activeStudentMetadata = collect([
                            $activeRegistration?->nis,
                            $activeUnitLabel,
                            $activeClassLabel,
                        ])->filter()->values();
                        $usesSegmentedContextSwitcher = $hasMultipleRegistrations && $selectedRegistrations->count() <= 3;
                    @endphp

                    @if($selectedRegistrations)
                        <header class="payment-selected-page-head">
                            <a href="{{ route('finance.payments.index') }}" class="payment-selected-back" aria-label="Kembali ke daftar pembayaran" title="Kembali ke daftar pembayaran">
                                {!! $icon('arrow-left') !!}
                            </a>
                            <div class="payment-selected-page-copy">
                                <h1 data-payment-student-name>{{ $selectedPreviewIdentity?->name ?? '-' }}</h1>
                                <p class="payment-selected-student-meta">
                                    @foreach($activeStudentMetadata as $index => $metadata)
                                        @if($index > 0)<span aria-hidden="true">•</span>@endif
                                        <span>{{ $metadata }}</span>
                                    @endforeach
                                </p>
                            </div>
                            @if($hasMultipleRegistrations)
                                <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-unit-context-form" data-payment-context-form>
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <input type="hidden" name="student_id" value="{{ $selectedPreviewIdentity?->id }}">
                                    <span class="payment-unit-context-label" id="payment-unit-context-label">Unit Aktif</span>
                                    @if($usesSegmentedContextSwitcher)
                                    <div @class(['payment-context-segmented', 'is-three' => $selectedRegistrations->count() === 3]) role="radiogroup" aria-labelledby="payment-unit-context-label">
                                        @foreach($selectedRegistrations as $registration)
                                            @php
                                                $registrationUnitLabel = collect([
                                                    $registration->schoolClass?->educationUnit?->code ?? $registration->schoolClass?->educationUnit?->name,
                                                    $registration->schoolClass?->name,
                                                ])->filter()->join(' • ');
                                                $isActiveRegistration = (int) $registration->id === (int) $activeRegistration?->id;
                                            @endphp
                                            <label @class(['payment-context-option', 'is-active' => $isActiveRegistration])>
                                                <input
                                                    type="radio"
                                                    name="registration_id"
                                                    value="{{ $registration->id }}"
                                                    @checked($isActiveRegistration)
                                                    aria-label="{{ $registrationUnitLabel ?: 'Unit belum tersedia' }}"
                                                    data-payment-context-switch
                                                >
                                                <span class="payment-context-option-content">{{ $registrationUnitLabel ?: 'Unit belum tersedia' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @else
                                    <select id="payment-unit-context-select" class="payment-context-select" name="registration_id" data-payment-context-switch aria-labelledby="payment-unit-context-label">
                                        @foreach($selectedRegistrations as $registration)
                                            @php
                                                $registrationUnitLabel = collect([
                                                    $registration->schoolClass?->educationUnit?->code ?? $registration->schoolClass?->educationUnit?->name,
                                                    $registration->schoolClass?->name,
                                                ])->filter()->join(' • ');
                                            @endphp
                                            <option value="{{ $registration->id }}" @selected((int) $registration->id === (int) $activeRegistration?->id)>{{ $registrationUnitLabel ?: 'Unit belum tersedia' }}</option>
                                        @endforeach
                                    </select>
                                    @endif
                                </form>
                            @endif
                        </header>
                    @else
                        <div class="payment-prd-page-head">
                            <div>
                                <h1>Daftar Pembayaran Siswa</h1>
                                <p>Pilih siswa untuk melihat seluruh rincian tagihan dan memproses pembayaran.</p>
                            </div>
                            @if($canImportPayments)
                                <a href="{{ route('finance.payments.import') }}" class="button button-primary payment-import-action">{!! $icon('upload') !!} Import Excel</a>
                            @endif
                        </div>
                    @endif
                    <div @class(['payment-one-stop-layout', 'payment-prd-layout', 'is-student-selected' => $selectedRegistrations])>
                        @unless($selectedRegistrations)
                            <section class="payment-overview-panel" aria-label="Daftar pembayaran siswa">
                                <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-overview-form">
                                    <div class="payment-overview-filter">
                                    <label>
                                        <span>Unit Pendidikan</span>
                                        <select name="unit_id" aria-label="Filter unit pendidikan" data-payment-unit-filter data-selected-unit="{{ $selectedUnitId ?? '' }}">
                                            <option value="">Semua Unit</option>
                                            @foreach($educationUnits ?? [] as $unit)
                                                <option value="{{ $unit->id }}" @selected((int) ($selectedUnitId ?? 0) === $unit->id)>{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span>Kelas</span>
                                        <select name="class_id" aria-label="Filter kelas" data-payment-class-filter @disabled(! ($selectedUnitId ?? 0))>
                                            <option value="">{{ ($selectedUnitId ?? 0) ? 'Semua Kelas' : 'Pilih unit pendidikan terlebih dahulu' }}</option>
                                            @foreach($classes ?? [] as $class)
                                                <option
                                                    value="{{ $class->id }}"
                                                    data-payment-class-unit="{{ $class->education_unit_id }}"
                                                    @selected((int) ($selectedClassId ?? 0) === $class->id)
                                                    @if(($selectedUnitId ?? 0) && (int) $class->education_unit_id !== (int) $selectedUnitId) hidden disabled @endif
                                                >{{ collect([$class->educationUnit?->code, $class->name])->filter()->join(' - ') }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <div class="payment-overview-filter-actions">
                                        <button class="button button-primary" type="submit">Filter</button>
                                        <a class="button button-secondary" href="{{ route('finance.payments.index') }}">Reset</a>
                                    </div>
                                    </div>

                                    <div class="payment-overview-table-toolbar">
                                        <label class="payment-overview-per-page-control">
                                            <span>Tampilkan</span>
                                            <select name="per_page" aria-label="Jumlah siswa per halaman" data-payment-overview-per-page>
                                                @foreach([10, 25, 50, 100] as $perPage)
                                                    <option value="{{ $perPage }}" @selected((int) ($paymentOverviewPerPage ?? 10) === $perPage)>{{ $perPage }}</option>
                                                @endforeach
                                                <option value="all" @selected(($paymentOverviewPerPage ?? 10) === 'all')>All</option>
                                            </select>
                                        </label>
                                        <label class="payment-overview-search-control">
                                            <span>Cari Siswa (NIS / Nama / NISN)</span>
                                            <span class="payment-overview-search">
                                                <span aria-hidden="true">{!! $icon('search') !!}</span>
                                                <input type="search" name="search" value="{{ $search }}" placeholder="Ketik nama siswa atau NIS...">
                                            </span>
                                        </label>
                                    </div>

                                <section aria-label="Data pembayaran siswa">
                                    <div class="payment-overview-table-wrap">
                                        <div class="payment-overview-table-frame">
                                            <table class="payment-overview-table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Kelas</th>
                                                    <th>Total Tagihan</th>
                                                    <th>Status Pembayaran</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse(($paymentOverviewRows ?? collect()) as $row)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <span class="payment-overview-student">
                                                                <span>
                                                                    <strong>{{ $row['name'] }}</strong>
                                                                    <small>{{ $row['nis_unit'] }}</small>
                                                                </span>
                                                            </span>
                                                        </td>
                                                        <td>{{ $row['class'] }}</td>
                                                        <td>Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                                        <td>
                                                            <span @class(['payment-overview-status', 'is-paid' => $row['status'] === 'Lunas', 'is-running' => $row['status'] === 'Berjalan', 'is-overdue' => $row['status'] === 'Jatuh Tempo'])>{{ $row['status'] }}</span>
                                                        </td>
                                                        <td>
                                                            <a class="payment-overview-action" href="{{ route('finance.payments.index', array_filter(['search' => $row['name'], 'student_id' => $row['identity_id'], 'unit_id' => ($selectedUnitId ?? 0) ?: null, 'class_id' => ($selectedClassId ?? 0) ?: null, 'per_page' => $paymentOverviewPerPage ?? 10, 'page' => ($paymentOverviewRows ?? null)?->currentPage() > 1 ? $paymentOverviewRows->currentPage() : null])) }}" aria-label="Lihat detail tagihan {{ $row['name'] }}" title="Detail Tagihan">
                                                                {!! $icon('arrow-right') !!}
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6">
                                                            <div class="payment-overview-empty">
                                                                <strong>Siswa tidak ditemukan</strong>
                                                                <span>Periksa kembali kata kunci pencarian.</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>
                                @if(($paymentOverviewRows ?? collect())->total() > 0)
                                    <nav class="payment-overview-pagination" aria-label="Navigasi halaman pembayaran">
                                        <p>Menampilkan {{ number_format($paymentOverviewRows->firstItem(), 0, ',', '.') }}-{{ number_format($paymentOverviewRows->lastItem(), 0, ',', '.') }} dari {{ number_format($paymentOverviewRows->total(), 0, ',', '.') }} siswa</p>
                                        @if($paymentOverviewRows->hasPages())
                                            @php
                                                $currentPage = (int) $paymentOverviewRows->currentPage();
                                                $lastPage = (int) $paymentOverviewRows->lastPage();
                                                $pageStart = max(1, $currentPage - 1);
                                                $pageEnd = min($lastPage, $currentPage + 1);
                                                $pageItems = [];

                                                if ($pageStart > 1) {
                                                    $pageItems[1] = $paymentOverviewRows->url(1);
                                                    if ($pageStart > 2) $pageItems['ellipsis-start'] = null;
                                                }

                                                for ($page = $pageStart; $page <= $pageEnd; $page++) {
                                                    $pageItems[$page] = $paymentOverviewRows->url($page);
                                                }

                                                if ($pageEnd < $lastPage) {
                                                    if ($pageEnd < $lastPage - 1) $pageItems['ellipsis-end'] = null;
                                                    $pageItems[$lastPage] = $paymentOverviewRows->url($lastPage);
                                                }
                                            @endphp
                                            <div class="payment-overview-pagination-links">
                                                @if($paymentOverviewRows->onFirstPage())
                                                    <span class="payment-overview-page-button is-disabled" aria-disabled="true" title="Halaman sebelumnya">{!! $icon('arrow-left') !!}</span>
                                                @else
                                                    <a class="payment-overview-page-button" href="{{ $paymentOverviewRows->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" title="Halaman sebelumnya">{!! $icon('arrow-left') !!}</a>
                                                @endif

                                                @foreach($pageItems as $page => $url)
                                                    @if($url === null)
                                                        <span class="payment-overview-page-button is-ellipsis" aria-hidden="true">...</span>
                                                    @elseif((int) $page === $currentPage)
                                                        <span class="payment-overview-page-button is-active" aria-current="page">{{ $page }}</span>
                                                    @else
                                                        <a class="payment-overview-page-button" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                                    @endif
                                                @endforeach

                                                @if($paymentOverviewRows->hasMorePages())
                                                    <a class="payment-overview-page-button" href="{{ $paymentOverviewRows->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" title="Halaman berikutnya">{!! $icon('arrow-right') !!}</a>
                                                @else
                                                    <span class="payment-overview-page-button is-disabled" aria-disabled="true" title="Halaman berikutnya">{!! $icon('arrow-right') !!}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </nav>
                                @endif
                                </form>
                            </section>
                        @else
                        @if(! $selectedRegistrations)
                        <section @class(['payment-one-stop-main', 'payment-prd-search-panel', 'payment-selected-student-context' => $selectedRegistrations])>
                            @unless($selectedRegistrations)
                                <div class="payment-one-stop-heading payment-prd-panel-heading">
                                    <div>
                                        <h2>Cari Siswa</h2>
                                    </div>
                                </div>
                                <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-one-stop-search">
                                    <label>
                                        <span class="payment-one-stop-search-field">
                                            <span class="payment-one-stop-search-icon" aria-hidden="true">{!! $icon('search') !!}</span>
                                            <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN..." aria-label="Cari Siswa" autofocus required>
                                            @if($search !== '')
                                                <a href="{{ route('finance.payments.index') }}" class="payment-one-stop-search-reset" aria-label="Reset pencarian">{!! $icon('x') !!}</a>
                                            @endif
                                        </span>
                                    </label>
                                </form>
                            @endunless

                            @if($search !== '' && $people->isNotEmpty() && ! $selectedRegistrations)
                                <div class="payment-one-stop-student-list">
                                    @foreach($people as $registrations)
                                        @php
                                            $identity = $registrations->firstWhere('identity_student_id', null) ?? $registrations->first();
                                            $isSelected = $selectedRegistrations && $selectedRegistrations->contains('id', $identity->id);
                                            $unitSummary = $registrations
                                                ->map(fn ($student) => collect([
                                                    $student->schoolClass?->educationUnit?->code,
                                                    $student->schoolClass?->name,
                                                ])->filter()->join(' · '))
                                                ->filter()
                                                ->unique()
                                                ->join(' / ');
                                            $statusLabel = 'Aktif';
                                        @endphp
                                        <a
                                            href="{{ route('finance.payments.index', ['search' => $identity->name, 'student_id' => $identity->id]) }}"
                                            @class(['payment-one-stop-student-card', 'is-selected' => $isSelected])
                                        >
                                            <span class="payment-one-stop-student-copy">
                                                <strong>{{ $identity->name }}</strong>
                                                <small>{{ $identity->nis }}{{ $unitSummary ? ' · '.$unitSummary : '' }}</small>
                                            </span>
                                            <span class="payment-one-stop-unit-count">{{ $statusLabel }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                        @endif

                        @unless($selectedRegistrations)
                        <section class="payment-one-stop-side">
                        @endunless
                            @if($search !== '')
                                @if($people->isEmpty())
                                    <div class="payment-one-stop-empty-state">
                                        <div class="payment-one-stop-empty-icon" aria-hidden="true">{!! $icon('search') !!}</div>
                                        <strong>Siswa tidak ditemukan</strong>
                                        <span>Periksa kembali nama, NIS, atau NISN yang dicari.</span>
                                    </div>
                                @elseif(! $selectedRegistrations)
                                    <div class="payment-one-stop-empty-state">
                                        <div class="payment-one-stop-empty-icon" aria-hidden="true">{!! $icon('search') !!}</div>
                                        <strong>Belum ada siswa dipilih</strong>
                                        <span>Cari siswa berdasarkan nama, NIS, atau NISN untuk melihat tagihan pembayaran.</span>
                                    </div>
                                @else
                                    @php
                                        $mandatoryRows = collect();
                                        $optionalRows = collect();
                                        $isEditingSppPayment = isset($editSppPayment) && $editSppPayment;
                                        $editSppBillKey = $isEditingSppPayment ? $editSppPayment->student_id.':spp' : null;
                                        $editSppModeKey = $editSppBillKey ? str_replace(':', '_', $editSppBillKey) : null;

                                        foreach (collect([$activeRegistration])->filter() as $student) {
                                            $unitCode = $student->schoolClass?->educationUnit?->code ?? '-';
                                            foreach (collect($student->payment_options ?? []) as $option) {
                                                $label = $option['label'];
                                                $mandatoryRows->push([
                                                    'name' => 'bill_keys[]',
                                                    'key' => $option['bill_key'],
                                                    'title' => $option['key'] === 'spp' ? trim('SPP '.$unitCode) : $label,
                                                    'detail' => $option['detail_label'] ?? '',
                                                    'amount' => (int) ($option['remaining_amount'] ?? 0),
                                                    'display_amount' => (int) ($option['display_amount'] ?? $option['remaining_amount'] ?? 0),
                                                    'paid_through_current' => (bool) ($option['paid_through_current'] ?? false),
                                                    'paid_through_label' => $option['paid_through_label'] ?? null,
                                                    'mode_key' => str_replace(':', '_', $option['bill_key']),
                                                    'period_options' => $option['period_options'] ?? [],
                                                    'default_period_count' => (int) ($option['default_period_count'] ?? 1),
                                                    'url' => $option['url'] ?? '#',
                                                ]);
                                            }

                                            foreach (collect($student->optional_payment_options ?? []) as $option) {
                                                $optionalRows->push([
                                                    'name' => 'optional_keys[]',
                                                    'key' => $option['bill_key'],
                                                    'title' => $option['label'] ?? $option['title'] ?? 'Pembayaran Opsional',
                                                    'detail' => $option['detail_label'] ?? $option['detail'] ?? '',
                                                    'amount' => (int) ($option['remaining_amount'] ?? $option['amount_value'] ?? 0),
                                                    'mode_key' => str_replace(':', '_', $option['bill_key']),
                                                    'period_options' => $option['period_options'] ?? [],
                                                    'default_period_count' => (int) ($option['default_period_count'] ?? 1),
                                                    'url' => $option['url'] ?? '#',
                                                ]);
                                            }
                                        }

                                        $mandatoryBillRows = $mandatoryRows->filter(fn ($row) => $row['amount'] > 0 || $row['period_options'] !== [])->values();
                                        $optionalBillRows = $optionalRows->filter(fn ($row) => $row['amount'] > 0 || $row['period_options'] !== [])->values();
                                        if ($isEditingSppPayment) {
                                            $mandatoryBillRows = $mandatoryBillRows->filter(fn ($row) => $row['key'] === $editSppBillKey)->values();
                                            $optionalBillRows = collect();
                                        }
                                        $billRows = $mandatoryBillRows->concat($optionalBillRows)->values();
                                        $hasOldSelection = old('bill_keys') !== null || old('optional_keys') !== null;
                                        $oldBillKeys = collect(old('bill_keys', $isEditingSppPayment ? [$editSppBillKey] : []));
                                        $oldOptionalKeys = collect(old('optional_keys', []));
                                        $oldPaymentMonthCounts = collect(old('payment_month_counts', []));
                                        $oldAllocationAmounts = collect(old('allocation_amounts', []));
                                        $initialBillChoice = $oldBillKeys->count() === 1 ? (string) $oldBillKeys->first() : 'all';
                                        if ($isEditingSppPayment && ! $oldPaymentMonthCounts->has($editSppModeKey)) {
                                            $oldPaymentMonthCounts->put($editSppModeKey, (int) old('month_count', $editSppPayment->items->count()));
                                        }
                                        $defaultTotal = $billRows
                                            ->filter(fn ($row) => $row['name'] === 'optional_keys[]'
                                                ? ($hasOldSelection && $oldOptionalKeys->contains($row['key']))
                                                : ($hasOldSelection ? $oldBillKeys->contains($row['key']) : true))
                                            ->sum(function ($row) use ($oldPaymentMonthCounts) {
                                                $count = (int) $oldPaymentMonthCounts->get($row['mode_key'], $row['default_period_count']);
                                                $option = collect($row['period_options'])->firstWhere('count', $count);
                                                return (int) ($option['amount'] ?? $row['amount']);
                                            });
                                        $oldPaidDigits = preg_replace('/\D/', '', (string) old('paid_amount', $isEditingSppPayment ? $editSppPayment->paid_amount : $defaultTotal));
                                        $oldPaidLabel = $oldPaidDigits !== '' ? number_format((int) $oldPaidDigits, 0, ',', '.') : '';
                                        $initialPaymentType = old('payment_type_ui', (int) ($oldPaidDigits ?: 0) >= $defaultTotal ? 'full' : 'partial');
                                        $initialPaymentType = in_array($initialPaymentType, ['full', 'partial'], true) ? $initialPaymentType : 'full';
                                        $transferPaymentsEnabled = $transferPaymentsEnabled ?? true;
                                        $transferProofUploadsEnabled = $transferProofUploadsEnabled ?? false;
                                        $oldPaymentMethod = $cashOnly || (! $transferPaymentsEnabled && ! $isEditingSppPayment)
                                            ? 'Cash'
                                            : old('payment_method', $isEditingSppPayment ? $editSppPayment->payment_method : 'Cash');
                                        $showTransferPaymentControls = ! $cashOnly && $transferPaymentsEnabled;
                                        $statTotalObligation = (int) ($paymentSummary['total_obligation'] ?? 0);
                                        $statTotalPaid = (int) ($paymentSummary['total_paid'] ?? 0);
                                        $statRemainingAmount = (int) ($paymentSummary['remaining'] ?? 0);
                                        $statPaidPercent = $statTotalObligation > 0 ? min(100, (int) round(($statTotalPaid / $statTotalObligation) * 100)) : 0;
                                        $statBillCount = (int) ($paymentSummary['bill_count'] ?? 0);
                                        $paymentFormAction = $isEditingSppPayment ? route('finance.spp.update', $editSppPayment) : route('finance.payments.store');
                                        $paymentReturnUrl = $returnUrl ?: route('reports.transactions', request()->except(['edit_payment', 'student_id', 'registration_id', 'search', 'history_period', 'return_url']));
                                        $canDeleteHistory = auth()->user()?->hasPermission('payments.verify_transfer') ?? false;
                                        $canSavePayment = $isEditingSppPayment ? $canImportPayments : $canCreateCashPayment;
                                    @endphp
                                    <section class="payment-reference-stats" aria-label="Ringkasan kewajiban siswa">
                                        <article class="payment-reference-stat">
                                            <span>TOTAL KEWAJIBAN</span>
                                            <strong>Rp <span>{{ number_format($statTotalObligation, 0, ',', '.') }}</span></strong>
                                            <small>{{ $statBillCount }} tagihan tercatat</small>
                                        </article>
                                        <article class="payment-reference-stat is-paid">
                                            <span>TOTAL YANG SUDAH DIBAYARKAN</span>
                                            <strong>Rp <span>{{ number_format($statTotalPaid, 0, ',', '.') }}</span></strong>
                                            <div class="payment-reference-progress" aria-hidden="true"><span @class(['is-empty' => $statPaidPercent < 1, 'is-trace' => $statPaidPercent > 0 && $statPaidPercent < 25, 'is-quarter' => $statPaidPercent >= 25 && $statPaidPercent < 50, 'is-half' => $statPaidPercent >= 50 && $statPaidPercent < 75, 'is-most' => $statPaidPercent >= 75 && $statPaidPercent < 100, 'is-complete' => $statPaidPercent >= 100])></span></div>
                                            <small>{{ $statPaidPercent }}% dari total kewajiban</small>
                                        </article>
                                        <article class="payment-reference-stat is-remaining">
                                            <span>SISA TAGIHAN</span>
                                            <strong>Rp <span>{{ number_format($statRemainingAmount, 0, ',', '.') }}</span></strong>
                                            <small>{{ $statRemainingAmount > 0 ? 'Masih ada nominal yang belum dibayar' : 'Seluruh kewajiban lunas' }}</small>
                                        </article>
                                    </section>

                                    <form method="POST" action="{{ $paymentFormAction }}" enctype="multipart/form-data" class="payment-one-stop-pay-form" data-payment-one-stop-form data-payment-require-transfer-proof="{{ $transferProofUploadsEnabled ? 'true' : 'false' }}" @if($isEditingSppPayment) data-payment-edit-mode="spp" data-payment-has-transfer-proof="{{ ($editSppPayment->transfer_proof_path || $editSppPayment->transfer_proof_file_id) ? 'true' : 'false' }}" @endif>
                                            @csrf
                                            @if($isEditingSppPayment)
                                                @method('PUT')
                                                <input type="hidden" name="return_url" value="{{ $paymentReturnUrl }}">
                                                <input type="hidden" name="status" value="{{ $editSppPayment->status }}">
                                            @endif
                                            <input type="hidden" name="student_id" value="{{ $isEditingSppPayment ? $editSppPayment->student_id : $selectedIdentity->id }}">
                                            @unless($isEditingSppPayment)
                                                <input type="hidden" name="registration_id" value="{{ $activeRegistration?->id }}">
                                            @endunless
                                            <input type="hidden" name="search" value="{{ $search }}">

                                            <section class="payment-one-stop-bills-card payment-prd-bill-card">
                                                <div class="payment-one-stop-bills-head">
                                                    <h2>{{ $isEditingSppPayment ? 'Edit Pembayaran SPP' : 'Daftar Tagihan Siswa' }}</h2>
                                                </div>

                                                @if($errors->has('bill_keys'))
                                                    <div class="payment-one-stop-form-error">{{ $errors->first('bill_keys') }}</div>
                                                @endif

                                                @if($billRows->isEmpty())
                                                    <div class="payment-one-stop-empty">Tidak ada tagihan aktif untuk siswa ini.</div>
                                                @else
                                                    @php
                                                        $billSections = collect([
                                                            ['title' => 'Tagihan Wajib', 'suffix' => 'mandatory', 'rows' => $mandatoryBillRows, 'optional' => false],
                                                            ['title' => 'Pembayaran Opsional', 'suffix' => 'optional', 'rows' => $optionalBillRows, 'optional' => true],
                                                        ])->filter(fn ($section) => $section['rows']->isNotEmpty());
                                                    @endphp
                                                    @forelse($billSections as $section)
                                                        <div @class(['payment-prd-bill-section', 'payment-one-stop-optional-section' => $section['optional'], 'is-only-optional' => $section['optional'] && $mandatoryBillRows->isEmpty()])>
                                                            @if($section['optional'])
                                                                <div class="payment-prd-bill-section-title">
                                                                    <strong>{{ $section['title'] }}</strong>
                                                                    <span>{{ $section['rows']->count() }} Pilihan</span>
                                                                </div>
                                                            @endif
                                                            <div class="payment-one-stop-bill-modern-list payment-prd-bill-list">
                                                                @foreach($section['rows'] as $row)
                                                                    @php
                                                                        $checked = $section['optional']
                                                                            ? ($hasOldSelection ? $oldOptionalKeys->contains($row['key']) : false)
                                                                            : ($hasOldSelection ? $oldBillKeys->contains($row['key']) : true);
                                                                        $selectedMonthCount = (int) $oldPaymentMonthCounts->get($row['mode_key'], $row['default_period_count']);
                                                                        $selectedPeriodOption = collect($row['period_options'])->firstWhere('count', $selectedMonthCount);
                                                                        $remainingAmount = (int) ($selectedPeriodOption['amount'] ?? $row['amount']);
                                                                        $displayDetail = $selectedPeriodOption['card_detail'] ?? $selectedPeriodOption['detail'] ?? $row['detail'];
                                                                        $cardPeriodOptions = collect($row['period_options'])
                                                                            ->map(fn (array $option) => [
                                                                                'amount' => (int) ($option['amount'] ?? 0),
                                                                                'detail' => (string) ($option['card_detail'] ?? $option['detail'] ?? ''),
                                                                            ])
                                                                            ->values();
                                                                        $oldAllocation = (int) $oldAllocationAmounts->get($row['key'], 0);
                                                                        $allocationAmount = $isEditingSppPayment ? (int) $oldPaidDigits : $oldAllocation;
                                                                        $cardId = 'payment-bill-'.$section['suffix'].'-'.$loop->iteration;
                                                                        $cardTitle = str_ends_with($row['key'], ':spp') ? 'BIAYA BULANAN' : $row['title'];
                                                                    @endphp
                                                                    <div @class(['payment-one-stop-bill-modern-row', 'payment-prd-bill-row', 'is-optional' => $section['optional']]) data-payment-source-url="{{ $row['url'] }}" data-payment-display-row="{{ $row['key'] }}" data-payment-bill-row data-payment-summary-bill-key="{{ $row['key'] }}" data-payment-bill-title="{{ $cardTitle }}" data-payment-bill-detail="{{ $displayDetail }}" data-payment-bill-period-options="{{ $cardPeriodOptions->toJson() }}" data-amount="{{ $remainingAmount }}">
                                                                        <input id="{{ $cardId }}" type="checkbox" name="{{ $row['name'] }}" value="{{ $row['key'] }}" data-payment-bill data-amount="{{ $remainingAmount }}" @checked($checked)>
                                                                        @if($row['period_options'] !== [])
                                                                            <input type="hidden" name="{{ $isEditingSppPayment && $row['key'] === $editSppBillKey ? 'month_count' : 'payment_month_counts['.$row['mode_key'].']' }}" value="{{ $selectedMonthCount }}">
                                                                        @endif
                                                                        @unless($isEditingSppPayment)
                                                                            <input type="hidden" name="allocation_amounts[{{ $row['key'] }}]" value="{{ $allocationAmount }}" data-payment-bill-allocation @disabled($allocationAmount < 1)>
                                                                        @endunless
                                                                        <label class="payment-prd-bill-choice" for="{{ $cardId }}">
                                                                            <span class="payment-one-stop-bill-modern-copy payment-prd-bill-copy">
                                                                                <span class="payment-prd-bill-heading">
                                                                                    <strong>{{ $cardTitle }}</strong>
                                                                                    <span class="payment-prd-bill-amount"><strong>Rp. <span data-payment-bill-amount>{{ number_format($remainingAmount, 0, ',', '.') }}</span></strong></span>
                                                                                </span>
                                                                                @if($displayDetail)
                                                                                    <span data-payment-bill-detail>{{ $displayDetail }}</span>
                                                                                @endif
                                                                            </span>
                                                                        </label>
                                                                        <div class="payment-prd-bill-payment-control">
                                                                            <label for="{{ $cardId }}-amount">NOMINAL PEMBAYARAN</label>
                                                                            <div class="payment-prd-bill-money-input">
                                                                                <span aria-hidden="true">Rp</span>
                                                                                <input id="{{ $cardId }}-amount" type="text" value="{{ $allocationAmount > 0 ? number_format($allocationAmount, 0, ',', '.') : '' }}" inputmode="numeric" data-currency-input data-payment-bill-input data-payment-bill-balance="{{ $remainingAmount }}" aria-describedby="{{ $cardId }}-error" @disabled(! $checked)>
                                                                                <button type="button" class="payment-prd-bill-full-button" data-payment-bill-full @disabled(! $checked)>PENUH</button>
                                                                            </div>
                                                                            <span id="{{ $cardId }}-error" class="payment-prd-field-error" data-payment-bill-error hidden></span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="payment-one-stop-empty">Tidak ada tagihan yang dapat dibayar saat ini.</div>
                                                    @endforelse
                                                @endif
                                            </section>

                                            <section class="payment-one-stop-payment-card payment-prd-summary-card">
                                                <div class="payment-one-stop-payment-head">
                                                    <h2>{{ $cashOnly ? 'Kasir Pembayaran Tunai' : 'Kasir Pembayaran' }}</h2>
                                                </div>

                                                <div class="payment-one-stop-form-controls">
                                                    @php
                                                        $cashierTimestamp = $isEditingSppPayment ? $editSppPayment->transaction_at : now();
                                                    @endphp
                                                    <section class="payment-prd-cashier-datetime" aria-label="Waktu transaksi">
                                                        <label for="payment-transaction-date">
                                                            <span>Tanggal</span>
                                                            <input id="payment-transaction-date" type="date" name="transaction_date" value="{{ old('transaction_date', $cashierTimestamp->format('Y-m-d')) }}">
                                                        </label>
                                                        <label for="payment-transaction-time">
                                                            <span>Jam</span>
                                                            <input id="payment-transaction-time" type="time" name="transaction_time" value="{{ old('transaction_time', $cashierTimestamp->format('H:i')) }}" step="60">
                                                        </label>
                                                    </section>
                                                    <section class="payment-prd-cashier-selected" data-payment-selected-summary aria-label="Tagihan dialokasikan">
                                                        <div class="payment-prd-cashier-selected-head">
                                                            <span>Tagihan Dipilih</span>
                                                            <strong data-payment-selected-count>0 Tagihan</strong>
                                                        </div>
                                                        <div class="payment-prd-cashier-selected-list" data-payment-selected-list aria-live="polite">
                                                            <span class="payment-prd-cashier-selected-empty" data-payment-selected-empty>Belum ada nominal pembayaran yang diisi.</span>
                                                        </div>
                                                    </section>

                                                    <input type="hidden" name="paid_amount" value="{{ $isEditingSppPayment ? $oldPaidDigits : 0 }}" data-payment-paid-display>
                                                    <span id="payment-paid-error" class="payment-prd-field-error" data-payment-paid-error @if(! $errors->has('paid_amount')) hidden @endif>{{ $errors->first('paid_amount') }}</span>

                                                    @if($showTransferPaymentControls || $oldPaymentMethod !== 'Transfer')
                                                    <fieldset class="payment-prd-summary-field payment-prd-method-card">
                                                        <legend>Metode Pembayaran</legend>
                                                        <input type="hidden" name="payment_method" value="{{ $oldPaymentMethod }}" data-payment-method>
                                                        <div class="payment-prd-segmented payment-prd-method-segments" data-payment-method-control>
                                                            <label class="payment-prd-segment-option">
                                                                <input type="radio" name="payment_method_ui" value="Cash" data-payment-method-option @checked($oldPaymentMethod === 'Cash')>
                                                                <span>Tunai</span>
                                                            </label>
                                                            @if($showTransferPaymentControls)
                                                            <label class="payment-prd-segment-option">
                                                                <input type="radio" name="payment_method_ui" value="Transfer" data-payment-method-option @checked($oldPaymentMethod === 'Transfer')>
                                                                <span>Transfer</span>
                                                            </label>
                                                            @endif
                                                        </div>
                                                        <span class="payment-prd-field-error" data-payment-method-error @if(! $errors->has('payment_method')) hidden @endif>{{ $errors->first('payment_method') }}</span>
                                                    </fieldset>
                                                    @endif

                                                    <div class="payment-one-stop-bill-total payment-prd-total-box">
                                                        <span>Total Pembayaran</span>
                                                        <span class="payment-one-stop-bill-total-amount">
                                                            <span>Rp.</span>
                                                            <b data-payment-total>{{ number_format($isEditingSppPayment ? (int) $oldPaidDigits : 0, 0, ',', '.') }},-</b>
                                                        </span>
                                                    </div>

                                                    @if($showTransferPaymentControls && $transferProofUploadsEnabled)
                                                    <div class="payment-prd-transfer-section" data-payment-transfer-panel @if($oldPaymentMethod !== 'Transfer') hidden @endif>
                                                        <div class="payment-one-stop-transfer-upload" data-payment-transfer-upload>
                                                            <span>Bukti Transfer</span>
                                                            <label class="payment-one-stop-upload-field">
                                                                <span class="payment-one-stop-upload-icon" aria-hidden="true">{!! $icon('upload') !!}</span>
                                                                <span class="payment-one-stop-upload-copy">
                                                                    <strong data-payment-upload-name>{{ $isEditingSppPayment && ($editSppPayment->transfer_proof_path || $editSppPayment->transfer_proof_file_id) ? 'Bukti lama tersimpan, pilih file jika ingin mengganti' : 'Pilih file bukti transfer' }}</strong>
                                                                    <small>JPG, JPEG, PNG, atau PDF maksimal 2 MB</small>
                                                                </span>
                                                                <input type="file" name="transfer_proof" accept=".jpg,.jpeg,.png,.pdf" aria-describedby="payment-transfer-error" data-payment-transfer-file>
                                                            </label>
                                                            <span id="payment-transfer-error" class="payment-prd-field-error" data-payment-transfer-error @if(! $errors->has('transfer_proof')) hidden @endif>{{ $errors->first('transfer_proof') }}</span>
                                                        </div>
                                                    </div>
                                                    @endif

                                                    @if($canSavePayment)
                                                    <button class="button button-primary payment-one-stop-pay-button" data-payment-submit @disabled($billRows->isEmpty())>{!! $isEditingSppPayment ? $icon('check') : $icon('printer') !!}<span data-payment-submit-label>{{ $isEditingSppPayment ? 'Simpan Perubahan' : 'Bayar & Cetak Struk' }}</span></button>
                                                    @endif
                                                </div>
                                            </section>
                                    </form>

                                    <section class="payment-one-stop-history-card">
                                            <div class="payment-one-stop-history-head">
                                                <div class="payment-prd-history-title">
                                                    <h2>Riwayat Terbaru</h2>
                                                    <span>Transaksi terakhir siswa ini</span>
                                                </div>
                                            </div>
                                            @if($paymentHistory->isEmpty())
                                                <div class="payment-one-stop-history-empty">
                                                    <strong>Belum ada riwayat pembayaran untuk siswa ini.</strong>
                                                    <span>Transaksi yang berhasil akan muncul di sini.</span>
                                                </div>
                                            @else
                                                <div class="payment-one-stop-history-list">
                                                    @foreach($paymentHistory as $history)
                                                        <article class="payment-one-stop-history-item" data-payment-history-type="{{ $history['type'] }}" data-payment-history-id="{{ $history['id'] }}">
                                                            <div class="payment-one-stop-history-copy">
                                                                <strong class="payment-prd-history-primary">{{ $history['title'] }}</strong>
                                                                @if($history['detail'])
                                                                <span class="payment-prd-history-detail">{{ $history['detail'] }}</span>
                                                                @endif
                                                                <span class="payment-prd-history-meta">{{ $history['date'] }} <span aria-hidden="true">•</span> {{ $history['method'] }}</span>
                                                            </div>
                                                            <div class="payment-one-stop-history-side">
                                                                <span class="payment-one-stop-history-amount">
                                                                    <span>Rp.</span>
                                                                    <strong>{{ $history['amount_label'] }}</strong>
                                                                </span>
                                                                <span class="payment-one-stop-history-actions">
                                                                    <a class="payment-one-stop-history-action" href="{{ $history['receipt_url'] }}" target="_blank" rel="noopener" title="Cetak struk" aria-label="Cetak struk">{!! $icon('printer') !!}<span>Struk</span></a>
                                                                    <a class="payment-one-stop-history-action" href="{{ $history['download_url'] }}" title="Download PDF" aria-label="Download PDF">{!! $icon('download') !!}<span>PDF</span></a>
                                                                    @if($canDeleteHistory)
                                                                    <form method="POST" action="{{ $history['delete_url'] }}" data-payment-history-delete-form data-payment-delete-title="{{ $history['title'] }}" data-payment-delete-detail="{{ $history['detail'] }}" data-payment-delete-amount="Rp. {{ $history['amount_label'] }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="return_url" value="{{ route('finance.payments.index', ['search' => $search, 'student_id' => $selectedIdentity->id]) }}">
                                                                        <button class="payment-one-stop-history-action danger" type="submit" title="Hapus transaksi" aria-label="Hapus transaksi">{!! $icon('trash') !!}</button>
                                                                    </form>
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </article>
                                                    @endforeach
                                                </div>
                                            @endif
                                    </section>
                                    @if($canDeleteHistory)
                                            <div class="modal-backdrop payment-history-delete-modal" data-payment-history-delete-modal hidden role="dialog" aria-modal="true" aria-labelledby="payment-delete-title" aria-describedby="payment-delete-description">
                                                <div class="form-modal spp-delete-modal payment-delete-card" role="document">
                                                    <span class="payment-delete-icon" aria-hidden="true">{!! $icon('trash') !!}</span>
                                                    <h2 id="payment-delete-title">Hapus Transaksi?</h2>
                                                    <p id="payment-delete-description">
                                                        Transaksi <strong data-payment-delete-name></strong>
                                                        <span data-payment-delete-meta></span>
                                                        akan dihapus dan sisa tagihan akan dihitung ulang.
                                                    </p>
                                                    <div class="form-actions">
                                                        <button type="button" class="button button-secondary" data-payment-delete-cancel>Batal</button>
                                                        <button type="button" class="button button-danger" data-payment-delete-confirm>Ya, Hapus</button>
                                                    </div>
                                                </div>
                                            </div>
                                    @endif
                                @endif
                            @else
                                <div class="payment-one-stop-empty-state">
                                    <div class="payment-one-stop-empty-icon" aria-hidden="true">{!! $icon('search') !!}</div>
                                    <strong>Belum ada siswa dipilih</strong>
                                    <span>Cari siswa berdasarkan nama, NIS, atau NISN untuk melihat tagihan pembayaran.</span>
                                </div>
                            @endif
                        @unless($selectedRegistrations)
                        </section>
                        @endunless
                        @endunless
                    </div>
                @elseif($mode === 'history')
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>Riwayat Pembayaran</h1>
                            <p>Pilih jenis riwayat untuk melihat transaksi yang sudah tercatat.</p>
                        </div>
                        @if(auth()->user()?->hasPermission('payments.cash.create'))
                        <div class="student-action-bar">
                            <a class="button student-add-button" href="{{ route('finance.payments.index') }}">Pembayaran</a>
                        </div>
                        @endif
                    </div>
                    <div class="payment-history-grid">
                        @foreach([
                            ['SPP', 'Pembayaran bulanan SPP siswa.', route('finance.spp.index'), 'SPP'],
                            ['Daftar Ulang', 'Pembayaran daftar ulang dan biaya awal siswa.', route('finance.other.index', ['category' => 'daftar-ulang']), 'DU'],
                            ['Laundry', 'Pembayaran laundry bulanan siswa.', route('finance.other.index', ['category' => 'laundry']), 'LD'],
                            ['Lain-lain', 'Kategori pembayaran lainnya.', route('finance.other.index'), 'LL'],
                        ] as [$title, $description, $url, $code])
                            <a href="{{ $url }}" class="payment-history-card">
                                <span>{{ $code }}</span>
                                <strong>Riwayat {{ $title }}</strong>
                                <small>{{ $description }}</small>
                            </a>
                        @endforeach
                    </div>
                @elseif($mode === 'import-result')
                    @php
                        $headerImportResult = collect(session('import_result', []));
                        $headerHasIssues = (int) $headerImportResult->get('failed', 0) > 0
                            || (int) $headerImportResult->get('skipped', 0) > 0;
                        $headerImported = (int) $headerImportResult->get('imported', 0);
                    @endphp
                    <div class="payment-import-heading-copy">
                        <h1>Import Pembayaran Selesai</h1>
                        <p>{{ $headerImported === 0 ? 'Belum ada transaksi yang berhasil diimpor.' : ($headerHasIssues ? 'Proses import telah selesai. Periksa ringkasan hasil di bawah.' : 'Proses import telah selesai dan hasil transaksi sudah diperbarui.') }}</p>
                    </div>
                @else
                    <div class="payment-import-heading-copy">
                        <h1>{{ $mode === 'import-preview' ? 'Preview & Validasi Import' : 'Import Pembayaran' }}</h1>
                        <p>{{ $mode === 'import-preview' ? 'Periksa data valid, gagal, dan duplikat sebelum transaksi diimpor.' : 'Unggah file Excel, tentukan konteks pembayaran, lalu validasi data sebelum diimpor.' }}</p>
                    </div>
                    <div class="payment-hub-heading-actions">
                        <a @class(['button', 'button-secondary', 'payment-import-preview-back-button' => $mode === 'import-preview']) href="{{ $mode === 'import-preview' ? route('finance.payments.import') : route('finance.payments.index') }}" title="{{ $mode === 'import-preview' ? 'Kembali' : 'Kembali ke Pembayaran' }}" aria-label="{{ $mode === 'import-preview' ? 'Kembali' : 'Kembali ke Pembayaran' }}">{!! $icon('arrow-left') !!}<span>{{ $mode === 'import-preview' ? 'Kembali' : 'Kembali ke Pembayaran' }}</span></a>
                    </div>
                @endif
            </section>

            @if($mode === 'import')
                @php
                    $importTypes = [
                        ['spp', 'SPP', 'SPP', 'Pembayaran bulanan siswa.', route('finance.spp.import.preview')],
                        ['daftar-ulang', 'DU', 'Daftar Ulang', 'Pembayaran daftar ulang siswa.', route('finance.other.import.preview', ['category' => 'daftar-ulang'])],
                        ['laundry', 'LD', 'Laundry', 'Pembayaran laundry per bulan.', route('finance.other.import.preview', ['category' => 'laundry'])],
                        ['lain-lain', 'LL', 'Lain-lain', 'Kategori pembayaran lainnya.', route('finance.other.import.preview')],
                    ];
                    $importMonths = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    $importYears = range(now()->year - 5, now()->year + 1);
                    $defaultImportMonth = (int) old('month', now()->month);
                    $defaultImportYear = (int) old('year', now()->year);
                @endphp

                @if(session('success'))
                    <div class="result-modal-backdrop show payment-import-success-modal" data-alert role="dialog" aria-modal="true" aria-labelledby="payment-import-success-title">
                        <div class="result-modal success-result payment-import-success-card" role="document">
                            <span class="result-icon payment-import-success-icon" aria-hidden="true">{!! $icon('check') !!}</span>
                            <strong id="payment-import-success-title">Import Berhasil</strong>
                            <p>{{ session('success') }}</p>
                            <button type="button" class="button button-primary" data-alert-close>Selesai</button>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="payment-import-alert" role="alert">
                        <strong>File belum dapat diproses.</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ $importTypes[0][4] }}"
                    enctype="multipart/form-data"
                    class="payment-import-simple-form"
                    data-payment-import
                >
                    @csrf
                    <section class="payment-import-card payment-import-context-card">
                        <div class="payment-import-context-grid">
                            <label class="payment-import-simple-field">
                                <span>Jenis Pembayaran</span>
                                <select data-payment-import-category>
                                    @foreach($importTypes as [$key, $code, $title, $description, $action])
                                        <option value="{{ $key }}" data-action="{{ $action }}">{{ $title }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="payment-import-spp-context" data-payment-import-spp-context>
                                <label class="payment-import-simple-field">
                                    <span>Unit Pendidikan</span>
                                    <select name="unit_id" required data-payment-import-spp-field>
                                        <option value="">Pilih unit</option>
                                        @foreach($educationUnits ?? [] as $unit)
                                            <option value="{{ $unit->id }}" @selected((int) old('unit_id') === $unit->id)>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="payment-import-simple-field">
                                    <span>Bulan</span>
                                    <select name="month" required data-payment-import-spp-field>
                                        @foreach($importMonths as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" @selected($defaultImportMonth === $monthNumber)>{{ $monthName }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="payment-import-simple-field">
                                    <span>Tahun</span>
                                    <select name="year" required data-payment-import-spp-field>
                                        @foreach($importYears as $year)
                                            <option value="{{ $year }}" @selected($defaultImportYear === $year)>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </label>

                            </div>
                        </div>
                    </section>

                    <section class="payment-import-card payment-import-file-card" aria-labelledby="payment-import-file-title">
                        <div class="payment-import-card-heading">
                            <div>
                                <h2 id="payment-import-file-title">File Excel</h2>
                                <p>Unggah file XLSX untuk melihat preview dan memvalidasi data sebelum diimpor.</p>
                            </div>
                        </div>

                        <div class="payment-import-dropzone" data-payment-import-dropzone>
                            <span class="payment-import-dropzone-icon" aria-hidden="true">{!! $icon('file') !!}</span>
                            <label class="payment-import-dropzone-copy" for="payment-import-file-input" data-payment-import-file-empty>
                                <strong>Pilih file Excel</strong>
                                <small>Tarik file ke sini atau klik untuk memilih. Format XLSX, maksimal 10 MB.</small>
                            </label>
                            <span class="payment-import-dropzone-copy" data-payment-import-file-selected hidden>
                                <strong data-payment-import-filename></strong>
                                <small data-payment-import-filesize></small>
                            </span>
                            <span class="payment-import-dropzone-actions">
                                <button type="button" class="button button-secondary" data-payment-import-file-change hidden>Ganti</button>
                                <button type="button" class="button button-secondary" data-payment-import-file-remove hidden>Hapus</button>
                            </span>
                            <input
                                id="payment-import-file-input"
                                type="file"
                                name="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required
                                data-payment-import-file
                            >
                        </div>
                    </section>

                    <div class="payment-import-simple-actions">
                        <button type="submit" class="button button-primary" disabled data-payment-import-submit>
                            <span class="payment-import-spinner" aria-hidden="true"></span>
                            {!! $icon('upload') !!}
                            <span data-payment-import-submit-label>Preview &amp; Validasi</span>
                        </button>
                    </div>
                </form>
            @elseif($mode === 'import-result')
                @php
                    $importResult = collect(session('import_result', []));
                    $resultImported = (int) $importResult->get('imported', 0);
                    $resultFailed = (int) $importResult->get('failed', 0);
                    $resultSkipped = (int) $importResult->get('skipped', 0);
                    $resultHasIssues = $resultFailed > 0 || $resultSkipped > 0;
                    $resultHeadline = $resultImported > 0 ? 'Import Pembayaran Selesai' : 'Import Selesai Diproses';
                    $resultSubtitle = $resultImported > 0
                        ? ($resultHasIssues
                            ? 'Proses import telah selesai. Periksa ringkasan hasil di bawah.'
                            : 'Proses import telah selesai dan hasil transaksi sudah diperbarui.')
                        : 'Belum ada transaksi yang berhasil diimpor.';
                @endphp
                <section class="payment-import-result-panel" aria-labelledby="payment-import-result-title">
                    <div class="payment-import-result-summary">
                        <div class="payment-import-result-icon{{ $resultImported > 0 ? ' is-success' : ' is-empty' }}" aria-hidden="true">
                            @if($resultImported > 0)
                                {!! $icon('check') !!}
                            @else
                                <span>!</span>
                            @endif
                        </div>
                        <h2 id="payment-import-result-title">{{ $resultHeadline }}</h2>
                        <p>{{ $resultSubtitle }}</p>
                        @if($resultImported > 0)
                            <strong>{{ number_format($resultImported, 0, ',', '.') }} transaksi berhasil diimpor.</strong>
                            @if($resultHasIssues)
                                <span>{{ number_format($resultFailed + $resultSkipped, 0, ',', '.') }} transaksi tidak diproses.</span>
                            @endif
                        @endif
                    </div>

                    <div class="payment-import-result-context">
                        <strong>{{ $importResult->get('context_label', 'Import Pembayaran') }}</strong>
                        @if($importResult->get('file_name'))
                            <small>{{ $importResult->get('file_name') }}</small>
                        @endif
                    </div>

                    <div class="payment-import-result-cards">
                        <article class="payment-import-result-card is-success">
                            <span>Berhasil Diimpor</span>
                            <strong>{{ number_format($resultImported, 0, ',', '.') }}</strong>
                        </article>
                        <article class="payment-import-result-card is-failed">
                            <span>Data Gagal</span>
                            <strong>{{ number_format($resultFailed, 0, ',', '.') }}</strong>
                        </article>
                        <article class="payment-import-result-card is-skipped">
                            <span>Duplikat / Dilewati</span>
                            <strong>{{ number_format($resultSkipped, 0, ',', '.') }}</strong>
                        </article>
                    </div>

                    <div class="payment-import-result-actions">
                        <a href="{{ route('finance.payments.index') }}" class="button button-primary">Kembali ke Pembayaran</a>
                        <a href="{{ route('finance.payments.import') }}" class="button button-secondary">Import Lagi</a>
                    </div>
                </section>
            @elseif($mode === 'import-preview')
                @php
                    $previewType = $importPreviewType ?? 'spp';
                    $sectionTitle = $importSection['title'] ?? 'SPP';
                    $unresolvedSources = collect($importUnresolvedSources ?? []);
                    $previewImportAction = $importAction ?? route('finance.spp.import');
                    $canImport = $importPreview['valid'] > 0;
                    $previewFailureRows = collect($importPreview['failures'] ?? []);
                    $previewReadyCount = (int) ($importPreview['valid'] ?? 0);
                    $previewFailureCount = $previewFailureRows->count();
                    $previewDuplicateCount = (int) ($importPreview['duplicates'] ?? 0);
                    $previewContextLabel = collect([
                        $sectionTitle,
                        $importContext['unit'] ?? null,
                        ! empty($importContext) ? (($importContext['month'] ?? '').' '.($importContext['year'] ?? '')) : null,
                    ])->filter()->implode(' · ');
                    $failureReasonLabel = static function (array $row): string {
                        $message = trim((string) ($row['message'] ?? ''));
                        $normalizedMessage = strtolower($message);

                        return match (true) {
                            str_contains($normalizedMessage, 'berurutan') => 'Urutan SPP belum lengkap',
                            str_contains($normalizedMessage, 'sudah lunas') => 'SPP sudah lunas',
                            str_contains($normalizedMessage, 'nis') && str_contains($normalizedMessage, 'tidak ditemukan') => 'NIS tidak ditemukan',
                            str_contains($normalizedMessage, 'nominal') => 'Nominal tidak valid',
                            str_contains($normalizedMessage, 'nama') && str_contains($normalizedMessage, 'tidak cocok') => 'Nama siswa tidak cocok',
                            str_contains($normalizedMessage, 'kategori') => 'Kategori pembayaran tidak cocok',
                            str_contains($normalizedMessage, 'transaksi tidak dapat') => 'Transaksi tidak dapat diproses',
                            $message !== '' => 'Validasi gagal',
                            default => 'Validasi gagal',
                        };
                    };
                    $failureReasonSummary = static function (string $label): string {
                        return match ($label) {
                            'Urutan SPP belum lengkap' => 'Periksa periode pembayaran sebelumnya pada siswa ini.',
                            'SPP sudah lunas' => 'Periksa periode SPP dan riwayat pembayaran siswa.',
                            'NIS tidak ditemukan' => 'Periksa NIS dan unit siswa pada file Excel.',
                            'Nama siswa tidak cocok' => 'Periksa ejaan nama dan NIS pada file Excel.',
                            'Nominal tidak valid' => 'Periksa nominal pembayaran pada file Excel.',
                            'Kategori pembayaran tidak cocok' => 'Periksa kategori, unit, dan kelas pembayaran.',
                            default => 'Periksa data pada baris Excel dan detail validasinya.',
                        };
                    };
                    $failureGroups = $previewFailureRows
                        ->groupBy(fn (array $row) => $failureReasonLabel($row))
                        ->map(fn ($rows, $label) => [
                            'label' => $label,
                            'count' => $rows->count(),
                            'details' => $rows->pluck('message')->filter()->unique()->values(),
                        ])
                        ->values();
                @endphp
                <section class="payment-import-preview-panel">
                    <div class="payment-import-preview-context">
                        <div>
                            <strong>{{ $previewContextLabel }}</strong>
                            @if(! empty($importFileName))
                                <small>{{ $importFileName }}</small>
                            @endif
                        </div>
                    </div>

                    @if($unresolvedSources->isNotEmpty())
                        <form method="POST" action="{{ $importMappingAction }}" class="payment-import-mapping">
                            @csrf
                            <input type="hidden" name="token" value="{{ $importToken }}">
                            <div class="payment-import-mapping-heading">
                                <strong>Pemetaan Kategori</strong>
                                <span>Pilih kategori pembayaran untuk data yang belum dikenali.</span>
                            </div>
                            <div class="payment-import-mapping-fields">
                                @foreach($unresolvedSources as $source)
                                    <label>
                                        <span>{{ $source['category'] }} · {{ $source['unit'] }}{{ $source['class_level'] ? ' · '.\App\Support\ClassLevel::label($source['class_level']) : '' }}</span>
                                        <select name="mappings[{{ $source['key'] }}]" required>
                                            <option value="">Pilih kategori</option>
                                            @foreach($importFeeTypes as $feeType)
                                                @php
                                                    $feeTypeScope = $feeType->schoolClass?->name
                                                        ?? ($feeType->class_level ? \App\Support\ClassLevel::label($feeType->class_level) : 'Semua Kelas');
                                                    $feeTypeYear = $feeType->academicYear?->name;
                                                @endphp
                                                <option value="{{ $feeType->id }}">{{ $feeType->name }} · {{ $feeType->educationUnit?->code ?? '-' }} · {{ $feeTypeScope }}{{ $feeTypeYear ? ' · '.$feeTypeYear : '' }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach
                            </div>
                            <div class="payment-import-mapping-actions">
                                <button type="submit" class="button button-primary">Terapkan</button>
                            </div>
                        </form>
                    @endif

                    <div class="payment-import-preview-dashboard" data-import-preview data-import-preview-page-size="25">
                        <div class="payment-import-preview-summary-grid">
                            <article class="payment-import-preview-summary-card is-ready">
                                <span>Siap Diimpor</span>
                                <strong>{{ number_format($previewReadyCount, 0, ',', '.') }}</strong>
                                <small>data valid</small>
                            </article>
                            <article class="payment-import-preview-summary-card is-failed">
                                <span>Data Gagal</span>
                                <strong>{{ number_format($previewFailureCount, 0, ',', '.') }}</strong>
                                <small>perlu diperiksa</small>
                            </article>
                            <article class="payment-import-preview-summary-card is-duplicate">
                                <span>Duplikat</span>
                                <strong>{{ number_format($previewDuplicateCount, 0, ',', '.') }}</strong>
                                <small>akan dilewati</small>
                            </article>
                        </div>

                        @if($failureGroups->isNotEmpty())
                            @php
                                $primaryFailure = $failureGroups->first();
                            @endphp
                            <section class="payment-import-preview-issues" aria-labelledby="payment-import-preview-issues-title">
                                <div class="payment-import-preview-section-heading">
                                    <div>
                                        <h2 id="payment-import-preview-issues-title">Masalah Utama</h2>
                                        <p>{{ number_format($previewFailureCount, 0, ',', '.') }} baris perlu diperiksa.</p>
                                    </div>
                                </div>
                                <div class="payment-import-preview-primary-issue">
                                    <span class="payment-import-preview-issue-icon" aria-hidden="true">!</span>
                                    <div>
                                        <strong>{{ $primaryFailure['label'] }}</strong>
                                        <span>{{ number_format($primaryFailure['count'], 0, ',', '.') }} siswa terdampak</span>
                                        @if($primaryFailure['details']->isNotEmpty())
                                            <small>{{ $failureReasonSummary($primaryFailure['label']) }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="payment-import-preview-error-groups">
                                    @foreach($failureGroups as $failureGroup)
                                        <button type="button" class="payment-import-preview-error-group" data-import-preview-group="{{ $failureGroup['label'] }}" aria-pressed="false" title="Tampilkan {{ $failureGroup['label'] }}">
                                            <span>{{ $failureGroup['label'] }}</span>
                                            <strong>{{ number_format($failureGroup['count'], 0, ',', '.') }}</strong>
                                        </button>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <section class="payment-import-preview-data-section" aria-labelledby="payment-import-preview-data-title">
                            <div class="payment-import-preview-section-heading">
                                <div>
                                    <h2 id="payment-import-preview-data-title">Data Perlu Diperiksa</h2>
                                    <p>Periksa data yang gagal sebelum melanjutkan proses import.</p>
                                </div>
                                <span class="payment-import-preview-failure-count">{{ number_format($previewFailureCount, 0, ',', '.') }} data gagal</span>
                            </div>

                            @if($previewFailureRows->isNotEmpty())
                                <div class="payment-import-preview-controls">
                                    <label class="payment-import-preview-search">
                                        <span class="sr-only">Cari NIS atau nama siswa</span>
                                        <span class="payment-import-preview-search-icon" aria-hidden="true">{!! $icon('search') !!}</span>
                                        <input type="search" placeholder="Cari NIS atau nama siswa" data-import-preview-search>
                                    </label>
                                </div>
                            @endif

                            @if($previewFailureRows->isNotEmpty())
                                <div class="table-wrap payment-import-preview-table-wrap">
                                    <table class="data-table payment-import-preview-table">
                                        <thead><tr><th>Baris</th><th>NIS</th><th>Nama Siswa</th><th>{{ $previewType === 'spp' ? 'Periode' : 'Kategori' }}</th><th>Nominal</th><th>Masalah</th></tr></thead>
                                        <tbody data-import-preview-rows>
                                            @foreach($previewFailureRows as $row)
                                                @php
                                                    $rowReason = $failureReasonLabel($row);
                                                    $rowPeriod = $previewType === 'spp'
                                                        ? ucfirst((string) ($row['month_name'] ?? '-')).' '.($row['year'] ?? '')
                                                        : ($row['category'] ?? '-');
                                                    $rowSearchText = implode(' ', array_filter([
                                                        $row['line'] ?? null,
                                                        $row['nis'] ?? null,
                                                        $row['name'] ?? null,
                                                        $rowPeriod,
                                                        $rowReason,
                                                        $row['message'] ?? null,
                                                    ]));
                                                @endphp
                                                <tr data-import-preview-row data-reason="{{ $rowReason }}" data-search="{{ $rowSearchText }}">
                                                    <td>{{ $row['line'] ?? '-' }}</td>
                                                    <td>{{ $row['nis'] ?? '-' }}</td>
                                                    <td><strong>{{ $row['name'] ?? '-' }}</strong></td>
                                                    <td>{{ $rowPeriod }}</td>
                                                    <td class="payment-import-preview-amount">Rp {{ number_format((int) ($row['nominal'] ?? 0), 0, ',', '.') }}</td>
                                                    <td>
                                                        <div class="payment-import-preview-problem-cell">
                                                            <span>{{ $rowReason }}</span>
                                                            <button type="button" class="payment-import-preview-detail-toggle" data-import-preview-detail-toggle aria-expanded="false">Lihat detail</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="payment-import-preview-detail-row" data-import-preview-detail-row hidden>
                                                    <td colspan="6">
                                                        <div class="payment-import-preview-detail-content">
                                                            <div>
                                                                <strong>Alasan</strong>
                                                                <p>{{ $row['message'] ?? 'Validasi gagal.' }}</p>
                                                            </div>
                                                            <div>
                                                                <strong>Yang perlu diperiksa</strong>
                                                                <p>{{ $failureReasonSummary($rowReason) }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="payment-import-preview-pagination" data-import-preview-pagination>
                                    <span data-import-preview-range></span>
                                    <div data-import-preview-pages></div>
                                </div>
                            @else
                                <div class="payment-import-preview-empty is-success">
                                    <span class="payment-import-preview-empty-icon" aria-hidden="true">{!! $icon('check') !!}</span>
                                    <div>
                                        <strong>Tidak ada data yang perlu diperiksa.</strong>
                                        <span>Seluruh data lolos validasi.</span>
                                    </div>
                                </div>
                            @endif
                        </section>
                    </div>

                    <div class="payment-import-preview-submit">
                        @if($canImport)
                            <small>{{ number_format($previewReadyCount, 0, ',', '.') }} transaksi siap diimpor.</small>
                        @else
                            <small>Tidak ada transaksi valid yang dapat diimpor.</small>
                        @endif
                        <form method="POST" action="{{ $previewImportAction }}" data-import-preview-submit-form>
                            @csrf
                            <input type="hidden" name="token" value="{{ $importToken }}">
                            <button class="button button-primary" data-import-preview-submit data-import-count="{{ $previewReadyCount }}" @disabled(! $canImport)>
                                <span class="payment-import-spinner" aria-hidden="true"></span>
                                {!! $icon('check') !!}
                                <span data-import-preview-submit-label>Import {{ number_format($previewReadyCount, 0, ',', '.') }} Transaksi</span>
                            </button>
                        </form>
                    </div>
                </section>
            @endif
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
