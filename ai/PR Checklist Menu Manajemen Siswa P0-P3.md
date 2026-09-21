# PR Checklist Internal - Menu Manajemen Siswa P0-P3

## Ringkasan PR

PR ini merilis penyempurnaan Menu Manajemen Siswa dari P0 sampai P3. Fokus utama: alur kerja data siswa aktif/alumni, import/export, pindah/naik kelas, rapikan identitas lintas unit, audit log operasi berisiko, permission granular, serta Dashboard Kualitas Data dengan filter detail dan pagination.

Rilis mengacu pada:

- `ai/PRD Menu Manajemen Siswa.md`
- `ai/standar aplikasi.md`
- `ai/Release Note Menu Manajemen Siswa P0-P3.md`

## Commit Penting

- `8d6775e fix: prevent invalid legacy student bills`
  Perbaikan tagihan lama dan sinkron tagihan agar siswa tidak mendapat tagihan SPP sebelum bulan mulai tagihannya.
- `997716b feat: add payment import success modal`
  Modal sukses import pembayaran dengan desain sederhana dan operasional.
- `1eca2a7 feat: finalize student management p0 p1 p2`
  Finalisasi Menu Manajemen Siswa P0-P2: data siswa, import/export, alumni, rapikan identitas, audit log, permission granular, dashboard kualitas data ringan.
- `1d8311a feat: add student data quality detail filters`
  P3 Dashboard Kualitas Data: filter indikator, detail lengkap, pagination, dan permission action.
- `53f64d1 docs: add student management p0 p3 release note`
  Release note internal Menu Manajemen Siswa P0-P3.

## Checklist Reviewer

### Migration

- [ ] `2026_08_01_000000_create_audit_logs_table` sudah `Ran`.
- [ ] `2026_08_01_010000_add_student_granular_permissions_to_roles` sudah `Ran`.
- [ ] Migration tidak menghapus data siswa, tagihan, pembayaran, atau role existing.
- [ ] Role existing mendapat permission siswa granular sesuai kebutuhan.

### Permission

- [ ] `students.view` bisa melihat menu Manajemen Siswa, Data Siswa, Alumni, dan Kualitas Data.
- [ ] `students.create` hanya untuk Tambah Siswa dan store siswa.
- [ ] `students.update` hanya untuk Edit Siswa dan aktifkan kembali alumni/nonaktif.
- [ ] `students.import` hanya untuk Import, Preview Import, Konfirmasi Import, dan Template.
- [ ] `students.export` hanya untuk Export Data Siswa.
- [ ] `students.movement` hanya untuk Pindah Kelas dan Naik Kelas.
- [ ] `students.alumni` hanya untuk Jadikan Alumni per kelas.
- [ ] `students.identity_cleanup` hanya untuk Rapikan Identitas, Tinjau, Gabung, dan Pisah.
- [ ] Tombol UI tanpa izin tidak tampil atau tidak bisa dipakai.
- [ ] Middleware tetap menolak akses route tanpa izin.

### Route

- [ ] `/manajemen-siswa` redirect ke Data Siswa.
- [ ] `/manajemen-siswa/data-siswa` terbuka dan filter berfungsi.
- [ ] `/manajemen-siswa/alumni` terbuka dan hanya menampilkan nonaktif/alumni.
- [ ] `/manajemen-siswa/rapikan-identitas` terbuka sesuai permission.
- [ ] `/manajemen-siswa/kualitas-data` terbuka dengan permission `students.view`.
- [ ] `/manajemen-siswa/pindah-kelas` terbuka sesuai permission.
- [ ] `/manajemen-siswa/naik-kelas` terbuka sesuai permission.
- [ ] Route `master-data/students/*` untuk store, update, export, template, preview import, dan import tetap aktif.

### Import / Export

- [ ] Import siswa selalu melalui preview sebelum konfirmasi.
- [ ] Preview menampilkan ringkasan valid, gagal, duplikat/update jika ada.
- [ ] Import menyebut jumlah data berhasil, kelas otomatis, baris dilewati, dan sinkronisasi tagihan jika relevan.
- [ ] Template import memuat kolom format terbaru.
- [ ] Export Data Siswa mengikuti filter aktif: unit, kelas, tahun, status, search, sort/direction.
- [ ] Nama file export jelas sesuai konteks filter.
- [ ] Export tidak bisa diakses tanpa `students.export`.

### Alumni

- [ ] Data Alumni menampilkan siswa `is_active = false`.
- [ ] Filter unit, kelas, tahun, search, dan pagination berjalan.
- [ ] Search mendukung nama, NIS, NISN, unit, kelas, tahun, dan alasan nonaktif.
- [ ] Edit alumni dapat mengaktifkan kembali siswa dengan validasi kelas/tahun/status.
- [ ] Jadikan Alumni per kelas menampilkan konfirmasi unit, kelas, tahun, jumlah siswa, tanggal keluar, dan alasan.
- [ ] Jadikan Alumni tidak menghapus transaksi atau tagihan.

