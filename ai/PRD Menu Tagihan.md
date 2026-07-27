# PRD Menu Tagihan

## Tujuan

Menu Tagihan adalah halaman kerja untuk melihat kewajiban siswa yang belum selesai. Menu ini menjadi tempat utama untuk melihat SPP belum bayar, daftar ulang yang masih punya sisa, dan pembayaran lain yang memang dibuat sebagai tagihan.

Menu Tagihan harus terasa ringkas, operasional, dan mudah dipindai. Pengguna cukup melihat ringkasan per unit, memfilter siswa, membuka detail tagihan jika perlu, lalu melanjutkan ke pembayaran.

Semua tampilan wajib mengikuti [standar aplikasi.md](standar aplikasi.md), terutama standar warna, tipografi, card filter, tabel compact, tombol aksi, pagination, responsif, dan print/PDF.

## Batas Menu

Menu Tagihan:

- Menampilkan kewajiban siswa yang belum selesai.
- Menampilkan SPP belum bayar dan pembayaran lain yang memang menjadi tagihan.
- Menyediakan detail tagihan, aksi bayar, surat tagihan, cetak, dan unduh.
- Menjadi tempat tindak lanjut piutang/tunggakan.

Menu Laporan:

- Menampilkan transaksi yang sudah terjadi.
- Menampilkan rekap pembayaran dan status pembayaran.
- Menjadi tempat arsip, audit, export, dan cetak laporan.
- Tidak menjadi tempat utama aksi bayar atau penagihan.

Menu Pembayaran:

- Memproses transaksi baru.
- Mencatat pembayaran siswa.
- Mencetak struk transaksi.
- Menjadi tujuan dari tombol Bayar di Menu Tagihan.

## Keputusan Laundry

Laundry tetap dianggap pembayaran opsional dan tidak masuk Menu Tagihan.

Laundry hanya boleh masuk Menu Tagihan jika nanti ada konsep bisnis baru bahwa laundry menjadi kewajiban berlangganan atau tagihan rutin. Selama belum ada keputusan itu, laundry tetap diproses dari Menu Pembayaran sebagai pembayaran opsional.

## Pengguna Utama

- **Admin:** melihat tagihan semua unit, membuka detail, mencetak surat tagihan, dan melanjutkan ke pembayaran.
- **Bendahara Unit:** melihat tagihan unit yang diizinkan, membuka detail, mencetak surat tagihan, dan melanjutkan ke pembayaran.
- **Wali Santri:** melihat tagihan anak yang terhubung dan mengirim bukti pembayaran transfer jika fitur wali aktif.

## Alur Utama Admin/Bendahara

1. Pengguna membuka Menu Tagihan.
2. Sistem menampilkan heading dan tabel ringkasan per unit.
3. Pengguna memakai filter Unit Pendidikan/Kelas atau Cari Siswa di toolbar tabel jika perlu.
4. Sistem menampilkan tabel siswa yang masih memiliki total tagihan.
5. Pengguna klik Detail untuk melihat surat tagihan siswa.
6. Pengguna klik Bayar untuk lanjut ke Menu Pembayaran dengan siswa terpilih.
7. Setelah pembayaran berhasil, sisa tagihan berkurang atau hilang dari Menu Tagihan.

## Alur Utama Wali Santri

1. Wali Santri membuka Menu Tagihan.
2. Sistem hanya menampilkan siswa yang terhubung dengan akun wali.
3. Wali melihat daftar tagihan anak.
4. Wali memilih tagihan yang akan dibayar.
5. Wali mengirim bukti transfer.
6. Setelah transfer diverifikasi diterima, tagihan berkurang atau lunas.

## Struktur Tampilan Admin/Bendahara

Urutan halaman:

1. Heading
2. Tabel Ringkasan Per Unit
3. Card Filter
4. Toolbar jumlah data
5. Tabel Tagihan Siswa
6. Pagination jika total data lebih dari jumlah data per halaman
7. Footer copyright

