<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagihan Anak - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'upload' => '<path d="M12 16V4m0 0-4 4m4-4 4 4M4 20h16"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $total = (int) $bills->sum('remaining_amount');
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'guardian-bills'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <a class="icon-button logout-button" href="{{ route('logout') }}">{!! $icon('logout') !!}</a>
        </header>

        <main class="student-page guardian-page">
            @if(session('success'))
                <div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif
            @if($errors->any())
                <div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif

            <section class="student-flat-header">
                <div class="student-master-heading">
                    <h1>Tagihan Anak</h1>
                    <p>Lihat tagihan anak dan kirim bukti pembayaran transfer.</p>
                </div>
            </section>

            <form method="GET" action="{{ route('guardian.bills.index') }}" class="guardian-filter student-filter-panel student-reference-filter">
                <label><span>Siswa</span><select name="student_id" onchange="this.form.submit()">@foreach($students as $student)<option value="{{ $student->id }}" @selected($selectedStudentId == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select></label>
            </form>

            <form method="POST" action="{{ route('guardian.transfers.store') }}" enctype="multipart/form-data" class="guardian-transfer-form">
                @csrf
                <input type="hidden" name="student_id" value="{{ $selectedStudentId }}">
                <section class="bills-data-card">
                    <div class="table-wrap">
                        <table class="data-table student-flat-table guardian-bill-table">
                            <thead><tr><th>Pilih</th><th>Tagihan</th><th>Periode</th><th>Sisa</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse($bills as $bill)
                                    <tr>
                                        <td><input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" checked></td>
                                        <td><strong>{{ $bill->title }}</strong><small>{{ $bill->student?->schoolClass?->educationUnit?->code ?? '-' }} - {{ $bill->student?->schoolClass?->name ?? '-' }}</small></td>
                                        <td>{{ $bill->month ? sprintf('%02d/%s', $bill->month, $bill->year) : ($bill->year ?? '-') }}</td>
                                        <td><strong class="bill-money remaining">{{ $rupiah($bill->remaining_amount) }}</strong></td>
                                        <td><span class="status neutral">{{ $bill->displayStatus() }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="empty-state">Tidak ada tagihan aktif untuk siswa ini.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot><tr><td colspan="3">Total Dipilih</td><td colspan="2"><strong>{{ $rupiah($total) }}</strong></td></tr></tfoot>
                        </table>
                    </div>
                </section>

                <section class="guardian-upload-panel">
                    <label><span>Bukti Transfer</span><input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" @required($bills->isNotEmpty())></label>
                    <button class="button button-primary" @disabled($bills->isEmpty())>{!! $icon('upload') !!} Kirim Bukti Transfer</button>
                </section>
            </form>

            <section class="bills-data-card guardian-history-card">
                <div class="table-wrap">
                    <table class="data-table student-flat-table guardian-history-table">
                        <thead><tr><th>Tanggal</th><th>Siswa</th><th>Jumlah</th><th>Status</th><th>Catatan</th></tr></thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td>{{ $transfer->created_at->format('d/m/Y H.i') }}</td>
                                    <td>{{ $transfer->student?->nis }} - {{ $transfer->student?->name }}</td>
                                    <td><strong>{{ $rupiah($transfer->amount) }}</strong></td>
                                    <td><span class="status {{ $transfer->status === 'Diterima' ? 'success' : ($transfer->status === 'Ditolak' ? 'danger' : 'neutral') }}">{{ $transfer->status }}</span></td>
                                    <td>{{ $transfer->rejected_reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty-state">Belum ada riwayat transfer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
