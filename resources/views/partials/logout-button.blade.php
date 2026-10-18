@php
    $accountProfileIcon = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>';
    $accountSettingsIcon = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>';
@endphp

<div class="account-menu" data-account-menu>
    <button
        class="icon-button account-menu-toggle"
        type="button"
        data-account-menu-toggle
        aria-label="Menu akun"
        aria-expanded="false"
        aria-controls="account-menu-popup"
        title="Menu akun"
    >{!! $accountProfileIcon !!}</button>
    <div id="account-menu-popup" class="account-menu-popup" data-account-menu-popup role="menu" aria-label="Menu akun" hidden>
        @if(auth()->user()?->hasPermission('settings.view'))
            <a href="{{ route('settings.index') }}" class="account-menu-item" role="menuitem">
                {!! $accountSettingsIcon !!}
                <span>Pengaturan Akun</span>
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="logout-form" data-account-menu-logout>
            @csrf
            <button class="account-menu-item" type="submit" role="menuitem" aria-label="Keluar" title="Keluar">
                {!! $icon !!}
                <span>Keluar</span>
            </button>
        </form>
    </div>
</div>
