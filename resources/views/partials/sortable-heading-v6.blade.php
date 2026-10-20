@php
    $legacySortState = request('sort') === $column
        ? (request('direction') === 'desc' ? 'descending' : 'ascending')
        : 'none';
@endphp
<span class="list-sort-heading list-sort-heading-v6" role="columnheader" aria-sort="{{ $legacySortState }}">
    @include('partials.app-sort-heading', ['column' => $column, 'label' => $label])
</span>
