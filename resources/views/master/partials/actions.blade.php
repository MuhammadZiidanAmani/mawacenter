@php($actionParameters = $type === 'students' ? array_merge([$row], request()->query()) : [$row])
@php($deleteParameters = $type === 'students' ? array_merge([$type, $row->id], request()->query()) : [$type, $row->id])
<div class="table-actions">
    <button class="icon-button edit-button" type="button" title="Edit" data-edit-record='@json($row)' data-update-action="{{ route('master.'.$type.'.update', $actionParameters) }}">
        <svg class="icon" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
    </button>
    <form method="POST" action="{{ route('master.destroy', $deleteParameters) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="icon-button delete-button" title="Hapus"><svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/></svg></button></form>
</div>
