# PRD Multi Login Petugas, Bendahara Unit, dan Wali Santri

## Tujuan

Multi login dibuat agar setiap pengguna masuk ke aplikasi yang sama, tetapi hanya melihat menu dan data sesuai tugasnya.

Fokus role:

1. **Super Admin** bisa mengakses semua menu dan semua data.
2. **Petugas** hanya menerima transaksi cash di kantor.
3. **Bendahara Unit** hanya melihat data yang sudah bayar dan belum bayar.
4. **Wali Santri** hanya melihat tagihan anaknya dan membayar dengan transfer. Jika ingin cash, wali santri harus datang ke kantor.

Sistem tetap memakai satu halaman login. Perbedaan akses ditentukan oleh role, permission, dan relasi data pengguna.

## Kondisi Saat Ini

Aplikasi sudah memiliki dasar berikut:

- Login memakai `username` dan `password`.
- User memiliki field `role`.
- Role sudah punya `permissions`.
- Permission utama yang tersedia: Dashboard, Manajemen Siswa, Pembayaran, Tagihan, Laporan, Data Master, Pengaturan Akun.
- Role bawaan saat ini: `admin`, `kasir`, `bendahara`, dan `orang_tua`.
- Pembayaran sudah mendukung metode `Cash` dan `Transfer`.
- Tabel pembayaran sudah memiliki `transfer_proof_path`.

Catatan penamaan:

- Role `admin` sebaiknya ditampilkan sebagai **Super Admin**.
- Role `kasir` secara fungsi bisa diarahkan menjadi **Petugas**.
- Role `orang_tua` sebaiknya di tampilan disebut **Wali Santri** agar sesuai istilah aplikasi.

## Prinsip Akses

Super Admin tetap menjadi role penuh, bisa mengakses semua menu, dan menjadi satu-satunya role yang boleh memverifikasi pembayaran transfer.

Petugas, bendahara unit, dan wali santri tidak boleh mendapat akses global tanpa pembatas data.

Pembatas data:

- Petugas dapat dibatasi ke unit tertentu jika dibutuhkan.
- Bendahara unit wajib dibatasi ke unit pendidikan yang ditugaskan.
- Wali santri wajib dibatasi hanya ke siswa yang terhubung dengan akunnya.

## Role dan Hak Akses

### Super Admin

Super Admin mengelola seluruh aplikasi.

Akses:

- Dashboard semua unit
- Manajemen siswa
- Pembayaran
- Tagihan
- Laporan
- Data master
- Pengaturan
- Data user dan role
- Verifikasi pembayaran transfer dari wali santri

### Petugas

Petugas fokus menerima pembayaran cash di kantor.

Akses utama:

- Pembayaran
- Cari siswa
- Lihat tagihan siswa sebelum transaksi
- Input pembayaran cash
- Cetak atau download kwitansi
- Melihat riwayat transaksi yang dia input sendiri

Batasan:

- Tidak mengubah data master.
- Tidak mengubah data siswa.
- Tidak bisa input pembayaran transfer.
- Tidak bisa upload bukti transfer.
- Tidak bisa memverifikasi transfer.
- Tidak melihat laporan pimpinan seluruh unit kecuali diberikan permission khusus.
- Jika petugas unit, hanya bisa menerima transaksi siswa di unit yang ditugaskan.
- Form pembayaran petugas hanya menampilkan metode `Cash`.

Menu petugas:

1. Pembayaran
2. Tagihan Siswa
3. Riwayat Transaksi Saya
4. Pengaturan Akun

### Bendahara Unit

Bendahara unit fokus melihat status pembayaran.

Akses utama:

- Dashboard unit
- Tagihan unit
- SPP Perbulan unit
- SPP Belum Bayar unit
- Data siswa yang sudah bayar
- Data siswa yang belum bayar
- Melihat pembayaran transfer yang masih menunggu verifikasi sebagai status belum terverifikasi

Batasan:

