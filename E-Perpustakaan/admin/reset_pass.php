<?php
require_once __DIR__ . '/../koneksi.php';

// Hapus semua admin yang ada (opsional, jika ingin bersih)
// $pdo->exec("DELETE FROM admin");

// Username yang akan dipakai
$username = 'admin';
$password_baru = 'admin123';
$hash = password_hash($password_baru, PASSWORD_DEFAULT);
$nama_admin = 'Administrator Utama';

// Cek apakah admin sudah ada
$stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->execute([$username]);
$existing = $stmt->fetch();

if ($existing) {
    // Update password
    $update = $pdo->prepare("UPDATE admin SET password = ? WHERE username = ?");
    $update->execute([$hash, $username]);
    echo "✅ Password untuk username '{$username}' berhasil direset menjadi: <strong>{$password_baru}</strong><br>";
} else {
    // Insert baru
    $insert = $pdo->prepare("INSERT INTO admin (username, password, nama_admin) VALUES (?, ?, ?)");
    $insert->execute([$username, $hash, $nama_admin]);
    echo "✅ Admin baru berhasil dibuat. Username: {$username}, Password: {$password_baru}<br>";
}

echo "<a href='login.php'>Klik disini untuk login</a>";
?>