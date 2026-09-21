# PRD Menu Manajemen Siswa MA'WA CENTER

## Ringkasan

Menu Manajemen Siswa adalah halaman kerja untuk mengelola data akademik dan identitas siswa di MA'WA CENTER. Menu ini menjadi pusat operasional untuk melihat data siswa aktif, menambah/mengubah siswa, import/export data siswa, merapikan identitas lintas unit, memindahkan kelas, menaikkan kelas, dan mengelola alumni/nonaktif.

PRD ini merapikan arah Menu Manajemen Siswa yang sudah ada tanpa mengubah fondasi data utama. Fokus perubahan adalah memperjelas alur kerja admin, menjaga data lintas unit tetap aman, mengurangi risiko duplikasi siswa, dan memastikan operasi massal seperti import, pindah kelas, naik kelas, dan alumni dilakukan dengan konfirmasi yang jelas.

Semua desain wajib mengikuti [standar aplikasi.md](standar aplikasi.md), terutama standar warna netral, tombol primer hijau, halaman operasional tanpa hero marketing, card filter ringkas, tabel compact, form 40px, tipografi 14/16/20px, pagination, responsif mobile, dan tidak ada elemen visual yang bertumpuk.

## Status Dokumen

- **Status:** Final PRD untuk audit UI/fitur dan implementasi bertahap.
- **Ruang lingkup:** Menu Manajemen Siswa dan route `student-management.*` yang terkait data siswa.
- **Sumber implementasi saat ini:** `routes/student_management.php`, `MasterDataController`, `StudentIdentityCleanupController`, view `resources/views/master/index.blade.php`, dan view `resources/views/student-management/*`.
- **Standar desain:** [standar aplikasi.md](standar aplikasi.md).
- **Keputusan utama:** Manajemen Siswa adalah halaman kerja data, bukan dashboard statistik dan bukan bagian dari Data Master umum.

## Tujuan Produk

- Admin dapat melihat dan mencari data siswa aktif dengan cepat.
- Admin dapat memfilter siswa berdasarkan unit pendidikan, kelas, tahun pelajaran, status, dan kata kunci.
- Admin dapat menambah dan memperbarui data siswa dengan validasi yang jelas.
- Admin dapat import dan export data siswa dari Excel dengan preview sebelum disimpan.
- Admin dapat membuat kelas baru otomatis saat import jika data Excel valid.
- Admin dapat menggabungkan identitas siswa yang sama lintas unit tanpa menghapus transaksi per unit.
- Admin dapat memisahkan kembali identitas yang sudah digabung jika salah.
- Admin dapat memindahkan siswa antar kelas pada tahun pelajaran yang sama.
- Admin dapat menaikkan siswa ke kelas/tahun pelajaran tujuan.
- Admin dapat menjadikan satu kelas sebagai alumni/nonaktif dengan tanggal keluar dan alasan.
- Admin dapat melihat, mencari, dan mengedit data alumni/nonaktif.
- Perubahan data siswa harus otomatis menjaga sinkronisasi tagihan yang relevan.

## Success Metrics

Metrik ini dipakai untuk mengevaluasi apakah PRD sudah berhasil diimplementasikan:

| Area | Metrik | Target |
|---|---:|---:|
| Pencarian siswa | Admin dapat menemukan siswa aktif dari nama/NIS/NISN | <= 5 detik setelah halaman siap |
| Filter data | Filter unit + kelas + tahun pelajaran menghasilkan data yang konsisten | 100% sesuai query |
| Import siswa | Import massal selalu melalui preview sebelum simpan | 100% proses import |
| Error import | Baris gagal menampilkan alasan spesifik | 100% baris gagal |
| Operasi massal | Pindah kelas/naik kelas/alumni menampilkan jumlah siswa terdampak | 100% operasi sukses |
| Keamanan data | Gabung identitas tidak menghapus transaksi atau tagihan per unit | 0 transaksi hilang |
| Sinkronisasi tagihan | Tambah/edit/import siswa memicu sinkronisasi tagihan yang relevan | 100% operasi berdampak |
| Responsif | Tidak ada overlap teks/tombol pada desktop dan mobile | 0 issue visual blocker |
| Auditability | Operasi berisiko punya pesan konfirmasi/hasil yang jelas | 100% operasi berisiko |

## Non-Tujuan

- Tidak membuat landing page atau halaman pengantar.
- Tidak menggabungkan Manajemen Siswa dengan Data Master umum.
- Tidak mengubah rumus tagihan, SPP, daftar ulang, laundry, atau pembayaran lain.
- Tidak membuat sistem akademik lengkap seperti absensi, nilai, rapor, atau presensi.
- Tidak membuat workflow persetujuan berlapis untuk pindah kelas/naik kelas/alumni pada tahap ini.
- Tidak menghapus riwayat transaksi ketika siswa dipindah, dinaikkan kelas, dinonaktifkan, atau identitas digabung.
- Tidak menjadikan alumni sebagai data terpisah dari tabel siswa; alumni tetap siswa nonaktif.

## Pengguna Utama

- **Admin:** mengelola seluruh data siswa, import/export, pindah kelas, naik kelas, alumni, dan rapikan identitas.
- **Operator Data:** membantu input dan koreksi data siswa sesuai izin.
- **Bendahara Unit:** memakai data siswa sebagai konteks pembayaran/tagihan, tetapi tidak selalu diberi izin mengubah data siswa.
- **Super Admin:** mengatur akses dan melakukan koreksi data berisiko tinggi jika dibutuhkan.

## Prinsip UX

1. Menu harus terasa seperti meja kerja data, bukan dashboard dekoratif.
2. Alur utama selalu dimulai dari daftar atau filter, bukan dari kartu statistik besar.
3. Tabel harus mudah dipindai: NIS, nama, unit, kelas, gender, tahun/status, aksi.
4. Aksi massal harus selalu punya konteks filter yang jelas dan halaman konfirmasi.
5. Import harus memakai preview sebelum data disimpan.
6. Data lintas unit harus aman: siswa yang sama boleh punya beberapa record unit, tetapi bisa dihubungkan lewat identitas utama.
7. Perubahan akademik tidak boleh menghapus transaksi.
8. Pesan sukses/error harus spesifik: jumlah siswa, kelas tujuan, alasan gagal, atau baris import yang dilewati.
9. Mobile harus tetap operasional, tetapi prioritas utama adalah desktop admin.

