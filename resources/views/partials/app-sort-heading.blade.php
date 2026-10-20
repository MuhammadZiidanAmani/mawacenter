@php
    $appSortCurrent = request('sort') === $column;
    $appSortDirection = request('direction') === 'desc' ? 'desc' : 'asc';
    $appSortNextDirection = $appSortCurrent && $appSortDirection === 'asc' ? 'desc' : 'asc';
    $appSortDirectionLabel = $appSortNextDirection === 'asc' ? 'naik' : 'turun';
    $appSortUrl = request()->url().'?'.http_build_query(array_merge(
        request()->except(['sort', 'direction', 'page']),
        ['sort' => $column, 'direction' => $appSortNextDirection],
    ));
@endphp

<a
    href="{{ $appSortUrl }}"
    @class(['app-sort-heading', 'is-sorted' => $appSortCurrent, $sortClass ?? ''])
    aria-label="Urutkan {{ $label }} {{ $appSortDirectionLabel }}"
    title="Urutkan {{ $label }} {{ $appSortDirectionLabel }}"
>
    <span class="app-sort-label">{{ $label }}</span>
    <span @class(['app-sort-indicator', 'is-neutral' => ! $appSortCurrent]) aria-hidden="true">
        @if(! $appSortCurrent)
            <svg class="app-sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m7 15 5 5 5-5" />
                <path d="m7 9 5-5 5 5" />
            </svg>
        @elseif($appSortDirection === 'asc')
            <svg class="app-sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 7-7 7 7" />
                <path d="M12 19V5" />
            </svg>
        @else
            <svg class="app-sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14" />
                <path d="m19 12-7 7-7-7" />
            </svg>
        @endif
    </span>
    @if($appSortCurrent)
        <span class="sr-only">Sedang diurutkan {{ $appSortDirection === 'asc' ? 'naik' : 'turun' }}</span>
    @endif
</a>
