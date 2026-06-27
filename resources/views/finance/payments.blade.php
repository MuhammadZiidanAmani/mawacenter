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
        'activePaymentMenu' => $mode === 'import' ? 'import' : ($mode === 'history' ? 'history' : 'transaction'),
    ])
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <div class="main-panel">
        <header class="topbar">
            <button class="icon-button menu-toggle always-visible" type="button" data-sidebar-toggle aria-label="Buka atau tutup sidebar">☰</button>
            <div class="active-year-pill"><span></span><small>Tahun Pelajaran Aktif:</small><strong>{{ $activeAcademicYear?->name ?? 'Belum diatur' }}</strong></div>
            <div class="topbar-spacer"></div>
        </header>

        <main @class(['payment-hub-page', 'student-page payment-flat-page' => in_array($mode, ['payment', 'history'], true)])>
            @if($mode === 'payment')
                <div class="student-flat-header payment-page-heading">
                    <div class="student-master-heading">
                        <h1>Transaksi Baru</h1>
                        <p>Cari siswa, pilih jenis tagihan, lalu proses pembayaran sesuai kebutuhan.</p>
                    </div>
                </div>
            @endif

            <section @class(['payment-hub-heading' => $mode === 'import', 'student-workspace payment-transaction-workspace' => $mode === 'payment', 'student-workspace payment-history-workspace' => $mode === 'history'])>
                @if($mode === 'payment')
                    <form method="GET" action="{{ route('finance.payments.index') }}" class="student-filter-panel payment-transaction-search">
                        <label>
                            <span>Cari Siswa</span>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Ketik nama, NIS, atau NISN..." autofocus required>
                        </label>
                        <div class="student-filter-actions payment-search-actions">
                            <button class="button student-search-button">Cari</button>
                            <a href="{{ route('finance.payments.index') }}" class="button student-filter-reset">Reset</a>
                        </div>
                    </form>

                    @if($search !== '')
                        <div class="payment-transaction-results">
                            <div class="table-wrap">
                                <table class="payment-transaction-table">
                                    <colgroup>
                                        <col class="column-number">
                                        <col class="column-nis">
                                        <col class="column-name">
                                        <col class="column-unit">
                                        <col class="column-class">
                                        <col class="column-bill">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="column-number">No</th>
                                            <th class="column-nis">NIS</th>
                                            <th class="column-name">Nama Siswa</th>
                                            <th class="column-unit">Unit Pendidikan</th>
                                            <th class="column-class">Kelas</th>
                                            <th class="column-bill">Tagihan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($people as $registrations)
                                            @php($identity = $registrations->firstWhere('identity_student_id', null) ?? $registrations->first())
                                            @foreach($registrations as $student)
                                                @php($options = $student->payment_options ?? [])
                                                <tr>
                                                    <td class="column-number">{{ $loop->parent->iteration }}{{ $registrations->count() > 1 ? '.'.$loop->iteration : '' }}</td>
                                                    <td class="column-nis">{{ $student->nis }}</td>
                                                    <td class="column-name payment-student-name">{{ $identity->name }}</td>
                                                    <td class="column-unit"><span class="education-code">{{ $student->schoolClass?->educationUnit?->code ?? '-' }}</span></td>
                                                    <td class="column-class">{{ $student->schoolClass?->name ?? '-' }}</td>
                                                    <td class="column-bill">
                                                        <div class="payment-option-buttons-flat">
                                                            @if(in_array('spp', $options, true))
                                                                <a href="{{ route('finance.spp.create', ['student_id' => $student->id]) }}">SPP</a>
                                                            @endif
                                                            @if(in_array('daftar-ulang', $options, true))
                                                                <a href="{{ route('finance.other.create', ['category' => 'daftar-ulang', 'student_id' => $student->id]) }}">Daftar Ulang</a>
                                                            @endif
                                                            @if(in_array('laundry', $options, true))
                                                                <a href="{{ route('finance.other.create', ['category' => 'laundry', 'student_id' => $student->id]) }}">Laundry</a>
                                                            @endif
                                                            @if(in_array('lain-lain', $options, true))
                                                                <a href="{{ route('finance.other.create', ['student_id' => $student->id]) }}">Lainnya</a>
                                                            @endif
                                                            @if(empty($options))
                                                                <small>Belum ada kategori</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr><td colspan="6" class="empty-state">Siswa tidak ditemukan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="payment-transaction-results">
                            <div class="empty-state">
                                <strong>Cari siswa terlebih dahulu</strong>
                                <span>Gunakan nama, NIS, atau NISN. Jika siswa terdaftar di beberapa unit, setiap unit akan tampil sebagai baris terpisah.</span>
                            </div>
                        </div>
                    @endif
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