- Tidak mengubah data master kecuali diberi permission khusus.
- Tidak menghapus transaksi.
- Tidak bisa input transaksi.
- Tidak bisa memverifikasi transfer.
- Tidak bisa menerima atau menolak bukti transfer.
- Tidak bisa export jika kebijakan sekolah ingin data tetap hanya dibaca di aplikasi.
- Tidak melihat unit lain.
- Tidak membuat user baru.

Menu bendahara unit:

1. Dashboard
2. Tagihan
3. Data Sudah Bayar
4. Data Belum Bayar
5. Pengaturan Akun

### Wali Santri

Wali santri fokus melihat tagihan anak dan melakukan pembayaran transfer.

Akses utama:

- Login menggunakan Unit Pendidikan, Username, dan Password.
- Username wali santri diisi dengan NIS sesuai unit yang dipilih.
- Melihat daftar anak/santri yang terhubung
- Melihat tagihan aktif
- Melihat rincian tagihan SPP dan pembayaran lain
- Upload bukti transfer
- Melihat status pembayaran: Menunggu Verifikasi, Diterima, Ditolak
- Download atau melihat kwitansi setelah pembayaran diterima

Batasan:

- Tidak bisa melihat data siswa lain.
- Tidak bisa melihat laporan internal.
- Tidak bisa input pembayaran cash.
- Tidak ada tombol bayar cash di portal wali santri.
- Tidak bisa mengubah nominal tagihan.
- Tidak bisa menghapus pembayaran.
- Jika wali santri ingin membayar cash, pembayaran wajib dilakukan ke kantor melalui Petugas.

Menu wali santri:

1. Tagihan Anak
2. Upload Pembayaran
3. Riwayat Pembayaran
4. Profil Wali

## Permission Baru yang Disarankan

Permission lama masih bisa dipakai, tetapi agar akses lebih rapi perlu permission yang lebih detail.

Permission modul:

- `dashboard.view`
- `students.view`
- `payments.cash.create`
- `payments.transfer.submit_guardian`
- `payments.verify_transfer`
- `payments.view_own`
- `payments.view_unit`
- `bills.view`
- `bills.view_unit`
- `bills.view_guardian`
- `reports.view`
- `reports.view_unit`
- `reports.export`
- `master.manage`
- `users.manage`
- `settings.view`

Mapping awal:

| Role | Permission |
| --- | --- |
| Super Admin | Semua permission |
| Petugas | `payments.cash.create`, `payments.view_own`, `bills.view`, `settings.view` |
| Bendahara Unit | `dashboard.view`, `payments.view_unit`, `bills.view_unit`, `reports.view_unit`, `settings.view` |
| Wali Santri | `payments.transfer.submit_guardian`, `bills.view_guardian`, `settings.view` |

Aturan khusus:

- `payments.verify_transfer` hanya diberikan ke Super Admin.
- Petugas dan Bendahara Unit tidak boleh memiliki `payments.verify_transfer`.
- `payments.cash.create` hanya untuk Super Admin dan Petugas.
- `payments.transfer.submit_guardian` hanya untuk Wali Santri.
- Petugas tidak boleh memiliki `payments.transfer.submit_guardian`.
- Bendahara Unit hanya memakai akses baca dari `payments.view_unit`, bukan akses input atau verifikasi.

## Data yang Perlu Ditambah

### 1. Relasi User ke Unit

Dipakai untuk petugas unit dan bendahara unit.

Tabel: `education_unit_user`

Kolom:

- `id`
- `user_id`
- `education_unit_id`
- `created_at`
- `updated_at`

Aturan:

- Satu user bisa punya satu atau beberapa unit.
- Query tagihan, transaksi, dan laporan wajib difilter berdasarkan unit user.

### 2. Relasi User Wali ke Siswa

Dipakai untuk login wali santri.

Tabel: `guardian_student`

Kolom:

- `id`
- `user_id`
- `student_id`
- `relationship`
- `is_primary`
- `verified_at`
- `created_at`
- `updated_at`

Aturan:

- Satu wali bisa punya beberapa siswa.
- Satu siswa bisa punya beberapa wali jika dibutuhkan.
- Wali hanya bisa melihat siswa yang ada di tabel ini.

