<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Services/EmailNotificationService.php';

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$allowedLocal = in_array($remoteAddress, ['127.0.0.1', '::1'], true) || str_starts_with($remoteAddress, '::ffff:127.0.0.1');

if (!$allowedLocal) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint test email hanya bisa dipakai dari localhost.',
    ]);
    exit;
}

$integrations = require __DIR__ . '/../config/integrations.php';
$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Tambahkan parameter email yang valid. Contoh: api/test_email.php?email=nama@gmail.com',
    ]);
    exit;
}

$service = new EmailNotificationService($integrations['email'] ?? []);
$token = 'TEST-' . date('YmdHis');
$result = $service->send(
    $email,
    'Tes Token Pembayaran E-Perpustakaan',
    "Halo,\n\nEmail ini dikirim otomatis dari sistem E-Perpustakaan untuk mengetes konfigurasi SMTP/Gmail.\n\nToken pembayaran tes Anda: {$token}\n\nJika email ini masuk, notifikasi pembayaran juga akan otomatis terkirim setelah QR pembayaran tervalidasi."
);

echo json_encode([
    'success' => ($result['status'] ?? '') === 'sent',
    'status' => $result['status'] ?? 'failed',
    'message' => $result['message'] ?? '',
    'token' => $token,
]);