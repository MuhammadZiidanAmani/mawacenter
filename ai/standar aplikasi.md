Standar Aplikasi MA'WA CENTER

Dokumen ini menjadi acuan utama UI, UX, tipografi, warna, layout, komponen, responsif, dan interaksi aplikasi MA'WA CENTER. Gunakan dokumen ini sebagai standar tunggal saat membuat halaman baru atau merapikan halaman lama.

Prinsip Utama

Aplikasi memakai gaya operasional yang bersih, rapi, ringan, dan mudah dipindai. Tampilan harus terasa seperti sistem kerja internal: padat tetapi tetap nyaman, bukan landing page, bukan hero marketing, dan bukan tampilan dekoratif.

Arah visual utama adalah putih bersih, netral abu hangat, dan hijau institusi. Hindari warna netral yang terlihat kebiruan agar halaman tidak terasa semu biru.

Layout utama memakai app-shell: sidebar kiri, topbar, lalu area kerja utama. Pola ini dipakai di dashboard, master data, siswa, pembayaran, laporan, dan pengaturan.

Navigasi memakai sidebar bertingkat dengan menu aktif, submenu terbuka, ikon, dan overlay mobile. Topbar selalu menampilkan konteks penting seperti tombol sidebar dan Tahun Pelajaran Aktif.

Standar Warna

Palet aman halaman baru:
- Kanvas halaman: #ffffff
- Panel lembut/empty state: #fbfdf8
- Netral sekunder ringan: #f9fafb
- Bidang lembut tambahan: #f6f8f5
- Teks utama: #020617
- Teks utama alternatif lama: #111c2c
- Label dan heading kecil: #334155
- Teks sekunder netral: #404942
- Hint, deskripsi, placeholder, metadata ringan: #707971
- Border utama: #d1d5db
- Divider halus: #e5e7eb
- Border lembut hijau/netral: #dfe5dc
- Hijau utama: #157144
- Hijau hover: #0d5f36
- Hijau gelap/brand/nominal penting: #004528
- Hijau lembut: #e9f8ef
- Hijau sangat lembut: #f3fbf6

Status warna:
- Sukses/Aktif: teks #157144, background #e9f8ef
- Bahaya/Error/Hapus: teks/tombol #ef1f2d, hover #c91724, background #fff4f4, border #f2cfd2
- Peringatan: teks #92400e, background #fff7ed, border #fed7aa
- Info: teks #2563eb, background #eff6ff, border #bfdbfe, hanya untuk informasi murni/chart/link bantuan
- Netral/Nonaktif: teks #707971, background #f9fafb, border #d1d5db

Larangan warna:
- Jangan pakai #f8fafc sebagai background halaman utama.
- Jangan pakai #64748b sebagai teks sekunder default.
- Jangan pakai #d8e2ef atau #dfe7f1 sebagai border default.
- Jangan memakai palet slate/blue Tailwind seperti slate-50, slate-500, blue-50, blue-600 sebagai warna dominan halaman.
- Warna biru hanya boleh dipakai sangat terbatas untuk info, chart, atau tautan bantuan.
- Checkbox native boleh memakai aksen biru bawaan browser seperti checkbox "Pilih Semua" pada menu Pindah Kelas.

Standar Tipografi

Jenis font utama memakai system sans: Inter jika tersedia, lalu ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif.

Gunakan satu keluarga font utama saja untuk seluruh aplikasi agar tampilan konsisten dan operasional.

Ukuran font maksimal 22px dan minimal 14px untuk UI aplikasi. Jangan memakai font di bawah 14px untuk label, metadata, tabel, tombol, placeholder, teks bantuan, badge, chip, atau detail kecil.

Skala tipografi:
- Judul halaman utama: 22px, font-weight 700, line-height 1.25
- Judul section/card/modal: 18px sampai 20px, font-weight 600 atau 700, line-height 1.25
- Judul item penting seperti nama siswa, nama tagihan, nama menu aktif: 16px sampai 18px, font-weight 600 atau 700, line-height 1.3
- Teks isi, metadata penting, tabel, form input, select, textarea, placeholder: 14px sampai 16px, font-weight 400 atau 500, line-height 1.35 sampai 1.5
- Label form dan label tabel: 14px, font-weight 500 atau 600, line-height 1.3
- Tombol: 14px sampai 16px, font-weight 600 atau 700, line-height 1
- Nominal uang biasa: 14px sampai 16px, font-weight 600, gunakan tabular-nums jika tersedia
- Nominal uang penting atau total: 18px sampai 22px, font-weight 700 atau 800, gunakan tabular-nums jika tersedia
- Badge/chip/status: 14px, font-weight 600 atau 700, line-height 1
- Teks kosong/empty state: 14px sampai 16px, font-weight 400 atau 500, line-height 1.45

