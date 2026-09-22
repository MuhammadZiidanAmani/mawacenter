# PRD Induk MA'WA CENTER

Versi: 1.0 | Tanggal konsolidasi: 21 September 2026 | Bahasa UI: Indonesia.

Dokumen ini menggabungkan seluruh PRD modul yang sebelumnya terpisah dalam folder `ai`, standar lintas aplikasi, serta catatan rilis/QA/cleanup. Dokumen sumber telah dihapus setelah konsolidasi. Detail visual berada di [Design System CSS/Tailwind](DESIGN-SYSTEM-TAILWIND.md). ID sumber historis dan pemetaannya berada di [indeks dokumentasi](README.md#inventaris-sumber); isi sumber lama dapat ditelusuri melalui riwayat Git.

**Status:** acuan produk gabungan, bukan pernyataan bahwa seluruh kebutuhan sudah diterapkan atau diuji. Istilah **Kebutuhan** menyatakan perilaku yang harus dipenuhi; **Kode saat ini** menyatakan hasil pembacaan repository; **Gap** menyatakan hal yang belum sesuai atau belum terbukti.

## Daftar Isi

1. [Produk dan tujuan](#1-produk-dan-tujuan)
2. [Arsitektur dan navigasi](#2-arsitektur-dan-navigasi)
3. [Keputusan dan perbedaan sumber](#3-keputusan-dan-perbedaan-sumber)
4. [Role dan autentikasi](#4-role-dan-autentikasi)
5. [Data dan aturan lintas modul](#5-data-dan-aturan-lintas-modul)
6. [Manajemen Siswa](#6-manajemen-siswa)
7. [Pembayaran](#7-pembayaran)
8. [Tagihan](#8-tagihan)
9. [Portal wali dan verifikasi](#9-portal-wali-dan-verifikasi)
10. [Laporan](#10-laporan)
11. [Dashboard, master, dan pengaturan](#11-dashboard-master-dan-pengaturan)
12. [Kontrak teknis dan nonfungsional](#12-kontrak-teknis-dan-nonfungsional)
13. [Kriteria penerimaan](#13-kriteria-penerimaan)
14. [Prioritas, risiko, dan rilis](#14-prioritas-risiko-dan-rilis)

## 1. Produk dan Tujuan

Ma'wa Center adalah aplikasi administrasi siswa dan keuangan lintas unit pendidikan. Alur utamanya: mengelola siswa serta tarif, membentuk tagihan, menerima pembayaran, memverifikasi transfer, dan menghasilkan laporan resmi.

Unit yang dikenal dalam PRD: PAUD, RA, MI, MTs, MA, dan PONPES. PONPES adalah unit sejajar, bukan turunan MTs/MA. Satu anak dapat memiliki beberapa pendaftaran unit yang dihubungkan sebagai satu identitas.

| Tujuan | Indikator Penerimaan |
| --- | --- |
| Pencarian cepat | Siswa ditemukan dari nama/NIS/NISN; target operasional <= 5 detik setelah halaman siap, belum merupakan benchmark terukur |
| Data akademik konsisten | Filter unit, kelas, tahun, dan status menghasilkan konteks yang benar |
| Import terkendali | Seluruh import melalui preview; setiap baris gagal memiliki alasan |
| Operasi massal jelas | Konfirmasi dan hasil menyebut konteks serta jumlah siswa terdampak |
| Riwayat keuangan utuh | Tidak ada transaksi hilang akibat perubahan kelas, alumni, atau identitas |
| Tagihan konsisten | Perubahan siswa/tarif/pembayaran memperbarui tagihan relevan tanpa menggandakan kewajiban |
| Akses sesuai tugas | Tidak ada pembacaan atau perubahan di luar permission dan scope data pengguna |
| UI operasional | Tidak ada overlap atau scroll halaman horizontal pada desktop/mobile |

Di luar cakupan: landing page pemasaran, absensi/nilai/rapor, jurnal akuntansi/neraca/buku besar lengkap, approval akademik berlapis, serta perubahan rumus keuangan hanya karena desain UI berubah. Pimpinan dan Operator Data adalah persona kebutuhan; keduanya bukan role key bawaan baru.

## 2. Arsitektur dan Navigasi

Kode saat ini memakai Laravel 13, PHP sesuai batas `composer.json`, Blade, Eloquent, JavaScript di `resources/js/app.js`, Vite, dan Tailwind CSS v4. CSS aplikasi memakai token `--app-*` dan module CSS; aplikasi bukan SPA. PDF memakai Dompdf; XLSX memakai helper lokal `SimpleXlsxWriter`/`StudentXlsx`.

| Area | Halaman/Route Utama | Tanggung Jawab |
| --- | --- | --- |
| Login | `/login` | Satu pintu masuk dengan mode biasa dan wali |
| Dashboard | `/` | Ringkasan penerimaan, kewajiban, tren, dan aktivitas |
| Manajemen Siswa | `/manajemen-siswa/data-siswa` | Data aktif, tambah/edit, import/export |
| Identitas | `/manajemen-siswa/rapikan-identitas` | Kandidat, tinjau, gabung, pisah |
| Perubahan kelas | `/manajemen-siswa/pindah-kelas`, `/manajemen-siswa/naik-kelas` | Perubahan akademik massal |
| Alumni | `/manajemen-siswa/alumni` | Siswa nonaktif dan reaktivasi |
| Pembayaran | `/keuangan/pembayaran` | Transaksi baru, struk, riwayat singkat |
| Tagihan | `/keuangan/tagihan` | Kewajiban belum selesai dan surat tagihan |
| Verifikasi | `/keuangan/verifikasi-transfer` | Pemeriksaan transfer wali oleh Super Admin |
| Laporan | `/laporan/transaksi`, `/laporan/spp-perbulan`, `/laporan/spp-tahun-pelajaran`, `/laporan/rekap-unit` | Arsip, rekap, audit, XLSX/PDF |
| Data Master | `/master-data?tab=...` | Tahun, unit, kelas, kategori, keringanan, role, user |
| Pengaturan | `/pengaturan` | Akun dan konfigurasi rekening transfer |
| Portal wali | `/wali-santri/tagihan` dan tampilan wali pada Tagihan | Tagihan anak serta pengajuan transfer |

Sidebar hanya menampilkan menu yang diizinkan. Topbar memuat konteks Tahun Pelajaran Aktif. Halaman `/manajemen-siswa` mengarah ke Data Siswa. Import Siswa, Jadikan Alumni, dan Import Pembayaran adalah aksi dalam konteks modul, bukan submenu/tab utama tambahan. Riwayat pembayaran lengkap berada di Laporan; route riwayat lama boleh dipertahankan untuk kompatibilitas dan kebutuhan riwayat petugas.

## 3. Keputusan dan Perbedaan Sumber

Instruksi pengguna terbaru mengarahkan perubahan. Untuk dokumen yang bertentangan, gunakan keputusan eksplisit pada tabel ini, kebutuhan akses yang paling membatasi, dan aturan modul yang lebih spesifik. Keberadaan kode yang belum sesuai bukan alasan menghapus kebutuhan PRD; catat gap dan uji ketika area itu dikerjakan.

| Topik | Keputusan Konsolidasi / Kode Saat Ini |
| --- | --- |
| Bendahara dapat membayar menurut PRD Tagihan/Pembayaran lama | Kebutuhan Multi Login berlaku: Bendahara Unit membaca data unit, tidak input, verifikasi, atau hapus transaksi. Export tidak diberikan secara default. Tombol Bayar hanya untuk role yang berhak. |
| "SPP Belum Bayar" disebut sebagai submenu laporan | Tindak lanjut tunggakan berada di Tagihan. Empat submenu laporan mengikuti PRD Laporan. |
| Redirect bendahara | Kebutuhan lama: Dashboard Unit. Kode `AuthController`: Tagihan. Jangan mengklaim Dashboard Unit sudah selesai. |
| NIS sebagai field personal bersama | Kode `studentPersonalFields()` tidak memuat `nis`; pertahankan NIS per pendaftaran/unit. NISN dan personal lain mengikuti field bersama. Jangan menyalin NIS antar unit saat edit identitas. |
| Kualitas Data disebut selesai di release P0-P3 | Route `/manajemen-siswa/kualitas-data` tidak ditemukan di `routes/student_management.php` saat konsolidasi. Kebutuhannya dipertahankan pada 6.8 sebagai gap, bukan route aktif. |
| Tata letak kasir kanan pada PRD lama | Arahan pengguna dengan `Setelah.jpeg`: ringkasan angka di atas, kasir kiri, tagihan kanan, riwayat di bawah kasir; warna tetap token Laravel aplikasi. Lihat 7.1. |
| Warna slate pada revisi Pembayaran | Warna aktual `--app-*` berlaku; jangan mengganti palet aplikasi dengan slate/biru dari referensi. |
| Tipografi global dan revisi Pembayaran | Kontrak lengkap berada di [Design System CSS/Tailwind](DESIGN-SYSTEM-TAILWIND.md#3-tipografi-dan-format-data): DM Sans; body/tabel 14/20px 400, label/header tabel/tombol 14/20px 600, metadata 12/16px, section 18/26px 700, judul halaman/total utama 24/32px 700. Pembayaran boleh memakai 12px/600 hanya untuk micro-label. Bukan izin mengecilkan body. |
| Radius 8px vs filter 12px | Standar aktif 7px mengikuti Tailwind Admin. Filter lama 12px bukan acuan untuk komponen baru dan dimigrasikan bertahap. |
| Checkbox native vs pilihan tagihan berbentuk lingkaran | Native untuk seleksi tabel/massal. Bentuk khusus tagihan mengikuti referensi Pembayaran, tetap input checkbox semantik dan dapat dioperasikan keyboard. |
| Pagination Tagihan | Tampil jika total > `per_page`; informasi hasil di footer bawah, tidak diduplikasi di toolbar. |
| Tahun pelajaran di setiap filter | Tahun aktif dari topbar untuk operasional. Pilihan tahun eksplisit untuk arsip/laporan, input akademik, serta target Naik Kelas; konteks sumber tetap dikirim dan divalidasi server. |
| Export data kosong | Khusus laporan tetap menghasilkan dokumen berheader dan keterangan kosong. Modul lain mengikuti perilaku terdokumentasi; jangan error mentah. |
| Mode terang dan gelap | Light memakai canvas putih `#ffffff`; dark memakai token `html[data-theme="dark"]` lengkap. Tombol topbar menyimpan preferensi pada `localStorage` (`mawa-center-theme`) dan memakai preferensi sistem bila belum ada pilihan. Alur keuangan tidak terpengaruh; PDF/struk tetap putih. Lihat [aturan mode gelap](DESIGN-SYSTEM-TAILWIND.md#mode-gelap). |
| Scope Dashboard Unit | `DashboardController` saat ini membangun agregat/cache global; scope pengguna/unit belum terlihat di query tersebut. Kebutuhan scope bendahara masih perlu audit, termasuk cache key. |

Catatan akses lain: permission `payments.verify_transfer` saat ini juga dipakai untuk sejumlah aksi import/edit/hapus/sinkron. Jangan menebak permission baru seperti `payments.import` tanpa mendefinisikan dan memigrasikannya. Verifikasi pembatasan peran tetap mencakup middleware, controller, service, dan URL langsung.

## 4. Role dan Autentikasi

### 4.1 Matrix Role

Sumber: S01, S02, `Role`, `User`, `AuthController`, `EnsureRolePermission`.

| Role Key | Label | Scope dan Kebutuhan |
| --- | --- | --- |
| `admin` | Super Admin | Semua unit; siswa, tarif, user/role, transaksi, laporan; satu-satunya verifier transfer wali |
| `kasir` | Petugas | Penerimaan Cash, pencarian/tagihan sesuai unit penugasan, struk, riwayat milik sendiri; tanpa input Transfer, master, atau verifikasi |
| `bendahara` | Bendahara Unit | Monitoring pembayaran/tagihan/laporan dalam unit yang ditugaskan; tanpa input, hapus, verifikasi; export tidak default |
| `orang_tua` | Wali Santri | Anak yang terhubung, termasuk pendaftaran identitas lintas unit; kirim transfer, status, struk setelah diterima; tanpa Cash/internal reporting |

Pengguna tanpa penugasan yang diperlukan tidak memperoleh akses global secara otomatis. `null` dan array kosong pada helper scope tidak boleh dipertukarkan: cek kontrak `accessibleUnitIds()`/`accessibleStudentIds()` sebelum mengubah query.

### 4.2 Permission

| Kelompok | Permission |
| --- | --- |
| Dashboard | `dashboard.view` |
| Siswa | `students.view`, `students.create`, `students.update`, `students.import`, `students.export`, `students.movement`, `students.alumni`, `students.identity_cleanup` |
| Pembayaran | `payments.cash.create`, `payments.transfer.submit_guardian`, `payments.verify_transfer`, `payments.view_own`, `payments.view_unit` |
| Tagihan | `bills.view`, `bills.view_unit`, `bills.view_guardian` |
| Laporan | `reports.view`, `reports.view_unit`, `reports.export` |
| Administrasi | `master.manage`, `users.manage`, `settings.view` |

`Role::defaultPermissionsFor()` menentukan mapping bawaan; role kustom tidak boleh diperlakukan sebagai admin berdasarkan nama tampilan. `students.view` tidak cukup untuk mengubah siswa atau operasi massal. Permission yang tampil di sidebar tidak menggantikan validasi server. Route autentikasi yang belum dipetakan harus ditolak oleh aturan akses, bukan otomatis dibuka.

### 4.3 Login dan Sesi

- Login biasa memakai username/password; username dinormalisasi lowercase dan trim. Email bukan alternatif login.
- Login wali memakai unit, NIS pada field username, serta password akun wali yang sudah terhubung. Unit PONPES berada sejajar dengan unit lain.
- Cocokkan NIS dalam unit terpilih, lalu relasi identitas dan akun wali; jangan membuat akun secara otomatis dari kredensial yang ditebak.
- Akun wali dengan `must_reset_password` wajib ditangani administrator sesuai alur reset yang tersedia.
- Setelah login, regenerasi sesi. Logout memakai POST dengan CSRF, mengakhiri sesi dan mengganti token.
- Kode saat ini membatasi 5 percobaan login per menit berdasarkan username dan IP.
- Redirect saat ini: admin ke `/`, petugas ke Pembayaran, bendahara dan wali ke Tagihan. UI wali sederhana dan tidak menampilkan ringkasan semua unit.
- Label username/NIS mode wali pada view saat ini berbeda dari PRD awal yang meminta label tetap Username; perubahan label tidak boleh mengubah kontrak field `username`.

## 5. Data dan Aturan Lintas Modul

### 5.1 Entitas

| Entitas | Tanggung Jawab dan Relasi |
| --- | --- |
| `AcademicYear`, `EducationUnit`, `SchoolClass` | Konteks tahun aktif, unit, kelas/tingkat |
| `Student` | Pendaftaran akademik; `identity_student_id` menghubungkan orang yang sama |
| `User`, `Role` | Akun, role, permission, keharusan reset password |
| `education_unit_user` | Penugasan satu pengguna ke satu/beberapa unit |
| `guardian_student` | Relasi wali-anak, hubungan, primary, waktu verifikasi |
| `FeeType`, `FeeDiscount` | Kategori/tarif, kelompok pembayaran, scope siswa/unit/kelas/tahun, keringanan |
| `Bill` | Kewajiban per siswa dan sumber/periode; total, terbayar, sisa, status |
| `BillPaymentAllocation`, `BillManualPayment` | Alokasi dan pembayaran yang dipakai service tagihan |
| `SppPayment`, `SppPaymentItem`, `SppPaymentCorrection` | Transaksi SPP, rincian periode, koreksi |
| `OtherPayment`, `OtherPaymentItem` | Daftar ulang, laundry, dan pembayaran lain |
| `GuardianTransferRequest` | Pengajuan transfer wali, bill IDs, nominal, bukti, status dan verifier |
| `BillSyncRun` | Status/progres sinkronisasi tagihan |
| `AppSetting`, `AuditLog` | Pengaturan aplikasi/rekening dan jejak operasi |

### 5.2 Ownership Siswa

Field personal bersama saat ini: NISN, nama, tempat/tanggal lahir, gender, nama ayah/ibu, WhatsApp ayah/ibu, provinsi, kota, kecamatan, desa, alamat, nama wali, dan WhatsApp wali. Saat anggota identitas diedit, sinkronkan field ini sesuai `studentPersonalFields()`.

Field per record/unit: NIS, kelas/unit, tahun pelajaran, tanggal masuk, tanggal mulai tagihan, status masuk, status aktif, tanggal keluar, dan alasan nonaktif. Pendaftaran dari identitas existing menyalin personal, bukan seluruh kondisi akademik.

**DATA-01:** gabung/pisah identitas hanya mengubah relasi identitas. `student_id` pada pembayaran, tagihan, dan riwayat tetap record asal. Nama sama tidak membuktikan orang yang sama. Alumni tetap baris `students` dengan `is_active=false`, bukan tabel arsip baru.

### 5.3 Keuangan

- **FIN-01:** hitung tarif/keringanan/alokasi di service server. Format rupiah tampilan tidak boleh menjadi sumber perhitungan atau mengubah nominal mentah.
- **FIN-02:** `Pending`/`Ditolak` tidak mengurangi sisa dan tidak dihitung sebagai penerimaan sah. Hanya status `Diterima` yang mengurangi kewajiban.
- **FIN-03:** nominal positif dan tidak melampaui sisa tagihan terpilih; validasi ulang pada submit, bukan hanya saat preview/quote.
- **FIN-04:** SPP dibayar berurutan dari tunggakan tertua; cicilan dialokasikan berurutan ke periode terpilih. Untuk MTs/MA, Juli masuk Daftar Ulang sesuai aturan service existing.
- **FIN-05:** `billing_start_date`, tanggal masuk, serta aturan tahun/periode mencegah tagihan sebelum awal kewajiban yang sah. Jangan menghasilkan tagihan lama hanya karena siswa diedit/diimpor.
- **FIN-06:** laundry adalah pembayaran opsional bulanan; tidak ikut pilih semua kewajiban dan tidak muncul sebagai tunggakan. Periode mengikuti penutupan bulan dan urutan bulan berjalan/berikutnya pada service existing.
- **FIN-07:** sinkronisasi menyesuaikan kewajiban yang relevan, tidak membuat transaksi pembayaran, menghilangkan pembayaran diterima, atau memindahkan ownership.
- **FIN-08:** pengajuan/keputusan transfer dan perubahan pembayaran harus konsisten ketika request diulang atau bersamaan; uji transaksi database, locking, validasi status, dan alokasi agar tidak terhitung dua kali.
- **FIN-09:** perubahan kelas, tahun, alumni, atau identitas tidak menghapus transaksi historis. Koreksi/batal mengikuti service dan jejak audit, bukan edit nominal langsung pada database.

### 5.4 Batas Modul

Pembayaran mencatat uang masuk dan mencetak struk. Tagihan menangani kewajiban belum selesai, surat, dan tindak lanjut pembayaran. Laporan menyajikan transaksi/rekap/arsip. Status belum bayar pada matriks tahunan boleh tampil sebagai konteks, tetapi bukan submenu penagihan baru.

## 6. Manajemen Siswa

Sumber: S02, S07-S09. ID kebutuhan: `STU-*`.

### 6.1 Data Siswa (`STU-01`)

Urutan: heading dan aksi, filter, toolbar jumlah data/pencarian, tabel, pagination. Aksi: Tambah Siswa, Import, Export, Template menurut permission; Jadikan Alumni hanya dalam konteks kelas lengkap. Import bukan submenu sendiri.

Filter mendukung unit, kelas, konteks tahun aktif/arsip, status, dan nama/NIS/NISN. Unit menentukan pilihan kelas; reset mengembalikan konteks aktif. Pertahankan filter, sort/direction, dan `per_page` saat pagination, export, edit, dan kembali dari form.

Kolom minimum: No, NIS, Nama, JK, Unit, Kelas, Aksi. NISN/tahun/status opsional sesuai pekerjaan. Nama rata kiri, JK L/P, kode unit resmi, aksi ikon. Empty state: `Belum ada data siswa pada filter ini.`

### 6.2 Tambah/Edit (`STU-02`)

Kelompok form: identitas, orang tua/wali, alamat, akademik, status. Nama, kelas, dan tahun wajib; NIS divalidasi dalam konteks unit; tanggal mulai tagihan opsional tetapi valid. Nonaktif wajib tanggal keluar dan alasan. Reaktivasi memvalidasi kembali kelas/tahun serta membersihkan atau menonaktifkan makna tanggal/alasan keluar.

Simpan siswa menjalankan sinkronisasi tagihan relevan. Data kelas/tahun kosong yang sudah terlanjur ada harus bisa ditinjau/diperbaiki tanpa membuat halaman gagal.

### 6.3 Import/Export (`STU-03`)

Alur wajib: download template bila perlu, upload Excel, preview, konfirmasi, simpan baris valid, hasil. Preview berisi total/valid/update/gagal, status Baru/Update/Gagal, dan alasan per baris. File sementara memakai token; token hilang/kedaluwarsa meminta upload ulang; file dibersihkan setelah selesai.

Template/export mencakup: No, NIS, NISN, Nama, Tempat Lahir, Tanggal Lahir, Jenis Kelamin, Nama Ayah, Nama Ibu, WhatsApp Ayah, WhatsApp Ibu, Provinsi, Kota/Kabupaten, Kecamatan, Desa/Kelurahan, Alamat, Unit Pendidikan, Kelas, Tanggal Masuk, Tanggal Mulai Tagihan, Status, Tanggal Keluar, Alasan Nonaktif.

- Header wajib hilang, unit tidak dikenal, data ambigu, atau tahun aktif tidak tersedia menghasilkan penolakan yang jelas.
- Kelas baru boleh dibuat otomatis hanya pada unit valid; preview/hasil menyebut kelas yang dibuat.
- Cell kosong pada baris update tidak boleh menghapus data lama tanpa aturan import eksplisit.
- Import memakai tahun aktif bagi siswa baru, menyinkronkan tagihan terdampak, dan melaporkan jumlah berhasil, update, gagal/dilewati, kelas baru, serta alasan.
- Export mengikuti filter dan urutan aktif, dengan nama `data-siswa-{scope}-{YYYY-MM-DD}.xlsx`; scope kode unit atau `semua`. Format kompatibel dengan template dan menjaga kode/NIS yang memiliki nol awal.

### 6.4 Rapikan Identitas (`STU-04`)

Kandidat: NISN sama atau nama+tanggal lahir sama = Kuat; nama+orang tua sama = Sedang; nama saja = Perlu cek; grup existing = Gabungan. Filter unit, kelas, tahun, search, jumlah data. Search juga dapat mencakup unit/kelas/tahun dan alasan kandidat.

Tinjau menampilkan NIS/NISN, nama, tanggal lahir, orang tua, unit, kelas, tahun, dan confidence. Gabung minimal dua record dengan root stabil; pisah membutuhkan root valid dan melepas relasi seluruh grup. Kandidat tidak boleh digabung otomatis hanya karena nama sama. Hasil menyatakan identitas berubah dan transaksi per unit tetap aman. Audit gabung/pisah wajib.

### 6.5 Pindah dan Naik Kelas (`STU-05`)

Filter tahun sumber, unit, kelas, search, jumlah data; pilih siswa, tentukan tujuan, tinjau konteks/jumlah, konfirmasi. Siswa harus aktif dan sesuai tahun sumber.

Pindah Kelas mengubah kelas dalam tahun yang sama. Naik Kelas mewajibkan kelas+tahun tujuan dan mengubah `intake_status` menjadi `Siswa Lama`. Tolak tujuan invalid, tujuan tidak sesuai scope unit, semua target sama dengan kondisi awal, atau nama duplikat dalam kelas tujuan. Pilihan campuran yang sudah berada di tujuan perlu hasil yang tidak ambigu; rekomendasi menolak kasus ambigu, bukan diam-diam memindahkan sebagian.

Setelah sukses, sinkronkan tagihan, pertahankan riwayat, dan laporkan jumlah siswa. Nama sama pada kelas berbeda tidak otomatis ditolak sebagai duplikasi global.

### 6.6 Alumni (`STU-06`)

Jadikan Alumni muncul ketika unit, kelas, tahun, dan status Aktif lengkap. Halaman konfirmasi menyebut kelas, tahun, jumlah siswa, tanggal keluar, dan alasan maksimal 120 karakter. Kelas harus sesuai unit; minimal satu siswa aktif. Hasil menjadikan semua siswa aktif pada konteks tersebut nonaktif dan kembali dengan filter nonaktif.

Data Alumni memuat No, NIS, Nama, JK, Unit, Tanggal Keluar, Alasan, Aksi. Filter/search mencakup unit, tahun, nama/NIS/NISN, alasan, serta konteks kelas bila tersedia. Edit membuka record yang sama dan dapat mengaktifkan kembali dengan `students.update`. Empty state alumni netral; tidak menghapus tagihan/transaksi lama. Alumni dengan sisa kewajiban masih dapat diproses pada pembayaran sesuai scope.

### 6.7 Audit (`STU-07`)

Catat create, update, reactivate, import, export, transfer, promotion, class alumni, merge, split: user/waktu/jenis aksi, jumlah dan ID siswa, before/after field penting, hasil. Audit tidak dapat diedit dari UI biasa; koreksi membuat catatan baru. Jangan menyertakan password atau rahasia akun.

### 6.8 Kualitas Data (`STU-08`, Gap)

Kebutuhan dari release P2-P3: indikator duplikat identitas, siswa aktif tanpa NISN, tanpa tanggal masuk, awal tagihan perlu dicek, dan alumni belum lengkap. Tiap indikator membuka daftar detail dengan filter dan pagination; view perlu `students.view`, edit perlu `students.update`, tinjau duplikat perlu `students.identity_cleanup`.

Indikator hanya membaca data dan tidak mengubah tagihan/pembayaran. Optimalkan query jika data besar; jangan mengganti halaman operasional dengan dashboard dekoratif. Klaim rilis 1 Agustus 2026 belum cocok dengan route saat ini; implementasi harus diverifikasi sebelum menu ditampilkan lagi.

## 7. Pembayaran

Sumber: S03, S06, V01 dan arahan pengguna terbaru. ID: `PAY-*`.

### 7.1 Layout dan Pencarian (`PAY-01`)

Halaman default langsung transaksi: Cari Siswa -> Pilih Tagihan -> Bayar -> Cetak Struk. Tidak ada landing page atau tab Transaksi Baru/Import/Riwayat. Import Excel merupakan aksi heading.

Saat belum memilih siswa, tampilkan pencarian ringkas dan empty state. Cari nama/NIS/NISN, kelompokkan pendaftaran lintas unit menjadi satu identitas. Boleh auto-select bila tepat satu identitas. Setelah dipilih, profil memuat NIS/unit/kelas gabungan, nama, dan status; pencarian tidak mendominasi workspace.

Layout terpilih mengikuti `Setelah.jpeg`:

```text
Heading dan aksi Import Excel
Pencarian / konteks siswa terpilih
Total Tagihan Dipilih | Nominal Dibayar | Sisa Setelah Bayar
Kasir Pembayaran     | Daftar Tagihan Siswa
Riwayat Terbaru      | Daftar Tagihan Siswa (lanjutan)
```

Kode override saat ini menggunakan kolom kasir 320-430px, tagihan fleksibel; <=1180px satu kolom: siswa, statistik, kasir, tagihan, riwayat; <=760px statistik juga bertumpuk. Gambar mengatur tata letak, bukan palet baru. Semantik statistik adalah transaksi yang sedang diisi, bukan jumlah seluruh pembayaran historis.

### 7.2 Pemilihan Tagihan (`PAY-02`)

Daftar dibagi Tagihan Wajib dan Pembayaran Opsional. Wajib yang masih bersisa default terpilih; pengguna dapat mengurangi pilihan. Laundry/opsional default tidak terpilih dan tidak ikut pilih semua wajib.

Checkbox dan pilihan periode berada pada daftar tagihan. SPP/laundry memakai label `Bayar sampai`; item tanpa periode menampilkan detail singkat. Nominal item dan total berubah saat pilihan/periode berubah. Profil/daftar lintas unit tetap memperhatikan scope pengguna.

### 7.3 Form Kasir (`PAY-03`)

Isi: total terpilih, tipe Lunas/Cicil, Nominal Dibayar, metode, bukti bila Transfer, tombol `Bayar & Cetak Struk`. Lunas mengisi total dan readonly; Cicil dapat diubah dari 1 sampai total terpilih. Jangan gunakan istilah Titip untuk cicilan.

Metode UI Tunai/Transfer Bank dipetakan ke `Cash`/`Transfer`. Petugas hanya Cash; pembatasan juga wajib pada request server. Transfer baru mewajibkan bukti JPG/JPEG/PNG/PDF maksimal 2 MB dan rekening tujuan. Transfer kasir/admin dan pengajuan transfer wali memiliki alur berbeda; jangan membuat semua transfer otomatis Pending bila service admin memang menerima langsung.

Tombol tidak aktif saat pilihan kosong/total nol/nominal invalid, dan menjaga ukuran saat loading. Sebelum penyimpanan keuangan, konfirmasi siswa, periode, nominal, serta metode. Submit ulang tidak boleh menggandakan pembayaran.

Statistik dihitung dari pilihan dan nominal saat ini; sisa/progres konsisten, tidak negatif atau lebih dari 100%. Keputusan nominal sah tetap pada server. Pertahankan fungsi rupiah/quote/alokasi existing.

### 7.4 Hasil dan Riwayat (`PAY-04`)

Setelah sukses, tampilkan siswa, total, metode, Cetak Struk, Download PDF, Bayar Lagi. Buka struk transaksi/gabungan sesuai implementasi; sediakan tombol manual bila popup diblokir. Tetap pada siswa yang sama dan refresh sisa serta riwayat.

Riwayat menggabungkan SPP/Other, maksimal 5-10 item; kode/test saat ini memakai lima terbaru pada alur terpilih. Filter bulan boleh tersedia, default berjalan. Cetak/PDF dan edit/hapus hanya sesuai izin. Riwayat panjang tetap pada laporan/riwayat, bukan workspace kasir.

### 7.5 Import Pembayaran (`PAY-05`)

Jenis: SPP, Daftar Ulang, Laundry, Lain-lain. Pilih konteks/file, Preview Data, validasi valid/gagal/duplikat, lalu konfirmasi jika ada baris valid. SPP mewajibkan unit, bulan, tahun dari form sebagai konteks utama; kolom Excel yang berbeda harus ditolak. Jika kolom unit/bulan/tahun tidak ada, gunakan konteks form.

Kolom minimal SPP: NIS, Nama, Cara bayar, Waktu, Nominal; Petugas opsional. Jangan menambah izin import bendahara berdasarkan sebutan generik pada PRD lama. Kode saat ini memproteksi import dengan `payments.verify_transfer`.

Empty/error penting: siswa tidak ditemukan, belum ada siswa dipilih, tidak ada tagihan aktif/wajib, opsional tersedia ketika wajib lunas, belum ada riwayat, belum memilih tagihan, nominal kosong/melebihi total, bukti belum diunggah, scope unit ditolak, atau tagihan sudah lunas. Error dekat konteks; empty bukan error.

## 8. Tagihan

Sumber: S04 dan S06. ID: `BILL-*`.

### 8.1 Dataset dan Scope (`BILL-01`)

Sumber utama `bills`: `remaining_amount > 0`, bukan `Dibatalkan`. SPP memakai `source_type=spp`; daftar ulang dari `fee_type` dengan `payment_group=daftar-ulang`; lain-lain dari manual/kategori relevan. Laundry dikecualikan selama opsional.

Tahun aktif menjadi konteks operasional; periode internal sampai bulan berjalan boleh dipakai sinkron, tetapi bukan filter utama tambahan. Sinkron admin/internal bukan aksi kasir harian. Pembayaran diterima memperbarui sisa sehingga siswa lunas hilang dari daftar kewajiban.

Admin melihat semua; bendahara unit yang ditugaskan; wali anak terhubung tanpa ringkasan semua unit. Detail/print/download/URL langsung harus mengikuti scope yang sama.

### 8.2 Halaman Kerja (`BILL-02`)

Urutan: heading, tabel rekap unit tanpa heading duplikat, filter, toolbar, tabel siswa, pagination, footer. Tidak memakai kartu angka besar, progress per unit, atau strip total tambahan.

- Rekap: No, Unit Pendidikan (nama tanpa kode), Siswa, Jumlah Tagihan. Tetap tampil saat kosong; `tfoot` Total Keseluruhan dengan `colspan` tepat.
- Filter utama: Unit Pendidikan, Kelas, Terapkan, Reset. Tanpa Tahun Tagihan/Sampai Bulan/Status/Kategori.
- Toolbar: jumlah data di kiri, Cari Siswa di kanan (nama/NIS/NISN). Pencarian digabung dengan filter aktif.
- Tabel: No, NIS, Nama Siswa, Unit, Kelas, Total Tagihan, Aksi. Sort NIS/Nama/Unit/Kelas/Total; No/Aksi tidak sortable. Pertahankan query saat sort/pagination.
- Detail membuka surat resmi. Bayar mengarah ke Pembayaran dengan siswa terpilih hanya bila berizin; wali menggunakan alur transfer; bendahara monitoring tidak diberi aksi input.
- Pagination bila total > `per_page`, informasi `Menampilkan ... dari ... siswa` di bawah, tanpa teks Inggris/duplikasi `Halaman x dari y`.

### 8.3 Surat (`BILL-03`)

Halaman resmi terpisah, bukan modal: judul Penertiban Administrasi Keuangan, NIS/nama/unit/kelas, rincian No/Uraian/Tahun/Rp./Jml Bulan/Jumlah, total, terbilang, tanggal, tanda tangan. Rincian mencakup SPP per rentang, daftar ulang/lain-lain tersisa, periode, nominal, jumlah bulan, total item.

Kertas setengah F4 portrait 165 x 215mm. Toolbar Kembali, Bayar bila berizin, Unduh, Cetak; tersembunyi saat print. Empty state: `Belum ada tagihan pada filter ini.` atau `Tidak ada tagihan aktif untuk siswa ini.`

## 9. Portal Wali dan Verifikasi

Sumber: S01; implementasi `GuardianPortalController`/`TransferVerificationController`. ID: `TRF-*`.

1. Wali masuk dengan unit/NIS/password dan memilih anak bila lebih dari satu.
2. Sistem menampilkan hanya kewajiban identitas yang terhubung, termasuk unit tambahan yang sah.
3. Wali memilih bill, melihat nominal serta rekening, lalu mengunggah bukti.
4. Pengajuan disimpan `Pending`/Menunggu Verifikasi; tidak ada alokasi pembayaran sah atau pengurangan sisa.
5. Super Admin memeriksa bukti dan menerima/menolak; hanya status yang masih dapat diproses yang boleh berubah.
6. Terima membuat/mengakui alokasi sesuai service, mengurangi sisa, menyimpan `verified_by`/`verified_at`, dan memungkinkan struk.
7. Tolak memerlukan alasan (`rejected_reason`), menjaga tagihan belum lunas, dan memberi status/alasan kepada wali.

Pengajuan saat ini menggunakan tabel `guardian_transfer_requests`, bukan sekadar menambah status ke tabel transaksi. Bukti disajikan melalui endpoint berizin. Verifikasi serentak/ulang tidak boleh menggandakan alokasi atau memproses nominal lebih dari sisa terbaru.

Petugas tidak dapat mengunggah/memverifikasi transfer. Bendahara boleh memonitor Pending dalam unit sebagai kebutuhan baca, tetapi tidak menerima/menolak; jangan membuka route verifikasi hanya untuk memberikan monitoring. Wali tidak dapat mengubah tarif/nominal kewajiban, menghapus pembayaran, atau memilih Cash; Cash ditangani petugas kantor.

## 10. Laporan

Sumber: S05. ID: `RPT-*`. Semua laporan mengikuti filter, scope user, tabel compact, dan export XLSX/PDF sesuai permission. Label agregat penerimaan: `Jumlah Penerimaan`.

### 10.1 Transaksi Pembayaran (`RPT-01`)

Filter tanggal dari/sampai (default hari ini), unit, kelas, petugas; Cari Siswa di toolbar. Kategori/metode hanya filter lanjutan bila diperlukan. Pencarian siswa ikut memfilter tabel dan rekap.

Rekap unit: No, Unit Pendidikan, Jumlah Transaksi, Cash, Transfer, Jumlah Penerimaan; satu footer total seluruh hasil filter. Detail: No, Tanggal, NIS, Nama Siswa, Unit, Kelas, Jenis Pembayaran, Cara Bayar, Petugas, Nominal. Memuat SPP, daftar ulang, laundry, lainnya yang merupakan transaksi sah. Tidak mengulang angka rekap sebagai kartu besar.

XLSX berisi detail dan ringkasan; PDF memuat periode, ringkasan unit, serta transaksi. Aksi detail/cetak opsional dan berizin; tidak menjadikan laporan sebagai form penagihan.

### 10.2 SPP Perbulan (`RPT-02`)

Filter Tahun kalender, Bulan, Unit, Kelas; Cari Siswa di toolbar; tidak ada filter Status Pembayaran. Hanya siswa/periode dengan pembayaran terjadi. Belum membayar ditindaklanjuti di Tagihan.

Rekap: No, Unit Pendidikan, Jumlah Siswa, Lunas, Sebagian, Jumlah Penerimaan. Detail final: No, Tanggal, NIS, Nama Siswa, Unit, Kelas, Bulan, Tahun, Nominal, Cara Bayar, Petugas. Tidak menambah Tagihan SPP atau Total Sisa sebagai kolom utama.

Sisa nol = Lunas; terbayar > 0 dan sisa > 0 = Sebagian; terbayar nol tidak masuk. Tanggal transaksi berbeda dari periode tagihan dan harus konsisten di layar/export. Sumber status dari `bills` SPP (`year`, `month`, `paid_amount`, `remaining_amount`, `status`); tanggal/metode/petugas dari rincian pembayaran. Empty: `Belum ada pembayaran SPP pada periode ini.`

### 10.3 SPP Pertahun (`RPT-03`)

Filter Tahun Pelajaran, Unit, Kelas; Cari Siswa di toolbar. Tahun pelajaran menentukan rentang Juli-Juni, bukan pembatas `students.academic_year_id`. Data pembayaran lama tetap ditemukan walau siswa pindah tahun aktif; relasi unit/kelas mengikuti kondisi siswa saat ini. Siswa nonaktif tidak tampil menurut PRD laporan ini.

Tabel: No, NIS, Nama Siswa, Unit, Kelas, Juli, Agustus, September, Oktober, November, Desember, Januari, Februari, Maret, April, Mei, Juni. Tanpa kartu atau tabel rekap tambahan.

Isi bulan: Lunas = tanggal pembayaran; cicil = Sebagian + tanggal terakhir bila ada; belum membayar = Belum Bayar; Tidak Ditagih hanya jika tidak ada kewajiban pada bulan itu. Sumber bill dan item SPP. Scroll hanya wrapper tabel; PDF A4 landscape, XLSX matriks bulanan.

### 10.4 Rekap Per Unit (`RPT-04`)

Filter tanggal dari/sampai, default hari ini; tidak ada Cari Siswa. Tabel No, Unit Pendidikan, SPP, Daftar Ulang, Laundry, Lain-lain, Jumlah Penerimaan. Footer Total Keseluruhan selalu tersedia dan menjumlahkan seluruh unit hasil filter. Tanpa kartu angka pengulangan.

### 10.5 Export dan Empty (`RPT-05`)

Route halaman diikuti `/export/xlsx` atau `/export/pdf`; `/laporan` dan route export legacy boleh menjadi pengarah kompatibilitas. XLSX adalah format utama, bukan CSV; nominal sebagai angka, kode identitas sebagai teks, header terbaca, sheet utama dan ringkasan bila didukung. Pakai helper existing sebelum menambah library.

PDF memakai identitas lembaga, judul, filter/periode, tanggal cetak, ringkasan dan tabel; A4 portrait kecuali tabel lebar, SPP Pertahun wajib landscape. Print putih-hitam dan toolbar disembunyikan.

Export mencakup seluruh hasil filter, bukan hanya halaman pagination, tetap dalam scope akses. Data kosong menghasilkan header/keterangan kosong; tahun aktif belum diatur menghasilkan keadaan terjelaskan, bukan exception mentah.

## 11. Dashboard, Master, dan Pengaturan

Bagian ini melengkapi PRD dari S06 dan pembacaan kode; belum ada PRD modul terpisah untuk ketiga area.

- **Dashboard:** ringkasan penerimaan hari/bulan, tren, siswa aktif, tagihan/sisa/jatuh tempo, persentase pelunasan, aktivitas terbaru, rekap unit. Statistik dan chart ringan; tidak menggantikan laporan. Kebutuhan Dashboard Unit harus membatasi query dan cache berdasarkan akses (gap bagian 3).
- **Tahun Pelajaran:** konteks aktif operasional serta periode arsip/laporan; validasi perubahan konteks dengan data terikat.
- **Unit/Kelas:** unit pendidikan, kelas/tingkat, tahun dan status; pilihan kelas mengikuti unit. Penonaktifan tidak menghapus riwayat siswa/pembayaran.
- **Kategori Pembayaran:** tarif/scope unit, kelas/tingkat, tahun, kelompok pembayaran, scope siswa dan `creates_bill`; gunakan `FeeType`/`ChargeCalculator`. Filter unit+nama/kode; aktif/nonaktif tampil bersama, status dikelola di form.
- **Keringanan Biaya:** relasi siswa dan pembayaran/tarif sesuai `FeeDiscount`. Filter unit+kelas+nama siswa; status bukan filter utama. Perubahan tarif/keringanan memperhatikan tagihan dan transaksi existing.
- **Data User/Role:** `users.manage`, role aktif/permission, penugasan unit dan wali-anak. Jangan membuka hak penuh karena nama role terlihat seperti admin.
- **Pengaturan:** nama, username, email, perubahan password dengan password saat ini dan konfirmasi, konfigurasi bank/rekening/nama pemilik. Kebutuhan: bedakan hak mengubah akun sendiri dari konfigurasi global. `SettingController` saat ini menyimpan keduanya pada endpoint yang sama; jangan menganggap pembatasan rekening per role sudah diaudit.

## 12. Kontrak Teknis dan Nonfungsional

### 12.1 Peta Kode

| Area | Entry Point dan Implementasi |
| --- | --- |
| Route umum/login/laporan/pengaturan | `routes/web.php`, `bootstrap/app.php` |
| Akses | `app/Http/Middleware/EnsureRolePermission.php`, `app/Models/User.php`, `app/Models/Role.php` |
| Siswa/master | `routes/student_management.php`, `routes/master.php`, `MasterDataController`, `StudentIdentityCleanupController`, `StudentImportService` |
| View siswa | `resources/views/master/index.blade.php`, `master/partials/*`, `student-management/*` |
| Pembayaran | `routes/finance.php`, `PaymentController`, `SppPaymentController`, `OtherPaymentController`, `resources/views/finance/payments.blade.php` |
| Perhitungan | `SppPaymentService`, `OtherPaymentService`, `LaundryPaymentService`, `ChargeCalculator` |
| Import pembayaran | `SppPaymentImportService`, `OtherPaymentImportService`, Form Request terkait |
| Tagihan | `BillController`, `BillService`, `BillQueryService`, `OutstandingBillService`, `resources/views/finance/bill*.blade.php` |
| Transfer wali | `GuardianPortalController`, `TransferVerificationController`, `resources/views/guardian/bills.blade.php`, `finance/transfer-verifications.blade.php` |
| Laporan | `ReportController`, `ReportQueryService`, `resources/views/reports/*`, `reports/pdf/*`, `SimpleXlsxWriter` |
| Dashboard/pengaturan | `DashboardController`, `SettingController`, `welcome.blade.php`, `settings/index.blade.php` |
| Audit/cache | `AuditLogService`, `PerformanceCache`, `AppServiceProvider`, `config/performance.php` |
| Frontend | `resources/css/app.css`, `resources/css/modules/*`, `resources/js/app.js`, `vite.config.js` |

Nama controller berada di `app/Http/Controllers`, service di `app/Services`, helper di `app/Support`. Gunakan route name dari file aktual; jangan mengarang nama dari URL. Rekomendasi memecah `payments.blade.php` menjadi partial masih refactor terpisah; path partial dalam PRD lama bukan bukti file sudah tersedia.

### 12.2 Keamanan dan Konsistensi

- Validasi server untuk input, scope unit/anak, nominal, status, upload, serta izin setiap aksi; CSRF tetap aktif di aplikasi.
- Escape output pengguna; jangan menampilkan detail exception/database di UI operasional.
- URL langsung, AJAX quote/search, struk, bukti, export, dan sinkron harus memakai aturan akses yang setara.
- Perubahan keuangan/massal harus atomik sesuai service dan mencatat hasil/audit; validasi ulang setelah preview bila data berubah.
- Jangan hardcode kredensial, menampilkan `.env`, atau mengisi dokumentasi dengan identitas siswa asli.
- Cache data terfilter wajib memasukkan konteks akses/filter yang relevan dan diinvalidasi setelah perubahan yang memengaruhi hasil.

### 12.3 Performa dan UI

Dataset besar memakai pagination/aggregate database, bukan mengambil semua record lalu paginate Collection. Export dapat membaca semua hasil filter dengan strategi yang sesuai ukurannya. Hindari N+1 pada relasi siswa, unit, kelas, pembayaran, dan alokasi.

UI berbahasa Indonesia, app-shell konsisten, tanpa landing page, tanpa kartu dekoratif berulang untuk data tabel. Semua kondisi loading, empty, error, sukses, readonly, disabled, pending/rejected harus jelas. Keyboard/focus, label input, tooltip aksi ikon, responsif dan print mengikuti design system. Tidak ada perubahan rumus bisnis karena redesain.

### 12.4 Isolasi Pengujian

`phpunit.xml` memaksa `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL` kosong, dan cache konfigurasi test terpisah. `tests/TestCase.php` menghentikan proses sebelum `RefreshDatabase` bila environment/koneksi tidak sesuai.

Jangan menghapus guard, memakai database operasional untuk test, menjalankan `migrate:fresh`/`db:wipe`, atau men-seed database pengguna untuk membuat tes lulus. Cache konfigurasi lokal dapat mengalahkan environment test bila tidak dipisahkan. Backup operasional dan file import/export bukan pengganti isolasi test.

## 13. Kriteria Penerimaan

Checklist berikut menyatukan skenario sumber; pilih test yang terkait perubahan. Pernyataan di sini bukan hasil test terbaru.

| ID | Skenario yang Harus Lulus | Lokasi Verifikasi |
| --- | --- | --- |
| AUTH-01 | Guest diarahkan login; username valid berhasil; password salah/email sebagai username ditolak; logout POST/CSRF | `AuthTest` |
| AUTH-02 | Rate limit, login wali unit/NIS/password, akun wajib reset, tidak auto-create wali | `AuthTest` |
| ACL-01 | Permission aksi siswa dibedakan; role tidak bisa bypass lewat URL; route tak terpetakan ditolak | `AuthTest`, `MasterDataTest`, `BillAccessTest` |
| ACL-02 | Bendahara hanya unitnya, wali hanya anaknya, kasir hanya Cash; print/proof/export/search ikut scope | `BillAccessTest`, `PaymentMenuTest`, `ReportMenuTest` dan audit endpoint |
| STU-01 | Default tahun aktif, unit-kelas dependen, nama/NIS/NISN, sort/per_page/pagination/query kembali konsisten | `MasterDataTest` |
| STU-02 | Create/edit valid, personal lintas identitas benar, akademik/NIS per record, nonaktif dan reaktivasi valid, tagihan sinkron | `MasterDataTest` |
| STU-03 | Header invalid, tanpa tahun aktif, preview hilang, update sel kosong, unit tidak valid, kelas otomatis, hasil serta cleanup file | `MasterDataTest` |
| STU-04 | Confidence benar, tinjau record benar, minimal dua anggota, root stabil, split melepas grup; `student_id` keuangan tetap | `MasterDataTest` |
| STU-05 | Pindah/promosi menolak nonaktif/tahun sumber salah/target sama/duplikat; promosi mengubah intake, tahun dan kelas | `MasterDataTest` |
| STU-06 | Alumni butuh konteks lengkap, kelas tak kosong, tanggal/alasan; jumlah benar; arsip dan reaktivasi tidak menghapus riwayat | `MasterDataTest` |
| STU-07/08 | Audit before/after dan pelaku benar; indikator kualitas read-only, detail terfilter/paginated sesuai izin | Kebutuhan tambahan; Kualitas Data belum route aktif |
| PAY-01/02 | Search/auto-select identitas, profile lintas unit, wajib/opsional default tepat, periode dan total sinkron | `PaymentMenuTest` + interaksi browser |
| PAY-03 | Lunas readonly, cicil positif <= total, bukti wajib/ukuran/tipe, request Transfer kasir ditolak, stale/double submit aman | `PaymentMenuTest` |
| PAY-04/05 | SPP/Other/Laundry sesuai service, struk tersedia, lima terbaru, import konteks form dan preview tetap benar | `PaymentMenuTest` |
| BILL-01 | Lunas/Dibatalkan/laundry tidak masuk; ringkasan dan tabel filter/search/scope sama | `BillAccessTest`, test pembayaran/tagihan relevan |
| BILL-02/03 | Sort dan pagination mempertahankan konteks, Detail surat, Bayar hanya berizin, rincian/terbilang/print sesuai | Feature terkait + QA print |
| TRF-01 | Pengajuan Pending tidak mengurangi sisa; hanya admin menerima/menolak, alasan tolak tersimpan, verifikasi ulang tidak menggandakan | `BillAccessTest`/test transfer terkait |
| RPT-01 | Detail semua jenis, filter tanggal/unit/kelas/petugas/siswa dan aggregate konsisten | `ReportMenuTest` |
| RPT-02 | Kolom bulanan final, tahun kalender, tanpa filter status, hanya Lunas/Sebagian, periode beda tanggal tetap benar | `ReportMenuTest` |
| RPT-03 | Juli-Juni, tanggal lunas/terakhir cicil, Tidak Ditagih tepat, tahun siswa kini tidak menyembunyikan pembayaran lama | `ReportMenuTest` |
| RPT-04/05 | Rekap kategori dan footer benar; export XLSX/PDF sesuai seluruh filter/scope, termasuk hasil kosong | `ReportMenuTest` |
| UI-01 | Desktop/mobile tanpa overlap, scroll hanya wrapper, focus/label jelas, modal tidak muncul sebelum dibuka | QA visual sesuai design system |
| DATA-01/FIN-* | Tidak ada kehilangan transaksi akibat identitas/akademik; status dan alokasi konsisten lintas layar/struk/export | Test regresi service/modul terdampak |

Nama suite mengacu file di `tests/Feature`; pemetaan menunjukkan tempat memeriksa/menambah cakupan, bukan menjamin setiap skenario sudah di-test. Untuk keuangan/akses, uji jalur ditolak dan kasus lintas unit selain happy path.

## 14. Prioritas, Risiko, dan Rilis

### 14.1 Urutan Kerja

- **P0:** isolasi test dan integritas data; permission/scope; input siswa, preview import, operasi kelas/alumni; tagihan dan pembayaran sah, konsistensi nominal, struk.
- **P1:** rapikan identitas dan alumni, export/filter, audit, portal wali/verifikasi, empat laporan. Urutan laporan: Transaksi, SPP Perbulan, SPP Pertahun, Rekap Unit.
- **P2:** penyempurnaan granular permission, monitoring kualitas data, UX/responsif, pemecahan view/JS bila kompleksitas membutuhkan.
- **P3/maintenance:** detail indikator Kualitas Data dan pagination sesuai rencana rilis historis; cleanup CSS bertahap dan optimasi query. Penamaan P0-P3 pada release lama hanya berlaku untuk rilis Manajemen Siswa tersebut.

Urutan Multi Login: label/permission, relasi unit, scope unit, verifikasi admin, relasi wali, portal wali, redirect, test akses. Urutan Pembayaran: layout/pilihan tagihan/form, riwayat dan feedback/mobile, lalu refactor partial/JS. Urutan Tagihan: konsistensi aturan, layout/rekap/tabel, filter/pagination, surat, CSS scope, query/akses, QA.

### 14.2 Risiko Utama

| Risiko | Penanganan |
| --- | --- |
| Cache test memakai database operasional | Isolasi konfigurasi, SQLite memory, guard sebelum migrasi; tidak mematikan proteksi |
| Import ambigu atau kelas salah | Preview, alasan baris, konteks unit/tahun, kelas baru disebut jelas |
| Identitas salah digabung atau transaksi pindah | Tinjau personal/konteks, root stabil, hanya ubah relasi identitas, sediakan split |
| Operasi kelas/alumni salah sasaran | Validasi filter/sumber/tujuan, konfirmasi jumlah, transaksi dan audit |
| Tagihan lama muncul/berubah tidak sah | Awal tagihan dan periode divalidasi; regressi service, riwayat diterima dipertahankan |
| Scope bocor lewat laporan/cache/proof | Scope sebelum query/aggregate/export; cek cache key dan URL langsung |
| Pending dihitung atau pembayaran ganda | Aturan status/alokasi, lock/transaction, pengujian request ulang |
| CSS lintas menu berubah karena cleanup | Batch per pemilik selector, audit cascade, build dan screenshot menu terdampak |
| Dokumen historis dikira implementasi aktif | Daftar gap, cek route/kode, catat tanggal bukti verifikasi |

### 14.3 Rilis dan Definition of Done

Sebelum rilis: review perubahan relevan/worktree, target environment dan database, backup database target, catat role/permission, pastikan tidak ada operasi massal berjalan saat perubahan skema. Test dilakukan di environment terisolasi; build jika frontend berubah. Migration rilis sesuai versi kode, bukan mengeksekusi semua petunjuk historis tanpa pemeriksaan.

Sumber rilis siswa menyebut migration `2026_08_01_000000_create_audit_logs_table` dan `2026_08_01_010000_add_student_granular_permissions_to_roles`. Status Ran serta angka 68 tests/971 assertions pada release note adalah hasil 1 Agustus 2026, bukan hasil pemeriksaan dokumentasi ini. Demikian pula hash commit lama adalah petunjuk sejarah, bukan target deploy otomatis.

Sesudah rilis: smoke login, sidebar/permission, siswa/alumni/identitas/kelas, preview import, export, pembayaran/tagihan dan struk. Uji kualitas data hanya jika route tersebut benar-benar disediakan. Desktop sekitar 1366px, mobile 390-430px; cek juga 360px/768px untuk batas sempit dan tablet.

Selesai ketika aturan produk dan validasi server terpenuhi, data historis aman, scope benar, UI/print sesuai konteks, tes relevan dan QA dicatat, serta gap yang tersisa disebutkan. Build/tes lama tidak boleh dipakai sebagai bukti pekerjaan baru.

Rollback harus terarah ke perubahan rilis yang bermasalah. Jangan rollback migration berisi data/audit/permission tanpa rencana pemulihan database. Cleanup CSS adalah batch terpisah; kegagalannya tidak membatalkan seluruh rilis Manajemen Siswa.
