<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'import' ? 'Import Pembayaran' : ($mode === 'history' ? 'Riwayat Pembayaran' : 'Transaksi Baru') }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'payment',
        'activePaymentMenu' => $mode === 'history' ? 'history' : ($mode === 'import' ? 'import' : 'transaction'),
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">☰</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
        </header>

        <main @class(['payment-hub-page', 'payment-transaction-page' => $mode === 'payment', 'student-page payment-flat-page' => in_array($mode, ['payment', 'history'], true)])>
            <section @class(['payment-hub-heading' => $mode === 'import', 'student-workspace payment-transaction-workspace' => $mode === 'payment', 'student-workspace payment-history-workspace' => $mode === 'history'])>
                @if($mode === 'payment')
                    @php
                        $selectedStudentId = (int) ($selectedStudentId ?? 0);
                        $selectedRegistrations = $selectedStudentId
                            ? $people->first(fn ($registrations) => $registrations->contains('id', $selectedStudentId))
                            : ($people->count() === 1 ? $people->first() : null);
                        $selectedIdentity = $selectedRegistrations
                            ? ($selectedRegistrations->firstWhere('identity_student_id', null) ?? $selectedRegistrations->first())
                            : null;
                        $icon = function (string $name) {
                            return match ($name) {
                                'search' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>',
                                'x' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>',
                                'check' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m20 6-11 11-5-5"></path></svg>',
                                'upload' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 8 5-5 5 5"></path><path d="M5 19h14"></path></svg>',
                                'copy' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
                                'receipt' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path></svg>',
                                'download' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>',
                                'trash' => '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 15H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
                                default => '',
                            };
                        };
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
                                <h1>Transaksi Baru</h1>
                                <p>Cari siswa, pilih tagihan, lalu proses pembayaran dari satu halaman.</p>
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
                                                $count = (int) $oldPaymentMonthCounts->get($row['mode_key'], 1);
                                                $option = collect($row['period_options'])->firstWhere('count', $count);
                                                return (int) ($option['amount'] ?? $row['amount']);
                                            });
                                        $oldPaidDigits = preg_replace('/\D/', '', (string) old('paid_amount', $defaultTotal));
                                        $oldPaidLabel = $oldPaidDigits !== '' ? number_format((int) $oldPaidDigits, 0, ',', '.') : '';
                                        $oldPaymentMethod = old('payment_method', 'Cash');
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
                                                                $selectedMonthCount = (int) $oldPaymentMonthCounts->get($row['mode_key'], 1);
                                                                $selectedPeriodOption = collect($row['period_options'])->firstWhere('count', $selectedMonthCount);
                                                                $displayAmount = (int) ($selectedPeriodOption['amount'] ?? $row['amount']);
                                                                $displayDetail = $selectedPeriodOption['detail'] ?? $row['detail'];
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
                                                                                    <option value="{{ $periodOption['count'] }}" data-amount="{{ $periodOption['amount'] }}" data-detail="{{ $periodOption['detail'] }}" @selected($selectedMonthCount === $periodOption['count'])>{{ $periodOption['detail'] }}</option>
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
                                                                            $selectedMonthCount = (int) $oldPaymentMonthCounts->get($row['mode_key'], 1);
                                                                            $selectedPeriodOption = collect($row['period_options'])->firstWhere('count', $selectedMonthCount);
                                                                            $displayAmount = (int) ($selectedPeriodOption['amount'] ?? $row['amount']);
                                                                            $displayDetail = $selectedPeriodOption['detail'] ?? $row['detail'];
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
                                                                                            <option value="{{ $periodOption['count'] }}" data-amount="{{ $periodOption['amount'] }}" data-detail="{{ $periodOption['detail'] }}" @selected($selectedMonthCount === $periodOption['count'])>{{ $periodOption['detail'] }}</option>
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
                                                            <option value="Transfer" @selected($oldPaymentMethod === 'Transfer')>Transfer Bank</option>
                                                        </select>
                                                    </label>

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
                            <a class="button student-add-button" href="{{ route('finance.payments.index') }}">Transaksi Baru</a>
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
                    <div>
                        <p class="eyebrow">Pembayaran</p>
                        <h1>Import Pembayaran</h1>
                        <p>Pilih jenis pembayaran, lalu unggah file Excel lama untuk diperiksa.</p>
                    </div>
                    <div class="payment-hub-heading-actions">
                        <a class="button button-secondary" href="{{ route('finance.payments.index') }}">Transaksi Baru</a>
                    </div>
                @endif
            </section>

            @if($mode === 'import')
                <section class="payment-import-grid">
                    @foreach([
                        ['spp', 'SPP', 'Pembayaran bulanan SPP.', route('finance.spp.import.preview')],
                        ['daftar-ulang', 'Daftar Ulang', 'Pembayaran daftar ulang siswa.', route('finance.other.import.preview', ['category' => 'daftar-ulang'])],
                        ['laundry', 'Laundry', 'Hanya transaksi bulan yang benar-benar diikuti.', route('finance.other.import.preview', ['category' => 'laundry'])],
                        ['lain-lain', 'Pembayaran Lain', 'Kategori pembayaran selain SPP, daftar ulang, dan laundry.', route('finance.other.import.preview')],
                    ] as [$key, $title, $description, $action])
                        <article class="card payment-import-card" data-import-category="{{ $key }}">
                            <div class="payment-import-icon">{{ strtoupper(substr($title, 0, 1)) }}</div>
                            <div><h2>{{ $title }}</h2><p>{{ $description }}</p></div>
                            <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                                @csrf
                                <label>
                                    <span>Pilih file Excel</span>
                                    <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                                </label>
                                <button class="button button-primary">Upload dan Preview</button>
                            </form>
                        </article>
                    @endforeach
                </section>
                <div class="payment-import-note"><strong>Sebelum data disimpan</strong><span>Sistem tetap menampilkan preview Valid, Duplikat, dan Gagal. Kolom Unit Pendidikan dan NIS digunakan untuk menemukan siswa yang tepat.</span></div>
            @endif
        </main>
    </div>
</div>
</body>
</html>
