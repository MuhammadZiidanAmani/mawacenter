# Perkembangan Proyek MA'WA CENTER

Terakhir diperbarui: **1 November 2026**
Dasar pemeriksaan: repository lokal pada commit `80e4b754` dan dokumentasi induk di `ai/`.

Dokumen ini adalah ringkasan perkembangan teknis aplikasi. PRD tetap menjadi sumber kebutuhan produk, sedangkan status di bawah didasarkan pada route, controller, service, view, migration, dan test yang tersedia di repository.

## Cara Membaca Status

| Status | Arti |
| --- | --- |
| **Tersedia** | Entry point dan implementasi utama ditemukan di kode. |
| **Tercakup test** | Skenario test ditemukan, tetapi belum tentu dijalankan pada pemeriksaan terbaru. |
| **Perlu verifikasi** | Implementasi tersedia, tetapi hasil test, build, QA browser, atau kondisi produksi belum diperiksa saat pembaruan dokumen ini. |
| **Gap** | Kebutuhan PRD belum ditemukan atau kode saat ini masih berbeda dari kebutuhan. |

Status **Tersedia** tidak otomatis berarti siap produksi. Kesiapan rilis tetap memerlukan test terisolasi, build frontend, audit akses, QA browser, dan pemeriksaan deployment.

## Ringkasan Teknis

- Laravel **13.15.0** dengan PHP **^8.3**.
- Blade server-rendered; aplikasi bukan SPA.
- Vite 8, Tailwind CSS v4, JavaScript modular, dan CSS modular berbasis token `--app-*`.
- 116 route aplikasi aktif.
- 13 controller, 20 model, 17 service, dan 51 migration.
- 54 Blade view.
- 212 metode feature test dan 2 metode unit test terdefinisi.
- Isolasi test dikonfigurasi menggunakan SQLite `:memory:` dan guard pada `tests/TestCase.php`.

## Status Modul

| Area | Status | Bukti implementasi | Catatan |
| --- | --- | --- | --- |
| Autentikasi dan akses | **Tersedia · Tercakup test** | Login biasa/wali, logout, rate limit, reset akun legacy, middleware permission, scope unit dan siswa | Skenario akses termasuk dalam full suite yang lulus. |
| Dashboard | **Tersedia · Tercakup test** | Ringkasan penerimaan, tagihan, tren bulanan, aktivitas, dan rekap unit | Query serta cache key sudah mengikuti scope unit/siswa pengguna. |
| Data siswa | **Tersedia · Tercakup test** | Daftar, tambah, edit, import preview/progress, export XLSX, filter, sort, pagination, perapihan status masuk, dan perapihan massal awal tagihan | Tambah/import baru memakai default Siswa Baru. Rapikan Status Masuk memeriksa seluruh data sesuai scope, hanya mengubah bukti kuat, mempertahankan pindahan/kasus ambigu/nonaktif, menyinkronkan tagihan, dan mencatat audit. Kolom awal tagihan kosong pada MTs, MA, dan PONPES langsung terisi 1 Agustus sesuai tahun masuk. |
| Pindah/naik kelas dan alumni | **Tersedia · Tercakup test** | Pindah kelas, promosi tahun/kelas, penonaktifan individual, alumni massal, daftar alumni, reaktivasi, dan perapihan tagihan alumni lama | Preview perapihan menunjukkan Batalkan/Tinjau dan mengikuti scope unit. Tagihan pasca-keluar yang belum dibayar dibatalkan; tagihan manual, periode keluar/sebelumnya, pembayaran sah, dan transaksi dipertahankan. |
| Rapikan identitas | **Tersedia · Tercakup test** | Kandidat duplikat, tinjau, gabung, dan pisah identitas | Test lulus: transaksi/tagihan tetap pada `student_id` asal. |
| Kualitas Data | **Gap** | Tidak ditemukan route `/manajemen-siswa/kualitas-data` | Kebutuhannya masih tercatat dalam PRD dan perlu dirancang sebagai fitur terpisah. |
| Data master | **Tersedia · Tercakup test** | Tahun pelajaran, unit, kelas, kategori pembayaran, keringanan, role, dan user | Pendaftaran dikunci untuk Siswa Baru/Pindahan; Daftar Ulang dikunci untuk Siswa Lama. Keduanya wajib, sekali bayar, dan menunggu Sinkron Tagihan sebelum diproses. Form tambah/edit kompleks sudah diseragamkan sebagai halaman penuh; edit data sederhana tetap modal. |
| Pembayaran | **Tersedia · Tercakup test** | Kasir terpadu, SPP, pendaftaran, daftar ulang/lain-lain/laundry, cicilan, import, koreksi, pembatalan, struk, dan riwayat | Pendaftaran dan Daftar Ulang tampil terpisah berdasarkan status masuk siswa serta tagihan hasil sinkronisasi. Pilihan dan nominal SPP memakai tagihan aktif yang sudah diterbitkan sebagai sumber tunggal. Alumni dengan kewajiban SPP atau kategori nonbulanan yang masih bersisa tetap dapat diproses sesuai scope dan tidak diberi biaya opsional baru. Transfer manual hanya dapat dicatat Super Admin. |
| Tagihan | **Tersedia · Tercakup test** | Daftar tunggakan, detail, surat, PDF, sinkronisasi bertahap, dan progress | Pembukaan menu tidak otomatis membuat tagihan; sinkronisasi tersedia sebagai aksi terpisah. |
| Portal wali dan transfer | **Dinonaktifkan sementara · Riwayat dipertahankan** | Wali tetap melihat tagihan dan riwayat; controller lama serta test historis dipertahankan | Pengajuan bukti, menu Verifikasi Transfer, dan endpoint terkait ditutup secara default menunggu payment gateway. |
| Laporan | **Tersedia · Tercakup test** | Transaksi, SPP per bulan, SPP tahun pelajaran, rekap unit, export PDF/XLSX | Skenario laporan termasuk dalam full suite yang lulus. |
| Pengaturan | **Tersedia · Sesuai keputusan sementara** | Profil, username, email, dan password | Field serta pemrosesan rekening global telah dihapus dari endpoint profil. Data lama tidak dihapus. |
| UI dan tema | **Tersedia · Build dan regresi lulus** | Design token, komponen bersama, CSS per modul, dark mode, sidebar responsif, font DM Sans, kontrak bersama untuk header, filter, toolbar/footer data, form tambah/edit, modal, dan aksi tabel, serta navigasi keyboard dialog | Form kompleks Master Data kini memakai struktur halaman penuh yang sama pada tambah/edit; form sederhana tetap ringkas di modal. QA visual browser belum dapat dijalankan karena koneksi Computer Use lokal tidak tersedia. |

