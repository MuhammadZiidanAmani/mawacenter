Standar Aplikasi MA'WA CENTER

Dokumen ini menjadi acuan utama UI, UX, tipografi, warna, layout, komponen, responsif, dan interaksi aplikasi MA'WA CENTER. Gunakan dokumen ini sebagai standar tunggal saat membuat halaman baru atau merapikan halaman lama.

Prinsip Utama

Aplikasi memakai gaya operasional yang bersih, rapi, ringan, dan mudah dipindai. Tampilan harus terasa seperti sistem kerja internal: padat tetapi tetap nyaman, bukan landing page, bukan hero marketing, dan bukan tampilan dekoratif.

Arah visual utama adalah putih bersih, netral abu hangat, dan hijau institusi. Hindari warna netral yang terlihat kebiruan agar halaman tidak terasa semu biru.

Layout utama memakai app-shell: sidebar kiri, topbar, lalu area kerja utama. Pola ini dipakai di dashboard, master data, siswa, pembayaran, laporan, dan pengaturan.

Navigasi memakai sidebar bertingkat dengan menu aktif, submenu terbuka, ikon, dan overlay mobile. Topbar selalu menampilkan konteks penting seperti tombol sidebar dan Tahun Pelajaran Aktif.

Keputusan Final Cepat

- Font dasar seluruh UI: 14px.
- Judul halaman: 20px / 700 / #020617.
- Judul card atau section: 16px / 700 / #020617.
- Label form dan label data: 14px / 500 / #334155.
- Isi tabel, input, select, dan teks utama: 14px / 400 atau 500 / #020617.
- Metadata, deskripsi, hint, placeholder, tanggal, metode, dan empty state: 14px / 400 / #707971.
- Nominal biasa: 14px atau 16px / 700 / #004528.
- Nominal total utama: 20px / 700 / #004528.
- Bold hanya untuk struktur dan informasi yang benar-benar perlu dipindai cepat.
- Hijau hanya untuk aksi utama, nominal penting, status aktif/sukses, dan brand.
- Default teks rata kiri, nominal rata kanan, kolom sempit/icon/status pendek rata tengah.
- Padding halaman desktop 24px 32px, tablet 20px 24px, mobile 16px.
- Padding card besar 24px, card kecil/list item 12px sampai 16px.
- Jarak antar elemen mengikuti skala 4px: 4, 8, 12, 16, 20, 24, 32.
- Card operasional tidak boleh terasa seperti card di dalam card tanpa alasan yang jelas.
- Error form selalu dekat field, loading tidak boleh menggeser layout, dan empty state bukan error.
- Modal hanya untuk konfirmasi atau form pendek; detail resmi, tabel besar, dan dokumen cetak memakai halaman sendiri.
- Tombol icon-only wajib memiliki `aria-label` dan `title`.
- Dokumen print/PDF memakai gaya resmi putih-hitam, bukan card aplikasi.
- Semua menu mengikuti template jenis halaman: data, form, detail, transaksi, laporan, dashboard, pengaturan, import, atau print.
- Search/filter memakai urutan konteks besar, konteks turunan, periode/status, pencarian, lalu aksi.
- Bahasa UI memakai kata kerja singkat dan konsisten seperti Simpan, Batal, Terapkan, Reset, Import, Export, Cetak, Unduh, Bayar, dan Hapus.
- Aksi berisiko wajib memakai modal konfirmasi yang menyebut objek dan dampak.
- Import wajib melalui preview dan validasi sebelum konfirmasi akhir.
- Aksi UI mengikuti hak akses user; tombol tanpa izin disembunyikan atau disabled dengan alasan jelas.
- Dashboard/chart memakai warna seperlunya, legenda jelas, dan tidak mengalahkan data utama.
- Riwayat aktivitas/audit log mencatat waktu, petugas, aksi, objek, dan hasil aksi.

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
- Hijau utama/aksi utama: #004528
- Hijau hover: #0d5f36
- Hijau aksen/status aktif: #157144
- Hijau gelap/brand/nominal penting: #004528
- Hijau lembut: #e9f8ef
- Hijau sangat lembut: #f3fbf6

Standar peran warna teks:
- Judul halaman, judul card, judul section, nama data penting: #020617.
- Isi utama, isi tabel, isi form, dan teks nilai biasa: #020617 atau #111c2c pada halaman lama.
- Label form, label data, header kecil, dan teks yang memberi konteks: #334155.
- Deskripsi, placeholder, metadata, tanggal, metode, hint, empty state, dan teks pendukung: #707971.
- Teks sekunder yang masih perlu lebih terbaca daripada metadata boleh memakai #404942.
- Hijau #004528 hanya dipakai untuk aksi utama, tombol primer, nominal penting, status aktif penting, dan elemen brand yang memang perlu aksen.
- Hijau #157144 dipakai untuk status aktif/sukses, bukan untuk semua judul atau semua teks penting.
- Merah #ef1f2d hanya untuk hapus, error, gagal, atau tindakan berbahaya.
- Biru hanya untuk info murni, chart, atau link bantuan yang memang berbeda konteks dari aksi utama.

Aturan disiplin warna:
- Jangan membuat semua teks penting menjadi hijau. Jika semua hijau, tidak ada lagi yang benar-benar penting.
- Jangan membuat semua judul atau nama item berwarna hijau. Default judul tetap #020617.
- Nominal biasa boleh #004528 jika perlu mudah dipindai, tetapi nominal total utama harus menjadi satu-satunya nominal yang paling dominan.
- Teks pendukung jangan memakai warna terlalu gelap. Gunakan #707971 agar hierarki terbaca.
- Tombol primer solid hijau hanya untuk aksi utama halaman atau form.
- Tombol sekunder memakai putih, border #d1d5db, teks #334155 atau #404942.
- Link kecil boleh hijau teks tanpa background solid.
- Badge/status boleh memakai background lembut, tetapi jangan dipakai untuk metadata biasa.

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

Standar Sidebar dan Navigasi

- Sidebar dan topbar memakai background #ffffff dengan border #d1d5db.
- Brand/sidebar identity memakai hijau utama #004528 untuk teks utama dan #707971 untuk teks pendamping.
- Item menu normal memakai teks dan ikon #334155 di atas background transparan/putih.
- Item menu hover memakai teks dan ikon #0d5f36 dengan background #f3fbf6.
- Item menu aktif, parent menu terbuka, dan submenu aktif memakai teks dan ikon #004528 dengan background #e9f8ef.
- Ikon sidebar mengikuti warna teks menu melalui currentColor agar warna ikon dan label selalu sama.
- Menu sidebar bukan tombol aksi primer solid. Jangan memakai background solid #004528 pada item menu sidebar, karena #004528 solid hanya untuk tombol aksi utama seperti Tambah, Import, Terapkan, Simpan, dan Konfirmasi.
- Semua submenu, termasuk Manajemen Siswa, Data Master, dan Laporan, wajib memakai warna normal, hover, focus, dan aktif yang sama.
- Focus-visible item sidebar memakai outline 2px solid #004528 dengan offset 2px.

Standar Identitas, Footer, dan Copyright

- Brand utama pada logo/sidebar boleh memakai `MA'WA CENTER` karena berfungsi sebagai identitas visual.
- Nama brand pada kalimat biasa, footer, copyright, dokumen, dan teks naratif memakai `Ma'wa Center`.
- Format copyright aplikasi: `© {tahun berjalan} Ma'wa Center`.
- Tahun copyright harus dinamis dari sistem jika memungkinkan, bukan hardcode.
- Copyright UI aplikasi memakai 14px / 400 / #707971 / line-height 1.4.
- Copyright tidak memakai bold, hijau, background, badge, atau gaya tombol.
- Footer aplikasi ditempatkan sebagai teks pendukung yang ringan; pada login boleh rata tengah, pada halaman internal boleh rata tengah di bawah area kerja.
- Footer tidak boleh mengalahkan konten utama, tidak perlu card, dan tidak perlu border kecuali layout halaman memang membutuhkan pemisah halus.
- Copyright dokumen print/PDF boleh lebih kecil mengikuti kebutuhan cetak, tetapi tetap netral, ringan, dan tidak dominan.

Standar CSS Global Foundation

- `resources/css/app.css` adalah tempat fondasi global aplikasi, bukan tempat menumpuk CSS final semua menu.
- `app.css` wajib memuat import Tailwind/source utama dan import module final seperti `resources/css/modules/reports.css` dan `resources/css/modules/bills.css`.
- Token warna global disimpan di `:root`, minimal mencakup kanvas, surface, panel lembut, border, teks utama, teks sekunder, brand, hover brand, dan aksen.
- Fondasi global yang boleh tinggal di `app.css`: `body`, `app-shell`, `sidebar`, `main-panel`, `topbar`, `.button`, `.button-primary`, `.button-secondary`, `.icon`, `.icon-button`, `.table-wrap`, `.app-footer`, `.modal-backdrop`, `.form-modal`, `.form-modal-header`, `.form-actions`, `.result-modal-backdrop`, serta dasar `input`, `select`, dan `textarea`.
- Class global tersebut wajib dipertahankan karena dipakai lintas menu. Jangan menghapus `.modal-backdrop`, `.form-modal`, `.app-footer`, `.button`, `.icon-button`, `.table-wrap`, atau `.result-modal-backdrop` tanpa audit pemakaian di Blade dan test visual minimal.
- CSS menu yang sudah final wajib scoped di module masing-masing. Contoh: Menu Laporan di `resources/css/modules/reports.css`, Menu Tagihan di `resources/css/modules/bills.css`, dan menu berikutnya memakai module sendiri saat mulai dirapikan serius.
- Selector module wajib memakai scope halaman menu, misalnya `.report-page-v2`, `.bill-flat-page`, `.guardian-bill-page`, atau class halaman khusus lain yang stabil.
- Jangan menambahkan selector global agresif seperti `body *`, `td`, `th`, `strong`, `button`, atau semua elemen dengan `!important` untuk mengatur tipografi, warna, spacing, atau visibility lintas aplikasi.
- Jangan memakai aturan global `font-weight` yang memaksa semua `strong`, `th`, `button`, `label`, atau `span` menjadi satu ketebalan. Ketebalan mengikuti peran komponen: 400, 500, atau 700 sesuai standar tipografi.
- Jika perlu memperbaiki menu lama, scope perbaikannya ke class halaman menu tersebut. Jangan membuat override global baru hanya untuk menyelesaikan satu menu.
- Jika ada CSS lama/minified di `app.css`, bersihkan bertahap per menu setelah module menu tersebut siap, bukan dengan hapus massal tanpa pembuktian visual.
- Setelah mengubah fondasi global, wajib menjalankan build dan test menu yang terdampak minimal, lalu cek bahwa modal tersembunyi saat belum dibuka, footer tetap putih/transparan, dan module Laporan/Tagihan tidak berubah tampilan.

