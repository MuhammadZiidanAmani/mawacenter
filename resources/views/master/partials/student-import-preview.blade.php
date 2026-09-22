@php
    $studentImportRows = $studentImportPreview['rows'] ?? [];
    $studentImportTotal = max((int) ($studentImportPreview['total'] ?? count($studentImportRows)), 0);
    $studentImportValid = (int) ($studentImportPreview['valid'] ?? 0);
    $studentImportCreated = (int) ($studentImportPreview['created'] ?? collect($studentImportRows)->where('status', 'Baru')->count());
    $studentImportUpdated = (int) ($studentImportPreview['updated'] ?? collect($studentImportRows)->where('status', 'Update')->count());
    $studentImportFailuresCount = count($studentImportPreview['failures'] ?? []);
    $studentImportDisplayRows = collect($studentImportRows)->values();
@endphp
<section class="student-import-preview student-import-datatable-preview">
    <div class="student-import-preview-head">
        <div class="student-import-preview-title">
            <h2>Preview Import Data Siswa</h2>
            <p>Periksa ringkasan validasi sebelum data disimpan.</p>
        </div>
        <form method="POST" action="{{ route('master.students.import') }}" data-student-import-confirm data-progress-url="{{ route('master.students.import.progress', $studentImportToken) }}">
            @csrf
            <input type="hidden" name="token" value="{{ $studentImportToken }}">
            <button class="button button-primary student-import-confirm {{ $studentImportValid < 1 ? 'is-disabled' : '' }}" @disabled($studentImportValid < 1)>{!! $icon('check') !!} <span data-student-import-confirm-label>Konfirmasi</span></button>
        </form>
    </div>

    <div class="student-import-progress-card" data-student-import-progress hidden>
        <div class="student-import-progress-head">
            <div>
                <strong data-student-import-progress-status>Menunggu konfirmasi</strong>
                <span data-student-import-progress-message>Import akan diproses setelah tombol Konfirmasi diklik.</span>
            </div>
            <b data-student-import-progress-percent>0%</b>
        </div>
        <div class="student-import-progress-track" aria-hidden="true">
            <span data-student-import-progress-bar></span>
        </div>
        <div class="student-import-progress-meta">
            <span>Diproses <strong data-student-import-progress-processed>0</strong>/<strong data-student-import-progress-total>{{ number_format($studentImportTotal, 0, ',', '.') }}</strong></span>
            <span>Baru <strong data-student-import-progress-created>0</strong></span>
            <span>Update <strong data-student-import-progress-updated>0</strong></span>
            <span>Gagal <strong data-student-import-progress-failed>0</strong></span>
        </div>
    </div>

    <div class="student-import-summary-grid" aria-label="Ringkasan preview import">
        <div class="student-import-summary-card">
            <span>Total</span>
            <strong>{{ number_format($studentImportTotal, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card valid">
            <span>Baru</span>
            <strong>{{ number_format($studentImportCreated, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card update">
            <span>Update</span>
            <strong>{{ number_format($studentImportUpdated, 0, ',', '.') }}</strong>
        </div>
        <div class="student-import-summary-card failed">
            <span>Gagal</span>
            <strong>{{ number_format($studentImportFailuresCount, 0, ',', '.') }}</strong>
        </div>
    </div>

    @if($studentImportFailuresCount < 1)
        <div class="student-import-validation-state success">
            <strong>Semua data siap diimpor.</strong>
            <span>{{ number_format($studentImportValid, 0, ',', '.') }} data siap diproses.</span>
        </div>
    @endif
    <div class="student-import-problem-title">
        <strong>Hasil Validasi</strong>
        <span>Status Baru akan ditambahkan, Update akan memperbarui data yang sudah ada, Gagal perlu diperiksa.</span>
    </div>
    <div class="table-wrap spp-import-table-wrap"><table class="data-table student-flat-table spp-import-table student-import-preview-table student-import-preview-table-v3">
        <colgroup>
            <col class="student-col-no">
            <col class="student-col-nis">
            <col class="student-col-name">
            <col class="student-import-col-unit">
            <col class="student-import-col-class">
            <col class="student-import-col-status">
            <col class="student-import-col-message">
        </colgroup>
        <thead><tr>
            <th>No</th>
            <th>NIS</th>
            <th class="student-import-name-heading">Nama</th>
            <th>Unit</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr></thead><tbody>
        @foreach($studentImportDisplayRows as $row)
        @php
            $studentImportMessage = $row['message'] ?? match ($row['status']) {
                'Baru' => 'Data baru siap diimpor.',
                'Update' => 'Data yang sudah ada akan diperbarui.',
                default => 'Baris gagal diproses. Periksa format data.',
            };
        @endphp
        <tr class="spp-import-row {{ strtolower($row['status']) }}" data-student-import-row data-status="{{ strtolower($row['status']) }}" data-search="{{ strtolower(implode(' ', [$row['nis'], $row['name'], $row['unit'], $row['class'], $row['status'], $studentImportMessage])) }}">
            <td>{{ $loop->iteration }}</td>
            <td><strong class="spp-import-nis">{{ $row['nis'] ?: '-' }}</strong></td>
            <td class="student-import-name-cell">
                <strong class="student-import-name">{{ $row['name'] ?: '-' }}</strong>
            </td>
            <td><span class="education-code">{{ $row['unit'] ?: '-' }}</span></td>
            <td><span class="student-import-class">{{ $row['class'] ?: '-' }}</span></td>
            <td><span class="student-import-status-badge {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td>
            <td><span class="student-import-message">{{ $studentImportMessage }}</span></td>
        </tr>
        @endforeach
    </tbody></table></div>
</section>
