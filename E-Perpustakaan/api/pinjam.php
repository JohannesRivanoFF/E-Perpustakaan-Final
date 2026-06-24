<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../app/Services/RecommendationService.php';
require_once __DIR__ . '/../app/Services/PaymentQrService.php';

$integrations = require __DIR__ . '/../config/integrations.php';

function clean_input(string $value): string
{
    return trim(strip_tags($value));
}

function respond(bool $success, string $message, int $statusCode = 200, array $extra = []): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function ensure_integration_tables(PDO $pdo): void
{
    try {
        $pdo->exec('ALTER TABLE anggota ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
    } catch (Throwable) {
        // Column already exists.
    }

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
        // Column already exists.
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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS rekomendasi_log (
            id_rekomendasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_peminjaman INT UNSIGNED DEFAULT NULL,
            id_buku INT UNSIGNED NOT NULL,
            score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            source VARCHAR(50) NOT NULL DEFAULT 'external_api',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL,
            FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
        )"
    );
}

function current_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $projectPath = preg_replace('#/api$#', '', $basePath) ?: '';

    return "{$scheme}://{$host}{$projectPath}";
}

function calculate_loan_days(string $startDate, string $endDate): int
{
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $days = (int) $start->diff($end)->days;

    return max($days, 1);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Request tidak valid.', 405);
}

$nama = clean_input($_POST['nama'] ?? '');
$alamat = clean_input($_POST['alamat'] ?? '');
$noHp = clean_input($_POST['no_hp'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$idBuku = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
$tanggalPinjam = clean_input($_POST['tanggal_pinjam'] ?? '');
$tanggalKembali = clean_input($_POST['tanggal_kembali'] ?? '');

if ($nama === '' || $alamat === '' || $noHp === '' || $email === '' || !$idBuku || $tanggalPinjam === '' || $tanggalKembali === '') {
    respond(false, 'Lengkapi semua data peminjaman.', 422);
}

if (!preg_match('/^[0-9]{9,15}$/', $noHp)) {
    respond(false, 'Nomor HP hanya boleh berisi angka 9 sampai 15 digit.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Format email tidak valid.', 422);
}

if (!strtotime($tanggalPinjam) || !strtotime($tanggalKembali)) {
    respond(false, 'Format tanggal tidak valid.', 422);
}

if ($tanggalKembali < $tanggalPinjam) {
    respond(false, 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.', 422);
}

try {
    ensure_integration_tables($pdo);

    $pdo->beginTransaction();

    $bookStatement = $pdo->prepare(
        'SELECT id_buku, judul, pengarang, kategori, tahun_terbit, stok, total_rating, jumlah_rating
         FROM buku
         WHERE id_buku = ?
         FOR UPDATE'
    );
    $bookStatement->execute([$idBuku]);
    $book = $bookStatement->fetch();

    if (!$book) {
        $pdo->rollBack();
        respond(false, 'Buku tidak ditemukan.', 404);
    }

    if ((int) $book['stok'] <= 0) {
        $pdo->rollBack();
        respond(false, 'Stok buku sudah habis.', 409);
    }

    $requestStatement = $pdo->prepare(
        "INSERT INTO pengajuan_peminjaman
            (nama, alamat, no_hp, email, id_buku, tanggal_pinjam, tanggal_kembali, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $requestStatement->execute([$nama, $alamat, $noHp, $email, $idBuku, $tanggalPinjam, $tanggalKembali]);
    $idPengajuan = (int) $pdo->lastInsertId();

    $recommendationService = new RecommendationService($integrations['recommendation'] ?? []);
    $recommendationScore = $recommendationService->scoreSingleBook($book, [
        'nama' => $nama,
        'alamat' => $alamat,
        'email' => $email,
        'tanggal_pinjam' => $tanggalPinjam,
        'tanggal_kembali' => $tanggalKembali,
    ]);

    $recommendationLog = $pdo->prepare(
        'INSERT INTO rekomendasi_log (id_peminjaman, id_buku, score, source) VALUES (?, ?, ?, ?)'
    );
    $recommendationLog->execute([
        null,
        $idBuku,
        $recommendationScore,
        $recommendationService->isEnabled() ? 'external_api' : 'fallback',
    ]);

    $loanDays = calculate_loan_days($tanggalPinjam, $tanggalKembali);
    $amount = $loanDays * 1000;
    $paymentConfig = $integrations['payment'] ?? [];
    $paymentConfig['amount'] = $amount;
    $paymentConfig['currency'] = 'IDR';
    $paymentService = new PaymentQrService($paymentConfig);
    $baseUrl = rtrim((string) ($paymentConfig['base_url'] ?? ''), '/') ?: current_base_url();

    $payment = $paymentService->createPayment(
        ['id_pengajuan' => $idPengajuan],
        ['nama' => $nama, 'no_hp' => $noHp],
        $book,
    );
    $payment['amount'] = $amount;
    $payment['currency'] = 'IDR';
    $payment['qr_string'] = $baseUrl . '/api/bayar_qr.php?reference=' . rawurlencode((string) $payment['reference']);
    $payment['qr_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=10&data=' . rawurlencode((string) $payment['qr_string']);

    $paymentStatement = $pdo->prepare(
        'INSERT INTO pembayaran_pengajuan
            (id_pengajuan, reference, amount, currency, qr_url, qr_string, status, raw_response)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $paymentStatement->execute([
        $idPengajuan,
        $payment['reference'],
        $payment['amount'],
        $payment['currency'],
        $payment['qr_url'],
        $payment['qr_string'],
        'pending',
        $payment['raw_response'] === null ? null : json_encode($payment['raw_response']),
    ]);

    $pdo->commit();

    $message = 'Pengajuan berhasil dibuat. Silakan bayar QRIS untuk mengaktifkan peminjaman.';

    respond(true, $message, 200, [
        'request_id' => $idPengajuan,
        'loan_id' => null,
        'book_title' => $book['judul'],
        'loan_days' => $loanDays,
        'recommendation_score' => $recommendationScore,
        'payment' => [
            'reference' => $payment['reference'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'qr_url' => $payment['qr_url'],
            'qr_string' => $payment['qr_string'],
            'status' => 'pending',
        ],
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (str_contains($exception->getMessage(), 'Stok buku tidak tersedia')) {
        respond(false, 'Stok buku tidak tersedia.', 409);
    }

    respond(false, 'Peminjaman belum bisa diproses.', 500);
}