Standar Tipografi

Jenis font utama memakai system sans: Inter jika tersedia, lalu ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif.

Gunakan satu keluarga font utama saja untuk seluruh aplikasi agar tampilan konsisten dan operasional.

UI aplikasi memakai ukuran dan ketebalan terbatas agar tampilan rapi, konsisten, dan mudah dirawat. Jangan menaikkan ukuran atau ketebalan hanya karena sebuah teks terasa penting; tentukan dulu perannya.

Ukuran font UI:
- 14px sebagai teks dasar. Dipakai untuk teks isi, tabel, form input, select, textarea, placeholder, label, metadata, badge, chip, tombol, teks bantuan, deskripsi, empty state, dan header tabel.
- 16px sebagai teks penting. Dipakai untuk nama siswa, nama tagihan, nama item penting, judul card kecil, judul item list, menu aktif yang perlu menonjol, dan nominal biasa yang perlu lebih terbaca.
- 20px sebagai teks utama. Dipakai untuk judul halaman utama, angka total utama, nominal total penting, dan informasi utama yang menjadi fokus halaman.

Ketebalan font UI:
- 400 sebagai normal. Dipakai untuk teks isi, deskripsi, placeholder, teks bantuan, metadata ringan, dan keterangan tambahan.
- 500 sebagai medium. Dipakai untuk label form, label tabel, isi tabel penting, isi form, metadata penting, nominal biasa, dan teks yang perlu lebih mudah dipindai.
- 700 sebagai bold. Dipakai untuk judul halaman, judul section/card/modal, nama siswa atau tagihan penting, tombol, badge/status, menu aktif, dan nominal total penting.

Standar hierarki tipografi global:
- Judul halaman: 20px / 700 / line-height 1.2 / warna #020617.
- Deskripsi halaman: 14px / 400 / line-height 1.45 / warna #404942 atau #707971.
- Judul card/panel: 16px / 700 / line-height 1.25 / warna #020617.
- Judul section di dalam card: 15px atau 16px / 700 / line-height 1.25 / warna #020617.
- Label form dan label data: 14px / 500 / line-height 1.25 / warna #334155. Jika halaman lama masih memakai 600, ubah bertahap ke 500 atau 700 sesuai konteks.
- Isi/input/select/table: 14px / 400 atau 500 / line-height 1.25 sampai 1.35 / warna #020617.
- Nama siswa, nama tagihan, atau nama item penting: 14px atau 16px / 700 / warna #020617. Gunakan hijau hanya jika nama itu memang berfungsi sebagai status/tautan/aksen utama.
- Metadata seperti tanggal, periode, metode, hint, dan keterangan kecil: 14px / 400 / warna #707971.
- Tombol: 14px / 700 / line-height 1 / warna sesuai jenis tombol.
- Nominal biasa: 14px atau 16px / 700 / warna #004528 jika perlu dipindai cepat.
- Nominal total utama: 20px / 700 / warna #004528. Jika halaman benar-benar membutuhkan fokus finansial utama, boleh 22px / 700 sebagai pengecualian, tetapi jangan memakai 800.

Kombinasi penggunaan:
- 14px / 400 untuk deskripsi, teks bantuan, placeholder, metadata ringan, empty state, dan catatan kecil.
- 14px / 500 untuk isi tabel, input, select, textarea, label form, label tabel, filter, dan metadata penting.
- 14px / 700 untuk tombol, badge, status, chip, header tabel rekap/ringkasan, footer total, dan aksi kecil yang harus jelas.
- 14px / 500 boleh dipakai untuk header tabel detail yang padat atau sortable agar header tidak terlihat terlalu tebal dan tidak mengalahkan isi tabel.
- 16px / 500 untuk item penting yang masih bersifat isi, misalnya nama pada list, nominal biasa, atau ringkasan penting.
- 16px / 700 untuk nama siswa, nama tagihan, judul card kecil, judul item, dan teks utama di dalam komponen.
- 20px / 500 untuk pilihan role/akses login dan teks utama yang perlu terlihat besar tetapi tidak sekuat judul.
- 20px / 700 untuk judul halaman utama, total besar, nominal utama, dan angka ringkasan yang menjadi fokus.

Aturan pembatas:
- Jangan memakai font di bawah 14px pada UI aplikasi.
- Jangan memakai font di atas 20px pada UI aplikasi, kecuali nominal total utama tertentu yang disetujui boleh 22px.
- Jangan memakai font-weight 300, 600, atau 800 pada UI aplikasi baru.
- Jika menemukan font-weight 600, ubah ke 500 atau 700 sesuai konteks.
- Jika menemukan font-weight 800, ubah ke 700.
- Jika menemukan 12px atau 13px pada UI aplikasi, ubah ke 14px.
- Jika menemukan 18px untuk judul section, boleh dipertahankan hanya sementara pada halaman lama, tetapi standar baru mengarah ke 16px / 700 untuk section dan 20px / 700 untuk halaman.
- Struk, kwitansi, PDF cetak, dan dokumen print boleh memakai ukuran khusus seperti 11.5px, 12.5px, 14px, dan 18px karena kebutuhan cetak berbeda dari UI aplikasi.

Letter spacing default 0. Jangan memakai letter-spacing negatif. Huruf kapital boleh dipakai untuk nama siswa, kode unit, status pendek, atau header tabel, tetapi tetap jaga ukuran maksimal 20px.

Jangan memakai ukuran hero atau display besar di dashboard, card, tabel, sidebar, modal, atau form transaksi.

Aturan disiplin bold:
- Bold dipakai untuk struktur, bukan untuk semua informasi.
- Satu card cukup memiliki satu sampai dua titik visual paling kuat.
- Jika judul card sudah bold, isi card tidak semuanya perlu bold.
- Label tidak perlu bold berat; gunakan 14px / 500 kecuali label sangat penting.
- Nama item boleh bold, detailnya harus normal.
- Nominal boleh bold, tetapi jangan membuat label nominal juga sama beratnya.
- Metadata, tanggal, metode, deskripsi, dan empty state tidak boleh bold.
- Tombol utama bold; tombol sekunder tetap bold tetapi warna dan background lebih ringan.

Standar Layout

Halaman default:
- Background halaman #ffffff
- App-shell, workspace, card, table wrapper, form, dan modal tetap putih
- Jika perlu membedakan area kerja, gunakan #fbfdf8, bukan biru-slate
- Gunakan ruang yang efisien, tidak terlalu banyak whitespace kosong

Standar Spacing

Gunakan skala jarak 4px: 4, 8, 12, 16, 20, 24, 32. Hindari angka acak seperti 7px, 13px, 19px, atau 27px kecuali untuk penyesuaian ikon yang sangat spesifik.

Standar alignment global:
- Default semua teks UI rata kiri.
- Judul halaman, deskripsi halaman, judul card, label form, isi form, empty state teks panjang, dan teks detail selalu rata kiri.
- Container halaman boleh rata tengah dengan `margin-inline: auto`, tetapi isi teks di dalamnya tetap rata kiri.
- Rata tengah dipakai hanya untuk kolom sempit seperti No, kode pendek, JK, status pendek, checkbox, ikon aksi, dan angka jumlah kecil.
- Rata kanan dipakai untuk nominal, total uang, angka finansial, dan ringkasan angka yang perlu dibandingkan secara vertikal.
- Header tabel rata tengah, kecuali header kolom teks utama boleh rata kiri jika tabelnya sangat padat dan lebih mudah dibaca.
- Semua header tabel aplikasi memakai 14px / 500 agar kolom seperti `No`, nama, nominal, dan kolom sortable terlihat konsisten dan tidak terlalu tebal.
- Footer total memakai 14px / 700 agar agregat akhir tetap mudah dipindai tanpa membuat header tabel terlalu berat.
- Isi kolom nama, deskripsi, pembayaran, kategori, alamat, keterangan, dan teks panjang rata kiri.
- Isi kolom nominal, total, sisa tagihan, dan nilai uang rata kanan.
- Tombol aksi utama di header halaman rata kanan pada desktop dan turun full width pada mobile jika ruang sempit.
- Jangan mencampur rata tengah dan rata kiri dalam satu kelompok teks kecuali memang tabel membutuhkan perataan kolom berbeda.
- Jangan memakai `text-align: center` untuk card operasional, form, atau panel data hanya agar terlihat simetris.

Standar padding dan posisi:
- Padding halaman desktop: 24px atas/bawah dan 32px kiri/kanan.
- Padding halaman tablet: 20px atas/bawah dan 24px kiri/kanan.
- Padding halaman mobile: 16px semua sisi.
- Padding card besar: 24px semua sisi.
- Padding card kecil/list item: 12px sampai 16px.
- Header card memakai tinggi stabil 56px atau padding vertikal setara, dengan padding kiri/kanan 24px.
- Isi card dimulai 16px sampai 24px dari header card, sesuai kepadatan konten.
- Jika card memiliki garis header, garis berada setelah header dan isi tidak boleh menempel ke garis.
- Jarak kiri judul card dan isi card harus konsisten dalam card yang sama.
- Jika dua card sejajar, padding header dan padding isi keduanya harus sama agar terlihat satu sistem.
- Field form harus sejajar dengan labelnya; label tidak boleh terlalu jauh atau terlalu mepet dari field.
- Tombol ikon harus berada tepat tengah secara horizontal dan vertikal di area kliknya.

Kanvas dan area kerja:
- Seluruh konten halaman harus berada di tengah area kerja dengan container `width: 100%` dan `margin-inline: auto`
- Heading, deskripsi, filter, canvas, tabel, dan form utama pada satu halaman harus memakai batas kiri-kanan container yang sama
- Halaman form atau tool sederhana boleh memakai max-width 720px; halaman tabel/data maksimal 1200px; dashboard padat maksimal 1440px
- Jika halaman turunan diminta mengikuti halaman induk, gunakan `max-width` kanvas halaman induk secara konsisten pada judul dan konten utama.
- Rata tengah yang dimaksud adalah posisi container, bukan `text-align: center`; judul, deskripsi, label, dan isi tetap rata kiri kecuali komponen memang membutuhkan perataan lain
- Jarak topbar ke konten/heading halaman mengikuti pola Data Siswa: desktop 24px dan mobile 16px.
- Jika judul/heading halaman adalah elemen pertama di area konten, jarak topbar ke judul mengikuti jarak topbar ke konten halaman: desktop 24px dan mobile 16px.

Heading halaman:
- Pola heading default memakai 16px untuk jarak antar blok utama, kecuali judul ke deskripsi tetap 4px karena masih satu pasangan informasi
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
- Tinggi baris tabel default 44px di desktop dan 40px di mobile
- Untuk tabel data compact yang mengikuti Data Siswa, tinggi header dan isi baris dibuat seragam 40px

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

