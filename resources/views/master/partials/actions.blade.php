@php
    $actionParameters = array_merge([$row], request()->query());
    $deleteParameters = array_merge([$type, $row->id], request()->query());
    $isStudentAction = $type === 'students';
    $isStudentCardAction = $isStudentAction && ($studentCardAction ?? false);
    $canEditStudent = auth()->user()?->hasPermission('students.update') ?? false;
    $canDeleteStudent = auth()->user()?->hasPermission('master.manage') ?? false;
    $requiredPermission = in_array($type, ['data-roles', 'data-users'], true) ? 'users.manage' : 'master.manage';
    $canManageMasterRow = auth()->user()?->hasPermission($requiredPermission) ?? false;
    $editRecord = $row->toArray();
    foreach (['birth_date', 'entry_date', 'billing_start_date', 'exit_date', 'start_date', 'end_date'] as $dateField) {
        $dateValue = $row->getAttribute($dateField);
        if ($dateValue instanceof \DateTimeInterface) {
            $editRecord[$dateField] = $dateValue->format('Y-m-d');
        }
    }
@endphp
<div @class(['table-actions', 'student-ghost-actions' => $isStudentAction, 'student-card-actions' => $isStudentCardAction])>
    @if($isStudentAction)
    @if($canEditStudent)
    <a class="icon-button edit-button" href="{{ route('student-management.students.edit', array_merge([$row], request()->query())) }}" title="Edit" aria-label="Edit data">
        {!! $icon('edit') !!}
    </a>
    @endif
    @elseif($canManageMasterRow)
    <button class="icon-button edit-button" type="button" title="Edit" aria-label="Edit data" data-edit-record='@json($editRecord)' data-update-action="{{ route('master.'.$type.'.update', $actionParameters) }}">
        {!! $icon('edit') !!}
    </button>
    @endif
    @if(($isStudentAction && $canDeleteStudent) || (! $isStudentAction && $canManageMasterRow))
    <form method="POST" action="{{ route('master.destroy', $deleteParameters) }}" data-master-delete-form>@csrf @method('DELETE')<button class="icon-button delete-button" title="Hapus" aria-label="Hapus data">{!! $icon('trash') !!}</button></form>
    @endif
</div>
