@php($sortDirection = request('sort') === $column ? request('direction', 'asc') : null)
<span class="list-sort-heading list-sort-heading-v6" style="position:relative!important;width:100%!important;min-height:18px!important;display:block!important;margin:0!important;padding:0 18px!important;color:#020617!important;background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif!important;font-size:14px!important;font-weight:600!important;line-height:1.25!important;letter-spacing:0!important;text-align:center!important;text-transform:none!important;">
    <span class="list-sort-label" style="display:block!important;width:100%!important;margin:0!important;padding:0!important;color:#020617!important;background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;font:inherit!important;font-size:14px!important;font-weight:600!important;line-height:1.25!important;letter-spacing:0!important;text-align:center!important;text-transform:none!important;white-space:normal!important;">{{ $label }}</span>
    <span class="list-sort-arrows" style="position:absolute!important;right:0!important;top:50%!important;transform:translateY(-50%)!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:2px!important;margin:0!important;padding:0!important;font-size:14px!important;font-weight:600!important;line-height:1!important;">
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'asc'])) }}"
           class="{{ $sortDirection === 'asc' ? 'active' : '' }}"
           style="display:inline-flex!important;align-items:center!important;justify-content:center!important;width:8px!important;height:18px!important;margin:0!important;padding:0!important;color:{{ $sortDirection === 'asc' ? '#157144' : '#94a3b8' }}!important;background:transparent!important;border:0!important;text-decoration:none!important;font-size:14px!important;font-weight:600!important;line-height:1!important;"
           title="Urutkan {{ $label }} naik"
           aria-label="Urutkan {{ $label }} naik">↑</a>
        <a href="{{ request()->url().'?'.http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => 'desc'])) }}"
           class="{{ $sortDirection === 'desc' ? 'active' : '' }}"
           style="display:inline-flex!important;align-items:center!important;justify-content:center!important;width:8px!important;height:18px!important;margin:0!important;padding:0!important;color:{{ $sortDirection === 'desc' ? '#157144' : '#94a3b8' }}!important;background:transparent!important;border:0!important;text-decoration:none!important;font-size:14px!important;font-weight:600!important;line-height:1!important;"
           title="Urutkan {{ $label }} turun"
           aria-label="Urutkan {{ $label }} turun">↓</a>
    </span>
</span>
