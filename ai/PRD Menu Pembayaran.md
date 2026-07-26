# PRD Menu Pembayaran MA'WA CENTER

## Ringkasan

Menu Pembayaran adalah halaman kerja utama untuk kasir/admin saat mencatat transaksi pembayaran siswa. Halaman ini harus terasa langsung, ringan, dan mudah dipahami: pengguna cukup mencari siswa, memilih tagihan, menentukan nominal/metode bayar, lalu mencetak struk.

PRD ini merapikan arah menu Pembayaran yang sudah ada tanpa mengubah fondasi bisnis utama. Fokus perubahan adalah memperjelas alur, menyederhanakan tampilan, dan memindahkan kontrol pilihan tagihan ke tempat yang paling natural, yaitu Daftar Tagihan.

Semua desain wajib mengikuti [standar aplikasi.md](standar aplikasi.md), terutama standar halaman transaksi, daftar tagihan, form 40px, tombol primer hijau, warna netral, typography 14/16/20px, dan responsif mobile.

## Tujuan Produk

- Kasir dapat mencatat pembayaran siswa dengan alur singkat dan jelas.
- Kasir dapat langsung melihat data siswa, tagihan wajib, pembayaran opsional, total bayar, dan riwayat terbaru.
- Pengguna dapat memilih tagihan langsung dari daftar tagihan.
- Pengguna dapat membayar penuh atau cicil tanpa istilah yang membingungkan.
- Struk dapat dibuka/dicetak setelah transaksi berhasil.
- Import Excel tetap tersedia, tetapi tidak mengganggu alur kasir harian.
- Halaman terasa profesional, tidak berat, tidak terlalu banyak pilihan teknis, dan mudah dipindai.

## Non-Tujuan

- Tidak membuat landing page atau halaman pengantar.
- Tidak membuat tab "Transaksi Baru" karena halaman default sudah langsung untuk transaksi baru.
- Tidak menampilkan seluruh riwayat panjang di halaman bayar.
- Tidak menggabungkan laporan transaksi lengkap ke halaman pembayaran.
- Tidak mengubah rumus bisnis SPP, daftar ulang, laundry, lain-lain, keringanan, atau sinkronisasi tagihan kecuali dibutuhkan oleh UI baru.
- Tidak membuat ulang halaman laporan; riwayat lengkap tetap masuk area laporan/riwayat yang sudah ada.

## Pengguna Utama

- **Petugas Kasir:** mencatat pembayaran tunai harian dan mencetak struk.
- **Admin/Bendahara:** mencatat cash/transfer, mengecek tagihan siswa, menghapus/memperbaiki transaksi jika diperlukan sesuai izin.
- **Operator Import:** mengunggah pembayaran massal dari Excel.

## Prinsip UX

1. Alur utama selalu: **Cari Siswa -> Pilih Tagihan -> Bayar -> Cetak Struk**.
2. Pencarian adalah langkah awal, bukan fokus visual terbesar setelah siswa dipilih.
3. Daftar Tagihan adalah tempat memilih tagihan.
4. Ringkasan Pembayaran hanya berisi konfirmasi dan input pembayaran.
5. Riwayat terbaru tampil sebagai konteks, bukan pusat halaman.
6. Import Excel tersedia sebagai aksi kanan di heading, bukan tab utama.
7. Bahasa UI harus konsisten dan operasional.

## Struktur Menu Final

Sidebar tetap satu menu:

```text
Pembayaran
```

Halaman Pembayaran memiliki aksi kanan:

```text
Import Excel
```

Tidak ada tab:

```text
Transaksi Baru
Riwayat Pembayaran
Import
```

Alasannya, halaman default Pembayaran sudah langsung berfungsi sebagai transaksi baru. Riwayat terbaru cukup tampil setelah siswa dipilih, sedangkan riwayat lengkap diarahkan ke menu laporan/riwayat yang sudah tersedia.

## Route

Route utama yang dipertahankan:

```text
GET  /keuangan/pembayaran
POST /keuangan/pembayaran
GET  /keuangan/pembayaran/import
GET  /keuangan/pembayaran/receipt
```

