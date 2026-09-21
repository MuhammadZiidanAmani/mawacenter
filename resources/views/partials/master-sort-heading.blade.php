@php
    $masterSortCurrent = request('sort') === $column;
    $masterSortDirection = request('direction') === 'desc' ? 'desc' : 'asc';
    $masterSortNextDirection = $masterSortCurrent && $masterSortDirection === 'asc' ? 'desc' : 'asc';
    $masterSortIcon = $masterSortCurrent ? ($masterSortDirection === 'asc' ? 'sort-up' : 'sort-down') : 'sort';
    $masterSortDirectionLabel = $masterSortNextDirection === 'asc' ? 'naik' : 'turun';
    $masterSortUrl = request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), [
        'sort' => $column,
        'direction' => $masterSortNextDirection,
    ]));
@endphp
<th @class([$thClass ?? '', 'master-sortable-heading', 'is-sorted' => $masterSortCurrent])>
    <a class="master-sort-link" href="{{ $masterSortUrl }}" aria-label="Urutkan {{ $label }} {{ $masterSortDirectionLabel }}" title="Urutkan {{ $label }} {{ $masterSortDirectionLabel }}">
        <span>{{ $label }}</span>
        <span class="master-sort-indicator" aria-hidden="true">{!! $icon($masterSortIcon) !!}</span>
        @if($masterSortCurrent)
            <span class="master-sr-only">Sedang diurutkan {{ $masterSortDirection === 'asc' ? 'naik' : 'turun' }}</span>
        @endif
    </a>
</th>
