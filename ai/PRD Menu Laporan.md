# PRD Menu Laporan MA'WA CENTER

## Ringkasan

Menu Laporan menjadi pusat rekap, arsip, kontrol, export, dan cetak laporan keuangan. Halaman ini membantu admin, bendahara, dan pimpinan melihat transaksi masuk, kontrol SPP perbulan, rekap SPP satu tahun pelajaran, serta rekap penerimaan per unit pendidikan.

Laporan tidak menjadi tempat utama untuk menindaklanjuti tunggakan. Data belum bayar yang membutuhkan aksi seperti bayar, detail tagihan, atau surat tagihan ditempatkan di Menu Tagihan.

Semua halaman laporan wajib mengikuti [standar aplikasi.md](standar aplikasi.md), terutama standar spacing, card filter, tabel compact 40px, tombol, warna, export, dan responsif mobile.

Semua tabel laporan wajib mengikuti **Standar Tabel Final** pada [standar aplikasi.md](standar aplikasi.md). Tabel detail dan tabel rekap memakai satu standar visual yang sama; perbedaan hanya pada fungsi data, alignment kolom, dan footer total jika ada.

- Tabel detail untuk daftar transaksi dan daftar siswa.
- Tabel rekap untuk ringkasan per unit, per kelas, per bulan, atau per status.
- Footer `Total Keseluruhan` untuk agregat akhir hasil filter.
- Empty state tabel untuk kondisi data kosong, tanpa membuat halaman melebar ke kanan.

## Tujuan Produk

- Admin dapat melihat total penerimaan dan detail transaksi dalam periode tertentu.
- Bendahara dapat melihat pemasukan harian/periode berdasarkan unit, kelas, dan petugas.
- Bendahara dapat melihat status pembayaran SPP per bulan.
- Admin dan pimpinan dapat melihat rekap SPP satu tahun pelajaran dari Juli sampai Juni.
- Pimpinan dapat melihat rekap penerimaan per unit pendidikan.
- Semua laporan dapat diekspor dalam format **XLSX** dan **PDF**.
- Laporan terasa operasional, cepat dipindai, dan tidak memakai card-card data berulang untuk data utama.

## Non-Tujuan

- Tidak membuat dashboard grafik besar di tahap awal.
- Tidak membuat laporan akuntansi lengkap seperti neraca, jurnal, atau buku besar.
- Tidak memakai CSV sebagai export utama.
- Tidak menjadikan laporan sebagai tempat aksi penagihan.
- Tidak menampilkan daftar tunggakan panjang di Menu Laporan jika data tersebut lebih tepat masuk Menu Tagihan.
- Tidak menampilkan ulang rincian tagihan panjang di tabel utama jika sudah tersedia halaman detail/tagihan.

## Batas Menu Laporan dan Tagihan

Menu Laporan:

- Menampilkan transaksi yang sudah terjadi.
- Menampilkan rekap pembayaran dan status pembayaran.
- Menampilkan data untuk arsip, audit, export, dan cetak.
- Tidak menjadi tempat utama aksi bayar atau penagihan.

Menu Tagihan:

- Menampilkan kewajiban yang belum selesai.
- Menampilkan belum bayar SPP dan belum bayar pembayaran lain.
- Menyediakan aksi bayar, detail tagihan, dan surat tagihan.
- Menjadi tempat tindak lanjut piutang/tunggakan.

Menu Pembayaran:

- Memproses transaksi baru.
- Mencatat pembayaran siswa.
- Mencetak struk transaksi.

## Pengguna Utama

- **Admin:** mengelola laporan lintas unit.
- **Bendahara:** mengecek transaksi, pemasukan unit, SPP perbulan, dan rekap SPP.
- **Pimpinan/Yayasan:** membaca ringkasan penerimaan dan perbandingan per unit.

## Struktur Menu Final

Sidebar Laporan:

1. Transaksi Pembayaran
2. SPP Perbulan
3. SPP Pertahun
4. Rekap Per Unit

