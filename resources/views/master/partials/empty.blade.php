@php
    $emptyColspan = $emptyColspan ?? match ($tab ?? null) {
        'students', 'data-roles' => 7,
        default => 6,
    };
@endphp
<tr><td colspan="{{ $emptyColspan }}"><div class="empty-state"><strong>Belum ada data</strong><span>Tambahkan data baru untuk mulai mengelola master data.</span></div></td></tr>
