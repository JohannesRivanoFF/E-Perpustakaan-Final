-- Pembayaran awal peminjaman.
-- Nominal dihitung aplikasi: jumlah hari pinjam x Rp1.000.

ALTER TABLE anggota ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL AFTER no_hp;

CREATE TABLE IF NOT EXISTS pengajuan_peminjaman (
    id_pengajuan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    id_buku INT UNSIGNED NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
    id_peminjaman INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE RESTRICT,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pembayaran_pengajuan (
    id_pembayaran INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengajuan INT UNSIGNED NOT NULL,
    id_peminjaman INT UNSIGNED DEFAULT NULL,
    reference VARCHAR(80) NOT NULL UNIQUE,
    amount INT UNSIGNED NOT NULL DEFAULT 0,
    currency VARCHAR(8) NOT NULL DEFAULT 'IDR',
    qr_url VARCHAR(500) DEFAULT NULL,
    qr_string TEXT DEFAULT NULL,
    status ENUM('pending', 'verified', 'manual', 'failed', 'expired') NOT NULL DEFAULT 'pending',
    verified_at DATETIME DEFAULT NULL,
    raw_response JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengajuan) REFERENCES pengajuan_peminjaman(id_pengajuan) ON DELETE CASCADE,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifikasi_email (
    id_notifikasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT UNSIGNED DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
);
