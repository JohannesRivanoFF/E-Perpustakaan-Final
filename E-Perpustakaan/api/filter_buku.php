<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../app/Services/RecommendationService.php';

$integrations = require __DIR__ . '/../config/integrations.php';

$search = $_GET['search'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$sort = $_GET['sort'] ?? 'terbaru';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(judul LIKE ? OR pengarang LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($kategori !== '') {
    $where[] = 'kategori = ?';
    $params[] = $kategori;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
$orderBy = match($sort) {
    'terpopuler' => 'total_rating DESC, jumlah_rating DESC',
    'terbaru' => 'tahun_terbit DESC',
    'terlama' => 'tahun_terbit ASC',
    default => 'id_buku DESC',
};

$sql = "SELECT id_buku, judul, pengarang, kategori, tahun_terbit, stok, cover_buku, 
               deskripsi, COALESCE(total_rating, 0) as total_rating, 
               COALESCE(jumlah_rating, 0) as jumlah_rating 
        FROM buku {$whereSql} 
        ORDER BY {$orderBy} 
        LIMIT 50";

$statement = $pdo->prepare($sql);
$statement->execute($params);
$books = $statement->fetchAll();

$recommendationService = new RecommendationService($integrations['recommendation'] ?? []);
$books = $recommendationService->scoreBooks($books, [
    'search' => $search,
    'kategori' => $kategori,
    'sort' => $sort,
]);

if ($sort === 'rekomendasi') {
    usort($books, static fn (array $a, array $b): int => ($b['recommendation_score'] <=> $a['recommendation_score']));
}

// Ambil daftar kategori unik
$categories = $pdo->query('SELECT DISTINCT kategori FROM buku ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'books' => $books,
    'categories' => $categories,
    'total' => count($books),
    'recommendation_source' => $recommendationService->isEnabled() ? 'external_api' : 'fallback'
]);
?>