### 3. Status Verifikasi Pembayaran Transfer Wali

Pembayaran dari wali santri sebaiknya masuk sebagai pending dulu.

Status yang dipakai:

- `Pending` atau `Menunggu Verifikasi`
- `Diterima`
- `Ditolak`

Jika ditolak, perlu kolom alasan:

- `rejected_reason`
- `verified_by`
- `verified_at`

Kolom ini bisa ditambahkan ke `spp_payments` dan `other_payments`, atau dibuat tabel audit terpisah jika ingin lebih rapi.

## Alur Login

1. User membuka `/login`.
2. User masuk dengan username dan password.
3. Sistem membaca role user.
4. Sistem redirect sesuai role:
   - Super Admin ke Dashboard.
   - Petugas ke Pembayaran.
   - Bendahara Unit ke Dashboard Unit.
   - Wali Santri ke Tagihan Anak.
5. Sidebar hanya menampilkan menu sesuai permission.

## Login Wali Santri

Halaman login wali santri memakai field:

1. **Unit Pendidikan**
2. **Username**
3. **Password**

Aturan field:

- Field **Username** diisi dengan NIS.
- Placeholder Username: `Masukkan NIS`.
- Teks bantuan Username: `Gunakan NIS sesuai unit yang dipilih.`
- Unit Pendidikan ditampilkan sebagai pilihan sejajar, bukan bertingkat.

Pilihan Unit Pendidikan:

- PAUD
- RA
- MI
- MTs
- MA
- PONPES

Catatan unit:

- PONPES tidak diletakkan di bawah MTs atau MA.
- PONPES menjadi pilihan unit sendiri karena santri PONPES bisa juga sekolah di MTs atau MA.
- Wali boleh login memakai NIS dari unit mana pun yang terhubung dengan identitas siswa.
- Setelah login berhasil, sistem menampilkan semua tagihan siswa yang identitasnya terhubung, termasuk tagihan PONPES jika siswa juga terdaftar di PONPES.

Contoh:

- Unit Pendidikan: `MTs`
- Username: `250111`
- Password: `******`

Jika NIS `250111` di unit MTs terhubung dengan identitas siswa yang juga punya data PONPES, halaman wali santri menampilkan tagihan MTs dan PONPES.

## Alur Petugas Menerima Transaksi

1. Petugas masuk ke menu Pembayaran.
2. Cari siswa berdasarkan nama, NIS, atau NISN.
3. Pilih tagihan.
4. Sistem menetapkan metode bayar sebagai `Cash`.
5. Simpan transaksi.
6. Sistem mencatat `operator_name` dari user login.
7. Kwitansi bisa langsung dicetak.

Catatan:

- Transaksi dari petugas loket bisa langsung `Diterima`.
- Petugas tidak melihat pilihan metode `Transfer`.
- Petugas tidak bisa mengunggah bukti transfer.
- Transfer dari wali santri wajib masuk `Pending` dan hanya bisa diverifikasi oleh Super Admin.
- Petugas tidak mendapat akses verifikasi transfer.

## Alur Bendahara Unit Mengecek Pembayaran

1. Bendahara unit masuk.
2. Sistem membaca unit yang ditugaskan.
3. Semua data pembayaran dan tagihan otomatis terfilter unit.
4. Bendahara bisa melihat:
   - Siswa yang sudah bayar
   - Siswa yang belum bayar
   - SPP perbulan
   - Tunggakan SPP
   - Transfer pending sebagai pembayaran belum terverifikasi
5. Bendahara tidak bisa input transaksi, export, menerima transfer, atau menolak transfer.

## Alur Super Admin Verifikasi Transfer

1. Super Admin masuk menu Verifikasi Transfer.
2. Sistem menampilkan semua pembayaran status Pending.
3. Super Admin membuka bukti transfer.
4. Super Admin memilih:
   - Terima
   - Tolak
5. Jika diterima:
   - Status menjadi `Diterima`.
   - Tagihan teralokasi sebagai terbayar.
   - Wali santri bisa melihat kwitansi.
