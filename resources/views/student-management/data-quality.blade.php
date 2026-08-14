<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kualitas Data Siswa - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="student-data-quality-body">
@php
    $icons = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.1-1.1"/>',
        'sort' => '<path d="m8 9 4-4 4 4M16 15l-4 4-4-4"/>',
        'sort-up' => '<path d="m7 14 5-5 5 5"/>',
        'sort-down' => '<path d="m7 10 5 5 5-5"/>',
    ];
    $icon = fn ($name, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
    $formatNumber = fn ($value) => number_format((int) $value, 0, ',', '.');
    $studentContext = fn ($student) => collect([
        $student->schoolClass?->educationUnit?->code,
        $student->schoolClass?->name,
        $student->academicYear?->name,
    ])->filter()->implode(' / ') ?: '-';
@endphp
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'students',
        'activeStudentMenu' => 'kualitas-data',
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar" title="Buka atau tutup sidebar">{!! $icon('menu') !!}</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
            <button class="icon-button notification-button" type="button" aria-label="Notifikasi" title="Notifikasi">{!! $icon('bell') !!}</button>
            @include('partials.logout-button', ['icon' => $icon('logout')])
        </header>

        <main class="student-page student-data-quality-page">
            <section class="data-quality-heading">
                <div>
                    <h1>Kualitas Data Siswa</h1>
                    <p>Pantau anomali data siswa yang perlu dibersihkan oleh admin tanpa mengubah pembayaran atau tagihan.</p>
                </div>
                <div class="data-quality-summary">
                    <span><strong>{{ $formatNumber($summary['active_students']) }}</strong> aktif</span>
                    <span><strong>{{ $formatNumber($summary['inactive_students']) }}</strong> nonaktif</span>
                    <span><strong>{{ $formatNumber($summary['total_issues']) }}</strong> perlu cek</span>
                </div>
            </section>

            <section class="data-quality-grid" aria-label="Ringkasan kualitas data siswa">
                @foreach ($qualityCards as $card)
                    <article class="data-quality-card is-{{ $card['tone'] }} {{ $selectedIndicator === $card['key'] ? 'is-selected' : '' }}">
                        <div class="data-quality-card-head">
                            <span class="data-quality-card-icon">{!! $icon($card['tone'] === 'danger' || $card['tone'] === 'warning' ? 'alert' : ($card['tone'] === 'neutral' ? 'users' : 'chart')) !!}</span>
                            <div>
                                <h2>{{ $card['title'] }}</h2>
                                <p>{{ $card['description'] }}</p>
                            </div>
                        </div>
                        <div class="data-quality-card-foot">
                            <strong>{{ $formatNumber($card['count']) }}</strong>
                            @if($card['action_url'])
                                <a href="{{ $card['action_url'] }}" aria-label="{{ $card['action_label'] }} {{ $card['title'] }}">{{ $card['action_label'] }}</a>
                            @else
                                <span>{{ $card['disabled_label'] ?? 'Tidak tersedia' }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="data-quality-filter-card">
                <form method="GET" action="{{ route('student-management.data-quality.index') }}" class="data-quality-filter-form">
                    <label>
                        <span>Indikator</span>
                        <select name="indicator">
                            @foreach ($indicatorOptions as $key => $option)
                                <option value="{{ $key }}" @selected($selectedIndicator === $key)>{{ $option['title'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                    <div class="data-quality-filter-actions">
                        <button class="button button-primary" type="submit">Terapkan</button>
                        <a class="button button-secondary" href="{{ route('student-management.data-quality.index') }}">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card master-card student-data-card data-quality-detail-card">
                <div class="data-quality-section-head">
                    <div>
                        <h2>{{ $selectedDetail['title'] }}</h2>
                        <p>{{ $selectedDetail['description'] }}</p>
                    </div>
                    @if($selectedDetail['type'] === 'duplicates' && $canManageIdentityCleanup)
                        <a class="button button-secondary" href="{{ route('student-management.identity-cleanup.index') }}">Rapikan Identitas</a>
                    @endif
                </div>
                <div class="student-reference-card-count student-management-table-toolbar">
                    <form method="GET" action="{{ route('student-management.data-quality.index') }}" class="student-reference-card-length">
                        <input type="hidden" name="indicator" value="{{ $selectedIndicator }}">
                        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                        @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                        <label>Tampilkan
                            <select name="per_page" onchange="this.form.submit()" aria-label="Jumlah data kualitas yang ditampilkan">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected($perPage === (string) $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            data
                        </label>
                    </form>
                </div>
                @if($selectedDetail['type'] === 'duplicates')
                    <div class="table-wrap data-quality-table-wrap">
                        <table class="data-table student-flat-table data-quality-table data-quality-duplicate-table">
                            <thead>
                                <tr>
                                    @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Kandidat'])
                                    @include('partials.master-sort-heading', ['column' => 'reason', 'label' => 'Alasan'])
                                    @include('partials.master-sort-heading', ['column' => 'confidence', 'label' => 'Confidence'])
                                    <th>Data Terdampak</th>
                                    @if($canManageIdentityCleanup)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($selectedDetail['rows'] as $candidate)
                                    <tr>
                                        <td><strong>{{ $candidate['name'] }}</strong></td>
                                        <td>{{ $candidate['reason'] }}</td>
                                        <td><span class="data-quality-badge">{{ $candidate['confidence'] }}</span></td>
                                        <td>
                                            @foreach ($candidate['students'] as $student)
                                                <span class="data-quality-inline-item">{{ $student->nis ?: '-' }} - {{ $student->name }} ({{ $studentContext($student) }})</span>
                                            @endforeach
                                        </td>
                                        @if($canManageIdentityCleanup)
                                            <td class="data-quality-action-cell">
                                                <a class="button button-secondary data-quality-review-link" href="{{ route('student-management.identity-cleanup.show', $candidate['key']) }}" aria-label="Tinjau kandidat {{ $candidate['name'] }}">Tinjau</a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageIdentityCleanup ? 5 : 4 }}" class="data-quality-empty">
                                            <strong>Tidak ada data perlu cek</strong>
                                            <span>Indikator ini sedang bersih.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="table-wrap data-quality-table-wrap">
                        <table class="data-table student-flat-table data-quality-table data-quality-student-table">
                            <thead>
                                <tr>
                                    @include('partials.master-sort-heading', ['column' => 'nis', 'label' => 'NIS'])
                                    @include('partials.master-sort-heading', ['column' => 'name', 'label' => 'Nama Siswa'])
                                    @include('partials.master-sort-heading', ['column' => 'context', 'label' => 'Konteks'])
                                    @include('partials.master-sort-heading', ['column' => 'entry_date', 'label' => 'Tanggal Masuk'])
                                    @include('partials.master-sort-heading', ['column' => 'billing_start_date', 'label' => 'Mulai Tagihan'])
                                    @include('partials.master-sort-heading', ['column' => 'exit_date', 'label' => 'Status Keluar'])
                                    @if($canUpdateStudents)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($selectedDetail['rows'] as $student)
                                    <tr>
                                        <td>{{ $student->nis ?: '-' }}</td>
                                        <td><strong>{{ $student->name }}</strong></td>
                                        <td>{{ $studentContext($student) }}</td>
                                        <td>{{ $student->entry_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $student->billing_start_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $student->exit_date?->format('d/m/Y') ?? '-' }}{{ $student->inactive_reason ? ' / '.$student->inactive_reason : '' }}</td>
                                        @if($canUpdateStudents)
                                            <td class="data-quality-action-cell">
                                                <a class="icon-button" href="{{ route('student-management.students.edit', $student) }}" title="Edit {{ $student->name }}" aria-label="Edit {{ $student->name }}">{!! $icon('edit') !!}</a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canUpdateStudents ? 7 : 6 }}" class="data-quality-empty">
                                            <strong>Tidak ada data perlu cek</strong>
                                            <span>Indikator ini sedang bersih.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                @php
                    $qualityRows = $selectedDetail['rows'];
                    $qualityCurrentPage = $qualityRows->currentPage();
                    $qualityLastPage = $qualityRows->lastPage();
                    $qualityStartPage = max(1, $qualityCurrentPage - 1);
                    $qualityEndPage = min($qualityLastPage, $qualityCurrentPage + 1);
                    if ($qualityCurrentPage <= 2) {
                        $qualityEndPage = min($qualityLastPage, 3);
                    }
                    if ($qualityCurrentPage >= $qualityLastPage - 1) {
                        $qualityStartPage = max(1, $qualityLastPage - 2);
                    }
                    $qualityPageUrl = fn ($page) => route('student-management.data-quality.index', array_merge(request()->except('page'), ['page' => $page]));
                    $qualityResultSummary = $qualityRows->total() > 0
                        ? 'Menampilkan '.$formatNumber($qualityRows->firstItem()).'-'.$formatNumber($qualityRows->lastItem()).' dari '.$formatNumber($qualityRows->total()).' data'
                        : 'Menampilkan 0 dari 0 data';
                @endphp
                <div class="data-quality-pagination student-report-pagination">
                    <p>{{ $qualityResultSummary }}</p>
                    @if($selectedDetail['rows']->lastPage() > 1)
                        <div class="data-quality-pagination-links student-pagination-links" aria-label="Navigasi halaman kualitas data">
                            @if($qualityRows->onFirstPage())
                                <span class="student-page-button is-disabled" aria-disabled="true">Sebelumnya</span>
                            @else
                                <a class="student-page-button" href="{{ $qualityPageUrl($qualityCurrentPage - 1) }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if($qualityStartPage > 1)
                                <a class="student-page-button is-number" href="{{ $qualityPageUrl(1) }}" aria-label="Halaman 1">1</a>
                                @if($qualityStartPage > 2)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                            @endif

                            @for($page = $qualityStartPage; $page <= $qualityEndPage; $page++)
                                @if($page === $qualityCurrentPage)
                                    <span class="student-page-button is-number is-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="student-page-button is-number" href="{{ $qualityPageUrl($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($qualityEndPage < $qualityLastPage)
                                @if($qualityEndPage < $qualityLastPage - 1)
                                    <span class="student-page-ellipsis" aria-hidden="true">...</span>
                                @endif
                                <a class="student-page-button is-number" href="{{ $qualityPageUrl($qualityLastPage) }}" aria-label="Halaman {{ $qualityLastPage }}">{{ $qualityLastPage }}</a>
                            @endif

                            @if($qualityRows->hasMorePages())
                                <a class="student-page-button" href="{{ $qualityPageUrl($qualityCurrentPage + 1) }}" rel="next">Berikutnya</a>
                            @else
                                <span class="student-page-button is-disabled" aria-disabled="true">Berikutnya</span>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        </main>
        @include('partials.app-footer')
    </div>
</div>
</body>
</html>