## Cakupan Test yang Tersedia

| Suite | Jumlah metode | Fokus utama |
| --- | ---: | --- |
| `AuthTest` | 10 | Login, wali, rate limit, permission default-deny |
| `BillAccessTest` | 14 | Scope tagihan, portal wali, transfer, sinkronisasi |
| `DashboardTest` | 5 | Statistik, empty state, sidebar, dan isolasi cache/scope unit |
| `MasterDataTest` | 102 | Siswa, master, import/export, perapihan status masuk/awal/tagihan alumni, penonaktifan individual/massal, identitas, pembayaran, sasaran Pendaftaran/Daftar Ulang, permission, audit, serta konsistensi halaman tambah/edit |
| `PaymentMenuTest` | 66 | Workspace pembayaran, alumni menunggak, transaksi, bukti, scope, riwayat, struk, dan konsistensi nominal dengan tagihan aktif |
| `ReportMenuTest` | 13 | Filter dan export empat laporan, termasuk pemisahan Pendaftaran dari Lain-lain |
| `PerformanceCacheTest` | 1 | Invalidasi namespace cache |
| Feature/unit contoh dan kertas struk | 3 | Smoke dasar dan ukuran PDF struk |

**Verifikasi 1 November 2026:** full suite lulus **214 test / 2.811 assertion** pada SQLite `:memory:` dan `npm run build` lulus dengan Vite 8.0.16. `MasterDataTest` lulus **102 test / 1.662 assertion**, termasuk default Siswa Baru, preview/perbaikan status masuk, perlindungan data ambigu/pindahan/nonaktif, sasaran serta sinkronisasi Pendaftaran/Daftar Ulang, permission gabungan, dan scope unit. Audit baca-saja pada MySQL lokal memeriksa 2.473 siswa: 380 rekomendasi aman, 5 kasus tingkat lanjutan untuk tinjauan, dan 2.088 konsisten. Konfirmasi memperbaiki 380 status menjadi Siswa Baru; audit ulang menghasilkan 0 kandidat otomatis, 380 Siswa Baru, 2.093 Siswa Lama, dan 5 tinjauan manual. Sinkronisasi terkait menyegarkan 1.738 tagihan dan membuat 380 SPP yang memang belum ada (379 November 2026 dan 1 Agustus 2026); transaksi pembayaran tidak dibuat atau diubah. Verifikasi sebelumnya untuk penonaktifan, alumni, perapihan awal tagihan, kasir, laporan, akses, dan UI tetap tercakup oleh suite lengkap. QA visual browser tetap perlu dilakukan pada lingkungan pengguna.

