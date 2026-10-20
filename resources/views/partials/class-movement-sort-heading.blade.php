@php
    $classMovementSortState = request('sort') === $column
        ? (request('direction') === 'desc' ? 'descending' : 'ascending')
        : 'none';
@endphp
<span class="cm-v6-sort-heading" role="columnheader" aria-sort="{{ $classMovementSortState }}">
    @include('partials.app-sort-heading', ['column' => $column, 'label' => $label, 'sortClass' => 'class-movement-sort-link'])
</span>
