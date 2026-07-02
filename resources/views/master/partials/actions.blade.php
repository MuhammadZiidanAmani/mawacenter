@php
    $actionParameters = $type === 'students' ? array_merge([$row], request()->query()) : [$row];
    $deleteParameters = $type === 'students' ? array_merge([$type, $row->id], request()->query()) : [$type, $row->id];
    $isStudentAction = $type === 'students';
    $isStudentCardAction = $isStudentAction && ($studentCardAction ?? false);
    $studentActionStyle = $isStudentCardAction
        ? 'width:36px !important;height:36px !important;min-width:36px !important;min-height:36px !important;margin:0 !important;padding:0 !important;border:1px solid #d1d5db !important;background:#ffffff !important;box-shadow:none !important;border-radius:8px !important;display:inline-grid !important;place-items:center !important;'
        : 'width:24px !important;height:32px !important;min-width:24px !important;min-height:32px !important;margin:0 !important;padding:0 !important;border:0 !important;background:transparent !important;box-shadow:none !important;border-radius:6px !important;display:inline-grid !important;place-items:center !important;';
    $editRecord = $row->toArray();
    foreach (['birth_date', 'entry_date', 'billing_start_date', 'exit_date', 'start_date', 'end_date'] as $dateField) {
        $dateValue = $row->getAttribute($dateField);
        if ($dateValue instanceof \DateTimeInterface) {
            $editRecord[$dateField] = $dateValue->format('Y-m-d');
        }
    }
@endphp
<div class="table-actions {{ $isStudentAction ? 'student-ghost-actions' : '' }}" @if($isStudentAction) style="display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:{{ $isStudentCardAction ? '8px' : '0' }} !important;width:{{ $isStudentCardAction ? 'auto' : '48px' }} !important;height:{{ $isStudentCardAction ? '36px' : '44px' }} !important;margin:0 !important;padding:0 !important;" @endif>
    <button class="icon-button edit-button" type="button" title="Edit" data-edit-record='@json($editRecord)' data-update-action="{{ route('master.'.$type.'.update', $actionParameters) }}" @if($isStudentAction) style="{{ $studentActionStyle }}color:#0d6b3d !important;" @endif>
        <svg class="icon" viewBox="0 0 24 24" @if($isStudentAction) style="width:16px;height:16px;" @endif><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
    </button>
    <form method="POST" action="{{ route('master.destroy', $deleteParameters) }}" onsubmit="return confirm('Hapus data ini?')" @if($isStudentAction) style="display:inline-flex !important;margin:0 !important;padding:0 !important;" @endif>@csrf @method('DELETE')<button class="icon-button delete-button" title="Hapus" @if($isStudentAction) style="{{ $studentActionStyle }}color:#ef1f2d !important;" @endif><svg class="icon" viewBox="0 0 24 24" @if($isStudentAction) style="width:16px !important;height:16px !important;" @endif><path d="M3 6h18M8 6V4h8v2m-9 0 1 15h8l1-15M10 11v5m4-5v5"/></svg></button></form>
</div>
