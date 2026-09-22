@php($sortDirection = request('sort') === $column ? request('direction', 'asc') : null)
<span class="cm-v6-sort-heading">
    <span class="cm-v6-sort-label">{{ $label }}</span>
    <span class="cm-v6-sort-arrows">
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'asc'])) }}"
           @class(['cm-v6-sort-link', 'is-active' => $sortDirection === 'asc'])
           title="Urutkan {{ $label }} naik"
           aria-label="Urutkan {{ $label }} naik">↑</a>
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'desc'])) }}"
           @class(['cm-v6-sort-link', 'is-active' => $sortDirection === 'desc'])
           title="Urutkan {{ $label }} turun"
           aria-label="Urutkan {{ $label }} turun">↓</a>
    </span>
</span>
