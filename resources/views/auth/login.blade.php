<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#157144">
    <title>Masuk - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
@php
    $icon = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $selectedLoginType = old('login_type', old('guardian_unit_id') ? 'wali' : null);
    $loginTypeLabels = ['petugas' => 'Petugas', 'bendahara' => 'Bendahara', 'wali' => 'Wali Santri'];
@endphp
<main class="login-page">
    <section class="login-card login-choice-card">
        <div class="login-brand">
            <div class="login-brand-mark">
                <img src="{{ asset('images/mawa-center-mark-transparent.png') }}" alt="">
            </div>
            <h1>MA'WA <span>CENTER</span></h1>
        </div>

        <div class="login-hero-copy">
            <strong>Selamat Datang Kembali</strong>
            <span class="login-access-context" data-login-access-title>
                @if ($selectedLoginType)
                    <span class="login-access-label">Masuk Sebagai :</span>
                    <span class="login-access-role">{{ $loginTypeLabels[$selectedLoginType] ?? 'Petugas' }}</span>
                @else
                    Pilih Peran Anda
                @endif
            </span>
        </div>

        @if (session('status'))
            <div class="login-alert success" data-login-alert>{!! $icon('<path d="m5 12 4 4L19 6"/>') !!}<span>{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="login-alert error" data-login-alert>{!! $icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>') !!}<span>{{ $errors->first() }}</span></div>
        @endif

        <input type="radio" name="login_type_picker" id="login-type-petugas" value="petugas" class="login-type-radio" @checked($selectedLoginType === 'petugas')>
        <input type="radio" name="login_type_picker" id="login-type-bendahara" value="bendahara" class="login-type-radio" @checked($selectedLoginType === 'bendahara')>
        <input type="radio" name="login_type_picker" id="login-type-wali" value="wali" class="login-type-radio" @checked($selectedLoginType === 'wali')>

        <div class="login-role-grid" aria-label="Pilihan login">
            <label for="login-type-petugas" class="login-role-card login-role-petugas">
                <span class="login-role-icon">{!! $icon('<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M9 5V3h6v2"/><circle cx="12" cy="11" r="2.5"/><path d="M8 16c.8-2 2.1-3 4-3s3.2 1 4 3"/>') !!}</span>
                <strong>Petugas</strong>
            </label>
            <label for="login-type-bendahara" class="login-role-card login-role-bendahara">
                <span class="login-role-icon">{!! $icon('<path d="M4 7h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><path d="M16 7V5a2 2 0 0 0-2-2H5.5A2.5 2.5 0 0 0 3 5.5V8"/><path d="M17 13h.01"/>') !!}</span>
                <strong>Bendahara</strong>
            </label>
            <label for="login-type-wali" class="login-role-card login-role-wali">
                <span class="login-role-icon">{!! $icon('<circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="10" r="2.5"/><path d="M14.5 15.5A4.5 4.5 0 0 1 21 19"/>') !!}</span>
                <strong>Wali Santri</strong>
            </label>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="login-form">
            @csrf
            <input type="hidden" name="login_type" value="{{ $selectedLoginType ?? '' }}" data-login-type-value>
            <div class="login-selected-role-field">
                <span>Masuk Sebagai :</span>
                <span class="login-selected-role-box">
                    {!! $icon('<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-4 2.9-6 7-6s6.4 2 7 6"/>') !!}
                    <span data-login-role-name>{{ $loginTypeLabels[$selectedLoginType] ?? 'Petugas' }}</span>
                </span>
            </div>
            <label class="login-guardian-field">
                <span>Unit Pendidikan</span>
                <span class="login-input">
                    {!! $icon('<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 13h8M10 19v-4h4v4"/>') !!}
                    <select name="guardian_unit_id">
                        <option value="">Pilih unit</option>
                        @foreach($educationUnits as $unit)
                            <option value="{{ $unit->id }}" @selected(old('guardian_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>
                        @endforeach
                    </select>
                </span>
            </label>
            <label>
                <span data-login-username-label>Username</span>
                <span class="login-input">
                    {!! $icon('<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-4 2.9-6 7-6s6.4 2 7 6"/>') !!}
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="Username" autocomplete="username" autofocus required>
                </span>
            </label>
            <label class="login-password-field">
                <span>Kata Sandi</span>
                <span class="login-input">
                    {!! $icon('<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>') !!}
                    <input type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" data-password required>
                    <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                        {!! $icon('<path d="M3 3l18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 5.2A11.8 11.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-2.1 3.2M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/>', 'password-icon-hidden') !!}
                        {!! $icon('<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>', 'password-icon-visible') !!}
                    </button>
                </span>
            </label>
            <button type="submit" class="login-submit">
                <span>Masuk</span>
                {!! $icon('<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5m5 5H3"/>') !!}
            </button>
            <button type="button" class="login-access-reset" data-login-access-reset>
                {!! $icon('<path d="M19 12H5m6-6-6 6 6 6"/>') !!}
                <span>Pilih Akses Masuk</span>
            </button>
        </form>
    </section>
    @include('partials.app-footer')
</main>
</body>
</html>
