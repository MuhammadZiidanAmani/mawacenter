# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# APLIKASI REKAP TAGIHAN DAN TUNGGAKAN SISWA

## Versi

1.0

## Nama Produk

Sistem Rekap Tagihan Siswa

---

# 1. LATAR BELAKANG

Sekolah telah memiliki data pembayaran siswa yang sudah melakukan pembayaran SPP, daftar ulang, dan pembayaran lainnya.

Permasalahan saat ini:

* Sulit mengetahui siswa yang belum melakukan pembayaran.
* Rekap tunggakan masih dilakukan secara manual.
* Membutuhkan waktu lama untuk membuat laporan piutang.
* Sulit mengetahui total tagihan yang belum dibayar berdasarkan kelas atau jenjang.

Aplikasi ini dibuat untuk mengolah data pembayaran yang sudah ada dan menghasilkan laporan tunggakan secara otomatis.

---

# 2. TUJUAN APLIKASI

### Tujuan Utama

Membandingkan data siswa, data tagihan, dan data pembayaran untuk menghasilkan informasi:

* Siswa yang belum membayar SPP.
* Siswa yang belum membayar daftar ulang.
* Siswa yang belum membayar tagihan lainnya.
* Total piutang sekolah.
* Rekap tunggakan per kelas.
* Rekap tunggakan per jenjang.
* Rekap tunggakan per siswa.

---

# 3. HAK AKSES

## Super Admin

Hak akses penuh.

Fitur:

* Kelola pengguna
* Kelola role
* Kelola tahun pelajaran
* Kelola data siswa
* Kelola jenis tagihan
* Import data
* Melihat seluruh laporan

---

## Admin Keuangan

Fitur:

* Import pembayaran
* Import siswa
* Kelola tagihan
* Rekap tunggakan
* Cetak laporan
* Kirim notifikasi WA

---

## Kepala Sekolah

Fitur:

* Dashboard
* Statistik pembayaran
* Statistik tunggakan
* Laporan

---

# 4. MASTER DATA

## Tahun Pelajaran

Field:

* ID
* Tahun Pelajaran
* Status Aktif

Contoh:

2025/2026

---

## Data Kelas

Field:

* ID
* Nama Kelas
* Jenjang

Contoh:

* VII A
* VII B
* VIII A

---

## Data Siswa

Field:

* ID
* NIS
* NISN
* Nama Siswa
* Jenis Kelamin
* Kelas
* Tahun Pelajaran
* Nama Wali
* Nomor WhatsApp
* Status Aktif

---

## Jenis Tagihan

Field:

* ID
* Kode
* Nama Tagihan
* Nominal
* Periode

Periode:

* Bulanan
* Tahunan
* Sekali Bayar

Contoh:

* SPP
* Daftar Ulang
* Uang Kegiatan
* Seragam
* Buku
* Ujian
* Lainnya

---

# 5. MODUL IMPORT DATA

## Import Data Siswa

Format Excel:

| NIS | Nama | Kelas | No WA |
| --- | ---- | ----- | ----- |

Fitur:

* Preview Data
* Validasi Duplikat
* Import Massal

---

## Import Data Pembayaran

Format Excel:

| Tanggal | NIS | Jenis Pembayaran | Periode | Nominal |
| ------- | --- | ---------------- | ------- | ------- |

Fitur:

* Preview Import
* Validasi Data
* Import Massal

---

# 6. MODUL GENERATE TAGIHAN

## Generate Tagihan SPP

Sistem otomatis membuat tagihan bulanan.

Contoh:

SPP Januari
SPP Februari
SPP Maret

dst.

---

## Generate Tagihan Tahunan

Untuk:

* Daftar Ulang
* Kegiatan
* Buku
* Seragam

Sistem dapat membuat tagihan massal berdasarkan kelas atau seluruh siswa.

---

# 7. MODUL REKONSILIASI OTOMATIS

Fitur utama aplikasi.

Sistem akan:

1. Membaca seluruh siswa aktif.
2. Membaca seluruh tagihan.
3. Membaca seluruh pembayaran.
4. Membandingkan tagihan dan pembayaran.
5. Menghasilkan status pembayaran.

