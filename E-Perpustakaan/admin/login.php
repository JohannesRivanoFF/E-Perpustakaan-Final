<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../koneksi.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $statement = $pdo->prepare('SELECT * FROM admin WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        $admin = $statement->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id' => (int) $admin['id_admin'],
                'username' => $admin['username'],
                'nama' => $admin['nama_admin'],
            ];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Username atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-login-body">
  <main class="admin-login-shell">
    <section class="admin-login-card glass-panel">
      <div class="brand flex items-center gap-3">
        <span class="brand-icon"><i class="fa-solid fa-layer-group"></i></span>
        <span>
          <strong>Perpus Digital</strong>
          <small>Admin Panel</small>
        </span>
      </div>
      <h1>Masuk Dashboard</h1>
      <p>Gunakan akun admin untuk mengelola buku, anggota, dan transaksi peminjaman.</p>

      <?php if ($error !== ''): ?>
        <div class="admin-alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="admin-form">
        <label>
          <span>Username</span>
          <input type="text" name="username" value="admin" autocomplete="username" required>
        </label>
        <label>
          <span>Password</span>
          <input type="password" name="password" placeholder="admin123" autocomplete="current-password" required>
        </label>
        <button type="submit" class="primary-button w-full">Login <i class="fa-solid fa-arrow-right"></i></button>
      </form>
    </section>
  </main>
</body>
</html>
