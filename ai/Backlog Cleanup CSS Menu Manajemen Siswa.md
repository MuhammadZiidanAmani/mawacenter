# Backlog Cleanup CSS Menu Manajemen Siswa

Status: backlog pasca rilis
Tanggal audit: 2026-08-01
Acuan: `ai/standar aplikasi.md`

## Tujuan

Membersihkan sisa CSS Manajemen Siswa yang masih tersebar di `resources/css/app.css` dan `resources/css/modules/payments.css`, lalu memindahkan aturan yang masih relevan ke `resources/css/modules/student-management.css` secara bertahap dan aman.

Cleanup ini tidak wajib untuk rilis P0-P3 karena CSS sudah siap rilis, build hijau, dan tidak ada blocker visual/teknis. Cleanup dilakukan setelah rilis agar risiko regresi lebih mudah dikendalikan.

## Prinsip Cleanup

- Jangan hapus massal selector legacy tanpa pembuktian visual.
- Pindahkan hanya selector yang benar-benar milik Menu Manajemen Siswa ke `student-management.css`.
- Jangan memindahkan selector pembayaran yang memakai kata `student` tetapi konteksnya memang pencarian siswa di pembayaran.
- Setiap batch wajib diakhiri dengan `npm run build`, `php artisan test tests/Feature/MasterDataTest.php`, dan smoke test route terkait.
- Jika selector memakai `!important`, kurangi hanya setelah urutan import dan specificity sudah terbukti aman.

## Audit `resources/css/app.css`

### Selector Yang Masih Tersisa

- Import module Manajemen Siswa sudah benar di `app.css`:
  - `@import "./modules/student-management.css";` di awal file.
- Ada blok CSS lama/minified yang masih mengatur `.student-page`, `.student-filter-panel`, `.student-search-button`, `.student-import-preview`, `.student-data-card`, `.nova-student-table`, dan pagination.
- Ada global foundation/override untuk halaman master dan siswa pada area white page standard.
- Ada selector Data Siswa aktif:
  - `.student-master-table`
  - `.student-ghost-actions`
  - `.student-import-page .student-import-preview-table-v3`
  - `#student-data-filter`
  - `.student-data-body`
  - `.student-export-actions`
- Ada selector Alumni:
  - `#student-alumni-page.student-alumni-page`
  - `.student-alumni-body`
  - `.student-alumni-v7`
- Ada selector generic yang masih menyasar semua `.student-page:not(...)` dengan daftar pengecualian panjang.

### Risiko

Risiko visual sedang jika dipindahkan sekaligus, karena beberapa selector lama memakai specificity tinggi, `:has(...)`, dan `!important` untuk mengalahkan CSS terdahulu.

Risiko teknis rendah jika dipindahkan bertahap per halaman, karena module final `student-management.css` sudah tersedia dan di-import dari `app.css`.

## Audit `resources/css/modules/payments.css`

### Selector Yang Memang Milik Pembayaran

Biarkan di `payments.css`:

- `.payment-one-stop-student-*`
- `.payment-spp-student-*`
- `.payment-registration-student-*`
- `.student-search-picker` dalam scope `.finance-page .payment-create-page`
- `.student-search-results` dalam scope form pembayaran
- `.payment-receipt-student`
- `.payment-student-name`

Selector ini memakai istilah student karena pembayaran memilih siswa, tetapi konteksnya tetap menu pembayaran.

### Selector Yang Nyasar Dari Manajemen Siswa

Kandidat dipindahkan ke `student-management.css`:

- `.student-class-alumni-button`
- `.student-import-confirm`
- `.class-movement-page .class-movement-filter .student-search-button`
- `.student-page .student-title-actions .student-class-alumni-button`
- `.student-class-alumni-page-actions .button-primary`
- `.student-alumni-v7 .student-filter-panel .student-search-button`

### Risiko

Risiko pembayaran rendah selama selector yang scoped ke `.finance-page`, `.payment-flat-page`, dan `.payment-create-page` tidak disentuh.

Risiko visual Manajemen Siswa sedang jika selector tombol dipindahkan tanpa mengecek urutan cascade, karena blok tombol final di `payments.css` saat ini membantu warna tombol primer lintas halaman.

## Prioritas Cleanup Bertahap

### Prioritas 1 - Risiko Rendah

Pindahkan selector Manajemen Siswa yang jelas nyasar dari `payments.css` ke `student-management.css`:

- `.student-class-alumni-button`
- `.student-import-confirm`
- `.student-class-alumni-page-actions .button-primary`
- `.student-alumni-v7 .student-filter-panel .student-search-button`
- `.class-movement-page .class-movement-filter .student-search-button`

Validasi:

- Build hijau.
- MasterDataTest hijau.
- Smoke test desktop/mobile:
  - `/manajemen-siswa/alumni`
  - `/manajemen-siswa/data-siswa/import`
  - `/manajemen-siswa/data-siswa/jadikan-alumni-kelas`
  - `/manajemen-siswa/pindah-kelas`
  - `/manajemen-siswa/naik-kelas`
- Smoke test pembayaran:
  - halaman pembayaran SPP tetap normal.
  - tombol cari siswa dan ganti siswa tetap normal.

### Prioritas 2 - Risiko Sedang

Pindahkan selector Data Siswa dan Alumni dari `app.css` ke `student-management.css`:

- `.student-master-table`
- `.student-ghost-actions`
- `.student-import-page .student-import-preview-table-v3`
- `#student-data-filter`
- `.student-data-body`
- `.student-export-actions`
- `#student-alumni-page.student-alumni-page`
- `.student-alumni-body`

Validasi:

- Bandingkan desktop/mobile Data Siswa dan Alumni sebelum/sesudah.
- Pastikan tabel tetap compact, filter tidak melebar, action icon tetap sejajar, dan pagination tetap rapi.
- Pastikan import preview tetap memiliki scroll horizontal hanya di tabel.

### Prioritas 3 - Risiko Sedang-Tinggi

Rapikan selector generic legacy di `app.css` yang memakai pola:

- `.student-page:not(...)`
- `.student-page .student-action-bar`
- `.student-page .student-filter-panel`
- `.student-page .student-data-card`
- `.nova-student-*`
- token lama seperti `--nova-primary`, `--nova-orange`, dan `--nova-surface`

Validasi:

- Cek semua route Menu Manajemen Siswa P0-P3.
- Cek route pembayaran import SPP yang memakai markup `.student-page` untuk preview pembayaran.
- Jangan hapus pengecualian `payment-flat-page`, `report-flat-page`, dan `bill-page` sebelum audit visual menu terkait.

### Prioritas 4 - Standarisasi Minor

Rapikan detail standar setelah struktur selector aman:

- Naikkan sisa `font-size: 13px` pada pagination mobile legacy ke 14px jika tidak menyebabkan tombol pagination pecah.
- Kurangi radius legacy 12px, 14px, 16px, dan 24px pada komponen Manajemen Siswa yang sudah punya pengganti final.
- Kurangi `!important` yang tidak lagi dibutuhkan.
- Ganti nama class warna lama seperti `action-purple` dan `action-green` secara bertahap hanya jika markup Blade sudah siap dan test permission tetap aman.

Validasi:

- Cek mobile 360px, tablet 768px, desktop 1366px.
- Pastikan text tidak overlap, tombol tidak wrap buruk, dan tabel hanya scroll di wrapper tabel.

## Rekomendasi Urutan Commit

1. `refactor: move student management button css out of payments module`
2. `refactor: move student data and alumni css into student module`
3. `refactor: reduce legacy student page css overrides`
4. `style: align student management legacy css with app standard`

## Checklist QA Per Batch

- `git status --short`
- `npm run build`
- `php artisan test tests/Feature/MasterDataTest.php`
- `php artisan route:list --path=manajemen-siswa`
- Smoke test desktop/mobile:
  - `/manajemen-siswa`
  - `/manajemen-siswa/data-siswa`
  - `/manajemen-siswa/data-siswa/import`
  - `/manajemen-siswa/alumni`
  - `/manajemen-siswa/rapikan-identitas`
  - `/manajemen-siswa/kualitas-data`
  - `/manajemen-siswa/pindah-kelas`
  - `/manajemen-siswa/naik-kelas`
- Smoke test pembayaran minimal:
  - cari siswa pembayaran SPP.
  - preview import pembayaran SPP.
  - tombol primer pembayaran tetap hijau dan tidak berubah ukuran.

## Catatan Rilis

Cleanup ini adalah pekerjaan pasca rilis. Jangan digabung dengan hotfix pembayaran/tagihan atau fitur baru Manajemen Siswa. Jika ada regresi visual, rollback batch cleanup terakhir saja dan biarkan rilis P0-P3 tetap berjalan.
