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

function clean_text(string $value): string
{
    return trim(strip_tags($value));
}

function upload_cover(array $file, ?string $currentCover = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentCover;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload cover gagal.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran cover maksimal 2 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = mime_content_type($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format cover harus JPG, PNG, atau WEBP.');
    }

    $fileName = 'cover_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = __DIR__ . '/../uploads/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Cover tidak bisa disimpan.');
    }

    return 'uploads/' . $fileName;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
            $judul = clean_text($_POST['judul'] ?? '');
            $pengarang = clean_text($_POST['pengarang'] ?? '');
            $kategori = clean_text($_POST['kategori'] ?? '');
            $tahun = filter_input(INPUT_POST, 'tahun_terbit', FILTER_VALIDATE_INT);
            $stok = filter_input(INPUT_POST, 'stok', FILTER_VALIDATE_INT);
            $currentCover = clean_text($_POST['current_cover'] ?? '');

            if ($judul === '' || $pengarang === '' || $kategori === '' || !$tahun || $stok === false || $stok < 0) {
                throw new RuntimeException('Lengkapi data buku dengan benar.');
            }

            $cover = upload_cover($_FILES['cover_buku'] ?? [], $currentCover ?: null);

            if ($id) {
                $statement = $pdo->prepare(
                    'UPDATE buku SET judul = ?, pengarang = ?, kategori = ?, tahun_terbit = ?, stok = ?, cover_buku = ? WHERE id_buku = ?'
                );
                $statement->execute([$judul, $pengarang, $kategori, $tahun, $stok, $cover, $id]);
                $message = 'Data buku berhasil diperbarui.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO buku (judul, pengarang, kategori, tahun_terbit, stok, cover_buku) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([$judul, $pengarang, $kategori, $tahun, $stok, $cover]);
                $message = 'Buku baru berhasil ditambahkan.';
            }
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('Buku tidak valid.');
            }

            $statement = $pdo->prepare('DELETE FROM buku WHERE id_buku = ?');
            $statement->execute([$id]);
            $message = 'Data buku berhasil dihapus.';
        }
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

$editBook = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
    $statement->execute([$editId]);
    $editBook = $statement->fetch() ?: null;
}

