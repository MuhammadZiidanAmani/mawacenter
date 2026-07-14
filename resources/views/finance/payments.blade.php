<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'import-preview' ? 'Preview Impor Pembayaran' : ($mode === 'import' ? 'Impor Pembayaran' : ($mode === 'history' ? 'Riwayat Pembayaran' : 'Pembayaran')) }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => $mode === 'history' ? 'reports' : 'payment',
        'activeReportMenu' => $mode === 'history' ? 'history' : '',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">☰</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
        </header>

        <main @class(['payment-hub-page', 'payment-import-page' => in_array($mode, ['import', 'import-preview'], true), 'payment-import-preview-page' => $mode === 'import-preview', 'payment-transaction-page' => $mode === 'payment', 'student-page payment-flat-page' => in_array($mode, ['payment', 'history'], true)])>
            <section @class(['payment-hub-heading payment-import-page-heading' => in_array($mode, ['import', 'import-preview'], true), 'student-workspace payment-transaction-workspace' => $mode === 'payment', 'student-workspace payment-history-workspace' => $mode === 'history'])>
                @php
                    $icon = function (string $name) {
                        return match ($name) {
                            'search' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>',
                            'x' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>',
                            'check' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m20 6-11 11-5-5"></path></svg>',
                            'upload' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 8 5-5 5 5"></path><path d="M5 19h14"></path></svg>',
                            'arrow-left' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path><path d="M9 12h10"></path></svg>',
                            'copy' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
                            'receipt' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path></svg>',
                            'download' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>',
                            'trash' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 15H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
                            default => '',
                        };
                    };
                @endphp
                @if($mode === 'payment')
                    @php
                        $selectedStudentId = (int) ($selectedStudentId ?? 0);
                        $selectedRegistrations = $selectedStudentId
                            ? $people->first(fn ($registrations) => $registrations->contains('id', $selectedStudentId))
                            : ($people->count() === 1 ? $people->first() : null);
                        $selectedIdentity = $selectedRegistrations
                            ? ($selectedRegistrations->firstWhere('identity_student_id', null) ?? $selectedRegistrations->first())
                            : null;
                        $createdReceipts = collect(session('payment_receipts', []));
                    @endphp
                    @if(session('success') && $createdReceipts->isNotEmpty())
                        <div hidden data-auto-receipts>
                            <script type="application/json" data-receipt-urls>@json($createdReceipts->pluck('receipt_url')->filter()->values())</script>
                        </div>
                    @elseif(session('success'))
                        <div class="result-modal-backdrop show" data-alert>
                            <div class="result-modal success-result">
                                <span class="result-icon">✓</span>
                                <strong>Sukses!</strong>
                                <p>{{ session('success') }}</p>
                                <button type="button" class="button button-primary" data-alert-close>OK</button>
                            </div>
                        </div>
                    @endif
                    <div class="payment-one-stop-layout">
                        <section class="payment-one-stop-main">
                            <div class="payment-one-stop-heading">
                                <div>
                                    <h1>Pembayaran</h1>
                                    <p>Cari siswa, pilih tagihan, lalu proses pembayaran.</p>
                                </div>
                                <a href="{{ route('finance.payments.import') }}" class="button action-purple payment-import-action">{!! $icon('upload') !!} Import</a>
                            </div>
                            <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-one-stop-search">
                                <label>
                                    <span class="payment-one-stop-search-field">
                                        <span class="payment-one-stop-search-icon">{!! $icon('search') !!}</span>
                                        <input type="search" name="search" value="{{ $search }}" placeholder="Ketik nama, NIS, atau NISN..." autofocus required>
                                        @if($search !== '')
                                            <a href="{{ route('finance.payments.index') }}" class="payment-one-stop-search-reset" aria-label="Reset pencarian">{!! $icon('x') !!}</a>
                                        @endif
                                    </span>
                                </label>
                            </form>

                            @if($search !== '' && $people->isNotEmpty() && ! $selectedRegistrations)
                                <div class="payment-one-stop-student-list">
                                    @foreach($people as $registrations)
                                        @php
                                            $identity = $registrations->firstWhere('identity_student_id', null) ?? $registrations->first();
                                            $isSelected = $selectedRegistrations && $selectedRegistrations->contains('id', $identity->id);
                                            $unitSummary = $registrations
                                                ->map(fn ($student) => trim(($student->schoolClass?->educationUnit?->code ?? '-') . ' ' . ($student->schoolClass?->name ?? '-')))
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
                                                <small>NIS: {{ $identity->nis }}{{ $unitSummary ? ' · '.$unitSummary : '' }}</small>
                                            </span>
                                            <span class="payment-one-stop-unit-count">{{ $statusLabel }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="payment-one-stop-side">
                            @if($search !== '')
                                @if($people->isEmpty())
                                    <div class="payment-one-stop-empty-state">
                                        <strong>Siswa tidak ditemukan</strong>
                                        <span>Periksa kembali nama, NIS, atau NISN yang dicari.</span>
                                    </div>
                                @elseif(! $selectedRegistrations)
                                    <div class="payment-one-stop-empty-state">
                                        <strong>Belum ada siswa yang dipilih</strong>
                                        <span>Pilih salah satu siswa dari hasil pencarian untuk melihat unit dan pilihan pembayaran.</span>
                                    </div>
                                @else
                                    @php
                                        $nisSummary = $selectedRegistrations->pluck('nis')->filter()->unique()->join('/');
                                        $unitNames = $selectedRegistrations
                                            ->map(fn ($student) => $student->schoolClass?->educationUnit?->name ?? $student->schoolClass?->educationUnit?->code)
                                            ->filter()
                                            ->unique()
                                            ->values();
                                        $classSummary = $selectedRegistrations
                                            ->map(fn ($student) => $student->schoolClass?->name)
                                            ->filter()
                                            ->unique()
                                            ->join('/');
                                        $studentStatusLabel = 'Aktif';
                                        $mandatoryRows = collect();
                                        $optionalRows = collect();

                                        foreach ($selectedRegistrations as $student) {
                                            $unitCode = $student->schoolClass?->educationUnit?->code ?? '-';
                                            foreach (collect($student->payment_options ?? []) as $option) {
                                                $label = $option['label'] === 'Lainnya' ? 'Lain-lain' : $option['label'];
                                                $mandatoryRows->push([
                                                    'name' => 'bill_keys[]',
                                                    'key' => $option['bill_key'],
                                                    'title' => $option['key'] === 'spp' ? trim('SPP '.$unitCode) : $label,
                                                    'detail' => $option['detail_label'] ?? '',
                                                    'amount' => (int) ($option['remaining_amount'] ?? 0),
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
                                        $billRows = $mandatoryBillRows->concat($optionalBillRows)->values();
                                        $hasOldSelection = old('bill_keys') !== null || old('optional_keys') !== null;
                                        $oldBillKeys = collect(old('bill_keys', []));
                                        $oldOptionalKeys = collect(old('optional_keys', []));
                                        $oldPaymentMonthCounts = collect(old('payment_month_counts', []));
                                        $defaultTotal = $billRows
                                            ->filter(fn ($row) => $row['name'] === 'optional_keys[]'
                                                ? ($hasOldSelection && $oldOptionalKeys->contains($row['key']))
                                                : ($hasOldSelection ? $oldBillKeys->contains($row['key']) : true))
                                            ->sum(function ($row) use ($oldPaymentMonthCounts) {
                                                $count = (int) $oldPaymentMonthCounts->get($row['mode_key'], $row['default_period_count']);
                                                $option = collect($row['period_options'])->firstWhere('count', $count);
                                                return (int) ($option['amount'] ?? $row['amount']);
                                            });
                                        $oldPaidDigits = preg_replace('/\D/', '', (string) old('paid_amount', $defaultTotal));
                                        $oldPaidLabel = $oldPaidDigits !== '' ? number_format((int) $oldPaidDigits, 0, ',', '.') : '';
                                        $oldPaymentMethod = $cashOnly ? 'Cash' : old('payment_method', 'Cash');
                                    @endphp
                                    <article class="payment-one-stop-person">
                                        <div class="payment-one-stop-person-head payment-one-stop-profile-card">
                                            <span class="payment-one-stop-student-icon" aria-hidden="true">
                                                <svg class="icon" viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                            </span>
                                            <div class="payment-one-stop-simple-profile">
                                                <span class="payment-one-stop-simple-nis">{{ $nisSummary ?: '-' }}</span>
                                                <strong>{{ strtoupper($selectedIdentity->name) }}</strong>
                                                <span class="payment-one-stop-simple-class">{{ $classSummary ?: '-' }}</span>
                                                <span class="payment-one-stop-simple-unit" aria-label="Unit pendidikan">
                                                    @foreach($unitNames as $item)
                                                        <span>{{ strtoupper($item) }}</span>
                                                    @endforeach
                                                </span>
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('finance.payments.store') }}" enctype="multipart/form-data" class="payment-one-stop-pay-form" data-payment-one-stop-form>
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $selectedIdentity->id }}">
                                            <input type="hidden" name="search" value="{{ $search }}">

                                            <section class="payment-one-stop-bills-card">
                                                <div class="payment-one-stop-bills-head">
                                                    <h2>Daftar Tagihan</h2>
                                                    <span @class(['is-administration-paid' => $mandatoryBillRows->isEmpty()])>
                                                        {{ $mandatoryBillRows->isEmpty() ? 'Lunas Administrasi' : $mandatoryBillRows->count().' Tagihan' }}
                                                    </span>
                                                </div>

                                                @if($errors->has('bill_keys'))
                                                    <div class="payment-one-stop-form-error">{{ $errors->first('bill_keys') }}</div>
                                                @endif

                                                @if($billRows->isEmpty())
                                                    <div class="payment-one-stop-empty">Tidak ada tagihan aktif untuk siswa ini.</div>
                                                @else
                                                    <div class="payment-one-stop-bill-list-reference">
                                                        @foreach($mandatoryBillRows as $row)
                                                            @php
                                                                $checked = $hasOldSelection ? $oldBillKeys->contains($row['key']) : true;
                                                                $selectedMonthCount = (int) $oldPaymentMonthCounts->get($row['mode_key'], $row['default_period_count']);
                                                                $selectedPeriodOption = collect($row['period_options'])->firstWhere('count', $selectedMonthCount);
                                                                $displayAmount = (int) ($selectedPeriodOption['amount'] ?? $row['amount']);
                                                                $displayDetail = $selectedPeriodOption['card_detail'] ?? $selectedPeriodOption['detail'] ?? $row['detail'];
                                                            @endphp
                                                            <div class="payment-one-stop-bill" data-payment-source-url="{{ $row['url'] }}" data-payment-bill-row>
                                                                <input
                                                                    type="checkbox"
                                                                    name="{{ $row['name'] }}"
                                                                    value="{{ $row['key'] }}"
                                                                    data-payment-bill
                                                                    data-amount="{{ $displayAmount }}"
                                                                    @checked($checked)
                                                                >
                                                                <span class="payment-one-stop-bill-top">
                                                                    <strong>{{ $row['title'] }}</strong>
                                                                    <span class="payment-one-stop-bill-detail" data-payment-bill-detail>{{ $displayDetail ?: 'Tagihan aktif' }}</span>
                                                                    @if($row['period_options'] !== [])
                                                                        <label class="payment-one-stop-period-select">
                                                                            <span>Bayar sampai</span>
                                                                            <select name="payment_month_counts[{{ $row['mode_key'] }}]" data-payment-period-select>
                                                                                @foreach($row['period_options'] as $periodOption)
                                                                                    <option value="{{ $periodOption['count'] }}" data-amount="{{ $periodOption['amount'] }}" data-detail="{{ $periodOption['card_detail'] ?? $periodOption['detail'] }}" @selected($selectedMonthCount === $periodOption['count'])>{{ $periodOption['detail'] }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </label>
                                                                    @endif
                                                                </span>
                                                                <span class="payment-one-stop-bill-amount">
                                                                    <span>Rp.</span>
                                                                    <strong data-payment-bill-amount>{{ number_format($displayAmount, 0, ',', '.') }},-</strong>
                                                                </span>
                                                            </div>
                                                        @endforeach

                                                        @if($optionalBillRows->isNotEmpty())
                                                            <div @class(['payment-one-stop-optional-section', 'is-only-optional' => $mandatoryBillRows->isEmpty()])>
                                                                <div class="payment-one-stop-optional-head">
                                                                    <strong>Pembayaran Opsional</strong>
                                                                    <span>{{ $optionalBillRows->count() }} Pilihan</span>
                                                                </div>

                                                                @foreach($optionalBillRows as $row)
                                                                        @php
                                                                            $checked = $hasOldSelection ? $oldOptionalKeys->contains($row['key']) : false;
                                                                            $selectedMonthCount = (int) $oldPaymentMonthCounts->get($row['mode_key'], $row['default_period_count']);
                                                                            $selectedPeriodOption = collect($row['period_options'])->firstWhere('count', $selectedMonthCount);
                                                                            $displayAmount = (int) ($selectedPeriodOption['amount'] ?? $row['amount']);
                                                                            $displayDetail = $selectedPeriodOption['card_detail'] ?? $selectedPeriodOption['detail'] ?? $row['detail'];
                                                                        @endphp
                                                                        <div class="payment-one-stop-bill is-optional" data-payment-source-url="{{ $row['url'] }}" data-payment-bill-row>
                                                                        <input
                                                                            type="checkbox"
                                                                            name="{{ $row['name'] }}"
                                                                            value="{{ $row['key'] }}"
                                                                            data-payment-bill
                                                                            data-amount="{{ $displayAmount }}"
                                                                            @checked($checked)
                                                                        >
                                                                        <span class="payment-one-stop-bill-top">
                                                                            <strong>{{ $row['title'] }}</strong>
                                                                            <span class="payment-one-stop-bill-detail" data-payment-bill-detail>{{ $displayDetail ?: 'Pembayaran opsional' }}</span>
                                                                            @if($row['period_options'] !== [])
                                                                                <label class="payment-one-stop-period-select">
                                                                                    <span>Bayar sampai</span>
                                                                                    <select name="payment_month_counts[{{ $row['mode_key'] }}]" data-payment-period-select>
                                                                                        @foreach($row['period_options'] as $periodOption)
                                                                                            <option value="{{ $periodOption['count'] }}" data-amount="{{ $periodOption['amount'] }}" data-detail="{{ $periodOption['card_detail'] ?? $periodOption['detail'] }}" @selected($selectedMonthCount === $periodOption['count'])>{{ $periodOption['detail'] }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </label>
                                                                            @endif
                                                                        </span>
                                                                        <span class="payment-one-stop-bill-amount">
                                                                            <span>Rp.</span>
                                                                            <strong data-payment-bill-amount>{{ number_format($displayAmount, 0, ',', '.') }},-</strong>
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <div class="payment-one-stop-bill-total">
                                                            <span>Total Tagihan:</span>
                                                            <span class="payment-one-stop-bill-total-amount">
                                                                <span>Rp.</span>
                                                                <b data-payment-total>{{ number_format($defaultTotal, 0, ',', '.') }},-</b>
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </section>

                                            <section class="payment-one-stop-payment-card">
                                                @if($errors->has('paid_amount') || $errors->has('payment_method') || $errors->has('transfer_proof'))
                                                    <div class="payment-one-stop-form-error">
                                                        {{ $errors->first('paid_amount') ?: ($errors->first('payment_method') ?: $errors->first('transfer_proof')) }}
                                                    </div>
                                                @endif

                                                <div class="payment-one-stop-form-controls">
                                                    <label>
                                                        <span>Pilih Metode Pembayaran</span>
                                                        <select name="payment_method" data-payment-method>
                                                            <option value="Cash" @selected($oldPaymentMethod === 'Cash')>Tunai</option>
                                                            @unless($cashOnly)
                                                            <option value="Transfer" @selected($oldPaymentMethod === 'Transfer')>Transfer Bank</option>
                                                            @endunless
                                                        </select>
                                                    </label>

                                                    @unless($cashOnly)
                                                    <div class="payment-one-stop-transfer-card" data-payment-transfer-panel @hidden($oldPaymentMethod !== 'Transfer')>
                                                        <div>
                                                            <span>Rekening Tujuan</span>
                                                            <strong>{{ $transferAccount['bank_name'] }} · {{ $transferAccount['account_number'] }}</strong>
                                                            <small>a.n. {{ $transferAccount['account_name'] }}</small>
                                                        </div>
                                                        <button type="button" data-payment-copy-account data-account-number="{{ $transferAccount['account_number'] }}">
                                                            {!! $icon('copy') !!}
                                                            <span>Salin Rekening</span>
                                                        </button>
                                                    </div>

                                                    <div class="payment-one-stop-transfer-upload" data-payment-transfer-upload @hidden($oldPaymentMethod !== 'Transfer')>
                                                        <span>Bukti Transfer</span>
                                                        <label class="payment-one-stop-upload-field">
                                                            <span class="payment-one-stop-upload-icon" aria-hidden="true">{!! $icon('upload') !!}</span>
                                                            <span class="payment-one-stop-upload-copy">
                                                                <strong data-payment-upload-name>Pilih file bukti transfer</strong>
                                                                <small>JPG, PNG, atau PDF maksimal 2 MB</small>
                                                            </span>
                                                            <input type="file" name="transfer_proof" accept=".jpg,.jpeg,.png,.pdf" data-payment-transfer-file>
                                                        </label>
                                                    </div>
                                                    @endunless

                                                    <label>
                                                        <span>Input Nominal Pembayaran (Rp)</span>
                                                        <input type="text" name="paid_amount" value="{{ $oldPaidLabel }}" inputmode="numeric" data-currency-input data-payment-paid-display>
                                                    </label>

                                                    <button class="payment-one-stop-pay-button" data-payment-submit @disabled($billRows->isEmpty())>Bayar Sekarang</button>
                                                </div>
                                            </section>
                                        </form>

                                        <section class="payment-one-stop-history-card">
                                            <div class="payment-one-stop-history-head">
                                                <h2>Riwayat Pembayaran</h2>
                                                <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-one-stop-history-filter">
                                                    <input type="hidden" name="search" value="{{ $search }}">
                                                    <input type="hidden" name="student_id" value="{{ $selectedIdentity->id }}">
                                                    <input type="month" name="history_period" value="{{ $historyPeriod }}" aria-label="Periode riwayat pembayaran" data-payment-history-period required>
                                                </form>
                                            </div>
                                            @if($paymentHistory->isEmpty())
                                                <div class="payment-one-stop-history-empty">
                                                    Belum ada riwayat pembayaran pada periode {{ $historyPeriodLabel }}.
                                                </div>
                                            @else
                                                <div class="payment-one-stop-history-list">
                                                    @foreach($paymentHistory as $history)
                                                        <div class="payment-one-stop-history-item">
                                                            <span class="payment-one-stop-history-copy">
                                                                <strong>{{ $history['title'] }}</strong>
                                                                <span>{{ $history['detail'] }}</span>
                                                                <small>{{ $history['date'] }} · {{ $history['method'] }}</small>
                                                            </span>
                                                            <span class="payment-one-stop-history-amount">
                                                                <span>Rp.</span>
                                                                <strong>{{ $history['amount_label'] }}</strong>
                                                            </span>
                                                            <span class="payment-one-stop-history-actions">
                                                                <a class="payment-one-stop-history-action" href="{{ $history['receipt_url'] }}" target="_blank" rel="noopener" title="Cetak struk" aria-label="Cetak struk">{!! $icon('receipt') !!}</a>
                                                                <a class="payment-one-stop-history-action" href="{{ $history['download_url'] }}" title="Download kwitansi" aria-label="Download kwitansi">{!! $icon('download') !!}</a>
                                                                <form method="POST" action="{{ $history['delete_url'] }}" onsubmit="return confirm('Hapus transaksi ini?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="return_url" value="{{ route('finance.payments.index', ['search' => $search, 'student_id' => $selectedIdentity->id, 'history_period' => $historyPeriod]) }}">
                                                                    <button class="payment-one-stop-history-action danger" type="submit" title="Hapus transaksi" aria-label="Hapus transaksi">{!! $icon('trash') !!}</button>
                                                                </form>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </section>

                                    </article>
                                @endif
                            @else
                                <div class="payment-one-stop-empty-state">
                                    <strong>Cari siswa terlebih dahulu</strong>
                                    <span>Gunakan nama, NIS, atau NISN. Jika siswa terdaftar di beberapa unit, semua unit akan muncul sebagai pilihan pembayaran.</span>
                                </div>
                            @endif
                        </section>
                    </div>
                @elseif($mode === 'history')
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>Riwayat Pembayaran</h1>
                            <p>Pilih jenis riwayat untuk melihat transaksi yang sudah tercatat.</p>
                        </div>
                        <div class="student-action-bar">
                            <a class="button student-add-button" href="{{ route('finance.payments.index') }}">Pembayaran</a>
                        </div>
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
                @else
                    <div class="payment-import-heading-copy">
                        <h1>{{ $mode === 'import-preview' ? 'Preview Impor Pembayaran' : 'Impor Pembayaran' }}</h1>
                        <p>{{ $mode === 'import-preview' ? 'Periksa data gagal sebelum mengimpor transaksi valid.' : 'Unggah data pembayaran dari file Excel untuk diperiksa sebelum disimpan.' }}</p>
                    </div>
                    <div class="payment-hub-heading-actions">
                        <a class="button button-secondary" href="{{ $mode === 'import-preview' ? route('finance.payments.import') : route('finance.payments.index') }}">{!! $icon('arrow-left') !!}<span>{{ $mode === 'import-preview' ? 'Kembali' : 'Pembayaran' }}</span></a>
                    </div>
                @endif
            </section>

            @if($mode === 'import')
                @php
                    $importTypes = [
                        ['spp', 'SPP', 'SPP', 'Pembayaran bulanan siswa.', route('finance.spp.import.preview')],
                        ['daftar-ulang', 'DU', 'Daftar Ulang', 'Pembayaran daftar ulang siswa.', route('finance.other.import.preview', ['category' => 'daftar-ulang'])],
                        ['laundry', 'LD', 'Laundry', 'Pembayaran laundry per bulan.', route('finance.other.import.preview', ['category' => 'laundry'])],
                        ['lain-lain', 'LL', 'Pembayaran Lain', 'Kategori pembayaran lainnya.', route('finance.other.import.preview')],
                    ];
                @endphp

                @if(session('success'))
                    <div class="payment-import-success" role="status">
                        {{ session('success') }}
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
                    <label class="payment-import-simple-field">
                        <span>Kategori Pembayaran</span>
                        <select data-payment-import-category>
                            @foreach($importTypes as [$key, $code, $title, $description, $action])
                                <option value="{{ $key }}" data-action="{{ $action }}">{{ $title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="payment-import-simple-field">
                        <span>File Excel</span>
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            data-payment-import-file
                        >
                        <small>Format XLSX, maksimal 10 MB.</small>
                    </label>

                    <div class="payment-import-simple-actions">
                        <button type="submit" class="button button-primary" disabled data-payment-import-submit>
                            <span class="payment-import-spinner" aria-hidden="true"></span>
                            {!! $icon('upload') !!}
                            <span data-payment-import-submit-label>Preview Data</span>
                        </button>
                    </div>
                </form>
            @elseif($mode === 'import-preview')
                @php
                    $previewType = $importPreviewType ?? 'spp';
                    $sectionTitle = $importSection['title'] ?? 'SPP';
                    $unresolvedSources = collect($importUnresolvedSources ?? []);
                    $previewImportAction = $importAction ?? route('finance.spp.import');
                    $canImport = $importPreview['valid'] > 0;
                @endphp
                <section class="payment-import-preview-panel">
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

                    <div class="payment-import-preview-top">
                        <div>
                            <strong>{{ number_format($importPreview['valid'], 0, ',', '.') }} transaksi siap diimpor</strong>
                            <span>{{ $sectionTitle }} · {{ number_format(count($importPreview['failures']), 0, ',', '.') }} gagal · {{ number_format($importPreview['duplicates'], 0, ',', '.') }} duplikat</span>
                        </div>
                        <form method="POST" action="{{ $previewImportAction }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $importToken }}">
                            <button class="button button-primary" @disabled(! $canImport)>
                                {!! $icon('check') !!}
                                Impor {{ number_format($importPreview['valid'], 0, ',', '.') }} Transaksi
                            </button>
                        </form>
                    </div>

                    @if(count($importPreview['failures']) > 0)
                        <div class="payment-import-preview-table-head">
                            <strong>Data Gagal</strong>
                            <span>{{ number_format(count($importPreview['failures']), 0, ',', '.') }} baris</span>
                        </div>
                        <div class="table-wrap payment-import-preview-table-wrap">
                            <table class="data-table payment-import-preview-table">
                                <thead><tr><th>Baris</th><th>NIS</th><th>Nama Siswa</th><th>{{ $previewType === 'spp' ? 'Periode' : 'Kategori' }}</th><th>Nominal</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                    @foreach($importPreview['failures'] as $row)
                                        <tr>
                                            <td>{{ $row['line'] }}</td>
                                            <td>{{ $row['nis'] }}</td>
                                            <td>{{ $row['name'] }}</td>
                                            <td>
                                                @if($previewType === 'spp')
                                                    {{ ucfirst($row['month_name']) }} {{ $row['year'] }}
                                                @else
                                                    {{ $row['category'] ?: '-' }}
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                            <td>{{ $row['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="payment-import-preview-empty">Tidak ada data gagal.</div>
                    @endif
                </section>
            @endif
        </main>
    </div>
</div>
</body>
</html>
