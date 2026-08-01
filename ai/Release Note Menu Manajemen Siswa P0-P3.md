# Release Note Internal - Menu Manajemen Siswa P0-P3

Tanggal audit final: 2026-08-01

## Ringkasan

Menu Manajemen Siswa sudah diselesaikan sampai P3 berdasarkan PRD final dan standar aplikasi. Fokus rilis ini adalah membuat alur data siswa lebih operasional, aman secara permission, terdokumentasi melalui audit log, dan mudah diaudit melalui dashboard kualitas data.

Commit acuan:

- `1eca2a7 feat: finalize student management p0 p1 p2`
- `1d8311a feat: add student data quality detail filters`

Commit pendukung yang terkait konteks rilis:

- `8d6775e fix: prevent invalid legacy student bills`
- `997716b feat: add payment import success modal`

## Perubahan Utama

### P0

- Data Siswa aktif diperkuat dengan filter unit, kelas, tahun pelajaran, status, search, pagination, sort, dan edit.
- Tambah/Edit siswa menjaga validasi akademik dan sinkronisasi tagihan berjalan.
- Import siswa memakai preview sebelum simpan, mendukung template terbaru, status create/update, dan ringkasan hasil.
- Pindah Kelas dan Naik Kelas memakai halaman operasional dengan filter, pilihan siswa, validasi target, dan sinkronisasi tagihan aman.
- Jadikan Alumni per kelas memakai halaman konfirmasi dengan konteks unit, kelas, tahun, jumlah siswa, tanggal keluar, dan alasan.

### P1

- Rapikan Identitas siswa lintas unit tersedia untuk kandidat duplikat, tinjau detail, gabung, dan pisahkan identitas.
- Gabung/pisah identitas hanya mengubah `identity_student_id`; transaksi dan tagihan tetap berada pada `student_id` asal.
- Data Alumni menampilkan siswa nonaktif/alumni dengan filter, search, pagination, dan aksi edit/aktifkan kembali.
- Export Data Siswa mengikuti filter aktif dan nama file dibuat sesuai konteks.
- Template import siswa disesuaikan dengan format import terbaru.
- Audit log ditambahkan untuk operasi berisiko: create, update, reactivate, import, export, class alumni, transfer class, promote class, identity merge, identity split.

### P2

- Permission granular per aksi ditambahkan:
  - `students.view`
  - `students.create`
  - `students.update`
  - `students.import`
  - `students.export`
  - `students.movement`
  - `students.alumni`
  - `students.identity_cleanup`
- Sidebar, tombol, dan route/controller mengikuti permission tersebut.
- Dashboard Kualitas Data ringan ditambahkan untuk memantau:
  - kandidat duplikat identitas
  - siswa aktif tanpa NISN
  - siswa aktif tanpa tanggal masuk
  - mulai tagihan perlu dicek
  - siswa nonaktif/alumni belum lengkap

### P3

- Dashboard Kualitas Data memiliki filter indikator.
- Setiap kartu indikator mengarah ke detail indikator terkait.
- Detail indikator tampil lengkap dengan pagination.
- Akses dashboard tetap minimal `students.view`.
- Tombol edit hanya tampil jika user memiliki `students.update`.
- Tombol tinjau duplikat hanya tampil jika user memiliki `students.identity_cleanup`.

## Route Dan Menu Terdampak

Menu utama:

- `GET /manajemen-siswa`
- `GET /manajemen-siswa/data-siswa`
- `GET /manajemen-siswa/alumni`
- `GET /manajemen-siswa/rapikan-identitas`
- `GET /manajemen-siswa/kualitas-data`
- `GET /manajemen-siswa/pindah-kelas`
- `GET /manajemen-siswa/naik-kelas`

Route aksi siswa:

