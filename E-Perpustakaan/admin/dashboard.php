<?php
declare(strict_types=1);

session_start();
// Tambahkan ini setelah session_start()
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}


// Di dashboard.php line ~13
$stats = [
    'buku' => (int) $pdo->query('SELECT COUNT(*) FROM buku')->fetchColumn(),
    'anggota' => (int) $pdo->query('SELECT COUNT(*) FROM anggota')->fetchColumn(),
    'peminjaman' => (int) $pdo->query('SELECT COUNT(*) FROM peminjaman')->fetchColumn(),
    'stok' => (int) $pdo->query('SELECT COALESCE(SUM(stok), 0) FROM buku')->fetchColumn(),
    'buku_dipinjam' => (int) $pdo->query('SELECT COUNT(*) FROM peminjaman WHERE status = "dipinjam"')->fetchColumn(), // Tambah ini
];

$latest = $pdo->query(
    "SELECT a.nama, b.judul, p.tanggal_pinjam, p.status
     FROM peminjaman p
     INNER JOIN anggota a ON a.id_anggota = p.id_anggota
     INNER JOIN buku b ON b.id_buku = p.id_buku
     ORDER BY p.id_peminjaman DESC
     LIMIT 8"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="dashboard.php" class="admin-logo"><i class="fa-solid fa-layer-group"></i><span>Perpus Digital</span></a>
    <nav>
      <a class="active" href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="buku.php"><i class="fa-solid fa-book"></i> Buku</a>
      <a href="anggota.php"><i class="fa-solid fa-users"></i> Anggota</a>
      <a href="peminjaman.php"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
      <a href="pembayaran.php"><i class="fa-solid fa-qrcode"></i> Pembayaran</a>
      <a href="../index.php"><i class="fa-solid fa-globe"></i> Landing Page</a>
      <a href="ganti_password.php?<?= time() ?>"><i class="fa-solid fa-key"></i> Ganti Password</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <div>
        <span>Dashboard</span>
        <h1>Selamat datang, <?= htmlspecialchars($_SESSION['admin']['nama']) ?></h1>
      </div>
      <a href="buku.php" class="primary-button">Tambah Buku <i class="fa-solid fa-plus"></i></a>
    </header>

    <section class="admin-stat-grid">
      <article><i class="fa-solid fa-book"></i><strong><?= number_format($stats['buku'], 0, ',', '.') ?></strong><span>Total Buku</span></article>
      <article><i class="fa-solid fa-users"></i><strong><?= number_format($stats['anggota'], 0, ',', '.') ?></strong><span>Total Anggota</span></article>
      <article><i class="fa-solid fa-clipboard-list"></i><strong><?= number_format($stats['peminjaman'], 0, ',', '.') ?></strong><span>Peminjaman</span></article>
      <article><i class="fa-solid fa-boxes-stacked"></i><strong><?= number_format($stats['stok'], 0, ',', '.') ?></strong><span>Total Stok</span></article>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Riwayat Aktivitas</h2>
          <p>Transaksi peminjaman terbaru dari database.</p>
        </div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Buku</th>
              <th>Tanggal Pinjam</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latest as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['nama']) ?></td>
                <td><?= htmlspecialchars($item['judul']) ?></td>
                <td><?= date('d M Y', strtotime($item['tanggal_pinjam'])) ?></td>
                <td><span class="status-badge <?= $item['status'] === 'dikembalikan' ? 'done' : '' ?>"><?= $item['status'] === 'dikembalikan' ? 'Dikembalikan' : 'Dipinjam' ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