Checklist audit visual cepat:
- Apakah semua judul memakai ukuran dan warna sesuai perannya?
- Apakah hanya 1 sampai 2 elemen dalam card yang benar-benar dominan?
- Apakah teks pendukung sudah abu dan normal, bukan hitam tebal?
- Apakah hijau hanya muncul pada aksi, nominal penting, status, atau brand?
- Apakah padding kiri judul card dan isi card sejajar?
- Apakah nominal rata kanan dan teks panjang rata kiri?
- Apakah jarak antar section, card, field, dan tombol memakai skala 4px?
- Apakah tidak ada card di dalam card tanpa alasan operasional?
- Apakah mobile tetap satu kolom, tidak melebar kanan, dan tombol tetap mudah ditekan?

Halaman data memakai pola:
- Heading
- Filter/search
- Jumlah data atau ringkasan
- Tabel/card list
- Pagination

Template jenis halaman:
- Halaman data/list: Heading, deskripsi singkat jika perlu, aksi kanan utama, filter/search, toolbar jumlah data, tabel/list, pagination atau ringkasan data.
- Halaman form tambah/edit: Heading, deskripsi singkat, card/form utama, field berurutan dari konteks besar ke detail, tombol Batal dan Simpan di akhir form.
- Halaman detail: Heading, aksi kanan jika perlu, ringkasan identitas utama, detail data berkelompok, riwayat/aktivitas jika relevan, tombol kembali.
- Halaman transaksi: Heading, konteks/pencarian, data objek terpilih, daftar pilihan transaksi, ringkasan/proses pembayaran, riwayat terbaru jika membantu.
- Halaman laporan: Heading, filter periode/konteks, ringkasan yang benar-benar dibutuhkan, tabel/detail laporan, aksi Export/Cetak.
- Halaman dashboard: Heading ringkas, ringkasan angka penting, chart/daftar prioritas, aktivitas terbaru, tanpa form panjang.
- Halaman pengaturan: Heading, section pengaturan per kelompok, kontrol yang jelas, simpan perubahan per section jika risikonya rendah atau satu tombol simpan global jika saling terkait.
- Halaman import: Heading, konteks import, upload file, preview data, ringkasan valid/gagal, daftar error per baris, tombol Konfirmasi Import.
- Halaman print/dokumen: Toolbar layar, dokumen resmi, tombol Kembali/Cetak/Unduh; toolbar hilang saat print.
- Jangan memakai template dashboard untuk halaman operasional transaksi atau data utama. Halaman kerja harus langsung membantu user menyelesaikan tugas.

Standar ringkasan laporan:
- Jangan menampilkan card ringkasan jika angka yang sama sudah jelas terjawab di tabel rekap dan footer total.
- Untuk laporan operasional, dahulukan tabel rekap dengan footer `Total Keseluruhan` dibanding card angka berulang.
- Card ringkasan hanya dipakai jika membantu memahami data sebelum tabel, misalnya status besar yang tidak muncul langsung di baris tabel.
- Jika laporan sudah memiliki kolom `Jumlah Transaksi`, `Cash`, `Transfer`, `Jumlah Penerimaan`, dan footer total, jangan ulangi nilai yang sama sebagai card di atas tabel.
- Ringkasan jenis pembayaran seperti SPP, Daftar Ulang, Laundry, dan Lain-lain lebih baik tampil sebagai kolom/tabel yang bisa dibandingkan atau masuk laporan khusus, bukan card terpisah yang mengulang data.

Standar search, filter, dan reset:
- Search tunggal dipakai jika user hanya perlu mencari satu objek utama, misalnya siswa, kategori, atau nomor transaksi.
- Card filter dipakai jika ada lebih dari dua field filter atau filter perlu konteks berpasangan seperti Unit dan Kelas.
- Search utama ditempatkan setelah heading/deskripsi atau di dalam card filter jika menjadi bagian dari filter data.
- Urutan filter umum: konteks besar, konteks turunan, periode/status, pencarian teks, aksi.
- Contoh urutan: Unit Pendidikan, Kelas, Status, Cari, Terapkan, Reset.
- Placeholder search harus singkat dan spesifik, misalnya "Nama atau NIS..." atau "Cari kategori...".
- Tombol filter utama selalu memakai label "Terapkan".
- Tombol reset selalu memakai label "Reset" dan mengembalikan halaman ke route bersih tanpa query filter.
- Filter harus mempertahankan query penting seperti per_page, sort, direction, dan tab aktif jika halaman membutuhkannya.
- Jika Unit Pendidikan berubah, field turunan seperti Kelas wajib disesuaikan atau direset jika nilainya tidak valid.
- Jangan menampilkan filter yang tidak membantu keputusan user pada halaman itu.
- Tahun Pelajaran tidak perlu diulang di filter jika sudah menjadi konteks aktif dari topbar, kecuali halaman arsip, laporan, import, atau form target tahun.
- Search live boleh dipakai untuk lookup kecil seperti cari siswa, tetapi halaman data besar lebih aman memakai tombol Terapkan agar query jelas.
- Reset tidak boleh menghapus data, hanya membersihkan filter.

Toolbar ringkasan jumlah data:
- Pola toolbar "Tampilkan 10/25/50" Data Siswa menjadi acuan untuk halaman data yang memakai tabel/list.
- Toolbar ini bukan card visual; tampil tanpa background khusus, tanpa border, tanpa padding, dan tanpa shadow.
- Toolbar berada setelah filter/search dan sebelum tabel/list dengan container yang sama seperti heading, filter, tabel, dan pagination.
- Layout desktop memakai display flex, align-items center, justify-content space-between, flex-wrap wrap, gap 14px, width 100%.
- Margin toolbar: 0 0 12px.
- Teks toolbar memakai warna #707971, font 14px / 400, line-height 1.35.
- Grup kiri "Tampilkan [select] data" memakai inline-flex, align-items center, gap 8px, margin 0, padding 0.
- Select jumlah data memakai width 78px, min-width 78px, height 34px, min-height 34px, padding 0 10px, border 1px solid #d1d5db, radius 8px, background #ffffff, teks #111c2c, font 14px / 500, line-height 34px.
- Opsi standar jumlah data: 10, 25, 50, 100, 500, dan All.
- Default jumlah data mengikuti Data Siswa yaitu 10, kecuali ada kebutuhan halaman yang benar-benar berbeda dan disetujui sebagai pengecualian.
- Area kanan toolbar boleh dipakai untuk pencarian tabel atau aksi ringan jika tersedia; ringkasan "Menampilkan x-y dari z data" mengikuti standar navigasi halaman/pagination final di bawah tabel.
- Pada mobile, toolbar boleh wrap atau grid satu kolom, tetapi select tetap 78px x 34px kecuali ruang tidak cukup.

Filter dan form pencarian:
- Label di atas field
- Input/select tinggi 40px
- Border #d1d5db
- Radius 8px
- Font input 14px sampai 16px
- Tombol filter full width di mobile

Standar card filter halaman data:
- Pola card filter Data Siswa menjadi acuan utama untuk halaman data yang memiliki filter lebih dari satu field.
- Card filter berada setelah heading/deskripsi dengan jarak 16px dan sebelum ringkasan jumlah data atau tabel/card list.
- Card filter memakai background #ffffff, border 1px solid #d1d5db, radius 12px, padding 16px, shadow none, dan margin 0.
- Card filter harus memakai container yang sama dengan heading, tabel/card list, dan pagination. Untuk halaman data utama gunakan lebar container maksimal 1200px.
- Form filter memakai display grid, align-items end, gap 12px, dan semua elemen berada dalam satu baris jika ruang cukup.
- Field filter wajib stabil tinggi 40px, width 100% dalam kolomnya, padding horizontal 12px, border 1px solid #d1d5db, radius 8px, background #ffffff, teks #020617 atau #111c2c, font 14px / 400, line-height 40px.
- Label filter memakai posisi di atas field, warna #334155 atau #404942, font 14px / 400, line-height 1.25, dan jarak label ke field 6px.
- Field pencarian memakai ikon search di kiri jika tersedia. Ikon search berukuran 18px, posisi kiri 12px dan tengah vertikal. Input pencarian memakai padding kiri 32px.
- Placeholder pencarian harus singkat dan spesifik, misalnya "Nama atau NIS..."; jangan memakai instruksi panjang.
- Tombol aksi filter berada di kanan pada desktop, align-self end, tinggi 40px, radius 8px, font 14px / 700, line-height 1, padding horizontal 14px sampai 16px.
- Tombol filter utama memakai label "Terapkan" atau kata kerja singkat lain. Mengikuti standar Data Siswa, gunakan background #004528, border #004528, teks #ffffff, hover #0d5f36.
- Tombol reset filter memakai label "Reset", background #ffffff, border #d1d5db, teks #404942 atau #334155.
- Tombol filter dan reset pada desktop boleh memakai lebar stabil 96px sampai 132px sesuai kepadatan halaman. Jika ruang terbatas, gunakan 96px; jika form lebih longgar, gunakan 132px.
- Pada mobile sampai 760px, grid filter berubah menjadi satu kolom, semua field width 100%, dan tombol aksi menjadi grid dua kolom sama lebar. Jika tombol tidak muat dua kolom, tumpuk satu kolom penuh.
- Jika ada filter berpasangan seperti Unit Pendidikan dan Kelas, pilihan Kelas harus difilter berdasarkan Unit Pendidikan yang dipilih tanpa reload halaman. Saat Unit berubah, pilihan Kelas yang tidak sesuai disembunyikan/dinonaktifkan dan nilai Kelas direset jika tidak valid.
- Filter harus mempertahankan query penting seperti per_page, sort, dan direction jika halaman membutuhkannya.
- Gunakan tombol "Reset" yang kembali ke route index bersih tanpa query filter.
- Jangan menaruh card filter di dalam card lain. Card filter berdiri sendiri sebagai panel operasional.
- Jangan memakai warna biru atau slate untuk card filter, field, border, tombol utama, atau focus state.
- Jangan menampilkan field Tahun Pelajaran di card filter operasional jika halaman sudah memakai Tahun Pelajaran Aktif dari topbar. Tahun Pelajaran di topbar menjadi acuan data aktif untuk Data Siswa, Rapikan Identitas, Pindah Kelas, Naik Kelas, Data Master Kelas, Kategori Pembayaran, Keringanan Biaya, dan Tagihan.
- Pengecualian field Tahun Pelajaran hanya untuk halaman arsip/riwayat/laporan yang memang membandingkan periode, form tambah/edit data yang menyimpan tahun pelajaran, dan field target seperti "Tahun Pelajaran Tujuan" pada Naik Kelas.