Jangan menampilkan card ringkasan besar, progress bar per unit, atau strip total di atas tabel utama. Data ringkasan cukup memakai tabel ringkasan per unit agar halaman tetap ringan.

## Heading

Judul halaman:

```text
Tagihan Siswa
```

Deskripsi halaman:

```text
Pantau kewajiban siswa yang belum selesai dan lanjutkan ke pembayaran.
```

Standar:

- Judul 20px / 700 / #020617.
- Deskripsi 14px / 400 / #707971 atau #404942.
- Jarak judul ke deskripsi 4px.
- Jarak heading ke tabel ringkasan 16px.

## Tabel Ringkasan Per Unit

Tabel ringkasan per unit ditampilkan langsung setelah heading, tanpa judul atau subdeskripsi tambahan.

Kolom:

1. No
2. Unit Pendidikan
3. Siswa
4. Jumlah Tagihan

Aturan:

- Header tabel 14px / 500 / #020617 atau #334155.
- Isi tabel 14px / 400 / #020617.
- No rata tengah.
- Unit Pendidikan rata kiri dan menampilkan nama unit tanpa kode unit.
- Siswa rata tengah.
- Jumlah Tagihan rata kanan.
- Nominal isi tabel memakai warna teks utama, bukan hijau berlebihan.
- Footer Total Keseluruhan 14px / 700.
- Nominal footer Total Keseluruhan memakai #004528.
- Tabel tetap tampil walaupun data kosong.

Footer:

```text
Total Keseluruhan | total siswa | total jumlah tagihan
```

Label `Total Keseluruhan` memakai colspan pada area No dan Unit Pendidikan agar tidak bertumpuk.

Empty state:

```text
Belum ada tagihan pada filter ini.
```

## Card Filter

Card filter diletakkan setelah tabel ringkasan per unit dan sebelum toolbar jumlah data.

Filter utama:

1. Unit Pendidikan
2. Kelas
3. Tombol Terapkan
4. Tombol Reset

Aturan:

- Background #ffffff.
- Border #d1d5db.
- Radius 12px.
- Padding 16px.
- Gap 12px.
- Label 14px / 400 / #334155.
- Field 40px, radius 8px, font 14px.
- Tombol Terapkan memakai tombol primer hijau #004528.
- Tombol Reset memakai tombol sekunder putih border #d1d5db.

Filter yang tidak ditampilkan di card utama:

- Tahun Tagihan.
- Sampai Bulan.
- Status Tagihan.
- Kategori.

Alasan: Tagihan adalah halaman tindak lanjut harian. Filter utama harus cepat dan tidak berat. Tahun Pelajaran Aktif dari topbar menjadi konteks utama data aktif.

## Cari Siswa Di Toolbar Tabel

Cari Siswa diletakkan di sisi kanan toolbar atas tabel Tagihan Siswa, sejajar dengan kontrol jumlah data di sisi kiri. Cari Siswa tidak masuk card filter agar card filter tetap ringan. Pada desktop, label `Cari Siswa` berada di samping input-card; pada mobile, label dan input menjadi satu kolom full width.

Cari Siswa menerima:

- Nama siswa.
- NIS.
- NISN jika data tersedia.

Saat siswa dipilih atau query pencarian aktif, tabel hanya menampilkan siswa yang sesuai dan tetap mengikuti filter Unit Pendidikan/Kelas.

Placeholder:

```text
Nama, NIS, atau NISN...
```

## Toolbar Jumlah Data

Toolbar berada tepat sebelum tabel siswa.

Kontrol kiri:

```text
Tampilkan [10] data
```

Info kanan:

```text
Menampilkan 1-10 dari 100 siswa
```

Standar:

- Font 14px / 400 / #707971.
- Select 78px x 34px.
- Jarak toolbar ke tabel 16px.

## Pagination

Menu Tagihan memakai pagination standar aplikasi jika total siswa lebih banyak dari jumlah data per halaman.

