<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../app/Services/RecommendationService.php';

$integrations = require __DIR__ . '/../config/integrations.php';

try {
    $statement = $pdo->query(
        'SELECT id_buku, judul, pengarang, kategori, tahun_terbit, stok,
                COALESCE(total_rating, 0) AS total_rating,
                COALESCE(jumlah_rating, 0) AS jumlah_rating
         FROM buku
         WHERE stok > 0
         ORDER BY judul ASC'
    );

    $recommendationService = new RecommendationService($integrations['recommendation'] ?? []);
    $books = $recommendationService->scoreBooks($statement->fetchAll(), [
        'surface' => 'borrow_form',
    ]);

    echo json_encode([
        'success' => true,
        'books' => $books,
        'recommendation_source' => $recommendationService->isEnabled() ? 'external_api' : 'fallback',
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Daftar buku belum bisa dimuat.',
    ]);
}