Template grid card filter Data Siswa:
- Kolom 1 Unit Pendidikan: 160px
- Kolom 2 Kelas: 150px
- Kolom 3 Cari siswa: minmax(220px, 300px)
- Kolom 4 Aksi: max-content
- Jika halaman memiliki field tambahan seperti Status Data, gunakan lebar stabil 150px dan tetap pertahankan urutan field utama, pencarian, lalu aksi.
- Gap antar kolom: 12px
- Padding panel: 16px
- Radius panel: 12px
- Tinggi semua field dan tombol: 40px
- Tombol Terapkan: background #004528, border #004528, hover #0d5f36
- Tombol Reset: background #ffffff, border #d1d5db, teks #404942

Pengecualian filter Kategori Pembayaran:
- Filter Kategori Pembayaran memakai Unit Pendidikan, pencarian nama/kode kategori, dan tombol aksi.
- Urutan field: Unit Pendidikan 160px, Cari kategori minmax(220px, 300px), lalu aksi.
- Jangan menampilkan filter Status Data pada Kategori Pembayaran.
- Tabel Kategori Pembayaran menampilkan data Aktif dan Nonaktif sekaligus untuk menjaga arsip dan riwayat keuangan tetap mudah diaudit.
- Status Data tetap tersedia di form tambah/edit dan boleh ditampilkan sebagai kolom kecil di tabel.
- Tabel Kategori Pembayaran di desktop tidak boleh memaksa scroll kanan. Gunakan min-width 0 dengan table width 100%, lalu lebar kolom sekitar No 48px, Kategori Pembayaran 260px, Unit 80px, Tingkat 120px, Nominal 128px, Status 92px, dan Aksi 84px.

Pengecualian filter Keringanan Biaya:
- Filter Keringanan Biaya memakai Unit Pendidikan, Kelas, pencarian nama siswa, dan tombol aksi.
- Urutan field: Unit Pendidikan 160px, Kelas 150px, Cari siswa minmax(220px, 300px), lalu aksi.
- Jangan menampilkan filter Status Data pada Keringanan Biaya agar card filter tetap ringkas.
- Status aktif/nonaktif keringanan tetap dikelola di form tambah/edit dan tidak menjadi filter utama halaman.
- Tabel Keringanan Biaya tidak boleh memaksa scroll kanan di desktop. Gunakan min-width 0 dengan table width 100%, lalu lebar kolom sekitar No 48px, Nama Siswa 300px, Unit 80px, Kelas 100px, Pembayaran 200px, dan Aksi 84px.

Standar tabel final:
- Semua tabel aplikasi memakai satu standar visual yang sama. Istilah tabel detail, tabel rekap, tabel master data, dan tabel transaksi hanya menjelaskan fungsi data, bukan membedakan tipografi dasar.
- Tabel utama untuk data operasional memakai `width: 100%`, `border-collapse: collapse`, tanpa garis vertikal, dan hanya memakai garis horizontal halus.
- Gunakan `table-layout: fixed` jika kolom banyak atau halaman perlu lebar stabil. Gunakan table auto hanya jika isi kolom sangat variatif dan tetap tidak merusak layout.
- Wrapper tabel memakai background #ffffff, border 1px solid #d1d5db, radius 8px, shadow none, dan overflow tersembunyi/scroll sesuai kebutuhan.
- Header tabel memakai background #fbfdf8 atau #f8faf7, teks #020617 atau #334155 sesuai kepadatan, dan border-bottom 1px solid #d1d5db atau #dbe5dd.
- Isi tabel memakai teks #020617, border-bottom #e5e7eb atau #eef2f7, dan hover row #fbfdf8.
- Tabel tidak boleh menampilkan card berulang untuk data yang lebih efektif dibaca sebagai baris.
- Horizontal scroll hanya boleh terjadi di dalam wrapper tabel pada layar sempit atau kolom benar-benar banyak. Halaman utama tidak boleh melebar ke kanan.
- Pada desktop, tabel yang kolomnya sedikit tidak boleh memunculkan scrollbar horizontal.

Jenis tabel:
- **Tabel Detail:** untuk transaksi, siswa, tagihan, riwayat, dan daftar data banyak baris.
- **Tabel Rekap:** untuk ringkasan per unit, kelas, kategori, bulan, atau status.
- **Tabel Master Data:** untuk unit, kelas, tahun pelajaran, kategori, dan data referensi.
- **Tabel Transaksi:** tabel detail transaksi dengan kolom nominal rata kanan, tanggal/waktu boleh dua baris jika ruang terbatas, dan aksi detail/cetak/hapus memakai ikon kecil.
- **Tabel Dokumen/Print/PDF:** boleh memakai ukuran cetak khusus dan warna hitam-putih resmi. Tidak mengikuti card UI aplikasi.

Standar header tabel:
- Semua header tabel aplikasi memakai 14px / 500.
- Header rata tengah sebagai default.
- Header kolom teks utama boleh rata kiri hanya jika tabel sangat padat dan lebih mudah dibaca.
- Header memakai Title Case, bukan kapital semua, kecuali singkatan resmi seperti NIS, NISN, SPP, MTs, MA, dan PAUD.
- Ikon sort hanya tampil pada kolom yang benar-benar bisa diurutkan. Ikon sort tidak boleh membuat tinggi header melebihi 40px.
- Jika sebagian header sortable dan sebagian tidak, berat font visualnya harus tetap konsisten. Contoh: kolom `No` tidak boleh terlihat lebih tebal sendirian dibanding header sortable lain.

Standar isi tabel:
- Isi semua tabel aplikasi memakai 14px / 400 sebagai default.
- Teks panjang seperti Nama Siswa, Unit Pendidikan, Nama Pembayaran, Alamat, Keterangan, dan Deskripsi rata kiri.
- Kolom sempit seperti No, kode pendek, status pendek, checkbox, ikon, dan aksi rata tengah.
- Kolom jumlah non-uang seperti Jumlah Siswa, Jumlah Transaksi, Lunas, Sebagian, dan Belum Bayar rata tengah.
- Kolom uang seperti Nominal, Cash, Transfer, Terbayar, Sisa, Tunggakan, Total, dan Jumlah Penerimaan rata kanan.
- Nominal di isi tabel memakai warna teks utama #020617 dan font 14px / 400. Nominal yang menjadi total akhir memakai standar footer total.
- Aksi tabel berada di kolom kanan atau kolom aksi khusus, memakai tombol ikon compact, dan wajib memiliki `aria-label` dan `title`.

Standar ukuran tabel:
- Tinggi header tabel compact: 40px.
- Tinggi isi baris tabel compact: 40px.
- Tinggi isi baris tabel detail yang perlu dua baris: 44px sampai 56px, sesuai kebutuhan isi.
- Padding cell compact: 8px 10px.
- Padding cell desktop biasa: 10px sampai 14px.
- Padding cell dengan ikon aksi compact boleh 4px 10px sampai 4px 12px agar baris tidak membesar.
- Radius wrapper tabel: 8px.
- Border wrapper: 1px solid #d1d5db.
- Garis antar baris: 1px solid #e5e7eb atau #eef2f7.

Standar footer total:
- Jika tabel memiliki agregat keseluruhan, gunakan satu baris `tfoot` di bawah tabel.
- Label footer memakai teks `Total Keseluruhan` jika menghitung semua hasil filter.
- Label `Total Keseluruhan` boleh memakai `colspan` pada kolom awal dan rata tengah.
- Angka jumlah non-uang di footer rata tengah, warna #020617, font 14px / 700.
- Nominal footer rata kanan, warna #004528, font 14px / 700.
- Footer memakai background #fbfdf8, border-top 1px solid #d1d5db, dan tidak memakai garis dobel.
- Jangan menaruh total keseluruhan sebagai card terpisah jika sudah ada footer tabel yang jelas.

Standar empty table:
- Empty table memakai satu baris/area ringkas dengan teks 14px / 400 / #707971 dan background #fbfdf8.
- Kalimat empty state harus netral, misalnya "Belum ada data pada filter ini."
- Empty state tabel tidak boleh membuat tabel kosong memaksa horizontal scroll panjang jika tidak ada data.
- Jika kolom banyak dan header tetap perlu terlihat, empty state boleh tampil dalam satu baris `colspan` penuh.
- Empty state bukan error; jangan memakai warna merah atau ikon peringatan kecuali data gagal dimuat.

Standar tabel data compact:
- Pola tabel Data Siswa menjadi acuan untuk halaman yang menampilkan data operasional berbentuk daftar ke bawah, bukan card-card.
- Gunakan tabel/list data ke bawah untuk data utama yang butuh dipindai cepat, dibandingkan card berulang yang memakan ruang.
- Header tabel tinggi 40px, min-height 40px, padding 8px 10px, font 14px / 500, line-height 1.2, teks rata tengah.
- Isi baris tinggi 40px, min-height 40px, font 14px / 400, line-height 1.2 sampai 1.25.
- Untuk tabel compact yang memiliki tombol ikon 30px sampai 32px di dalam cell, gunakan padding isi 4px 12px agar tinggi visual baris tetap 40px. Padding 8px 12px hanya boleh dipakai jika isi cell tidak membuat tinggi baris melebihi 40px.
- Untuk menjaga tinggi 40px secara visual, isi cell tabel compact tidak boleh membungkus ke baris kedua; gunakan white-space nowrap, overflow hidden, dan text-overflow ellipsis pada cell teks panjang.
- Kolom teks utama seperti Nama tetap 14px / 400 dan warna #020617 sebagai default. Gunakan 14px / 500 hanya jika benar-benar perlu penekanan ringan.
- Header semua kolom rata tengah, termasuk header Nama. Isi kolom Nama tetap rata kiri agar nama panjang mudah dibaca.
- Kolom angka pendek, kode, JK, unit, kelas, dan aksi rata tengah.
- Tabel compact Data Siswa menjadi standar garis tabel: tidak memakai garis vertikal, tidak memakai border luar tabel, dan hanya memakai garis horizontal tipis antar baris.
- Garis horizontal isi baris memakai `border-bottom: 1px solid #eef2f7`.
- Header tabel memakai background #f8faf7, teks #475569, dan garis bawah header `border-bottom: 1px solid #dbe5dd`.
- Hover baris memakai background #fbfdf8 dan boleh mengubah warna garis bawah menjadi #dfe8df.
- Baris terakhir/tabel paling bawah wajib `border-bottom: 0` agar tampilan list berakhir bersih tanpa garis penutup.
- Pada desktop, tabel Data Master tidak boleh memunculkan garis/scrollbar horizontal di bagian paling bawah. Gunakan width 100%, min-width 0, dan overflow-x hidden pada wrapper. Horizontal scroll hanya boleh aktif pada layar mobile/sempit.
- Header Data Siswa memakai label ringkas: No, NIS, Nama, JK, Unit, Kelas, Aksi.
- Isi JK memakai singkatan L untuk Laki-Laki dan P untuk Perempuan agar kolom Nama lebih fleksibel.
- Kolom status tidak perlu ditampilkan jika status tidak menjadi fokus kerja utama.
- Table-layout fixed, width 100%, min-width menyesuaikan jumlah kolom, dan gunakan horizontal scroll saat layar sempit.
- Hindari ikon sort atau panah sort di header compact jika membuat header tidak rapi, terutama pada kolom sempit seperti JK dan Unit.
- Aksi edit/hapus di tabel memakai tombol ikon compact 30px sampai 32px, berada di tengah cell, dan tidak boleh membuat tinggi baris melebihi 40px.