### Rapikan Identitas

- [ ] Daftar kandidat duplikat menampilkan alasan dan confidence.
- [ ] Detail tinjau menampilkan NIS, NISN, tanggal lahir, orang tua, unit, kelas, dan tahun.
- [ ] Gabung identitas hanya mengubah `identity_student_id`.
- [ ] Pisah identitas mengembalikan `identity_student_id` ke null.
- [ ] Transaksi/tagihan tetap berada pada record siswa asal.
- [ ] Audit log tercatat untuk gabung dan pisah.

### Pindah / Naik Kelas

- [ ] Filter sumber unit, kelas, tahun, dan search berjalan.
- [ ] Siswa yang diproses harus aktif dan sesuai tahun sumber.
- [ ] Target kelas/tahun divalidasi.
- [ ] Sistem menolak target yang sama atau duplikat nama pada kelas tujuan.
- [ ] Sinkronisasi tagihan tetap aman setelah pindah/naik kelas.
- [ ] Audit log tercatat untuk transfer dan promosi.

### Dashboard Kualitas Data

- [ ] Ringkasan indikator tampil tanpa hero/landing page.
- [ ] Indikator tersedia:
  - kandidat duplikat identitas
  - siswa aktif tanpa NISN
  - siswa aktif tanpa tanggal masuk
  - mulai tagihan perlu dicek
  - nonaktif/alumni belum lengkap
- [ ] Kartu indikator mengarah ke detail filter indikator.
- [ ] Dropdown indikator dan `per_page` berjalan.
- [ ] Detail indikator tampil lengkap dengan pagination.
- [ ] Tombol edit hanya muncul jika user punya `students.update`.
- [ ] Tombol tinjau duplikat hanya muncul jika user punya `students.identity_cleanup`.
- [ ] Dashboard tidak mengubah pembayaran atau tagihan.

### Test / Build

- [ ] `php artisan test tests/Feature/MasterDataTest.php` hijau.
- [ ] `npm run build` hijau.
- [ ] `git status --short` bersih sebelum merge.
- [ ] Jika ada perubahan setelah PR checklist ini, test/build dijalankan ulang.

## Instruksi QA Manual Desktop

Gunakan browser desktop lebar sekitar 1366px atau lebih.

1. Buka `/manajemen-siswa/data-siswa`.
2. Cek filter unit, kelas, status, search, sort, pagination, tombol Tambah, Import, Export, Template, dan Jadikan Alumni sesuai permission.
3. Buka form tambah dan edit siswa, pastikan field rapi, validasi terbaca, tombol Simpan/Batal jelas.
4. Buka halaman import siswa, upload file contoh kecil, cek preview dan konfirmasi.
5. Buka `/manajemen-siswa/alumni`, cek filter, search, pagination, dan tombol edit/aktifkan kembali.
6. Buka `/manajemen-siswa/rapikan-identitas`, cek daftar kandidat dan halaman tinjau.
7. Buka `/manajemen-siswa/pindah-kelas` dan `/manajemen-siswa/naik-kelas`, cek filter, tabel, checkbox, target, dan tombol aksi.
8. Buka `/manajemen-siswa/kualitas-data`, cek kartu indikator, filter indikator, pagination, tombol edit/tinjau sesuai permission.
9. Pastikan tidak ada overlap teks, tombol, badge, tabel, atau footer.

## Instruksi QA Manual Mobile

Gunakan viewport sekitar 390px sampai 430px.

1. Buka sidebar, pastikan Manajemen Siswa dan submenu bisa dibuka/ditutup.
2. Cek Data Siswa: filter menjadi satu kolom atau grid mobile yang rapi, tombol tidak keluar layar.
3. Cek tabel Data Siswa: hanya area tabel yang horizontal scroll, halaman utama tidak melebar.
4. Cek form tambah/edit: field 100% lebar, tombol mudah ditekan, error dekat field.
5. Cek Alumni: filter/search tidak overlap, tabel scroll horizontal, pagination terbaca.
6. Cek Rapikan Identitas dan Tinjau: tabel/detail tidak membuat halaman melebar.
7. Cek Pindah/Naik Kelas: checkbox, target, dan tombol aksi tetap bisa dipakai.
8. Cek Dashboard Kualitas Data: kartu turun satu kolom, filter indikator full width, pagination wrap rapi.
9. Cek modal/alert hasil aksi jika ada: tidak terpotong dan tombol bisa ditekan.

## Catatan Merge

- Rilis ini sudah memiliki release note internal.
- Jika reviewer menemukan masalah visual kecil, sebaiknya dibuat commit follow-up terpisah.
- Jika masalah menyentuh tagihan/pembayaran, pisahkan dari PR Manajemen Siswa kecuali regresi langsung dari data siswa.
