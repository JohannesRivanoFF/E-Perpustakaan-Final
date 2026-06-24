<?php
declare(strict_types=1);

session_start();

// ===== ANTI CACHE - SOLUSI SIMPLE =====
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

try {
    $pdo->exec('ALTER TABLE anggota ADD email VARCHAR(150) DEFAULT NULL AFTER no_hp');
} catch (Throwable) {
    // Column already exists.
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id_anggota', FILTER_VALIDATE_INT);
            $nama = clean_text($_POST['nama'] ?? '');
            $alamat = clean_text($_POST['alamat'] ?? '');
            $noHp = clean_text($_POST['no_hp'] ?? '');
            $email = clean_text($_POST['email'] ?? '');

            if ($nama === '' || $alamat === '' || !preg_match('/^[0-9]{9,15}$/', $noHp) || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                throw new RuntimeException('Lengkapi data anggota dengan benar.');
            }

            if ($id) {
                $statement = $pdo->prepare('UPDATE anggota SET nama = ?, alamat = ?, no_hp = ?, email = ? WHERE id_anggota = ?');
                $statement->execute([$nama, $alamat, $noHp, $email ?: null, $id]);
                $message = 'Data anggota berhasil diperbarui.';
            } else {
                $statement = $pdo->prepare('INSERT INTO anggota (nama, alamat, no_hp, email) VALUES (?, ?, ?, ?)');
                $statement->execute([$nama, $alamat, $noHp, $email ?: null]);
                $message = 'Anggota baru berhasil ditambahkan.';
            }
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id_anggota', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('Anggota tidak valid.');
            }

            $statement = $pdo->prepare('DELETE FROM anggota WHERE id_anggota = ?');
            $statement->execute([$id]);
            $message = 'Data anggota berhasil dihapus.';
        }
    }
} catch (Throwable $exception) {
    $error = str_contains($exception->getMessage(), 'foreign key')
        ? 'Anggota tidak bisa dihapus karena masih memiliki riwayat peminjaman.'
        : $exception->getMessage();
}

$editMember = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM anggota WHERE id_anggota = ?');
    $statement->execute([$editId]);
    $editMember = $statement->fetch() ?: null;
}

$search = clean_text($_GET['q'] ?? '');
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$params = [];
$whereSql = '';

if ($search !== '') {
    $whereSql = 'WHERE nama LIKE ? OR alamat LIKE ? OR no_hp LIKE ? OR email LIKE ?';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
}

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM anggota {$whereSql}");
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max((int) ceil($totalRows / $limit), 1);

$dataStatement = $pdo->prepare("SELECT * FROM anggota {$whereSql} ORDER BY id_anggota DESC LIMIT {$limit} OFFSET {$offset}");
$dataStatement->execute($params);
$members = $dataStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>CRUD Anggota - Perpus Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="dashboard.php?<?= time() ?>" class="admin-logo"><i class="fa-solid fa-layer-group"></i><span>Perpus Digital</span></a>
    <nav>
      <a href="dashboard.php?<?= time() ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      <a href="buku.php?<?= time() ?>"><i class="fa-solid fa-book"></i> Buku</a>
      <a class="active" href="anggota.php?<?= time() ?>"><i class="fa-solid fa-users"></i> Anggota</a>
      <a href="peminjaman.php?<?= time() ?>"><i class="fa-solid fa-right-left"></i> Peminjaman</a>
      <a href="pembayaran.php?<?= time() ?>"><i class="fa-solid fa-qrcode"></i> Pembayaran</a>
      <a href="../index.php?<?= time() ?>"><i class="fa-solid fa-globe"></i> Landing Page</a>
      <a href="ganti_password.php?<?= time() ?>"><i class="fa-solid fa-key"></i> Ganti Password</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar"><div><span>Manajemen Anggota</span><h1>Kelola data peminjam</h1></div></header>
    <?php if ($message): ?><div class="admin-alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2><?= $editMember ? 'Edit Anggota' : 'Tambah Anggota' ?></h2><p>Nomor HP digunakan sebagai identitas unik anggota.</p></div></div>
      <form method="POST" class="admin-form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id_anggota" value="<?= htmlspecialchars((string) ($editMember['id_anggota'] ?? '')) ?>">
        <label><span>Nama</span><input type="text" name="nama" value="<?= htmlspecialchars((string) ($editMember['nama'] ?? '')) ?>" required></label>
        <label><span>No HP</span><input type="tel" name="no_hp" value="<?= htmlspecialchars((string) ($editMember['no_hp'] ?? '')) ?>" required></label>
        <label><span>Email</span><input type="email" name="email" value="<?= htmlspecialchars((string) ($editMember['email'] ?? '')) ?>" placeholder="Email notifikasi pembayaran"></label>
        <label class="admin-wide"><span>Alamat</span><textarea name="alamat" rows="3" required><?= htmlspecialchars((string) ($editMember['alamat'] ?? '')) ?></textarea></label>
        <button type="submit" class="primary-button"><?= $editMember ? 'Simpan Perubahan' : 'Tambah Anggota' ?> <i class="fa-solid fa-floppy-disk"></i></button>
        <?php if ($editMember): ?><a href="anggota.php?<?= time() ?>" class="secondary-button">Batal</a><?php endif; ?>
      </form>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-head"><div><h2>Daftar Anggota</h2><p><?= number_format($totalRows, 0, ',', '.') ?> data ditemukan.</p></div></div>
      <form method="GET" class="admin-filter">
        <input type="search" name="q" placeholder="Cari nama, email, alamat, atau nomor HP" value="<?= htmlspecialchars($search) ?>">
        <button class="secondary-button" type="submit">Cari</button>
      </form>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>No HP</th><th>Email</th><th>Alamat</th><th>Terdaftar</th><th>Aksi</th></thead>
          <tbody>
            <?php foreach ($members as $member): ?>
              <tr>
                <td><strong><?= htmlspecialchars($member['nama']) ?></strong></td>
                <td><?= htmlspecialchars($member['no_hp']) ?></td>
                <td><?= htmlspecialchars((string) ($member['email'] ?? '-')) ?></td>
                <td><?= htmlspecialchars($member['alamat']) ?></td>
                <td><?= date('d M Y', strtotime($member['created_at'])) ?></td>
                <td class="admin-actions">
                  <a href="anggota.php?edit=<?= (int) $member['id_anggota'] ?>&<?= time() ?>" class="admin-icon edit"><i class="fa-solid fa-pen"></i></a>