## Struktur Menu Final

Sidebar memiliki satu parent menu:

```text
Manajemen Siswa
  Data Siswa
  Rapikan Identitas
  Pindah Kelas
  Naik Kelas
  Data Alumni
```

Catatan:

- `Data Siswa` adalah halaman default ketika membuka `/manajemen-siswa`.
- `Import` bukan submenu sendiri di sidebar; import menjadi aksi dari halaman Data Siswa.
- `Jadikan Alumni` bukan submenu sendiri; aksi ini muncul dari Data Siswa saat filter unit, kelas, dan tahun pelajaran sudah dipilih.
- `Data Alumni` menampilkan siswa nonaktif/alumni.

## Prioritas Fitur

Prioritas dipakai untuk menentukan urutan audit dan implementasi.

| Prioritas | Fitur | Alasan |
|---|---|---|
| P0 | Data Siswa aktif, filter, search, pagination, edit | Fondasi semua menu keuangan dan akademik bergantung pada data siswa yang benar. |
| P0 | Tambah/Edit siswa + sinkronisasi tagihan | Perubahan data siswa langsung mempengaruhi tagihan berjalan. |
| P0 | Import siswa dengan preview | Import massal berisiko tinggi dan sudah dipakai untuk operasional. |
| P0 | Pindah Kelas dan Naik Kelas | Operasi massal yang mempengaruhi data akademik banyak siswa. |
| P0 | Jadikan Alumni per kelas | Operasi massal berisiko tinggi karena mengubah status aktif siswa. |
| P1 | Rapikan Identitas | Penting untuk siswa lintas unit, tetapi harus tetap aman dan bisa ditinjau. |
| P1 | Data Alumni dan aktifkan kembali lewat edit | Dibutuhkan untuk arsip dan koreksi status siswa. |
| P1 | Export dan template import sesuai filter | Mendukung kerja admin dan backup operasional. |
| P1 | Audit log operasi berisiko | Dibutuhkan untuk pelacakan perubahan penting. |
| P2 | Permission granular per aksi | Peningkatan kontrol akses setelah alur utama stabil. |
| P2 | Dashboard kualitas data ringan | Hanya jika data duplikat/import sudah membutuhkan monitoring khusus. |

## Route

Route utama:

```text
GET  /manajemen-siswa
GET  /manajemen-siswa/data-siswa
GET  /manajemen-siswa/data-siswa/create
GET  /manajemen-siswa/data-siswa/import
GET  /manajemen-siswa/data-siswa/{student}/edit
GET  /manajemen-siswa/data-siswa/jadikan-alumni-kelas
POST /manajemen-siswa/data-siswa/jadikan-alumni-kelas
GET  /manajemen-siswa/rapikan-identitas
GET  /manajemen-siswa/rapikan-identitas/tinjau/{candidateKey}
POST /manajemen-siswa/rapikan-identitas/gabungkan
POST /manajemen-siswa/rapikan-identitas/pisahkan
GET  /manajemen-siswa/pindah-kelas
POST /manajemen-siswa/pindah-kelas
GET  /manajemen-siswa/naik-kelas
POST /manajemen-siswa/naik-kelas
GET  /manajemen-siswa/alumni
```

Route pendukung yang tetap dipakai dari master data:

```text
POST /master-data/students
PUT  /master-data/students/{student}
GET  /master-data/students/export
GET  /master-data/students/template
POST /master-data/students/import/preview
POST /master-data/students/import
```

## Hak Akses Dan Permission Matrix

Menu Manajemen Siswa tampil jika user memiliki izin:

```text
students.view
```

Permission granular final yang direkomendasikan:

| Permission | Fungsi |
|---|---|
| `students.view` | Melihat Data Siswa, Data Alumni, dan submenu Manajemen Siswa. |
| `students.create` | Menambah siswa. |
| `students.update` | Mengedit siswa dan mengaktifkan kembali siswa nonaktif. |
| `students.import` | Mengunduh template, preview import, dan konfirmasi import. |
| `students.export` | Export data siswa sesuai filter. |
| `students.movement` | Pindah Kelas dan Naik Kelas. |
| `students.alumni` | Jadikan Alumni per kelas dan melihat pengelolaan alumni. |
| `students.identity_cleanup` | Gabung dan pisah identitas siswa. |

Matrix role rekomendasi:

| Role | View | Create | Update | Import | Export | Movement | Alumni | Identity Cleanup |
|---|---|---|---|---|---|---|---|---|
| Super Admin | Ya | Ya | Ya | Ya | Ya | Ya | Ya | Ya |
| Admin | Ya | Ya | Ya | Ya | Ya | Ya | Ya | Ya |
| Operator Data | Ya | Ya | Ya | Ya | Ya | Tidak default | Tidak default | Tidak default |
| Bendahara Unit | Ya terbatas unit | Tidak | Tidak | Tidak | Tidak default | Tidak | Tidak | Tidak |
| Petugas Kasir | Ya terbatas kebutuhan pembayaran | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| Wali Santri | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |

Catatan implementasi:

- Jika permission granular belum tersedia, sistem boleh tetap memakai permission existing `students.view`, tetapi tombol berisiko harus disembunyikan dari role yang tidak semestinya.
- Server tetap menjadi sumber validasi izin. UI hanya membantu mencegah klik yang tidak relevan.
- Aksi massal P0 wajib diproteksi di route/controller, bukan hanya di Blade.

## Data Utama Siswa

Data siswa memiliki dua jenis kepemilikan data: identitas bersama dan data spesifik record/unit. Pembedaan ini penting karena satu anak bisa terdaftar pada lebih dari satu unit.

### Field Identitas Bersama

Field ini menggambarkan orang yang sama. Jika record siswa sudah tergabung melalui `identity_student_id`, perubahan pada salah satu record harus disalin ke seluruh record dalam grup identitas:

- NIS.
- NISN.
- Nama.
- Tempat lahir.
- Tanggal lahir.
- Jenis kelamin.
- Nama ayah.
- Nama ibu.
- WhatsApp ayah.
- WhatsApp ibu.
- Provinsi.
- Kota/Kabupaten.
- Kecamatan.
- Desa/Kelurahan.
- Alamat.

Catatan:

- NIS tetap boleh berbeda antar unit jika kebijakan sekolah begitu, tetapi ketika record digabung, perubahan NIS harus dilakukan hati-hati karena bisa berdampak ke pencarian dan import.
- Jika ke depan NIS terbukti spesifik unit, NIS harus dipindahkan dari field bersama menjadi field spesifik record. Untuk implementasi saat ini, update personal mengikuti daftar `studentPersonalFields()` di controller.

### Field Spesifik Record/Unit

Field ini melekat pada record siswa di unit/kelas tertentu dan tidak boleh disalin otomatis ke record unit lain:

- Unit pendidikan.
- Kelas.
- Tahun pelajaran.
- Tanggal masuk.
- Tanggal mulai tagihan.
- Status masuk: siswa lama, siswa baru, pindahan.
- Status aktif/nonaktif.
- Tanggal keluar.
- Alasan nonaktif/alumni.

### Field Relasi Identitas

- `identity_student_id` untuk menghubungkan record siswa yang sama pada unit berbeda.

Aturan ownership:

- Gabung identitas hanya mengubah relasi `identity_student_id`.
- Gabung identitas tidak memindahkan `student_id` transaksi, tagihan, atau pembayaran.
- Pisah identitas menghapus relasi grup dan tidak mengubah data personal maupun transaksi.
- Data akademik seperti kelas, unit, tahun pelajaran, status aktif, dan tanggal keluar tetap milik record masing-masing.

## Data Siswa

### Tujuan

Data Siswa adalah halaman utama untuk melihat, mencari, menambah, mengedit, export, import, dan menindaklanjuti siswa aktif.

### Struktur Tampilan

Urutan halaman:

1. Heading.
2. Aksi utama.
3. Filter.
4. Toolbar jumlah data dan pencarian.
5. Tabel siswa.
6. Pagination.

Heading:

```text
Data Siswa
Kelola data siswa aktif berdasarkan unit, kelas, dan tahun pelajaran.
```

Aksi utama:

```text
[Tambah Siswa] [Import] [Export] [Template]
```

Jika filter unit, kelas, dan tahun pelajaran aktif sudah lengkap, tampilkan:

```text
[Jadikan Alumni]
```

### Filter

Filter utama:

- Unit Pendidikan.
- Kelas.
- Tahun Pelajaran.
- Status Data: Aktif/Nonaktif jika halaman mendukung.
- Cari siswa.

Cari siswa menerima:

- Nama.
- NIS.
- NISN.

Aturan:

- Filter unit mempengaruhi pilihan kelas.
- Tahun pelajaran default mengikuti tahun pelajaran aktif.
- Reset menghapus filter dan kembali ke tahun aktif jika konteksnya Data Siswa aktif.
- Semua filter harus tetap terbawa saat pagination, sort, export, edit, dan kembali dari form.

### Tabel Data Siswa

Kolom minimum:

1. No.
2. NIS.
3. Nama.
4. Jenis Kelamin.
5. Unit.
6. Kelas.
7. Aksi.

Kolom opsional:

- NISN.
- Tahun Pelajaran.
- Status.

Aturan:

- Nama menjadi kolom utama dan paling mudah dipindai.
- NIS memakai font tabular/monospace ringan jika diperlukan.
- Unit memakai kode unit agar ringkas.
- Aksi edit memakai icon button dengan tooltip/label aksesibilitas.
- Tabel tidak boleh melebar tanpa scroll horizontal yang jelas.
- Empty state harus menyebut filter:

```text
Belum ada data siswa pada filter ini.
```

### Tambah/Edit Siswa

Form tambah/edit siswa harus memiliki kelompok field:

1. Identitas siswa.
2. Data orang tua/wali.
3. Alamat.
4. Data akademik.
5. Status data.

Validasi:

- Nama wajib.
- Kelas wajib.
- Tahun pelajaran wajib.
- NIS tidak boleh bentrok secara tidak sengaja pada konteks yang tidak valid.
- Tanggal keluar wajib jika status dibuat nonaktif.
- Alasan nonaktif wajib jika status dibuat nonaktif.
- Tanggal mulai tagihan opsional, tetapi jika ada harus valid.

Perilaku:

- Saat membuat siswa dari identitas yang sudah ada, field personal disalin dari identitas utama.
- Saat update field personal pada siswa yang sudah tergabung identitas, field personal ikut diperbarui pada semua record dalam grup identitas.
- Setelah siswa dibuat atau diperbarui, tagihan berjalan siswa harus disinkronkan ulang.

Pesan sukses:

```text
Data siswa berhasil ditambahkan.
Data siswa berhasil diperbarui.
```

## Import Data Siswa

### Tujuan

Import Data Siswa membantu admin memasukkan data siswa massal dari Excel dengan preview, validasi, dan ringkasan hasil.

### Alur

1. Admin membuka Data Siswa.
2. Admin klik Import.
3. Admin download template jika perlu.
4. Admin upload file Excel.
5. Sistem membaca file dan menampilkan preview.
6. Admin memeriksa status setiap baris.
7. Admin klik Konfirmasi Import.
8. Sistem menyimpan baris valid, membuat kelas baru jika diperlukan, dan menampilkan hasil.

### Template Excel

Kolom template harus sesuai export agar bolak-balik data mudah:

- No.
- NIS.
- NISN.
- Nama.
- Tempat Lahir.
- Tanggal Lahir.
- Jenis Kelamin.
- Nama Ayah.
- Nama Ibu.
- WhatsApp Ayah.
- WhatsApp Ibu.
- Provinsi.
- Kota/Kabupaten.
- Kecamatan.
- Desa/Kelurahan.
- Alamat.
- Unit Pendidikan.
- Kelas.
- Tanggal Masuk.
- Tanggal Mulai Tagihan.
- Status.
- Tanggal Keluar.
- Alasan Nonaktif.

### Preview Import

Preview menampilkan:

- Total baris.
- Valid.
- Update.
- Gagal.
- Pesan per baris.

Status baris:

- `Baru`: data baru siap disimpan.
- `Update`: data siswa sudah ada dan akan diperbarui.
- `Gagal`: baris tidak dapat disimpan.

Aturan:

- Import tidak boleh langsung menyimpan tanpa preview.
- File preview disimpan sementara dengan token.
- Jika token/file preview hilang, user diminta upload ulang.
- Jika tidak ada tahun pelajaran aktif, import ditolak.
- Setelah import berhasil, tagihan siswa baru/terdampak otomatis disinkronkan.

Pesan sukses:

```text
143 data siswa berhasil diimpor.
3 kelas baru dibuat otomatis.
2 baris dilewati: ...
Tagihan siswa baru otomatis diperbarui.
```

## Export Data Siswa

Export harus mengikuti filter yang sedang aktif:

- Unit.
- Kelas.
- Tahun Pelajaran.
- Status.
- Search.

Nama file:

```text
data-siswa-{scope}-{YYYY-MM-DD}.xlsx
```

Aturan:

- Jika unit dipilih, scope memakai kode unit.
- Jika tidak ada unit, scope memakai `semua`.
- Kolom export harus kompatibel dengan template import.

## Rapikan Identitas

### Tujuan

Rapikan Identitas dipakai untuk menemukan siswa yang kemungkinan adalah orang yang sama tetapi memiliki record berbeda, terutama karena siswa bisa terdaftar di beberapa unit.

Contoh:

- Satu anak memiliki record RA dan PONPES.
- Satu anak memiliki NIS berbeda antar unit.
- Nama sama tetapi NISN atau data orang tua menunjukkan orang yang sama.

### Prinsip Data

- Gabung identitas tidak menggabungkan transaksi.
- Setiap record siswa tetap berdiri pada unit/kelas masing-masing.
- Transaksi, tagihan, dan riwayat unit tetap aman.
- Relasi identitas hanya menghubungkan record sebagai orang yang sama.

### Kandidat Duplikasi

Sistem mendeteksi kandidat berdasarkan:

- NISN sama: confidence `Kuat`.
- Nama dan tanggal lahir sama: confidence `Kuat`.
- Nama dan orang tua sama: confidence `Sedang`.
- Nama sama: confidence `Perlu cek`.
- Grup yang sudah digabung: confidence `Gabungan`.

### Filter

Filter:

- Unit Pendidikan.
- Kelas.
- Tahun Pelajaran.
- Search.
- Jumlah data.

Search menerima:

- Nama.
- NIS.
- NISN.
- Unit.
- Kelas.
- Tahun Pelajaran.
- Alasan kandidat.

### Tinjau Kandidat

Halaman tinjau menampilkan semua record dalam kandidat:

- Nama.
- NIS.
- NISN.
- Unit.
- Kelas.
- Tahun pelajaran.
- Tanggal lahir.
- Orang tua.
- Status confidence.

Aksi:

```text
[Gabungkan Identitas]
[Kembali]
```

Untuk grup yang sudah digabung:

```text
[Pisahkan Kembali]
```

Validasi:

- Gabung wajib memilih minimal 2 siswa.
- Pisah wajib memakai root identitas yang valid.

Pesan sukses:

```text
Identitas siswa berhasil digabung. Transaksi tiap unit tetap aman.
Identitas siswa berhasil dipisahkan kembali.
```

## Pindah Kelas

### Tujuan

Pindah Kelas digunakan untuk memindahkan siswa aktif ke kelas lain pada tahun pelajaran yang sama.

### Alur

1. Admin membuka Pindah Kelas.
2. Admin memilih tahun pelajaran sumber.
3. Admin memfilter unit/kelas atau mencari siswa.
4. Admin memilih siswa yang akan dipindahkan.
5. Admin memilih kelas tujuan.
6. Admin klik Konfirmasi Pindah Kelas.
7. Sistem memvalidasi data.
8. Sistem memperbarui kelas siswa.

### Filter

Filter:

- Tahun Pelajaran.
- Unit Pendidikan.
- Kelas.
- Search.
- Jumlah data.

Search menerima:

- Nama.
- NIS.
- Unit.
- Kelas.
- Tahun Pelajaran.

### Validasi

- Minimal satu siswa harus dipilih.
- Siswa harus aktif.
- Siswa harus berada pada tahun pelajaran sumber.
- Kelas tujuan harus valid.
- Jika filter unit dipakai, kelas tujuan harus berada pada unit yang sama dengan filter.
- Kelas tujuan tidak boleh sama untuk seluruh siswa terpilih.
- Kelas tujuan tidak boleh sudah memiliki siswa dengan nama yang sama.

Pesan sukses:

```text
12 siswa berhasil dipindahkan kelas.
```

## Naik Kelas

### Tujuan

Naik Kelas digunakan untuk menaikkan siswa aktif ke kelas dan tahun pelajaran tujuan.

### Perbedaan Dengan Pindah Kelas

Pindah Kelas:

- Tahun pelajaran tetap sama.
- Hanya kelas berubah.

Naik Kelas:

- Tahun pelajaran tujuan wajib dipilih.
- Kelas tujuan wajib dipilih.
- Status masuk siswa menjadi `Siswa Lama`.

### Alur

1. Admin membuka Naik Kelas.
2. Admin memilih tahun pelajaran sumber.
3. Admin memfilter unit/kelas atau mencari siswa.
4. Admin memilih siswa.
5. Admin memilih tahun pelajaran tujuan.
6. Admin memilih kelas tujuan.
7. Admin klik Konfirmasi Naik Kelas.
8. Sistem memperbarui kelas dan tahun pelajaran siswa.

### Validasi

- Minimal satu siswa harus dipilih.
- Tahun pelajaran sumber wajib.
- Tahun pelajaran tujuan wajib.
- Kelas tujuan wajib.
- Siswa harus aktif.
- Siswa harus sesuai tahun pelajaran sumber.
- Kombinasi kelas dan tahun tujuan tidak boleh sama dengan kondisi siswa terpilih.
- Kelas tujuan tidak boleh sudah memiliki siswa dengan nama yang sama.

Pesan sukses:

```text
12 siswa berhasil dinaikkan kelas.
```

## Jadikan Alumni Per Kelas

### Tujuan

Jadikan Alumni Per Kelas digunakan untuk menonaktifkan seluruh siswa aktif pada kelas tertentu sebagai alumni.

### Akses Dari UI

Aksi ini muncul di Data Siswa hanya jika filter berikut sudah lengkap:

- Unit Pendidikan.
- Kelas.
- Tahun Pelajaran.
- Status Data: Aktif.

### Alur

1. Admin membuka Data Siswa.
2. Admin memilih unit, kelas, dan tahun pelajaran.
3. Admin klik Jadikan Alumni.
4. Sistem membuka halaman konfirmasi.
5. Admin mengisi tanggal keluar dan alasan.
6. Admin klik Konfirmasi.
7. Sistem mengubah semua siswa aktif pada kelas/tahun tersebut menjadi nonaktif.
8. Sistem mengarahkan ke Data Siswa dengan status nonaktif.

