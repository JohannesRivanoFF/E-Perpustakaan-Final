<?php
declare(strict_types=1);

session_start();

// ===== ANTI CACHE =====
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

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id_peminjaman', FILTER_VALIDATE_INT);
            $idAnggota = filter_input(INPUT_POST, 'id_anggota', FILTER_VALIDATE_INT);
            $idBuku = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
            $tanggalPinjam = trim($_POST['tanggal_pinjam'] ?? '');
            $tanggalKembali = trim($_POST['tanggal_kembali'] ?? '');
            $status = $_POST['status'] === 'dikembalikan' ? 'dikembalikan' : 'dipinjam';

            if (!$idAnggota || !$idBuku || $tanggalPinjam === '' || $tanggalKembali === '' || $tanggalKembali < $tanggalPinjam) {
                throw new RuntimeException('Lengkapi data peminjaman dengan benar.');
            }

            if ($id) {
                $statement = $pdo->prepare(
                    'UPDATE peminjaman SET id_anggota = ?, id_buku = ?, tanggal_pinjam = ?, tanggal_kembali = ?, status = ? WHERE id_peminjaman = ?'
                );
                $statement->execute([$idAnggota, $idBuku, $tanggalPinjam, $tanggalKembali, $status, $id]);
                $message = 'Data peminjaman berhasil diperbarui.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, ?, ?)'
                );
                $statement->execute([$idAnggota, $idBuku, $tanggalPinjam, $tanggalKembali, $status]);
                $message = 'Peminjaman baru berhasil ditambahkan.';
            }
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id_peminjaman', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('Peminjaman tidak valid.');
            }

            $statement = $pdo->prepare('DELETE FROM peminjaman WHERE id_peminjaman = ?');
            $statement->execute([$id]);
            $message = 'Data peminjaman berhasil dihapus.';
        }
    }
} catch (Throwable $exception) {
    $error = str_contains($exception->getMessage(), 'Stok buku tidak tersedia')
        ? 'Stok buku tidak tersedia.'
        : $exception->getMessage();
}

$editLoan = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM peminjaman WHERE id_peminjaman = ?');
    $statement->execute([$editId]);
    $editLoan = $statement->fetch() ?: null;
}

$members = $pdo->query('SELECT id_anggota, nama, no_hp FROM anggota ORDER BY nama ASC')->fetchAll();
$books = $pdo->query('SELECT id_buku, judul, stok FROM buku WHERE stok > 0 OR id_buku IN (SELECT id_buku FROM peminjaman) ORDER BY judul ASC')->fetchAll();

$search = trim(strip_tags($_GET['q'] ?? ''));
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$params = [];
$whereSql = '';