Menu **Riwayat Pembayaran** tidak dibuat submenu terpisah karena fungsinya masuk ke **Transaksi Pembayaran**.

Menu **SPP Belum Bayar** tidak dibuat submenu laporan karena fungsinya pindah ke Menu Tagihan.

## Cari Siswa di Toolbar Tabel

Halaman laporan yang menampilkan data per siswa boleh memakai card/input kecil `Cari Siswa` di area kanan toolbar atas tabel.

Aturan:

- Berlaku untuk Transaksi Pembayaran, SPP Perbulan, dan SPP Pertahun.
- Tidak berlaku untuk Rekap Per Unit karena halaman tersebut bersifat agregat unit, bukan daftar siswa.
- Pencarian berdasarkan Nama, NIS, atau NISN.
- Jika pencarian siswa aktif, tabel dan ringkasan pada halaman tersebut hanya menampilkan data siswa yang cocok, tetap digabung dengan filter lain seperti tanggal, unit, kelas, petugas, bulan, dan tahun.
- State aktif ditampilkan sebagai `Nama Siswa · NIS` dengan tombol clear.
- Toolbar atas tetap mengikuti standar aplikasi: kiri `Tampilkan [10] data`, kanan `Cari Siswa`, sedangkan teks `Menampilkan x-y dari z data` berada di footer bawah tabel.

## Halaman 1: Transaksi Pembayaran

### Tujuan

Menjawab kebutuhan harian bendahara dan admin:

- Hari ini/periode ini masuk berapa?
- Unit tertentu menerima pembayaran berapa?
- Kelas tertentu menerima pembayaran berapa?
- Petugas tertentu mencatat transaksi apa saja?
- Pemasukan unit tertentu berasal dari SPP, daftar ulang, laundry, atau pembayaran lain berapa?

### Filter

Filter utama:

- Tanggal Dari
- Tanggal Sampai
- Unit Pendidikan
- Kelas
- Petugas

Catatan:

- Default `Tanggal Dari` dan `Tanggal Sampai` pada Transaksi Pembayaran adalah tanggal hari ini.
- Filter Unit Pendidikan dapat dipakai untuk melihat pemasukan MTs, MA, PAUD, dan unit lain.
- Filter Kelas bergantung pada Unit Pendidikan jika Unit Pendidikan dipilih.
- Filter Petugas dipakai untuk audit transaksi kasir/bendahara.
- Cari Siswa tersedia di toolbar tabel untuk melihat semua transaksi siswa tertentu berdasarkan Nama, NIS, atau NISN.
- Kategori pembayaran dan cara bayar boleh ditambahkan sebagai filter lanjutan jika dibutuhkan, tetapi bukan filter utama.

### Ringkasan Per Unit

Transaksi Pembayaran tidak memakai card ringkasan angka di atas tabel jika data yang sama sudah dijawab oleh tabel rekap dan footer `Total Keseluruhan`. Halaman ini harus terasa sebagai laporan operasional, bukan dashboard berat.

Ringkasan utama ditampilkan sebagai tabel rekap per unit:

- No
- Unit Pendidikan
- Jumlah Transaksi
- Cash
- Transfer
- Jumlah Penerimaan

Footer tabel rekap per unit:

- Total Keseluruhan
- Jumlah transaksi semua unit
- Total Cash semua unit
- Total Transfer semua unit
- Jumlah Penerimaan semua unit

Data jenis pembayaran seperti SPP, Daftar Ulang, Laundry, dan Lain-lain tidak perlu dibuat card ringkasan jika sudah tersedia di tabel detail, tabel rekap, export, atau laporan khusus. Jika nanti diperlukan, tampilkan sebagai kolom/tabel yang bisa dibandingkan, bukan card berulang.

### Tabel Detail

Kolom tabel:

- No
- Tanggal
- NIS
- Nama Siswa
- Unit
- Kelas
- Jenis Pembayaran
- Cara Bayar
- Petugas
- Nominal

