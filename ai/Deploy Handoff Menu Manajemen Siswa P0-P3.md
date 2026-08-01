# Deploy Handoff - Menu Manajemen Siswa P0-P3

Dokumen ini dipakai sebagai panduan singkat sebelum merge/deploy, saat deploy, dan setelah deploy Menu Manajemen Siswa P0-P3.

Acuan:

- `ai/Release Note Menu Manajemen Siswa P0-P3.md`
- `ai/PR Checklist Menu Manajemen Siswa P0-P3.md`

## Sebelum Deploy

- [ ] Pastikan branch sudah berisi commit rilis Manajemen Siswa P0-P3.
- [ ] Pastikan worktree bersih.
- [ ] Pastikan `.env` target sudah menunjuk database yang benar.
- [ ] Backup database produksi/staging sebelum menjalankan migration.
- [ ] Catat role/permission penting sebelum deploy, terutama role admin/operator yang memakai Menu Manajemen Siswa.
- [ ] Pastikan tidak ada proses import siswa, pindah kelas, naik kelas, alumni, atau rapikan identitas yang sedang berjalan.
- [ ] Jalankan test dan build di environment staging/CI jika tersedia.

Command:

```bash
git status
php artisan test tests/Feature/MasterDataTest.php
npm run build
```

## Saat Deploy

- [ ] Pull/checkout kode rilis.
- [ ] Install dependency jika ada perubahan dependency dari pipeline deploy.
- [ ] Jalankan migration dengan mode force.
- [ ] Build asset frontend.
- [ ] Clear/cache config/view sesuai standar deploy aplikasi.
- [ ] Pastikan permission file dan storage tetap benar.

Command utama:

```bash
git status
php artisan migrate --force
php artisan migrate:status
npm run build
```

Migration yang wajib terlihat `Ran`:

- `2026_08_01_000000_create_audit_logs_table`
- `2026_08_01_010000_add_student_granular_permissions_to_roles`

## Setelah Deploy

- [ ] Pastikan aplikasi bisa login.
- [ ] Pastikan sidebar Manajemen Siswa tampil untuk user dengan `students.view`.
- [ ] Pastikan user tanpa permission aksi tidak melihat tombol aksi terkait.
- [ ] Pastikan audit log table tersedia.
- [ ] Pastikan role granular siswa tersedia.
- [ ] Jalankan smoke test route utama.
- [ ] Jalankan MasterDataTest jika environment deploy mengizinkan test terhadap database test.

Command:

```bash
php artisan migrate:status
php artisan test tests/Feature/MasterDataTest.php
```

## Smoke Test Cepat

Route wajib:

- [ ] `/manajemen-siswa`
- [ ] `/manajemen-siswa/data-siswa`
- [ ] `/manajemen-siswa/alumni`
- [ ] `/manajemen-siswa/rapikan-identitas`
- [ ] `/manajemen-siswa/kualitas-data`
- [ ] `/manajemen-siswa/pindah-kelas`
- [ ] `/manajemen-siswa/naik-kelas`

Checklist smoke:

- [ ] `/manajemen-siswa` redirect ke Data Siswa.
- [ ] Data Siswa menampilkan filter, search, pagination, dan tombol sesuai permission.
- [ ] Alumni menampilkan siswa nonaktif/alumni dan filter/search berjalan.
- [ ] Rapikan Identitas menampilkan kandidat atau empty state tanpa error.
- [ ] Kualitas Data menampilkan kartu indikator, filter indikator, dan pagination detail.
- [ ] Pindah Kelas dan Naik Kelas terbuka, filter tampil, dan tidak error.
- [ ] Import siswa tetap melalui preview.
- [ ] Export siswa mengikuti filter aktif.
- [ ] Tombol edit siswa hanya tampil untuk `students.update`.

## QA Desktop

- [ ] Gunakan lebar browser sekitar 1366px atau lebih.
- [ ] Cek tidak ada overlap pada filter, tombol, tabel, badge, pagination, dan footer.
- [ ] Cek tabel yang lebar tetap rapi dan dapat discan.
- [ ] Cek modal/alert hasil aksi tidak menutup konten penting.
- [ ] Cek Dashboard Kualitas Data tidak terasa seperti landing page/hero.

## QA Mobile

- [ ] Gunakan viewport sekitar 390px sampai 430px.
- [ ] Sidebar bisa dibuka/ditutup dan submenu Manajemen Siswa tampil rapi.
- [ ] Filter turun rapi dan tombol tidak keluar layar.
- [ ] Hanya tabel yang horizontal scroll; halaman utama tidak melebar.
- [ ] Dashboard Kualitas Data turun satu kolom, filter full width, pagination wrap rapi.
- [ ] Form tambah/edit tetap bisa dipakai dan error dekat field.

## Rollback Cepat

Rollback dilakukan per commit sesuai masalah:

1. Jika hanya detail filter Dashboard Kualitas Data bermasalah:

```bash
git revert 1d8311a
```

2. Jika finalisasi P0-P2 Manajemen Siswa perlu dibatalkan:

```bash
git revert 1eca2a7
```

3. Jika perbaikan tagihan lama perlu dibatalkan:

```bash
git revert 8d6775e
```

Catatan penting:

- Jangan rollback migration produksi tanpa rencana database.
- Jika rollback menyentuh `audit_logs` atau permission granular, backup database dulu.
- Setelah rollback, jalankan migration status, test relevan, dan build ulang.

Command setelah rollback:

```bash
php artisan migrate:status
php artisan test tests/Feature/MasterDataTest.php
npm run build
```

## Kontak Dan Catatan Operasional

- Jika user melaporkan tagihan lama muncul lagi setelah edit/import siswa, cek `entry_date`, `billing_start_date`, dan hasil sinkron tagihan.
- Jika tombol tidak muncul, cek permission role user terlebih dahulu.
- Jika dashboard kualitas data lambat pada data besar, jadwalkan optimasi query sebagai backlog, bukan hotfix rilis kecuali menjadi blocker.
