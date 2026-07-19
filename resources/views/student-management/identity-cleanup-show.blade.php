<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tinjau Identitas - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $backUrl = route('student-management.identity-cleanup.index', request()->query());
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'rapikan-identitas',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" aria-label="Notifikasi">{!! $icon('bell') !!}</button>
            <button class="icon-button logout-button" aria-label="Keluar">{!! $icon('logout') !!}</button>
        </header>

        <main class="student-page identity-cleanup-page identity-review-page">
            <section class="student-flat-header identity-cleanup-header">
                <div class="student-master-heading">
                    <h1>Tinjau Identitas</h1>
                    <p>Periksa data siswa yang terdeteksi mirip sebelum digabungkan.</p>
                </div>
                <a href="{{ $backUrl }}" class="button student-filter-reset identity-back-button">Kembali</a>
            </section>

            <section class="card master-card student-data-card identity-review-canvas">
                <form method="POST" action="{{ route('student-management.identity-cleanup.merge') }}" class="identity-review-layout">
                    @csrf
                    <div class="identity-review-main">
                        <div class="identity-review-title">
                            <span>Kandidat Duplikat</span>
                            <strong>{{ $candidate['name'] }}</strong>
                        </div>

                        <div class="table-wrap identity-review-table-wrap">
                            <table class="data-table student-flat-table identity-review-table">
                                <colgroup>
                                    <col class="identity-review-col-check">
                                    <col class="identity-review-col-nis">
                                    <col class="identity-review-col-name">
                                    <col class="identity-review-col-unit">
                                    <col class="identity-review-col-class">
                                    <col class="identity-review-col-year">
                                    <col class="identity-review-col-status">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Pilih</th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Unit</th>
                                        <th>Kelas</th>
                                        <th>Tahun</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($candidate['students'] as $student)
                                        <tr>
                                            <td class="identity-cell-center">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" checked>
                                            </td>
                                            <td class="identity-cell-center">{{ $student->nis }}</td>
                                            <td class="identity-cell-main">{{ $student->name }}</td>
                                            <td class="identity-cell-center">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</td>
                                            <td class="identity-cell-center">{{ $student->schoolClass?->name ?? '-' }}</td>
                                            <td class="identity-cell-center">{{ $student->academicYear?->name ?? '-' }}</td>
                                            <td class="identity-cell-center">
                                                <span class="status-pill {{ $student->is_active ? 'active' : 'inactive' }}">
                                                    {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="identity-review-summary">
                        <span>Ringkasan</span>
                        <strong>{{ $candidate['students']->count() }} data ditemukan</strong>
                        <dl>
                            <div>
                                <dt>Alasan</dt>
                                <dd>{{ $candidate['reason'] }}</dd>
                            </div>
                            <div>
                                <dt>Tingkat Keyakinan</dt>
                                <dd>{{ $candidate['confidence'] }}</dd>
                            </div>
                            <div>
                                <dt>Aksi</dt>
                                <dd>Centang minimal dua data yang benar-benar milik orang yang sama.</dd>
                            </div>
                        </dl>
                        <div class="identity-review-actions">
                            <a href="{{ $backUrl }}" class="button student-filter-reset">Batal</a>
                            <button class="button student-add-button" type="submit">{!! $icon('check') !!} Gabungkan Identitas</button>
                        </div>
                    </aside>
                </form>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
