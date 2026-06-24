-- Prototype scan-to-pay:
-- Pengajuan dibuat pending, stok baru berkurang setelah QR pembayaran discan.

CREATE TABLE IF NOT EXISTS pengajuan_peminjaman (
    id_pengajuan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
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