Status:

* Lunas
* Belum Lunas
* Menunggak

Proses berjalan otomatis setiap:

* Import data pembayaran
* Generate tagihan
* Perubahan data siswa

---

# 8. DASHBOARD

## Ringkasan

Card:

* Total Siswa
* Total Tagihan
* Total Pembayaran
* Total Tunggakan
* Total Piutang

---

## Statistik

Grafik:

### Pembayaran

* Harian
* Mingguan
* Bulanan
* Tahunan

### Tunggakan

* Per Kelas
* Per Jenjang
* Per Jenis Tagihan

---

# 9. MODUL REKAP TUNGGAKAN

## Rekap Tunggakan SPP

Filter:

* Tahun Pelajaran
* Kelas
* Bulan

Output:

| Nama  | Kelas | Jan | Feb | Mar |
| ----- | ----- | --- | --- | --- |
| Ahmad | VII A | ✓   | ✓   | X   |
| Ali   | VII A | X   | X   | ✓   |

Keterangan:

✓ = Sudah Bayar

X = Belum Bayar

---

## Rekap Daftar Ulang

Output:

| Nama  | Kelas | Status      |
| ----- | ----- | ----------- |
| Ahmad | VII A | Lunas       |
| Ali   | VII A | Belum Bayar |

---

## Rekap Tagihan Lainnya

Menampilkan:

* Uang Kegiatan
* Buku
* Seragam
* Ujian
* Lainnya

---

## Rekap Per Siswa

Menampilkan seluruh riwayat:

* Tagihan
* Pembayaran
* Tunggakan

---

## Rekap Piutang

Menampilkan:

* Total Piutang SPP
* Total Piutang Daftar Ulang
* Total Piutang Lainnya

Grand Total Piutang Sekolah.

---

# 10. MODUL NOTIFIKASI WHATSAPP

## Kirim Tagihan Individu

Pesan otomatis:

Nama siswa

Jenis tagihan yang belum dibayar

Nominal

Total tunggakan

---

## Kirim Tagihan Massal

Filter:

* Kelas
* Jenjang
* Jenis Tagihan

Kirim otomatis ke seluruh wali yang memiliki tunggakan.

---

# 11. MODUL LAPORAN

## Laporan Pembayaran

Filter:

* Harian
* Bulanan
* Tahunan

Output:

* PDF
* Excel

---

## Laporan Tunggakan

Filter:

* Kelas
* Jenjang
* Tahun Pelajaran

Output:

* PDF
* Excel

---

## Laporan Piutang

Output:

* Total Piutang
* Detail Piutang Per Siswa

---

# 12. AUDIT LOG

Mencatat seluruh aktivitas:

* Login
* Import Data
* Tambah Data
* Edit Data
* Hapus Data

Field:

* User
* Aktivitas
* Waktu
* IP Address

---

# 13. NON-FUNCTIONAL REQUIREMENTS

## Performance

* Minimal 5.000 siswa
* Minimal 500.000 transaksi pembayaran

---

## Security

* Laravel Spatie Permission
* Password Hashing
* Activity Log
* CSRF Protection

---

# 14. TEKNOLOGI

## Backend

* Laravel 12
* PHP 8.4

## Database

* MariaDB 11+

## Frontend

* Blade
* Tailwind CSS
* Alpine.js

## Integrasi

* WhatsApp Gateway
* Laravel Excel
* DomPDF

---

# 15. STRUKTUR MENU

Dashboard

Master Data
├── Tahun Pelajaran
├── Kelas
├── Data Siswa
├── Jenis Tagihan

Import Data
├── Import Siswa
├── Import Pembayaran

Tagihan
├── Generate SPP
├── Generate Tagihan Tahunan
├── Daftar Tagihan

Rekap
├── Tunggakan SPP
├── Tunggakan Daftar Ulang
├── Tunggakan Lainnya
├── Piutang Sekolah
├── Rekap Per Siswa

Laporan
├── Pembayaran
├── Tunggakan
├── Piutang

Notifikasi
├── WhatsApp Individu
├── WhatsApp Massal

Pengaturan
├── Role
├── User
├── Audit Log
