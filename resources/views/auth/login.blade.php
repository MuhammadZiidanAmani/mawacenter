<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d5f36">
    <title>Masuk - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
@php
    $icon = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
@endphp
<main class="login-page">
    <section class="login-identity">
        <div class="login-brand">
            <img src="{{ asset('images/mawa-center-logo.png') }}" alt="MA'WA Center">
        </div>

        <div class="login-intro">
            <span class="login-kicker">Sistem Manajemen Keuangan</span>
            <h1>Administrasi yang tertata, layanan pendidikan yang lebih bermakna.</h1>
            <p>Kelola tagihan, pembayaran, dan laporan keuangan lembaga dalam satu ruang kerja yang aman.</p>
        </div>

        <div class="login-highlights" aria-label="Keunggulan aplikasi">
            <div>
                <span>{!! $icon('<path d="M12 3 4 7v5c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/>') !!}</span>
                <p><strong>Aman dan terpercaya</strong><small>Data keuangan terlindungi dalam satu sistem.</small></p>
            </div>
            <div>
                <span>{!! $icon('<path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/>') !!}</span>
                <p><strong>Ringkas dan terukur</strong><small>Pantau kondisi keuangan secara menyeluruh.</small></p>
            </div>
        </div>
    </section>

    <section class="login-access">
        <div class="login-form-wrap">
            <div class="login-mobile-brand">
                <img src="{{ asset('images/mawa-center-logo.png') }}" alt="MA'WA Center">
            </div>
            <div class="login-form-heading">
                <span>Selamat datang kembali</span>
                <h2>Masuk ke akun Anda</h2>
                <p>Gunakan akun petugas yang telah terdaftar.</p>
            </div>

            @if (session('status'))
                <div class="login-alert success">{!! $icon('<path d="m5 12 4 4L19 6"/>') !!}<span>{{ session('status') }}</span></div>
            @endif
            @if ($errors->any())
                <div class="login-alert error">{!! $icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>') !!}<span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf
                <label>
                    <span>Alamat email</span>
                    <span class="login-input">
                        {!! $icon('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>') !!}
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@mawacenter.id" autocomplete="email" autofocus required>
                    </span>
                </label>
                <label>
                    <span>Kata sandi</span>
                    <span class="login-input">
                        {!! $icon('<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>') !!}
                        <input type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" data-password required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                            {!! $icon('<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>') !!}
                        </button>
                    </span>
                </label>
                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya di perangkat ini</span>
                </label>
                <button type="submit" class="login-submit">
                    <span>Masuk ke aplikasi</span>
                    {!! $icon('<path d="M5 12h14m-6-6 6 6-6 6"/>') !!}
                </button>
            </form>

            <p class="login-help">Mengalami kendala akses? Hubungi administrator lembaga.</p>
        </div>
        <footer>© {{ date('Y') }} MA'WA Center · Sistem Manajemen Keuangan</footer>
    </section>
</main>
</body>
</html>
