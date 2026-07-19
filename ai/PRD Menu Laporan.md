# PRD Menu Laporan MA'WA CENTER

## Ringkasan

Menu Laporan menjadi pusat kontrol keuangan untuk admin dan bendahara. Halaman ini harus membantu pengguna melihat transaksi masuk, status SPP bulanan, tunggakan SPP, rekap SPP satu tahun pelajaran, dan rekap penerimaan per unit pendidikan.

PRD ini menggantikan arah lama yang memisahkan "Transaksi Harian" dan "Riwayat Pembayaran". Keduanya digabung menjadi satu halaman **Semua Transaksi** agar menu lebih ringkas dan tidak dobel fungsi.

Semua halaman laporan wajib mengikuti [standar aplikasi.md](standar aplikasi.md), terutama standar spacing, card filter, tabel compact 40px, tombol, warna, dan responsif mobile.

## Tujuan Produk

- Admin dapat melihat total penerimaan dan detail transaksi dalam periode tertentu.
- Bendahara dapat melihat status SPP per bulan: sudah bayar, sebagian, atau belum bayar.
- Admin dapat melihat daftar tunggakan SPP per unit, kelas, dan siswa.
- Pimpinan dapat melihat rekap SPP per tahun pelajaran dan rekap penerimaan per unit.
- Semua laporan dapat diekspor dalam format **XLSX** dan **PDF**.
- Laporan terasa operasional, cepat dipindai, dan tidak memakai card-card data berulang untuk data utama.

## Non-Tujuan

- Tidak membuat dashboard grafik besar di tahap awal.
- Tidak membuat laporan akuntansi lengkap seperti neraca, jurnal, atau buku besar.
- Tidak memakai CSV sebagai export utama.
- Tidak menampilkan ulang rincian tagihan panjang di tabel utama jika sudah tersedia halaman detail/tagihan.

## Pengguna Utama

- **Admin:** mengelola semua laporan lintas unit.
- **Bendahara:** mengecek transaksi, SPP bulanan, dan tunggakan.
- **Pimpinan/Yayasan:** membaca ringkasan penerimaan dan tunggakan per unit.

## Struktur Menu Final

Sidebar Laporan:

1. Semua Transaksi
2. SPP Perbulan
3. SPP Belum Bayar
4. SPP Tahun Pelajaran
5. Rekap Per Unit

Menu **Riwayat Pembayaran** tidak dibuat submenu terpisah karena fungsinya masuk ke **Semua Transaksi**.

## Halaman 1: Semua Transaksi

### Tujuan

Menggabungkan kebutuhan transaksi harian dan riwayat pembayaran. Halaman ini menjawab:

- Hari ini/periode ini masuk berapa?
- Unit tertentu menerima pembayaran berapa?
- Siswa tertentu pernah membayar apa saja?
- Transaksi mana yang statusnya diterima atau pending?
- Metode pembayaran cash/transfer berapa totalnya?

### Filter

- Tanggal Dari
- Tanggal Sampai
- Unit Pendidikan
- Kelas
- Kategori Pembayaran
- Cara Bayar
- Status
- Cari Siswa

### Ringkasan

Ringkasan utama:

- Total Penerimaan
- Jumlah Transaksi
- Jumlah Siswa
- Total SPP
- Total Daftar Ulang
- Total Laundry
- Total Lain-lain

Ringkasan per unit:

- No
- Unit Pendidikan
- Jumlah Transaksi
- Total Penerimaan

### Tabel Detail

Kolom tabel:

- No
- Tanggal
- NIS
- Nama Siswa
- Unit
- Kelas
- Kategori
- Cara Bayar
- Status
- Nominal

### Export

- XLSX: data detail transaksi dan ringkasan per unit.
- PDF: ringkasan periode, ringkasan per unit, dan tabel transaksi.

## Halaman 2: SPP Perbulan

### Tujuan

Melihat status pembayaran SPP pada bulan tertentu. Halaman ini paling sering dipakai untuk kontrol bulanan bendahara.

