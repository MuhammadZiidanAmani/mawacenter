# Design System CSS dan Tailwind MA'WA CENTER

Versi: 1.2 | Tanggal: 18 Oktober 2026 | Pasangan dokumen: [PRD induk](PRD-MAWA-CENTER.md).

Panduan ini menggabungkan standar visual sumber S06, ketentuan khusus PRD modul, referensi layout Pembayaran, dan struktur CSS aplikasi saat ini. Tujuannya menjaga UI administrasi yang konsisten dan membantu AI memilih class, token, serta file yang benar.

**Status:** kontrak visual aktif. `resources/css/app.css` memuat token global dan modul CSS; `resources/css/theme.css` menghubungkan utility Tailwind ke token tersebut. `ai/tailwindadmin-react-1.0.0/` adalah referensi visual utama, bukan dependency runtime. [tailwind-theme.reference.css](tailwind-theme.reference.css) adalah salinan contoh adapter, tidak diimpor produksi.

## Daftar Isi

1. [Fondasi dan arah visual](#1-fondasi-dan-arah-visual)
2. [Token warna dan tema](#2-token-warna-dan-tema)
3. [Tipografi dan format data](#3-tipografi-dan-format-data)
4. [Layout dan responsif](#4-layout-dan-responsif)
5. [Kontrak komponen](#5-kontrak-komponen)
6. [Pola halaman](#6-pola-halaman)
7. [Pola Tailwind v4](#7-pola-tailwind-v4)
8. [Print dan export](#8-print-dan-export)
9. [Arsitektur CSS dan migrasi](#9-arsitektur-css-dan-migrasi)
10. [QA visual dan interaksi](#10-qa-visual-dan-interaksi)
11. [Cakupan standar sumber](#11-cakupan-standar-sumber)

## 1. Fondasi dan Arah Visual

- Produk adalah workspace administrasi, bukan landing page. Prioritaskan pencarian, filter, tabel, nominal, serta aksi yang sering dipakai.
- Putih/netral adalah kanvas dominan; biru Tailwind Admin adalah warna primer untuk CTA, navigasi aktif, dan fokus. Hijau/teal dipakai khusus untuk status sukses. Bahaya, peringatan, dan info punya warna semantik sendiri.
- Referensi Tailwind Admin menentukan bahasa visual dan palet. Tata letak operasional Laravel tetap dipertahankan.
- Gunakan satu keluarga font DM Sans dan hierarki terbatas. Hindari hero, gradient/orb, shadow berat, atau kartu dekoratif untuk setiap section.
- Halaman memakai app-shell, sidebar, topbar, main-panel, konten, footer. Heading berada langsung pada konten; frame hanya untuk tool, form terkelompok, tabel, item berulang, atau dialog yang membutuhkannya.
- UI berbahasa Indonesia. Label menjelaskan data/aksi; jangan menambahkan paragraf promosi fitur, petunjuk teknis CSS, atau penjelasan penggunaan yang sudah jelas dari kontrol.
- Brand visual: `MA'WA CENTER`; teks naratif/footer: `Ma'wa Center`; copyright tahun berjalan, ringan, tanpa kartu.

### Implementasi Aktif

`package.json` memasang `tailwindcss` dan `@tailwindcss/vite` v4. `vite.config.js` memuat DM Sans. Entry `resources/css/app.css` memuat `@import "tailwindcss"`, token `--app-*`, dan CSS modul aktif. Shell global memakai sidebar putih berkelompok, state aktif primer lembut, serta topbar berbasis aksi. Pembayaran menjadi pola tabel pertama: heading ringan, toolbar/filter, card tabel ber-border tipis, badge status, dan overflow horizontal khusus tabel di layar kecil. Jangan menambahkan Tailwind CDN, `tailwind.config.js` v3, atau instalasi framework kedua.

## 2. Token Warna dan Tema

Sumber warna aktif: `:root` di `resources/css/app.css`. Gunakan variable tersebut dalam CSS modul dan adapter, bukan menyalin kode warna baru pada tiap selector.

| Peran | Token Aktif | Nilai Terang | Contoh Utility Setelah Adapter |
| --- | --- | --- | --- |
| Kanvas | `--app-canvas` | `#ffffff` | `bg-app-canvas` |
| Surface | `--app-surface` | `#ffffff` | `bg-app-surface` |
| Panel/empty lembut | `--app-panel-soft` | `#f8f9fc` | `bg-app-panel-soft` |
| Netral ringan | `--app-neutral-soft` | `#f5f5f5` | `bg-app-neutral-soft` |
| Field lembut | `--app-field-soft` | `#ffffff` | `bg-app-field-soft` |
| Teks utama | `--app-text` | `#1c2536` | `text-app-text` |
| Teks alternatif kompatibilitas | `--app-text-alt` | `#1c2536` | Gunakan hanya bila selector existing memerlukannya |
| Label | `--app-label` | `#2a3547` | `text-app-label` |
| Metadata | `--app-muted` | `#5a6a85` | `text-app-muted` |
| Sekunder kuat | `--app-muted-strong` | `#3b4a61` | `text-app-muted-strong` |
| Border | `--app-border` | `#dfe5ef` | `border-app-border` |
| Divider | `--app-border-soft` | `#edf0f5` | `border-app-border-soft` |
| Border lembut | `--app-border-green-soft` | `#d9e2f7` | `border-app-border-green-soft` |
| Primer | `--app-primary` | `#5d87ff` | `bg-app-primary`, `text-app-primary` |
| Hover primer | `--app-primary-hover` | Primer 80% + hitam, OKLab | `hover:bg-app-primary-hover` |
| Surface primer | `--app-primary-soft` | Primer 12%, transparan | `bg-app-primary-soft` |
| Sekunder | `--app-secondary` | `#49beff` | `bg-app-secondary` |
| Sukses | `--app-success` | `#13a88e` | `text-app-success` |
| Surface sukses | `--app-success-soft` | `#e8fbf7` | `bg-app-success-soft` |
| Selected/hover lembut kompatibilitas | `--app-success-subtle` | Alias `--app-primary-soft` | `bg-app-success-subtle` |
| Bahaya | `--app-danger` | `#ef4444` | `text-app-danger` |
| Hover bahaya | `--app-danger-hover` | `#dc2626` | `hover:bg-app-danger-hover` |
| Surface bahaya | `--app-danger-soft` | `#fef2f2` | `bg-app-danger-soft` |
| Peringatan | `--app-warning` | `#a16207` | `text-app-warning` |
| Surface peringatan | `--app-warning-soft` | `#fff8e6` | `bg-app-warning-soft` |
| Informasi | `--app-info` | `#8754ec` | `text-app-info` |
| Surface informasi | `--app-info-soft` | Informasi 12%, transparan | `bg-app-info-soft` |

Nominal baris memakai teks utama; nominal footer/total penting boleh primer. Biru primer untuk CTA, navigasi, fokus, dan chart; informasi memakai ungu sesuai template. Sukses dan peringatan mempertahankan warna semantik aplikasi yang lebih gelap daripada aksen mint/kuning template agar label tidak memakai aksen terang. Ini penyesuaian aplikasi, bukan klaim semua pasangan warna sudah lolos uji kontras. Jangan memakai `slate-50` atau `slate-500` sebagai default aplikasi.

Token muted/success/danger tetap perlu pemeriksaan kontras menurut background dan ukuran aktual; keberadaan warna di standar bukan bukti lolos aksesibilitas. Status wajib memiliki teks, tidak hanya warna. Jangan mengubah token global hanya untuk memperbaiki satu layar tanpa audit lintas modul.

### Mode Gelap

Mode gelap aktif melalui selector `html[data-theme="dark"]`. Semua komponen aplikasi mengambil warna dari token di `resources/css/app.css`; CSS modul tidak boleh memiliki warna hex/RGBA sendiri.

| Peran | Light | Dark |
| --- | --- | --- |
| Canvas | `#ffffff` | `#1c2536` |
| Surface/sidebar/topbar/card | `#ffffff` | `#1c2536` |
| Panel lembut | `#f8f9fc` | `#2a3851` |
| Field | `#ffffff` | `#1c2536` |
| Border | `#dfe5ef` | `#333f55` |
| Teks utama/label | `#1c2536` / `#2a3547` | `#ffffff` |
| Teks muted | `#5a6a85` | `#7c8fac` |
| Primer | `#5d87ff` | `#5d87ff` |
| Surface primer | Primer 12%, transparan | Primer 20%, transparan |
| Informasi | `#8754ec` | `#8754ec` |

Status sukses, bahaya, peringatan, dan informasi memakai warna semantik Tailwind Admin dengan surface transparan 20% pada dark mode. Shadow komponen dimatikan pada dark mode agar kedalaman berasal dari border `#333f55`, spacing, dan state primer. `--app-panel-soft` hanya dipakai untuk header tabel dan permukaan muted; bukan sebagai latar canvas halaman.

`resources/views/partials/theme-toggle.blade.php` menempatkan tombol ikon bulan/matahari pada topbar. Kode UI mandiri di `resources/js/app.js`:

- mengubah `html[data-theme]` antara `light` dan `dark`;
- menyimpan pilihan pengguna pada `localStorage` dengan key `mawa-center-theme`;
- memakai preferensi sistem ketika belum ada pilihan tersimpan;
- mengikuti perubahan preferensi sistem hanya saat pengguna belum memilih tema;
- memperbarui `aria-label`, `title`, `aria-pressed`, dan warna tema browser.

Login tanpa topbar tetap mengikuti token tema yang sama. Template PDF/struk dan media cetak tidak memakai token tema aplikasi dan tetap putih.

## 3. Tipografi dan Format Data

| Peran | Ukuran / Line Height | Weight | Warna |
| --- | --- | --- | --- |
| Body/input/tabel | 14 / 20px | 400 | Teks utama atau muted sesuai peran |
| Label form/header tabel/tombol | 14 / 20px | 600 | Label/teks utama |
| Metadata/helper text | 12 / 16px | 400 atau 500 | Muted |
| Status/footer total | 14 / 20px | 600 atau 700 | Sesuai aksi/status |
| Judul section/dialog | 18 / 26px | 700 | Teks utama |
| Judul halaman/total utama | 24 / 32px | 700 | Teks utama/primer |
| Micro-label Pembayaran saja | 12 / 16px | 600 | Label/muted, bukan body |
| Nama/item/tombol Pembayaran | 14 atau 16 / 20 atau 24px | 600 | Teks utama, sesuai revisi khusus |

Default global memakai DM Sans dengan 14/20px untuk body, 12/16px untuk metadata, 18/26px untuk section, 24/32px untuk judul halaman, serta 20/28px untuk total utama. Gunakan 400 untuk isi, 500 untuk metadata yang perlu sedikit penekanan, 600 untuk label/header tabel/tombol, dan 700 untuk heading/total. Token aktifnya adalah `--app-text-body-*`, `--app-text-meta-*`, `--app-text-section-*`, `--app-text-page-*`, dan `--app-text-total-*`. Pengecualian 12px/600 pada Pembayaran hanya berlaku untuk micro-label, bukan body. Aturan lama 13/24/28px dicatat untuk audit, bukan standar utility baru. Jangan memakai font-weight 800.

Font-family mengikuti `--app-font`. Letter spacing 0; ukuran tidak menggunakan `vw`/`clamp()` berbasis lebar viewport. Bungkus teks panjang, pakai `min-w-0`; tabel compact boleh ellipsis dengan akses nama lengkap dari tooltip/detail. Angka keuangan dan kode menggunakan `tabular-nums`.

Pengecualian teknis hanya berlaku untuk glyph ikon pada kontrol berukuran tetap, misalnya panah sort atau tombol close. Glyph tersebut boleh memakai `line-height: 1` agar berada di tengah tombol; teks yang dapat dibaca pengguna tetap memakai token line-height di atas. PDF/struk mengikuti aturan cetak pada bagian 8 dan tidak termasuk CSS halaman aplikasi.

- Rupiah legacy sumber: `Rp. 50.000,-`; pertahankan formatter modul existing agar layar/struk/export tidak berubah diam-diam. Contoh dokumen dapat memakai `Rp 50.000`, tetapi normalisasi seluruh aplikasi merupakan pekerjaan tersendiri.
- Input angka boleh menampilkan pemisah ribuan; server menerima nilai numerik yang divalidasi. Jangan menghitung keuangan dari string hasil format.
- Tanggal tabel `19/06/2026`, waktu `09.45 WIB`, periode `Juli 2026`, dokumen dapat memakai tanggal panjang.
- NIS/NISN/telepon/rekening adalah identitas tekstual; nol awal tidak boleh hilang saat import/export.
- Kode unit mengikuti kapitalisasi resmi `MTs`, bukan otomatis diubah menjadi `MTS`.

## 4. Layout dan Responsif

| Konteks | Dimensi |
| --- | --- |
| Form/tool sederhana | Maksimum 720px |
| Tabel/data/transaksi standar | Maksimum 1200px |
| Dashboard padat | Maksimum 1440px |
| Padding desktop | 24px vertikal, 32px horizontal |
| Padding tablet | 20px vertikal, 24px horizontal |
| Padding mobile | 16px |
| Judul ke deskripsi | 4px |
| Heading ke isi/filter | 16px |
| Antarsection | 20px desktop, 16px mobile |
| Gap field/grid | 12px/16px |
| Gap tombol | 8px |
| Radius tombol/kontrol aksi | 10px |
| Field dan panel filter | Field 12px; panel 12px dengan border tipis |

Skala spacing 4, 8, 12, 16, 20, 24, 32px. Heading/filter/toolbar/tabel/pagination pada satu halaman memakai batas kiri-kanan yang sama. Container boleh terpusat, teks tetap rata kiri. Nominal rata kanan; No/kode/jumlah pendek/aksi rata tengah.

CSS existing memiliki breakpoint 640, 700, 760, 850, 900, 960, dan 1180px sesuai modul. Jangan menyamakan `md:` bawaan Tailwind dengan 760px atau mengganti semua breakpoint sekaligus. Pertahankan breakpoint halaman yang diubah; contoh pola baru dapat memakai `min-[761px]:`/`min-[1181px]:` bila diperlukan.

Mobile: sidebar drawer dengan overlay, filter/form satu kolom, tombol wrap atau full width, area transaksi bertumpuk. Set `min-width:0` pada anak flex/grid. Hanya wrapper tabel yang boleh scroll horizontal; jangan menyembunyikan overflow pada seluruh body untuk menutupi layout rusak. Nama, nominal panjang, dan loading tidak boleh menggeser dimensi kontrol.

## 5. Kontrak Komponen

### Navigasi dan Footer

Sidebar/topbar putih dengan border. Normal teks label; hover dan aktif memakai teks primer dengan `--app-primary-soft` (alias kompatibilitas `--app-success-subtle`). Item navigasi bukan tombol primer solid. Ikon memakai `currentColor`, parent expanded/active konsisten, focus terlihat. Footer copyright dinamis, 14px normal/muted, putih/transparan tanpa panel dekoratif.

### Form dan Filter

- Input/select 40px, padding horizontal 12px, radius field 12px, border token, font 14px. Textarea minimal 96px dan resize vertikal.
- Geometri field native dimiliki `resources/css/components.css`; perubahan tinggi, padding, dan radius dilakukan melalui token `--app-field-*`/`--app-radius-field`, bukan selector halaman. Field gabungan memakai `.app-field-shell`; alias class lama dipetakan pada kontrak yang sama selama migrasi.
- Label field memakai `.app-field`. Upload import memakai `.app-upload-dropzone`, `.app-upload-input`, `.app-upload-icon`, `.app-upload-copy`, dan `.app-upload-actions`; modul halaman hanya mengatur penempatan atau state bisnisnya.
- Label terhubung ke field, jarak 6-8px. Field wajib memiliki penanda yang jelas tanpa membuat seluruh label merah.
- Focus ring/outline 2px primer dengan offset yang sesuai. Error di bawah field, 14px danger, `aria-invalid` dan `aria-describedby` bila diperlukan; fokus ke error pertama.
- Readonly tetap terbaca dan dapat disalin; disabled opacity sekitar 0.55 tanpa hover aktif. Nominal Lunas memakai readonly bila nilainya perlu dikirim.
- Unit mengendalikan kelas, nilai kelas invalid direset. Reset hanya membersihkan filter, tidak menyentuh data. Per_page/sort/direction tetap terbawa saat aksi yang mempertahankan filter.
- Panel filter adalah tool mandiri, tidak di dalam kartu lain: memakai surface, border, padding 16px, gap 12px, radius panel 12px. Desktop grid align-end, mobile satu kolom.
- Acuan desktop Siswa: Unit 160px, Kelas 150px, Search 220-300px, aksi max-content. Kategori: Unit+Search; Keringanan: Unit+Kelas+Search. Status tidak ditambahkan sebagai filter utama kategori/keringanan.

### Tombol dan Ikon

| Jenis | Kontrak |
| --- | --- |
| Primer | 40px, padding 14px, radius 7px, primary/white, hover primary-hover; satu aksi dominan per kelompok |
| Sekunder netral/reset | Putih, border, teks label/muted-strong |
| Sekunder lembut | Surface putih atau selected primer lembut; bukan alasan mengubah semua reset menjadi primer |
| Bahaya | Danger solid hanya pada konfirmasi berisiko; aksi hapus tabel berupa ikon merah |
| Ikon standar | 40 x 40px, ikon 18px, `aria-label` dan `title` |
| Ikon tabel | 32 x 32px desktop; target sentuh mobile minimal 40px, baris boleh bertambah tinggi |
| Pagination | 36px, radius 7px, gap 8px; halaman aktif primary/white |
| CTA pembayaran | 40px atau 44px bila diperlukan, full width pada mobile |

Gunakan `.button`, `.button-primary`, `.button-secondary`, `.icon-button`, `.icon` yang ada sebelum membuat sistem komponen kedua. Ikon aplikasi sekarang memakai helper SVG Blade; gunakan ikon existing. Lucide hanya bila sudah diintegrasikan, jangan mengasumsikan ada dependency atau komponen `<x-icon>`.

Tombol pagination aplikasi memakai `.app-page-button`; class lama per modul boleh tetap menjadi alias sementara, tetapi tidak boleh memiliki ulang warna, border, radius, atau state hover/aktif sendiri.

Untuk aksi tool gunakan ikon yang umum: printer, download, edit, trash, receipt/detail. Teks/icon+teks untuk perintah jelas seperti Simpan/Terapkan/Bayar. Tipe pembayaran memakai segmented radio, seleksi memakai checkbox, mode biner toggle/checkbox, opsi memakai select/menu, angka memakai input/stepper. Ikon dekoratif `aria-hidden=true`.

Loading mempertahankan ukuran tombol dan mencegah klik ulang. Focus tidak hanya perubahan warna; disabled bukan merah. Animasi pendek dan `prefers-reduced-motion` dihormati. Efek hover legacy Data Siswa dapat dipertahankan, tetapi jangan memperkenalkan pergeseran layout.

### Tabel, Toolbar, dan Pagination

Tabel untuk siswa, master, transaksi, rekap, tagihan: font body 14/20px weight 400, header 14/20px weight 600 Title Case, footer 14/20px weight 700. Compact header/baris 40px; detail dua baris boleh 44-56px. Tidak memaksa baris tetap 40px bila memotong konten/target sentuh.

Wrapper memakai surface, border 1px, radius panel 12px, dan shadow token; garis tabel horizontal halus, tanpa garis vertikal. `table-layout:fixed` bila perlu kestabilan; `min-width` hanya sebesar kebutuhan kolom. Baris akhir tidak memiliki divider ganda. Header/hover memakai token tabel yang bersumber dari panel-soft dan hover-surface.

Semua `<table>` di dalam `.app-shell main` otomatis mengikuti kontrak tabel pada `resources/css/components.css`. Model bersama—padding sel, header, divider, hover, tipografi, footer, dan frame—diubah melalui token `--app-table-*`. CSS modul hanya boleh mengatur `min-width`, `table-layout`, lebar kolom, alignment data khusus, serta baris detail/expanded. Tabel struk, surat, PDF, dan print berada di luar shell dan tidak mengikuti kontrak tabel aplikasi.

Header default tengah; nama/deskripsi isi kiri; nominal isi kanan; jumlah siswa/transaksi/no/kode pendek tengah. Footer Total Keseluruhan satu `tfoot`, nominal primer dan kanan, jumlah non-uang teks utama dan tengah, label `colspan` sesuai kolom. Total seluruh hasil filter tidak boleh berubah menjadi total halaman saja.

Toolbar tanpa kartu: jumlah data kiri, pencarian siswa kanan pada laporan per siswa/Tagihan. Select 78 x 34px; opsi 10/25/50/100/500/Semua mengikuti dukungan backend. Default 10. Jangan mengaktifkan mode Semua pada dataset besar tanpa batas/strategi query.

Cari Siswa toolbar: input 36px, lebar acuan 280px, label sejajar desktop; satu kolom/full width mobile. Pertahankan konteks filter dan clear pilihan. Rekap Per Unit tidak memakai Cari Siswa.

Jarak toolbar-tabel 16px hanya sekali, bukan gap+margin ganda. Footer kiri `Menampilkan 1-10 dari 100 data`; navigasi kanan hanya jika perlu. Tanpa `Showing ... results` atau duplikasi `Halaman x dari y`. Mobile footer bertumpuk dan tombol wrap.

Sorting hanya kolom yang benar-benar didukung server; ikon tidak membesarkan header dan gunakan `aria-sort` pada kolom aktif. Empty row `colspan` sesuai jumlah kolom, muted, tidak memaksa lebar tabel kosong. Loading/error tabel berada di area data dengan Coba Lagi bila relevan.

### Status, Dialog, dan Feedback

Status selalu teks+warna: Diterima/sukses, Pending/peringatan, Ditolak/error, netral/nonaktif. Jangan menjadikan badge metadata biasa. Arti status pembayaran berbeda dari status pelunasan Lunas/Sebagian/Belum Bayar; jangan mencampur enum.

Modal untuk konfirmasi/form pendek: radius 7px, padding 24px desktop/16px mobile, lebar 420px kecil, 560px form, maksimal 720px. Backdrop terang `rgba(2,6,23,.38)`. Tabel besar, surat, dan detail resmi memakai halaman sendiri.

Modal memiliki judul, `aria-modal`, fokus masuk/terkunci/kembali ke pemicu, Batal/close. Escape/backdrop boleh menutup ketika tidak loading. Konfirmasi pembayaran menyebut siswa/periode/metode/nominal; import menyebut konteks/jumlah valid/gagal; alumni/perubahan kelas menyebut jumlah terdampak. Hapus/batal berisiko menyebut objek dan dampak.

Toast singkat 1-2 baris, 3-5 detik untuk sukses; error penting bertahan sampai ditutup. Inline alert untuk informasi sebelum tindakan. Jangan tampilkan pesan yang sama sebagai toast dan alert sekaligus. Empty netral, error dekat konteks, success seperlunya; loading tidak mengganti seluruh halaman bila hanya tabel sedang dimuat.

### Checkbox dan Nilai Finansial

Seleksi tabel/massal mempertahankan input native dan perilaku keyboard. Bentuk khusus pada daftar tagihan Pembayaran mengikuti referensi, tetapi tetap checkbox sungguhan, label dapat diklik, focus jelas, dan checked tidak hanya ditunjukkan warna. Pilih Semua wajib tidak otomatis memilih Laundry/opsional.

Nominal memakai tabular angka, total lebih kuat dari item, `min-w-0` dan wrapping terkendali. Hindari `whitespace-nowrap` pada nominal panjang di layar sempit bila menyebabkan overflow. Progress statistik hanya pelengkap angka/teks, bukan satu-satunya informasi pembayaran.

## 6. Pola Halaman

| Jenis | Urutan dan Ketentuan |
| --- | --- |
| Data Siswa/master/alumni | Heading+aksi -> filter -> toolbar -> tabel -> footer/pagination |
| Tambah/edit | Heading -> form berkelompok -> error per field -> Batal/Simpan |
| Identitas | Filter kandidat -> daftar -> halaman tinjau personal/unit -> konfirmasi gabung/pisah |
| Pindah/Naik Kelas | Konteks sumber -> target -> seleksi tabel -> tinjau jumlah -> konfirmasi |
| Pembayaran belum dipilih | Heading/Import Excel -> pencarian ringkas -> empty state siswa |
| Pembayaran terpilih | Konteks siswa -> tiga statistik -> kasir kiri/tagihan kanan -> riwayat bawah kasir; satu kolom <=1180px |
| Tagihan | Heading -> rekap unit -> filter unit/kelas -> toolbar/search -> tabel siswa -> pagination |
| Laporan transaksi/bulanan | Heading -> filter -> rekap unit -> toolbar/search -> detail -> footer/export |
| SPP Pertahun | Heading -> filter -> toolbar/search -> matriks Juli-Juni; tanpa rekap/kartu tambahan |
| Rekap Per Unit | Heading -> tanggal -> tabel kategori+footer; tanpa Cari Siswa |
| Import | Heading -> konteks+file/template -> preview+status baris -> konfirmasi -> hasil |
| Dashboard | Heading -> statistik penting -> tren/prioritas -> aktivitas; bukan hero |
| Pengaturan | Heading -> kelompok akun/konfigurasi sesuai izin -> simpan |
| Surat/struk | Toolbar layar -> dokumen resmi; toolbar hilang saat print |

Referensi [Setelah.jpeg](Setelah.jpeg) khusus untuk susunan Pembayaran. Kartu statistik transaksi merupakan pengecualian yang diminta pengguna; jangan memindahkannya ke laporan yang sudah memiliki footer total. Riwayat kasir tetap singkat; seluruh histori tidak ditempelkan ke workspace pembayaran.

## 7. Pola Tailwind v4

### Adapter Token

Tailwind `@theme inline` di `resources/css/theme.css` memetakan utility ke CSS variable existing. Alias `bg-primary` dan `bg-app-primary` mengambil `--app-primary` yang sama, termasuk mode gelap. Contoh terpisah ada di [tailwind-theme.reference.css](tailwind-theme.reference.css). Panduan sintaks: [Theme Variables](https://tailwindcss.com/docs/theme).

```css
/* Contoh setelah @import "tailwindcss" pada integrasi yang diaudit. */
@theme inline {
    --color-app-primary: var(--app-primary, #5d87ff);
    --color-app-surface: var(--app-surface, #ffffff);
    --color-app-text: var(--app-text, #020617);
}
```

Jangan mengimpor file `ai/` sebagai dependency produksi. Adapter produksi sudah diimpor sekali melalui entry CSS; definisi token tetap di `app.css`. Hindari mendefinisikan ulang palet pada setiap modul.

Adapter mencakup `font-app`, `text-ui-body`, `text-ui-section`, `text-ui-title`, `text-ui-micro`, `rounded-ui`, `max-w-ui-form`, `max-w-ui-data`, dan `max-w-ui-dashboard`. Ukuran tetap, bukan skala viewport.

### Contoh Layout

Contoh untuk konten di dalam shell existing, bukan membuat shell kedua. Ini pola visual, bukan Blade page lengkap dengan data/route.

```html
<section class="mx-auto grid w-full min-w-0 max-w-ui-data gap-4 font-app text-ui-body text-app-text">
    <header class="flex min-w-0 flex-wrap items-start justify-between gap-3">
        <h1 class="min-w-0 text-ui-title font-bold">Data Siswa</h1>
        <!-- Gunakan tombol route/action existing sesuai permission. -->
    </header>
    <div class="min-w-0 overflow-x-auto rounded-ui border border-app-border bg-app-surface">
        <table class="w-full border-collapse text-ui-body">
            <caption class="sr-only">Data siswa pada filter aktif</caption>
            <thead class="bg-app-panel-soft text-app-label">
                <tr>
                    <th scope="col" class="h-10 px-2.5 py-2 text-center font-medium">NIS</th>
                    <th scope="col" class="h-10 px-2.5 py-2 text-left font-medium">Nama</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="p-4 text-app-muted">Belum ada data siswa pada filter ini.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
```

Nama header boleh kiri pada contoh tabel sangat ringkas; pada tabel modul dengan ketentuan header tengah, ikuti kontrak modul. Jangan menjadikan contoh ini alasan mengubah semua header.

### Contoh Kontrol

Tombol di bawah adalah pola style. Saat digunakan, letakkan di form existing dengan CSRF, validasi, dan handler yang benar; jangan menambahkan tombol tanpa aksi.

```html
<button type="submit"
    class="inline-flex h-10 min-w-[88px] items-center justify-center gap-2 rounded-ui border border-app-primary bg-app-primary px-3.5 text-ui-body font-semibold text-white transition-colors hover:border-app-primary-hover hover:bg-app-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-app-primary disabled:pointer-events-none disabled:opacity-55 motion-reduce:transition-none">
    Simpan
</button>

<label for="student-search" class="grid min-w-0 gap-2 text-ui-body font-medium text-app-label">
    Cari Siswa
    <input id="student-search" name="search" type="search" placeholder="Nama / NIS / NISN"
        class="h-10 w-full min-w-0 rounded-ui border border-app-border bg-app-surface px-3 text-ui-body font-normal text-app-text placeholder:text-app-muted focus:border-app-primary focus:outline-2 focus:outline-offset-0 focus:outline-app-primary/20">
</label>
```

Nama parameter `search` hanya ilustrasi; pertahankan nama request aktual pada modul ketika mengadopsi style. Jangan mengubah `name`, `data-*`, ID, route, atau state JS hanya karena mengganti class visual.

### Class Dinamis dan Sumber

Gunakan class lengkap pada mapping status Blade/JS, bukan interpolasi seperti `bg-{{ $color }}-100`. Scanner membaca class sebagai teks; mapping statis membuat utility ditemukan. Lihat [Detecting Classes](https://tailwindcss.com/docs/detecting-classes-in-source-files).

```blade
@php
    $statusClass = match ($status) {
        'Diterima' => 'bg-app-success-soft text-app-success',
        'Pending' => 'bg-app-warning-soft text-app-warning',
        'Ditolak' => 'bg-app-danger-soft text-app-danger',
        default => 'bg-app-neutral-soft text-app-muted',
    };
@endphp
<span class="inline-flex rounded-ui px-2 py-1 text-ui-body font-medium {{ $statusClass }}">{{ $status }}</span>
```

Jika class tidak masuk build, cek lokasi source dan hasil CSS terlebih dahulu. Entry produksi memakai `@import "tailwindcss" source(none)` dengan source eksplisit `../views`, `../js`, dan `../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views`. Path relatif terhadap `resources/css/app.css`. Referensi React dalam `ai/` tidak ikut menghasilkan utility produksi. Tambahkan source bila runtime baru membutuhkan utility di luar lokasi tersebut.

### Dark Variant

Adapter menyediakan variant sesuai attribute aplikasi:

```css
@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));
```

Gunakan token untuk warna umum; variant `dark:` untuk perbedaan yang benar-benar diperlukan. Directive tidak membuat toggle atau menyimpan preferensi. Rujukan: [Dark Mode](https://tailwindcss.com/docs/dark-mode).

## 8. Print dan Export

Print/PDF adalah dokumen resmi, bukan screenshot kartu aplikasi. Background putih, teks hitam, garis tipis, tanpa shadow/radius dekoratif atau kontrol UI. Nominal kanan/tabular, total tegas, identitas/periode/tanggal/petugas konsisten; terbilang bila dokumen memerlukannya.

- Surat Tagihan: setengah F4 portrait 165 x 215mm, judul Penertiban Administrasi Keuangan, siswa, rincian, total, terbilang, tanda tangan.
- SPP Pertahun: A4 landscape; bulan Juli-Juni dengan nama lengkap.
- Laporan lain: A4 portrait kecuali tabel membutuhkan landscape. Ulangi header tabel pada halaman lanjutan bila didukung renderer.
- Struk: lebih ringkas, transaksi/siswa/item/total/metode/waktu/petugas/referensi. Font boleh 11.5/12.5/14/18px menurut kebutuhan cetak.
- Toolbar Kembali/Bayar/Unduh/Cetak hanya untuk layar dan mengikuti izin.
- XLSX menyimpan nominal numerik, NIS/kode sebagai teks, header jelas, footer total bila perlu, dan hasil sesuai filter/permission.

Gunakan CSS sederhana dan Blade PDF existing untuk Dompdf; jangan bergantung pada utility browser, flex/grid kompleks, atau CSS custom properties yang belum terbukti pada PDF renderer. CSS print dibatasi dokumen terkait, bukan `@page` global untuk semua jenis kertas.

```css
/* Contoh di stylesheet/view Surat Tagihan saja. */
@media print {
    @page {
        size: 165mm 215mm;
        margin: 8mm;
    }

    .bill-notice-toolbar {
        display: none;
    }

    .bill-notice-document {
        color: #000000;
        background: #ffffff;
        box-shadow: none;
    }
}
```

Nama class contoh disesuaikan dengan view yang dikerjakan; ukuran dokumen tetap kontrak produk.

## 9. Arsitektur CSS dan Migrasi

### Pemilik File

| File | Pemilik Aturan |
| --- | --- |
| `resources/css/app.css` | Import, token, reset/foundation, app-shell/sidebar/topbar, modal, dan footer bersama |
| `resources/css/theme.css` | Adapter utility Tailwind ke token aplikasi dan variant dark berdasarkan `data-theme` |
| `resources/css/components.css` | Kontrak visual lintas menu untuk field, field gabungan, filter, card dasar, surface/isi tabel, pagination, dan bentuk status |
| `modules/login.css` | Login |
| `modules/dashboard.css` | Dashboard |
| `modules/student-management.css` | Siswa, alumni, perubahan kelas, identitas |
| `modules/master.css` | Master data dan form bersama sesuai scope |
| `modules/payments.css` | Kasir, pilihan siswa/tagihan, import/riwayat pembayaran |
| `modules/bills.css` | Tagihan dan portal tagihan sesuai scope |
| `modules/reports.css` | Laporan |
| `modules/finance.css` | Komponen keuangan terkait sesuai penggunaan aktual |
| `modules/cashier.css` | Form SPP/pembayaran lain, riwayat, pencarian siswa, dan import kasir |
| `modules/settings.css` | Pengaturan |

Jangan menghapus class global `.modal-backdrop`, `.form-modal`, `.result-modal-backdrop`, `.app-footer`, `.button`, `.icon-button`, `.table-wrap` tanpa audit penggunaannya. Selector `student` di Pembayaran belum tentu milik Manajemen Siswa.

### Cascade Aktual

Urutan import `app.css`: Tailwind -> adapter tema -> modul halaman -> `components.css`; definisi token dan foundation shell berada setelah import. `components.css` sengaja dimuat setelah modul agar tampilan komponen lintas menu memiliki satu pemilik. Reset elemen/form berada dalam `@layer base`, sedangkan modul halaman hanya mengatur layout, ukuran khusus, dan state yang memang berbeda. Hindari mendefinisikan ulang background, border, radius, padding sel, header, atau hover tabel di modul. CSS lama tidak diimpor.

Utility Tailwind berada dalam cascade layer. Jangan memakai `!important` atau `style` inline pada halaman aplikasi; gunakan token dan class yang ter-scope. Nilai progress yang berubah saat interaksi boleh diatur oleh JavaScript existing, bukan atribut `style` di Blade. Urutan class pada atribut HTML bukan jaminan urutan kemenangan CSS.

### Urutan Migrasi

1. Tentukan satu halaman dan state yang akan diubah; catat baseline desktop/mobile, terang/gelap jika tersedia, serta print bila terkait.
2. Cari selector dan pemakai di Blade/JS, periksa import/override terakhir. Jangan mengubah nama state `data-*` yang dipakai JavaScript tanpa kebutuhan.
3. Gunakan token existing; integrasikan adapter sekali pada stylesheet produksi bila utility semantik dibutuhkan.
4. Pindahkan aturan milik modul bersama dependensi cascade; hilangkan duplikasi hanya setelah aturan pengganti terbukti.
5. Ubah satu kelompok komponen, build dan periksa CSS output/computed style, tes perilaku bila markup/JS ikut berubah.
6. Verifikasi seluruh state dan menu yang berbagi foundation; jangan menambah selector berantai atau `!important`.

Pekerjaan visual berikutnya memakai urutan yang sama: perluas komponen bersama bila benar-benar dipakai lintas modul, kemudian ubah CSS pada pemilik halaman. Pertahankan `.payment-one-stop-student-*`, `.payment-spp-student-*`, `.payment-receipt-student`, dan pencarian di scope Pembayaran pada pemilik pembayaran.

Jangan menambah `body *`, aturan semua `strong/th/button/span`, atau seluruh elemen `!important` untuk memperbaiki satu menu. Jangan melakukan cleanup lintas modul bersama hotfix keuangan yang tidak memerlukannya.

## 10. QA Visual dan Interaksi

| Area | Pemeriksaan |
| --- | --- |
| Desktop | 1366px dan layar lebar; heading/filter/tabel sejajar, baris/aksi tidak bergeser |
| Mobile/tablet | 360px, 390-430px, 768px; filter/tombol wrap, drawer berfungsi, scroll hanya wrapper |
| Batas layout | Sekitar 760px dan 1180px; urutan kasir/tagihan/riwayat sesuai spesifikasi |
| Data ekstrem | Nama panjang, unit gabungan, nominal besar, kosong, banyak baris, error validasi |
| Kontrol | Normal, hover, focus-visible, pressed, readonly, disabled, loading; ukuran stabil |
| Akses | Tombol/sidebar sesuai role; data tersembunyi tidak bocor lewat URL/response |
| Keuangan | Checklist/periode memperbarui total, Lunas/Cicil, Transfer/proof, fallback cetak |
| Dialog | Awalnya tersembunyi, fokus masuk/kembali, Escape/Batal, tidak tertutup viewport |
| Tema | Warna teks/field/status terbaca; layout identik; persistensi bila diimplementasikan |
| Print/export | Toolbar hilang, kertas sesuai, nominal/terbilang lengkap, tabel tidak terpotong |

Untuk perubahan CSS aktif jalankan `npm run build`, inspeksi browser dan screenshot sebelum/sesudah. Jika markup/JS/akses ikut berubah, jalankan feature test terkait dengan isolasi sesuai PRD. Screenshot/build tidak membuktikan keamanan data; feature test juga tidak membuktikan layout bebas overlap.

Dokumentasi dan adapter referensi cukup diperiksa link, token, dan kompilasi contoh. Jangan menjalankan migrasi/seed atau mengubah data operasional untuk memverifikasi desain.

### Audit Referensi 18 Oktober 2026

Audit source mencakup entry CSS, adapter, dan seluruh 10 modul aktif. Acuan lokal: `src/css/globals.css`, `src/css/app.css`, layout, serta komponen button/input/table/card/badge pada [Tailwind Admin](tailwindadmin-react-1.0.0/). Perbandingan mengikuti kontrak aplikasi untuk ukuran operasional, Bahasa Indonesia, dan dokumen resmi.

- Utility dan CSS modul memakai token tema yang sama; token `--app-secondary` dan `--app-primary-soft` yang sebelumnya dirujuk tanpa definisi sudah tersedia.
- Foundation berprioritas rendah; tombol khusus modul dan header tabel tidak tertimpa aturan bersama. Field input, select, textarea, pencarian, upload, dan kontrol jumlah data memakai token khusus 12px agar sisi field terlihat konsisten membulat; tombol dan kontrol aksi tetap 10px, sedangkan card/panel memakai 12px. Pagination 36px/gap 8px, aksi tabel 32px dengan target mobile 40px.
- Kontrak lintas menu dipusatkan di `components.css`. Lima belas view bertabel pada shell aplikasi mengikuti aturan tabel otomatis; delapan view struk/surat/PDF dipisahkan agar format cetaknya tidak berubah. Duplikasi visual tabel pada modul Pembayaran, Tagihan, Keuangan, Kasir, Master, Laporan, dan Manajemen Siswa dihapus; modul tersebut menyimpan kebutuhan struktur datanya saja.
- Toolbar daftar memakai kontrak `app-data-toolbar`, `app-page-size-form`/`app-page-size-control`, `app-data-search`, dan `app-data-search-field`. Pilihan jumlah data memiliki lebar tetap dari `--app-page-size-width`; area pencarian, posisi label, jarak, dan susunan mobile berasal dari token/komponen bersama. View baru wajib memakai class ini agar perubahan toolbar berlaku lintas menu tanpa override modul.
- Selector filter, pencarian, pagination Tagihan, portal wali, sorting, badge status, serta struktur chart Laporan dicocokkan dengan Blade aktif. Tidak semua class Blade membutuhkan selector tersendiri; sebagian merupakan penanda state/JS atau alternatif tampilan lama.
- File upload dan pilihan tersembunyi mendapat fokus yang terlihat; helper screen reader tidak muncul sebagai teks visual. Nominal panjang pada kasir mobile dapat membungkus.
- `settings.css` sudah menggunakan token dan struktur responsif yang sama, sehingga tidak memerlukan perubahan. CSS inline struk/surat/PDF tetap mengikuti kontrak cetak di bagian 8; template referensi tidak diimpor ke dokumen resmi.

Build, kompilasi alias utility/dark variant, audit token/import, dan feature test dijalankan pada perubahan ini. Pemeriksaan browser dark mode dilakukan pada halaman Pembayaran dan SPP Perbulan dalam viewport desktop 1918px serta mobile 430px: sidebar/topbar/card menyatu dengan canvas `#1c2536`, pembeda permukaan memakai border `#333f55`, menu aktif memakai primary transparan, filter mobile menumpuk satu kolom, dan tabel tetap berada dalam wrapper gulir. Toolbar kedua halaman memperlihatkan ukuran pilihan jumlah data, label, lebar pencarian, dan susunan mobile yang sama. Pemeriksaan ini mencakup representasi utama komponen bersama; dialog serta state data lain tetap perlu diperiksa saat modul tersebut diubah.

## 11. Cakupan Standar Sumber

| Kelompok Sumber S06/S10 | Lokasi Konsolidasi |
| --- | --- |
| Prinsip, keputusan cepat, warna, rumus implementasi | Bagian 1-3 |
| Sidebar/navigasi, identitas/copyright | Bagian 1, 5 |
| Foundation CSS, module, cleanup/cascade | Bagian 7, 9 |
| Tipografi, alignment, angka, tanggal | Bagian 3-5 |
| Spacing, canvas, jenis halaman, responsif | Bagian 4, 6 |
| Search/filter/reset, pengecualian master | Bagian 5; PRD bagian 6, 8, 10, 11 |
| Tabel detail/rekap/compact/empty/footer/sort/pagination | Bagian 5 |
| Card/panel, form, checkbox, validasi, disabled | Bagian 5 |
| Tombol, ikon, microcopy, konfirmasi | Bagian 1, 5 |
| Badge/status, modal/toast, aksesibilitas | Bagian 5, 10 |
| Role/hak akses, audit, import/export | PRD bagian 4-5, 6-10, 12-13; bagian 5, 8 di dokumen ini |
| Dashboard/chart | Bagian 1, 6; PRD bagian 11 |
| Pembayaran/Tagihan/Surat | Bagian 5-6, 8; PRD bagian 7-9 |
| Mode terang/gelap | Bagian 2, 7, 10 |
| QA/cleanup batch | Bagian 9-10; PRD bagian 14 |

Perbedaan khusus typography, radius, checkbox, tombol sekunder, dan layout dicatat di atas serta pada PRD bagian 3. Asal kebutuhan tercatat pada [inventaris sumber](README.md#inventaris-sumber). Dokumen lama sudah dihapus setelah konsolidasi dan dapat ditelusuri melalui riwayat Git; dokumen historis tidak menimpa keputusan konsolidasi.