$search = clean_text($_GET['q'] ?? '');
$category = clean_text($_GET['kategori'] ?? '');
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 8;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(judul LIKE ? OR pengarang LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($category !== '') {
    $where[] = 'kategori = ?';
    $params[] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM buku {$whereSql}");
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max((int) ceil($totalRows / $limit), 1);

$dataStatement = $pdo->prepare("SELECT * FROM buku {$whereSql} ORDER BY id_buku DESC LIMIT {$limit} OFFSET {$offset}");
$dataStatement->execute($params);
$books = $dataStatement->fetchAll();

$categories = $pdo->query('SELECT DISTINCT kategori FROM buku ORDER BY kategori ASC')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>CRUD Buku - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="dashboard.php?<?= time() ?>" class="admin-logo"><i class="fa-solid fa-layer-group"></i><span>Perpus Digital</span></a>
    <nav>
      <a href="dashboard.php?<?= time() ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a class="active" href="buku.php?<?= time() ?>"><i class="fa-solid fa-book"></i> Buku</a>
      <a href="anggota.php?<?= time() ?>"><i class="fa-solid fa-users"></i> Anggota</a>
      <a href="peminjaman.php?<?= time() ?>"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
      <a href="../index.php?<?= time() ?>"><i class="fa-solid fa-globe"></i> Landing Page</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar"><div><span>Manajemen Buku</span><h1>Kelola koleksi dan stok</h1></div></header>
    <?php if ($message): ?><div class="admin-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2><?= $editBook ? 'Edit Buku' : 'Tambah Buku' ?></h2><p>Cover bersifat opsional. Jika kosong, kartu buku memakai ilustrasi default.</p></div></div>
      <form method="POST" enctype="multipart/form-data" class="admin-form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id_buku" value="<?= htmlspecialchars((string) ($editBook['id_buku'] ?? '')) ?>">
        <input type="hidden" name="current_cover" value="<?= htmlspecialchars((string) ($editBook['cover_buku'] ?? '')) ?>">
        <label><span>Judul</span><input type="text" name="judul" value="<?= htmlspecialchars((string) ($editBook['judul'] ?? '')) ?>" required></label>
        <label><span>Pengarang</span><input type="text" name="pengarang" value="<?= htmlspecialchars((string) ($editBook['pengarang'] ?? '')) ?>" required></label>
        <label><span>Kategori</span><input type="text" name="kategori" value="<?= htmlspecialchars((string) ($editBook['kategori'] ?? '')) ?>" required></label>
        <label><span>Tahun Terbit</span><input type="number" name="tahun_terbit" min="1900" max="2100" value="<?= htmlspecialchars((string) ($editBook['tahun_terbit'] ?? date('Y'))) ?>" required></label>
        <label><span>Stok</span><input type="number" name="stok" min="0" value="<?= htmlspecialchars((string) ($editBook['stok'] ?? '1')) ?>" required></label>
        <label><span>Cover Buku</span><input type="file" name="cover_buku" accept="image/jpeg,image/png,image/webp"></label>
        <button type="submit" class="primary-button"><?= $editBook ? 'Simpan Perubahan' : 'Tambah Buku' ?> <i class="fa-solid fa-floppy-disk"></i></button>
        <?php if ($editBook): ?><a href="buku.php?<?= time() ?>" class="secondary-button">Batal</a><?php endif; ?>
      </form>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2>Daftar Buku</h2><p><?= number_format($totalRows, 0, ',', '.') ?> data ditemukan.</p></div></div>
      <form method="GET" class="admin-filter">
        <input type="search" name="q" placeholder="Cari judul atau pengarang" value="<?= htmlspecialchars($search) ?>">
        <select name="kategori">
          <option value="">Semua kategori</option>
          <?php foreach ($categories as $item): ?>
            <option value="<?= htmlspecialchars($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= htmlspecialchars($item) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="secondary-button" type="submit">Filter</button>
      </form>
      <div class="admin-table-wrap">
          <table class="admin-table">
              <thead>
                  <tr>
                      <th>Cover</th>
                      <th>Judul</th>
                      <th>Kategori</th>
                      <th>Tahun</th>
                      <th>Stok</th>
                      <th>Aksi</th>
                  </tr>
              </thead>
              <tbody>
                <?php foreach ($books as $book): ?>
                <tr>
<td>
    <?php if (!empty($book['cover_buku'])): ?>
        <img class="admin-cover" src="../<?= $book['cover_buku'] ?>?t=<?= time() ?>" width="50" height="60" style="object-fit: cover;" alt="cover">
    <?php else: ?>
        <span class="admin-cover fallback"><i class="fa-solid fa-book"></i></span>
    <?php endif; ?>
</td>
                    <td><strong><?= htmlspecialchars($book['judul']) ?></strong><br><small><?= htmlspecialchars($book['pengarang']) ?></small></td>
                    <td><?= htmlspecialchars($book['kategori']) ?></td>
                    <td><?= htmlspecialchars((string) $book['tahun_terbit']) ?></td>
                    <td><?= htmlspecialchars((string) $book['stok']) ?></td>
                    <td class="admin-actions">
                        <a href="buku.php?edit=<?= (int) $book['id_buku'] ?>&<?= time() ?>" class="admin-icon edit"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" onsubmit="return confirm('Hapus buku ini?')" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_buku" value="<?= (int) $book['id_buku'] ?>">
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
          <a class="<?= $i === $page ? 'active' : '' ?>" href="?q=<?= urlencode($search) ?>&kategori=<?= urlencode($category) ?>&page=<?= $i ?>&<?= time() ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </section>
  </main>
</body>
</html>