### Validasi

- Unit wajib.
- Kelas wajib.
- Tahun pelajaran wajib.
- Kelas harus sesuai unit.
- Tanggal keluar wajib.
- Alasan nonaktif wajib, maksimal 120 karakter.
- Harus ada minimal satu siswa aktif pada kelas dan tahun pelajaran yang dipilih.

Pesan sukses:

```text
28 siswa berhasil dijadikan alumni.
```

Pesan error:

```text
Pilih unit pendidikan, kelas, dan tahun pelajaran aktif terlebih dahulu.
Tidak ada siswa aktif pada kelas dan tahun pelajaran yang dipilih.
```

## Data Alumni

### Tujuan

Data Alumni menampilkan siswa nonaktif/alumni agar tetap bisa dicari, dicek, dan diedit/diaktifkan kembali jika diperlukan.

### Struktur Tampilan

Urutan halaman:

1. Heading.
2. Filter.
3. Toolbar jumlah data.
4. Tabel alumni.
5. Pagination.

Heading:

```text
Data Alumni
Kelola data siswa yang sudah lulus atau berstatus nonaktif/alumni.
```

Filter:

- Unit Pendidikan.
- Tahun Pelajaran.
- Search.
- Jumlah data.

Search menerima:

- Nama.
- NIS.
- NISN.
- Alasan nonaktif.
- Unit.
- Tahun pelajaran.

Tabel alumni:

1. No.
2. NIS.
3. Nama.
4. Jenis Kelamin.
5. Unit.
6. Tanggal Keluar.
7. Alasan.
8. Aksi.

Aksi:

```text
Edit / aktifkan kembali
```

Empty state:

```text
Belum ada data alumni
Data akan muncul setelah siswa dijadikan nonaktif/alumni.
```

## Sinkronisasi Tagihan

Perubahan data siswa yang berdampak ke tagihan harus memanggil sinkronisasi tagihan:

- Siswa baru dibuat.
- Siswa diperbarui.
- Import siswa berhasil.
- Kelas/tahun pelajaran siswa berubah.
- Status aktif/nonaktif berubah jika mempengaruhi tagihan berjalan.

Aturan:

- Sinkronisasi tidak boleh membuat transaksi baru.
- Sinkronisasi hanya memperbarui tagihan yang seharusnya ada/berubah.
- Riwayat pembayaran tetap utuh.
- Jika siswa punya pembayaran lama, perubahan identitas tidak boleh memindahkan transaksi ke record lain kecuali ada proses bisnis khusus.

## Edge Case Operasional

Edge case berikut wajib dipakai saat audit fitur dan test regresi.

### Data Siswa

- Siswa punya nama sama dalam kelas berbeda: tetap boleh, selama konteks kelas/unit jelas.
- Siswa punya nama sama dalam kelas tujuan: operasi pindah/naik kelas harus ditolak untuk mencegah duplikasi.
- Siswa aktif tanpa tahun pelajaran: harus tampil sebagai data bermasalah dan tidak boleh menyebabkan halaman error.
- Siswa aktif tanpa kelas: form/edit harus mengarahkan admin memperbaiki data, bukan membuat tabel rusak.
- NIS kosong: boleh hanya jika bisnis mengizinkan, tetapi pencarian dan import harus tetap stabil.
- NIS sama beda unit: harus ditinjau sebagai kemungkinan siswa lintas unit, bukan otomatis dianggap error global.
- Siswa nonaktif diedit menjadi aktif: tanggal keluar dan alasan nonaktif perlu dikosongkan atau tidak dipakai sebagai status aktif.

### Import Data Siswa

- File tanpa header wajib ditolak dengan pesan kolom wajib.
- File preview kadaluarsa/hilang wajib meminta upload ulang.
- Baris dengan unit tidak dikenal wajib gagal, kecuali unit sudah ada dan penulisan dapat dicocokkan.
- Baris dengan kelas baru pada unit valid boleh membuat kelas otomatis sesuai implementasi saat ini.
- Baris update tidak boleh menghapus field lama karena cell Excel kosong kecuali aturan import memang eksplisit mengizinkan.
- Import siswa baru harus memakai tahun pelajaran aktif.
- Jika tidak ada tahun pelajaran aktif, preview/import ditolak.
- Setelah import selesai, file sementara harus dihapus.

### Rapikan Identitas

- Kandidat nama sama tetapi orang berbeda tidak boleh digabung otomatis.
- Gabung dua record lintas unit tidak boleh memindahkan transaksi SPP, pembayaran lain, tagihan, atau riwayat.
- Gabung grup yang sudah pernah digabung harus menghasilkan satu root identitas yang stabil.
- Pisah grup harus menghapus relasi identitas semua anggota grup tersebut.
- Kandidat yang hilang karena filter/search tidak boleh menyebabkan route tinjau membuka data yang salah.

### Pindah Kelas

- Siswa yang dipilih tetapi sudah nonaktif harus ditolak.
- Siswa yang tidak berada pada tahun pelajaran sumber harus ditolak.
- Kelas tujuan yang tidak sesuai unit filter harus ditolak.
- Semua siswa terpilih sudah berada di kelas tujuan: operasi harus ditolak.
- Sebagian siswa sudah berada di kelas tujuan: sistem boleh memproses hanya jika hasil akhir valid dan pesan tetap jelas; rekomendasi final adalah menolak jika membuat konteks ambigu.

### Naik Kelas

- Tahun tujuan sama dengan tahun sumber dan kelas tujuan sama harus ditolak.
- Tahun tujuan wajib ada.
- Kelas tujuan wajib ada.
- Setelah naik kelas, `intake_status` menjadi `Siswa Lama`.
- Duplikasi nama di kelas tujuan harus ditolak.

### Jadikan Alumni

- Aksi tidak boleh muncul jika unit, kelas, dan tahun belum lengkap.
- Konfirmasi alumni wajib menyebut jumlah siswa terdampak.
- Jika kelas sudah kosong, proses ditolak.
- Tanggal keluar wajib valid.
- Alasan nonaktif wajib dan tidak boleh terlalu panjang.
- Setelah sukses, siswa tidak boleh tampil lagi di Data Siswa aktif pada filter yang sama.

### Data Alumni