Route pendukung tetap dipakai:

```text
/keuangan/pembayaran/spp/*
/keuangan/pembayaran/lain-lain/*
```

Catatan:

- `/keuangan/pembayaran` menjadi halaman kasir utama.
- `/keuangan/pembayaran/import` menjadi halaman import Excel.
- `/keuangan/pembayaran/riwayat` boleh tetap ada untuk kompatibilitas, tetapi tidak menjadi tab utama di halaman pembayaran.

## Layout Desktop

Gambaran layout:

```text
+--------------------------------------------------------------+
| Pembayaran                                      [Import Excel]|
| Cari siswa, pilih tagihan, lalu proses pembayaran.            |
+--------------------------------------------------------------+

+----------------------+---------------------------------------+
| Cari Siswa           | Data Siswa                            |
| [Nama / NIS / NISN]  | NIS    : 260001                       |
|                      | Nama   : AHMAD FAUZAN                 |
| Hasil pencarian      | Unit   : MTs / PONPES                 |
| Ahmad Fauzan         | Kelas  : VII A / Asrama               |
+----------------------+---------------------------------------+

+--------------------------------------------------------------+
| Panel Pembayaran                                             |
| +------------------------------+---------------------------+ |
| | Daftar Tagihan               | Ringkasan Pembayaran      | |
| |                              |                           | |
| | Tagihan Wajib                | Total Tagihan             | |
| | [x] SPP MTs                  | Rp 800.000                | |
| |     Bayar sampai [Agt 2026]  |                           | |
| |     Rp 300.000               | Tipe Pembayaran           | |
| |                              | [Lunas] [Cicil]           | |
| | [x] Daftar Ulang             |                           | |
| |     Rp 500.000               | Nominal Dibayar           | |
| |                              | Rp 800.000                | |
| | Pembayaran Opsional          |                           | |
| | [ ] Laundry                  | Metode Bayar              | |
| |     Bayar sampai [Agt 2026]  | [Tunai]                   | |
| |     Rp 100.000               |                           | |
| |                              | [Bayar & Cetak Struk]     | |
| +------------------------------+---------------------------+ |
+--------------------------------------------------------------+

+--------------------------------------------------------------+
| Riwayat Terbaru Siswa                                        |
| SPP MTs - Juli - Agustus 2026        Rp 600.000   [Struk]    |
| Laundry - Juli 2026                  Rp 100.000   [Struk]    |
+--------------------------------------------------------------+
```

## Layout Mobile

Urutan mobile:

```text
Pembayaran
[Import Excel]

Cari Siswa
[Nama / NIS / NISN]
Hasil pencarian

Data Siswa

Daftar Tagihan

Ringkasan Pembayaran

Riwayat Terbaru Siswa
```

Aturan mobile:

- Semua panel menjadi satu kolom.
- Tidak boleh ada overlap teks, nominal, atau tombol.
- Daftar tagihan boleh tetap memakai row/list, bukan tabel.
- Nominal harus tetap rata kanan atau turun ke baris sendiri dengan rapi.
- Tombol `Bayar & Cetak Struk` full width.

## Halaman Kosong Sebelum Cari Siswa

Jika belum ada pencarian:

```text
+----------------------+---------------------------------------+
| Cari Siswa           | Belum Ada Siswa Dipilih               |
| [Nama / NIS / NISN]  | Pilih siswa dari hasil pencarian      |
|                      | untuk melihat tagihan pembayaran.     |
+----------------------+---------------------------------------+
```

Jika siswa tidak ditemukan:

```text
Siswa tidak ditemukan
Periksa kembali nama, NIS, atau NISN yang dicari.
```

Jika siswa dipilih tetapi tidak ada tagihan:

```text
Tidak ada tagihan aktif untuk siswa ini.
```

## Pencarian Siswa

### Tujuan

Memilih siswa secepat mungkin tanpa membuat input pencarian terlalu dominan.

### Perilaku

