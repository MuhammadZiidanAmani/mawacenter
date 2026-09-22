@php($sortDirection = request('sort') === $column ? request('direction', 'asc') : null)
<span class="list-sort-heading list-sort-heading-v6">
    <span class="list-sort-label">{{ $label }}</span>
    <span class="list-sort-arrows">
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'asc'])) }}"
           @class(['is-active' => $sortDirection === 'asc'])
           title="Urutkan {{ $label }} naik"
           aria-label="Urutkan {{ $label }} naik">↑</a>
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'desc'])) }}"
           @class(['is-active' => $sortDirection === 'desc'])
           title="Urutkan {{ $label }} turun"
           aria-label="Urutkan {{ $label }} turun">↓</a>
    </span>
</span>
