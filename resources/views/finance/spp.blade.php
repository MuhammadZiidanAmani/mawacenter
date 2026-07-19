<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran SPP - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'grid' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'database' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'finance' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M7 5V3h10v2M3 10h18M7 15h3"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'upload' => '<path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $paymentQuery = fn (array $except = []) => collect(request()->except(array_merge($except, ['page'])))
        ->filter(fn ($value) => is_scalar($value))
        ->all();
    $nativeDateValue = function ($value, $fallback = null) {
        $value = filled($value) ? (string) $value : (string) $fallback;
        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            try {
                $date = \Carbon\CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) return $date->format('Y-m-d');
            } catch (\Throwable) {
                //
            }
        }

        return $fallback ?? now()->toDateString();
    };
    $nativeTimeValue = function ($value, $fallback = null) {
        $value = filled($value) ? (string) $value : (string) $fallback;
        if (preg_match('/^([01]?\d|2[0-3])[.:]([0-5]\d)/', $value, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            return "{$hour}:{$matches[2]}";
        }

        return now()->format('H:i');
    };
    $filterDateFrom = $nativeDateValue(request('date_from'), now()->toDateString());
    $filterDateTo = $nativeDateValue(request('date_to'), now()->toDateString());
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'payment', 'activePaymentMenu' => $showCreate ? 'transaction' : 'history'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>
        <main @class(['finance-page' => $showCreate, 'student-page payment-flat-page' => ! $showCreate])>
            @php
                $paymentAction = session('payment_action');
            @endphp
            @if($paymentAction)<div class="result-modal-backdrop show" data-alert><div class="result-modal success-result payment-action-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><div class="payment-result-actions"><a href="{{ $paymentAction['receipt_url'] }}" target="_blank" class="button button-primary">Cetak</a><a href="{{ $paymentAction['download_url'] }}" class="button button-secondary">Unduh PDF</a><a href="{{ $paymentAction['back_url'] }}" class="button button-secondary">Kembali</a></div></div></div>@elseif(session('success'))<div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @if($errors->any())<div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @unless($showCreate)
            <div>
                <section class="student-workspace payment-workspace payment-single-canvas">
                    <div class="student-flat-header">
                        <h1>Riwayat SPP</h1>
                        <div class="student-title-actions">
                            <a href="{{ route('finance.payments.history') }}" class="button button-secondary">Kembali</a>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('finance.spp.index') }}" class="student-filter-panel payment-filter-panel payment-filter-compact">
                        <div class="payment-filter-primary">
                            <label class="payment-date-filter"><span>Waktu</span><span class="payment-date-range"><input type="date" name="date_from" value="{{ $filterDateFrom }}"><b>-</b><input type="date" name="date_to" value="{{ $filterDateTo }}"></span></label>
                            <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                            <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                            <label class="payment-student-field"><span>Siswa</span><span class="payment-student-filter"><span class="student-search-picker" data-student-picker data-student-optional><input type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" data-student-search><select name="student_id" data-student-source><option value="">Semua siswa</option>@foreach($studentOptions as $student)<option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select><span class="student-search-results" data-student-results hidden></span></span></span></label>
                            <div class="student-filter-actions payment-filter-actions">
                                <button class="button student-search-button" aria-label="Cari">{!! $icon('search') !!}</button>
                                <a href="{{ route('finance.spp.index') }}" class="button student-filter-reset">Reset</a>
                            </div>
                        </div>
                        <details class="payment-advanced-filters" @if(request()->filled('payment_method') || request()->filled('status') || request()->filled('operator_name')) open @endif>
                            <summary>Filter Lainnya</summary>
                            <div class="payment-filter-secondary">
                                <label><span>Kategori Pembayaran</span><select disabled><option>SPP</option></select></label>
                                <label><span>Cara Bayar</span><select name="payment_method"><option value="">semua</option><option value="Cash" @selected(request('payment_method') === 'Cash')>Cash</option><option value="Transfer" @selected(request('payment_method') === 'Transfer')>Transfer</option></select></label>
                                <label><span>Status</span><select name="status"><option value="">semua</option><option value="Diterima" @selected(request('status') === 'Diterima')>Diterima</option><option value="Pending" @selected(request('status') === 'Pending')>Pending</option></select></label>
                                <label><span>Petugas</span><select name="operator_name"><option value="">semua</option>@foreach($operators as $operator)<option value="{{ $operator }}" @selected(request('operator_name') === $operator)>{{ $operator }}</option>@endforeach</select></label>
                            </div>
                        </details>
                        @foreach($paymentQuery(['date_from', 'date_to', 'payment_method', 'status', 'operator_name', 'unit_id', 'class_id', 'nis', 'student_id', 'student_search']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                <div class="student-data-card payment-data-card spp-history">
                    @include('partials.list-toolbar', ['action' => route('finance.spp.index'), 'searchLabel' => 'Cari pembayaran SPP'])
                    <div class="table-wrap"><table class="data-table student-flat-table payment-flat-table spp-list-table registration-payment-table"><thead><tr><th>No</th><th>@include('partials.sortable-heading', ['column' => 'nis', 'label' => 'NIS'])</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama'])</th><th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'method', 'label' => 'Cara Bayar'])</th><th>@include('partials.sortable-heading', ['column' => 'total', 'label' => 'Total'])</th><th>Rincian</th></tr></thead><tbody>
                        @forelse($payments as $payment)
                            @php
                                $sppPeriodText = $payment->items
                                    ->sortBy(fn($item) => ((int) $item->year * 100) + (int) $item->month)
                                    ->groupBy('year')
                                    ->map(fn($items, $year) => $items->map(fn($item) => $months[$item->month])->join(', ').' '.$year)
                                    ->join('; ');
                            @endphp
                            <tr class="spp-main-row">
                                <td>{{ $payments->firstItem()+$loop->index }}</td><td>{{ $payment->student?->nis }}</td><td><span class="payment-student-name">{{ $payment->student?->name }}</span><small class="payment-student-unit">Unit Pendidikan: {{ $payment->student?->schoolClass?->educationUnit?->code ?? '-' }}</small></td><td class="spp-class-cell">{{ $payment->student?->schoolClass?->name ?? '-' }}</td><td><span class="payment-method">{{ strtolower($payment->payment_method) }}</span></td><td class="spp-total-cell"><span>Rp {{ number_format($payment->paid_amount,0,',','.') }}</span></td><td><button type="button" class="spp-expand-button" data-spp-row-toggle="{{ $payment->id }}" aria-expanded="false">Lihat</button></td>
                            </tr>
                            <tr class="spp-expanded-row" data-spp-row-detail="{{ $payment->id }}" hidden><td colspan="7">
                                <div class="registration-payment-detail spp-payment-detail">
                                    <div class="registration-detail-item payment-type"><span>Bulan</span><strong>{{ $sppPeriodText }}</strong></div>
                                    <div class="registration-detail-item"><span>Cara Bayar</span><strong><span class="compact-badge method">{{ strtolower($payment->payment_method) }}</span></strong></div>
                                    <div class="registration-detail-item"><span>Status</span><strong><span class="compact-badge {{ $payment->status === 'Diterima' ? 'success' : 'neutral' }}">{{ strtolower($payment->status) }}</span></strong></div>
                                    <div class="registration-detail-item"><span>Nominal</span><strong>Rp {{ number_format($payment->paid_amount,0,',','.') }}</strong></div>
                                    <div class="registration-detail-item time"><span>Waktu</span><strong>{{ $payment->transaction_at->format('Y-m-d H:i:s') }}</strong></div>
                                    <div class="registration-detail-item"><span>Petugas</span><strong>{{ $payment->operator_name ?: '-' }}</strong></div>
                                    <div class="registration-detail-item"><span>Pembayaran</span><strong>{{ $payment->payment_status }}</strong></div>
                                    <div class="registration-detail-item action"><span>Aksi</span><div class="registration-actions spp-compact-actions"><a href="{{ route('finance.spp.receipt', $payment) }}" target="_blank" class="registration-action print" title="Cetak Struk" aria-label="Cetak Struk"><svg class="icon" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg></a><button type="button" class="registration-action edit" title="Edit Transaksi" aria-label="Edit Transaksi" data-spp-edit-url="{{ route('finance.spp.show', $payment) }}" data-spp-update-url="{{ route('finance.spp.update', $payment) }}"><svg class="icon" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></button><button type="button" class="registration-action delete" title="Hapus Transaksi" aria-label="Hapus Transaksi" data-spp-delete-url="{{ route('finance.spp.destroy', $payment) }}" data-spp-delete-name="{{ $payment->student?->name }}"><svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/></svg></button></div></div>
                                </div>
                            </td></tr>
                        @empty <tr><td colspan="7" class="empty-state">Belum ada pembayaran SPP.</td></tr> @endforelse
                    </tbody></table></div><div class="pagination-wrap">{{ $payments->links() }}</div>
                </div>
                </section>
            </div>
            @else
            @php
                $isEditingSpp = isset($editPayment) && $editPayment;
                $sppFormAction = $isEditingSpp ? route('finance.spp.update', $editPayment) : route('finance.spp.store');
                $sppReturnUrl = $returnUrl ?? url()->previous();
                $sppMonthCount = $isEditingSpp ? $editPayment->items->count() : null;
                $sppPaymentMethod = old('payment_method', $isEditingSpp ? $editPayment->payment_method : ($defaultPaymentMethod ?? 'Cash'));
                $sppStatus = old('status', $isEditingSpp ? $editPayment->status : 'Diterima');
            @endphp
            <section class="spp-form-page payment-create-page">
                <div class="student-flat-header payment-create-heading">
                    <div class="student-master-heading">
                        <h1>{{ $isEditingSpp ? 'Edit Pembayaran SPP' : 'Pembayaran SPP' }}</h1>
                        <p>{{ $isEditingSpp ? 'Sesuaikan periode, nominal, dan data penerimaan transaksi SPP.' : 'Lengkapi data pembayaran SPP sebelum transaksi disimpan.' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ $sppFormAction }}" class="card payment-spp-v8-form" data-spp-form data-quote-url="{{ route('finance.spp.quote') }}" data-months-url="{{ route('finance.spp.months') }}" @if($isEditingSpp) data-spp-edit-payment="{{ $editPayment->id }}" @endif>
                @csrf
                @if($isEditingSpp)
                    @method('PUT')
                    <input type="hidden" name="return_url" value="{{ $sppReturnUrl }}">
                @endif
                <div class="payment-form-body">
                    <select name="student_id" data-spp-student hidden>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id', request('student_id', $selectedStudent?->id)) == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->schoolClass?->name ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                        @endforeach
                    </select>

                    <section class="payment-spp-v8-layout">
                        <div class="payment-spp-v8-main">
                            <section class="payment-spp-v8-time-grid">
                                <label>Tanggal
                                    <input type="date" name="transaction_date" value="{{ $nativeDateValue(old('transaction_date'), $isEditingSpp ? $editPayment->transaction_at->format('Y-m-d') : now()->toDateString()) }}" required data-spp-date>
                                </label>
                                <label>Jam
                                    <span class="payment-spp-clock-field">
                                        <input type="time" name="transaction_time" value="{{ $nativeTimeValue(old('transaction_time'), $isEditingSpp ? $editPayment->transaction_at->format('H:i:s') : now()->format('H:i:s')) }}" required data-wib-clock data-spp-time>
                                        <b>WIB</b>
                                    </span>
                                </label>
                            </section>

                            <div class="payment-spp-v8-student-control">
                                <label class="payment-spp-student-field-compact">Siswa
                                    <input class="payment-spp-student-input-compact" type="text" value="{{ $selectedStudent?->schoolClass?->educationUnit?->code ?? '-' }} - {{ $selectedStudent?->nis }} - {{ $selectedStudent?->name }}" readonly>
                                </label>
                                @unless($isEditingSpp)
                                    <a href="{{ route('finance.payments.index') }}" class="button button-secondary payment-spp-change-student">Ganti Siswa</a>
                                @endunless
                            </div>

                            <section class="payment-spp-period-block">
                                <div class="payment-spp-period-field">
                                    <label>Jumlah Bulan
                                        <span class="payment-spp-month-input">
                                            <input type="number" name="month_count" min="1" max="120" step="1" value="{{ old('month_count', $sppMonthCount) }}" required data-spp-month-count-input>
                                        </span>
                                    </label>
                                    <div class="payment-spp-period-result">
                                        <span>Dibayar sampai</span>
                                        <strong data-spp-paid-until>-</strong>
                                    </div>
                                </div>
                                <small data-spp-arrears-notice>Memuat periode tagihan SPP...</small>
                            </section>

                            <section class="payment-spp-v8-payment-grid">
                                <label>Cara Bayar
                                    <select name="payment_method" required><option @selected($sppPaymentMethod === 'Cash')>Cash</option><option @selected($sppPaymentMethod === 'Transfer')>Transfer</option></select>
                                </label>
                                <label>Status
                                    <select name="status" required><option @selected($sppStatus === 'Diterima')>Diterima</option><option @selected($sppStatus === 'Pending')>Pending</option></select>
                                </label>
                                <label class="payment-spp-total-field">Total Bayar
                                    <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount', $isEditingSpp ? $editPayment->paid_amount : null) }}" placeholder="Otomatis sesuai tagihan, bisa diedit" data-spp-paid-input data-currency-input>
                                </label>
                            </section>
                        </div>
                        <aside class="payment-spp-receipt-summary">
                            <span class="payment-spp-receipt-title">Ringkasan</span>
                            <strong class="payment-spp-receipt-name">{{ $selectedStudent?->name }}</strong>
                            <small>{{ $selectedStudent?->nis }} · {{ $selectedStudent?->schoolClass?->educationUnit?->code ?? '-' }} · {{ $selectedStudent?->schoolClass?->name ?? '-' }}</small>
                            <div class="payment-spp-receipt-period">
                                <span>Periode</span>
                                <strong data-spp-period>-</strong>
                                <small><span data-spp-month-count>0 bulan</span> x <span data-spp-base>Rp 0</span></small>
                            </div>
                            <dl>
                                <div><dt>Subtotal</dt><dd data-spp-original>Rp 0</dd></div>
                                <div><dt>Keringanan</dt><dd data-spp-discount>Rp 0</dd></div>
                                <div><dt>Sudah Dibayar</dt><dd data-spp-paid>Rp 0</dd></div>
                                <div><dt>Sisa Tagihan</dt><dd data-spp-remaining>Rp 0</dd></div>
                            </dl>
                            <div class="payment-spp-receipt-total">
                                <span>Total Bayar</span>
                                <strong data-spp-total>Rp 0</strong>
                                <small data-spp-status>Belum Lunas</small>
                            </div>
                            <p class="payment-spp-message" data-spp-message>Memuat tagihan SPP siswa.</p>
                        </aside>
                    </section>

                    <div data-spp-hidden-months></div>
                </div>
                <div class="form-actions">
                    <a href="{{ $isEditingSpp ? $sppReturnUrl : route('finance.spp.index') }}" class="button button-secondary">Batal</a>
                    @if($isEditingSpp)
                        <button class="button button-primary">Simpan Perubahan</button>
                    @else
                        <button class="button button-primary" formtarget="_blank">Simpan Pembayaran</button>
                    @endif
                </div>
            </form>
            </section>
            @endunless
            <div class="modal-backdrop" data-spp-detail-modal>
                <div class="form-modal spp-crud-modal">
                    <div class="form-modal-header"><div><p class="eyebrow">Pembayaran · SPP</p><h2>Detail Transaksi</h2></div><button type="button" class="icon-button" data-spp-crud-close>×</button></div>
                    <div class="spp-detail-content" data-spp-detail-content></div>
                    <div class="form-actions spp-modal-actions"><button type="button" class="button button-primary" data-spp-crud-close>Tutup</button></div>
                </div>
            </div>
            <div class="modal-backdrop" data-spp-edit-modal>
                <div class="form-modal spp-edit-modal">
                    <div class="form-modal-header"><div><p class="eyebrow">Pembayaran · SPP</p><h2>Edit Transaksi</h2></div><button type="button" class="icon-button" data-spp-crud-close>×</button></div>
                    <form method="POST" data-spp-edit-form class="master-form spp-edit-form">@csrf @method('PUT')
                        <div class="spp-edit-readonly"><span data-spp-edit-summary>Siswa dan bulan pembayaran tidak dapat dipindahkan.</span></div>
                        <label>Tanggal Transaksi<input type="date" name="transaction_date" required></label>
                        <label>Jam Transaksi (WIB)<input type="text" name="transaction_time" inputmode="numeric" placeholder="Contoh: 18.00" pattern="(?:[01]\d|2[0-3])[.:][0-5]\d" required></label>
                        <label>Cara Bayar<select name="payment_method" required><option>Cash</option><option>Transfer</option></select></label>
                        <label>Status Penerimaan<select name="status" required><option>Diterima</option><option>Pending</option></select></label>
                        <label class="span-2">Total Bayar<input type="text" inputmode="numeric" name="paid_amount" required data-spp-edit-paid data-currency-input></label>
                        <div class="form-actions span-2"><button type="button" class="button button-secondary" data-spp-crud-close>Batal</button><button class="button button-primary">Simpan Perubahan</button></div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop" data-spp-correction-modal>
                <div class="form-modal spp-edit-modal">
                    <div class="form-modal-header"><div><p class="eyebrow">Pembayaran · SPP</p><h2>Koreksi Nominal Pembayaran</h2></div><button type="button" class="icon-button" data-spp-crud-close>×</button></div>
                    <form method="POST" data-spp-correction-form class="master-form spp-edit-form">@csrf
                        <div class="spp-edit-readonly"><span>Koreksi hanya dapat mengurangi nominal sebagai refund. Penambahan pembayaran harus dicatat sebagai transaksi baru.</span></div>
                        <label class="span-2">Siswa<input type="text" data-spp-correction-name readonly></label>
                        <label>Nominal Sebelumnya<input type="text" data-spp-correction-old readonly></label>
                        <label>Nominal Setelah Koreksi<input type="text" inputmode="numeric" name="new_paid_amount" required data-spp-correction-new data-currency-input></label>
                        <label class="span-2">Alasan Koreksi<input name="reason" maxlength="255" required placeholder="Contoh: Salah input nominal atau pengembalian dana"></label>
                        <div class="form-actions span-2"><button type="button" class="button button-secondary" data-spp-crud-close>Batal</button><button class="button button-primary">Simpan Koreksi</button></div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop" data-spp-delete-modal>
                <div class="form-modal spp-delete-modal">
                    <div class="spp-delete-icon">!</div>
                    <h2>Hapus Transaksi?</h2>
                    <p>Transaksi pembayaran <strong data-spp-delete-name></strong> akan dihapus. Status pembayaran bulan terkait akan dihitung kembali.</p>
                    <form method="POST" data-spp-delete-form>@csrf @method('DELETE')<div class="form-actions"><button type="button" class="button button-secondary" data-spp-crud-close>Batal</button><button class="button button-danger">Ya, Hapus Transaksi</button></div></form>
                </div>
            </div>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
