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
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
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
                    <a href="{{ route('finance.spp.index') }}" class="active">{!! $icon('wallet') !!}<span>SPP</span></a>
                    <a href="{{ route('finance.other.index') }}">{!! $icon('receipt') !!}<span>Lain-lain</span></a>
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
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button">{!! $icon('logout') !!}</button>
        </header>
        <main class="finance-page">
            @if(session('success'))<div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @if($errors->any())<div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>@endif
            @unless($showCreate)
            <div>
                <section class="hero master-hero">
                    <div><p class="eyebrow">Pembayaran · SPP</p><h1>Daftar Pembayaran SPP</h1><p>Lihat dan kelola seluruh transaksi pembayaran SPP siswa.</p></div>
                    <div class="spp-hero-actions"><a href="{{ route('finance.spp.create') }}" class="button button-primary">{!! $icon('plus') !!} Tambah Pembayaran SPP</a></div>
                </section>
                <section class="card spp-import-card">
                    <div class="spp-import-copy">
                        <span class="spp-import-icon"><svg class="icon" viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg></span>
                        <div>
                            <span class="spp-import-kicker">Import Data</span>
                            <strong>Import Pembayaran SPP Bulanan</strong>
                            <span>Unggah laporan XLSX. Sistem akan memeriksa NIS, periode, nominal, dan duplikasi sebelum data disimpan.</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('finance.spp.import.preview') }}" enctype="multipart/form-data" class="spp-import-form">
                        @csrf
                        <label class="spp-file-picker">
                            <input type="file" name="file" accept=".xlsx" required data-spp-import-file>
                            <span class="spp-file-mark"><svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                            <span class="spp-file-text"><strong data-spp-import-filename>Pilih file laporan</strong><small>XLSX · maksimal 10 MB</small></span>
                            <span class="spp-file-browse">Pilih File</span>
                        </label>
                        <button class="button button-primary spp-preview-button"><svg class="icon" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg> Preview Data</button>
                    </form>
                </section>
                @if($importPreview)
                <section class="card spp-import-preview">
                    <div class="spp-preview-header">
                        <div class="spp-preview-title">
                            <span class="spp-preview-icon"><svg class="icon" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12a9 9 0 1 1-5.3-8.2"/></svg></span>
                            <div><span class="spp-import-kicker">Hasil Validasi</span><strong>Preview Import Pembayaran</strong><span>{{ $importPreview['valid'] > 0 ? 'Data valid siap disimpan. Periksa baris gagal sebelum melanjutkan.' : 'Belum ada transaksi yang dapat diimpor. Periksa keterangan pada tabel.' }}</span></div>
                        </div>
                        <form method="POST" action="{{ route('finance.spp.import') }}">@csrf<input type="hidden" name="token" value="{{ $importToken }}"><button class="button button-primary spp-confirm-button" @disabled($importPreview['valid'] < 1)><svg class="icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg><span>Konfirmasi Import</span><b>{{ $importPreview['valid'] }} Transaksi</b></button></form>
                    </div>
                    <div class="spp-import-stats">
                        <div class="total"><span class="spp-stat-icon">Σ</span><p><span>Total Baris</span><strong>{{ number_format($importPreview['total'], 0, ',', '.') }}</strong><small>data diperiksa</small></p></div>
                        <div class="valid"><span class="spp-stat-icon">✓</span><p><span>Valid</span><strong>{{ number_format($importPreview['valid'], 0, ',', '.') }}</strong><small>siap diimpor</small></p></div>
                        <div class="duplicate"><span class="spp-stat-icon">↻</span><p><span>Duplikat</span><strong>{{ number_format($importPreview['duplicates'], 0, ',', '.') }}</strong><small>akan dilewati</small></p></div>
                        <div class="failed"><span class="spp-stat-icon">!</span><p><span>Gagal</span><strong>{{ number_format(count($importPreview['failures']), 0, ',', '.') }}</strong><small>perlu diperiksa</small></p></div>
                    </div>
                    <div class="spp-validation-bar"><span style="width: {{ $importPreview['total'] > 0 ? ($importPreview['valid'] / $importPreview['total']) * 100 : 0 }}%"></span></div>
                    <div class="spp-preview-table-head"><div><strong>Rincian Hasil Pemeriksaan</strong><span>Menampilkan maksimal 100 baris pertama</span></div><span class="spp-preview-count">{{ count($importPreview['rows']) }} baris</span></div>
                    <div class="table-wrap spp-import-table-wrap"><table class="data-table spp-import-table"><thead><tr><th>Baris</th><th>NIS</th><th>Nama Siswa</th><th>Periode</th><th>Nominal</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
                        @foreach(array_slice($importPreview['rows'], 0, 100) as $row)
                        <tr class="spp-import-row {{ strtolower($row['status']) }}"><td><span class="spp-line-number">{{ $row['line'] }}</span></td><td><strong class="spp-import-nis">{{ $row['nis'] }}</strong></td><td><strong>{{ $row['name'] }}</strong></td><td><span class="spp-period">{{ ucfirst($row['month_name']) }} <b>{{ $row['year'] }}</b></span></td><td><strong class="spp-import-amount">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</strong></td><td><span class="status {{ $row['status']==='Valid'?'success':($row['status']==='Duplikat'?'warning':'danger') }}">{{ $row['status'] }}</span></td><td><span class="spp-import-message">{{ $row['message'] }}</span></td></tr>
                        @endforeach
                    </tbody></table></div>
                    @if(count($importPreview['rows']) > 100)<p class="spp-import-note"><span>i</span> Menampilkan 100 dari {{ count($importPreview['rows']) }} baris hasil validasi.</p>@endif
                </section>
                @endif
                <section class="card master-card spp-history">
                    <div class="simple-list-header"><div><strong>Data Pembayaran SPP</strong><span>{{ $payments->total() }} transaksi tersimpan</span></div></div>
                    <form method="GET" action="{{ route('finance.spp.index') }}" class="spp-list-toolbar">
                        <label>Show <select name="per_page" onchange="this.form.submit()">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected(request('per_page', 10)==$size)>{{ $size }}</option>@endforeach</select> entries</label>
                        <label>Search: <input name="search" value="{{ request('search') }}" aria-label="Cari pembayaran SPP"><button class="button button-primary">Cari</button></label>
                    </form>
                    <div class="table-wrap"><table class="data-table spp-list-table"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Unit Pendidikan</th><th>Kelas</th><th>Cara Bayar</th><th>Total</th><th></th></tr></thead><tbody>
                        @forelse($payments as $payment)
                            <tr class="spp-main-row">
                                <td>{{ $payments->firstItem()+$loop->index }}</td><td>{{ $payment->student?->nis }}</td><td><strong>{{ $payment->student?->name }}</strong></td><td>{{ $payment->student?->schoolClass?->educationUnit?->name ?? '-' }}</td><td>{{ $payment->student?->schoolClass?->name ?? '-' }}</td><td><span class="payment-method">{{ strtolower($payment->payment_method) }}</span></td><td><strong>Rp {{ number_format($payment->paid_amount,0,',','.') }}</strong></td><td><button type="button" class="spp-expand-button" data-spp-row-toggle="{{ $payment->id }}" aria-expanded="false">+</button></td>
                            </tr>
                            <tr class="spp-expanded-row" data-spp-row-detail="{{ $payment->id }}" hidden><td colspan="8">
                                <div class="spp-expanded-content">
                                    <div class="spp-expanded-meta"><div><span>Status Penerimaan</span><strong><span class="status {{ $payment->status==='Diterima'?'success':'neutral' }}">{{ $payment->status }}</span></strong></div><div><span>Status Pembayaran</span><strong>{{ $payment->payment_status }}</strong></div><div><span>Waktu Transaksi</span><strong>{{ $payment->transaction_at->format('d/m/Y H:i:s') }}</strong></div><div><span>Bulan</span><strong>{{ $payment->items->map(fn($item)=>$months[$item->month])->join(', ') }}</strong></div><div><span>Total Wajib</span><strong>Rp {{ number_format($payment->total_amount,0,',','.') }}</strong></div><div><span>Sisa Tagihan</span><strong>Rp {{ number_format($payment->remaining_amount,0,',','.') }}</strong></div></div>
                                    <div class="spp-expanded-actions"><span>Aksi</span><div class="table-actions spp-crud-actions"><a href="{{ route('finance.spp.receipt', $payment) }}" target="_blank" class="icon-button print-button" title="Cetak Struk"><svg class="icon" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg></a><button type="button" class="icon-button view-button" title="Detail" data-spp-detail-url="{{ route('finance.spp.show', $payment) }}"><svg class="icon" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button><button type="button" class="icon-button correction-button" title="Koreksi Nominal" data-spp-correction-url="{{ route('finance.spp.correct', $payment) }}" data-spp-correction-name="{{ $payment->student?->name }}" data-spp-correction-amount="{{ $payment->paid_amount }}"><svg class="icon" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/><path d="M12 7v5l3 2"/></svg></button><button type="button" class="icon-button edit-button" title="Edit Metadata" data-spp-edit-url="{{ route('finance.spp.show', $payment) }}" data-spp-update-url="{{ route('finance.spp.update', $payment) }}"><svg class="icon" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></button><button type="button" class="icon-button delete-button" title="Hapus" data-spp-delete-url="{{ route('finance.spp.destroy', $payment) }}" data-spp-delete-name="{{ $payment->student?->name }}"><svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/></svg></button></div></div>
                                </div>
                            </td></tr>
                        @empty @include('master.partials.empty') @endforelse
                    </tbody></table></div><div class="pagination-wrap">{{ $payments->links() }}</div>
                </section>
            </div>
            @else
            <section class="spp-form-page payment-create-page">
                <section class="hero master-hero">
                    <div><p class="eyebrow">Pembayaran · SPP</p><h1>Tambah Pembayaran SPP</h1><p>Catat pembayaran bulanan siswa dengan nominal dan keringanan otomatis.</p></div>
                    <a href="{{ route('finance.spp.index') }}" class="button button-secondary">Kembali ke Daftar</a>
                </section>
                <form method="POST" action="{{ route('finance.spp.store') }}" class="card spp-payment-form" data-spp-form data-quote-url="{{ route('finance.spp.quote') }}" data-months-url="{{ route('finance.spp.months') }}">
                @csrf
                <div class="spp-form-section">
                    <div class="spp-form-heading"><strong>Informasi Transaksi</strong><span><b>*</b> Wajib diisi</span></div>
                    <div class="spp-form-grid">
                        <label>Waktu Transaksi <span class="spp-inline"><input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required readonly data-spp-date><input type="time" name="transaction_time" step="1" value="{{ old('transaction_time', now()->format('H:i:s')) }}" required readonly data-spp-time></span></label>
                        <label>Siswa <select name="student_id" required data-spp-student><option value="">Pilih siswa...</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_id')==$student->id)>{{ $student->nis }} - {{ $student->name }} · {{ $student->schoolClass?->educationUnit?->code ?? '-' }} · {{ $student->schoolClass?->name ?? '-' }}</option>@endforeach</select></label>
                        <fieldset class="spp-month-field"><legend>Bulan</legend><div class="spp-months">@foreach($months as $number=>$name)<label><input type="checkbox" name="months[]" value="{{ $number }}" @checked(in_array($number, old('months', [])))><span class="spp-month-name">{{ $name }}</span><small class="spp-month-status">Belum Dibayar</small></label>@endforeach</div></fieldset>
                        <label>Tahun <select name="year" required data-spp-year>@foreach($years as $year)<option value="{{ $year }}" @selected(old('year', now()->year)==$year)>{{ $year }}</option>@endforeach</select></label>
                        <label>Cara Bayar <select name="payment_method" required><option @selected(($defaultPaymentMethod ?? 'Cash')==='Cash')>Cash</option><option @selected(($defaultPaymentMethod ?? 'Cash')==='Transfer')>Transfer</option></select></label>
                        <label>Status <select name="status" required><option>Diterima</option><option>Pending</option></select></label>
                        <label>Nominal Dibayar Sekarang <input type="text" inputmode="numeric" name="paid_amount" required value="{{ old('paid_amount') }}" placeholder="Masukkan nominal titipan atau pelunasan" data-spp-paid-input data-currency-input></label>
                    </div>
                </div>
                <div class="spp-summary">
                    <div><span>Besar SPP / Bulan</span><strong data-spp-base>Rp 0</strong></div>
                    <div><span>Total SPP</span><strong data-spp-original>Rp 0</strong></div>
                    <div class="discount"><span>Keringanan Otomatis</span><strong data-spp-discount>Rp 0</strong></div>
                    <div><span>Total Wajib Dibayar</span><strong data-spp-total>Rp 0</strong></div>
                    <div><span>Sudah Dibayar</span><strong data-spp-paid>Rp 0</strong></div>
                    <div class="total"><span>Sisa Tagihan</span><strong data-spp-remaining>Rp 0</strong><small data-spp-status>Belum Lunas</small></div>
                </div>
                <p class="spp-quote-message" data-spp-message>Pilih siswa dan bulan untuk menghitung pembayaran.</p>
                <div class="form-actions"><a href="{{ route('finance.spp.index') }}" class="button button-secondary">Batal</a><button class="button button-primary">Simpan Pembayaran</button></div>
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
                        <div class="spp-edit-readonly"><span>Siswa tidak dapat dipindahkan. Gunakan fitur Koreksi Nominal untuk refund atau perbaikan nominal dengan histori yang tercatat.</span></div>
                        <label>Tanggal Transaksi<input type="date" name="transaction_date" required></label>
                        <label>Waktu Transaksi<input type="time" name="transaction_time" step="1" required></label>
                        <label>Cara Bayar<select name="payment_method" required><option>Cash</option><option>Transfer</option></select></label>
                        <label>Status Penerimaan<select name="status" required><option>Diterima</option><option>Pending</option></select></label>
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
    </div>
</div>
</body>
</html>
