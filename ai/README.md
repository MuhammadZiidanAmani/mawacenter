# Dokumentasi AI MA'WA CENTER

Diperbarui: 21 September 2026. Mulai dari dokumen di bawah agar konteks aplikasi tidak perlu dirakit ulang dari banyak PRD.

## Urutan Baca

| Urutan | Dokumen | Isi |
| --- | --- | --- |
| 1 | [PRD-MAWA-CENTER.md](PRD-MAWA-CENTER.md) | PRD gabungan: tujuan, modul, role, aturan bisnis, data, route, acceptance criteria, risiko, dan perbedaan terhadap kode |
| 2 | [DESIGN-SYSTEM-TAILWIND.md](DESIGN-SYSTEM-TAILWIND.md) | Standar UI, token warna, tipografi, komponen, responsif, mode gelap, print, dan pola Tailwind v4 |
| 3 | [tailwind-theme.reference.css](tailwind-theme.reference.css) | Contoh penghubung token CSS aplikasi ke utility Tailwind; belum diimpor ke aplikasi |
| 4 | Kode/test pada peta modul PRD induk | Bukti perilaku dan implementasi yang sebenarnya |

PRD induk menggabungkan kebutuhan semua PRD sumber. Design system menjadi pasangan PRD untuk rincian visual. Sepuluh dokumen lama yang sudah tercakup telah dihapus agar AI membaca acuan yang konsisten. Sumber historis tetap dapat ditelusuri melalui riwayat Git.

## Cara Memakai

- Perubahan fitur: baca bagian modul, aturan lintas modul, acceptance criteria, dan daftar konflik pada PRD induk.
- Perubahan tampilan: baca design system, lalu audit Blade, CSS modul, aturan akhir `app.css`, serta JavaScript terkait.
- Perubahan akses: baca matrix role dan `EnsureRolePermission`, kemudian scope query controller/service.
- Import atau keuangan: baca ownership data, status pembayaran, validasi server, dan ketentuan isolasi test.
- Catatan rilis historis pada PRD induk hanya menjelaskan keadaan saat rilis tersebut. Cocokkan kembali route/file dan hasil tes terbaru.

Contoh arahan kerja:

```text
Baca AGENTS.md, ai/PRD-MAWA-CENTER.md, dan ai/DESIGN-SYSTEM-TAILWIND.md.
Kerjakan modul [nama modul] sesuai kebutuhan [perubahan].
Periksa perbedaan PRD dengan kode, pertahankan aturan bisnis dan scope akses,
lalu verifikasi bagian yang berubah. Laporkan perubahan serta batas verifikasi.
```

## Inventaris Sumber

ID S01-S10 pada PRD dan design system adalah penanda asal kebutuhan, bukan file yang perlu dibaca lagi. Nama di bawah merupakan dokumen historis yang sudah dihapus setelah konsolidasi; gambar V01 tetap tersedia.

| ID | Dokumen Asal | Digabungkan Ke |
| --- | --- | --- |
| S01 | `PRD Multi Login.md` | PRD bagian 3, 4, 5, 9, 12, 13 |
| S02 | `PRD Menu Manajemen Siswa.md` | PRD bagian 5, 6, 12, 13, 14 |
| S03 | `PRD-Menu-Pembayaran-Mawa-Center-Revisi-Typography.md` | PRD bagian 5, 7, 12, 13; design system bagian 3, 5, 6 |
| S04 | `PRD Menu Tagihan.md` | PRD bagian 5, 8, 12, 13; design system tabel dan print |
| S05 | `PRD Menu Laporan.md` | PRD bagian 5, 10, 12, 13; design system tabel dan export |
| S06 | `standar aplikasi.md` | Design system seluruh bagian; PRD kebutuhan lintas modul |
| S07 | `Release Note Menu Manajemen Siswa P0-P3.md` | PRD bagian 3, 6.8, 14; klaim rilis ditandai historis |
| S08 | `PR Checklist Menu Manajemen Siswa P0-P3.md` | PRD bagian 13, 14; design system QA |
| S09 | `Deploy Handoff Menu Manajemen Siswa P0-P3.md` | PRD bagian 14; command lama harus mengikuti isolasi test terbaru |
| S10 | `Backlog Cleanup CSS Menu Manajemen Siswa.md` | Design system bagian 9 dan 11; backlog, bukan pekerjaan otomatis |
| V01 | [Sebelum.jpeg](Sebelum.jpeg), [Setelah.jpeg](Setelah.jpeg) | Referensi perubahan layout Pembayaran; warna mengikuti aplikasi |

## Pemeliharaan

Perbarui PRD induk ketika aturan produk berubah, design system ketika aturan visual berubah, dan tabel perbedaan ketika suatu target benar-benar terimplementasi. Cantumkan sumber keputusan serta tanggal. Jangan menulis klaim "selesai" hanya karena sebuah view, route, atau dokumen pernah ada.