- Alumni harus tetap bisa dicari berdasarkan nama, NIS, NISN, unit, tahun, dan alasan.
- Edit alumni harus tetap membuka form siswa yang sama.
- Mengaktifkan kembali alumni harus memastikan kelas dan tahun pelajaran masih valid.

## Empty State Dan Error State

Data Siswa:

```text
Belum ada data siswa pada filter ini.
```

Import:

```text
File belum dapat diproses.
Kolom wajib belum tersedia: ...
File preview sudah tidak tersedia. Silakan unggah ulang.
```

Rapikan Identitas:

```text
Belum ada kandidat identitas pada filter ini.
```

Pindah Kelas / Naik Kelas:

```text
Pilih minimal satu siswa.
Kelas tujuan masih sama dengan data siswa terpilih.
Kelas tujuan sudah memiliki siswa dengan nama yang sama: ...
```

Alumni:

```text
Belum ada data alumni.
```

## Standar Visual

Halaman Manajemen Siswa harus memakai standar visual aplikasi:

- Background halaman putih.
- Heading 20px / 700.
- Deskripsi 14px / 400.
- Filter card putih, border #d1d5db, radius 8px atau 12px sesuai standar halaman.
- Field tinggi 40px.
- Tombol primer hijau #004528 atau #157144 sesuai konteks existing.
- Tombol sekunder putih dengan border #d1d5db.
- Tabel compact, font 14px.
- Tidak memakai hero besar.
- Tidak memakai card bertumpuk.
- Tidak memakai warna dekoratif berlebihan.
- Tidak ada teks yang overlap di desktop atau mobile.

## Layout Desktop

Gambaran Data Siswa:

```text
+--------------------------------------------------------------+
| Data Siswa                          [Tambah] [Import] [Export]|
| Kelola data siswa aktif berdasarkan unit, kelas, dan tahun.   |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| Unit Pendidikan | Kelas | Tahun Pelajaran | Cari Siswa        |
| [Semua]         | [..]  | [2026/2027]     | [Nama/NIS/NISN]   |
| [Terapkan] [Reset]                                      |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| Tampilkan [10] data                         1-10 dari 250    |
+--------------------------------------------------------------+
| No | NIS    | Nama                | L/P | Unit | Kelas | Aksi |
| 1  | 240010 | AHMAD HASBY MUJTABA | L   | RA   | B5    | edit |
+--------------------------------------------------------------+
```

Gambaran Pindah Kelas / Naik Kelas:

```text
+--------------------------------------------------------------+
| Pindah Kelas                                                 |
| Pilih kelas tujuan, centang siswa, lalu konfirmasi.           |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| Tahun | Unit | Kelas | Cari                                  |
| [..]  | [..] | [..]  | [Nama/NIS] [Terapkan] [Reset]         |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| Kelas Tujuan [..]                         [Konfirmasi]        |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| [ ] | NIS | Nama | Unit | Kelas | Tahun                       |
+--------------------------------------------------------------+
```

## Layout Mobile

Urutan mobile Data Siswa:

```text
Data Siswa
[Tambah Siswa]
[Import] [Export]

Filter
Unit Pendidikan
Kelas
Tahun Pelajaran
Cari Siswa
[Terapkan]
[Reset]

Tabel/List Siswa
Pagination
```

Aturan mobile:

- Semua filter menjadi satu kolom.
- Tombol utama full width jika ruang sempit.
- Tabel boleh horizontal scroll, tetapi header dan isi tidak boleh bertumpuk.
- Aksi icon harus tetap minimal 40px tinggi/lebar sentuh.
- Form tambah/edit menjadi satu kolom.
- Modal/pesan sukses harus berada di tengah dan tidak menutupi tombol penting secara permanen.

## Audit Dan Keamanan Data

Operasi yang perlu audit log:

- Tambah siswa.
- Edit siswa.
- Import siswa.
- Export siswa.
- Gabung identitas.
- Pisah identitas.
- Pindah kelas.
- Naik kelas.
- Jadikan alumni.
- Aktifkan kembali siswa.

Minimal audit log mencatat:

- User.
- Waktu.
- Jenis aksi.
- Jumlah siswa terdampak.
- ID siswa terdampak.
- Data sebelum dan sesudah untuk field penting.

Jika audit log belum tersedia, PRD ini menjadi arah implementasi berikutnya dan operasi tetap harus memakai pesan konfirmasi yang jelas.

## Kriteria Penerimaan

Kriteria dibuat testable agar bisa dipakai sebagai checklist audit UI/fitur.

### Navigasi Dan Akses

- Given user memiliki `students.view`, when membuka sidebar, then parent menu `Manajemen Siswa` tampil.
- Given user membuka `/manajemen-siswa`, when request berhasil, then sistem redirect ke `/manajemen-siswa/data-siswa`.
- Given user tidak memiliki izin aksi tertentu, when membuka halaman, then tombol aksi terkait tidak tampil atau disabled dengan alasan jelas.
- Given user mencoba akses route aksi tanpa izin, when request dikirim, then server menolak sesuai middleware permission.

### Data Siswa

- Given tahun pelajaran aktif tersedia, when Data Siswa dibuka tanpa query, then data default memakai konteks tahun pelajaran aktif.
- Given filter unit dipilih, when form filter tampil, then pilihan kelas hanya berisi kelas pada unit tersebut atau nilai kelas invalid direset.
- Given user mencari nama/NIS/NISN, when menekan Terapkan, then tabel hanya menampilkan siswa yang cocok dan query tetap terbawa ke pagination.
- Given user mengubah `per_page`, when halaman reload, then jumlah baris sesuai pilihan dan filter tetap bertahan.
- Given user klik sort kolom yang didukung, when halaman reload, then urutan data sesuai kolom dan arah sort.
- Given filter unit, kelas, dan tahun aktif lengkap, when status data aktif, then tombol `Jadikan Alumni` tampil.

### Tambah/Edit Siswa

- Given form tambah siswa valid, when disimpan, then siswa baru dibuat, pesan sukses tampil, dan sinkronisasi tagihan dipanggil.
- Given form tambah siswa memilih identitas existing, when disimpan, then field identitas bersama disalin dari root identitas dan `identity_student_id` terisi.
- Given form edit siswa tergabung identitas, when field personal diperbarui, then field personal pada record dalam grup identitas ikut berubah.
- Given status siswa diubah menjadi nonaktif, when tanggal keluar atau alasan kosong, then validasi menolak.
- Given status siswa aktif, when form disimpan, then data akademik tetap milik record tersebut dan tidak disalin ke record unit lain.

