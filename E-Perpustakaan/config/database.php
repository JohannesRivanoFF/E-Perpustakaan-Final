<?php
declare(strict_types=1);

$host = 'localhost';
$database = 'perpustakaan_digital';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

/**
 * Generate pagination links yang rapi dengan elipsis
 * @param int $currentPage Halaman aktif
 * @param int $totalPages Total halaman
 * @param string $url Base URL (query string akan ditambahkan)
 * @return string HTML pagination
 */
function generatePagination($currentPage, $totalPages, $url) {
    $html = '';
    $range = 2; // Jumlah halaman di kiri dan kanan halaman aktif
    
    // Tombol Previous
    if ($currentPage > 1) {
        $html .= '<a href="' . $url . '&page=' . ($currentPage - 1) . '"><i class="fa-solid fa-chevron-left"></i></a>';
    }
    
    // Halaman pertama
    if ($currentPage - $range > 1) {
        $html .= '<a href="' . $url . '&page=1">1</a>';
        if ($currentPage - $range > 2) $html .= '<span>...</span>';
    }
    
    // Halaman sekitar currentPage
    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<a class="' . $active . '" href="' . $url . '&page=' . $i . '">' . $i . '</a>';
    }
    
    // Halaman terakhir
    if ($currentPage + $range < $totalPages) {
        if ($currentPage + $range < $totalPages - 1) $html .= '<span>...</span>';
        $html .= '<a href="' . $url . '&page=' . $totalPages . '">' . $totalPages . '</a>';
    }
    
    // Tombol Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $url . '&page=' . ($currentPage + 1) . '"><i class="fa-solid fa-chevron-right"></i></a>';
    }
    
    return $html;
}

$dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal. Pastikan MySQL aktif dan database sudah di-import.',
    ]);
    exit;
}
