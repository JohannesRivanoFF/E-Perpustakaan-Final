<?php
declare(strict_types=1);

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konfirmasi = $_POST['password_konfirmasi'] ?? '';
    
    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = 'Semua field harus diisi.';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = 'Password baru dan konfirmasi tidak sesuai.';
    } else {
        // Ambil password lama dari database
        $id_admin = $_SESSION['admin']['id'];
        $statement = $pdo->prepare('SELECT password FROM admin WHERE id_admin = ?');
        $statement->execute([$id_admin]);
        $admin = $statement->fetch();
        
        if ($admin && password_verify($password_lama, $admin['password'])) {
            // Hash password baru
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            
            // Update password
            $update = $pdo->prepare('UPDATE admin SET password = ? WHERE id_admin = ?');
            $update->execute([$password_hash, $id_admin]);
            
            $message = 'Password berhasil diubah! Silakan login kembali.';
            
            // Optional: logout setelah ganti password
            // session_destroy();
            // header('Location: login.php');
            // exit;
        } else {
            $error = 'Password lama tidak sesuai.';
        }
    }
}

// Ambil info admin yang login
$admin_nama = $_SESSION['admin']['nama'] ?? 'Administrator';
$admin_username = $_SESSION['admin']['username'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Ganti Password - Perpus Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a href="dashboard.php?<?= time() ?>" class="admin-logo">
            <i class="fa-solid fa-layer-group"></i>
            <span>Perpus Digital</span>
        </a>
        <nav>
            <a href="dashboard.php?<?= time() ?>">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a href="buku.php?<?= time() ?>">
                <i class="fa-solid fa-book"></i> Buku
            </a>
            <a href="anggota.php?<?= time() ?>">
                <i class="fa-solid fa-users"></i> Anggota
            </a>
            <a href="peminjaman.php?<?= time() ?>">
                <i class="fa-solid fa-right-left"></i> Peminjaman
            </a>
            <a href="pembayaran.php?<?= time() ?>">
                <i class="fa-solid fa-qrcode"></i> Pembayaran
            </a>
            <a href="../index.php?<?= time() ?>">
                <i class="fa-solid fa-globe"></i> Landing Page
            </a>
            <a class="active" href="ganti_password.php?<?= time() ?>">
                <i class="fa-solid fa-key"></i> Ganti Password
            </a>
            <a href="logout.php">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <span>Keamanan Akun</span>
                <h1>Ganti Password</h1>
                <p class="text-muted mt-1">Halo, <?= htmlspecialchars($admin_nama) ?> (<?= htmlspecialchars($admin_username) ?>)</p>
            </div>
            <a href="dashboard.php" class="secondary-button">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </header>

        <?php if ($message): ?>
            <div class="admin-alert success">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="admin-alert error">
                <i class="fa-solid fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <section class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2><i class="fa-solid fa-key"></i> Ubah Password</h2>
                    <p>Pastikan password baru minimal 6 karakter dan mudah diingat.</p>
                </div>
            </div>

            <form method="POST" class="admin-form-grid" style="max-width: 600px;">
                <div class="admin-wide">
                    <label>
                        <span><i class="fa-solid fa-lock"></i> Password Lama</span>
                        <input type="password" name="password_lama" placeholder="Masukkan password lama" required autocomplete="current-password">
                    </label>
                </div>

                <div class="admin-wide">
                    <label>
                        <span><i class="fa-solid fa-key"></i> Password Baru</span>
                        <input type="password" name="password_baru" placeholder="Minimal 6 karakter" required autocomplete="new-password">
                    </label>
                </div>

                <div class="admin-wide">
                    <label>
                        <span><i class="fa-solid fa-check-circle"></i> Konfirmasi Password Baru</span>
                        <input type="password" name="password_konfirmasi" placeholder="Ulangi password baru" required autocomplete="new-password">
                    </label>
                </div>

                <div class="admin-wide" style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                    <button type="submit" class="primary-button">
                        <i class="fa-solid fa-save"></i> Simpan Password
                    </button>
                    <a href="dashboard.php" class="secondary-button">
                        <i class="fa-solid fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </section>

        <!-- Informasi tambahan -->
        <section class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2><i class="fa-solid fa-shield-halved"></i> Tips Keamanan</h2>
                    <p>Jaga kerahasiaan password Anda.</p>
                </div>
            </div>
            <div style="display: grid; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-check-circle" style="color: var(--gold);"></i>
                    <span>Gunakan password minimal 6 karakter</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-check-circle" style="color: var(--gold);"></i>
                    <span>Kombinasikan huruf, angka, dan simbol</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-check-circle" style="color: var(--gold);"></i>
                    <span>Jangan bagikan password kepada siapapun</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-check-circle" style="color: var(--gold);"></i>
                    <span>Ganti password secara berkala untuk keamanan maksimal</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