## Gap dan Risiko Prioritas

### P0 — akses dan integritas

Risiko penghapusan siswa, kebocoran Dashboard lintas unit, rekening global, dan izin keuangan yang terlalu luas telah dimitigasi pada kode. Hapus permanen siswa ditolak, Dashboard memakai scope pada query/cache, dan izin keuangan dipisah melalui migration. Pengajuan/verifikasi transfer wali tetap dinonaktifkan sementara; riwayat lama dipertahankan dan verifier dibatasi hanya Super Admin bila fitur diaktifkan kembali.

### P1 — kelengkapan produk

1. **Kualitas Data belum tersedia**  
   Route, controller, dan view khusus indikator kualitas data belum ditemukan.

2. **QA visual dan deployment belum dicatat**  
   QA responsif/dark mode, print/PDF, smoke test role, dan status migration deployment masih perlu diperiksa sebelum klaim siap rilis.

3. **Status deployment tidak diketahui dari repository**  
   Status migration, queue, konfigurasi Google Drive, rekening transfer, backup, dan database target harus diperiksa pada lingkungan deployment; jangan menyimpan rahasia atau data siswa dalam dokumen ini.

## Perkembangan Terbaru dari Riwayat Git

Ringkasan berikut disimpulkan dari file yang berubah, bukan dari judul commit yang sebagian masih generik.