Standar tabel lanjutan:
- Loading tabel memakai baris skeleton atau pesan "Memuat data..." di area tabel, bukan spinner besar di tengah halaman.
- Empty table memakai satu baris/box ringkas dengan teks 14px / 400 / #707971 dan background #fbfdf8.
- Error table memakai pesan ringkas dengan warna bahaya dan tombol Coba Lagi jika data diambil async.
- Sorting hanya ditampilkan pada kolom yang benar-benar bisa diurutkan.
- Ikon sort tidak boleh membuat header kolom lebih tinggi dari standar 40px.
- Kolom teks panjang wajib memakai ellipsis pada tabel compact.
- Kolom nominal wajib rata kanan di header dan isi, kecuali header tabel global memaksa rata tengah; jika begitu isi nominal tetap rata kanan.
- Kolom aksi selalu di kanan dan memakai tombol ikon compact.
- Navigasi halaman/pagination final:
- Toolbar atas tabel berisi kontrol jumlah data di kiri.
- Area kanan toolbar atas boleh dipakai untuk pencarian tabel jika tersedia; jika belum ada fitur pencarian, area kanan boleh kosong.
- Pencarian siswa di toolbar tabel memakai pola final yang sama di Menu Laporan dan Menu Tagihan: pada desktop label `Cari Siswa` sejajar di samping input-card, input tinggi 36px, radius 8px, lebar acuan 280px, placeholder `Nama / NIS / NISN`; pada mobile label dan input menjadi satu kolom full width.
- Jangan menampilkan teks "Menampilkan x-y dari z data" di toolbar atas tabel.
- Footer bawah tabel kiri menampilkan format "Menampilkan 1-10 dari 705 data".
- Footer bawah tabel kanan menampilkan tombol navigasi halaman.
- Jangan menampilkan teks duplikat "Halaman x dari y".
- Tombol pagination tampil hanya jika total data lebih besar dari jumlah data per halaman.
- Jika data tidak melebihi jumlah per halaman, cukup tampilkan teks hasil di footer bawah tanpa tombol halaman.
- Tombol pagination memakai tinggi 36px, radius 8px, gap 8px, padding horizontal 12px, dan font 14px / 500.
- Tombol halaman aktif memakai font 14px / 700, background #004528, border #004528, dan teks #ffffff.
- Tombol normal memakai background #ffffff, border #d1d5db, dan teks #334155.
- Tombol disabled memakai teks #707971, background #f9fafb, border #d1d5db, dan cursor not-allowed.
- Desktop: info hasil rata kiri, tombol navigasi rata kanan.
- Mobile: footer tabel menjadi 1 kolom; info hasil berada di atas tombol; tombol wrap rapi tanpa membuat halaman melebar.
- Jangan menampilkan dua kontrol jumlah data dalam satu halaman. Jika sudah ada toolbar "Tampilkan ... data", jangan tambah select serupa di bawah tabel.
- Pada mobile, hanya wrapper tabel yang scroll horizontal; halaman utama tidak boleh melebar ke kanan.
- Sticky header boleh dipakai untuk tabel sangat panjang, tetapi tinggi header, warna, border, dan z-index harus dijaga agar tidak menutup topbar atau filter.

Standar tabel ringkasan laporan:
- Tabel ringkasan laporan memakai tabel compact 40px dengan header rata tengah, tanpa garis vertikal.
- Kolom No rata tengah.
- Kolom teks utama seperti Unit Pendidikan, Nama Siswa, Nama Pembayaran, dan Keterangan rata kiri.
- Kolom angka jumlah pendek seperti Jumlah Siswa, Jumlah Transaksi, Lunas, Sebagian, dan Belum Bayar rata tengah.
- Kolom uang seperti Cash, Transfer, SPP, Daftar Ulang, Laundry, Lain-lain, Sisa, Tunggakan, dan Jumlah Penerimaan rata kanan.
- Label penerimaan di laporan memakai istilah `Jumlah Penerimaan`, bukan `Total Penerimaan`.
- Jika tabel ringkasan memiliki agregat keseluruhan, tampilkan satu baris `tfoot` dengan label `Total Keseluruhan`.
- Label `Total Keseluruhan` rata tengah pada area kolom teks awal, angka jumlah non-uang rata tengah memakai warna teks utama #020617, dan nominal uang rata kanan memakai hijau #004528.
- Header tabel ringkasan tetap mengikuti standar header tabel 14px / 500. Footer total memakai font 14px / 700, background #fbfdf8, border-top 1px solid #d1d5db, dan tidak memakai garis dobel.

Card dan Panel

Card hanya dipakai untuk item berulang, modal, panel data, atau tool yang memang perlu frame. Jangan menaruh card di dalam card jika tidak perlu.

Standar card:
- Background #ffffff.
- Border 1px solid #d1d5db.
- Radius 8px sebagai standar baru.
- Radius 12px hanya boleh dipertahankan pada card filter lama yang sudah mengikuti pola Data Siswa sampai halaman itu dirapikan ulang.
- Shadow default none.
- Shadow sangat tipis boleh dipakai hanya untuk card yang perlu sedikit naik dari kanvas: 0 1px 2px rgba(15, 23, 42, 0.04).
- Jangan membuat shadow atau border terlihat biru.
- Padding card besar 24px.
- Padding card operasional sedang 16px sampai 20px.
- Padding card kecil/list item 12px sampai 16px.
- Jarak antar card sejajar atau bertumpuk 16px.
- Jika dua card sejajar, tinggi header, padding kiri-kanan, dan jarak isi harus konsisten.
- Judul card memakai 16px / 700 / #020617.
- Judul card rata kiri dan tidak perlu diberi background khusus.
- Header card memakai padding kiri-kanan yang sama dengan isi card.
- Header card boleh memakai tinggi stabil 56px atau padding vertikal setara.
- Divider header hanya 1 garis: border-bottom 1px solid #e5e7eb.
- Jangan membuat garis ganda di bawah judul card. Jika header sudah punya border-bottom, isi pertama tidak boleh punya border-top.
- Isi card dimulai 16px sampai 24px dari header atau divider.
- Jarak kiri judul card, divider, dan isi card harus sejajar.
- Card boleh tanpa divider jika isinya pendek, misalnya Data Siswa, empty state, atau ringkasan sederhana.
- Card yang berisi form memakai label di atas field, gap field 12px, dan field tinggi 40px.
- Card yang berisi nominal utama boleh memiliki satu blok highlight lembut #fbfdf8 dengan border #d1d5db atau #dfe5dc.
- Card item/list tidak boleh terlalu tinggi jika data bisa ditampilkan compact.
- Card bukan alat untuk membuat semua section terlihat terpisah; gunakan card hanya ketika frame membantu pemahaman alur.
- Jangan menaruh card di dalam card. Pengecualian hanya untuk modal, empty state kecil, highlight total, atau grup pilihan yang benar-benar perlu frame sendiri.
- Pada mobile, card full width, padding 12px sampai 16px, dan tidak boleh membuat scroll horizontal.

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

Validasi form:
- Field wajib diberi label jelas. Tanda wajib boleh memakai teks kecil "Wajib" atau asterisk, tetapi jangan membuat label menjadi merah sebelum ada error.
- Pesan error berada tepat di bawah field terkait dengan jarak 6px.
- Pesan error memakai 14px / 400 / #ef1f2d dan line-height 1.35.
- Field error memakai border #ef1f2d dan focus ring rgba(239, 31, 45, 0.10).
- Helper text memakai 14px / 400 / #707971 dan diletakkan di bawah field sebelum error muncul.
- Jika error muncul, helper text boleh disembunyikan agar tidak terlalu ramai.
- Submit utama disabled sampai data minimal valid jika validasi bisa diketahui di sisi UI.
- Setelah submit gagal, fokus diarahkan ke field error pertama jika memungkinkan.
- Pesan error halaman/form umum boleh tampil di atas form, tetapi error spesifik tetap wajib dekat field.
- Jangan memakai alert browser default untuk validasi utama jika halaman sudah memiliki pola validasi sendiri.

Readonly dan disabled:
- Readonly dipakai untuk data yang boleh disalin/dibaca tetapi tidak boleh diedit. Tampilan tetap terbaca, background boleh #f9fafb, teks #404942.
- Disabled dipakai untuk kontrol yang benar-benar tidak bisa dipakai. Gunakan opacity 0.55, cursor not-allowed, dan jangan memunculkan hover aktif.
- Jangan menyamakan readonly dengan disabled untuk field penting seperti nominal, NIS, kode, atau rekening yang mungkin perlu disalin.

Format data:
- Rupiah layar: `Rp. 50.000,-` mengikuti pola aplikasi saat ini. Nominal di input boleh tanpa `Rp.` selama sedang diedit, misalnya `50.000`.
- Rupiah tabel dan ringkasan wajib rata kanan.
- Tanggal layar: `19/06/2026` untuk data tabel/riwayat; tanggal panjang boleh `19 Juni 2026` untuk dokumen atau tampilan detail.
- Jam transaksi: `09.45 WIB`.
- Periode bulan: `Juli 2026`.
- Rentang periode: `Juli 2025 - Juli 2026`.
- Nama siswa boleh uppercase jika data sumber sudah uppercase, tetapi jangan menaikkan ukuran atau bold hanya karena uppercase.
- Kode unit mengikuti penulisan resmi, misalnya `PAUD`, `MI`, `MTs`, `MA`, dan `PONPES`. Jangan mengubah `MTs` menjadi `MTS`.
- Kelas memakai format ringkas sesuai data, misalnya `7B`, `VII A`, atau `11A`; jangan menambahkan label "Kelas" di dalam nilai jika label sudah tersedia.
- NIS, NISN, nomor HP, rekening, dan kode lain memakai tabular-nums jika tersedia.
- Nomor HP boleh ditampilkan dengan spasi kelompok jika kebutuhan baca lebih penting, tetapi data mentah tetap disimpan normal.

Tombol dan Aksi

