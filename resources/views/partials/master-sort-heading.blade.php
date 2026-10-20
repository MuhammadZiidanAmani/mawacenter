@php($masterSortCurrent = request('sort') === $column)
<th
    @class([$thClass ?? '', 'master-sortable-heading', 'is-sorted' => $masterSortCurrent])
    aria-sort="{{ $masterSortCurrent ? (request('direction') === 'desc' ? 'descending' : 'ascending') : 'none' }}"
>
    @include('partials.app-sort-heading', ['column' => $column, 'label' => $label, 'sortClass' => 'master-sort-link'])
</th>