- **1 November 2026:** Kategori resmi Pendaftaran dan Daftar Ulang dipisahkan. Pendaftaran otomatis menargetkan Siswa Baru/Pindahan, Daftar Ulang menargetkan Siswa Lama; keduanya wajib sekali bayar, masuk Tagihan melalui sinkronisasi, tampil terpisah di kasir/riwayat/import/struk/laporan, dan divalidasi server agar Pendaftaran yang belum disinkronkan tidak dapat dibayar lewat URL langsung.
- **1 November 2026:** Rapikan Status Masuk ditambahkan pada Data Siswa dengan pemeriksaan seluruh scope, tabel khusus data yang perlu ditinjau, klasifikasi konservatif berbasis tanggal/tahun/tingkat/riwayat, permission `students.update` + `bills.sync`, sinkronisasi tagihan, dan audit. Form/import baru default Siswa Baru; nilai import tidak dikenal ditolak. Data lokal diperiksa dan 380 status aman telah diperbaiki, sedangkan 5 penerimaan tingkat lanjutan tetap menunggu keputusan manual. Header Data Siswa juga dirapikan: Tambah/Import/Export tetap sebagai aksi utama, sementara tiga aksi perapihan ditempatkan pada toolbar sekunder agar judul tidak tertekan atau pecah per huruf.
- **29 Oktober 2026:** kasir SPP kini membaca tagihan aktif yang benar-benar tersimpan pada tabel `bills`. Ringkasan kewajiban, jumlah tagihan, rentang bulan, dan pilihan pembayaran tidak lagi berbeda akibat pembuatan bulan historis/future secara virtual dengan tarif saat ini.
- **29 Oktober 2026:** penonaktifan siswa individual dan alumni satu kelas kini sama-sama menyinkronkan tagihan. Kewajiban belum dibayar setelah periode keluar dibatalkan, kewajiban lama tetap tersimpan, dan alumni yang masih menunggak dapat dilunasi melalui Kasir dengan label konteks Alumni tanpa biaya opsional baru.
- **29 Oktober 2026:** Data Siswa mendapat aksi Nonaktifkan dengan konfirmasi tanggal/alasan. Rapikan Tagihan Alumni menambahkan preview rincian Batalkan/Tinjau, permission `students.alumni` + `bills.sync`, scope unit, audit, perlindungan pembayaran/alokasi/tagihan manual, dan penanda alumni tanpa tanggal keluar.
- **29 Oktober 2026:** form Tambah/Edit Siswa dibagi menjadi Identitas, Akademik, Orang Tua, Alamat, dan Status. Heading dan kartu memakai lebar yang sama, pasangan field memakai grid dua kolom desktop/satu kolom mobile, status memiliki panel jelas, dan validasi tampil dekat field terkait.
- **29 Oktober 2026:** kolom awal tagihan kosong pada MTs, MA, dan PONPES langsung terisi 1 Agustus sesuai tahun tanggal masuk pada form tambah, form edit data lama, dan import siswa baru. Petugas tetap dapat menentukan tanggal lain untuk siswa pindahan/kondisi khusus; nilai manual existing tidak ditimpa.
- **29 Oktober 2026:** aksi massal Rapikan Awal Tagihan ditambahkan pada Data Siswa dengan preview, permission `students.update` + `bills.sync`, scope unit, audit, dan perlindungan transaksi. Tagihan pra-Agustus tanpa pembayaran dibatalkan; siswa dengan pembayaran sebagian dilewati untuk ditinjau manual.
- **29 Oktober 2026:** form tambah/edit Master Data diseragamkan. Siswa, kategori pembayaran, keringanan, role, dan user menggunakan halaman penuh dengan kelompok informasi serta footer aksi yang sama; edit tahun pelajaran, unit, dan kelas tetap modal. Route edit kompleks mendapat permission dan scope data yang sama dengan aksi terkait.
- **29 Oktober 2026:** batch kedua konsistensi UI menyatukan aksi filter pada mobile, footer/pagination data, ukuran modal konfirmasi/form/import, serta padding modal responsif; audit source dark/responsif/print dilakukan setelah browser QA tidak tersedia.
- **29 Oktober 2026:** audit aksesibilitas UI menambahkan nama aksesibel pada tombol ikon topbar/sidebar dan menyatukan perilaku fokus dialog: fokus awal, siklus Tab, Escape, dan kembali ke pemicu.
- **28 Oktober 2026:** batch pertama konsistensi UI menyatukan header halaman, aksi header mobile, tombol sekunder, ikon tombol, serta aksi tabel pada riwayat pembayaran/SPP/pembayaran lain-lain.
- **21 Oktober 2026:** penyempurnaan interaksi dan tampilan workspace Pembayaran serta test terkait.
- **20 Oktober 2026:** konsolidasi komponen tabel/sorting, tampilan lintas modul, dan penyesuaian verifikasi transfer.
- **19 Oktober 2026:** konsolidasi style struk layar/PDF, query tagihan untuk pembayaran, dan inisialisasi tema.
- **18 Oktober 2026:** modularisasi JavaScript, invalidasi cache, penguatan design system, dan penyempurnaan UI lintas modul.
- **26 September 2026:** integrasi penyimpanan bukti pembayaran melalui Google Drive yang dapat dikonfigurasi.

## Urutan Pekerjaan yang Disarankan

1. QA desktop 1366px, mobile 360/390–430px/768px, dark mode, serta print/PDF.
2. Tinjau dan terapkan migration permission pada deployment terkontrol setelah backup serta audit role kustom.
3. Implementasikan modul Kualitas Data setelah kebutuhan indikator, permission, filter, dan pagination disepakati.
4. Lakukan smoke test deployment tanpa migrasi destruktif dan tanpa memakai database operasional untuk pengujian otomatis.

## Checklist Pembaruan Dokumen

Setiap kali ada perubahan penting, perbarui bagian berikut:

- Tanggal dan commit dasar pemeriksaan.
- Status modul dan bukti file/route/test.
- Hasil test/build yang benar-benar dijalankan.
- Gap yang ditutup atau risiko baru.
- Perubahan migration, permission, integrasi eksternal, atau kebutuhan deployment.
- Jangan menulis password, `.env`, token Google Drive, hash kredensial, atau identitas siswa.

## Referensi Utama

- `AGENTS.md`
- `ai/README.md`
- `ai/PRD-MAWA-CENTER.md`
- `ai/DESIGN-SYSTEM-TAILWIND.md`
- `composer.json` dan `composer.lock`
- `package.json` dan `vite.config.js`
- `routes/`, `app/`, `resources/`, dan `tests/`