Keputusan ini dipakai karena data tagihan bisa besar dan tidak boleh membebani halaman.

Aturan:

- Pagination tampil hanya jika total data lebih dari `per_page`.
- Footer bawah tabel kiri menampilkan info hasil jika pagination dipakai.
- Footer bawah tabel kanan menampilkan tombol pagination.
- Jangan memakai teks bawaan Inggris seperti `Showing ... results`.
- Jangan memakai teks `Halaman x dari y`.
- Tombol normal 14px / 500.
- Tombol halaman aktif 14px / 700.
- Tinggi tombol 36px.
- Radius 8px.
- Gap 8px.
- Aktif: background #004528, teks #ffffff.
- Normal: background #ffffff, border #d1d5db, teks #334155.
- Disabled: teks #707971, background #f9fafb, border #d1d5db.
- Mobile menjadi satu kolom dan tombol boleh wrap tanpa membuat halaman melebar.

Catatan standar aplikasi yang perlu disesuaikan: bagian lama yang menyebut pagination bawah Tagihan tidak ditampilkan perlu diperbarui agar selaras dengan keputusan ini.

## Tabel Tagihan Siswa

Tabel utama memakai tabel compact, bukan card siswa berulang.

Kolom:

1. No
2. NIS
3. Nama Siswa
4. Unit
5. Kelas
6. Total Tagihan
7. Aksi

Aturan alignment:

- Header tabel rata tengah.
- No rata tengah.
- NIS rata tengah.
- Nama Siswa rata kiri.
- Unit rata tengah.
- Kelas rata tengah.
- Total Tagihan rata kanan.
- Aksi rata tengah.

Aturan visual:

- Header 14px / 500.
- Isi 14px / 400.
- Nama Siswa 14px / 400 warna #020617.
- Total Tagihan isi tabel 14px / 400 warna #020617.
- Total penting di footer/ringkasan boleh hijau #004528.
- Header dan baris utama tinggi 40px.
- Padding cell 8px sampai 10px.
- Border bawah #e5e7eb.
- Baris terakhir tidak memakai garis bawah penutup berlebih.
- Header memakai Title Case, bukan kapital semua kecuali NIS.
- Sorting tersedia untuk kolom NIS, Nama Siswa, Unit, Kelas, dan Total Tagihan.
- Kolom No dan Aksi tidak sortable.
- Ikon sort harus kecil, rapi, dan tidak membuat header melebihi tinggi standar 40px.
- Sorting wajib mempertahankan filter Unit Pendidikan, Kelas, Cari Siswa, dan per_page.

Lebar kolom acuan desktop:

- No: 48px.
- NIS: 84px.
- Nama Siswa: fleksibel.
- Unit: 74px.
- Kelas: sekitar 168px.
- Total Tagihan: sekitar 136px.
- Aksi: sekitar 60px.

## Rincian Tagihan

Rincian SPP, daftar ulang, dan lain-lain tidak ditampilkan sebagai kolom panjang di tabel utama.

Rincian ditampilkan di halaman Detail/Surat Tagihan agar tabel utama tetap ringan.

Rincian minimal:

- SPP belum bayar per rentang bulan.
- Daftar ulang yang masih punya sisa.
- Lain-lain yang masih punya sisa.
- Tahun/periode.
- Nominal per item.
- Jumlah bulan jika item berupa SPP bulanan.
- Total per item.
- Total keseluruhan.
- Terbilang.

## Aksi Tabel

Kolom Aksi memakai tombol ikon transparan tanpa card/border berlebih.

Aksi:

- Detail: membuka halaman Surat Tagihan Siswa.
- Bayar: membuka Menu Pembayaran dengan siswa terpilih.

Aturan:

- Tombol ikon sekitar 28px sampai 32px.
- Wajib memiliki `aria-label` dan `title`.
- Ikon Detail memakai ikon lihat/dokumen.
- Ikon Bayar memakai ikon wallet/pembayaran.
- Jangan memakai teks di tombol aksi tabel agar kolom tetap longgar.