### Filter

- Tahun Pelajaran
- Bulan
- Unit Pendidikan
- Kelas
- Status SPP
- Cari Siswa

Status SPP:

- Semua
- Sudah Bayar
- Sebagian
- Belum Bayar

### Ringkasan Per Unit

Kolom ringkasan:

- No
- Unit Pendidikan
- Jumlah Siswa
- Sudah Bayar
- Sebagian
- Belum Bayar
- Total Terbayar
- Total Tunggakan

### Tabel Detail

Kolom tabel:

- No
- NIS
- Nama Siswa
- Unit
- Kelas
- Bulan
- Tagihan SPP
- Terbayar
- Sisa
- Status

### Sumber Data

Utama dari tabel `bills`:

- `source_type = spp`
- `year`
- `month`
- `total_amount`
- `paid_amount`
- `remaining_amount`
- `status`

### Export

- XLSX: status SPP semua siswa pada bulan terpilih.
- PDF: daftar kontrol SPP bulanan untuk cetak dan arsip.

## Halaman 3: SPP Belum Bayar

### Tujuan

Melihat semua tunggakan SPP lintas bulan untuk kebutuhan penagihan.

### Filter

- Tahun Pelajaran
- Unit Pendidikan
- Kelas
- Sampai Bulan
- Cari Siswa

### Ringkasan Per Unit

Kolom ringkasan:

- No
- Unit Pendidikan
- Siswa Menunggak
- Jumlah Bulan Tunggakan
- Total Tunggakan

### Tabel Detail

Kolom tabel:

- No
- NIS
- Nama Siswa
- Unit
- Kelas
- Bulan Tunggakan
- Total Tunggakan
- Aksi

### Aksi

- Detail Tagihan: membuka halaman surat tagihan siswa.
- Bayar: menuju menu Pembayaran dengan siswa terpilih.

### Sumber Data

Utama dari tabel `bills`:

- `source_type = spp`
- `remaining_amount > 0`
- periode sampai bulan yang dipilih

### Export

- XLSX: daftar tunggakan SPP.
- PDF: daftar penagihan SPP per unit/kelas.

## Halaman 4: SPP Tahun Pelajaran

### Tujuan

Melihat rekap SPP satu tahun pelajaran, dari Juli sampai Juni. Halaman ini untuk evaluasi tahunan dan laporan pimpinan.

### Filter

- Tahun Pelajaran
- Unit Pendidikan
- Kelas
- Cari Siswa

### Ringkasan

- Total Tagihan SPP
- Total Terbayar
- Total Tunggakan
- Jumlah Siswa

### Tabel Detail

Kolom tabel:

- No
- NIS
- Nama Siswa
- Unit
- Kelas
- Jul
- Agu
- Sep
- Okt
- Nov
- Des
- Jan
- Feb
- Mar
- Apr
- Mei
- Jun
- Total Terbayar
- Sisa

Status bulan:

- Lunas
- Sebagian
- Belum Bayar
- Tidak Ditagih

### Export

- XLSX: rekap SPP siswa per bulan.
- PDF: landscape A4 karena jumlah kolom bulan banyak.

## Halaman 5: Rekap Per Unit

### Tujuan

Memberi ringkasan besar untuk admin/pimpinan: penerimaan dan tunggakan per unit pendidikan.

### Filter

- Tanggal Dari
- Tanggal Sampai
- Tahun Pelajaran

### Tabel

Kolom tabel:

- No
- Unit Pendidikan
- SPP
- Daftar Ulang
- Laundry
- Lain-lain
- Total Penerimaan
- Total Tunggakan SPP

Footer:

- Total Keseluruhan

### Export

- XLSX: rekap angka per unit.
- PDF: laporan ringkas resmi untuk pimpinan/yayasan.

## Standar UI Semua Halaman Laporan

Semua halaman laporan mengikuti pola:

1. Heading
2. Deskripsi
3. Card Filter
4. Ringkasan per unit atau ringkasan utama
5. Toolbar jumlah data
6. Tabel
7. Export XLSX/PDF

Aturan visual:

- Container maksimal 1200px.
- Jarak topbar ke judul: 24px desktop dan 16px mobile.
- Judul: 20px / 700.
- Deskripsi: 14px / 400.
- Jarak judul ke deskripsi: 4px.
- Jarak deskripsi ke card filter: 16px.
- Card filter: background #ffffff, border #d1d5db, radius 12px, padding 16px, gap 12px.
- Field filter: tinggi 40px, radius 8px, border #d1d5db.
- Tombol Terapkan: background #004528, border #004528, teks #ffffff.
- Tombol Reset: background #ffffff, border #d1d5db, teks #404942.
- Data utama memakai tabel ke bawah, bukan card-card.
- Header dan isi tabel compact tinggi 40px.
- Header tabel Title Case, bukan kapital semua kecuali singkatan resmi seperti NIS dan SPP.
- Baris terakhir tabel tidak memakai garis bawah penutup.
- Toolbar "Tampilkan 10 data" mengikuti standar Data Siswa.
- Mobile: filter satu kolom, tombol aksi dua kolom atau full width, tabel scroll hanya di `.table-wrap`.

## Standar Export

### Format

Semua laporan memiliki:

- Export XLSX
- Export PDF

CSV tidak menjadi format utama.

### XLSX

Kebutuhan:

- File mudah dibuka di Excel.
- Header kolom rapi.
- Nominal berupa angka, bukan teks jika memungkinkan.
- Sheet pertama berisi data utama.
- Jika perlu, sheet kedua berisi ringkasan.

Catatan teknis:

- Project saat ini belum memiliki package Laravel Excel di `composer.json`.
- Saat implementasi XLSX, tambahkan dependency `maatwebsite/excel` atau solusi setara berbasis PhpSpreadsheet.

### PDF

Kebutuhan:

- Format siap cetak.
- Memakai kop/identitas laporan MA'WA CENTER.
- Menampilkan periode/filter laporan.
- Menampilkan tanggal cetak.
- Menampilkan ringkasan dan tabel utama.
- Untuk SPP Tahun Pelajaran gunakan A4 landscape.
- Untuk laporan lain gunakan A4 portrait kecuali tabel terlalu lebar.

Catatan teknis:

- Project sudah memiliki `dompdf/dompdf`.
- PDF dibuat dengan Blade view khusus per laporan.

## Struktur Route

Route halaman:

```text
/laporan/transaksi
/laporan/spp-perbulan
/laporan/spp-belum-bayar
/laporan/spp-tahun-pelajaran
/laporan/rekap-unit
```

Route export:

```text
/laporan/transaksi/export/xlsx
/laporan/transaksi/export/pdf
/laporan/spp-perbulan/export/xlsx
/laporan/spp-perbulan/export/pdf
/laporan/spp-belum-bayar/export/xlsx
/laporan/spp-belum-bayar/export/pdf
/laporan/spp-tahun-pelajaran/export/xlsx
/laporan/spp-tahun-pelajaran/export/pdf
/laporan/rekap-unit/export/xlsx
/laporan/rekap-unit/export/pdf
```

## Struktur Teknis

Controller:

```text
app/Http/Controllers/ReportController.php
```

Service:

```text
app/Services/ReportQueryService.php
```

Method service:

```text
transactions()
transactionSummary()
transactionUnitSummary()
monthlySpp()
monthlySppSummary()
outstandingSpp()
outstandingSppSummary()
yearlySpp()
yearlySppSummary()
unitRecap()
```

View halaman:

```text
resources/views/reports/transactions.blade.php
resources/views/reports/monthly-spp.blade.php
resources/views/reports/outstanding-spp.blade.php
resources/views/reports/yearly-spp.blade.php
resources/views/reports/unit-recap.blade.php
```

View PDF:

```text
resources/views/reports/pdf/transactions.blade.php
resources/views/reports/pdf/monthly-spp.blade.php
resources/views/reports/pdf/outstanding-spp.blade.php
resources/views/reports/pdf/yearly-spp.blade.php
resources/views/reports/pdf/unit-recap.blade.php
```

Export XLSX:

```text
app/Exports/Reports/TransactionReportExport.php
app/Exports/Reports/MonthlySppReportExport.php
app/Exports/Reports/OutstandingSppReportExport.php
app/Exports/Reports/YearlySppReportExport.php
app/Exports/Reports/UnitRecapReportExport.php
```

## Prinsip Query dan Performa

- Jangan mengambil semua data lalu paginate di Collection untuk dataset besar.
- Query laporan harus memakai pagination database jika memungkinkan.
- Ringkasan per unit dihitung dengan aggregate query.
- Filter Unit dan Kelas harus memakai relasi siswa/kelas/unit.
- Laporan SPP memakai tabel `bills` agar status tagihan, terbayar, dan sisa konsisten dengan menu Tagihan.
- Export boleh mengambil seluruh data sesuai filter, tetapi tetap harus dibatasi oleh filter yang jelas.

## Validasi dan Empty State

- Jika tidak ada data, tampilkan empty state singkat: "Belum ada data pada filter ini."
- Jika Tahun Pelajaran belum aktif, halaman tetap terbuka tetapi menampilkan keterangan "Tahun Pelajaran Aktif belum diatur."
- Jika export tidak menemukan data, tetap hasilkan file dengan header dan keterangan kosong, bukan error mentah.

## Testing

Tambahkan test feature untuk:

- Semua Transaksi menampilkan transaksi SPP dan pembayaran lain.
- Filter unit/kelas/siswa bekerja.
- SPP Perbulan menampilkan status Lunas, Sebagian, dan Belum Bayar.
- SPP Belum Bayar hanya menampilkan `remaining_amount > 0`.
- SPP Tahun Pelajaran menampilkan bulan Juli sampai Juni.
- Rekap Per Unit menghitung total per kategori.
- Export XLSX dan PDF mengunduh file dengan nama yang sesuai.

## Urutan Implementasi

1. Rapikan sidebar dan route laporan.
2. Buat `ReportQueryService`.
3. Buat komponen/pola layout laporan mengikuti standar aplikasi.
4. Implementasi Semua Transaksi.
5. Implementasi export XLSX/PDF Semua Transaksi.
6. Implementasi SPP Perbulan.
7. Implementasi export XLSX/PDF SPP Perbulan.
8. Implementasi SPP Belum Bayar.
9. Implementasi export XLSX/PDF SPP Belum Bayar.
10. Implementasi SPP Tahun Pelajaran.
11. Implementasi export XLSX/PDF SPP Tahun Pelajaran.
12. Implementasi Rekap Per Unit.
13. Implementasi export XLSX/PDF Rekap Per Unit.
14. Tambahkan test dan verifikasi UI mobile.

## Prioritas

Prioritas pengerjaan:

1. Semua Transaksi
2. SPP Perbulan
3. SPP Belum Bayar
4. SPP Tahun Pelajaran
5. Rekap Per Unit

Alasannya: Semua Transaksi dan SPP Perbulan adalah kebutuhan harian bendahara. SPP Belum Bayar mendukung penagihan. SPP Tahun Pelajaran dan Rekap Per Unit lebih cocok untuk evaluasi dan laporan pimpinan.

## Kriteria Selesai

Menu Laporan dianggap selesai jika:

- Semua submenu final tersedia di sidebar.
- Semua halaman mengikuti standar UI aplikasi.
- Semua halaman memiliki filter, ringkasan, tabel, dan export XLSX/PDF.
- Data utama tampil dalam tabel compact 40px.
- Mobile tidak melebar ke kanan kecuali scroll horizontal di `.table-wrap`.
- Export XLSX bisa dibuka di Excel.
- Export PDF siap cetak.
- Test feature utama lulus.