Aturan tabel:

- Data utama wajib tampil sebagai tabel compact, bukan card transaksi berulang.
- Tabel detail transaksi dan tabel rekap per unit mengikuti satu **Standar Tabel Final** yang sama.
- Header tabel rata tengah.
- Semua header tabel memakai 14px / 500 agar tidak terlalu tebal.
- Footer total memakai 14px / 700.
- Kolom No dan Jumlah Transaksi rata tengah.
- Unit Pendidikan rata kiri.
- Cash, Transfer, Jumlah Penerimaan, dan semua nominal rata kanan.
- Label footer `Total Keseluruhan` rata tengah pada area gabungan kolom No dan Unit Pendidikan.
- Angka footer `Jumlah Transaksi` rata tengah dan memakai warna teks utama, bukan hijau.
- Nominal footer Cash, Transfer, dan Jumlah Penerimaan rata kanan, bold, dan hijau.
- Footer Total Keseluruhan memakai satu baris `tfoot` di bawah tabel.
- Nominal rata kanan.
- Nama siswa rata kiri.
- Status transaksi boleh masuk detail baris jika tidak menjadi fokus utama.
- Aksi detail/cetak boleh berupa ikon kecil transparan jika dibutuhkan.

### Export

- XLSX: data detail transaksi dan ringkasan per unit.
- PDF: ringkasan periode, ringkasan per unit, dan tabel transaksi.

## Halaman 2: SPP Perbulan

### Tujuan

Melihat pembayaran SPP bulanan yang sudah terjadi pada bulan tertentu. Halaman ini dipakai untuk arsip dan rekap aktivitas pembayaran SPP bendahara.

### Filter

Filter utama:

- Tahun
- Bulan
- Unit Pendidikan
- Kelas

Catatan:

- SPP Perbulan memakai `Tahun` kalender, bukan `Tahun Pelajaran`, karena periode SPP disimpan sebagai kombinasi bulan dan tahun.
- Tahun Pelajaran tetap dipakai di SPP Pertahun.
- Unit Pendidikan dan Kelas menjadi filter utama sesuai kebutuhan operasional.
- SPP Perbulan tidak memakai filter `Status Pembayaran`; status hanya menjadi penanda data transaksi yang sudah terjadi.
- Cari Siswa tersedia di toolbar tabel untuk melihat status SPP siswa tertentu berdasarkan Nama, NIS, atau NISN.
- Siswa yang belum membayar SPP tidak ditampilkan di SPP Perbulan; data tersebut menjadi tanggung jawab Menu Tagihan.

### Ringkasan Per Unit

Kolom ringkasan:

- No
- Unit Pendidikan
- Jumlah Siswa
- Lunas
- Sebagian
- Jumlah Penerimaan

Footer tabel rekap:

- Total Keseluruhan
- Jumlah siswa yang membayar
- Jumlah lunas
- Jumlah sebagian
- Jumlah penerimaan SPP pada filter aktif

### Tabel Detail

Kolom tabel final:

- No
- Tanggal
- NIS
- Nama Siswa
- Unit
- Kelas
- Bulan
- Tahun
- Nominal
- Cara Bayar
- Petugas

Status SPP:

- Lunas
- Sebagian

Aturan tabel:

- Kolom `Tagihan SPP` tidak ditampilkan di tabel utama agar tabel lebih ringan.
- Nilai tagihan awal boleh tersedia di detail/tooltip jika dibutuhkan.
- Tabel detail SPP Perbulan menampilkan data transaksi pembayaran SPP, bukan daftar tagihan siswa.
- `Nominal` adalah jumlah pembayaran SPP pada bulan dan tahun terpilih.
- `Tanggal` adalah tanggal transaksi pembayaran SPP.
- Jika `Sisa = 0`, status `Lunas`.
- Jika `Terbayar > 0` dan `Sisa > 0`, status `Sebagian`.
- Jika `Terbayar = 0`, siswa tidak ditampilkan di SPP Perbulan dan masuk ke Menu Tagihan.
- `Total Sisa` tidak ditampilkan di tabel rekap SPP Perbulan; sisa/tagihan belum selesai menjadi fokus Menu Tagihan.

