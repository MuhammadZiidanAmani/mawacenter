<form method="GET" action="{{ $action }}" class="list-toolbar app-data-toolbar">
    @foreach (request()->except(array_merge(['per_page', 'search', 'page'], isset($unitFilter) ? ['unit_id'] : [])) as $key => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label class="app-page-size-control">Tampilkan
        <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100, 500] as $size)
                <option value="{{ $size }}" @selected(request('per_page', 10) == $size)>{{ $size }}</option>
            @endforeach
            <option value="all" @selected(request('per_page') === 'all')>Semua</option>
        </select>
        data
    </label>
    @isset($unitFilter)
        <label class="list-toolbar-filter">Unit Pendidikan:
            <select name="unit_id" aria-label="Filter unit pendidikan" onchange="this.form.submit()">
                <option value="">Semua Unit Pendidikan</option>
                @foreach ($unitFilter as $unit)
                    <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->code }}</option>
                @endforeach
            </select>
        </label>
    @endisset
    @isset($sortOptions)
        <div class="list-toolbar-sort" aria-label="Urutkan daftar">
            @foreach ($sortOptions as $column => $label)
                @include('partials.sortable-heading', ['column' => $column, 'label' => $label])
            @endforeach
        </div>
    @endisset
    <label class="app-data-search"><span>Cari data</span>
        <span class="app-field-shell app-data-search-field">
            <input type="search" name="search" value="{{ request('search') }}" aria-label="{{ $searchLabel ?? 'Cari data' }}" placeholder="{{ $searchLabel ?? 'Cari data' }}">
        </span>
    </label>
</form>
