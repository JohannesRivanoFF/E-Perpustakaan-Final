<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

function status_response(bool $success, string $message, int $statusCode = 200, array $extra = []): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function ensure_payment_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pengajuan_peminjaman (
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
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pembayaran_pengajuan (
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
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pembayaran_peminjaman (
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
        )"
    );
}

$reference = trim((string) ($_GET['reference'] ?? ''));

if ($reference === '') {
    status_response(false, 'Reference pembayaran wajib diisi.', 422);
}

try {
    ensure_payment_table($pdo);

    $statement = $pdo->prepare(
        'SELECT reference, amount, currency, qr_url, qr_string, status, verified_at, created_at, id_peminjaman
         FROM pembayaran_pengajuan
         WHERE reference = ?
         LIMIT 1'
    );
    $statement->execute([$reference]);
    $payment = $statement->fetch();

    if (!$payment) {
        $legacyStatement = $pdo->prepare(
            'SELECT reference, amount, currency, qr_url, qr_string, status, verified_at, created_at, id_peminjaman
             FROM pembayaran_peminjaman
             WHERE reference = ?
             LIMIT 1'
        );
        $legacyStatement->execute([$reference]);
        $payment = $legacyStatement->fetch();
    }

    if (!$payment) {
        status_response(false, 'Data pembayaran tidak ditemukan.', 404);
    }

    status_response(true, 'Status pembayaran ditemukan.', 200, [
        'payment' => $payment,
    ]);
} catch (Throwable) {
    status_response(false, 'Status pembayaran belum bisa dimuat.', 500);
}
