<?php
function clean_text(string $value): string {
    return trim(strip_tags($value));
}
function escapeHtml(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
function logActivity($pdo, int $id_admin, string $aksi, ?string $tabel = null, ?int $data_id = null, ?string $detail = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_admin, aksi, tabel, data_id, detail, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_admin, $aksi, $tabel, $data_id, $detail, $ip]);
}