6. Jika ditolak:
   - Status menjadi `Ditolak`.
   - Tagihan tetap belum lunas.
   - Wali santri melihat alasan penolakan.

Catatan:

- Bendahara Unit hanya boleh melihat daftar transfer pending sesuai unitnya.
- Bendahara Unit tidak boleh melihat tombol Terima atau Tolak.
- Petugas tidak boleh mengakses halaman verifikasi transfer.

## Alur Wali Santri Membayar Transfer

1. Wali santri login.
2. Masuk ke Tagihan Anak.
3. Pilih siswa jika punya lebih dari satu anak.
4. Lihat daftar tagihan.
5. Pilih tagihan yang ingin dibayar.
6. Sistem menampilkan nominal dan rekening tujuan.
7. Wali upload bukti transfer.
8. Pembayaran tersimpan sebagai `Menunggu Verifikasi`.
9. Setelah Super Admin menerima, wali bisa melihat status `Diterima` dan mengunduh kwitansi.
10. Jika wali santri ingin membayar cash, wali santri datang ke kantor dan pembayaran dicatat oleh Petugas.

## Tampilan yang Dibutuhkan

### Petugas

Halaman Pembayaran tetap seperti saat ini, tetapi:

- Sidebar lebih ringkas.
- Riwayat yang ditampilkan default transaksi miliknya.
- Jika petugas dibatasi unit, pencarian siswa hanya unit tersebut.
- Pilihan metode bayar dikunci `Cash`.
- Tidak ada input upload bukti transfer.

### Bendahara Unit

Dashboard unit:

- Total tagihan unit
- Total terbayar unit
- Total belum bayar unit
- Shortcut ke SPP Perbulan dan SPP Belum Bayar

Halaman monitoring:

- Data Sudah Bayar.
- Data Belum Bayar.
- Transfer Pending ditampilkan sebagai belum terverifikasi, hanya baca.
- Semua filter unit otomatis terkunci ke unit bendahara.
- Tidak ada tombol input transaksi, verifikasi, hapus, atau export.

### Wali Santri

Tampilan harus lebih sederhana daripada admin.

Halaman Login Wali Santri:

- Field Unit Pendidikan.
- Field Username dengan placeholder `Masukkan NIS`.
- Field Password.
- Label field tetap **Username**, bukan **NIS**, agar masih fleksibel jika format login berubah.
- Teks bantuan: `Gunakan NIS sesuai unit yang dipilih.`
- Pilihan unit: PAUD, RA, MI, MTs, MA, PONPES.

Halaman Tagihan Anak:

- Pilihan anak
- Total tagihan
- Daftar tagihan
- Tombol Bayar Transfer
- Tidak ada tombol Bayar Cash
- Status pembayaran terakhir

Halaman Upload Pembayaran:

- Ringkasan tagihan
- Nominal
- Info rekening
- Upload bukti
- Tombol Kirim Pembayaran

## Tahapan Implementasi

### Tahap 1: Rapikan Role dan Permission

1. Putuskan role key:
   - Opsi A: rename `kasir` menjadi `petugas`.
   - Opsi B: tetap pakai key `kasir`, tetapi label tampilannya `Petugas`.
2. Ubah label `orang_tua` menjadi `Wali Santri`.
3. Tambahkan permission detail.
4. Sesuaikan Data Role dan Data User.
5. Update test role.

Rekomendasi: pakai Opsi B untuk risiko paling kecil. Key database tetap `kasir`, label UI menjadi `Petugas`.

### Tahap 2: Tambah Relasi Unit dan Wali Siswa

1. Buat migration `education_unit_user`.
2. Buat migration `guardian_student`.
3. Tambah relasi di model `User`, `EducationUnit`, dan `Student`.
4. Tambah field unit pada form Data User untuk role Petugas dan Bendahara.
5. Tambah field siswa pada form Data User untuk role Wali Santri.

### Tahap 3: Middleware Scope Data

1. Buat helper scope akses:
   - `accessibleUnitIds()`
   - `accessibleStudentIds()`
2. Terapkan ke:
   - Pembayaran
   - Tagihan
   - Laporan
   - Pencarian siswa