Aturan tebal tipis:
- 400 untuk teks normal dan deskripsi ringan
- 500 untuk metadata yang perlu terbaca
- 600 untuk label, tombol sekunder, dan judul kecil
- 700 untuk judul utama, nama item penting, dan aksi penting
- 800 hanya untuk angka total atau nama utama yang perlu sangat menonjol

Letter spacing default 0. Jangan memakai letter-spacing negatif. Huruf kapital boleh dipakai untuk nama siswa, kode unit, status pendek, atau header tabel, tetapi tetap jaga ukuran maksimal 22px.

Jangan memakai ukuran hero atau display besar di dashboard, card, tabel, sidebar, modal, atau form transaksi.

Standar Layout

Halaman default:
- Background halaman #ffffff
- App-shell, workspace, card, table wrapper, form, dan modal tetap putih
- Jika perlu membedakan area kerja, gunakan #fbfdf8, bukan biru-slate
- Gunakan ruang yang efisien, tidak terlalu banyak whitespace kosong

Standar Spacing

Gunakan skala jarak 4px: 4, 8, 12, 16, 20, 24, 32. Hindari angka acak seperti 7px, 13px, 19px, atau 27px kecuali untuk penyesuaian ikon yang sangat spesifik.

Kanvas dan area kerja:
- Jarak topbar ke konten halaman desktop: 24px
- Jarak topbar ke konten halaman mobile: 16px
- Padding halaman desktop: 24px 32px
- Padding halaman tablet: 20px 24px
- Padding halaman mobile: 16px
- Padding workspace/card besar desktop: 24px
- Padding workspace/card besar mobile: 16px
- Padding card kecil atau list item: 12px sampai 16px

Heading halaman:
- Jarak judul ke deskripsi: 4px
- Jarak deskripsi ke filter/search: 16px
- Jarak deskripsi ke card/konten utama langsung tanpa filter: 16px
- Jika halaman tidak memiliki deskripsi, jarak judul ke filter/search: 16px
- Jika halaman tidak memiliki deskripsi dan filter, jarak judul ke card/konten utama: 16px
- Jarak setelah blok heading selalu dihitung dari elemen terakhir: dari deskripsi jika tersedia, atau dari judul jika tidak ada deskripsi
- Jarak heading ke tombol aksi kanan: minimal 12px
- Judul dan deskripsi tidak perlu dibungkus card jika hanya menjadi pengantar halaman

Section dan komponen:
- Jarak antar section utama: 20px
- Jarak antar card sejajar/grid: 16px
- Jarak antar card bertumpuk: 16px
- Jarak header card ke isi card: 16px
- Jarak antar baris list/table-card: 12px sampai 16px
- Jarak divider ke konten atas dan bawah: 12px sampai 16px

Form dan filter:
- Jarak label ke field: 6px sampai 8px
- Jarak antar field horizontal: 12px
- Jarak antar field vertikal: 12px
- Jarak field ke tombol aksi: 12px
- Jarak antar tombol: 8px
- Form pencarian tunggal tidak boleh terlalu jauh dari heading; gunakan margin-top 16px

Tabel:
- Jarak toolbar tabel ke tabel: 16px
- Padding cell tabel desktop: 10px sampai 14px
- Padding cell tabel mobile/compact: 8px sampai 10px
- Tinggi baris tabel minimal 44px di desktop dan 40px di mobile

Mobile:
- Jarak antar section mobile: 16px
- Heading ke search mobile: 12px sampai 16px
- Card/list item mobile memakai padding 12px sampai 14px jika kontennya padat
- Hindari whitespace kosong besar di kanan atau bawah; konten operasional harus tetap mudah dipindai

Keputusan umum:
- Default gunakan 16px untuk jarak antar elemen yang berhubungan.
- Gunakan 20px atau 24px untuk memisahkan section besar.
- Gunakan 8px atau 12px untuk elemen kecil di dalam komponen.
- Jangan memakai jarak lebih dari 32px di halaman operasional kecuali halaman benar-benar kosong atau perlu fokus khusus.

