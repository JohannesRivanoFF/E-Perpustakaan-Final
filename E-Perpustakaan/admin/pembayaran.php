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

function ensure_upfront_payment_tables(PDO $pdo): void
{
    try {
        $pdo->exec('ALTER TABLE anggota ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
    } catch (Throwable) {
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pengajuan_peminjaman (
            id_pengajuan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            alamat TEXT NOT NULL,
            no_hp VARCHAR(15) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            id_buku INT UNSIGNED NOT NULL,
            tanggal_pinjam DATE NOT NULL,
            tanggal_kembali DATE NOT NULL,
            status ENUM('pending', 'paid', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
            id_peminjaman INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE RESTRICT,
            FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
        )"
    );

    try {
        $pdo->exec('ALTER TABLE pengajuan_peminjaman ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
    } catch (Throwable) {
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pembayaran_pengajuan (
            id_pembayaran INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_pengajuan INT UNSIGNED NOT NULL,
            id_peminjaman INT UNSIGNED DEFAULT NULL,
            reference VARCHAR(80) NOT NULL UNIQUE,
            amount INT UNSIGNED NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'IDR',
            qr_url VARCHAR(500) DEFAULT NULL,
            qr_string TEXT DEFAULT NULL,
            status ENUM('pending', 'verified', 'manual', 'failed', 'expired') NOT NULL DEFAULT 'pending',
            verified_at DATETIME DEFAULT NULL,
            raw_response JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_pengajuan) REFERENCES pengajuan_peminjaman(id_pengajuan) ON DELETE CASCADE,
            FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifikasi_email (
            id_notifikasi INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            id_peminjaman INT UNSIGNED DEFAULT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL
        )"
    );
}

function payment_status_label(string $status): string
{
    return match ($status) {
        'verified' => 'Terverifikasi',
        'failed' => 'Gagal',
        'expired' => 'Expired',
        default => 'Menunggu QRIS',
    };
}

function request_status_label(string $status): string
{
    return match ($status) {
        'paid' => 'Peminjaman Aktif',
        'cancelled' => 'Dibatalkan',
        'expired' => 'Kedaluwarsa',
        default => 'Menunggu Pembayaran',
    };
}

function email_status_label(?string $status): string
{
    return match ($status) {
        'sent' => 'Email Terkirim',
        'failed' => 'Email Gagal',
        'queued' => 'Email Antrean',
        default => 'Belum Dikirim',
    };
}

ensure_upfront_payment_tables($pdo);

$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $idPembayaran = filter_input(INPUT_POST, 'id_pembayaran', FILTER_VALIDATE_INT);

        if (!$idPembayaran) {
            throw new RuntimeException('ID pembayaran tidak valid.');
        }

        $paymentStatement = $pdo->prepare(
            "SELECT pay.*, req.status AS request_status
             FROM pembayaran_pengajuan pay
             INNER JOIN pengajuan_peminjaman req ON req.id_pengajuan = pay.id_pengajuan
             WHERE pay.id_pembayaran = ?
             LIMIT 1"
        );
        $paymentStatement->execute([$idPembayaran]);
        $payment = $paymentStatement->fetch();

        if (!$payment) {
            throw new RuntimeException('Data pembayaran tidak ditemukan.');
        }

        if ($payment['status'] === 'verified') {
            throw new RuntimeException('Pembayaran yang sudah terverifikasi tidak bisa diubah.');
        }

        if ($action === 'expire') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE pembayaran_pengajuan SET status = 'expired' WHERE id_pembayaran = ?")->execute([$idPembayaran]);
            $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'expired' WHERE id_pengajuan = ?")->execute([(int) $payment['id_pengajuan']]);
            $pdo->commit();
            $message = 'Pembayaran ditandai expired.';
        }

        if ($action === 'cancel') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE pembayaran_pengajuan SET status = 'failed' WHERE id_pembayaran = ?")->execute([$idPembayaran]);
            $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'cancelled' WHERE id_pengajuan = ?")->execute([(int) $payment['id_pengajuan']]);
            $pdo->commit();
            $message = 'Pengajuan pembayaran dibatalkan.';
        }

        if ($action === 'reopen') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE pembayaran_pengajuan SET status = 'pending' WHERE id_pembayaran = ?")->execute([$idPembayaran]);
            $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'pending' WHERE id_pengajuan = ?")->execute([(int) $payment['id_pengajuan']]);
            $pdo->commit();
            $message = 'Pembayaran dibuka ulang.';
        }
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $exception->getMessage();
}