### Sumber Data

Utama dari tabel `bills`:

- `source_type = spp`
- `year`
- `month`
- `paid_amount`
- `remaining_amount`
- `status`

Data tanggal pembayaran dapat diambil dari item pembayaran SPP jika diperlukan untuk detail.

### Empty State

- Jika tidak ada pembayaran SPP pada bulan dan tahun terpilih, tampilkan: "Belum ada pembayaran SPP pada periode ini."

### Export

- XLSX: daftar pembayaran SPP yang sudah terjadi pada bulan terpilih.
- PDF: daftar pembayaran SPP bulanan untuk cetak dan arsip.

## Halaman 3: SPP Pertahun

### Tujuan

Melihat rekap SPP satu tahun pelajaran, dari Juli sampai Juni. Halaman ini dipakai untuk evaluasi tahunan dan laporan pimpinan.

### Filter

- Tahun Pelajaran
- Unit Pendidikan
- Kelas

Cari Siswa tersedia di toolbar tabel untuk melihat rekap SPP satu siswa tertentu berdasarkan Nama, NIS, atau NISN.

Catatan:

- Tahun Pelajaran pada SPP Pertahun hanya menentukan periode bulan Juli sampai Juni.
- Tahun Pelajaran tidak boleh dipakai untuk membatasi `students.academic_year_id`, agar transaksi tahun pelajaran lama tetap tampil meskipun data siswa sudah berpindah tahun aktif.
- Filter Unit Pendidikan dan Kelas tetap memakai relasi kelas/unit siswa saat ini.
- Siswa nonaktif tidak ditampilkan di SPP Pertahun walaupun memiliki bill atau riwayat pembayaran pada periode tersebut.

### Ringkasan

SPP Pertahun tidak memakai card ringkasan dan tidak memakai tabel rekap tambahan. Halaman ini fokus pada tabel matriks Juli sampai Juni per siswa agar pembacaan status tahunan tetap ringan.

### Tabel Detail

Kolom tabel final:

- No
- NIS
- Nama Siswa
- Unit
- Kelas
- Juli
- Agustus
- September
- Oktober
- November
- Desember
- Januari
- Februari
- Maret
- April
- Mei
- Juni

Aturan isi kolom bulan:

- Jika sudah lunas: tampilkan tanggal pembayaran, contoh `19/06/2026`.
- Jika sebagian/cicil: tampilkan `Sebagian` dan tanggal bayar terakhir jika ada, contoh `Sebagian · 19/06/2026`.
- Jika belum bayar: tampilkan badge tipis merah lembut `Belum Bayar`.
- Jika tidak ditagih: tampilkan badge tipis abu-abu `Tidak Ditagih` hanya jika memang tidak ada tagihan SPP untuk bulan tersebut.

Aturan tabel:

- Tabel boleh memakai scroll horizontal karena kolom bulan banyak.
- Header bulan memakai nama lengkap: Juli, Agustus, September, dan seterusnya.
- Nama siswa harus tetap mudah dibaca; kolom nama boleh lebih lebar daripada kolom bulan.
- Export PDF menggunakan A4 landscape.

### Sumber Data

Utama dari tabel `bills` dan item pembayaran SPP:

- `bills.source_type = spp`
- `bills.year`
- `bills.month`
- `bills.paid_amount`
- `bills.remaining_amount`
- item pembayaran untuk tanggal pembayaran terakhir.

### Export

- XLSX: rekap SPP siswa per bulan.
- PDF: landscape A4 karena jumlah kolom bulan banyak.

## Halaman 4: Rekap Per Unit

### Tujuan

Memberi ringkasan penerimaan per unit pendidikan berdasarkan periode transaksi yang dipilih.

### Filter