- Input mencari berdasarkan nama, NIS, atau NISN.
- Jika hasil hanya satu identitas siswa, sistem boleh auto-select seperti alur saat ini.
- Jika siswa punya beberapa unit/registrasi, tampilkan dalam satu identitas siswa.
- Hasil pencarian tampil di panel kiri.
- Setelah siswa dipilih, fokus utama berpindah ke data siswa dan pembayaran.

### Tampilan

- Desktop: panel kiri sekitar 32 sampai 38 persen lebar workspace.
- Input pencarian tidak perlu sepanjang halaman.
- Hasil pencarian berupa list ringkas:

```text
Ahmad Fauzan
260001 - MTs VII A / PONPES Asrama
```

## Profil Siswa

Profil siswa tampil setelah siswa dipilih.

Data yang ditampilkan:

- NIS
- Nama Siswa
- Unit Pendidikan
- Kelas
- Status

Aturan:

- Jika siswa punya beberapa unit, gabungkan NIS/unit/kelas secara ringkas.
- Nama siswa boleh uppercase untuk konsistensi halaman lama.
- Jangan tampilkan informasi yang tidak diperlukan untuk transaksi harian.

## Daftar Tagihan

### Tujuan

Menjadi area utama untuk memilih tagihan yang akan dibayar.

### Struktur

Daftar tagihan dibagi dua:

1. **Tagihan Wajib**
2. **Pembayaran Opsional**

### Tagihan Wajib

Contoh:

```text
[x] SPP MTs
    Bayar sampai [Agustus 2026]
    Rp 300.000

[x] Daftar Ulang
    Tahun Pelajaran 2026/2027
    Rp 500.000
```

Aturan:

- Tagihan wajib default tercentang jika ada sisa tagihan.
- Pengguna boleh memilih semua atau sebagian tagihan wajib.
- Jika SPP punya pilihan periode, label utama adalah **Bayar sampai**, bukan "jumlah bulan".
- Jika tagihan tidak punya pilihan periode, tampilkan detail singkat.
- Nominal item berubah saat periode diubah.
- Total tagihan berubah real-time saat checkbox/periode berubah.

### Pembayaran Opsional

Contoh:

```text
[ ] Laundry
    Bayar sampai [Agustus 2026]
    Rp 100.000
```

Aturan:

- Pembayaran opsional default tidak tercentang.
- Laundry dan pembayaran opsional lain tidak ikut otomatis saat memilih semua tagihan wajib.
- Opsional hanya masuk total jika dicentang.
- Jika laundry punya pilihan periode, gunakan label **Bayar sampai**.

### Empty State Daftar Tagihan

Jika tidak ada tagihan wajib:

```text
Tidak ada tagihan wajib untuk siswa ini.
```

Jika hanya ada opsional:

```text
Tagihan wajib sudah lunas. Pembayaran opsional tersedia jika diperlukan.
```

## Ringkasan Pembayaran

### Tujuan

Menjadi area konfirmasi sebelum transaksi disimpan.

### Isi

- Total Tagihan
- Tipe Pembayaran
- Nominal Dibayar
- Metode Bayar
- Bukti Transfer jika metode Transfer
- Tombol Bayar & Cetak Struk

### Tipe Pembayaran

Gunakan bahasa:

```text
Lunas
Cicil
```

Aturan:

- **Lunas:** nominal otomatis sama dengan total tagihan dan field nominal readonly.
- **Cicil:** nominal bisa diedit, minimal 1, maksimal total tagihan terpilih.
- Jangan gunakan istilah "Titip" untuk pembayaran sebagian karena kurang jelas secara akuntansi.

### Metode Bayar

Pilihan:

```text
Tunai
Transfer Bank
```

Mapping data tetap:

```text
Cash
Transfer
```

Aturan:

- Petugas yang hanya boleh cash tidak melihat opsi Transfer.
- Jika Transfer dipilih, tampilkan rekening tujuan dan upload bukti transfer.
- Bukti transfer wajib untuk transaksi Transfer baru.
- File bukti transfer: JPG, JPEG, PNG, PDF, maksimal 2 MB.

### Tombol Utama

Label:

```text
Bayar & Cetak Struk
```

Aturan:

- Tombol disabled jika tidak ada tagihan dipilih atau total 0.
- Setelah submit sukses, sistem membuka struk.
- Jika browser memblokir popup, tampilkan modal sukses dengan tombol `Cetak Struk` dan `Download PDF`.

## Setelah Pembayaran Berhasil

Tampilkan feedback sukses.

Contoh:

```text
Pembayaran Berhasil
Ahmad Fauzan
Total dibayar: Rp 800.000
Metode: Tunai

[Cetak Struk] [Download PDF] [Bayar Lagi]
```

Aturan:

- Untuk satu transaksi, buka struk transaksi tersebut.
- Untuk beberapa transaksi, buka struk masing-masing atau sediakan daftar struk.
- Halaman kembali ke siswa yang sama agar kasir bisa melihat riwayat terbaru.

## Riwayat Terbaru Siswa

### Tujuan

Memberi konteks cepat tanpa mengubah halaman pembayaran menjadi halaman laporan.

### Isi

- Maksimal 5 sampai 10 transaksi terbaru pada periode/bulan yang dipilih.
- Tampilkan SPP dan pembayaran lain dalam satu list.
- Tampilkan tombol Struk dan Download PDF.
- Aksi hapus/edit boleh tetap ada sesuai izin, tetapi tidak terlalu menonjol.

Contoh:

```text
SPP MTs - Juli - Agustus 2026
25/07/2026 10.15 WIB - Tunai
Rp 600.000
[Struk] [PDF]
```

### Filter

- Filter bulan riwayat boleh tetap ada.
- Default bulan berjalan.
- Riwayat lengkap diarahkan ke menu laporan/riwayat.

## Import Excel

### Tujuan

Mencatat pembayaran massal dari file Excel.

### Akses

- Tampil sebagai tombol kanan heading: `Import Excel`.
- Boleh dibatasi hanya untuk role tertentu.

### Jenis Import

- SPP
- Daftar Ulang
- Laundry
- Lain-lain

### Form Import

Field:

- Jenis Pembayaran
- Unit Pendidikan, Bulan, Tahun untuk SPP
- File Excel

Aturan:

- Untuk SPP, Unit Pendidikan, Bulan, dan Tahun pada form menjadi konteks utama.
- Kolom Excel yang berbeda dari konteks form harus ditolak.
- Tombol utama: `Preview Data`.
- Preview menampilkan jumlah valid, gagal, dan duplikat.
- Import hanya aktif jika ada data valid.

## Bahasa UI

Gunakan label final berikut:

```text
Pembayaran
Import Excel
Cari Siswa
Data Siswa
Daftar Tagihan
Tagihan Wajib
Pembayaran Opsional
Bayar sampai
Ringkasan Pembayaran
Total Tagihan
Tipe Pembayaran
Lunas
Cicil
Nominal Dibayar
Metode Bayar
Tunai
Transfer Bank
Bukti Transfer
Bayar & Cetak Struk
Riwayat Terbaru Siswa
Cetak Struk
Download PDF
```

Hindari label UI berikut:

```text
Titip
Other
Lainnya
Transaksi Baru
Hub
Jumlah Bulan
```

Catatan:

- `Lain-lain` tetap boleh dipakai sebagai kategori pembayaran resmi.
- `Jumlah Bulan` boleh ada di kode atau data, tetapi UI kasir lebih baik memakai `Bayar sampai`.

## Aturan Bisnis yang Dipertahankan

### SPP

- Pembayaran SPP harus berurutan dari tunggakan tertua.
- Untuk MTs/MA, SPP Juli termasuk Daftar Ulang sesuai aturan yang sudah ada.
- SPP dapat dibayar sampai bulan tertentu.
- Cicilan dialokasikan berurutan ke periode yang dipilih.
- Pembayaran tidak boleh melebihi sisa tagihan.

### Daftar Ulang dan Lain-lain

- Nominal dihitung dari `FeeType` dan keringanan.
- Transaksi `Pending` tidak mengurangi sisa tagihan.
- Transaksi `Diterima` mengurangi sisa tagihan.
- Pembayaran tidak boleh melebihi sisa tagihan.