Halaman data memakai pola:
- Heading
- Filter/search
- Jumlah data atau ringkasan
- Tabel/card list
- Pagination

Filter dan form pencarian:
- Label di atas field
- Input/select tinggi 40px
- Border #d1d5db
- Radius 8px
- Font input 14px sampai 16px
- Tombol filter full width di mobile

Tabel:
- Table-layout fixed jika kolom banyak
- Header jelas
- Aksi di kanan
- Gunakan horizontal scroll saat layar sempit
- Header background #f9fafb atau #fbfdf8
- Border #d1d5db atau #e5e7eb
- Hover row #fbfdf8

Card dan Panel

Card hanya dipakai untuk item berulang, modal, panel data, atau tool yang memang perlu frame. Jangan menaruh card di dalam card jika tidak perlu.

Standar card:
- Background #ffffff
- Border #d1d5db
- Radius 8px
- Shadow sangat tipis boleh dipakai: 0 1px 2px rgba(15, 23, 42, 0.04)
- Jangan membuat shadow atau border terlihat biru

Panel lembut/empty state:
- Background #fbfdf8
- Border #d1d5db atau #dfe5dc
- Teks #707971

Form

Input, select, textarea:
- Background #ffffff
- Border default #d1d5db
- Teks #020617
- Label #334155
- Placeholder #707971
- Focus border #157144
- Focus ring rgba(21, 113, 68, 0.10)
- Tinggi field standar 40px
- Radius 8px
- Font 14px sampai 16px

Form pembayaran dan master data boleh memakai bantuan otomatis:
- Format rupiah
- Format tanggal Indonesia
- Filter kelas berdasarkan unit
- Tombol submit disabled sampai pilihan valid

Checkbox:
- Gunakan checkbox native browser seperti checkbox "Pilih Semua" pada menu Pindah Kelas
- Jangan menetapkan width, height, radius, atau membuat kotak centang pengganti jika tidak dibutuhkan
- Gunakan accent-color: auto agar kondisi terpilih memakai aksen biru native browser, bukan aksen hijau
- Posisi checkbox harus sejajar vertikal dengan label atau isi baris

Tombol dan Aksi

Ukuran dasar semua tombol:
- Tinggi tombol standar 40px
- Tinggi tombol compact untuk toolbar atau tabel padat 32px
- Padding horizontal tombol teks 16px; tombol compact 12px
- Radius 8px
- Font 14px sampai 16px, weight 600 atau 700, line-height 1
- Jarak ikon ke teks 8px
- Ukuran ikon 16px sampai 18px; jangan membesarkan ikon melebihi teks tombol
- Tombol teks desktop memiliki min-width 88px agar ukuran aksi utama stabil, kecuali ruangnya memang terbatas
- Jangan memakai shadow pada tombol
- Label tombol harus singkat dan memakai kata kerja yang jelas, misalnya Tambah, Simpan, Cari, Tampilkan, Impor, atau Hapus

Tombol primer:
- Background #157144
- Teks #ffffff
- Hover #0d5f36
- Border 1px solid #157144 agar ukuran tidak berubah saat dibandingkan dengan tombol sekunder
- Dipakai untuk satu aksi utama pada satu halaman, section, form, atau modal

Tombol sekunder:
- Background #ffffff
- Teks #334155
- Border 1px solid #d1d5db
- Hover #fbfdf8
- Dipakai untuk Batal, Kembali, filter tambahan, atau aksi pendamping

Tombol bahaya:
- Background #ef1f2d
- Teks #ffffff
- Hover #c91724
- Border 1px solid #ef1f2d
- Tombol bahaya solid hanya dipakai sebagai aksi konfirmasi utama di modal hapus atau tindakan berisiko

Tombol ikon:
- Background #ffffff
- Border 1px solid #d1d5db
- Ikon #334155
- Hover hijau hanya untuk aksi positif
- Ukuran standar 40px x 40px; ukuran compact pada tabel atau kartu padat 32px x 32px
- Padding 0 dan ikon harus berada tepat di tengah
- Aksi hapus memakai ikon merah #ef1f2d dengan hover background #fff4f4, bukan hover hijau
- Gunakan ikon yang sudah umum; tombol ikon tanpa teks wajib memiliki tooltip dan aria-label

