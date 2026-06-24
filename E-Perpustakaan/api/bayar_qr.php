<?php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../app/Services/EmailNotificationService.php';

$integrations = require __DIR__ . '/../config/integrations.php';

function ensure_prototype_payment_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pengajuan_peminjaman (
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
        )"
    );

    try {
        $pdo->exec('ALTER TABLE pengajuan_peminjaman ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
    } catch (Throwable) {
    }

    try {
        $pdo->exec('ALTER TABLE anggota ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
    } catch (Throwable) {
    }

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
        "CREATE TABLE IF NOT EXISTS notifikasi_email (
            id_notifikasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_peminjaman INT UNSIGNED DEFAULT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
        )"
    );
}

function notify_payment_email(PDO $pdo, array $emailConfig, int $loanId, string $email, string $name, string $title, int $amount, string $reference): array
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'failed', 'message' => 'Email peminjam tidak valid.'];
    }

    try {
        $subject = 'Token Pembayaran Peminjaman Tervalidasi';
        $body = "Halo {$name},\n\n"
            . 'Pembayaran peminjaman buku "' . $title . '" sebesar Rp' . number_format($amount, 0, ',', '.') . " sudah tervalidasi otomatis.\n"
            . "Token pembayaran Anda: {$reference}\n"
            . "Peminjaman Anda sudah aktif dan stok buku sudah diperbarui oleh sistem.\n\n"
            . "Terima kasih sudah menggunakan E-Perpustakaan.";

        $emailService = new EmailNotificationService($emailConfig);
        $sendResult = $emailService->send($email, $subject, $body);
        $status = in_array($sendResult['status'] ?? 'queued', ['queued', 'sent', 'failed'], true)
            ? (string) $sendResult['status']
            : 'queued';
        $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : null;

        $statement = $pdo->prepare(
            'INSERT INTO notifikasi_email (id_peminjaman, email, subject, body, status, sent_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$loanId, $email, $subject, $body, $status, $sentAt]);

        return [
            'status' => $status,
            'message' => (string) ($sendResult['message'] ?? ''),
        ];
    } catch (Throwable $error) {
        return ['status' => 'failed', 'message' => $error->getMessage()];
    }
}

function payment_token_email_has_been_sent(PDO $pdo, int $loanId, string $reference): bool
{
    $statement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM notifikasi_email
         WHERE id_peminjaman = ? AND status = 'sent' AND body LIKE ?"
    );
    $statement->execute([$loanId, '%' . $reference . '%']);

    return (int) $statement->fetchColumn() > 0;
}

