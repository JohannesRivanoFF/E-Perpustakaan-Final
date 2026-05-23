<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../koneksi.php';

try {
    // Cek koneksi database
    if (!$pdo) {
        throw new Exception('Database tidak terhubung');
    }
    
    // Ambil data dari database
    $stmt1 = $pdo->query('SELECT COUNT(*) as total FROM buku');
    $total_buku = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    $stmt2 = $pdo->query('SELECT COUNT(*) as total FROM anggota');
    $total_anggota = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    $stmt3 = $pdo->query('SELECT COUNT(*) as total FROM peminjaman');
    $total_peminjaman = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    $stmt4 = $pdo->query('SELECT COALESCE(SUM(stok), 0) as total FROM buku');
    $buku_tersedia = $stmt4->fetch(PDO::FETCH_ASSOC);
    
    // Buku terbaru
    $books = $pdo->query("SELECT id_buku, judul, pengarang, kategori, tahun_terbit, stok, cover_buku FROM buku ORDER BY id_buku DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    
    // Riwayat peminjaman
    $history = $pdo->query("SELECT a.nama, b.judul, p.tanggal_pinjam, p.status FROM peminjaman p JOIN anggota a ON a.id_anggota = p.id_anggota JOIN buku b ON b.id_buku = p.id_buku ORDER BY p.id_peminjaman DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'stats' => [
            'total_buku' => (int) ($total_buku['total'] ?? 0),
            'total_anggota' => (int) ($total_anggota['total'] ?? 0),
            'total_peminjaman' => (int) ($total_peminjaman['total'] ?? 0),
            'buku_tersedia' => (int) ($buku_tersedia['total'] ?? 0),
        ],
        'books' => $books ?: [],
        'history' => $history ?: [],
    ];
    
    echo json_encode($response);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'stats' => [
            'total_buku' => 0,
            'total_anggota' => 0,
            'total_peminjaman' => 0,
            'buku_tersedia' => 0,
        ],
        'books' => [],
        'history' => [],
    ]);
}