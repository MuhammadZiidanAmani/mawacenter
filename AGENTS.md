# Panduan AI MA'WA CENTER

## Mulai Membaca

1. Baca [indeks dokumentasi](ai/README.md).
2. Baca [PRD induk](ai/PRD-MAWA-CENTER.md) untuk aturan produk, akses, data, dan peta kode.
3. Untuk pekerjaan UI, baca [design system CSS/Tailwind](ai/DESIGN-SYSTEM-TAILWIND.md).
4. Baca implementasi dan test modul yang akan diubah sebelum membuat perubahan.

## Aturan Proyek

- Aplikasi memakai Laravel, Blade, JavaScript, Vite, dan Tailwind CSS v4 dengan CSS modular. Ikuti pola lokal; jangan mengganti framework hanya untuk perubahan UI.
- PRD adalah kebutuhan produk, bukan bukti fitur sudah tersedia. Periksa daftar perbedaan dokumen/kode di PRD induk.
- Instruksi pengguna terbaru mengarahkan pekerjaan. Jangan otomatis mengerjakan seluruh backlog yang tercantum di dokumen.
- UI berbahasa Indonesia dan memakai token `--app-*` dari `resources/css/app.css`.
- Scope CSS per halaman dalam `resources/css/modules/`. Audit cascade sebelum mengubah aturan legacy yang memiliki specificity tinggi atau `!important`.
- Pertahankan permission dan scope unit/siswa di server, termasuk pada pencarian, export, bukti transfer, dan struk.
- Gabung identitas hanya mengubah `identity_student_id`; jangan memindahkan transaksi/tagihan dari `student_id` asal.
- Transfer wali berstatus `Pending` tidak mengurangi tagihan. Hanya pembayaran `Diterima` yang boleh diperhitungkan sebagai pembayaran sah.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, reset, atau seeder terhadap database operasional sebagai bagian dari pengujian.
- Sebelum test database, pertahankan isolasi `phpunit.xml`: `APP_ENV=testing`, SQLite `:memory:`, `DB_URL` kosong, cache konfigurasi test terpisah, dan guard dalam `tests/TestCase.php`. Jika guard gagal, perbaiki isolasi; jangan menonaktifkannya.
- Jalankan test relevan untuk perubahan perilaku dan `npm run build` untuk perubahan frontend. Dokumentasi saja cukup diperiksa tautan, konsistensi, dan contoh teknisnya.
- Pertahankan perubahan pengguna yang sudah ada. Gunakan PRD induk dan design system sebagai acuan; dokumen lama sudah dikonsolidasikan dan dihapus dari folder `ai`. Jangan menganggap hasil tes dalam catatan rilis historis sebagai hasil pengujian saat ini.
- Jangan memasukkan kata sandi, `.env`, hash kredensial, atau data pribadi siswa ke dokumentasi/contoh.
