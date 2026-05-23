<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

function clean_input(string $value): string
{
    return trim(strip_tags($value));
}

function respond(bool $success, string $message, int $statusCode = 200, array $extra = []): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Request tidak valid.', 405);
}

$nama = clean_input($_POST['nama'] ?? '');
$alamat = clean_input($_POST['alamat'] ?? '');
$noHp = clean_input($_POST['no_hp'] ?? '');
$idBuku = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
$tanggalPinjam = clean_input($_POST['tanggal_pinjam'] ?? '');
$tanggalKembali = clean_input($_POST['tanggal_kembali'] ?? '');

if ($nama === '' || $alamat === '' || $noHp === '' || !$idBuku || $tanggalPinjam === '' || $tanggalKembali === '') {
    respond(false, 'Lengkapi semua data peminjaman.', 422);
}

if (!preg_match('/^[0-9]{9,15}$/', $noHp)) {
    respond(false, 'Nomor HP hanya boleh berisi angka 9 sampai 15 digit.', 422);
}

if (!strtotime($tanggalPinjam) || !strtotime($tanggalKembali)) {
    respond(false, 'Format tanggal tidak valid.', 422);
}

if ($tanggalKembali < $tanggalPinjam) {
    respond(false, 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.', 422);
}

try {
    $pdo->beginTransaction();

    $bookStatement = $pdo->prepare('SELECT id_buku, judul, stok FROM buku WHERE id_buku = ? FOR UPDATE');
    $bookStatement->execute([$idBuku]);
    $book = $bookStatement->fetch();

    if (!$book) {
        $pdo->rollBack();
        respond(false, 'Buku tidak ditemukan.', 404);
    }

    if ((int) $book['stok'] <= 0) {
        $pdo->rollBack();
        respond(false, 'Stok buku sudah habis.', 409);
    }

    $memberStatement = $pdo->prepare('SELECT id_anggota FROM anggota WHERE no_hp = ? LIMIT 1');
    $memberStatement->execute([$noHp]);
    $member = $memberStatement->fetch();

    if ($member) {
        $idAnggota = (int) $member['id_anggota'];
        $updateMember = $pdo->prepare('UPDATE anggota SET nama = ?, alamat = ? WHERE id_anggota = ?');
        $updateMember->execute([$nama, $alamat, $idAnggota]);
    } else {
        $insertMember = $pdo->prepare('INSERT INTO anggota (nama, alamat, no_hp) VALUES (?, ?, ?)');
        $insertMember->execute([$nama, $alamat, $noHp]);
        $idAnggota = (int) $pdo->lastInsertId();
    }

    $loanStatement = $pdo->prepare(
        "INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status)
         VALUES (?, ?, ?, ?, 'dipinjam')"
    );
    $loanStatement->execute([$idAnggota, $idBuku, $tanggalPinjam, $tanggalKembali]);

    $pdo->commit();

    respond(true, 'Peminjaman berhasil dicatat.', 200, [
        'book_title' => $book['judul'],
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (str_contains($exception->getMessage(), 'Stok buku tidak tersedia')) {
        respond(false, 'Stok buku tidak tersedia.', 409);
    }

    respond(false, 'Peminjaman belum bisa diproses.', 500);
}
