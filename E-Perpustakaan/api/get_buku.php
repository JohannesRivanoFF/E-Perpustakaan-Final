<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../koneksi.php';

try {
    $statement = $pdo->query(
        'SELECT id_buku, judul, pengarang, kategori, tahun_terbit, stok
         FROM buku
         WHERE stok > 0
         ORDER BY judul ASC'
    );

    echo json_encode([
        'success' => true,
        'books' => $statement->fetchAll(),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Daftar buku belum bisa dimuat.',
    ]);
}