<!-- Anggota -->
<form method="POST" onsubmit="showConfirmModal('⚠️ Yakin ingin menghapus anggota ini?', this, this.querySelector('button')); return false;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id_anggota" value="<?= (int) $member['id_anggota'] ?>">
    <button class="admin-icon danger" type="submit"><i class="fa-solid fa-trash"></i></button>
</form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
<div class="admin-pagination">
    <?= generatePagination($page, $totalPages, 'anggota.php?q=' . urlencode($search)) ?>
</div>
    </section>
  </main>
  <script>
// ==================== TOAST NOTIFICATION ====================
function showToast(message, type = 'success') {
    const oldToast = document.querySelector('.toast-notification');
    if (oldToast) oldToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    let icon = '<i class="fa-solid fa-check-circle"></i>';
    if (type === 'error') icon = '<i class="fa-solid fa-exclamation-circle"></i>';
    if (type === 'warning') icon = '<i class="fa-solid fa-triangle-exclamation"></i>';
    
    toast.innerHTML = `${icon}<span>${message}</span><span class="toast-close"><i class="fa-solid fa-xmark"></i></span>`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
    
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    });
}

// ==================== MODAL KONFIRMASI CUSTOM ====================

// Buat elemen modal (hanya sekali)
if (!document.getElementById('confirmModal')) {
    const modalHtml = `
        <div class="modal-confirm-overlay" id="confirmModal">
            <div class="modal-confirm-box">
                <div class="modal-confirm-icon">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3>Konfirmasi Hapus</h3>
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="modal-confirm-buttons">
                    <button class="modal-confirm-btn cancel" id="modalCancelBtn">Batal</button>
                    <button class="modal-confirm-btn confirm" id="modalConfirmBtn">Hapus</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

const modal = document.getElementById('confirmModal');
let currentForm = null;

// Fungsi untuk menunjukkan modal
window.showConfirmModal = function(message, formElement) {
    const msgEl = document.getElementById('confirmMessage');
    if (msgEl) msgEl.textContent = message;
    currentForm = formElement;
    if (modal) modal.classList.add('show');
}

// Setup event listener modal (hanya sekali)
if (!window.modalListenersAttached) {
    window.modalListenersAttached = true;
    
    const confirmBtn = document.getElementById('modalConfirmBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (modal) modal.classList.remove('show');
            if (currentForm) {
                // Submit form biasa (bukan AJAX)
                currentForm.submit();
            }
            currentForm = null;
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (modal) modal.classList.remove('show');
            currentForm = null;
        });
    }
    
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
                currentForm = null;
            }
        });
    }
}
</script>
</body>
</html>
