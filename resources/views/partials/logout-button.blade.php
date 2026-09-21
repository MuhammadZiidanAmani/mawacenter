<form method="POST" action="{{ route('logout') }}" class="logout-form">
    @csrf
    <button class="icon-button logout-button" type="submit" aria-label="Keluar" title="Keluar">{!! $icon !!}</button>
</form>
