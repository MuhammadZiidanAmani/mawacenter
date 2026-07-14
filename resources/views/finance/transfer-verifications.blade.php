<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Transfer - MA'WA CENTER</title>
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
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'transfer-verification'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" data-sidebar-toggle>{!! $icon('menu') !!}</button>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button">{!! $icon('bell') !!}</button>
            <a class="icon-button logout-button" href="{{ route('logout') }}">{!! $icon('logout') !!}</a>
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

            <form method="GET" action="{{ route('finance.transfer-verifications.index') }}" class="transfer-filter student-filter-panel student-reference-filter">
                <label><span>Status</span><select name="status" onchange="this.form.submit()"><option value="Pending" @selected($status === 'Pending')>Pending</option><option value="Diterima" @selected($status === 'Diterima')>Diterima</option><option value="Ditolak" @selected($status === 'Ditolak')>Ditolak</option></select></label>
            </form>

            <section class="bills-data-card">
                <div class="table-wrap">
                    <table class="data-table student-flat-table transfer-verification-table">
                        <thead><tr><th>No</th><th>Tanggal</th><th>Wali</th><th>Siswa</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse($requests as $transfer)
                                <tr>
                                    <td>{{ $requests->firstItem() + $loop->index }}</td>
                                    <td>{{ $transfer->created_at->format('d/m/Y H.i') }}</td>
                                    <td><strong>{{ $transfer->user?->name }}</strong><small>{{ $transfer->user?->username }}</small></td>
                                    <td><strong>{{ $transfer->student?->name }}</strong><small>{{ $transfer->student?->schoolClass?->educationUnit?->code ?? '-' }} - {{ $transfer->student?->nis }}</small></td>
                                    <td><strong class="bill-money remaining">{{ $rupiah($transfer->amount) }}</strong></td>
                                    <td><a href="{{ asset('storage/'.$transfer->proof_path) }}" target="_blank" class="button transfer-proof-link">{!! $icon('file') !!}</a></td>
                                    <td><span class="status {{ $transfer->status === 'Diterima' ? 'success' : ($transfer->status === 'Ditolak' ? 'danger' : 'neutral') }}">{{ $transfer->status }}</span></td>
                                    <td>
                                        @if($transfer->status === 'Pending')
                                            <div class="transfer-actions">
                                                <form method="POST" action="{{ route('finance.transfer-verifications.accept', $transfer) }}">@csrf<button class="button transfer-accept-button" title="Terima" aria-label="Terima">{!! $icon('check') !!}</button></form>
                                                <form method="POST" action="{{ route('finance.transfer-verifications.reject', $transfer) }}">@csrf<input type="hidden" name="rejected_reason" value="Bukti transfer tidak valid"><button class="button transfer-reject-button" title="Tolak" aria-label="Tolak">{!! $icon('x') !!}</button></form>
                                            </div>
                                        @else
                                            {{ $transfer->verifier?->name ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-state">Tidak ada transfer dengan status ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $requests->links() }}
            </section>
        </main>
    </div>
</div>
</body>
</html>