Ukuran dasar semua tombol:
- Tinggi tombol standar 40px
- Tinggi tombol compact untuk toolbar atau tabel padat 32px
- Padding horizontal tombol aksi halaman 14px mengikuti tombol Data Siswa; tombol form/dialog yang lebih panjang boleh 16px; tombol compact 12px
- Radius 8px
- Font 14px / 700, line-height 1
- Jarak ikon ke teks 8px
- Ukuran ikon 16px sampai 18px; jangan membesarkan ikon melebihi teks tombol
- Tombol teks desktop memiliki min-width 88px agar ukuran aksi utama stabil, kecuali ruangnya memang terbatas
- Tombol aksi halaman boleh memakai shadow halus standar Data Siswa: normal 0 4px 12px rgba(0, 0, 0, .05), hover 0 10px 20px rgba(0, 69, 40, .16)
- Label tombol harus singkat dan memakai kata kerja yang jelas, misalnya Tambah, Simpan, Cari, Tampilkan, Import, Konfirmasi, atau Hapus

Standar bahasa UI dan microcopy:
- Gunakan bahasa Indonesia yang ringkas, jelas, dan konsisten.
- Hindari campuran istilah Inggris jika sudah ada padanan yang umum di aplikasi, kecuali istilah teknis seperti Import, Export, atau PDF.
- Label tombol utama memakai kata kerja singkat: Tambah, Simpan, Terapkan, Reset, Import, Export, Cetak, Unduh, Bayar, Konfirmasi, Hapus, Batal, Kembali.
- Jangan memakai label generik seperti OK, Submit, Proses, Kirim, atau Ya jika konteksnya bisa dibuat lebih jelas.
- Tombol hapus memakai "Hapus", bukan "Delete".
- Tombol batal memakai "Batal", bukan "Cancel".
- Tombol kembali memakai "Kembali", bukan ikon saja jika berada di halaman detail/dokumen.
- Judul halaman memakai nama menu atau pekerjaan utama, misalnya Data Siswa, Pembayaran, Tagihan Siswa, Laporan Pembayaran.
- Deskripsi halaman satu kalimat pendek dan menjelaskan alur kerja, bukan promosi fitur.
- Empty state memakai kalimat netral, misalnya "Belum ada data." atau "Belum ada riwayat pembayaran untuk siswa ini."
- Error memakai kalimat aktif dan membantu, misalnya "Data gagal disimpan. Periksa kembali field yang ditandai."
- Success memakai kalimat pendek, misalnya "Data berhasil disimpan." atau "Pembayaran berhasil diproses."
- Konfirmasi aksi harus menyebut objek yang terdampak, misalnya "Hapus pembayaran SPP PAUD Maret 2026?"
- Jangan memakai tanda seru berlebihan pada pesan sistem.
- Jangan memakai teks instruksi panjang di dalam card jika label dan kontrol sudah jelas.
- Nama menu, label, dan status memakai Title Case secukupnya; isi kalimat tetap memakai kapital normal.

Tombol primer:
- Background #004528
- Teks #ffffff
- Hover #0d5f36
- Border 1px solid #004528 agar ukuran tidak berubah saat dibandingkan dengan tombol sekunder
- Dipakai untuk satu aksi utama pada satu halaman, section, form, atau modal
- Semua tombol aksi utama seperti Tambah, Simpan, Terapkan, Import, Konfirmasi, dan tombol submit utama lain wajib memakai warna primer ini agar konsisten dengan standar Data Siswa.

Standar tombol aksi halaman Data Siswa:
- Pola tombol Data Siswa menjadi acuan utama untuk tombol aksi kanan di heading halaman, seperti Tambah, Import, Export, dan aksi utama setara.
- Markup tombol memakai class dasar `button` dan class aksi spesifik yang netral terhadap warna, misalnya `button button-primary` atau `button action-primary`. Jangan memakai nama class yang menyebut warna lama seperti purple jika tampilan finalnya hijau.
- Teks tombol import file memakai label `Import` sesuai standar Data Siswa. Jangan membungkus teks tombol dengan elemen tambahan seperti `<span>` jika tombol standar Data Siswa tidak membutuhkannya.
- Tinggi 40px, min-height 40px, padding 0 14px, radius 8px, font 14px / 700, line-height 1, display inline-flex, align-items center, justify-content center, gap 8px, min-width 88px.
- Warna normal: background #004528, border 1px solid #004528, teks #ffffff, ikon #ffffff.
- Warna hover: background #0d5f36, border #0d5f36, teks #ffffff, ikon tetap #ffffff.
- Efek normal: box-shadow 0 4px 12px rgba(0, 0, 0, .05), transform translateY(0), transition transform .16s ease, box-shadow .16s ease, background-color .16s ease.
- Efek hover: box-shadow 0 10px 20px rgba(0, 69, 40, .16), transform translateY(-2px).
- Icon di dalam tombol berukuran 18px x 18px dan memakai warna/stroke #ffffff.
- Focus-visible memakai outline 2px solid #004528 dengan outline-offset 2px.
- Jangan memakai wrapper teks tambahan, shadow none, padding 16px, warna #157144, atau hover tanpa transform pada tombol aksi heading jika sedang mengikuti standar Data Siswa.

Tombol sekunder:
- Background #f3fbf6
- Teks #0d5f36
- Border 1px solid #b9dcc7
- Hover background #e9f8ef
- Hover teks #004528
- Hover border #157144
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

Standar ikon global:
- Gunakan ikon dari library yang konsisten jika tersedia, misalnya lucide atau ikon bawaan aplikasi.
- Ukuran ikon tombol teks: 16px sampai 18px.
- Ukuran ikon tombol icon-only standar: 18px.
- Ukuran ikon aksi tabel compact: 16px sampai 18px di dalam tombol 28px sampai 32px.
- Ikon mengikuti warna teks tombol melalui currentColor jika memungkinkan.
- Ikon dekoratif murni harus disembunyikan dari screen reader dengan `aria-hidden="true"`.
- Tombol icon-only wajib memiliki `aria-label` dan `title` yang menjelaskan aksi, misalnya Cetak, Unduh, Hapus, Detail, atau Bayar.
- Jangan memakai ikon yang artinya ambigu untuk aksi keuangan. Cetak memakai printer, unduh memakai download, hapus memakai trash, detail/struk memakai document/receipt.
- Ikon status tidak boleh menjadi satu-satunya penanda status; tetap sediakan teks atau label jika status penting.

Keadaan tombol:
- Focus-visible tombol memakai outline/ring 2px #004528 dengan offset 2px; focus tidak boleh hanya ditandai perubahan warna
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

Standar konfirmasi aksi:
- Aksi berisiko wajib memakai modal konfirmasi, bukan confirm browser default.
- Aksi berisiko meliputi Hapus, Batalkan Transaksi, Bayar, Konfirmasi Import, Reset Data, Naik Kelas, Pindah Kelas massal, dan perubahan status penting.
- Judul konfirmasi menyebut aksi dan objek, misalnya "Hapus Data Siswa?" atau "Konfirmasi Pembayaran?"
- Isi konfirmasi menjelaskan dampak dalam satu sampai dua kalimat.
- Jika aksi tidak bisa dibatalkan, tuliskan dengan jelas.
- Tombol utama modal memakai warna sesuai risiko: primer hijau untuk konfirmasi positif, merah untuk hapus/batal/reset berisiko.
- Tombol Batal selalu tersedia dan menjadi aksi aman.
- Untuk aksi keuangan, tampilkan ringkasan nominal, siswa, periode, dan metode sebelum user konfirmasi.
- Untuk import massal, tampilkan jumlah baris valid, jumlah baris gagal, dan konteks import sebelum tombol Konfirmasi Import aktif.
- Saat konfirmasi sedang diproses, tombol utama loading dan tombol lain disabled agar tidak klik ganda.
- Setelah berhasil, modal ditutup dan tampilkan toast/alert singkat.
- Setelah gagal, modal tetap terbuka jika user perlu memperbaiki atau membaca error.

Badge dan Chip

Badge/chip selalu minimal 14px.

Standar:
- Aktif/sukses: #157144 di atas #e9f8ef
- Nonaktif/lunas netral: #707971 di atas #f9fafb
- Error/bahaya: #ef1f2d di atas #fff4f4
- Warning: #92400e di atas #fff7ed

Nominal dan Keuangan

Nominal biasa memakai #020617, ukuran 14px sampai 16px, weight 500.

Nominal penting atau total boleh memakai #004528 atau #020617, ukuran 20px, weight 700.

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

State UI:
- Loading halaman memakai skeleton atau area kosong terstruktur, bukan spinner besar tanpa konteks.
- Loading tombol mempertahankan ukuran tombol agar layout tidak bergeser.
- Loading tabel/list tampil di area data yang sedang dimuat, bukan mengganti seluruh halaman.
- Empty state harus menjawab apa yang kosong dan apa langkah berikutnya jika ada.
- Empty state memakai background #fbfdf8, border #d1d5db atau #dfe5dc, teks #707971, padding 16px sampai 24px, radius 8px.
- Error state harus singkat, jelas, dan memberi aksi pemulihan seperti Coba Lagi, Kembali, atau Hubungi Admin jika relevan.
- Success state cukup memakai toast atau alert ringkas; jangan menambah card sukses besar kecuali prosesnya selesai dan halaman butuh konfirmasi resmi.
- Disabled state tidak boleh terlihat seperti error. Gunakan opacity/netral, bukan merah.
- Data kosong bukan error. Jangan memakai warna merah untuk empty state.
- Jika proses menyimpan data keuangan, cegah klik ganda dan tampilkan loading pada tombol submit.

Standar hak akses dan role:
- UI wajib mengikuti izin user aktif. Jangan menampilkan aksi yang tidak boleh dipakai hanya untuk kemudian gagal di server.
- Aksi utama seperti Tambah, Edit, Hapus, Bayar, Import, Export, Cetak, dan Konfirmasi hanya tampil jika user punya izin.
- Jika aksi disembunyikan membuat alur membingungkan, boleh tampil disabled dengan tooltip/alasan singkat, misalnya "Tidak memiliki akses".
- Jangan memakai warna merah untuk tombol disabled karena disabled bukan error.
- Kolom Aksi pada tabel boleh disembunyikan jika tidak ada satu pun aksi yang bisa dilakukan user.
- Menu sidebar yang tidak diizinkan tidak perlu ditampilkan.
- Data sensitif keuangan hanya tampil sesuai role; jika role hanya boleh melihat ringkasan, jangan tampilkan tombol detail/cetak transaksi.
- Server tetap menjadi sumber keamanan utama. Standar UI ini hanya untuk kejelasan pengalaman, bukan pengganti validasi permission server.
- Jika user kehilangan akses saat berada di halaman, tampilkan pesan netral dan tombol Kembali, bukan halaman kosong.
- Audit aksi penting seperti hapus, import, dan pembayaran harus mencatat user/petugas jika sistem mendukung.