$stats = [
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM pembayaran_pengajuan WHERE status = 'pending'")->fetchColumn(),
    'verified' => (int) $pdo->query("SELECT COUNT(*) FROM pembayaran_pengajuan WHERE status = 'verified'")->fetchColumn(),
    'pending_amount' => (int) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM pembayaran_pengajuan WHERE status = 'pending'")->fetchColumn(),
    'paid_amount' => (int) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM pembayaran_pengajuan WHERE status = 'verified'")->fetchColumn(),
];

$search = trim(strip_tags($_GET['q'] ?? ''));
$status = trim(strip_tags($_GET['status'] ?? ''));
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$params = [];
$where = [];

if ($search !== '') {
    $where[] = '(req.nama LIKE ? OR req.email LIKE ? OR req.no_hp LIKE ? OR b.judul LIKE ? OR pay.reference LIKE ?)';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
}

if (in_array($status, ['pending', 'verified', 'failed', 'expired', 'manual'], true)) {
    $where[] = 'pay.status = ?';
    $params[] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStatement = $pdo->prepare(
    "SELECT COUNT(*)
     FROM pembayaran_pengajuan pay
     INNER JOIN pengajuan_peminjaman req ON req.id_pengajuan = pay.id_pengajuan
     INNER JOIN buku b ON b.id_buku = req.id_buku
     {$whereSql}"
);
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max((int) ceil($totalRows / $limit), 1);

$dataStatement = $pdo->prepare(
    "SELECT pay.*, req.nama, req.email, req.no_hp, req.tanggal_pinjam, req.tanggal_kembali,
            req.status AS request_status, b.judul,
            GREATEST(DATEDIFF(req.tanggal_kembali, req.tanggal_pinjam), 1) AS hari_pinjam,
            notif.status AS email_status,
            notif.sent_at AS email_sent_at
     FROM pembayaran_pengajuan pay
     INNER JOIN pengajuan_peminjaman req ON req.id_pengajuan = pay.id_pengajuan
     INNER JOIN buku b ON b.id_buku = req.id_buku
     LEFT JOIN notifikasi_email notif ON notif.id_notifikasi = (
        SELECT MAX(latest.id_notifikasi)
        FROM notifikasi_email latest
        WHERE latest.id_peminjaman = pay.id_peminjaman
     )
     {$whereSql}
     ORDER BY pay.id_pembayaran DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$dataStatement->execute($params);
$payments = $dataStatement->fetchAll();
$queryBase = 'pembayaran.php?q=' . urlencode($search) . '&status=' . urlencode($status);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring Pembayaran - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="dashboard.php?<?= time() ?>" class="admin-logo"><i class="fa-solid fa-layer-group"></i><span>Perpus Digital</span></a>
    <nav>
      <a href="dashboard.php?<?= time() ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="buku.php?<?= time() ?>"><i class="fa-solid fa-book"></i> Buku</a>
      <a href="anggota.php?<?= time() ?>"><i class="fa-solid fa-users"></i> Anggota</a>
      <a href="peminjaman.php?<?= time() ?>"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
      <a class="active" href="pembayaran.php?<?= time() ?>"><i class="fa-solid fa-qrcode"></i> Pembayaran</a>
      <a href="../index.php?<?= time() ?>"><i class="fa-solid fa-globe"></i> Landing Page</a>
      <a href="ganti_password.php?<?= time() ?>"><i class="fa-solid fa-key"></i> Ganti Password</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <div>
        <span>Payment Console</span>
        <h1>Monitoring pembayaran QRIS peminjaman</h1>
      </div>
      <a href="../index.php#pinjam" class="primary-button">Buat Pengajuan <i class="fa-solid fa-plus"></i></a>
    </header>

    <?php if ($message): ?><div class="admin-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="admin-stat-grid payment-stat-grid">
      <article><i class="fa-solid fa-clock"></i><strong><?= number_format($stats['pending'], 0, ',', '.') ?></strong><span>Menunggu QRIS</span></article>
      <article><i class="fa-solid fa-circle-check"></i><strong><?= number_format($stats['verified'], 0, ',', '.') ?></strong><span>Terverifikasi</span></article>
      <article><i class="fa-solid fa-file-invoice-dollar"></i><strong>Rp<?= number_format($stats['pending_amount'], 0, ',', '.') ?></strong><span>Belum Dibayar</span></article>
      <article><i class="fa-solid fa-wallet"></i><strong>Rp<?= number_format($stats['paid_amount'], 0, ',', '.') ?></strong><span>Sudah Dibayar</span></article>
    </section>

    <section class="admin-panel payment-insight-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Aturan Pembayaran</h2>
          <p>Biaya peminjaman dihitung otomatis: jumlah hari pinjam x Rp1.000. Peminjaman aktif setelah QRIS dibayar/discan.</p>
        </div>
      </div>
      <div class="payment-flow">
        <div><i class="fa-solid fa-file-invoice"></i><span>Pengajuan dibuat</span></div>
        <div><i class="fa-solid fa-qrcode"></i><span>Bayar QRIS</span></div>
        <div><i class="fa-solid fa-envelope"></i><span>Email dikirim otomatis</span></div>
        <div><i class="fa-solid fa-book-open-reader"></i><span>Peminjaman aktif</span></div>
      </div>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Daftar Pembayaran</h2>
          <p><?= number_format($totalRows, 0, ',', '.') ?> invoice ditemukan.</p>
        </div>
      </div>

      <form method="GET" class="admin-filter payment-filter">
        <input type="search" name="q" placeholder="Cari nama, email, buku, atau reference" value="<?= htmlspecialchars($search) ?>">
        <select name="status">
          <option value="">Semua status</option>
          <?php foreach (['pending' => 'Menunggu QRIS', 'verified' => 'Terverifikasi', 'failed' => 'Gagal', 'expired' => 'Expired'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button class="secondary-button" type="submit">Filter</button>
      </form>

      <div class="admin-table-wrap">
        <table class="admin-table payment-table">
          <thead><tr><th>Reference</th><th>Peminjam</th><th>Buku</th><th>Biaya</th><th>Status</th><th>Email</th><th>QR</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (!$payments): ?><tr><td colspan="8">Belum ada invoice pembayaran.</td></tr><?php endif; ?>
            <?php foreach ($payments as $payment): ?>
              <?php $badgeClass = $payment['status'] === 'verified' ? 'done' : ($payment['status'] === 'pending' ? 'pending' : 'danger'); ?>
              <?php $emailBadgeClass = $payment['email_status'] === 'sent' ? 'done' : ($payment['email_status'] === 'failed' ? 'danger' : 'pending'); ?>
              <tr>
                <td><strong class="payment-reference-text"><?= htmlspecialchars($payment['reference']) ?></strong><br><small><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></small></td>
                <td><?= htmlspecialchars($payment['nama']) ?><br><small><?= htmlspecialchars((string) $payment['email']) ?></small></td>
                <td><?= htmlspecialchars($payment['judul']) ?><br><small><?= date('d M Y', strtotime($payment['tanggal_pinjam'])) ?> - <?= date('d M Y', strtotime($payment['tanggal_kembali'])) ?></small></td>
                <td><strong>Rp<?= number_format((int) $payment['amount'], 0, ',', '.') ?></strong><br><small><?= (int) $payment['hari_pinjam'] ?> hari x Rp1.000</small></td>
                <td><span class="status-badge <?= $badgeClass ?>"><?= payment_status_label((string) $payment['status']) ?></span><br><small><?= request_status_label((string) $payment['request_status']) ?></small></td>
                <td><span class="status-badge <?= $emailBadgeClass ?>"><?= email_status_label($payment['email_status'] ?? null) ?></span><br><small><?= $payment['email_sent_at'] ? date('d M Y H:i', strtotime($payment['email_sent_at'])) : 'Setelah pembayaran valid' ?></small></td>
                <td>
                  <a class="payment-qr-thumb" href="<?= htmlspecialchars((string) $payment['qr_url']) ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars((string) $payment['qr_url']) ?>" alt="QR <?= htmlspecialchars($payment['reference']) ?>">
                  </a>
                </td>
                <td class="admin-actions payment-actions-admin">
                  <a href="<?= htmlspecialchars((string) $payment['qr_string']) ?>" class="admin-icon edit" target="_blank" rel="noopener" title="Buka pembayaran"><i class="fa-solid fa-up-right-from-square"></i></a>
                  <?php if ($payment['status'] !== 'verified'): ?>
                    <form method="POST"><input type="hidden" name="id_pembayaran" value="<?= (int) $payment['id_pembayaran'] ?>"><input type="hidden" name="action" value="expire"><button class="admin-icon" type="submit" title="Expire"><i class="fa-solid fa-hourglass-end"></i></button></form>
                    <form method="POST" onsubmit="return confirm('Batalkan pengajuan pembayaran ini?');"><input type="hidden" name="id_pembayaran" value="<?= (int) $payment['id_pembayaran'] ?>"><input type="hidden" name="action" value="cancel"><button class="admin-icon danger" type="submit" title="Batalkan"><i class="fa-solid fa-ban"></i></button></form>
                    <?php if (in_array($payment['status'], ['failed', 'expired'], true)): ?>
                      <form method="POST"><input type="hidden" name="id_pembayaran" value="<?= (int) $payment['id_pembayaran'] ?>"><input type="hidden" name="action" value="reopen"><button class="admin-icon edit" type="submit" title="Buka ulang"><i class="fa-solid fa-rotate-left"></i></button></form>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="admin-pagination">
        <?= generatePagination($page, $totalPages, $queryBase) ?>
      </div>
    </section>
  </main>
</body>
</html>