## Halaman Surat Tagihan

Halaman Surat Tagihan adalah halaman resmi siap cetak, bukan modal kecil.

Isi:

- Toolbar layar: Kembali, Bayar, Unduh, Cetak.
- Judul surat resmi.
- Identitas siswa: NIS, Nama Siswa, Unit Pendidikan, Kelas.
- Tabel rincian: No, Uraian, Tahun, Rp., Jml Bulan, Jumlah.
- Total Keseluruhan.
- Terbilang.
- Tanggal cetak.
- Tanda tangan.

Standar:

- Dokumen print/PDF memakai background putih.
- Ukuran dokumen Surat Tagihan memakai setengah F4 portrait: 16.5cm x 21.5cm (165mm x 215mm).
- Font print boleh mengikuti standar cetak, bukan standar UI 14px penuh.
- Toolbar layar hilang saat print.
- Dokumen tidak memakai card dekoratif, shadow berat, atau warna UI berlebihan saat dicetak.

## Empty State

Jika tidak ada tagihan:

```text
Belum ada tagihan pada filter ini.
```

Jika siswa terpilih tidak memiliki tagihan:

```text
Tidak ada tagihan aktif untuk siswa ini.
```

Empty state bukan error, tidak memakai warna merah, dan tetap 14px / 400 / #707971.

## Hak Akses

Admin:

- Melihat semua unit.
- Melihat semua tagihan siswa.
- Membuka detail/surat tagihan.
- Melanjutkan ke pembayaran.

Bendahara Unit:

- Hanya melihat unit yang diizinkan.
- Filter Unit Pendidikan hanya berisi unit yang diizinkan.
- Tidak bisa membuka detail tagihan siswa di luar unitnya.
- Tidak bisa lanjut bayar siswa di luar unitnya.

Wali Santri:

- Hanya melihat siswa yang terhubung.
- Tidak melihat tabel ringkasan semua unit.
- Tidak bisa membuka tagihan siswa lain lewat URL manual.
- Mengirim bukti transfer jika fitur wali aktif.

## Data dan Query

Sumber utama data Tagihan adalah tabel `bills`.

Tagihan yang ditampilkan:

- `status != Dibatalkan`.
- `remaining_amount > 0`.
- SPP dari `source_type = spp`.
- Daftar ulang dari `source_type = fee_type` dengan `payment_group = daftar-ulang`.
- Lain-lain dari `source_type = manual` atau `source_type = fee_type` selain SPP, daftar ulang, dan laundry.

Laundry:

- Tidak ditampilkan selama masih dianggap pembayaran opsional.
- `payment_group = laundry` dikecualikan dari tagihan.

Periode:

- Tahun Pelajaran Aktif dari topbar menjadi konteks utama data aktif.
- Filter tahun/bulan tidak ditampilkan di UI utama.
- Sistem boleh memakai periode internal sampai bulan berjalan untuk generate/sync tagihan, tetapi tidak membuat filter utama menjadi berat.

Sinkronisasi tagihan:

- Jika masih diperlukan, aksi sinkron harus bersifat admin/internal dan tidak menjadi aksi utama harian.
- Sinkronisasi tidak boleh mengubah pembayaran yang sudah diterima.
- Setelah pembayaran, bill harus refresh agar sisa tagihan akurat.

## Responsif

Desktop:

- Container maksimal 1200px.
- Heading, tabel ringkasan, filter, toolbar, tabel utama sejajar kiri-kanan.
- Tabel utama tetap compact.

Mobile:

- Halaman tidak boleh melebar ke kanan.
- Filter menjadi satu kolom.
- Tombol filter menjadi dua kolom atau full width jika ruang sempit.
- Hanya `.table-wrap` yang boleh scroll horizontal.
- Tabel ringkasan per unit min-width sekitar 520px.
- Tabel siswa min-width sekitar 760px.
- Aksi ikon tetap mudah disentuh.