### Laundry

- Laundry mengikuti alur bulanan.
- Tidak boleh membayar bulan sebelumnya jika sudah ditutup sesuai aturan saat ini.
- Bisa bayar bulan berjalan atau bulan berikutnya secara berurutan.

### Transfer

- Bukti transfer wajib diunggah untuk metode Transfer.
- Petugas yang dibatasi cash hanya dapat memilih Tunai.

## Struktur Teknis yang Disarankan

Controller utama tetap:

```text
app/Http/Controllers/PaymentController.php
```

Service yang tetap dipakai:

```text
app/Services/SppPaymentService.php
app/Services/OtherPaymentService.php
app/Services/LaundryPaymentService.php
app/Services/BillService.php
app/Services/ChargeCalculator.php
```

View utama saat ini:

```text
resources/views/finance/payments.blade.php
```

Direkomendasikan dipecah menjadi partial:

```text
resources/views/finance/payments/index.blade.php
resources/views/finance/payments/_search.blade.php
resources/views/finance/payments/_student-profile.blade.php
resources/views/finance/payments/_bill-list.blade.php
resources/views/finance/payments/_payment-summary.blade.php
resources/views/finance/payments/_recent-history.blade.php
resources/views/finance/payments/_import.blade.php
resources/views/finance/payments/_import-preview.blade.php
```

Catatan:

- Pemecahan view tidak harus mengubah route.
- Pemecahan dilakukan agar file lebih mudah dirawat.
- Logic PHP kompleks di Blade sebaiknya dipindahkan bertahap ke controller/helper kecil jika diperlukan.

JavaScript yang tetap dipakai dan dirapikan:

```text
resources/js/app.js
```

Fungsi JS yang dibutuhkan:

- Update total saat checkbox/periode berubah.
- Toggle Lunas/Cicil.
- Format nominal rupiah.
- Toggle field Transfer dan bukti transfer.
- Submit guard agar nominal tidak melebihi total.
- Auto-open struk setelah sukses.
- Sync import form dan preview.

## Data dan Model

Model utama:

```text
Student
FeeType
SppPayment
SppPaymentItem
OtherPayment
OtherPaymentItem
Bill
BillPaymentAllocation
AppSetting
```

Data yang harus tampil di UI:

- Siswa: NIS, nama, unit, kelas, status.
- Tagihan: jenis, periode, nominal, sisa.
- Pembayaran: total, nominal dibayar, metode, bukti transfer.
- Riwayat: jenis pembayaran, periode, tanggal, metode, nominal, struk.

## Permission

Aturan minimal:

- User harus punya izin `payments.cash.create` untuk mencatat pembayaran dari menu ini.
- Petugas hanya boleh membuat transaksi Tunai jika role membatasi cash.
- Transfer hanya tampil untuk user yang diizinkan.
- Import Excel dapat dibatasi ke admin/bendahara jika permission tersedia.
- Edit/hapus transaksi mengikuti permission yang sudah ada atau yang akan dipertegas.

## Standar Visual

Ikuti standar aplikasi:

- Background halaman: #ffffff.
- Panel: #ffffff dengan border #d1d5db.
- Panel lembut/empty state: #fbfdf8.
- Teks utama: #020617.
- Teks sekunder: #404942 atau #707971.
- Hijau utama: #004528.
- Hover hijau: #0d5f36.
- Field tinggi 40px, radius 8px.
- Tombol tinggi 40px, radius 8px.
- Font hanya 14px, 16px, dan 20px.
- Judul halaman 20px / 700.
- Judul card 16px / 700.
- Item tagihan 14px sampai 16px.
- Total penting 20px / 700 atau 16px / 700 jika ruang sempit.

## Responsif

Breakpoint acuan:

- 1180px: layout dua kolom besar.
- 900px: panel pembayaran dapat turun.
- 760px: semua panel menjadi satu kolom.

Aturan:

- Tidak ada teks yang overlap.
- Input pencarian full width di mobile.
- Daftar tagihan turun satu kolom.
- Ringkasan pembayaran full width.
- Tombol Bayar & Cetak Struk full width.
- Riwayat terbaru tetap readable tanpa tabel lebar.