### Import Data Siswa

- Given file Excel valid, when preview dijalankan, then sistem menampilkan ringkasan total, valid, update, gagal, dan pesan per baris.
- Given file Excel tidak punya header wajib, when preview dijalankan, then sistem menolak dengan pesan kolom yang belum tersedia.
- Given tidak ada tahun pelajaran aktif, when import dilakukan, then sistem menolak sebelum preview disimpan.
- Given preview valid dan token masih tersedia, when Konfirmasi Import dikirim, then baris valid disimpan dan file sementara dihapus.
- Given import membuat kelas baru, when import selesai, then pesan sukses menyebut jumlah kelas baru.
- Given import punya baris gagal, when import selesai, then pesan sukses menyebut jumlah baris dilewati dan contoh alasan.
- Given import menyimpan siswa baru/terdampak, when selesai, then tagihan siswa baru otomatis diperbarui.

### Export Data Siswa

- Given filter unit/kelas/tahun/status/search aktif, when Export diklik, then file Excel hanya berisi data sesuai filter.
- Given tidak ada unit terpilih, when Export diklik, then nama file memakai scope `semua`.
- Given unit terpilih, when Export diklik, then nama file memakai kode unit.
- Given template import diunduh, when file dibuka, then header kompatibel dengan format import/export.

### Rapikan Identitas

- Given ada siswa dengan NISN sama, when Rapikan Identitas dibuka, then kandidat tampil dengan confidence `Kuat`.
- Given ada siswa dengan nama dan tanggal lahir sama, when Rapikan Identitas dibuka, then kandidat tampil dengan confidence `Kuat`.
- Given ada siswa dengan nama dan orang tua sama, when Rapikan Identitas dibuka, then kandidat tampil dengan confidence `Sedang`.
- Given hanya nama yang sama, when Rapikan Identitas dibuka, then kandidat tampil dengan confidence `Perlu cek`.
- Given minimal dua siswa dipilih, when Gabungkan Identitas dikirim, then satu root identity dipilih dan anggota lain mengarah ke root.
- Given identitas digabung, when transaksi siswa dicek, then `student_id` transaksi tetap pada record asal.
- Given grup identitas dipisah, when Pisahkan Kembali dikirim, then seluruh anggota grup memiliki `identity_student_id = null`.

### Pindah Kelas

- Given siswa aktif dipilih dan kelas tujuan valid, when Konfirmasi Pindah Kelas dikirim, then `school_class_id` siswa berubah ke kelas tujuan.
- Given siswa terpilih tidak sesuai tahun sumber, when proses dikirim, then sistem menolak.
- Given kelas tujuan masih sama untuk seluruh siswa terpilih, when proses dikirim, then sistem menolak.
- Given kelas tujuan sudah memiliki siswa dengan nama sama, when proses dikirim, then sistem menolak dan menyebut nama duplikat.
- Given proses sukses, when redirect selesai, then pesan menyebut jumlah siswa yang dipindahkan.

### Naik Kelas

- Given siswa aktif dipilih, tahun tujuan valid, dan kelas tujuan valid, when Konfirmasi Naik Kelas dikirim, then `academic_year_id`, `school_class_id`, dan `intake_status` diperbarui.
- Given tahun/kelas tujuan sama dengan data siswa terpilih, when proses dikirim, then sistem menolak.
- Given kelas tujuan sudah memiliki siswa dengan nama sama, when proses dikirim, then sistem menolak.
- Given proses sukses, when redirect selesai, then pesan menyebut jumlah siswa yang dinaikkan kelas.

### Jadikan Alumni

- Given unit/kelas/tahun belum lengkap, when admin mencoba membuka Jadikan Alumni, then sistem redirect ke Data Siswa dengan pesan error.
- Given kelas/tahun tidak punya siswa aktif, when admin membuka Jadikan Alumni, then sistem menolak dengan pesan error.
- Given form konfirmasi valid, when disimpan, then semua siswa aktif pada kelas/tahun tersebut menjadi `is_active = false`.
- Given proses sukses, when redirect selesai, then status filter mengarah ke nonaktif dan pesan menyebut jumlah siswa yang dijadikan alumni.

### Data Alumni

- Given siswa sudah nonaktif, when Data Alumni dibuka, then siswa tampil di tabel alumni.
- Given search alumni memakai nama/NIS/NISN/alasan, when Terapkan diklik, then hasil sesuai query.
- Given user klik aksi edit alumni, when halaman edit dibuka, then form menampilkan record siswa nonaktif yang benar.
- Given belum ada alumni, when halaman dibuka, then empty state tampil tanpa tabel rusak.

### Visual Dan Responsif

- Given viewport desktop, when semua halaman Manajemen Siswa dibuka, then heading, filter, toolbar, tabel, dan pagination memakai container yang konsisten.
- Given viewport mobile, when halaman dibuka, then filter menjadi satu kolom dan halaman utama tidak melebar ke kanan.
- Given tabel memiliki banyak kolom, when viewport sempit, then hanya wrapper tabel yang scroll horizontal.
- Given tombol aksi tabel tampil, when dicek aksesibilitas, then setiap tombol icon-only punya `title` dan `aria-label`.
- Given pesan sukses/error tampil, when modal/toast dibuka, then teks tidak overlap dan tombol close/OK bisa ditekan.

## Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Import Excel membuat kelas otomatis yang salah karena nama kelas tidak standar. | Data kelas bertambah tidak rapi dan siswa masuk kelas keliru. | Preview wajib menampilkan unit/kelas/status, kelas baru disebut di ringkasan, dan admin perlu konfirmasi sebelum simpan. |
| Import memperbarui data siswa yang sebenarnya berbeda orang karena NIS/nama mirip. | Identitas siswa tertukar. | Status update harus jelas pada preview; baris ambigu harus gagal atau masuk kandidat Rapikan Identitas. |
| Gabung identitas salah. | Konteks siswa lintas unit membingungkan. | Tinjau kandidat menampilkan NIS, NISN, tanggal lahir, orang tua, unit, kelas; pisah kembali tersedia. |
| Gabung identitas memindahkan transaksi. | Riwayat pembayaran/tagihan rusak. | Gabung hanya boleh mengubah `identity_student_id`; transaksi tetap memakai `student_id` asal. |
| Pindah kelas massal salah sasaran karena filter tidak jelas. | Banyak siswa berpindah kelas keliru. | Halaman konfirmasi menampilkan tahun sumber, kelas tujuan, dan jumlah siswa terpilih; operasi butuh minimal satu siswa terpilih. |
| Naik kelas membuat duplikasi siswa pada kelas tujuan. | Data kelas tujuan menjadi ganda. | Validasi nama duplikat di kelas tujuan wajib berjalan sebelum update. |
| Jadikan Alumni salah kelas. | Satu kelas aktif menjadi nonaktif. | Tombol hanya muncul saat unit, kelas, dan tahun lengkap; halaman konfirmasi wajib menyebut jumlah siswa terdampak. |
| Siswa alumni diaktifkan kembali tanpa kelas/tahun valid. | Siswa aktif tidak masuk konteks akademik yang benar. | Form edit wajib memastikan kelas dan tahun pelajaran valid sebelum status aktif disimpan. |
| Sinkronisasi tagihan setelah edit/pindah kelas tidak tepat. | Tagihan berjalan tidak sesuai data akademik terbaru. | Operasi yang mengubah data akademik wajib memanggil service sinkronisasi dan punya test regresi. |
| Permission terlalu luas. | Operator dapat menjalankan aksi massal yang tidak semestinya. | Gunakan matrix permission sebagai target; sebelum granular tersedia, sembunyikan aksi berisiko dari role non-admin. |

## Definition Of Done

Sebuah pekerjaan audit/implementasi Menu Manajemen Siswa dianggap selesai jika:

- Perubahan sesuai bagian P0/P1/P2 pada PRD ini.
- Route, controller, view, dan CSS mengikuti standar aplikasi.
- Validasi server tersedia untuk setiap aksi berisiko.
- UI hanya menampilkan aksi yang sesuai permission user.
- Pesan sukses/error menyebut objek atau jumlah data terdampak.
- Import selalu melalui preview.
- Operasi massal memiliki konteks dan konfirmasi yang jelas.
- Sinkronisasi tagihan tetap berjalan untuk operasi yang mengubah data siswa.
- Tidak ada transaksi/tagihan yang hilang akibat gabung identitas, pindah kelas, naik kelas, atau alumni.
- Halaman lolos cek desktop dan mobile tanpa overlap.
- Test feature atau audit manual dicatat untuk flow yang diubah.

## Rekomendasi Tahapan Implementasi

Tahap 1:

- Audit UI Data Siswa terhadap PRD ini.
- Rapikan filter, toolbar, tabel, dan aksi utama.
- Pastikan import/export mengikuti filter.

Tahap 2:

- Audit Rapikan Identitas.
- Tambahkan tampilan tinjau yang lebih jelas jika belum cukup.
- Tambahkan audit log gabung/pisah identitas.

Tahap 3:

- Audit Pindah Kelas dan Naik Kelas.
- Perkuat konfirmasi massal dan validasi duplikasi.
- Pastikan sinkronisasi tagihan setelah perpindahan akademik.

Tahap 4:

- Audit Jadikan Alumni dan Data Alumni.
- Tambahkan alur aktifkan kembali yang eksplisit jika belum cukup.
- Tambahkan audit log alumni/nonaktif.

Tahap 5:

- Pecah permission granular jika dibutuhkan.
- Tambahkan dashboard kualitas data ringan jika nanti diperlukan, tetapi tidak menggantikan halaman kerja utama.

## Traceability Implementasi

Bagian ini membantu developer menghubungkan requirement PRD dengan kode yang sudah ada.

| Area PRD | Route/Controller/View saat ini | Catatan audit |
|---|---|---|
| Sidebar Manajemen Siswa | `resources/views/partials/sidebar.blade.php` | Pastikan submenu aktif, hover, dan permission mengikuti standar sidebar. |
| Data Siswa | `student-management.students.index`, `MasterDataController@studentIndex`, `resources/views/master/index.blade.php` | Data Siswa masih memakai view master; audit visual harus hati-hati agar Data Master lain tidak terdampak. |
| Tambah/Edit Siswa | `student-management.students.create`, `student-management.students.edit`, `storeStudent`, `updateStudent` | Pastikan field ownership identitas bersama vs record/unit dipatuhi. |
| Import Siswa | `student-management.students.import`, `previewStudentImport`, `importStudents`, `StudentImportService` | Preview token, kelas otomatis, dan sinkronisasi tagihan wajib diuji. |
| Export/Template | `exportStudents`, `studentTemplate` | Export harus mengikuti filter aktif dan kompatibel dengan template. |
| Rapikan Identitas | `StudentIdentityCleanupController`, `identity-cleanup*.blade.php` | Gabung/pisah hanya mengubah relasi identitas, bukan transaksi. |
| Pindah Kelas | `studentTransfer`, `storeStudentTransfer`, `class-movement.blade.php` | Mode transfer memakai tahun sumber sebagai tahun target. |
| Naik Kelas | `studentPromotion`, `storeStudentPromotion`, `class-movement.blade.php` | Mode promotion wajib memperbarui tahun tujuan dan `intake_status`. |
| Jadikan Alumni | `classAlumniCreate`, `storeClassAlumni`, `class-alumni.blade.php` | Aksi hanya dari filter unit/kelas/tahun yang lengkap. |
| Data Alumni | `studentAlumni`, `alumni.blade.php` | Alumni adalah siswa `is_active = false`, bukan tabel terpisah. |
| Sinkronisasi Tagihan | `BillService` dipanggil dari operasi siswa | Audit agar perubahan akademik tidak membuat tagihan ganda atau hilang. |

## Catatan Implementasi Saat Ini

Implementasi saat ini sudah memiliki dasar berikut:

- Sidebar Manajemen Siswa dengan submenu Data Siswa, Rapikan Identitas, Pindah Kelas, Naik Kelas, Data Alumni.
- Route `student-management.*`.
- Data Siswa memakai controller existing di `MasterDataController`.
- Import siswa memakai preview token dan `StudentImportService`.
- Rapikan Identitas memakai `StudentIdentityCleanupController`.
- Pindah Kelas dan Naik Kelas memakai halaman class movement yang sama dengan mode berbeda.
- Data Alumni memakai siswa `is_active = false`.

PRD ini menjadi acuan untuk audit visual, penyempurnaan UX, permission, audit log, dan perapian alur agar semua bagian Manajemen Siswa konsisten dengan menu lain.