if ($search !== '') {
    $whereSql = 'WHERE a.nama LIKE ? OR b.judul LIKE ? OR p.status LIKE ?';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$countStatement = $pdo->prepare(
    "SELECT COUNT(*)
     FROM peminjaman p
     INNER JOIN anggota a ON a.id_anggota = p.id_anggota
     INNER JOIN buku b ON b.id_buku = p.id_buku
     {$whereSql}"
);
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max((int) ceil($totalRows / $limit), 1);

$dataStatement = $pdo->prepare(
    "SELECT p.*, a.nama, b.judul
     FROM peminjaman p
     INNER JOIN anggota a ON a.id_anggota = p.id_anggota
     INNER JOIN buku b ON b.id_buku = p.id_buku
     {$whereSql}
     ORDER BY p.id_peminjaman DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$dataStatement->execute($params);
$loans = $dataStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>CRUD Peminjaman - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="dashboard.php?<?= time() ?>" class="admin-logo"><i class="fa-solid fa-layer-group"></i><span>Perpus Digital</span></a>
    <nav>
      <a href="dashboard.php?<?= time() ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="buku.php?<?= time() ?>"><i class="fa-solid fa-book"></i> Buku</a>
      <a href="anggota.php?<?= time() ?>"><i class="fa-solid fa-users"></i> Anggota</a>
      <a class="active" href="peminjaman.php?<?= time() ?>"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
      <a href="../index.php?<?= time() ?>"><i class="fa-solid fa-globe"></i> Landing Page</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar"><div><span>Manajemen Peminjaman</span><h1>Kelola transaksi dan status</h1></div></header>
    <?php if ($message): ?><div class="admin-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2><?= $editLoan ? 'Edit Peminjaman' : 'Tambah Peminjaman' ?></h2><p>Status dikembalikan otomatis menambah stok melalui trigger database.</p></div></div>
      <form method="POST" class="admin-form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id_peminjaman" value="<?= htmlspecialchars((string) ($editLoan['id_peminjaman'] ?? '')) ?>">
        <label><span>Anggota</span><select name="id_anggota" required>
          <option value="">Pilih anggota</option>
          <?php foreach ($members as $member): ?>
            <option value="<?= (int) $member['id_anggota'] ?>" <?= (int) ($editLoan['id_anggota'] ?? 0) === (int) $member['id_anggota'] ? 'selected' : '' ?>><?= htmlspecialchars($member['nama'] . ' - ' . $member['no_hp']) ?></option>
          <?php endforeach; ?>
        </select></label>
        <label><span>Buku</span><select name="id_buku" required>
          <option value="">Pilih buku</option>
          <?php foreach ($books as $book): ?>
            <option value="<?= (int) $book['id_buku'] ?>" <?= (int) ($editLoan['id_buku'] ?? 0) === (int) $book['id_buku'] ? 'selected' : '' ?>><?= htmlspecialchars($book['judul'] . ' | Stok ' . $book['stok']) ?></option>
          <?php endforeach; ?>
        </select></label>
        <label><span>Tanggal Pinjam</span><input type="date" name="tanggal_pinjam" value="<?= htmlspecialchars((string) ($editLoan['tanggal_pinjam'] ?? date('Y-m-d'))) ?>" required></label>
        <label><span>Tanggal Kembali</span><input type="date" name="tanggal_kembali" value="<?= htmlspecialchars((string) ($editLoan['tanggal_kembali'] ?? date('Y-m-d', strtotime('+7 days')))) ?>" required></label>
        <label><span>Status</span><select name="status"><option value="dipinjam" <?= ($editLoan['status'] ?? '') === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option><option value="dikembalikan" <?= ($editLoan['status'] ?? '') === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option></select></label>
        <button type="submit" class="primary-button"><?= $editLoan ? 'Simpan Perubahan' : 'Tambah Peminjaman' ?> <i class="fa-solid fa-floppy-disk"></i></button>
        <?php if ($editLoan): ?><a href="peminjaman.php?<?= time() ?>" class="secondary-button">Batal</a><?php endif; ?>
      </form>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2>Daftar Peminjaman</h2><p><?= number_format($totalRows, 0, ',', '.') ?> transaksi ditemukan.</p></div></div>
      <form method="GET" class="admin-filter">
        <input type="search" name="q" placeholder="Cari nama, buku, atau status" value="<?= htmlspecialchars($search) ?>">
        <button class="secondary-button" type="submit">Cari</button>
      </form>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>Buku</th><th>Pinjam</th><th>Kembali</th><th>Status</th><th>Aksi</th></thead>
          <tbody>
            <?php foreach ($loans as $loan): ?>
              <tr>
                <td><?= htmlspecialchars($loan['nama']) ?></td>
                <td><?= htmlspecialchars($loan['judul']) ?></td>
                <td><?= date('d M Y', strtotime($loan['tanggal_pinjam'])) ?></td>
                <td><?= date('d M Y', strtotime($loan['tanggal_kembali'])) ?></td>
                <td><span class="status-badge <?= $loan['status'] === 'dikembalikan' ? 'done' : '' ?>"><?= $loan['status'] === 'dikembalikan' ? 'Dikembalikan' : 'Dipinjam' ?></span></td>
                <td class="admin-actions">
                  <a href="peminjaman.php?edit=<?= (int) $loan['id_peminjaman'] ?>&<?= time() ?>" class="admin-icon edit"><i class="fa-solid fa-pen"></i></a>
                  <form method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_peminjaman" value="<?= (int) $loan['id_peminjaman'] ?>">
                    <button class="admin-icon danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="admin-pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a class="<?= $i === $page ? 'active' : '' ?>" href="?q=<?= urlencode($search) ?>&page=<?= $i ?>&<?= time() ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </section>
  </main>
</body>
</html>