## Empty State dan Error

Empty state wajib singkat:

- `Cari siswa terlebih dahulu.`
- `Siswa tidak ditemukan.`
- `Belum ada siswa yang dipilih.`
- `Tidak ada tagihan aktif untuk siswa ini.`
- `Belum ada riwayat pembayaran pada periode ini.`

Error validasi wajib dekat dengan konteks:

- Tagihan belum dipilih.
- Nominal kosong.
- Nominal melebihi total.
- Bukti transfer belum diunggah.
- Siswa tidak memiliki akses unit.
- Tagihan sudah lunas.

## Testing

Tambahkan atau sesuaikan test feature untuk:

- Halaman Pembayaran menampilkan search dan tombol Import Excel.
- Search siswa menampilkan hasil dan auto-select jika satu identitas.
- Siswa lintas unit digabung dalam profil.
- Daftar Tagihan menampilkan checkbox pada tagihan wajib dan opsional.
- SPP menampilkan pilihan `Bayar sampai`.
- Laundry opsional default tidak tercentang.
- Total berubah sesuai pilihan tagihan dan periode.
- Tipe Lunas mengisi nominal sama dengan total.
- Tipe Cicil mengizinkan nominal sebagian.
- Transfer tanpa bukti transfer ditolak.
- Petugas cash-only tidak melihat opsi Transfer.
- Submit pembayaran membuat transaksi SPP/Other/Laundry sesuai pilihan.
- Riwayat terbaru menampilkan transaksi siswa terpilih.
- Import Excel tetap mengarah ke preview import yang sudah ada.

## Urutan Implementasi yang Disarankan

1. Rapikan copy dan struktur heading halaman Pembayaran.
2. Ubah layout search menjadi panel kiri dan area siswa/pembayaran di kanan.
3. Pindahkan pilihan tagihan dari Ringkasan Pembayaran ke Daftar Tagihan.
4. Ubah label periode menjadi `Bayar sampai`.
5. Rapikan Ringkasan Pembayaran agar hanya berisi total, Lunas/Cicil, nominal, metode, bukti transfer, dan tombol final.
6. Pindahkan riwayat menjadi panel `Riwayat Terbaru Siswa` yang lebih ringan.
7. Pastikan Import Excel tetap tersedia sebagai tombol heading.
8. Pecah Blade menjadi partial agar mudah dirawat.
9. Sesuaikan JS untuk checkbox/periode di Daftar Tagihan.
10. Tambahkan/ubah test feature.
11. Jalankan test dan build asset.

## Prioritas

Prioritas 1:

- Layout utama.
- Daftar Tagihan bisa dipilih.
- Ringkasan Pembayaran sederhana.
- Label Lunas/Cicil.

Prioritas 2:

- Riwayat terbaru lebih ringkas.
- Empty state dan sukses modal.
- Responsif mobile.

Prioritas 3:

- Pecah view menjadi partial.
- Rapikan JS menjadi fungsi kecil.
- Permission import/edit/hapus yang lebih tegas jika dibutuhkan.

## Kriteria Selesai

Menu Pembayaran dianggap selesai jika:

- Halaman default langsung dapat dipakai untuk cari siswa dan bayar.
- Tidak ada tab `Transaksi Baru`.
- Tombol `Import Excel` tersedia di heading.
- Input pencarian tidak memenuhi seluruh lebar desktop.
- Profil siswa jelas setelah siswa dipilih.
- Daftar Tagihan memiliki checkbox dan pilihan `Bayar sampai`.
- Ringkasan Pembayaran hanya berisi konfirmasi pembayaran.
- Tipe pembayaran memakai `Lunas` dan `Cicil`.
- Total dan nominal dibayar selalu sinkron dengan pilihan tagihan.
- Transfer mewajibkan bukti transfer.
- Petugas cash-only tetap aman.
- Setelah sukses, struk dapat dicetak/download.
- Riwayat terbaru siswa tampil tanpa mengganggu flow bayar.
- Mobile rapi dan tidak melebar.
- Test feature utama lulus.