function render_payment_page(string $title, string $message, string $type = 'success'): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $icon = $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    $color = $type === 'success' ? '#0e6a3d' : '#8b1d1d';

    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$safeTitle}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: #f8f5ec; color: #071a35; }
    main { width: min(440px, calc(100% - 32px)); padding: 28px; border-radius: 14px; background: #fff; box-shadow: 0 22px 54px rgba(7, 26, 53, 0.14); text-align: center; }
    i { color: {$color}; font-size: 44px; }
    h1 { margin: 16px 0 10px; font-size: 24px; }
    p { margin: 0; line-height: 1.6; color: #53616f; }
    a { display: inline-flex; margin-top: 22px; padding: 12px 18px; border-radius: 999px; color: #071a35; background: linear-gradient(135deg, #f4d991, #d6a84f); font-weight: 800; text-decoration: none; }
  </style>
</head>
<body>
  <main>
    <i class="fa-solid {$icon}"></i>
    <h1>{$safeTitle}</h1>
    <p>{$safeMessage}</p>
    <a href="../index.php#pinjam">Kembali ke halaman peminjaman</a>
  </main>
</body>
</html>
HTML;
    exit;
}

$reference = trim((string) ($_GET['reference'] ?? ''));

if ($reference === '') {
    render_payment_page('Reference tidak valid', 'QR pembayaran tidak membawa reference yang benar.', 'error');
}

try {
    ensure_prototype_payment_tables($pdo);
    $pdo->beginTransaction();

    $paymentStatement = $pdo->prepare(
        "SELECT pay.*, req.nama, req.alamat, req.no_hp, req.email, req.id_buku, req.tanggal_pinjam, req.tanggal_kembali, req.status AS request_status
         FROM pembayaran_pengajuan pay
         INNER JOIN pengajuan_peminjaman req ON req.id_pengajuan = pay.id_pengajuan
         WHERE pay.reference = ?
         FOR UPDATE"
    );
    $paymentStatement->execute([$reference]);
    $payment = $paymentStatement->fetch();

    if (!$payment) {
        $pdo->rollBack();
        render_payment_page('Pembayaran tidak ditemukan', 'Reference ini tidak terdaftar pada pengajuan peminjaman.', 'error');
    }

    if ($payment['status'] === 'verified' && !empty($payment['id_peminjaman'])) {
        $titleStatement = $pdo->prepare('SELECT judul FROM buku WHERE id_buku = ? LIMIT 1');
        $titleStatement->execute([(int) $payment['id_buku']]);
        $bookTitle = (string) ($titleStatement->fetchColumn() ?: 'Buku');
        $idPeminjaman = (int) $payment['id_peminjaman'];
        $emailAlreadySent = payment_token_email_has_been_sent($pdo, $idPeminjaman, (string) $payment['reference']);
        $pdo->commit();

        $emailResult = $emailAlreadySent
            ? ['status' => 'sent', 'message' => 'Email sebelumnya sudah terkirim.']
            : notify_payment_email(
                $pdo,
                $integrations['email'] ?? [],
                $idPeminjaman,
                (string) $payment['email'],
                (string) $payment['nama'],
                $bookTitle,
                (int) $payment['amount'],
                (string) $payment['reference']
            );

        $emailMessage = $emailResult['status'] === 'sent'
            ? ' Notifikasi pembayaran sudah dikirim ke email peminjam.'
            : ' Notifikasi pembayaran sudah dicatat, tetapi email belum terkirim: ' . ((string) ($emailResult['message'] ?? 'konfigurasi email belum lengkap')) . '.';

        render_payment_page('Pembayaran sudah terverifikasi', 'Peminjaman sebelumnya sudah aktif. Stok buku tidak dikurangi ulang.' . $emailMessage);
    }

    $bookStatement = $pdo->prepare('SELECT id_buku, judul, stok FROM buku WHERE id_buku = ? FOR UPDATE');
    $bookStatement->execute([(int) $payment['id_buku']]);
    $book = $bookStatement->fetch();

    if (!$book || (int) $book['stok'] <= 0) {
        $updateFailed = $pdo->prepare(
            "UPDATE pembayaran_pengajuan SET status = 'failed' WHERE id_pembayaran = ?"
        );
        $updateFailed->execute([(int) $payment['id_pembayaran']]);
        $pdo->commit();
        render_payment_page('Stok tidak tersedia', 'Pembayaran tidak diproses karena stok buku sudah habis.', 'error');
    }

    $memberStatement = $pdo->prepare('SELECT id_anggota FROM anggota WHERE no_hp = ? LIMIT 1');
    $memberStatement->execute([(string) $payment['no_hp']]);
    $member = $memberStatement->fetch();

    if ($member) {
        $idAnggota = (int) $member['id_anggota'];
        $updateMember = $pdo->prepare('UPDATE anggota SET nama = ?, alamat = ?, email = ? WHERE id_anggota = ?');
        $updateMember->execute([(string) $payment['nama'], (string) $payment['alamat'], (string) $payment['email'], $idAnggota]);
    } else {
        $insertMember = $pdo->prepare('INSERT INTO anggota (nama, alamat, no_hp, email) VALUES (?, ?, ?, ?)');
        $insertMember->execute([(string) $payment['nama'], (string) $payment['alamat'], (string) $payment['no_hp'], (string) $payment['email']]);
        $idAnggota = (int) $pdo->lastInsertId();
    }

    $loanStatement = $pdo->prepare(
        "INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status)
         VALUES (?, ?, ?, ?, 'dipinjam')"
    );
    $loanStatement->execute([
        $idAnggota,
        (int) $payment['id_buku'],
        (string) $payment['tanggal_pinjam'],
        (string) $payment['tanggal_kembali'],
    ]);
    $idPeminjaman = (int) $pdo->lastInsertId();

    $stockStatement = $pdo->prepare('UPDATE buku SET stok = stok - 1 WHERE id_buku = ? AND stok > 0');
    $stockStatement->execute([(int) $payment['id_buku']]);

    if ($stockStatement->rowCount() !== 1) {
        throw new RuntimeException('Stok buku tidak tersedia');
    }

    $updateRequest = $pdo->prepare(
        "UPDATE pengajuan_peminjaman SET status = 'paid', id_peminjaman = ? WHERE id_pengajuan = ?"
    );
    $updateRequest->execute([$idPeminjaman, (int) $payment['id_pengajuan']]);

    $updatePayment = $pdo->prepare(
        "UPDATE pembayaran_pengajuan
         SET status = 'verified', verified_at = NOW(), id_peminjaman = ?
         WHERE id_pembayaran = ?"
    );
    $updatePayment->execute([$idPeminjaman, (int) $payment['id_pembayaran']]);

    $pdo->commit();

    $emailResult = notify_payment_email(
        $pdo,
        $integrations['email'] ?? [],
        $idPeminjaman,
        (string) $payment['email'],
        (string) $payment['nama'],
        (string) $book['judul'],
        (int) $payment['amount'],
        (string) $payment['reference']
    );

    // Log hasil email
error_log('Email notification result: ' . print_r($emailResult, true));

$emailMessage = $emailResult['status'] === 'sent'
    ? ' Notifikasi pembayaran sudah dikirim ke email peminjam.'
    : ' Notifikasi pembayaran dicatat, tetapi email gagal terkirim: ' . ((string) ($emailResult['message'] ?? 'konfigurasi email belum lengkap')) . '. Silakan cek log untuk detail.';

    $emailMessage = $emailResult['status'] === 'sent'
        ? ' Notifikasi pembayaran sudah dikirim ke email peminjam.'
        : ' Notifikasi pembayaran sudah dicatat, tetapi email belum terkirim: ' . ((string) ($emailResult['message'] ?? 'konfigurasi email belum lengkap')) . '.';

    render_payment_page('Pembayaran berhasil', 'Peminjaman buku "' . (string) $book['judul'] . '" sudah aktif. Pembayaran Rp' . number_format((int) $payment['amount'], 0, ',', '.') . ' sudah tervalidasi otomatis.' . $emailMessage);
} catch (Throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    render_payment_page('Pembayaran gagal', 'Pembayaran prototype belum bisa diproses. Silakan coba lagi atau hubungi petugas.', 'error');
}