Modal dan dialog:
- Modal dipakai untuk konfirmasi, form pendek, atau pilihan yang tidak perlu halaman baru.
- Proses panjang, tabel besar, detail resmi, atau dokumen cetak lebih baik memakai halaman sendiri.
- Backdrop memakai rgba(2, 6, 23, 0.38) pada mode terang.
- Panel modal background #ffffff, border #d1d5db, radius 8px, shadow halus, width sesuai konteks.
- Lebar modal kecil 420px, modal form 560px, modal besar maksimal 720px. Jangan memakai modal terlalu lebar untuk data tabel besar.
- Padding modal 24px desktop dan 16px mobile.
- Judul modal 16px / 700 / #020617.
- Deskripsi modal 14px / 400 / #707971.
- Footer modal memakai jarak atas 20px, tombol sejajar kanan pada desktop, dan full width/stack pada mobile jika ruang sempit.
- Tombol primer modal berada paling kanan; tombol batal di kiri dari tombol primer.
- Modal konfirmasi hapus memakai judul jelas, isi menyebut objek yang akan dihapus, tombol bahaya sebagai aksi utama, dan tombol Batal sebagai aksi sekunder.
- Modal harus bisa ditutup dengan tombol close atau Batal. Escape/backdrop boleh aktif kecuali tindakan sedang loading.
- Saat modal terbuka, fokus masuk ke modal; setelah modal ditutup, fokus kembali ke tombol pemicu jika memungkinkan.

Alert dan toast:
- Toast dipakai untuk feedback singkat setelah aksi berhasil/gagal, misalnya data tersimpan, transaksi berhasil, atau import selesai.
- Alert inline dipakai untuk informasi yang perlu dibaca sebelum user lanjut, misalnya error validasi umum atau peringatan import.
- Toast posisi kanan atas pada desktop dan atas penuh/center pada mobile jika ruang sempit.
- Toast maksimal 1 sampai 2 baris teks, font 14px / 500, radius 8px, padding 12px 14px.
- Toast sukses memakai #157144 di atas #e9f8ef.
- Toast error memakai #ef1f2d di atas #fff4f4.
- Toast warning memakai #92400e di atas #fff7ed.
- Toast info memakai #2563eb di atas #eff6ff dan hanya untuk informasi murni.
- Durasi toast normal 3 sampai 5 detik. Error penting boleh tetap tampil sampai ditutup.
- Jangan menampilkan toast dan alert dengan pesan yang sama secara bersamaan.
- Teks toast memakai kalimat aktif dan singkat, misalnya "Pembayaran berhasil disimpan." atau "Data gagal dihapus."

Aksesibilitas dasar:
- Semua tombol icon-only wajib punya `aria-label`.
- Semua input wajib punya label yang terhubung atau label visual yang jelas.
- Focus-visible wajib terlihat pada tombol, link, input, select, textarea, sidebar, pagination, dan item menu.
- Jangan menghapus outline fokus tanpa pengganti yang jelas.
- Target klik minimal 32px untuk tabel compact dan 40px untuk tombol/form utama.
- Warna tidak boleh menjadi satu-satunya pembeda status. Gunakan teks, label, atau ikon pendamping.
- Kontras teks utama dan teks sekunder harus tetap terbaca di atas background putih maupun panel lembut.
- Dialog/modal harus memiliki judul yang jelas dan fokus tidak boleh berpindah ke belakang modal saat modal aktif.
- Link harus terlihat sebagai link atau aksi, bukan hanya teks hijau biasa di antara paragraf.

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

Standar Dashboard dan Chart

- Dashboard dipakai untuk ringkasan dan prioritas kerja, bukan untuk menggantikan halaman laporan lengkap.
- Urutan dashboard: heading ringkas, ringkasan angka utama, chart/visual jika membantu, daftar prioritas/aktivitas terbaru.
- Card statistik memakai background #ffffff, border #d1d5db, radius 8px, padding 16px sampai 20px.
- Label statistik memakai 14px / 400 / #707971.
- Angka statistik memakai 20px / 700 / #020617 atau #004528 jika angka itu menjadi fokus utama.
- Maksimal satu angka paling dominan dalam satu card statistik.
- Chart memakai warna terbatas dan konsisten. Hijau untuk data utama/positif, merah untuk bahaya/tunggakan, kuning untuk peringatan, biru hanya untuk info murni.
- Chart tidak boleh memakai palet warna acak atau terlalu banyak warna dalam satu visual.
- Legend memakai 14px / 400 dan warna teks #404942 atau #707971.
- Tooltip chart memakai background #ffffff, border #d1d5db, radius 8px, teks 14px, dan shadow halus.
- Label sumbu dan angka chart harus tetap terbaca, minimal 14px jika tampil sebagai teks UI.
- Empty chart memakai empty state standar, misalnya "Belum ada data untuk periode ini."
- Jika chart tidak membantu keputusan cepat, gunakan tabel/ringkasan angka saja.
- Dashboard mobile harus menumpuk satu kolom; chart tidak boleh menyebabkan scroll horizontal halaman.
- Jangan membuat dashboard terasa seperti landing page dengan hero besar, ilustrasi dekoratif, atau angka raksasa tanpa konteks kerja.

Standar Audit Log dan Riwayat Aktivitas

- Audit log dipakai untuk melacak aksi penting pada data siswa, keuangan, import, hapus, perubahan status, pindah kelas, naik kelas, dan pengaturan sistem.
- Item audit minimal mencatat waktu, petugas/user, aksi, objek/data terdampak, dan hasil aksi.
- Jika relevan, audit mencatat nilai sebelum dan sesudah perubahan.
- Format waktu audit mengikuti standar data: tanggal `19/06/2026`, jam `09.45 WIB`.
- Nama aksi memakai bahasa kerja yang jelas, misalnya Membuat, Mengubah, Menghapus, Mengimport, Membayar, Mencetak, Membatalkan, atau Mengubah Status.
- Riwayat terbaru di halaman detail cukup menampilkan 5 sampai 10 aktivitas terakhir.
- Riwayat lengkap ditampilkan di halaman laporan/audit khusus jika datanya panjang.
- Audit log tampil sebagai list compact atau tabel, bukan card besar berulang.
- Kolom audit umum: Waktu, Petugas, Aksi, Objek, Keterangan, Status.
- Status audit memakai badge standar: sukses, gagal, peringatan, atau netral.
- Teks keterangan audit memakai 14px / 400 / #707971; aksi/objek penting boleh 14px / 500 atau 700 sesuai kebutuhan.
- Aksi audit tidak boleh bisa diedit dari UI biasa. Jika ada koreksi, buat catatan koreksi baru.
- Data audit sensitif mengikuti hak akses role.

Standar Halaman Transaksi dan Pembayaran

Halaman transaksi harus operasional dan langsung usable. Jangan membuat landing page atau hero.

Standar import dan export global:
- Import dipakai untuk memasukkan data massal dan wajib memiliki konteks yang jelas sebelum file diproses.
- Flow import standar: pilih konteks, pilih file, preview, validasi, ringkasan valid/gagal, konfirmasi import, hasil import.
- Tombol Download Template ditampilkan jika format file tidak sederhana atau rawan salah.
- File upload menerima tipe file yang jelas, misalnya `.xlsx` atau `.csv`, dan menolak tipe lain dengan pesan error.
- Setelah file dipilih, tampilkan nama file, ukuran jika tersedia, dan tombol ganti/hapus file.
- Preview import wajib menampilkan baris valid dan baris bermasalah sebelum data disimpan permanen.
- Error import per baris harus menyebut nomor baris dan alasan yang bisa diperbaiki.
- Ringkasan import minimal menampilkan total baris, valid, gagal, dan duplikat jika ada.
- Tombol Konfirmasi Import disabled jika tidak ada baris valid.
- Import massal wajib memakai konfirmasi sebelum simpan akhir.
- Setelah import berhasil, tampilkan ringkasan hasil dan arahkan user kembali ke halaman data terkait atau tampilkan hasil di halaman yang sama.
- Export dipakai untuk mengambil data sesuai filter aktif, bukan selalu seluruh data, kecuali tombolnya jelas menyebut Export Semua.
- Tombol Export/Cetak berada di kanan heading atau toolbar sesuai konteks halaman.
- Export Excel untuk data mentah/tabel; PDF/Cetak untuk dokumen resmi, laporan, surat, struk, atau kwitansi.
- Nama file export memakai format jelas, misalnya `laporan-pembayaran-2026-07.xlsx`.
- Export tidak boleh mengabaikan permission user.
- Jika data kosong, tombol export boleh disabled atau menampilkan pesan "Tidak ada data untuk diexport."

Standar import SPP bulanan:
- Import SPP dilakukan per Unit Pendidikan, Bulan, dan Tahun dari form sebelum file Excel dipreview.
- Field konteks import SPP wajib: Unit Pendidikan, Bulan, Tahun, dan File Excel.
- Unit Pendidikan, Bulan, dan Tahun pada form menjadi sumber utama periode import.
- Kolom Unit Pendidikan, Bulan, dan Tahun di Excel boleh tetap ada, tetapi hanya dipakai sebagai validasi silang. Jika nilainya berbeda dengan pilihan form, baris wajib ditolak.
- Jika Excel tidak memiliki kolom Unit Pendidikan, Bulan, atau Tahun, import tetap berjalan memakai konteks dari form.
- Kolom minimal Excel untuk import SPP: NIS, Nama, Cara bayar, Waktu, dan Nominal. Kolom Petugas boleh opsional.
- Form import mengikuti standar form: label di atas field, tinggi field 40px, radius 8px, border #d1d5db, gap antar field 12px, dan tombol Preview memakai tombol primer #004528.

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
- Card Daftar Tagihan pada menu Pembayaran memakai judul card 16px / 700, item tagihan 14px, dan Total Tagihan 16px / 700.
- Garis pemisah pada Card Daftar Tagihan harus sejajar dengan inset kiri-kanan 16px.
- Jarak garis bawah judul Card Daftar Tagihan ke item tagihan pertama memakai 12px.
- Jarak antar item tagihan memakai padding vertikal 12px.
- Baris Total Tagihan memakai padding vertikal 12px, rata tengah vertikal, dan dipisahkan dengan satu garis atas yang sejajar dengan garis lainnya.

