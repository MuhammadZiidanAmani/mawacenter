<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Transfer - MA'WA CENTER</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'file' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z"/><path d="M14 3v6h6"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
    $rupiah = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
    $transferSummary = $requests->total() > 0
        ? 'Menampilkan '.$requests->firstItem().'-'.$requests->lastItem().' dari '.$requests->total().' transfer'
        : 'Menampilkan 0 dari 0 transfer';
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'transfer-verification'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            @include('partials.theme-toggle')
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main class="student-page transfer-verification-page">
            @if(session('success'))
                <div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif
            @if(session('error') || $errors->any())
                <div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ session('error') ?: $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif

            <section class="student-flat-header">
                <div class="student-master-heading">
                    <h1>Verifikasi Transfer</h1>
                    <p>Periksa bukti transfer wali santri sebelum tagihan dianggap terbayar.</p>
                </div>
            </section>

            <form method="GET" action="{{ route('finance.transfer-verifications.index') }}" class="app-filter-panel transfer-filter student-filter-panel student-reference-filter">
                @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">@endif
                <label><span>Status</span><select name="status"><option value="Pending" @selected($status === 'Pending')>Pending</option><option value="Diterima" @selected($status === 'Diterima')>Diterima</option><option value="Ditolak" @selected($status === 'Ditolak')>Ditolak</option></select></label>
                <div class="transfer-filter-actions"><button class="button button-primary" type="submit">Filter</button><a class="button button-secondary" href="{{ route('finance.transfer-verifications.index') }}">Reset</a></div>
            </form>

            <section class="app-table-surface bills-data-card">
                <form method="GET" action="{{ route('finance.transfer-verifications.index') }}" class="app-table-toolbar transfer-table-toolbar" role="search">
                    <input type="hidden" name="status" value="{{ $status }}">
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">@endif
                    <label class="app-table-per-page app-page-size-control"><span>Tampilkan</span><select name="per_page" aria-label="Jumlah transfer per halaman" onchange="this.form.submit()">@foreach([10, 25, 50, 100, 500] as $size)<option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}</option>@endforeach<option value="all" @selected(request('per_page') === 'all')>Semua</option></select><span>data</span></label>
                    <label class="app-table-search app-data-search"><span>Cari Transfer</span><span class="app-field-shell app-data-search-field"><input type="search" name="search" value="{{ $search }}" placeholder="Nama, NIS, NISN, atau wali"></span></label>
                </form>
                <div class="app-table-scroll table-wrap">
                    <table class="app-data-table data-table student-flat-table transfer-verification-table">
                        <thead><tr><th>No</th><th aria-sort="{{ request('sort') === 'date' ? (request('direction') === 'asc' ? 'ascending' : 'descending') : 'none' }}">@include('partials.app-sort-heading', ['column' => 'date', 'label' => 'Tanggal'])</th><th>Wali</th><th>Siswa</th><th aria-sort="{{ request('sort') === 'amount' ? (request('direction') === 'asc' ? 'ascending' : 'descending') : 'none' }}">@include('partials.app-sort-heading', ['column' => 'amount', 'label' => 'Jumlah'])</th><th>Bukti</th><th aria-sort="{{ request('sort') === 'status' ? (request('direction') === 'asc' ? 'ascending' : 'descending') : 'none' }}">@include('partials.app-sort-heading', ['column' => 'status', 'label' => 'Status'])</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse($requests as $transfer)
                                <tr>
                                    <td>{{ $requests->firstItem() + $loop->index }}</td>
                                    <td>{{ $transfer->created_at->format('d/m/Y H.i') }}</td>
                                    <td><strong>{{ $transfer->user?->name }}</strong><small>{{ $transfer->user?->username }}</small></td>
                                    <td><strong>{{ $transfer->student?->name }}</strong><small>{{ $transfer->student?->schoolClass?->educationUnit?->code ?? '-' }} - {{ $transfer->student?->nis }}</small></td>
                                    <td><strong class="bill-money remaining">{{ $rupiah($transfer->amount) }}</strong></td>
                                    <td><a href="{{ route('finance.transfer-verifications.proof', $transfer) }}" target="_blank" class="app-table-action is-icon-only transfer-proof-link" title="Lihat bukti transfer" aria-label="Lihat bukti transfer">{!! $icon('file') !!}</a></td>
                                    <td><span @class(['app-status', 'status', 'success' => $transfer->status === 'Diterima', 'warning' => $transfer->status === 'Pending', 'danger' => in_array($transfer->status, ['Ditolak', 'Dibatalkan'], true), 'is-accepted' => $transfer->status === 'Diterima', 'is-pending' => $transfer->status === 'Pending', 'is-rejected' => $transfer->status === 'Ditolak', 'is-cancelled' => $transfer->status === 'Dibatalkan'])>{{ $transfer->status }}</span></td>
                                    <td>
                                        @if($transfer->status === 'Pending')
                                            <div class="transfer-actions">
                                                <form method="POST" action="{{ route('finance.transfer-verifications.accept', $transfer) }}">@csrf<button class="app-table-action is-success is-icon-only transfer-accept-button" title="Terima transfer" aria-label="Terima transfer">{!! $icon('check') !!}</button></form>
                                                <form method="POST" action="{{ route('finance.transfer-verifications.reject', $transfer) }}">@csrf<input type="hidden" name="rejected_reason" value="Bukti transfer tidak valid"><button class="app-table-action is-danger is-icon-only transfer-reject-button" title="Tolak transfer" aria-label="Tolak transfer">{!! $icon('x') !!}</button></form>
                                            </div>
                                        @else
                                            {{ $transfer->verifier?->name ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="app-table-empty empty-state">Tidak ada transfer dengan status ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <nav class="app-table-summary transfer-pagination-footer" aria-label="Navigasi halaman transfer">
                    <p>{{ $transferSummary }}</p>
                    <div class="app-pagination transfer-pagination-links">
                        {{ $requests->links() }}
                    </div>
                </nav>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