3. Pastikan user bendahara unit tidak bisa akses data unit lain lewat URL manual.
4. Pastikan wali santri tidak bisa akses tagihan siswa lain lewat URL manual.

### Tahap 4: Dashboard dan Redirect Role

1. Setelah login, redirect berdasarkan role.
2. Super Admin ke dashboard admin.
3. Petugas ke Pembayaran.
4. Bendahara Unit ke Dashboard Unit.
5. Wali Santri ke Tagihan Anak.
6. Sidebar mengikuti permission dan role.

### Tahap 5: Verifikasi Transfer

1. Buat menu Verifikasi Transfer khusus Super Admin.
2. Tampilkan semua transaksi pending untuk Super Admin.
3. Buat aksi Terima dan Tolak.
4. Simpan `verified_by`, `verified_at`, dan `rejected_reason`.
5. Update status tagihan setelah verifikasi diterima.
6. Untuk Bendahara Unit, tampilkan daftar transfer pending unitnya dalam mode baca saja.
7. Petugas tidak diberi menu atau route verifikasi transfer.

### Tahap 6: Portal Wali Santri

1. Buat halaman Tagihan Anak.
2. Buat halaman detail tagihan.
3. Buat form upload bukti transfer.
4. Buat halaman riwayat pembayaran wali.
5. Kwitansi hanya muncul jika pembayaran sudah diterima.

### Tahap 7: Test dan Keamanan

Test wajib:

- Petugas tidak bisa akses Data Master.
- Petugas tidak bisa akses Verifikasi Transfer.
- Petugas tidak melihat opsi Transfer di form pembayaran.
- Petugas hanya bisa menyimpan transaksi Cash.
- Petugas hanya bisa mencari siswa sesuai unit jika unit dibatasi.
- Bendahara unit hanya melihat data sudah bayar dan belum bayar unitnya.
- Bendahara unit tidak bisa input transaksi.
- Bendahara unit tidak melihat tombol export jika aksesnya hanya baca.
- Bendahara unit tidak bisa menerima atau menolak transfer.
- Bendahara unit tidak bisa verifikasi transaksi unit sendiri maupun unit lain.
- Super Admin bisa menerima dan menolak transfer semua unit.
- Wali santri hanya melihat anak yang terhubung.
- Wali santri tidak bisa akses route admin.
- Wali santri tidak melihat tombol Bayar Cash.
- Wali santri hanya bisa mengirim pembayaran Transfer.
- Wali santri upload transfer masuk status Pending.
- Setelah transfer diterima, tagihan berkurang dan kwitansi tersedia.

## Prioritas Pengerjaan

Urutan paling aman:

1. Role label dan permission.
2. Relasi user ke unit.
3. Scope data untuk bendahara unit.
4. Verifikasi transfer khusus Super Admin.
5. Relasi wali ke siswa.
6. Portal wali santri.
7. Redirect dashboard per role.
8. Test akses dan keamanan.

Alasan:

Bendahara dan petugas paling dekat dengan fitur yang sudah ada. Portal wali santri butuh pembatas data dan alur pending transfer yang lebih hati-hati, jadi dikerjakan setelah scope unit stabil.

## Kriteria Selesai

Multi login dianggap selesai jika:

- Setiap role masuk dari halaman login yang sama.
- Sidebar setiap role berbeda sesuai tugas.
- Petugas hanya bisa menerima transaksi Cash.
- Bendahara unit bisa melihat sudah bayar dan belum bayar hanya untuk unitnya.
- Super Admin bisa verifikasi transfer.
- Petugas dan Bendahara Unit tidak bisa verifikasi transfer.
- Wali santri bisa melihat tagihan anak.
- Wali santri bisa upload bukti transfer.
- Wali santri tidak bisa memilih pembayaran Cash dari portal.
- Wali santri tidak bisa melihat data anak lain.
- Semua route sensitif dilindungi permission dan scope data.
- Bendahara Unit tidak memiliki aksi input, verifikasi, hapus, atau export jika aksesnya hanya monitoring.
