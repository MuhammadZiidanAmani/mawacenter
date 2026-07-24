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
    $isWaliMode = old('login_type') === 'wali' || old('guardian_unit_id');
@endphp
<main class="login-page">
    <section class="login-card">
        <div class="login-brand">
            <div class="login-brand-mark">
                <img src="{{ asset('images/mawa-center-mark-transparent.png') }}" alt="">
            </div>
            <h1>MA'WA <span>CENTER</span></h1>
        </div>

        <div class="login-hero-copy">
            <h2>Silakan Masuk</h2>
        </div>

        @if (session('status'))
            <div class="login-alert success">{!! $icon('<path d="m5 12 4 4L19 6"/>') !!}<span>{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="login-alert error">{!! $icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>') !!}<span>{{ $errors->first() }}</span></div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="login-form{{ $isWaliMode ? ' wali-mode' : '' }}" data-login-form>
            @csrf
            <input type="hidden" name="login_type" value="{{ $isWaliMode ? 'wali' : '' }}" data-login-type-value>

            {{-- Field Unit Pendidikan: hanya tampil di mode Wali Santri --}}
            <label class="login-guardian-field">
                <span>Unit Pendidikan</span>
                <span class="login-input">
                    {!! $icon('<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 13h8M10 19v-4h4v4"/>') !!}
                    <select name="guardian_unit_id" data-guardian-unit>
                        <option value="">Pilih unit pendidikan</option>
                        @foreach($educationUnits as $unit)
                            <option value="{{ $unit->id }}" @selected(old('guardian_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>
                        @endforeach
                    </select>
                </span>
            </label>

            {{-- Field Username / NIS --}}
            <label>
                <span data-login-username-label>{{ $isWaliMode ? 'NIS Santri' : 'Username' }}</span>
                <span class="login-input">
                    {!! $icon('<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-4 2.9-6 7-6s6.4 2 7 6"/>') !!}
                    <input type="text" name="username" value="{{ old('username') }}"
                        placeholder="{{ $isWaliMode ? 'Masukkan NIS santri' : 'Masukkan username' }}"
                        autocomplete="username" autofocus required
                        data-login-username-input>
                </span>
            </label>

            {{-- Field Kata Sandi: hanya tampil di mode normal --}}
            <label class="login-password-field">
                <span>Kata Sandi</span>
                <span class="login-input">
                    {!! $icon('<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>') !!}
                    <input type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" data-password>
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

            {{-- Toggle Wali Santri --}}
            <div class="login-mode-switch">
                <button type="button" class="login-wali-toggle" data-wali-toggle>
                    {!! $icon('<circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="10" r="2.5"/><path d="M14.5 15.5A4.5 4.5 0 0 1 21 19"/>') !!}
                    <span>Saya Wali Santri? Masuk di sini</span>
                    {!! $icon('<path d="M9 18l6-6-6-6"/>') !!}
                </button>
                <button type="button" class="login-wali-reset" data-wali-reset>
                    {!! $icon('<path d="M19 12H5m6-6-6 6 6 6"/>') !!}
                    <span>Kembali ke login biasa</span>
                </button>
            </div>
        </form>
    </section>
    @include('partials.app-footer')
</main>
</body>
</html>