- `GET /manajemen-siswa/data-siswa/create`
- `GET /manajemen-siswa/data-siswa/import`
- `GET /manajemen-siswa/data-siswa/{student}/edit`
- `GET /manajemen-siswa/data-siswa/jadikan-alumni-kelas`
- `POST /manajemen-siswa/data-siswa/jadikan-alumni-kelas`
- `POST /manajemen-siswa/pindah-kelas`
- `POST /manajemen-siswa/naik-kelas`
- `GET /manajemen-siswa/rapikan-identitas/tinjau/{candidateKey}`
- `POST /manajemen-siswa/rapikan-identitas/gabungkan`
- `POST /manajemen-siswa/rapikan-identitas/pisahkan`

Route pendukung import/export:

- `POST /master-data/students`
- `PUT /master-data/students/{student}`
- `GET /master-data/students/export`
- `GET /master-data/students/template`
- `POST /master-data/students/import/preview`
- `POST /master-data/students/import`

## Migration Wajib

Pastikan migration berikut sudah berjalan:

- `2026_08_01_000000_create_audit_logs_table`
- `2026_08_01_010000_add_student_granular_permissions_to_roles`

Status audit terakhir:

- `2026_08_01_000000_create_audit_logs_table` - Ran
- `2026_08_01_010000_add_student_granular_permissions_to_roles` - Ran

## Permission

Permission granular final:

| Permission | Fungsi |
|---|---|
| `students.view` | Melihat Data Siswa, Alumni, Kualitas Data, dan parent menu Manajemen Siswa. |
| `students.create` | Membuka form tambah dan menyimpan siswa baru. |
| `students.update` | Membuka edit siswa, memperbarui siswa, dan mengaktifkan kembali alumni/nonaktif. |
| `students.import` | Membuka import, download template, preview import, dan konfirmasi import. |
| `students.export` | Export Data Siswa sesuai filter. |
| `students.movement` | Pindah Kelas dan Naik Kelas. |
| `students.alumni` | Jadikan Alumni per kelas. |
| `students.identity_cleanup` | Rapikan Identitas, tinjau kandidat, gabung, dan pisahkan identitas. |

Catatan:

- UI menyembunyikan tombol yang tidak sesuai izin.
- Middleware tetap menjadi validasi server-side.
- Super Admin tetap mendapat semua akses.

## Hasil Verifikasi Terakhir

Final release audit:

- `git status --short`: bersih.
- Commit terakhir: `1d8311a feat: add student data quality detail filters`.
- `php artisan test tests/Feature/MasterDataTest.php`: hijau.
- Hasil test: 68 tests, 971 assertions.
- `npm run build`: hijau.

## Risiko Rilis

- Audit browser/screenshot desktop-mobile belum dijalankan pada audit final.
- CSS Manajemen Siswa sudah scoped, tetapi masih ada sebagian CSS legacy/global aplikasi yang perlu dibersihkan bertahap pada backlog terpisah.
- Dashboard Kualitas Data saat ini memakai query koleksi read-only; aman untuk data sedang, tetapi bisa perlu optimasi query jika jumlah siswa sangat besar.
- Operasi tagihan/pembayaran tidak menjadi ruang lingkup utama rilis ini kecuali perbaikan bug tagihan lama yang sudah dipisah commit.

## Catatan Rollback

Rollback paling aman dilakukan per commit:

1. Jika hanya detail filter dashboard bermasalah, rollback commit:
   - `1d8311a feat: add student data quality detail filters`
2. Jika fitur P0-P2 Manajemen Siswa perlu dibatalkan, rollback commit:
   - `1eca2a7 feat: finalize student management p0 p1 p2`
3. Jika rollback menyentuh migration audit log atau permission granular, siapkan rencana database manual karena migration sudah berjalan dan dapat memengaruhi role/permission aktif.

Sebelum rollback produksi:

- Backup database.
- Catat role dan permission aktif.
- Pastikan tidak ada operasi import, pindah kelas, naik kelas, alumni, atau rapikan identitas yang sedang berjalan.
- Jalankan `php artisan test tests/Feature/MasterDataTest.php` dan `npm run build` setelah rollback.
