# E-Perpustakaan

Project web perpustakaan digital berbasis PHP, MySQL, HTML, CSS, dan JavaScript.

## Struktur Folder

```text
E-Perpustakaan/
├── admin/          Halaman pengelolaan buku, anggota, dan peminjaman
├── api/            Endpoint AJAX untuk katalog, statistik, rating, dan peminjaman
├── assets/         Aset statis aplikasi
│   ├── css/        Stylesheet utama
│   ├── images/     Gambar tampilan
│   └── js/         Script frontend
├── config/         Konfigurasi database dan helper global
├── database/       File SQL untuk import database
├── uploads/        Cover buku hasil upload
├── detail_buku.php Halaman detail buku
├── index.php       Landing page dan form peminjaman
└── koneksi.php     Alias kompatibilitas ke config/database.php
```

## Catatan

- Jangan pindahkan folder `uploads/` tanpa memperbarui data `cover_buku` di database.
- File `koneksi.php` sengaja dipertahankan agar `require_once` lama di halaman admin dan API tetap berjalan.
- Import database dari `database/perpustakaan_digital.sql`, lalu sesuaikan kredensial di `config/database.php` bila diperlukan.
- Integrasi tambahan memakai konfigurasi environment di `config/integrations.php`.

## Integrasi API

Jalankan migrasi `database/migrations/2026_06_17_integrasi_rekomendasi_pembayaran.sql` bila ingin menyiapkan tabel secara manual. Endpoint peminjaman juga akan membuat tabel integrasi otomatis saat pertama dipakai.

Untuk XAMPP, cara paling mudah adalah salin `config/integrations.local.example.php` menjadi `config/integrations.local.php`, lalu isi credential API di sana. File lokal itu akan menimpa konfigurasi default.

Environment variable yang juga tersedia:

```text
RECOMMENDATION_API_ENABLED=true
RECOMMENDATION_API_URL=https://example.com/recommend
RECOMMENDATION_API_KEY=...

PAYMENT_QR_ENABLED=true
PAYMENT_QR_API_URL=https://example.com/payment/qr
PAYMENT_QR_API_KEY=...
PAYMENT_QR_MERCHANT_ID=...
PAYMENT_QR_AMOUNT=0
PAYMENT_QR_CURRENCY=IDR
PAYMENT_QR_QRIS_STATIC_PAYLOAD=...
PAYMENT_QR_BASE_URL=http://localhost/E-Perpustakaan

GMAIL_API_ENABLED=true
GMAIL_ACCESS_TOKEN=...
GMAIL_USER_ID=me
GMAIL_PAYMENT_QUERY=from:payment@example.com newer_than:7d
GMAIL_VERIFICATION_SECRET=isi-token-rahasia
```

Verifikasi otomatis pembayaran dapat dipanggil dari cron/browser:

```text
api/verifikasi_pembayaran.php?secret=isi-token-rahasia
api/verifikasi_pembayaran.php?reference=LOAN-1-20260617120000&secret=isi-token-rahasia
```
