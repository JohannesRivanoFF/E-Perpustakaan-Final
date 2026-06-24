<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$id_buku = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
$nama = trim(strip_tags($_POST['nama'] ?? ''));
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review = trim(strip_tags($_POST['review'] ?? ''));

if (!$id_buku || !$nama || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Simpan rating
    $stmt = $pdo->prepare('INSERT INTO rating_review (id_buku, nama_pengunjung, rating, review) VALUES (?, ?, ?, ?)');
    $stmt->execute([$id_buku, $nama, $rating, $review]);
    
    // Update total rating di tabel buku
    $stmt = $pdo->prepare('UPDATE buku SET 
                           total_rating = (SELECT ROUND(AVG(rating), 1) FROM rating_review WHERE id_buku = ?),
                           jumlah_rating = (SELECT COUNT(*) FROM rating_review WHERE id_buku = ?)
                           WHERE id_buku = ?');
    $stmt->execute([$id_buku, $id_buku, $id_buku]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Terima kasih atas rating dan review Anda!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan rating: ' . $e->getMessage()]);
}
?>