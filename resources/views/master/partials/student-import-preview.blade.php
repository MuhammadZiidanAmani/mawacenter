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
        <div class="student-import-preview-title">
            <h2>Preview Import Data Siswa</h2>
            <p>Periksa ringkasan validasi sebelum data disimpan.</p>
        </div>
        <form method="POST" action="{{ route('master.students.import') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $studentImportToken }}">
            <button class="button button-primary student-import-confirm {{ $studentImportValid < 1 ? 'is-disabled' : '' }}" @disabled($studentImportValid < 1)>{!! $icon('check') !!} Konfirmasi</button>
        </form>
    </div>

    <div class="student-import-summary-grid" aria-label="Ringkasan preview import">
        <div class="student-import-summary-card">
            <span>Total</span>
            <strong>{{ number_format($studentImportTotal, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card valid">
            <span>Valid</span>
            <strong>{{ number_format($studentImportValid, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card duplicate">
            <span>Duplikat</span>
            <strong>{{ number_format($studentImportDuplicates, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card failed">
            <span>Gagal</span>
            <strong>{{ number_format($studentImportFailuresCount, 0, ',', '.') }}</strong>
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
    <div class="table-wrap spp-import-table-wrap"><table class="data-table student-flat-table spp-import-table student-import-preview-table student-import-preview-table-v3">
        <colgroup>
            <col class="student-col-no">
            <col class="student-col-nis">
            <col class="student-col-name">
            <col class="student-import-col-status">
            <col class="student-import-col-message">
        </colgroup>
        <thead><tr>
            <th>No</th>
            <th>NIS</th>
            <th class="student-import-name-heading">Nama</th>
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
        <tr class="spp-import-row {{ strtolower($row['status']) }}" data-student-import-row data-status="{{ strtolower($row['status']) }}" data-search="{{ strtolower(implode(' ', [$row['nis'], $row['name'], $row['unit'], $row['class'], $row['status'], $studentImportMessage])) }}">
            <td>{{ $loop->iteration }}</td>
            <td><strong class="spp-import-nis">{{ $row['nis'] ?: '-' }}</strong></td>
            <td class="student-import-name-cell">
                <strong class="student-import-name">{{ $row['name'] ?: '-' }}</strong>
            </td>
            <td><span class="student-import-status-badge {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td>
            <td><span class="student-import-message">{{ $studentImportMessage }}</span></td>
        </tr>
        @endforeach
    </tbody></table></div>
    @endif
</section>
