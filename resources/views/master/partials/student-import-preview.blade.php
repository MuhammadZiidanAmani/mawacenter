@php
    $studentImportRows = $studentImportPreview['rows'] ?? [];
    $studentImportTotal = max((int) ($studentImportPreview['total'] ?? count($studentImportRows)), 0);
    $studentImportValid = (int) ($studentImportPreview['valid'] ?? 0);
    $studentImportDuplicates = (int) ($studentImportPreview['duplicates'] ?? 0);
    $studentImportFailuresCount = count($studentImportPreview['failures'] ?? []);
    $studentImportProblemRows = collect($studentImportRows)->reject(fn ($row) => ($row['status'] ?? '') === 'Valid')->values();
@endphp
<section class="student-import-preview student-import-datatable-preview">
    <div class="student-import-preview-head">
        <h2>Preview Import Data Siswa</h2>
        <div class="student-import-action-bar" aria-label="Ringkasan preview import">
            <span class="student-import-count-button">Total <b>{{ number_format($studentImportTotal, 0, ',', '.') }}</b></span>
            <span class="student-import-count-button valid">Valid <b>{{ number_format($studentImportValid, 0, ',', '.') }}</b></span>
            <span class="student-import-count-button duplicate">Duplikat <b>{{ number_format($studentImportDuplicates, 0, ',', '.') }}</b></span>
            <span class="student-import-count-button failed">Gagal <b>{{ number_format($studentImportFailuresCount, 0, ',', '.') }}</b></span>
            <form method="POST" action="{{ route('master.students.import') }}">@csrf<input type="hidden" name="token" value="{{ $studentImportToken }}"><button class="button button-primary student-import-confirm" @disabled($studentImportValid < 1)>Konfirmasi</button></form>
        </div>
    </div>
    @if($studentImportProblemRows->isEmpty())
        <div class="student-import-validation-state success">
            <strong>Semua data siap diimpor.</strong>
            <span>{{ number_format($studentImportValid, 0, ',', '.') }} data valid dan tidak ada baris bermasalah.</span>
        </div>
    @else
    <div class="student-import-problem-title">
        <strong>Baris perlu diperiksa</strong>
        <span>Hanya data duplikat dan gagal yang ditampilkan.</span>
    </div>
    <div class="table-wrap spp-import-table-wrap"><table class="data-table student-flat-table spp-import-table student-import-preview-table">
        <colgroup>
            <col class="student-col-no">
            <col class="student-col-nis">
            <col class="student-col-name">
            <col class="student-col-unit">
            <col class="student-col-class">
            <col class="student-import-col-status">
            <col class="student-import-col-message">
        </colgroup>
        <thead><tr>
            <th>No</th>
            @foreach ([
                'nis' => 'NIS',
                'name' => 'Nama',
                'unit' => 'Unit Pendidikan',
                'class' => 'Kelas',
            ] as $sortColumn => $sortLabel)
                <th>
                    @include('partials.sortable-heading', ['column' => $sortColumn, 'label' => $sortLabel])
                </th>
            @endforeach
            <th>Status</th>
            <th>Keterangan</th>
        </tr></thead><tbody>
        @foreach($studentImportProblemRows as $row)
        @php
            $studentImportMessage = $row['message'] ?? match ($row['status']) {
                'Valid' => 'Siap diimpor.',
                'Duplikat' => 'Data sudah ada, akan dilewati.',
                default => 'Baris gagal diproses. Periksa format data.',
            };
        @endphp
        <tr class="spp-import-row {{ strtolower($row['status']) }}" data-student-import-row data-status="{{ strtolower($row['status']) }}" data-search="{{ strtolower(implode(' ', [$row['nis'], $row['name'], $row['unit'], $row['class'], $row['status'], $studentImportMessage])) }}"><td>{{ $loop->iteration }}</td><td><strong class="spp-import-nis">{{ $row['nis'] ?: '-' }}</strong></td><td><strong>{{ $row['name'] ?: '-' }}</strong></td><td><span class="education-code">{{ $row['unit'] ?: '-' }}</span></td><td><span class="student-import-class">{{ $row['class'] ?: '-' }}</span></td><td class="student-import-status-text">{{ $row['status'] }}</td><td><span class="student-import-message">{{ $studentImportMessage }}</span></td></tr>
        @endforeach
    </tbody></table></div>
    @endif
</section>