Keadaan tombol:
- Focus-visible memakai outline/ring 2px #157144 dengan offset 2px; focus tidak boleh hanya ditandai perubahan warna
- Disabled memakai opacity 0.55, cursor not-allowed, dan warna hover tidak berubah
- Loading mempertahankan lebar tombol, menampilkan spinner 16px, dan menonaktifkan klik berulang
- Tombol aktif/pressed harus tetap terbaca dan tidak mengubah tinggi, padding, atau posisi elemen lain

Susunan dan prioritas aksi:
- Maksimal satu tombol primer dalam satu kelompok aksi
- Tombol primer ditempatkan paling kanan pada kelompok tombol horizontal
- Jarak antar tombol 8px dan jarak heading ke kelompok tombol minimal 12px
- Tombol aksi halaman sejajar dengan heading jika ruang cukup; jika turun pada mobile, beri jarak 12px
- Tombol filter atau submit pada mobile boleh selebar container jika field dan tombol ditumpuk
- Jika field dan tombol masih muat dalam satu baris mobile, pertahankan tinggi 40px dan jarak 12px tanpa memaksa tombol full-width

Edit dan hapus memakai tombol ikon, bukan tombol teks panjang. Aksi berisiko wajib memakai confirm/modal.

Badge dan Chip

Badge/chip selalu minimal 14px.

Standar:
- Aktif/sukses: #157144 di atas #e9f8ef
- Nonaktif/lunas netral: #707971 di atas #f9fafb
- Error/bahaya: #ef1f2d di atas #fff4f4
- Warning: #92400e di atas #fff7ed

Nominal dan Keuangan

Nominal biasa memakai #020617, ukuran 14px sampai 16px, weight 600.

Nominal penting atau total boleh memakai #004528 atau #020617, ukuran 18px sampai 22px, weight 700 atau 800.

Gunakan tabular-nums jika tersedia agar angka sejajar dan mudah dipindai.

Jangan memakai biru untuk nominal kecuali konteksnya grafik atau informasi murni.

Standar UX Interaksi

Sidebar bisa collapse di desktop dan menjadi drawer di mobile.

Modal bisa ditutup dengan tombol, klik backdrop, atau Escape jika panel mendukung.

Empty state wajib jelas, misalnya:
- Belum ada data
- Siswa tidak ditemukan
- Tidak ada siswa
- Tidak ada tagihan aktif

Jangan memakai teks instruksi panjang di dalam UI jika kontrolnya sudah jelas.

Standar Responsif

Breakpoint yang umum dipakai:
- 760px
- 850px
- 900px
- 1180px

Aturan mobile:
- Filter grid berubah menjadi satu kolom
- Tombol filter menjadi full width
- Tabel terlalu lebar diberi scroll horizontal
- Card list turun menjadi satu kolom
- Text tidak boleh tumpang tindih
- Nominal dan aksi harus tetap mudah dipindai

Standar Halaman Transaksi dan Pembayaran

Halaman transaksi harus operasional dan langsung usable. Jangan membuat landing page atau hero.

Alur pembayaran:
- Cari siswa
- Pilih siswa
- Tampilkan profil ringkas siswa
- Tampilkan daftar tagihan
- Setiap tagihan bisa dicentang
- Pilih metode pembayaran
- Input nominal pembayaran dengan format ribuan
- Klik bayar sekarang
- Setelah berhasil, tampilkan opsi cetak struk atau download kwitansi

Profil siswa transaksi:
- Tampilkan nama siswa dengan jelas
- Gabungkan NIS jika siswa punya beberapa unit
- Gabungkan unit pendidikan jika siswa punya beberapa unit
- Tampilkan kelas
- Tampilkan status Aktif/Alumni
- Alumni tetap boleh masuk transaksi jika masih punya tagihan

Daftar tagihan:
- Checkbox di kiri
- Nama tagihan dan periode/detail di tengah
- Nominal rata kanan
- Total tagihan jelas di bawah
- Font detail minimal 14px
- Jangan biarkan nominal turun tidak rapi di mobile

Rumus Aman Implementasi

Gunakan:
- Kanvas: #ffffff
- Panel lembut: #fbfdf8
- Teks utama: #020617
- Teks sekunder: #404942 atau #707971
- Label: #334155
- Border: #d1d5db
- Aksi utama/status aktif: #157144
- Hover aksi utama: #0d5f36
- Brand/nominal penting: #004528
- Font utama: Inter/system sans
- Ukuran font: 14px sampai 22px
- Field form: 40px
- Radius umum: 8px

Dengan standar ini, aplikasi MA'WA CENTER harus terasa putih, netral, rapi, operasional, konsisten, dan tidak semu biru.
