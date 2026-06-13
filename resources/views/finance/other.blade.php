<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Lain-lain - MA'WA CENTER</title>
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
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
@endphp
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="brand"><div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div><div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div><button class="icon-button sidebar-close" data-sidebar-close>×</button></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item">{!! $icon('grid') !!}<span>Dashboard</span></a>
            <div class="nav-group master-nav">
                <button type="button" class="nav-item nav-parent" data-master-nav-toggle aria-expanded="false">{!! $icon('database') !!}<span>Data Master</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    @foreach (['academic-years'=>['Tahun Pelajaran','calendar'],'education-units'=>['Unit Pendidikan','grid'],'classes'=>['Kelas','database'],'students'=>['Siswa','users'],'fee-types'=>['Kategori Pembayaran','receipt'],'spp-settings'=>['Set SPP','wallet'],'fee-discounts'=>['Keringanan Biaya','wallet']] as $key=>$item)
                        <a href="{{ route('master.index', ['tab'=>$key]) }}">{!! $icon($item[1]) !!}<span>{{ $item[0] }}</span></a>
                    @endforeach
                </div>
            </div>
            <div class="nav-group nested-nav open">
                <button type="button" class="nav-item nav-parent active" data-nav-toggle aria-expanded="true">{!! $icon('card') !!}<span>Pembayaran</span>{!! $icon('chevron', 'nav-chevron') !!}</button>
                <div class="nav-submenu">
                    <a href="{{ route('finance.spp.index') }}">{!! $icon('wallet') !!}<span>SPP</span></a>
                    <a href="{{ route('finance.other.index') }}" class="active">{!! $icon('receipt') !!}<span>Lain-lain</span></a>
                </div>
            </div>
            <a href="{{ route('finance.bills.index') }}" class="nav-item">{!! $icon('receipt') !!}<span>Tagihan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
            <a href="{{ route('reports.index') }}" class="nav-item">{!! $icon('chart') !!}<span>Laporan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
            <a href="{{ route('settings.index') }}" class="nav-item">{!! $icon('settings') !!}<span>Pengaturan</span>{!! $icon('chevron', 'nav-chevron') !!}</a>
        </nav>
    </aside>
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div><button class="icon-button notification-button">{!! $icon('bell') !!}</button><button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>
        <main class="finance-page">
            @if(session('success'))<div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @if($errors->any())<div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @unless($showCreate)
            <div>
                <section class="hero master-hero"><div><p class="eyebrow">Pembayaran · Lain-lain</p><h1>Daftar Pembayaran Lain-lain</h1><p>Lihat seluruh transaksi pembayaran selain SPP.</p></div></section>
                <section class="card spp-import-card">
                    <div class="spp-import-copy">
                        <span class="spp-import-icon">{!! $icon('receipt') !!}</span>
                        <div><span class="spp-import-kicker">Import Data</span><strong>Import Pembayaran Lain-lain</strong><span>Unggah laporan XLSX, petakan kategori pembayaran, lalu periksa transaksi sebelum disimpan.</span></div>
                    </div>
                    <form method="POST" action="{{ route('finance.other.import.preview') }}" enctype="multipart/form-data" class="spp-import-form">@csrf
                        <label class="spp-file-picker"><input type="file" name="file" accept=".xlsx" required data-spp-import-file><span class="spp-file-mark">{!! $icon('receipt') !!}</span><span class="spp-file-text"><strong data-spp-import-filename>Pilih file laporan</strong><small>XLSX · maksimal 10 MB</small></span><span class="spp-file-browse">Pilih File</span></label>
                        <button class="button button-primary spp-preview-button">Preview Data</button>
                    </form>
                </section>
                @if($importPreview)
                <section class="card other-import-mapping">
                    <div class="spp-preview-table-head"><div><strong>Pemetaan Kategori Pembayaran</strong><span>Pastikan kategori Excel diarahkan ke kategori aplikasi yang benar untuk setiap unit.</span></div><span class="spp-preview-count">{{ count($importSources) }} pemetaan</span></div>
                    <form method="POST" action="{{ route('finance.other.import.preview') }}" class="other-mapping-form">@csrf<input type="hidden" name="token" value="{{ $importToken }}">
                        @foreach($importSources as $source)
                        <label><span><strong>{{ $source['category'] }}</strong><small>{{ $source['unit'] }} · {{ $source['rows'] }} transaksi</small></span><select name="mappings[{{ $source['key'] }}]"><option value="">Belum dipetakan</option>@foreach($feeTypes as $feeType)<option value="{{ $feeType->id }}" @selected(($importMappings[$source['key']] ?? null)==$feeType->id)>{{ $feeType->name }} · {{ $feeType->educationUnit?->name }} · {{ $feeType->schoolClass?->name ?? 'Semua Kelas' }}</option>@endforeach</select></label>
                        @endforeach
                        <button class="button button-secondary">Terapkan Pemetaan & Periksa Ulang</button>
                    </form>
                </section>
                <section class="card spp-import-preview">
                    <div class="spp-preview-header"><div class="spp-preview-title"><span class="spp-preview-icon">✓</span><div><span class="spp-import-kicker">Hasil Validasi</span><strong>Preview Import Pembayaran Lain-lain</strong><span>{{ $importPreview['valid'] > 0 ? 'Transaksi valid siap disimpan setelah pemetaan diperiksa.' : 'Belum ada transaksi yang dapat diimpor.' }}</span></div></div><form method="POST" action="{{ route('finance.other.import') }}">@csrf<input type="hidden" name="token" value="{{ $importToken }}"><button class="button button-primary spp-confirm-button" @disabled($importPreview['valid'] < 1)><span>Konfirmasi Import</span><b>{{ $importPreview['valid'] }} Transaksi</b></button></form></div>
                    <div class="spp-import-stats">
                        <div class="total"><span class="spp-stat-icon">Σ</span><p><span>Total Baris</span><strong>{{ $importPreview['total'] }}</strong><small>data diperiksa</small></p></div>
                        <div class="valid"><span class="spp-stat-icon">✓</span><p><span>Valid</span><strong>{{ $importPreview['valid'] }}</strong><small>siap diimpor</small></p></div>
                        <div class="duplicate"><span class="spp-stat-icon">↻</span><p><span>Duplikat</span><strong>{{ $importPreview['duplicates'] }}</strong><small>akan dilewati</small></p></div>
                        <div class="failed"><span class="spp-stat-icon">!</span><p><span>Gagal</span><strong>{{ count($importPreview['failures']) }}</strong><small>perlu diperiksa</small></p></div>
                    </div>
                    <div class="spp-validation-bar"><span style="width: {{ $importPreview['total'] > 0 ? ($importPreview['valid'] / $importPreview['total']) * 100 : 0 }}%"></span></div>
                    <div class="table-wrap spp-import-table-wrap"><table class="data-table spp-import-table other-import-preview-table"><thead><tr><th>Baris</th><th>NIS</th><th>Nama Siswa</th><th>Kategori Excel</th><th>Nominal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>@foreach(array_slice($importPreview['rows'],0,100) as $row)<tr class="spp-import-row {{ strtolower($row['status']) }}"><td><span class="spp-line-number">{{ $row['line'] }}</span></td><td><strong class="spp-import-nis">{{ $row['nis'] }}</strong></td><td><strong>{{ $row['name'] }}</strong></td><td><span class="other-import-category">{{ $row['category'] }}</span><small>{{ $row['unit'] }}</small></td><td><strong class="spp-import-amount">Rp {{ number_format($row['nominal'],0,',','.') }}</strong></td><td><span class="status {{ $row['status']==='Valid'?'success':($row['status']==='Duplikat'?'warning':'danger') }}">{{ $row['status'] }}</span></td><td><span class="spp-import-message">{{ $row['message'] }}</span></td></tr>@endforeach</tbody></table></div>
                </section>
                @endif
                <section class="card master-card spp-history other-payment-history">
                    <div class="other-payment-heading">
                        <div><strong>Data Transaksi</strong><span>{{ $payments->total() }} transaksi tersimpan</span></div>
                        <a href="{{ route('finance.other.create') }}" class="button button-primary">{!! $icon('plus') !!} Tambah</a>
                    </div>
                    <form method="GET" action="{{ route('finance.other.index') }}" class="spp-list-toolbar">
                        <label>Show <select name="per_page" onchange="this.form.submit()">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected(request('per_page', 10)==$size)>{{ $size }}</option>@endforeach</select> entries</label>
                        <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari pembayaran lain-lain"><button class="button button-primary">Cari</button></label>
                    </form>
                    <div class="table-wrap"><table class="data-table other-payment-table"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Pendidikan</th><th>Kelas</th><th>Kategori</th><th>Cara Bayar</th><th>Status</th><th>Nominal</th><th>Waktu</th></tr></thead><tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payments->firstItem()+$loop->index }}</td>
                                <td>{{ $payment->student?->nis ?? '-' }}</td>
                                <td><strong>{{ $payment->student?->name ?? '-' }}</strong></td>
                                <td>{{ $payment->student?->schoolClass?->educationUnit?->name ?? '-' }}</td>
                                <td>{{ $payment->student?->schoolClass?->name ?? '-' }}</td>
                                <td>{{ $payment->feeType?->name ?? '-' }}</td>
                                <td><span class="payment-method">{{ strtolower($payment->payment_method) }}</span></td>
                                <td><span class="status {{ $payment->status==='Diterima'?'success':'neutral' }}">{{ strtolower($payment->status) }}</span></td>
                                <td class="other-payment-amount"><strong>{{ number_format($payment->paid_amount,0,',','.') }}</strong>@if($payment->discount_amount > 0)<small>Potongan {{ number_format($payment->discount_amount,0,',','.') }}</small>@endif</td>
                                <td class="other-payment-time">{{ $payment->transaction_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty @include('master.partials.empty') @endforelse
                    </tbody></table></div><div class="pagination-wrap">{{ $payments->links() }}</div>
                </section>
            </div>
            @else
            <section class="spp-form-page payment-create-page">
                <section class="hero master-hero"><div><p class="eyebrow">Pembayaran · Lain-lain</p><h1>Tambah Pembayaran Lain-lain</h1><p>Pilih siswa dan kategori pembayaran yang berlaku untuk kelasnya.</p></div><a href="{{ route('finance.other.index') }}" class="button button-secondary">Kembali ke Daftar</a></section>
                <form method="POST" action="{{ route('finance.other.store') }}" class="card spp-payment-form" data-other-form data-quote-url="{{ route('finance.other.quote') }}">@csrf
                    <div class="spp-form-section"><div class="spp-form-heading"><strong>Informasi Transaksi</strong><span><b>*</b> Wajib diisi</span></div><div class="spp-form-grid">
                        <label>Waktu Transaksi <span class="spp-inline"><input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required data-other-date><input type="time" name="transaction_time" step="1" value="{{ old('transaction_time', now()->format('H:i:s')) }}" required data-other-time></span></label>
                        <label>Siswa <select name="student_id" required data-other-student><option value="">Pilih siswa...</option>@foreach($students as $student)<option value="{{ $student->id }}" data-class-id="{{ $student->school_class_id }}" data-unit-id="{{ $student->schoolClass?->education_unit_id }}" @selected(old('student_id')==$student->id)>{{ $student->nis }} - {{ $student->name }} · {{ $student->schoolClass?->name }}</option>@endforeach</select></label>
                        <label>Jenis Pembayaran <select name="fee_type_id" required data-other-fee><option value="">Pilih jenis pembayaran...</option>@foreach($feeTypes as $feeType)<option value="{{ $feeType->id }}" data-class-id="{{ $feeType->school_class_id }}" data-unit-id="{{ $feeType->education_unit_id }}" @selected(old('fee_type_id')==$feeType->id)>{{ $feeType->name }} · {{ $feeType->educationUnit?->code }} · {{ $feeType->schoolClass?->name ?? 'Semua Kelas' }}</option>@endforeach</select></label>
                        <label>Cara Bayar <select name="payment_method" required><option @selected(($defaultPaymentMethod ?? 'Cash')==='Cash')>Cash</option><option @selected(($defaultPaymentMethod ?? 'Cash')==='Transfer')>Transfer</option></select></label>
                        <label>Status <select name="status" required><option>Diterima</option><option>Pending</option></select></label>
                        <label>Nominal Dibayar Sekarang <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount') }}" placeholder="Masukkan nominal yang dibayar" data-other-paid-input data-currency-input></label>
                    </div></div>
                    <div class="other-payment-summary"><div><span>Nominal Asli</span><strong data-other-original>Rp 0</strong></div><div class="discount"><span>Keringanan Otomatis</span><strong data-other-discount>Rp 0</strong></div><div><span>Sudah Dibayar</span><strong data-other-paid>Rp 0</strong></div><div class="total"><span>Sisa Tagihan</span><strong data-other-total>Rp 0</strong></div></div>
                    <p class="spp-quote-message" data-other-message>Pilih siswa dan jenis pembayaran untuk menghitung nominal.</p>
                    <div class="form-actions"><a href="{{ route('finance.other.index') }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Pembayaran</button></div>
                </form>
            </section>
            @endunless
        </main>
    </div>
</div>
</body>
</html>