- Tanggal Dari
- Tanggal Sampai

Catatan:

- Default `Tanggal Dari` dan `Tanggal Sampai` pada Rekap Per Unit adalah tanggal hari ini.
- Rekap Per Unit tidak menampilkan Cari Siswa karena outputnya agregat per unit pendidikan.

### Ringkasan Atas

Rekap Per Unit tidak memakai card ringkasan angka di atas tabel jika data yang sama sudah dijawab oleh tabel dan footer `Total Keseluruhan`. Halaman ini fokus pada tabel rekap per unit agar mudah dibandingkan.

### Tabel

Kolom tabel:

- No
- Unit Pendidikan
- SPP
- Daftar Ulang
- Laundry
- Lain-lain
- Jumlah Penerimaan

Footer tabel:

- Total Keseluruhan

Aturan footer:

- Baris footer selalu ada.
- Semua nominal footer adalah total dari semua baris unit yang tampil.
- Label `Total Keseluruhan` rata tengah pada kolom teks awal.
- Nominal footer rata kanan dan bold.

### Export

- XLSX: rekap angka per unit dengan footer total keseluruhan.
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
- Baris terakhir tabel tidak memakai garis bawah penutup kecuali ada footer tabel.
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
- Rekap Per Unit wajib memiliki baris footer Total Keseluruhan.

Catatan teknis:

- Project saat ini memakai `SimpleXlsxWriter`.
- Jika kebutuhan formatting Excel makin kompleks, boleh dipertimbangkan package `maatwebsite/excel` atau solusi setara berbasis PhpSpreadsheet.

### PDF

Kebutuhan:

- Format siap cetak.
- Memakai kop/identitas laporan MA'WA CENTER.
- Menampilkan periode/filter laporan.
- Menampilkan tanggal cetak.
- Menampilkan ringkasan dan tabel utama.
- Untuk SPP Pertahun gunakan A4 landscape.
- Untuk laporan lain gunakan A4 portrait kecuali tabel terlalu lebar.

Catatan teknis:

- Project sudah memiliki `dompdf/dompdf`.
- PDF dibuat dengan Blade view khusus per laporan.

## Struktur Route

Route halaman:

```text
/laporan/transaksi
/laporan/spp-perbulan
/laporan/spp-tahun-pelajaran
/laporan/rekap-unit
```

Route export:

```text
/laporan/transaksi/export/xlsx
/laporan/transaksi/export/pdf
/laporan/spp-perbulan/export/xlsx
/laporan/spp-perbulan/export/pdf
/laporan/spp-tahun-pelajaran/export/xlsx
/laporan/spp-tahun-pelajaran/export/pdf
/laporan/rekap-unit/export/xlsx
/laporan/rekap-unit/export/pdf
```

