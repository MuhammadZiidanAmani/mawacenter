<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran {{ $paymentSection['title'] }} - MA'WA CENTER</title>
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
    $filterDateFrom = $nativeDateValue(request('date_from'), now()->startOfMonth()->toDateString());
    $filterDateTo = $nativeDateValue(request('date_to'), now()->toDateString());
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'payment', 'activePaymentMenu' => $showCreate ? 'transaction' : 'history'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div><button class="icon-button notification-button">{!! $icon('bell') !!}</button><button class="icon-button logout-button">{!! $icon('logout') !!}</button>
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
                        <h1>Pembayaran {{ $paymentSection['title'] }}</h1>
                        <div class="student-title-actions">
                            <a href="{{ route('finance.other.create', ['category' => $paymentSection['key']]) }}" class="button student-add-button">{!! $icon('plus') !!} Tambah</a>
                            <button type="button" class="button action-purple spp-import-toggle {{ $importPreview || $errors->any() ? 'active' : '' }}" data-spp-import-toggle aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">{!! $icon('upload') !!} Import</button>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('finance.other.index', ['category' => $paymentSection['key']]) }}" class="student-filter-panel payment-filter-panel payment-filter-compact">
                        <input type="hidden" name="category" value="{{ $paymentSection['key'] }}">
                        <div class="payment-filter-primary">
                            <label class="payment-date-filter"><span>Waktu</span><span class="payment-date-range"><input type="date" name="date_from" value="{{ $filterDateFrom }}"><b>-</b><input type="date" name="date_to" value="{{ $filterDateTo }}"></span></label>
                            <label><span>Unit Pendidikan</span><select name="unit_id" data-student-filter-unit><option value="">semua</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
                            <label><span>Kelas</span><select name="class_id" data-student-filter-class><option value="">semua</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></label>
                            <label class="payment-student-field"><span>Siswa</span><span class="payment-student-filter"><span class="student-search-picker" data-student-picker data-student-optional><input type="search" name="student_search" value="{{ request('student_search') }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" data-student-search><select name="student_id" data-student-source><option value="">Semua siswa</option>@foreach($studentOptions as $student)<option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select><span class="student-search-results" data-student-results hidden></span></span></span></label>
                            <div class="student-filter-actions payment-filter-actions">
                                <button class="button student-search-button" aria-label="Cari">{!! $icon('search') !!}</button>
                                <a href="{{ route('finance.other.index', ['category' => $paymentSection['key']]) }}" class="button student-filter-reset">Reset</a>
                            </div>
                        </div>
                        <details class="payment-advanced-filters" @if(request()->filled('fee_type_id') || request()->filled('payment_method') || request()->filled('status') || request()->filled('operator_name')) open @endif>
                            <summary>Filter Lainnya</summary>
                            <div class="payment-filter-secondary">
                                <label><span>Kategori Pembayaran</span><select name="fee_type_id"><option value="">semua</option>@foreach($feeTypes as $feeType)<option value="{{ $feeType->id }}" @selected(request('fee_type_id') == $feeType->id)>{{ $feeType->name }}</option>@endforeach</select></label>
                                <label><span>Cara Bayar</span><select name="payment_method"><option value="">semua</option><option value="Cash" @selected(request('payment_method') === 'Cash')>Cash</option><option value="Transfer" @selected(request('payment_method') === 'Transfer')>Transfer</option></select></label>
                                <label><span>Status</span><select name="status"><option value="">semua</option><option value="Diterima" @selected(request('status') === 'Diterima')>Diterima</option><option value="Pending" @selected(request('status') === 'Pending')>Pending</option></select></label>
                                <label><span>Petugas</span><select name="operator_name"><option value="">semua</option>@foreach($operators as $operator)<option value="{{ $operator }}" @selected(request('operator_name') === $operator)>{{ $operator }}</option>@endforeach</select></label>
                            </div>
                        </details>
                        @foreach($paymentQuery(['category', 'date_from', 'date_to', 'fee_type_id', 'payment_method', 'status', 'operator_name', 'unit_id', 'class_id', 'nis', 'student_id', 'student_search']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                <div class="spp-import-modal-backdrop {{ $errors->any() ? 'show' : '' }}" data-spp-import-panel @if(! $errors->any()) hidden @endif>
                    <section class="spp-import-modal" role="dialog" aria-modal="true" aria-labelledby="other-import-title">
                        <header class="spp-import-modal-head">
                            <div><span class="spp-import-kicker">Pembayaran · {{ $paymentSection['title'] }}</span><h2 id="other-import-title">Import Pembayaran {{ $paymentSection['title'] }}</h2><p>Unggah laporan pembayaran untuk memproses transaksi massal.</p></div>
                            <button type="button" class="spp-import-close" data-spp-import-close aria-label="Tutup modal import">×</button>
                        </header>
                        <div class="spp-import-progress">
                            <div class="active"><b>1</b><span>Pilih file</span></div>
                            <div><b>2</b><span>Pemetaan & Preview</span></div>
                            <div><b>3</b><span>Konfirmasi</span></div>
                        </div>
                        <div class="spp-import-info">
                            <span><svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></svg></span>
                            <div><strong>Validasi Sistem</strong><p>Sistem akan memeriksa <b>NIS, kategori, nominal, dan duplikasi</b> sebelum data disimpan.</p></div>
                        </div>
                        <form method="POST" action="{{ route('finance.other.import.preview', ['category' => $paymentSection['key']]) }}" enctype="multipart/form-data" class="spp-import-modal-form">@csrf
                            <label class="spp-import-dropzone">
                                <input type="file" name="file" accept=".xlsx" required data-spp-import-file>
                                <span class="spp-import-drop-icon">{!! $icon('upload') !!}</span>
                                <strong data-spp-import-filename>Ketuk untuk pilih berkas</strong>
                                <small>Format XLSX · Maksimal 10 MB</small>
                                <span class="spp-import-browse">Cari di Dokumen Saya</span>
                            </label>
                            <div class="spp-import-modal-actions">
                                <button class="button button-primary spp-preview-button">{!! $icon('upload') !!} Preview Data</button>
                                <button type="button" class="button button-secondary" data-spp-import-close>Batal</button>
                            </div>
                        </form>
                    </section>
                </div>
                @if($importPreview)
                <section class="card other-import-mapping">
                    <div class="spp-preview-table-head"><div><strong>Pemetaan Kategori Pembayaran</strong><span>Pastikan kategori Excel diarahkan ke kategori pembayaran aplikasi yang benar untuk setiap unit.</span></div><span class="spp-preview-count">{{ count($importSources) }} pemetaan</span></div>
                    <form method="POST" action="{{ route('finance.other.import.preview', ['category' => $paymentSection['key']]) }}" class="other-mapping-form">@csrf<input type="hidden" name="token" value="{{ $importToken }}">
                        @foreach($importSources as $source)
                        <label>
                            <span><strong>{{ $source['category'] }}</strong><small>{{ $source['unit'] }} · {{ $source['rows'] }} transaksi</small></span>
                            <select name="mappings[{{ $source['key'] }}]">
                                <option value="">Belum dipetakan</option>
                                @foreach($feeTypes as $feeType)
                                    @php
                                        $feeTypeScope = $feeType->schoolClass?->name
                                            ?? ($feeType->class_level ? \App\Support\ClassLevel::label($feeType->class_level) : 'Semua Kelas');
                                        $feeTypeYear = $feeType->academicYear?->name;
                                    @endphp
                                    <option value="{{ $feeType->id }}" @selected(($importMappings[$source['key']] ?? null)==$feeType->id)>{{ $feeType->name }} · {{ $feeType->educationUnit?->name }} · {{ $feeTypeScope }}{{ $feeTypeYear ? ' · '.$feeTypeYear : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        @endforeach
                        <button class="button button-secondary">Terapkan Pemetaan & Periksa Ulang</button>
                    </form>
                </section>
                <section class="card spp-import-preview">
                    <div class="spp-preview-header"><div class="spp-preview-title"><span class="spp-preview-icon">✓</span><div><span class="spp-import-kicker">Hasil Validasi</span><strong>Preview Import Pembayaran {{ $paymentSection['title'] }}</strong><span>{{ $importPreview['valid'] > 0 ? 'Transaksi valid siap disimpan setelah pemetaan diperiksa.' : 'Belum ada transaksi yang dapat diimpor.' }}</span></div></div><form method="POST" action="{{ route('finance.other.import', ['category' => $paymentSection['key']]) }}">@csrf<input type="hidden" name="token" value="{{ $importToken }}"><button class="button button-primary spp-confirm-button" @disabled($importPreview['valid'] < 1)><span>Konfirmasi Import</span><b>{{ $importPreview['valid'] }} Transaksi</b></button></form></div>
                    <div class="spp-import-stats">
                        <div class="total"><span class="spp-stat-icon">Σ</span><p><span>Total Baris</span><strong>{{ $importPreview['total'] }}</strong><small>data diperiksa</small></p></div>
                        <div class="valid"><span class="spp-stat-icon">✓</span><p><span>Valid</span><strong>{{ $importPreview['valid'] }}</strong><small>siap diimpor</small></p></div>
                        <div class="duplicate"><span class="spp-stat-icon">↻</span><p><span>Duplikat</span><strong>{{ $importPreview['duplicates'] }}</strong><small>akan dilewati</small></p></div>
                        <div class="failed"><span class="spp-stat-icon">!</span><p><span>Gagal</span><strong>{{ count($importPreview['failures']) }}</strong><small>perlu diperiksa</small></p></div>
                    </div>
                    <div class="spp-validation-bar"><progress class="spp-validation-progress" max="{{ max(1, $importPreview['total']) }}" value="{{ $importPreview['valid'] }}" aria-label="Persentase transaksi valid"></progress></div>
                    <div class="table-wrap spp-import-table-wrap"><table class="data-table spp-import-table other-import-preview-table"><thead><tr><th>Baris</th><th>NIS</th><th>Nama Siswa</th><th>Kategori Excel</th><th>Nominal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>@foreach(array_slice($importPreview['rows'],0,100) as $row)<tr class="spp-import-row {{ strtolower($row['status']) }}"><td><span class="spp-line-number">{{ $row['line'] }}</span></td><td><strong class="spp-import-nis">{{ $row['nis'] }}</strong></td><td><strong>{{ $row['name'] }}</strong></td><td><span class="other-import-category">{{ $row['category'] }}</span><small>{{ $row['unit'] }}</small></td><td><strong class="spp-import-amount">Rp {{ number_format($row['nominal'],0,',','.') }}</strong></td><td><span class="status {{ $row['status']==='Valid'?'success':($row['status']==='Duplikat'?'warning':'danger') }}">{{ $row['status'] }}</span></td><td><span class="spp-import-message">{{ $row['message'] }}</span></td></tr>@endforeach</tbody></table></div>
                </section>
                @endif
                <div class="student-data-card payment-data-card spp-history other-payment-history">
                    @include('partials.list-toolbar', ['action' => route('finance.other.index', ['category' => $paymentSection['key']]), 'searchLabel' => 'Cari pembayaran '.$paymentSection['title']])
                    <div class="table-wrap"><table @class(['data-table', 'student-flat-table', 'payment-flat-table', 'spp-list-table', 'registration-payment-table' => in_array($paymentSection['key'], ['daftar-ulang', 'laundry'], true)])><thead><tr><th>No</th><th>@include('partials.sortable-heading', ['column' => 'nis', 'label' => 'NIS'])</th><th>@include('partials.sortable-heading', ['column' => 'name', 'label' => 'Nama'])</th><th>@include('partials.sortable-heading', ['column' => 'class', 'label' => 'Kelas'])</th><th>@include('partials.sortable-heading', ['column' => 'method', 'label' => 'Cara Bayar'])</th><th>@include('partials.sortable-heading', ['column' => 'total', 'label' => 'Total'])</th><th>Rincian</th></tr></thead><tbody>
                        @forelse($payments as $payment)
                            <tr class="spp-main-row">
                                <td>{{ $payments->firstItem()+$loop->index }}</td><td>{{ $payment->student?->nis ?? '-' }}</td><td><span class="payment-student-name">{{ $payment->student?->name ?? '-' }}</span><small class="payment-student-unit">Unit Pendidikan: {{ $payment->student?->schoolClass?->educationUnit?->code ?? '-' }}</small></td><td>{{ $payment->student?->schoolClass?->name ?? '-' }}</td><td><span class="payment-method">{{ strtolower($payment->payment_method) }}</span></td><td><span>Rp {{ number_format($payment->paid_amount,0,',','.') }}</span></td><td><button type="button" class="spp-expand-button" data-spp-row-toggle="other-{{ $payment->id }}" aria-expanded="false">Lihat</button></td>
                            </tr>
                            <tr class="spp-expanded-row" data-spp-row-detail="other-{{ $payment->id }}" hidden><td colspan="7">
                                @if(in_array($paymentSection['key'], ['daftar-ulang', 'laundry'], true))
                                    <div @class(['registration-payment-detail', 'spp-payment-detail' => $paymentSection['key'] === 'laundry'])>
                                        <div class="registration-detail-item payment-type"><span>{{ $paymentSection['key'] === 'laundry' ? 'Bulan' : 'Kategori Pembayaran' }}</span><strong>{{ $paymentSection['key'] === 'laundry' ? $payment->items->map(fn($item) => $months[$item->month].' '.$item->year)->join(', ') : ($payment->feeType?->name ?? '-') }}</strong></div>
                                        <div class="registration-detail-item"><span>Cara Bayar</span><strong><span class="compact-badge method">{{ strtolower($payment->payment_method) }}</span></strong></div>
                                        <div class="registration-detail-item"><span>Status</span><strong><span class="compact-badge {{ $payment->status === 'Diterima' ? 'success' : 'neutral' }}">{{ strtolower($payment->status) }}</span></strong></div>
                                        <div class="registration-detail-item"><span>Nominal</span><strong>Rp {{ number_format($payment->paid_amount,0,',','.') }}</strong></div>
                                        <div class="registration-detail-item time"><span>Waktu</span><strong>{{ $payment->transaction_at->format('Y-m-d H:i:s') }}</strong></div>
                                        <div class="registration-detail-item"><span>Petugas</span><strong>{{ $payment->operator_name ?: '-' }}</strong></div>
                                        <div class="registration-detail-item"><span>Pembayaran</span><strong>{{ $payment->payment_status }}</strong></div>
                                        <div class="registration-detail-item action"><span>Aksi</span><div @class(['registration-actions', 'spp-compact-actions' => $paymentSection['key'] === 'laundry'])><a href="{{ route('finance.other.receipt', $payment) }}" target="_blank" class="registration-action print" title="Cetak Struk" aria-label="Cetak Struk"><svg class="icon" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg></a><button type="button" class="registration-action edit" title="Edit Transaksi" aria-label="Edit Transaksi" data-other-edit-url="{{ route('finance.other.show', $payment) }}" data-other-update-url="{{ route('finance.other.update', $payment) }}"><svg class="icon" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></button><button type="button" class="registration-action delete" title="Hapus Transaksi" aria-label="Hapus Transaksi" data-other-delete-url="{{ route('finance.other.destroy', $payment) }}" data-other-delete-name="{{ $payment->student?->name }}"><svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/></svg></button></div></div>
                                    </div>
                                @else
                                    <div class="spp-expanded-content">
                                        <div class="spp-expanded-meta"><div><span>Kategori Pembayaran</span><strong>{{ $payment->feeType?->name ?? '-' }}</strong></div><div><span>Status Penerimaan</span><strong><span class="status {{ $payment->status==='Diterima'?'success':'neutral' }}">{{ $payment->status }}</span></strong></div><div><span>Status Pembayaran</span><strong>{{ $payment->payment_status }}</strong></div><div><span>Waktu Transaksi</span><strong>{{ $payment->transaction_at->format('d/m/Y H.i') }} WIB</strong></div><div><span>Nominal Asli</span><strong>Rp {{ number_format($payment->original_amount,0,',','.') }}</strong></div><div><span>Keringanan</span><strong>Rp {{ number_format($payment->discount_amount,0,',','.') }}</strong></div><div><span>Total Wajib</span><strong>Rp {{ number_format($payment->total_amount,0,',','.') }}</strong></div><div><span>Sisa Tagihan</span><strong>Rp {{ number_format($payment->remaining_amount,0,',','.') }}</strong></div></div>
                                    </div>
                                @endif
                            </td></tr>
                        @empty <tr><td colspan="7" class="empty-state">Belum ada pembayaran {{ $paymentSection['title'] }}.</td></tr> @endforelse
                    </tbody></table></div><div class="pagination-wrap">{{ $payments->links() }}</div>
                </div>
                </section>
            </div>
            @else
            <section class="spp-form-page payment-create-page">
                <div class="student-flat-header payment-create-heading">
                    <div class="student-master-heading">
                        <h1>Pembayaran {{ $paymentSection['title'] }}</h1>
                        <p>{{ $paymentSection['key'] === 'laundry' ? 'Catat pembayaran Laundry bulanan siswa dengan nominal dan keringanan otomatis.' : 'Lengkapi data pembayaran sebelum transaksi disimpan.' }}</p>
                    </div>
                </div>
                <?php if ($paymentSection['key'] === 'laundry') : ?>
                @php
                    $selectedLaundryStudentId = old('student_id', request('student_id'));
                    $selectedLaundryStudent = $selectedLaundryStudentId ? $students->firstWhere('id', (int) $selectedLaundryStudentId) : null;
                    $selectedLaundryStudentText = $selectedLaundryStudent
                        ? (($selectedLaundryStudent->schoolClass?->educationUnit?->code ?? '-').' - '.$selectedLaundryStudent->nis.' - '.$selectedLaundryStudent->name)
                        : old('student_search');
                @endphp
                <form method="POST" action="{{ route('finance.other.store', ['category' => 'laundry']) }}" class="card payment-spp-v8-form payment-registration-v1-form payment-other-v1-form payment-laundry-v1-form" data-laundry-form data-payment-category="laundry" data-quote-url="{{ route('finance.other.quote', ['category' => 'laundry']) }}" data-months-url="{{ route('finance.other.months', ['category' => 'laundry']) }}">@csrf
                    <div class="payment-form-body">
                        <section class="payment-spp-v8-layout">
                            <div class="payment-spp-v8-main">
                                <section class="payment-spp-v8-time-grid">
                                    <label>Tanggal
                                        <input type="date" name="transaction_date" value="{{ $nativeDateValue(old('transaction_date'), now()->toDateString()) }}" required>
                                    </label>
                                    <label>Jam
                                        <span class="payment-spp-clock-field">
                                            <input type="time" name="transaction_time" value="{{ $nativeTimeValue(old('transaction_time'), now()->format('H:i:s')) }}" required data-laundry-time>
                                            <b>WIB</b>
                                        </span>
                                    </label>
                                </section>

                                <div class="payment-spp-v8-student-control payment-registration-student-control">
                                    <label class="payment-spp-student-field-compact">Siswa
                                        <span class="student-search-picker payment-registration-student-picker" data-student-picker>
                                            <input class="payment-spp-student-input-compact" type="search" name="student_search" value="{{ $selectedLaundryStudentText }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" required data-student-search>
                                            <select name="student_id" data-laundry-student data-student-source hidden>
                                                <option value="">Pilih siswa...</option>
                                                @foreach($students as $student)
                                                    <option value="{{ $student->id }}" data-class-id="{{ $student->school_class_id }}" data-class-name="{{ $student->schoolClass?->name }}" data-unit-id="{{ $student->schoolClass?->education_unit_id }}" data-unit-code="{{ $student->schoolClass?->educationUnit?->code }}" data-year-id="{{ $student->academic_year_id }}" data-nis="{{ $student->nis }}" data-name="{{ $student->name }}" @selected(old('student_id', request('student_id'))==$student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="student-search-results" data-student-results hidden></span>
                                        </span>
                                    </label>
                                    <a href="{{ route('finance.payments.index') }}" class="button button-secondary payment-spp-change-student">Ganti</a>
                                </div>

                                <section class="payment-registration-v1-category-grid">
                                    <label>Tahun
                                        <select name="year" required data-laundry-year>@foreach($years as $year)<option value="{{ $year }}" @selected(old('year', now()->year)==$year)>{{ $year }}</option>@endforeach</select>
                                    </label>
                                    <label>Kategori Laundry
                                        <select name="fee_type_id" required data-laundry-fee disabled>
                                            <option value="">Pilih siswa terlebih dahulu</option>
                                            @foreach($feeTypes as $feeType)<option value="{{ $feeType->id }}" data-class-id="{{ $feeType->school_class_id }}" data-unit-id="{{ $feeType->education_unit_id }}" data-year-id="{{ $feeType->academic_year_id }}" @selected(old('fee_type_id')==$feeType->id)>{{ $feeType->name }}</option>@endforeach
                                        </select>
                                    </label>
                                </section>

                                <section class="payment-spp-period-block payment-laundry-period-block">
                                    <div class="payment-spp-period-field">
                                        <label>Jumlah Bulan
                                            <span class="payment-spp-month-input payment-laundry-month-input">
                                                <input type="number" name="month_count" min="1" max="12" step="1" value="{{ old('month_count', count(old('months', [])) ?: '') }}" required data-laundry-month-count>
                                            </span>
                                        </label>
                                        <div class="payment-spp-period-result">
                                            <span>Dibayar sampai</span>
                                            <strong data-laundry-period-end>-</strong>
                                        </div>
                                    </div>
                                    <small data-laundry-start-note>Pilih siswa dan kategori Laundry untuk melihat periode pembayaran.</small>
                                    <div data-laundry-month-values hidden></div>
                                </section>

                                <section class="payment-spp-v8-payment-grid payment-registration-v1-payment-grid">
                                    <label>Cara Bayar
                                        <select name="payment_method" required><option @selected(($defaultPaymentMethod ?? 'Cash')==='Cash')>Cash</option><option @selected(($defaultPaymentMethod ?? 'Cash')==='Transfer')>Transfer</option></select>
                                    </label>
                                    <label>Status
                                        <select name="status" required><option>Diterima</option><option>Pending</option></select>
                                    </label>
                                    <label class="payment-spp-total-field">Total Bayar
                                        <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount') }}" placeholder="Masukkan nominal" data-laundry-paid-input data-currency-input>
                                    </label>
                                </section>
                            </div>

                            <aside class="payment-spp-receipt-summary payment-registration-summary">
                                <span class="payment-spp-receipt-title">Ringkasan</span>
                                <strong class="payment-spp-receipt-name" data-laundry-summary-name>{{ $selectedLaundryStudent?->name ?? '-' }}</strong>
                                <small data-laundry-summary-meta>{{ $selectedLaundryStudent ? $selectedLaundryStudent->nis.' · '.($selectedLaundryStudent->schoolClass?->educationUnit?->code ?? '-').' · '.($selectedLaundryStudent->schoolClass?->name ?? '-') : 'Pilih siswa terlebih dahulu' }}</small>
                                <div class="payment-spp-receipt-period">
                                    <span>Kategori</span>
                                    <strong data-laundry-summary-category>Laundry</strong>
                                    <small>Pembayaran bulanan</small>
                                </div>
                                <dl>
                                    <div><dt>Biaya / Bulan</dt><dd data-laundry-base>Rp 0</dd></div>
                                    <div><dt>Total Laundry</dt><dd data-laundry-original>Rp 0</dd></div>
                                    <div><dt>Keringanan</dt><dd data-laundry-discount>Rp 0</dd></div>
                                    <div><dt>Sudah Dibayar</dt><dd data-laundry-paid>Rp 0</dd></div>
                                    <div><dt>Sisa Tagihan</dt><dd data-laundry-remaining>Rp 0</dd></div>
                                </dl>
                                <div class="payment-spp-receipt-total">
                                    <span>Total Bayar</span>
                                    <strong data-laundry-total>Rp 0</strong>
                                    <small data-laundry-status>Belum Lunas</small>
                                </div>
                                <p class="payment-spp-message" data-laundry-message>Pilih siswa, kategori Laundry, dan jumlah bulan untuk menghitung pembayaran.</p>
                            </aside>
                        </section>
                    </div>
                    <div class="form-actions">
                        <a href="{{ route('finance.other.index', ['category' => 'laundry']) }}" class="button button-secondary">Batal</a>
                        <button class="button button-primary">Simpan Pembayaran</button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if ($paymentSection['key'] === 'daftar-ulang') : ?>
                @php
                    $selectedOtherStudentId = old('student_id', request('student_id'));
                    $selectedOtherStudent = $selectedOtherStudentId ? $students->firstWhere('id', (int) $selectedOtherStudentId) : null;
                    $selectedOtherStudentText = $selectedOtherStudent
                        ? (($selectedOtherStudent->schoolClass?->educationUnit?->code ?? '-').' - '.$selectedOtherStudent->nis.' - '.$selectedOtherStudent->name)
                        : old('student_search');
                @endphp
                <form method="POST" action="{{ route('finance.other.store', ['category' => 'daftar-ulang']) }}" class="card payment-spp-v8-form payment-registration-v1-form" data-other-form data-payment-category="daftar-ulang" data-quote-url="{{ route('finance.other.quote', ['category' => 'daftar-ulang']) }}">@csrf
                    <div class="payment-form-body">
                        <section class="payment-spp-v8-layout">
                            <div class="payment-spp-v8-main">
                                <section class="payment-spp-v8-time-grid">
                                    <label>Tanggal
                                        <input type="date" name="transaction_date" value="{{ $nativeDateValue(old('transaction_date'), now()->toDateString()) }}" required data-other-date>
                                    </label>
                                    <label>Jam
                                        <span class="payment-spp-clock-field">
                                            <input type="time" name="transaction_time" value="{{ old('transaction_time') ? substr((string) old('transaction_time'), 0, 5) : now()->format('H:i') }}" required data-other-time>
                                            <b>WIB</b>
                                        </span>
                                    </label>
                                </section>

                                <div class="payment-spp-v8-student-control payment-registration-student-control">
                                    <label class="payment-spp-student-field-compact">Siswa
                                        <span class="student-search-picker payment-registration-student-picker" data-student-picker>
                                            <input class="payment-spp-student-input-compact" type="search" name="student_search" value="{{ $selectedOtherStudentText }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" required data-student-search data-other-student-search>
                                            <select name="student_id" data-other-student data-student-source hidden>
                                                <option value="">Pilih siswa...</option>
                                                @foreach($students as $student)
                                                    <option value="{{ $student->id }}" data-class-id="{{ $student->school_class_id }}" data-class-name="{{ $student->schoolClass?->name }}" data-unit-id="{{ $student->schoolClass?->education_unit_id }}" data-unit-code="{{ $student->schoolClass?->educationUnit?->code }}" data-year-id="{{ $student->academic_year_id }}" data-nis="{{ $student->nis }}" data-name="{{ $student->name }}" @selected(old('student_id', request('student_id'))==$student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="student-search-results" data-student-results hidden></span>
                                        </span>
                                    </label>
                                    <a href="{{ route('finance.payments.index') }}" class="button button-secondary payment-spp-change-student">Ganti</a>
                                </div>

                                <section class="payment-registration-v1-category-grid">
                                    <label>Tahun Pelajaran
                                        <select name="academic_year_id" data-other-academic-year>
                                            <option value="">Semua Tahun Pelajaran</option>
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}" @selected(old('academic_year_id', request('academic_year_id', $activeAcademicYear?->id)) == $year->id)>{{ $year->name }}{{ $year->is_active ? ' · Aktif' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>Kategori
                                        <select name="fee_type_id" required data-other-fee disabled>
                                            <option value="">Pilih siswa terlebih dahulu</option>
                                            @foreach($feeTypes as $feeType)
                                                <option value="{{ $feeType->id }}" data-class-id="{{ $feeType->school_class_id }}" data-class-name="{{ $feeType->schoolClass?->name }}" data-unit-id="{{ $feeType->education_unit_id }}" data-unit-code="{{ $feeType->educationUnit?->code }}" data-year-id="{{ $feeType->academic_year_id }}" @selected(old('fee_type_id')==$feeType->id)>{{ $feeType->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </section>

                                <section class="payment-spp-v8-payment-grid payment-registration-v1-payment-grid">
                                    <label>Cara Bayar
                                        <select name="payment_method" required><option @selected(($defaultPaymentMethod ?? 'Cash')==='Cash')>Cash</option><option @selected(($defaultPaymentMethod ?? 'Cash')==='Transfer')>Transfer</option></select>
                                    </label>
                                    <label>Status
                                        <select name="status" required><option>Diterima</option><option>Pending</option></select>
                                    </label>
                                    <label class="payment-spp-total-field">Total Bayar
                                        <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount') }}" placeholder="Otomatis sesuai tagihan" data-other-paid-input data-currency-input>
                                    </label>
                                </section>
                            </div>

                            <aside class="payment-spp-receipt-summary payment-registration-summary">
                                <span class="payment-spp-receipt-title">Ringkasan</span>
                                <strong class="payment-spp-receipt-name" data-other-summary-name>{{ $selectedOtherStudent?->name ?? '-' }}</strong>
                                <small data-other-summary-meta>{{ $selectedOtherStudent ? $selectedOtherStudent->nis.' · '.($selectedOtherStudent->schoolClass?->educationUnit?->code ?? '-').' · '.($selectedOtherStudent->schoolClass?->name ?? '-') : 'Pilih siswa terlebih dahulu' }}</small>
                                <div class="payment-spp-receipt-period">
                                    <span>Kategori</span>
                                    <strong data-other-summary-category>-</strong>
                                    <small>Daftar ulang siswa</small>
                                </div>
                                <dl>
                                    <div><dt>Nominal Asli</dt><dd data-other-original>Rp 0</dd></div>
                                    <div><dt>Keringanan</dt><dd data-other-discount>Rp 0</dd></div>
                                    <div><dt>Sudah Dibayar</dt><dd data-other-paid>Rp 0</dd></div>
                                    <div><dt>Sisa Tagihan</dt><dd data-other-total>Rp 0</dd></div>
                                </dl>
                                <div class="payment-spp-receipt-total">
                                    <span>Total Bayar</span>
                                    <strong data-other-summary-total>Rp 0</strong>
                                    <small data-other-summary-status>Belum Lunas</small>
                                </div>
                                <p class="payment-spp-message" data-other-message>Pilih siswa dan kategori pembayaran untuk menghitung nominal.</p>
                            </aside>
                        </section>
                    </div>
                    <div class="form-actions">
                        <a href="{{ route('finance.other.index', ['category' => 'daftar-ulang']) }}" class="button button-secondary">Batal</a>
                        <button class="button button-primary" data-other-submit formtarget="_blank">Simpan Pembayaran</button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (! in_array($paymentSection['key'], ['laundry', 'daftar-ulang'], true)) : ?>
                @php
                    $selectedOtherStudentId = old('student_id', request('student_id'));
                    $selectedOtherStudent = $selectedOtherStudentId ? $students->firstWhere('id', (int) $selectedOtherStudentId) : null;
                    $selectedOtherStudentText = $selectedOtherStudent
                        ? (($selectedOtherStudent->schoolClass?->educationUnit?->code ?? '-').' - '.$selectedOtherStudent->nis.' - '.$selectedOtherStudent->name)
                        : old('student_search');
                @endphp
                <form method="POST" action="{{ route('finance.other.store', ['category' => $paymentSection['key']]) }}" class="card payment-spp-v8-form payment-registration-v1-form payment-other-v1-form" data-other-form data-payment-category="{{ $paymentSection['key'] }}" data-quote-url="{{ route('finance.other.quote', ['category' => $paymentSection['key']]) }}">@csrf
                    <div class="payment-form-body">
                        <section class="payment-spp-v8-layout">
                            <div class="payment-spp-v8-main">
                                <section class="payment-spp-v8-time-grid">
                                    <label>Tanggal
                                        <input type="date" name="transaction_date" value="{{ $nativeDateValue(old('transaction_date'), now()->toDateString()) }}" required data-other-date>
                                    </label>
                                    <label>Jam
                                        <span class="payment-spp-clock-field">
                                            <input type="time" name="transaction_time" value="{{ $nativeTimeValue(old('transaction_time'), now()->format('H:i:s')) }}" required data-other-time>
                                            <b>WIB</b>
                                        </span>
                                    </label>
                                </section>

                                <div class="payment-spp-v8-student-control payment-registration-student-control">
                                    <label class="payment-spp-student-field-compact">Siswa
                                        <span class="student-search-picker payment-registration-student-picker" data-student-picker>
                                            <input class="payment-spp-student-input-compact" type="search" name="student_search" value="{{ $selectedOtherStudentText }}" placeholder="Ketik NIS atau nama siswa..." autocomplete="off" required data-student-search data-other-student-search>
                                            <select name="student_id" data-other-student data-student-source hidden>
                                                <option value="">Pilih siswa...</option>
                                                @foreach($students as $student)
                                                    <option value="{{ $student->id }}" data-class-id="{{ $student->school_class_id }}" data-class-name="{{ $student->schoolClass?->name }}" data-unit-id="{{ $student->schoolClass?->education_unit_id }}" data-unit-code="{{ $student->schoolClass?->educationUnit?->code }}" data-year-id="{{ $student->academic_year_id }}" data-nis="{{ $student->nis }}" data-name="{{ $student->name }}" @selected(old('student_id', request('student_id'))==$student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="student-search-results" data-student-results hidden></span>
                                        </span>
                                    </label>
                                    <a href="{{ route('finance.payments.index') }}" class="button button-secondary payment-spp-change-student">Ganti</a>
                                </div>

                                <section class="payment-registration-v1-category-grid">
                                    <label>Kategori Pembayaran
                                        <select name="fee_type_id" required data-other-fee disabled>
                                            <option value="">Pilih siswa terlebih dahulu</option>
                                            @foreach($feeTypes as $feeType)<option value="{{ $feeType->id }}" data-class-id="{{ $feeType->school_class_id }}" data-class-name="{{ $feeType->schoolClass?->name }}" data-unit-id="{{ $feeType->education_unit_id }}" data-unit-code="{{ $feeType->educationUnit?->code }}" data-year-id="{{ $feeType->academic_year_id }}" @selected(old('fee_type_id')==$feeType->id)>{{ $feeType->name }}</option>@endforeach
                                        </select>
                                    </label>
                                </section>

                                <section class="payment-spp-v8-payment-grid payment-registration-v1-payment-grid">
                                    <label>Cara Bayar
                                        <select name="payment_method" required><option @selected(($defaultPaymentMethod ?? 'Cash')==='Cash')>Cash</option><option @selected(($defaultPaymentMethod ?? 'Cash')==='Transfer')>Transfer</option></select>
                                    </label>
                                    <label>Status
                                        <select name="status" required><option>Diterima</option><option>Pending</option></select>
                                    </label>
                                    <label class="payment-spp-total-field">Total Bayar
                                        <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount') }}" placeholder="Masukkan nominal" data-other-paid-input data-currency-input>
                                    </label>
                                </section>
                            </div>

                            <aside class="payment-spp-receipt-summary payment-registration-summary">
                                <span class="payment-spp-receipt-title">Ringkasan</span>
                                <strong class="payment-spp-receipt-name" data-other-summary-name>{{ $selectedOtherStudent?->name ?? '-' }}</strong>
                                <small data-other-summary-meta>{{ $selectedOtherStudent ? $selectedOtherStudent->nis.' · '.($selectedOtherStudent->schoolClass?->educationUnit?->code ?? '-').' · '.($selectedOtherStudent->schoolClass?->name ?? '-') : 'Pilih siswa terlebih dahulu' }}</small>
                                <div class="payment-spp-receipt-period">
                                    <span>Kategori</span>
                                    <strong data-other-summary-category>-</strong>
                                    <small>Pembayaran {{ strtolower($paymentSection['title']) }}</small>
                                </div>
                                <dl>
                                    <div><dt>Nominal Asli</dt><dd data-other-original>Rp 0</dd></div>
                                    <div><dt>Keringanan</dt><dd data-other-discount>Rp 0</dd></div>
                                    <div><dt>Sudah Dibayar</dt><dd data-other-paid>Rp 0</dd></div>
                                    <div><dt>Sisa Tagihan</dt><dd data-other-total>Rp 0</dd></div>
                                </dl>
                                <div class="payment-spp-receipt-total">
                                    <span>Total Bayar</span>
                                    <strong data-other-summary-total>Rp 0</strong>
                                    <small data-other-summary-status>Belum Lunas</small>
                                </div>
                                <p class="payment-spp-message" data-other-message>Pilih siswa dan kategori pembayaran untuk menghitung nominal.</p>
                            </aside>
                        </section>
                    </div>
                    <div class="form-actions">
                        <a href="{{ route('finance.other.index', ['category' => $paymentSection['key']]) }}" class="button button-secondary">Batal</a>
                        <button class="button button-primary" data-other-submit>Simpan Pembayaran</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>
            @endunless
            <div class="modal-backdrop" data-other-edit-modal>
                <div class="form-modal spp-edit-modal">
                    <div class="form-modal-header"><div><p class="eyebrow">Pembayaran · {{ $paymentSection['title'] }}</p><h2>Edit Transaksi</h2></div><button type="button" class="icon-button" data-other-crud-close>×</button></div>
                    <form method="POST" data-other-edit-form class="master-form spp-edit-form">@csrf @method('PUT')
                        <div class="spp-edit-readonly"><span data-other-edit-summary>Data siswa dan kategori pembayaran tidak dapat diubah.</span></div>
                        <label>Tanggal Transaksi<input type="date" name="transaction_date" required></label>
                        <label>Jam Transaksi (WIB)<input type="text" name="transaction_time" inputmode="numeric" placeholder="Contoh: 18.00" pattern="(?:[01]\d|2[0-3])[.:][0-5]\d" required></label>
                        <label>Cara Bayar<select name="payment_method" required><option>Cash</option><option>Transfer</option></select></label>
                        <label>Status Penerimaan<select name="status" required><option>Diterima</option><option>Pending</option></select></label>
                        <div class="form-actions span-2"><button type="button" class="button button-secondary" data-other-crud-close>Batal</button><button class="button button-primary">Simpan Perubahan</button></div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop" data-other-delete-modal>
                <div class="form-modal spp-delete-modal">
                    <div class="spp-delete-icon">!</div>
                    <h2>Hapus Transaksi?</h2>
                    <p>Transaksi pembayaran <strong data-other-delete-name></strong> akan dihapus dan sisa tagihan akan dihitung ulang.</p>
                    <form method="POST" data-other-delete-form>@csrf @method('DELETE')<div class="form-actions"><button type="button" class="button button-secondary" data-other-crud-close>Batal</button><button class="button button-danger">Ya, Hapus Transaksi</button></div></form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
