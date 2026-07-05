<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Akun - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $svg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'role' => '<path d="M12 3 5 6v5c0 4.5 3 8.1 7 10 4-1.9 7-5.5 7-10V6l-7-3Z"/><path d="M9 12l2 2 4-5"/>',
        'bank' => '<path d="M3 21h18M4 10h16M6 10v8M10 10v8M14 10v8M18 10v8M12 3 4 7v3h16V7l-8-4Z"/>',
    ];
    $icon = fn ($name, $class = '') => $svg($icons[$name], $class);
@endphp
<div class="app-shell">
    @include('partials.sidebar', ['activeMenu' => 'settings'])
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}<span></span></button>
            <button class="icon-button logout-button" type="button" aria-label="Keluar" title="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main class="finance-page settings-page">
            @if(session('success'))
                <div class="result-modal-backdrop show" data-alert><div class="result-modal success-result"><span class="result-icon">✓</span><strong>Sukses!</strong><p>{{ session('success') }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif
            @if($errors->any())
                <div class="result-modal-backdrop show" data-alert><div class="result-modal error-result"><span class="result-icon">!</span><strong>Perlu Diperiksa</strong><p>{{ $errors->first() }}</p><button type="button" class="button button-primary" data-alert-close>OK</button></div></div>
            @endif

            <section class="hero master-hero">
                <div>
                    <p class="eyebrow">Sistem - Pengaturan</p>
                    <h1>Pengaturan Akun</h1>
                    <p>Kelola nama, username, email, dan password akun yang sedang digunakan.</p>
                </div>
            </section>

            <div class="settings-layout">
                <form method="POST" action="{{ route('settings.update') }}" class="card settings-form">
                    @csrf
                    @method('PUT')
                    <div class="settings-form-head"><span>{!! $icon('user') !!}</span><div><strong>Profil Akun</strong><small>Perubahan hanya berlaku untuk akun login saat ini.</small></div></div>
                    <div class="settings-fields">
                        <label>Nama
                            <input name="name" value="{{ old('name', $user->name) }}" required>
                        </label>
                        <label>Username
                            <input name="username" value="{{ old('username', $user->username) }}" required>
                        </label>
                        <label class="wide">Email
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </label>
                        <label>Password Saat Ini
                            <input type="password" name="current_password" autocomplete="current-password" placeholder="Wajib jika ganti password">
                        </label>
                        <label>Password Baru
                            <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                        </label>
                        <label class="wide">Konfirmasi Password Baru
                            <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
                        </label>
                    </div>
                    <div class="settings-form-head settings-transfer-head"><span>{!! $icon('bank') !!}</span><div><strong>Rekening Transfer</strong><small>Dipakai pada Informasi Rekening di Pembayaran.</small></div></div>
                    <div class="settings-fields">
                        <label>Nama Bank
                            <input name="transfer_bank_name" value="{{ old('transfer_bank_name', $transferSettings['transfer_bank_name'] ?? '') }}" placeholder="Contoh: BSI">
                        </label>
                        <label>Nomor Rekening
                            <input name="transfer_account_number" value="{{ old('transfer_account_number', $transferSettings['transfer_account_number'] ?? '') }}" inputmode="numeric" placeholder="Contoh: 1234567890">
                        </label>
                        <label class="wide">Atas Nama
                            <input name="transfer_account_name" value="{{ old('transfer_account_name', $transferSettings['transfer_account_name'] ?? '') }}" placeholder="Contoh: MA'WA CENTER">
                        </label>
                    </div>
                    <div class="settings-save"><span>Username otomatis disimpan dalam huruf kecil tanpa spasi.</span><button class="button button-primary">Simpan Akun</button></div>
                </form>

                <aside class="settings-side">
                    <section class="card setup-card account-summary-card">
                        <div class="settings-form-head"><span>{!! $icon('role') !!}</span><div><strong>Ringkasan Akun</strong><small>Informasi akun aktif saat ini.</small></div></div>
                        <div class="account-summary">
                            <span><small>Nama</small><strong>{{ $user->name }}</strong></span>
                            <span><small>Username</small><strong>{{ $user->username }}</strong></span>
                            <span><small>Email</small><strong>{{ $user->email }}</strong></span>
                            <span><small>Role</small><strong>{{ $user->roleLabel() }}</strong></span>
                        </div>
                    </section>
                    <section class="settings-note"><strong>Catatan</strong><p>Data User dan Data Role kini dikelola melalui submenu Data Master.</p></section>
                </aside>
            </div>
        </main>
    </div>
</div>
</body>
</html>