Standar halaman Tagihan Siswa:
- Halaman Tagihan Siswa memakai kanvas data maksimal 1200px, rata tengah, background putih, dan urutan Heading, Tabel Ringkasan Per Unit tanpa judul/subdeskripsi, Card Filter, Toolbar tabel, Tabel Tagihan Siswa, Pagination jika diperlukan, lalu footer copyright.
- Judul halaman memakai 20px / 700, deskripsi 14px / 400, jarak judul ke deskripsi 4px, jarak blok heading ke tabel ringkasan 16px, dan jarak tabel ringkasan ke card filter 16px.
- Card filter Tagihan Siswa mengikuti standar card filter Data Siswa: background #ffffff, border #d1d5db, radius 12px, padding 16px, gap 12px, field 40px, label 14px / 400, tombol Terapkan primer #004528, dan tombol Reset putih border #d1d5db.
- Card filter Tagihan Siswa diletakkan setelah tabel ringkasan per unit dan tepat sebelum toolbar/tabel siswa. Urutan filter Tagihan Siswa: Unit Pendidikan, Kelas, Terapkan, lalu Reset. Cari Siswa tidak masuk card filter.
- Filter Tahun Tagihan, Sampai Bulan, Status Tagihan, dan Kategori tidak ditampilkan di card filter Tagihan agar panel tetap ringkas untuk admin/bendahara.
- Ringkasan Tagihan Siswa tidak dibuat card-card besar dan tidak memakai strip total di atas tabel. Nominal pembayaran masuk seperti Terbayar ditempatkan di menu Laporan, bukan di menu Tagihan.
- Tampilkan tabel ringkasan per unit tanpa judul atau subdeskripsi tambahan. Kolom acuan: No, Unit Pendidikan, Siswa, Jumlah Tagihan.
- Tabel Ringkasan Per Unit mengikuti standar tabel compact 40px: header rata tengah, No rata tengah, Unit Pendidikan rata kiri dan menampilkan nama unit tanpa kode unit, jumlah siswa rata tengah, dan nominal rata kanan. Total ditampilkan sebagai satu baris `tfoot` compact: label Total Keseluruhan rata tengah memakai `colspan="2"` pada area kolom No dan Unit Pendidikan, total siswa berupa angka saja di kolom Siswa, dan total nominal langsung di kolom Jumlah Tagihan tanpa label bertumpuk.
- Toolbar tabel Tagihan Siswa menampilkan kontrol jumlah data di kiri dan Cari Siswa di kanan. Cari Siswa menerima Nama/NIS/NISN dan mempertahankan filter Unit Pendidikan, Kelas, `per_page`, `sort`, dan `direction`. Pada desktop, label `Cari Siswa` sejajar di samping input-card, bukan di atas; input memakai tinggi 36px, radius 8px, dan lebar acuan 280px seperti pola toolbar laporan. Pada mobile, label dan input menjadi satu kolom full width. Select jumlah data memakai opsi 10/25/50/100/500/Semua dengan ukuran 78px x 34px. Pada mode tanpa pagination, info kanan boleh menampilkan "Menampilkan 1-10 dari ... siswa". Pada mode dengan pagination, info hasil ditempatkan di footer bawah tabel sesuai standar pagination. Jarak toolbar ke tabel siswa wajib 16px, dihitung dari bawah toolbar/select/input ke atas header tabel. Jika parent workspace sudah memakai gap 16px, toolbar tidak boleh menambah margin-bottom lagi agar jarak tidak dobel.
- Setelah toolbar jumlah data, langsung tampilkan tabel siswa. Jangan menampilkan subjudul duplikat seperti "Daftar Tagihan Siswa" atau ringkasan ulang "Total ... SPP ... Lain-lain ..." di atas tabel siswa.
- Tabel utama Tagihan Siswa memakai tabel compact ke bawah, bukan card siswa berulang. Header dan baris utama tinggi 40px, header rata tengah, isi nama siswa rata kiri, nominal rata kanan, dan baris terakhir tidak boleh memiliki garis bawah penutup.
- Kolom tabel Tagihan Siswa: No, NIS, Nama Siswa, Unit, Kelas, Total Tagihan, Aksi.
- Header tabel Tagihan Siswa memakai Title Case rata tengah. Kolom sortable memakai ikon sort kecil standar seperti Menu Laporan; kolom No dan Aksi tetap teks polos tanpa ikon. Jangan memakai kapital semua kecuali singkatan resmi seperti NIS.
- Kolom Aksi berisi ikon transparan tanpa card/border, ukuran tombol sekitar 28px: ikon Detail dan ikon Bayar, masing-masing wajib memiliki `aria-label`/`title`. Jangan memakai teks di tombol aksi tabel Tagihan agar kolom Kelas tetap longgar.
- Lebar kolom tabel Tagihan Siswa acuan desktop: No 48px, NIS 84px, Unit 74px, Kelas sekitar 168px, Total Tagihan sekitar 136px, Aksi sekitar 60px, dan Nama Siswa memakai ruang fleksibel tersisa.
- Rincian SPP dan Lain-lain tidak ditampilkan sebagai kolom/baris detail di tabel utama. Tombol Detail membuka halaman Surat Tagihan Siswa siap cetak, bukan modal kecil, agar admin/bendahara bisa melihat rincian resmi dan mencetak pemberitahuan untuk wali murid.
- Surat Tagihan Siswa memakai ukuran setengah F4 portrait, yaitu 16.5cm x 21.5cm (165mm x 215mm), background putih, font cetak resmi, judul resmi "Penertiban Administrasi Keuangan", identitas siswa, tabel rincian No, Uraian, Tahun, Rp., Jml Bulan, Jumlah, total keseluruhan, terbilang, tanggal, dan tanda tangan. Toolbar layar hanya berisi Kembali, Bayar, Unduh, dan Cetak, lalu wajib hilang saat print.
- Pagination Tagihan Siswa tampil jika total data lebih dari `per_page`, karena data tagihan bisa besar. Gunakan standar pagination aplikasi: tidak memakai teks Inggris bawaan seperti "Showing ... results", tidak memakai teks "Halaman x dari y", tombol normal 14px / 500, tombol aktif 14px / 700, tinggi tombol 36px, radius 8px, gap 8px, aktif background #004528 dan teks #ffffff, normal background #ffffff border #d1d5db teks #334155, disabled background #f9fafb border #d1d5db teks #707971.
- Pada desktop, footer pagination Tagihan menampilkan info hasil di kiri dan tombol navigasi di kanan. Pada mobile, footer pagination menjadi satu kolom: info hasil di atas tombol, tombol boleh wrap rapi, dan tidak boleh membuat halaman melebar.
- Menu Tagihan Siswa wajib responsif mobile: halaman tidak boleh melebar ke kanan; parent grid/section tabel wajib `min-width:0`, hanya `.table-wrap` yang boleh scroll horizontal, filter menjadi satu kolom, tombol filter menjadi dua kolom atau full width jika ruang tidak cukup, dan dialog detail berubah satu kolom. Tabel ringkasan per unit di mobile memakai min-width sekitar 520px, sedangkan tabel siswa boleh sekitar 760px.

Standar Print, PDF, Struk, dan Kwitansi

- Tampilan print/PDF mengutamakan keterbacaan resmi, bukan gaya card aplikasi.
- Background print selalu putih, teks utama hitam, dan aksen warna dipakai sangat terbatas.
- Toolbar layar seperti Kembali, Cetak, Unduh, dan Bayar wajib hilang saat print.
- Margin dokumen resmi mengikuti ukuran kertas yang dipakai; untuk Surat Tagihan setengah F4 portrait gunakan padding ringkas agar kop, isi, total, terbilang, tanggal, dan tanda tangan tetap muat tanpa terpotong.
- Font print boleh lebih kecil dari standar UI, misalnya 11.5px, 12.5px, 14px, dan 18px, karena kebutuhan cetak berbeda.
- Judul dokumen resmi memakai ukuran paling dominan tetapi tetap wajar, bukan gaya hero.
- Kop surat, identitas lembaga, nomor surat, tanggal, dan tanda tangan harus konsisten antar dokumen resmi.
- Tabel print memakai garis tipis hitam/abu, padding cell cukup, dan nominal rata kanan.
- Total akhir pada dokumen keuangan harus jelas, tebal, dan berada dekat rincian nominal.
- Terbilang ditampilkan jika dokumen menjadi bukti atau surat resmi pembayaran/tagihan.
- Struk transaksi lebih ringkas daripada surat resmi: identitas transaksi, siswa, rincian pembayaran, total, metode, waktu, petugas, dan nomor referensi jika ada.
- Kwitansi/struk tidak memakai warna UI seperti button hijau atau background panel.
- Print tidak boleh memotong tabel penting di tengah tanpa header lanjutan jika dokumen lebih dari satu halaman.
- Hindari elemen interaktif, ikon tombol, shadow, radius besar, dan card dekoratif pada dokumen print.
- Format nominal print tetap konsisten dengan aplikasi, misalnya `Rp. 50.000,-`.
- Semua dokumen keuangan harus bisa dibaca dalam hitam-putih.

Standar Mode Terang dan Gelap

- Aplikasi mendukung mode terang dan gelap global.
- Pilihan tema disimpan di browser dengan `localStorage` agar tetap sama setelah pindah halaman atau login ulang.
- Tombol tema memakai tombol ikon standar 40px x 40px, radius 8px, ikon 18px, dan ditempatkan di topbar sebelum notifikasi. Pada halaman login, tombol boleh tampil sebagai tombol kecil di kanan atas.
- Mode terang tetap menjadi standar utama aplikasi: background halaman #ffffff atau #fbfdf8, panel #ffffff, border #d1d5db, teks utama #020617, teks sekunder #404942/#707971, dan aksen hijau tua #004528.
- Mode gelap memakai palet hijau gelap, bukan hitam pekat total: background halaman #07140f, panel #0b1b14, field/table header #0f241a atau #102419, border #284337/#335247, teks utama #e7f0ea, teks sekunder #b9c9c0, placeholder #8ea199, aksen #57c785 atau tombol primer #00693d.
- Struktur, ukuran, spacing, radius, tinggi field 40px, tinggi tombol 40px, dan tinggi tabel compact tetap sama di mode terang maupun gelap.
- Mode gelap tidak boleh mengubah layout, lebar kolom, tinggi baris, posisi tombol, atau urutan elemen.
- Status, danger, warning, success, dan nominal penting tetap harus terbaca jelas di atas panel gelap.

Rumus Aman Implementasi

Gunakan:
- Kanvas: #ffffff
- Panel lembut: #fbfdf8
- Teks utama: #020617
- Teks sekunder: #404942 atau #707971
- Label: #334155
- Border: #d1d5db
- Aksi utama: #004528
- Status aktif: #157144
- Hover aksi utama: #0d5f36
- Brand/nominal penting: #004528
- Font utama: Inter/system sans
- Ukuran font: 14px, 16px, dan 20px
- Field form: 40px
- Radius umum: 8px

Dengan standar ini, aplikasi MA'WA CENTER harus terasa putih, netral, rapi, operasional, konsisten, dan tidak semu biru.
