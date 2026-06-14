@php($sortDirection = request('sort') === $column ? request('direction', 'asc') : null)
<span class="list-sort-heading">
    {{ $label }}
    <span class="list-sort-arrows">
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'asc'])) }}"
           class="{{ $sortDirection === 'asc' ? 'active' : '' }}"
           title="Urutkan {{ $label }} naik"
           aria-label="Urutkan {{ $label }} naik">↑</a>
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'desc'])) }}"
           class="{{ $sortDirection === 'desc' ? 'active' : '' }}"
           title="Urutkan {{ $label }} turun"
           aria-label="Urutkan {{ $label }} turun">↓</a>
    </span>
</span>