Route `/laporan` boleh tetap diarahkan ke `/laporan/transaksi` untuk kompatibilitas.

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
yearlySpp()
yearlySppSummary()
unitRecap()
```

View halaman:

```text
resources/views/reports/transactions.blade.php
resources/views/reports/monthly-spp.blade.php
resources/views/reports/yearly-spp.blade.php
resources/views/reports/unit-recap.blade.php
```

View PDF:

```text
resources/views/reports/pdf/transactions.blade.php
resources/views/reports/pdf/monthly-spp.blade.php
resources/views/reports/pdf/yearly-spp.blade.php
resources/views/reports/pdf/unit-recap.blade.php
```

Export XLSX:

```text
app/Support/SimpleXlsxWriter.php
```

Catatan:

- Route/view/method laporan untuk `outstanding-spp` atau `spp-belum-bayar` tidak dipakai lagi di Menu Laporan. Konsep belum bayar/tunggakan menjadi ranah Menu Tagihan.

## Prinsip Query dan Performa

- Jangan mengambil semua data lalu paginate di Collection untuk dataset besar.
- Query laporan harus memakai pagination database jika memungkinkan.
- Ringkasan per unit dihitung dengan aggregate query jika memungkinkan.
- Filter Unit dan Kelas harus memakai relasi siswa/kelas/unit.
- Laporan SPP memakai tabel `bills` agar status tagihan, terbayar, dan sisa konsisten dengan Menu Tagihan.
- Export boleh mengambil seluruh data sesuai filter, tetapi tetap harus dibatasi oleh filter yang jelas.

## Validasi dan Empty State

- Jika tidak ada data, tampilkan empty state singkat: "Belum ada data pada filter ini."
- Jika Tahun Pelajaran belum aktif, halaman tetap terbuka tetapi menampilkan keterangan "Tahun Pelajaran Aktif belum diatur."
- Jika export tidak menemukan data, tetap hasilkan file dengan header dan keterangan kosong, bukan error mentah.

## Testing

Tambahkan test feature untuk:

- Transaksi Pembayaran menampilkan transaksi SPP, daftar ulang, laundry, dan pembayaran lain.
- Filter Transaksi Pembayaran berdasarkan unit, kelas, dan petugas bekerja.
- SPP Perbulan menampilkan kolom final: No, Tanggal, NIS, Nama Siswa, Unit, Kelas, Bulan, Tahun, Nominal, Cara Bayar, Petugas.
- SPP Perbulan tidak menampilkan filter Status Pembayaran.
- SPP Perbulan menampilkan status Lunas dan Sebagian saja; Belum Bayar masuk Menu Tagihan.
- SPP Pertahun menampilkan bulan Juli sampai Juni.
- SPP Pertahun menampilkan tanggal pembayaran pada bulan yang sudah dibayar.
- Rekap Per Unit menghitung total per kategori.
- Rekap Per Unit menampilkan footer Total Keseluruhan.
- Export XLSX dan PDF mengunduh file dengan nama yang sesuai.

## Urutan Implementasi

1. Rapikan sidebar dan route laporan final.
2. Pastikan `SPP Belum Bayar` tidak muncul sebagai submenu laporan.
3. Rapikan `ReportQueryService` sesuai struktur final.
4. Buat atau rapikan komponen/pola layout laporan mengikuti standar aplikasi.
5. Implementasi Transaksi Pembayaran.
6. Implementasi export XLSX/PDF Transaksi Pembayaran.
7. Implementasi SPP Perbulan.
8. Implementasi export XLSX/PDF SPP Perbulan.
9. Implementasi SPP Pertahun.
10. Implementasi export XLSX/PDF SPP Pertahun.
11. Implementasi Rekap Per Unit.
12. Implementasi export XLSX/PDF Rekap Per Unit.
13. Tambahkan test dan verifikasi UI mobile.
14. Audit ulang apakah fitur tunggakan sudah dipindahkan ke Menu Tagihan.

## Prioritas

Prioritas pengerjaan:

1. Transaksi Pembayaran
2. SPP Perbulan
3. SPP Pertahun
4. Rekap Per Unit

Alasannya: Transaksi Pembayaran dan SPP Perbulan adalah kebutuhan harian bendahara. SPP Pertahun dan Rekap Per Unit lebih cocok untuk evaluasi dan laporan pimpinan.

## Kriteria Selesai

Menu Laporan dianggap selesai jika:

- Semua submenu final tersedia di sidebar.
- Menu SPP Belum Bayar tidak tampil sebagai submenu laporan.
- Semua halaman mengikuti standar UI aplikasi.
- Semua halaman memiliki filter, ringkasan, tabel, dan export XLSX/PDF.
- Data utama tampil dalam tabel compact 40px.
- SPP Perbulan memakai kolom final yang disepakati.
- SPP Pertahun menampilkan tanggal pembayaran pada kolom bulan.
- Rekap Per Unit memiliki footer Total Keseluruhan.
- Mobile tidak melebar ke kanan kecuali scroll horizontal di `.table-wrap`.
- Export XLSX bisa dibuka di Excel.
- Export PDF siap cetak.
- Test feature utama lulus.
