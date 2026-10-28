# Perkembangan Proyek MA'WA CENTER

Terakhir diperbarui: **28 Oktober 2026**  
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
- 104 route aplikasi aktif.
- 13 controller, 20 model, 14 service, dan 51 migration.
- 53 Blade view.
- 194 metode feature test dan 2 metode unit test terdefinisi.
- Isolasi test dikonfigurasi menggunakan SQLite `:memory:` dan guard pada `tests/TestCase.php`.

## Status Modul

| Area | Status | Bukti implementasi | Catatan |
| --- | --- | --- | --- |
| Autentikasi dan akses | **Tersedia · Tercakup test** | Login biasa/wali, logout, rate limit, reset akun legacy, middleware permission, scope unit dan siswa | Skenario akses termasuk dalam full suite yang lulus. |
| Dashboard | **Tersedia · Tercakup test** | Ringkasan penerimaan, tagihan, tren bulanan, aktivitas, dan rekap unit | Query serta cache key sudah mengikuti scope unit/siswa pengguna. |
| Data siswa | **Tersedia · Tercakup test** | Daftar, tambah, edit, import preview/progress, export XLSX, filter, sort, dan pagination | Hapus permanen diblokir; gunakan Alumni/nonaktif agar histori tetap utuh. |
| Pindah/naik kelas dan alumni | **Tersedia · Tercakup test** | Pindah kelas, promosi tahun/kelas, alumni massal, daftar alumni, dan reaktivasi | Jejak audit dan validasi konteks memiliki skenario test. |
| Rapikan identitas | **Tersedia · Tercakup test** | Kandidat duplikat, tinjau, gabung, dan pisah identitas | Test lulus: transaksi/tagihan tetap pada `student_id` asal. |
| Kualitas Data | **Gap** | Tidak ditemukan route `/manajemen-siswa/kualitas-data` | Kebutuhannya masih tercatat dalam PRD dan perlu dirancang sebagai fitur terpisah. |
| Data master | **Tersedia · Tercakup test** | Tahun pelajaran, unit, kelas, kategori pembayaran, keringanan, role, dan user | Permission granular tersedia; perlu pemeriksaan ulang untuk role kustom. |
| Pembayaran | **Tersedia · Tercakup test** | Kasir terpadu, SPP, daftar ulang/lain-lain/laundry, cicilan, import, koreksi, pembatalan, struk, dan riwayat | Transfer manual hanya dapat dicatat Super Admin; izin import, kelola transaksi, sinkron tagihan, dan verifikasi sudah dipisah. |
| Tagihan | **Tersedia · Tercakup test** | Daftar tunggakan, detail, surat, PDF, sinkronisasi bertahap, dan progress | Pembukaan menu tidak otomatis membuat tagihan; sinkronisasi tersedia sebagai aksi terpisah. |
| Portal wali dan transfer | **Dinonaktifkan sementara · Riwayat dipertahankan** | Wali tetap melihat tagihan dan riwayat; controller lama serta test historis dipertahankan | Pengajuan bukti, menu Verifikasi Transfer, dan endpoint terkait ditutup secara default menunggu payment gateway. |
| Laporan | **Tersedia · Tercakup test** | Transaksi, SPP per bulan, SPP tahun pelajaran, rekap unit, export PDF/XLSX | Skenario laporan termasuk dalam full suite yang lulus. |
| Pengaturan | **Tersedia · Sesuai keputusan sementara** | Profil, username, email, dan password | Field serta pemrosesan rekening global telah dihapus dari endpoint profil. Data lama tidak dihapus. |
| UI dan tema | **Tersedia · Build lulus** | Design token, komponen bersama, CSS per modul, dark mode, sidebar responsif, font DM Sans, serta kontrak bersama untuk header halaman dan aksi tabel | Ukuran heading, aksi header, tombol sekunder, ikon tombol, dan aksi tabel lintas menu sudah diseragamkan pada batch pertama; QA visual desktop/mobile/dark/print masih perlu dilakukan. |

## Cakupan Test yang Tersedia

| Suite | Jumlah metode | Fokus utama |
| --- | ---: | --- |
| `AuthTest` | 10 | Login, wali, rate limit, permission default-deny |
| `BillAccessTest` | 14 | Scope tagihan, portal wali, transfer, sinkronisasi |
| `DashboardTest` | 5 | Statistik, empty state, sidebar, dan isolasi cache/scope unit |
| `MasterDataTest` | 88 | Siswa, master, import/export, identitas, pembayaran, tagihan, permission, audit |
| `PaymentMenuTest` | 63 | Workspace pembayaran, transaksi, bukti, scope, riwayat, struk |
| `ReportMenuTest` | 12 | Filter dan export empat laporan |
| `PerformanceCacheTest` | 1 | Invalidasi namespace cache |
| Feature/unit contoh dan kertas struk | 3 | Smoke dasar dan ukuran PDF struk |

**Verifikasi 28 Oktober 2026:** full suite lulus, **196 test / 2.581 assertion**, pada SQLite `:memory:`. Setelah batch konsistensi UI, `PaymentMenuTest` kembali lulus **63 test / 766 assertion** dan `npm run build` lulus dengan Vite 8.0.16. Smoke HTTP pada server dan SQLite sementara lulus untuk Dashboard, Data Siswa tanpa aksi hapus, Pembayaran, Tagihan, Pengaturan tanpa rekening, serta endpoint Verifikasi Transfer yang menghasilkan 404. Migration permission baru ikut teruji pada database test, tetapi belum diterapkan ke database operasional. QA visual browser dan status migration deployment belum diperiksa.

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

