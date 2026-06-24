<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

$id_buku = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_buku) {
    echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
    exit;
}

// Ambil detail buku
$stmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
$stmt->execute([$id_buku]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
    exit;
}

// Ambil rating & review
$stmt = $pdo->prepare('SELECT nama_pengunjung, rating, review, created_at 
                       FROM rating_review WHERE id_buku = ? 
                       ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$id_buku]);
$reviews = $stmt->fetchAll();

// Ambil rekomendasi (buku dengan kategori sama)
$stmt = $pdo->prepare('SELECT id_buku, judul, pengarang, cover_buku 
                       FROM buku 
                       WHERE kategori = ? AND id_buku != ? 
                       ORDER BY total_rating DESC LIMIT 4');
$stmt->execute([$book['kategori'], $id_buku]);
$rekomendasi = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'book' => $book,
    'reviews' => $reviews,
    'rekomendasi' => $rekomendasi
]);
?>