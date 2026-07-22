<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagihan - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'refresh' => '<path d="M21 12a9 9 0 0 1-15.5 6.2M3 12A9 9 0 0 1 18.5 5.8"/><path d="M21 4v6h-6M3 20v-6h6"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'upload' => '<path d="M12 16V4m0 0-4 4m4-4 4 4M4 20h16"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 12h8M8 16h6"/>',
        'package' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'coins' => '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1.5a1.5 1.5 0 0 1 0 3H7V6Zm0 3h2a1.5 1.5 0 0 1 0 3H7V9Z"/>',
        'bars' => '<path d="M3 3v18h18"/><path d="M7 16V9M12 16V5M17 16v-3"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $tableRupiah = fn ($amount) => '<span class="bill-money-pair"><span>Rp.</span><strong>'.number_format($amount, 0, ',', '.').',-</strong></span>';
    $tableNominal = fn ($amount) => '<span class="bill-money-number">'.number_format($amount, 0, ',', '.').',-</span>';
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $billQuery = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))
        ->filter(fn ($value) => is_scalar($value))
        ->all();
    $showingFrom = $studentsWithBills->total() > 0 ? $studentsWithBills->firstItem() : 0;
    $showingTo = $studentsWithBills->total() > 0 ? $studentsWithBills->lastItem() : 0;
    $isGuardianView = $isGuardianView ?? false;
    $guardianTotal = $isGuardianView ? (int) $guardianBills->sum('remaining_amount') : 0;
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

        <main @class(['student-page', 'bill-page', 'bill-flat-page', 'payment-transaction-page', 'payment-flat-page', 'guardian-bill-page' => $isGuardianView])>
            @if(session('success'))
                <div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif
            @if($errors->any())
                <div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif

            <section class="bill-workspace">
                <div class="bill-page-heading">
                    @unless($isGuardianView)
                        <span class="bill-heading-icon" aria-hidden="true">{!! $icon('file') !!}</span>
                    @endunless
                    <div>
                        <h1>{{ $isGuardianView ? 'Tagihan' : 'Tagihan Siswa' }}</h1>
                        <p>{{ $isGuardianView ? 'Lihat tagihan siswa yang terhubung dan kirim bukti pembayaran transfer.' : 'Pantau tagihan semua siswa, rincian SPP, pembayaran lain-lain, dan sisa kewajiban.' }}</p>
                    </div>
                    @unless($isGuardianView)
                        <span class="bill-heading-date">
                            <small>Terakhir diperbarui</small>
                            <strong>{{ now()->format('d/m/Y') }}</strong>
                        </span>
                    @endunless
                </div>

                @if($isGuardianView)
                    @php
                        $selectedGuardianStudent = $guardianStudents->firstWhere('id', (int) $selectedGuardianStudentId) ?? $guardianStudents->first();
                        $guardianUnit = $selectedGuardianStudent?->schoolClass?->educationUnit;
                        $guardianClass = $selectedGuardianStudent?->schoolClass;
                        $guardianClassSummary = collect([$guardianUnit?->code, $guardianClass?->name])->filter()->implode(' · ');
                    @endphp

                    @if($selectedGuardianStudent)
                    @php
                        $periodRange = function ($bills) use ($months) {
                            $periods = $bills
                                ->filter(fn ($bill) => $bill->month && $bill->year)
                                ->sortBy(fn ($bill) => ((int) $bill->year * 100) + (int) $bill->month)
                                ->values();

                            if ($periods->isEmpty()) {
                                return 'Tagihan aktif';
                            }

                            $first = $periods->first();
                            $last = $periods->last();
                            $firstLabel = ($months[(int) $first->month] ?? 'Bulan').' '.$first->year;
                            $lastLabel = ($months[(int) $last->month] ?? 'Bulan').' '.$last->year;

                            return $firstLabel === $lastLabel ? $firstLabel : $firstLabel.' - '.$lastLabel;
                        };
                        $guardianBillRows = collect();
                        $sppBills = $guardianBills
                            ->where('source_type', 'spp')
                            ->sortBy(fn ($bill) => ((int) $bill->year * 100) + (int) $bill->month)
                            ->values();

                        if ($sppBills->isNotEmpty()) {
                            $runningAmount = 0;
                            $periodOptions = [];
                            foreach ($sppBills as $index => $bill) {
                                $selectedBills = $sppBills->take($index + 1)->values();
                                $runningAmount += (int) $bill->remaining_amount;
                                $periodOptions[] = [
                                    'count' => $index + 1,
                                    'amount' => $runningAmount,
                                    'detail' => $periodRange($selectedBills),
                                    'label' => ($months[(int) $bill->month] ?? 'Bulan').' '.$bill->year,
                                    'bill_ids' => $selectedBills->pluck('id')->values()->all(),
                                ];
                            }
                            $defaultOption = collect($periodOptions)->last();
                            $guardianBillRows->push([
                                'title' => trim('SPP '.($guardianUnit?->code ?? '')),
                                'detail' => $defaultOption['detail'] ?? 'Tagihan aktif',
                                'amount' => (int) ($defaultOption['amount'] ?? 0),
                                'bill_ids' => $defaultOption['bill_ids'] ?? [],
                                'period_options' => $periodOptions,
                                'default_count' => (int) ($defaultOption['count'] ?? 1),
                            ]);
                        }

                        foreach ($guardianBills->where('source_type', '!=', 'spp')->values() as $bill) {
                            $periodLabel = $bill->month ? ($months[(int) $bill->month] ?? 'Bulan').' '.$bill->year : ($bill->year ?? 'Tagihan aktif');
                            $guardianBillRows->push([
                                'title' => $bill->title,
                                'detail' => collect([$bill->feeType?->name, $periodLabel, $bill->displayStatus()])->filter()->implode(' · '),
                                'amount' => (int) $bill->remaining_amount,
                                'bill_ids' => [$bill->id],
                                'period_options' => [],
                                'default_count' => 1,
                            ]);
                        }

                        $guardianDefaultTotal = (int) $guardianBillRows->sum('amount');
                    @endphp
                    <article class="payment-one-stop-person guardian-payment-portal">
                        <div class="payment-one-stop-person-head payment-one-stop-profile-card">
                            <span class="payment-one-stop-student-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </span>
                            <div class="payment-one-stop-simple-profile">
                                <span class="payment-one-stop-simple-nis">{{ $selectedGuardianStudent->nis ?: '-' }}</span>
                                <strong>{{ strtoupper($selectedGuardianStudent->name) }}</strong>
                                <span class="payment-one-stop-simple-class">{{ $guardianClassSummary ?: '-' }}</span>
                                <span class="payment-one-stop-simple-unit" aria-label="Unit pendidikan">
                                    <span>{{ strtoupper($guardianUnit?->name ?? '-') }}</span>
                                </span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('finance.bills.transfer') }}" enctype="multipart/form-data" class="payment-one-stop-pay-form guardian-transfer-form" data-payment-one-stop-form>
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $selectedGuardianStudent->id }}">
                            <input type="hidden" name="payment_method" value="Transfer" data-payment-method>

                            <section class="payment-one-stop-bills-card">
                                <div class="payment-one-stop-bills-head">
                                    <h2>Daftar Tagihan</h2>
                                    <span @class(['is-administration-paid' => $guardianBillRows->isEmpty()])>
                                        {{ $guardianBillRows->isEmpty() ? 'Lunas Administrasi' : $guardianBillRows->count().' Tagihan' }}
                                    </span>
                                </div>

                                @if($errors->has('bill_ids'))
                                    <div class="payment-one-stop-form-error">{{ $errors->first('bill_ids') }}</div>
                                @endif

                                @if($guardianBillRows->isEmpty())
                                    <div class="payment-one-stop-empty">Tidak ada tagihan aktif untuk siswa ini.</div>
                                @else
                                    <div class="payment-one-stop-bill-list-reference">
                                        @foreach($guardianBillRows as $row)
                                            <div class="payment-one-stop-bill" data-payment-bill-row>
                                                <input
                                                    type="checkbox"
                                                    data-payment-bill
                                                    data-amount="{{ (int) $row['amount'] }}"
                                                    data-bill-ids='@json($row['bill_ids'])'
                                                    checked
                                                >
                                                <span data-payment-bill-ids>
                                                    @foreach($row['bill_ids'] as $billId)
                                                        <input type="hidden" name="bill_ids[]" value="{{ $billId }}">
                                                    @endforeach
                                                </span>
                                                <span class="payment-one-stop-bill-top">
                                                    <strong>{{ $row['title'] }}</strong>
                                                    <span class="payment-one-stop-bill-detail" data-payment-bill-detail>{{ $row['detail'] }}</span>
                                                    @if($row['period_options'] !== [])
                                                        <label class="payment-one-stop-period-select">
                                                            <span>Bayar sampai</span>
                                                            <select name="guardian_period_counts[{{ $loop->index }}]" data-payment-period-select>
                                                                @foreach($row['period_options'] as $periodOption)
                                                                    <option value="{{ $periodOption['count'] }}" data-amount="{{ $periodOption['amount'] }}" data-detail="{{ $periodOption['detail'] }}" data-bill-ids='@json($periodOption['bill_ids'])' @selected($row['default_count'] === $periodOption['count'])>{{ $periodOption['label'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                    @endif
                                                </span>
                                                <span class="payment-one-stop-bill-amount">
                                                    <span>Rp.</span>
                                                    <strong data-payment-bill-amount>{{ number_format($row['amount'], 0, ',', '.') }},-</strong>
                                                </span>
                                            </div>
                                        @endforeach

                                        <div class="payment-one-stop-bill-total">
                                            <span>Total Tagihan:</span>
                                            <span class="payment-one-stop-bill-total-amount">
                                                <span>Rp.</span>
                                                <b data-payment-total>{{ number_format($guardianDefaultTotal, 0, ',', '.') }},-</b>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </section>

                            <section class="payment-one-stop-payment-card">
                                @if($errors->has('proof'))
                                    <div class="payment-one-stop-form-error">{{ $errors->first('proof') }}</div>
                                @endif

                                <div class="payment-one-stop-form-controls">
                                    <label>
                                        <span>Metode Pembayaran</span>
                                        <input type="text" value="Transfer Bank" readonly>
                                    </label>

                                    <div class="guardian-cash-note">Pembayaran tunai dilayani langsung di kantor.</div>

                                    <div class="payment-one-stop-transfer-card" data-payment-transfer-panel>
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

                                    <div class="payment-one-stop-transfer-upload" data-payment-transfer-upload>
                                        <span>Bukti Transfer</span>
                                        <label class="payment-one-stop-upload-field">
                                            <span class="payment-one-stop-upload-icon" aria-hidden="true">{!! $icon('upload') !!}</span>
                                            <span class="payment-one-stop-upload-copy">
                                                <strong data-payment-upload-name>Pilih file bukti transfer</strong>
                                                <small>JPG, PNG, atau PDF maksimal 4 MB</small>
                                            </span>
                                            <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" data-payment-transfer-file @required($guardianBills->isNotEmpty())>
                                        </label>
                                    </div>

                                    <label>
                                        <span>Nominal Transfer (Rp)</span>
                                        <input type="text" value="{{ number_format($guardianDefaultTotal, 0, ',', '.') }}" inputmode="numeric" readonly data-currency-input data-currency-disabled="true" data-payment-paid-display>
                                    </label>

                                    <button class="payment-one-stop-pay-button" data-payment-submit @disabled($guardianBillRows->isEmpty())>Kirim Bukti Transfer</button>
                                </div>
                            </section>
                        </form>
                    </article>
                    @else
                    <section class="payment-one-stop-empty-state">
                        <strong>Belum ada santri terhubung.</strong>
                        <span>Silakan masuk ulang memakai unit pendidikan dan NIS yang sesuai.</span>
                    </section>
                    @endif

                <section class="payment-one-stop-history-card guardian-history-card">
                    <div class="payment-one-stop-history-head">
                        <h2>Riwayat Pembayaran</h2>
                    </div>

                    @if($guardianTransfers->isEmpty())
                        <div class="payment-one-stop-history-empty">
                            Belum ada riwayat pembayaran.
                        </div>
                    @else
                        <div class="payment-one-stop-history-list">
                            @foreach($guardianTransfers as $transfer)
                                <div class="payment-one-stop-history-item">
                                    <span class="payment-one-stop-history-copy">
                                        <strong>Transfer Tagihan</strong>
                                        <span>{{ $transfer->student?->nis }} - {{ $transfer->student?->name }}</span>
                                        <small>{{ $transfer->created_at->format('d/m/Y H.i') }} · {{ $transfer->status }}</small>
                                        @if($transfer->rejected_reason)
                                            <small>Catatan: {{ $transfer->rejected_reason }}</small>
                                        @endif
                                    </span>
                                    <span class="payment-one-stop-history-amount">
                                        <span>Rp.</span>
                                        <strong>{{ number_format($transfer->amount, 0, ',', '.') }}</strong>
                                    </span>
                                    <span class="payment-one-stop-history-actions">
                                        <span class="bill-status {{ $transfer->status === 'Diterima' ? 'is-paid' : '' }}">{{ $transfer->status }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
                @else
                @php
                    $unitSummaryTotals = [
                        'students' => (int) $unitSummaries->sum('students'),
                        'spp' => (int) $unitSummaries->sum('spp'),
                        'daftar_ulang' => (int) $unitSummaries->sum('daftar_ulang'),
                        'lain_lain' => (int) $unitSummaries->sum('lain_lain'),
                        'remaining' => (int) $unitSummaries->sum('remaining'),
                    ];
                    $billOverviewCards = [
                        ['label' => 'Total Siswa', 'value' => number_format($unitSummaryTotals['students'], 0, ',', '.'), 'icon' => 'users', 'tone' => 'neutral'],
                        ['label' => 'SPP', 'value' => $rupiah($unitSummaryTotals['spp']), 'icon' => 'file', 'tone' => 'primary'],
                        ['label' => 'Daftar Ulang', 'value' => $rupiah($unitSummaryTotals['daftar_ulang']), 'icon' => 'clipboard', 'tone' => 'gold'],
                        ['label' => 'Lain-lain', 'value' => $rupiah($unitSummaryTotals['lain_lain']), 'icon' => 'package', 'tone' => 'muted'],
                        ['label' => 'Total Tagihan', 'value' => $rupiah($unitSummaryTotals['remaining']), 'icon' => 'coins', 'tone' => 'total'],
                    ];
                    $totalUnitRemaining = max(1, (int) $unitSummaryTotals['remaining']);
                @endphp

                <section class="bill-visual-overview" aria-label="Ringkasan tagihan">
                    @foreach($billOverviewCards as $card)
                        <article class="bill-overview-card is-{{ $card['tone'] }}">
                            <span class="bill-overview-icon">{!! $icon($card['icon']) !!}</span>
                            <span class="bill-overview-copy">
                                <small>{{ $card['label'] }}</small>
                                <strong>{{ $card['value'] }}</strong>
                            </span>
                        </article>
                    @endforeach
                </section>

                <section class="bill-unit-progress-card" aria-label="Tagihan per unit">
                    <div class="bill-unit-progress-head">
                        <span class="bill-overview-icon">{!! $icon('bars') !!}</span>
                        <div>
                            <h2>Tagihan per Unit</h2>
                            <p>Total sisa kewajiban berdasarkan unit pendidikan.</p>
                        </div>
                    </div>
                    <div class="bill-unit-progress-list">
                        @forelse($unitSummaries as $unitSummary)
                            @php
                                $unitRemaining = (int) $unitSummary['remaining'];
                                $unitPercentRaw = $totalUnitRemaining > 0 ? ($unitRemaining / $totalUnitRemaining) * 100 : 0;
                                $unitPercent = min(100, max(0, round($unitPercentRaw)));
                                $unitBarPercent = $unitRemaining > 0 ? max(1, $unitPercent) : 0;
                                $unitPercentLabel = $unitRemaining > 0 && $unitPercentRaw < 1 ? '<1%' : $unitPercent.'%';
                            @endphp
                            <div class="bill-unit-progress-row">
                                <div class="bill-unit-progress-meta">
                                    <strong>{{ $unitSummary['unit_name'] }}</strong>
                                    <b>{{ $unitPercentLabel }} <em>({{ $rupiah($unitRemaining) }})</em></b>
                                </div>
                                <div class="bill-unit-progress-track" aria-hidden="true">
                                    <span style="width: {{ $unitBarPercent }}%"></span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada ringkasan tagihan per unit.</div>
                        @endforelse
                    </div>
                </section>
                @endif

                @unless($isGuardianView)
                <form method="GET" action="{{ route('finance.bills.index') }}" class="bills-filter-panel">
                    <label class="bill-search-field"><span>Cari Siswa</span><span class="bill-search-input">{!! $icon('search') !!}<input type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Nama atau NIS..."></span></label>
                    <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                    <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                    <div class="bill-filter-actions">
                        <a href="{{ route('finance.bills.index') }}" class="button bill-filter-reset">Reset</a>
                        <button class="button bill-filter-apply">Terapkan</button>
                    </div>
                </form>

                <div class="bill-table-toolbar">
                    <form method="GET" action="{{ route('finance.bills.index') }}" class="bill-page-size-form">
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            @if(is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label>Tampilkan
                            <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100, 500] as $size)
                                    <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>
                                @endforeach
                                <option value="all" @selected(request('per_page') === 'all')>All</option>
                            </select>
                            data
                        </label>
                    </form>
                    <span>Menampilkan {{ number_format($showingFrom, 0, ',', '.') }}-{{ number_format($showingTo, 0, ',', '.') }} dari {{ number_format($studentsWithBills->total(), 0, ',', '.') }} siswa</span>
                </div>

                <section class="bills-data-card">
                    <div class="table-wrap">
                        <table class="bill-flat-table bill-stitch-table">
                            <colgroup>
                                <col class="bill-stitch-col-name">
                                <col class="bill-stitch-col-unit">
                                <col class="bill-stitch-col-class">
                                <col class="bill-stitch-col-register">
                                <col class="bill-stitch-col-spp">
                                <col class="bill-stitch-col-other">
                                <col class="bill-stitch-col-total">
                                <col class="bill-stitch-col-action">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nama Siswa</th>
                                    <th>Unit</th>
                                    <th>Kelas</th>
                                    <th>Daftar Ulang</th>
                                    <th>SPP</th>
                                    <th>Lain-lain</th>
                                    <th>Total Tagihan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentsWithBills as $summary)
                                    @php($student = $summary['student'])
                                    <tr class="bill-main-row">
                                        <td>
                                            <span class="bill-student-name">{{ $student?->name }}</span>
                                            <small>{{ $student?->nis ?: '-' }}</small>
                                        </td>
                                        <td><span class="bill-unit-chip">{{ $student?->schoolClass?->educationUnit?->code ?? '-' }}</span></td>
                                        <td>{{ $student?->schoolClass?->name ?? '-' }}</td>
                                        <td><span class="bill-money remaining">{{ $rupiah($summary['daftar_ulang']) }}</span></td>
                                        <td><span class="bill-money remaining">{{ $rupiah($summary['spp']) }}</span></td>
                                        <td><span class="bill-money remaining">{{ $rupiah($summary['lain_lain']) }}</span></td>
                                        <td><span class="bill-money remaining">{{ $rupiah($summary['total_remaining']) }}</span></td>
                                        <td>
                                            <div class="bill-table-actions">
                                                <a href="{{ $student ? route('finance.bills.show', array_merge(request()->only(['unit_id', 'class_id', 'student_id', 'student_search', 'per_page', 'sort', 'direction']), ['student' => $student->id, 'year' => $year, 'until_month' => $untilMonth])) : '#' }}" class="button bill-detail-trigger" aria-label="Detail tagihan" title="Detail" target="_blank" rel="noopener">{!! $icon('eye') !!}</a>
                                                <a href="{{ route('finance.payments.index', ['student_id' => $student?->id, 'search' => $student?->nis]) }}" class="bill-pay-short" aria-label="Bayar tagihan" title="Bayar">{!! $icon('wallet') !!}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="empty-state">Belum ada tagihan pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                @endunless
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
