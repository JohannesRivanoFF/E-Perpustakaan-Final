-- Tambahan integrasi rekomendasi, QR payment, dan verifikasi Gmail.
-- Jalankan setelah database utama sudah di-import.

CREATE TABLE IF NOT EXISTS pembayaran_peminjaman (
    id_pembayaran INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT UNSIGNED NOT NULL,
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
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rekomendasi_log (
    id_rekomendasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT UNSIGNED DEFAULT NULL,
    id_buku INT UNSIGNED NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    source VARCHAR(50) NOT NULL DEFAULT 'external_api',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
);