## Canvas dan Footer

- Kanvas utama #ffffff.
- Footer copyright memakai format `© {tahun berjalan} Ma'wa Center`.
- Footer 14px / 400 / #707971 / rata tengah.
- Footer background transparan atau #ffffff.
- Footer tidak memakai #fbfdf8 sebagai area kosong besar.

## CSS

CSS Menu Tagihan sebaiknya dipisahkan bertahap dari `resources/css/app.css` ke file module:

```text
resources/css/modules/bills.css
```

Aturan:

- Selector aktif harus scoped ke `.bill-flat-page` atau class Tagihan yang relevan.
- Jangan menambah override baru yang menumpuk.
- Hapus aturan lama yang bertentangan saat refactor CSS dilakukan.
- `resources/css/app.css` cukup mengimport module Tagihan jika struktur module sudah dibuat.
- Jangan menyentuh CSS Menu Pembayaran atau Menu Laporan saat merapikan Tagihan.

## Testing

Test yang perlu ada:

- Admin bisa melihat tagihan semua unit.
- Bendahara Unit hanya melihat tagihan unit yang diizinkan.
- Wali Santri hanya melihat tagihan anak yang terhubung.
- Filter Unit Pendidikan bekerja.
- Filter Kelas bekerja.
- Cari Siswa berdasarkan nama/NIS/NISN bekerja.
- Tabel ringkasan per unit menampilkan total siswa dan jumlah tagihan.
- Tabel utama menampilkan kolom: No, NIS, Nama Siswa, Unit, Kelas, Total Tagihan, Aksi.
- Tagihan dengan `remaining_amount = 0` tidak tampil di tabel utama.
- Tagihan `Dibatalkan` tidak tampil.
- Laundry tidak tampil sebagai tagihan.
- Tombol Detail membuka surat tagihan siswa.
- Tombol Bayar mengarah ke Menu Pembayaran dengan siswa terpilih.
- Surat tagihan menampilkan rincian dan Total Keseluruhan.
- Empty state tampil saat tidak ada tagihan.
- Pagination memakai bahasa Indonesia dan tidak memakai teks bawaan Inggris.

## Urutan Implementasi

1. Rapikan standar aplikasi yang masih konflik tentang pagination Tagihan.
2. Audit visual Menu Tagihan terhadap PRD ini dan standar aplikasi.
3. Tahap 1: rapikan struktur Blade agar urutan halaman sesuai PRD.
4. Tahap 2: rapikan tabel ringkasan per unit dan tabel utama.
5. Tahap 3: rapikan filter dan toolbar/pagination.
6. Tahap 4: rapikan halaman Surat Tagihan.
7. Tahap 5: pisahkan CSS Tagihan ke `resources/css/modules/bills.css`.
8. Tahap 6: audit fungsional hak akses, query, dan test.
9. Tahap final: visual polish desktop/mobile dan build.

## Kriteria Selesai

Menu Tagihan dianggap siap jika:

- PRD, standar, dan implementasi tidak saling bertentangan.
- Belum bayar/tunggakan tidak muncul sebagai menu laporan.
- Ringkasan per unit tampil sebagai tabel, bukan card besar.
- Filter utama ringkas: Unit Pendidikan dan Kelas.
- Cari Siswa berada di toolbar atas tabel Tagihan Siswa.
- Tabel utama hanya berisi kolom final yang disepakati.
- Detail/surat tagihan siap cetak dan bisa lanjut ke pembayaran.
- Admin, Bendahara Unit, dan Wali Santri hanya melihat data sesuai akses.
- Laundry tidak muncul sebagai tagihan selama masih opsional.
- CSS Tagihan tidak menumpuk di `app.css` secara liar.
- Mobile tidak overflow kecuali di `.table-wrap`.
- Test relevan lulus.
