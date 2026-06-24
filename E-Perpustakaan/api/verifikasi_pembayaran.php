<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../app/Services/GmailVerificationService.php';

$integrations = require __DIR__ . '/../config/integrations.php';
$gmailConfig = $integrations['gmail'] ?? [];

function verify_response(bool $success, string $message, int $statusCode = 200, array $extra = []): void
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

$secret = (string) ($gmailConfig['verification_secret'] ?? '');
$requestSecret = (string) ($_GET['secret'] ?? $_POST['secret'] ?? '');

if ($secret !== '' && !hash_equals($secret, $requestSecret)) {
    verify_response(false, 'Akses verifikasi ditolak.', 403);
}

$gmail = new GmailVerificationService($gmailConfig);

if (!$gmail->isEnabled()) {
    verify_response(false, 'Gmail API belum dikonfigurasi.', 503);
}

$reference = trim((string) ($_GET['reference'] ?? $_POST['reference'] ?? ''));
$params = [];
$where = "WHERE status = 'pending'";

if ($reference !== '') {
    $where .= ' AND reference = ?';
    $params[] = $reference;
}

try {
    ensure_payment_table($pdo);

    $statement = $pdo->prepare(
        "SELECT id_pembayaran, reference
         FROM pembayaran_peminjaman
         {$where}
         ORDER BY id_pembayaran ASC
         LIMIT 20"
    );
    $statement->execute($params);
    $payments = $statement->fetchAll();

    $verified = [];
    foreach ($payments as $payment) {
        $paymentReference = (string) $payment['reference'];
        if (!$gmail->paymentReferenceExists($paymentReference)) {
            continue;
        }

        $update = $pdo->prepare(
            "UPDATE pembayaran_peminjaman
             SET status = 'verified', verified_at = NOW()
             WHERE id_pembayaran = ?"
        );
        $update->execute([(int) $payment['id_pembayaran']]);
        $verified[] = $paymentReference;
    }

    verify_response(true, 'Verifikasi pembayaran selesai.', 200, [
        'checked' => count($payments),
        'verified' => $verified,
    ]);
} catch (Throwable) {
    verify_response(false, 'Verifikasi pembayaran belum bisa diproses.', 500);
}
