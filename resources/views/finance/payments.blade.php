<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'import' ? 'Import Pembayaran' : ($mode === 'history' ? 'Riwayat Pembayaran' : 'Transaksi Baru') }} - MA'WA CENTER</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar', [
        'activeMenu' => 'payment',
        'activePaymentMenu' => $mode === 'history' ? 'history' : ($mode === 'import' ? 'import' : 'transaction'),
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">☰</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
        </header>

        <main @class(['payment-hub-page', 'payment-transaction-page' => $mode === 'payment', 'student-page payment-flat-page' => in_array($mode, ['payment', 'history'], true)])>
            @if($mode === 'payment')
                <div class="student-flat-header payment-page-heading">
                    <div class="student-master-heading">
                        <nav class="payment-responsive-breadcrumb" aria-label="Breadcrumb">
                            <span>Transaksi</span>
                            <span aria-hidden="true">/</span>
                            <strong>Transaksi Baru</strong>
                        </nav>
                        <h1>Transaksi Baru</h1>
                        <p>Cari siswa, pilih pembayaran, lalu proses transaksi sesuai kebutuhan.</p>
                    </div>
                </div>
            @endif

            <section @class(['payment-hub-heading' => $mode === 'import', 'student-workspace payment-transaction-workspace' => $mode === 'payment', 'student-workspace payment-history-workspace' => $mode === 'history'])>
                @if($mode === 'payment')
                    @php
                        $selectedStudentId = (int) ($selectedStudentId ?? 0);
                        $selectedRegistrations = $selectedStudentId
                            ? $people->first(fn ($registrations) => $registrations->contains('id', $selectedStudentId))
                            : null;
                        $selectedIdentity = $selectedRegistrations
                            ? ($selectedRegistrations->firstWhere('identity_student_id', null) ?? $selectedRegistrations->first())
                            : null;
                    @endphp
                    <div class="payment-one-stop-layout">
                        <section class="payment-one-stop-main">
                            <div class="payment-one-stop-card-title">
                                <span aria-hidden="true">CS</span>
                                <strong>Cari Siswa</strong>
                            </div>
                            <form method="GET" action="{{ route('finance.payments.index') }}" class="payment-one-stop-search">
                                <label>
                                    <span>Nama atau NIS Siswa</span>
                                    <input type="search" name="search" value="{{ $search }}" placeholder="Ketik nama, NIS, atau NISN..." autofocus required>
                                </label>
                                <div class="payment-one-stop-actions">
                                    <button class="button payment-one-stop-submit">Cari</button>
                                    <a href="{{ route('finance.payments.index') }}" class="button student-filter-reset">Reset</a>
                                </div>
                            </form>

                            @if($search !== '' && $people->isNotEmpty() && ! $selectedRegistrations)
                                <div class="payment-one-stop-student-list">
                                    @foreach($people as $registrations)
                                        @php
                                            $identity = $registrations->firstWhere('identity_student_id', null) ?? $registrations->first();
                                            $isSelected = $selectedRegistrations && $selectedRegistrations->contains('id', $identity->id);
                                            $unitSummary = $registrations
                                                ->map(fn ($student) => trim(($student->schoolClass?->educationUnit?->code ?? '-') . ' ' . ($student->schoolClass?->name ?? '-')))
                                                ->filter()
                                                ->unique()
                                                ->join(' · ');
                                        @endphp
                                        <a
                                            href="{{ route('finance.payments.index', ['search' => $identity->name, 'student_id' => $identity->id]) }}"
                                            @class(['payment-one-stop-student-card', 'is-selected' => $isSelected])
                                        >
                                            <span class="payment-one-stop-avatar" aria-hidden="true">{{ strtoupper(substr($identity->name, 0, 1)) }}</span>
                                            <span class="payment-one-stop-student-copy">
                                                <strong>{{ $identity->name }}</strong>
                                                <small>NIS: {{ $identity->nis }}{{ $unitSummary ? ' · '.$unitSummary : '' }}</small>
                                            </span>
                                            <span class="payment-one-stop-unit-count">{{ $registrations->count() }} unit</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="payment-one-stop-side">
                            @if($search !== '')
                                @if($people->isEmpty())
                                    <div class="payment-one-stop-empty-state">
                                        <strong>Siswa tidak ditemukan</strong>
                                        <span>Periksa kembali nama, NIS, atau NISN yang dicari.</span>
                                    </div>
                                @elseif(! $selectedRegistrations)
                                    <div class="payment-one-stop-empty-state">
                                        <strong>Belum ada siswa yang dipilih</strong>
                                        <span>Pilih salah satu siswa dari hasil pencarian untuk melihat unit dan pilihan pembayaran.</span>
                                    </div>
                                @else
                                    <article class="payment-one-stop-person">
                                        <div class="payment-one-stop-person-head">
                                            <span class="payment-responsive-avatar" aria-hidden="true">{{ strtoupper(substr($selectedIdentity->name, 0, 1)) }}</span>
                                            <span class="payment-responsive-profile-copy">
                                                <span class="payment-responsive-kicker">Profil Siswa</span>
                                                <strong>{{ $selectedIdentity->name }}</strong>
                                                <small>NIS: {{ $selectedIdentity->nis }}{{ $selectedIdentity->nisn ? ' · NISN: '.$selectedIdentity->nisn : '' }}</small>
                                            </span>
                                            <span class="payment-responsive-status">Aktif</span>
                                        </div>

                                        <div class="payment-one-stop-units">
                                            @foreach($selectedRegistrations as $student)
                                                @php
                                                    $options = collect($student->payment_options ?? []);
                                                    $mandatoryOptions = $options->whereIn('key', ['spp', 'daftar-ulang', 'lain-lain'])->values();
                                                    $optionalOptions = $options->whereIn('key', ['laundry'])->values();
                                                    $unitCode = $student->schoolClass?->educationUnit?->code ?? '-';
                                                @endphp
                                                <div class="payment-one-stop-unit-card">
                                                    <div class="payment-one-stop-unit-meta">
                                                        <span class="payment-responsive-unit-mark" aria-hidden="true">{{ strtoupper(substr($unitCode, 0, 2)) }}</span>
                                                        <span class="payment-responsive-unit-copy">
                                                            <strong>{{ $unitCode }}</strong>
                                                            <span>{{ $student->schoolClass?->name ?? '-' }} · {{ $student->academicYear?->name ?? '-' }}</span>
                                                        </span>
                                                    </div>

                                                    <div class="payment-one-stop-bill-section">
                                                        <span class="payment-one-stop-section-title">Tagihan Wajib</span>
                                                        <div class="payment-one-stop-bill-list">
                                                            @if($mandatoryOptions->isEmpty())
                                                                <span class="payment-one-stop-empty">Tidak ada tagihan wajib aktif.</span>
                                                            @else
                                                                @foreach($mandatoryOptions as $option)
                                                                    @if($option['status'] === 'payable')
                                                                        <a href="{{ $option['url'] }}" class="payment-one-stop-bill is-payable">
                                                                            <span class="payment-one-stop-bill-top">
                                                                                <strong>{{ $option['label'] === 'Lainnya' ? 'Lain-lain' : $option['label'] }}</strong>
                                                                                <small>Proses</small>
                                                                            </span>
                                                                            <span class="payment-one-stop-bill-amount">{{ $option['amount_label'] ?? 'Rp 0' }}</span>
                                                                            <span class="payment-one-stop-bill-detail">{{ $option['detail_label'] ?? '' }}</span>
                                                                        </a>
                                                                    @else
                                                                        <span class="payment-one-stop-bill is-paid">
                                                                            <span class="payment-one-stop-bill-top">
                                                                                <strong>{{ $option['label'] === 'Lainnya' ? 'Lain-lain' : $option['label'] }}</strong>
                                                                                <small>Lunas</small>
                                                                            </span>
                                                                            <span class="payment-one-stop-bill-amount">{{ $option['amount_label'] ?? 'Rp 0' }}</span>
                                                                            <span class="payment-one-stop-bill-detail">{{ $option['detail_label'] ?? '' }}</span>
                                                                        </span>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="payment-one-stop-bill-section">
                                                        <span class="payment-one-stop-section-title">Pembayaran Opsional</span>
                                                        <div class="payment-one-stop-bill-list">
                                                            @if($optionalOptions->isEmpty())
                                                                <span class="payment-one-stop-empty">Laundry belum tersedia untuk siswa ini.</span>
                                                            @else
                                                                @foreach($optionalOptions as $option)
                                                                    @if($option['status'] === 'payable')
                                                                        <a href="{{ $option['url'] }}" class="payment-one-stop-bill is-optional">
                                                                            <span class="payment-one-stop-bill-top">
                                                                                <strong>{{ $option['label'] }}</strong>
                                                                                <small>Proses</small>
                                                                            </span>
                                                                            <span class="payment-one-stop-bill-amount">{{ $option['amount_label'] ?? 'Rp 0' }}</span>
                                                                            <span class="payment-one-stop-bill-detail">{{ $option['detail_label'] ?? '' }}</span>
                                                                        </a>
                                                                    @else
                                                                        <span class="payment-one-stop-bill is-paid">
                                                                            <span class="payment-one-stop-bill-top">
                                                                                <strong>{{ $option['label'] }}</strong>
                                                                                <small>Lunas</small>
                                                                            </span>
                                                                            <span class="payment-one-stop-bill-amount">{{ $option['amount_label'] ?? 'Rp 0' }}</span>
                                                                            <span class="payment-one-stop-bill-detail">{{ $option['detail_label'] ?? '' }}</span>
                                                                        </span>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="payment-one-stop-detail">
                                            <span>Rincian Pembayaran</span>
                                            <strong>Pilih salah satu pembayaran</strong>
                                            <small>Form transaksi dan rincian nominal akan terbuka sesuai jenis pembayaran yang dipilih.</small>
                                        </div>
                                    </article>
                                @endif
                            @else
                                <div class="payment-one-stop-empty-state">
                                    <strong>Cari siswa terlebih dahulu</strong>
                                    <span>Gunakan nama, NIS, atau NISN. Jika siswa terdaftar di beberapa unit, semua unit akan muncul sebagai pilihan pembayaran.</span>
                                </div>
                            @endif
                        </section>
                    </div>
                @elseif($mode === 'history')
                    <div class="student-flat-header">
                        <div class="student-master-heading">
                            <h1>Riwayat Pembayaran</h1>
                            <p>Pilih jenis riwayat untuk melihat transaksi yang sudah tercatat.</p>
                        </div>
                        <div class="student-action-bar">
                            <a class="button student-add-button" href="{{ route('finance.payments.index') }}">Transaksi Baru</a>
                        </div>
                    </div>
                    <div class="payment-history-grid">
                        @foreach([
                            ['SPP', 'Pembayaran bulanan SPP siswa.', route('finance.spp.index'), 'SPP'],
                            ['Daftar Ulang', 'Pembayaran daftar ulang dan biaya awal siswa.', route('finance.other.index', ['category' => 'daftar-ulang']), 'DU'],
                            ['Laundry', 'Pembayaran laundry bulanan siswa.', route('finance.other.index', ['category' => 'laundry']), 'LD'],
                            ['Lain-lain', 'Kategori pembayaran lainnya.', route('finance.other.index'), 'LL'],
                        ] as [$title, $description, $url, $code])
                            <a href="{{ $url }}" class="payment-history-card">
                                <span>{{ $code }}</span>
                                <strong>Riwayat {{ $title }}</strong>
                                <small>{{ $description }}</small>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div>
                        <p class="eyebrow">Pembayaran</p>
                        <h1>Import Pembayaran</h1>
                        <p>Pilih jenis pembayaran, lalu unggah file Excel lama untuk diperiksa.</p>
                    </div>
                    <div class="payment-hub-heading-actions">
                        <a class="button button-secondary" href="{{ route('finance.payments.index') }}">Transaksi Baru</a>
                    </div>
                @endif
            </section>

            @if($mode === 'import')
                <section class="payment-import-grid">
                    @foreach([
                        ['spp', 'SPP', 'Pembayaran bulanan SPP.', route('finance.spp.import.preview')],
                        ['daftar-ulang', 'Daftar Ulang', 'Pembayaran daftar ulang siswa.', route('finance.other.import.preview', ['category' => 'daftar-ulang'])],
                        ['laundry', 'Laundry', 'Hanya transaksi bulan yang benar-benar diikuti.', route('finance.other.import.preview', ['category' => 'laundry'])],
                        ['lain-lain', 'Pembayaran Lain', 'Kategori pembayaran selain SPP, daftar ulang, dan laundry.', route('finance.other.import.preview')],
                    ] as [$key, $title, $description, $action])
                        <article class="card payment-import-card" data-import-category="{{ $key }}">
                            <div class="payment-import-icon">{{ strtoupper(substr($title, 0, 1)) }}</div>
                            <div><h2>{{ $title }}</h2><p>{{ $description }}</p></div>
                            <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                                @csrf
                                <label>
                                    <span>Pilih file Excel</span>
                                    <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                                </label>
                                <button class="button button-primary">Upload dan Preview</button>
                            </form>
                        </article>
                    @endforeach
                </section>
                <div class="payment-import-note"><strong>Sebelum data disimpan</strong><span>Sistem tetap menampilkan preview Valid, Duplikat, dan Gagal. Kolom Unit Pendidikan dan NIS digunakan untuk menemukan siswa yang tepat.</span></div>
            @endif
        </main>
    </div>
</div>
</body>
</html>
