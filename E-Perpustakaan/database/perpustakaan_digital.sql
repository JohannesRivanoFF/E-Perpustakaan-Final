-- ======================================================
-- DATABASE: perpustakaan_digital
-- SEMUA TABEL, TRIGGER, DAN DATA AWAL
-- ======================================================

CREATE DATABASE IF NOT EXISTS perpustakaan_digital
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE perpustakaan_digital;

-- ==================== TABEL admin ====================
CREATE TABLE admin (
    id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_admin VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================== TABEL anggota ====================
CREATE TABLE anggota (
    id_anggota INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================== TABEL buku ====================
CREATE TABLE buku (
    id_buku INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    pengarang VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    tahun_terbit YEAR NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    cover_buku VARCHAR(255) DEFAULT NULL,
    deskripsi TEXT,
    total_rating DECIMAL(2,1) DEFAULT 0.0,
    jumlah_rating INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================== TABEL peminjaman ====================
CREATE TABLE peminjaman (
    id_peminjaman INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_anggota INT UNSIGNED NOT NULL,
    id_buku INT UNSIGNED NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    status ENUM('dipinjam', 'dikembalikan') DEFAULT 'dipinjam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON DELETE RESTRICT,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE RESTRICT
);

-- ==================== TABEL rating_review ====================
CREATE TABLE rating_review (
    id_rating INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_buku INT UNSIGNED NOT NULL,
    nama_pengunjung VARCHAR(100) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
);

-- ==================== TABEL log_aktivitas (opsional) ====================
CREATE TABLE log_aktivitas (
    id_log INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_admin INT UNSIGNED,
    aksi VARCHAR(50) NOT NULL,
    tabel VARCHAR(50),
    data_id INT UNSIGNED,
    detail TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL
);

-- ==================== TRIGGER: update stok saat status peminjaman berubah ====================
-- Saat status diubah menjadi 'dikembalikan', stok buku bertambah 1
-- Saat status diubah menjadi 'dipinjam' (karena edit manual), stok berkurang 1 (hanya jika sebelumnya dikembalikan)
-- Catatan: Untuk keamanan, trigger ini hanya menambah stok saat status = 'dikembalikan'
-- Pengurangan stok dilakukan di aplikasi (pinjam.php) agar tidak double.

DELIMITER $$

CREATE TRIGGER update_stok_setelah_pengembalian
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    -- Jika status berubah dari 'dipinjam' menjadi 'dikembalikan', tambah stok buku
    IF OLD.status = 'dipinjam' AND NEW.status = 'dikembalikan' THEN
        UPDATE buku SET stok = stok + 1 WHERE id_buku = NEW.id_buku;
    END IF;
END$$

DELIMITER ;

-- ==================== DATA AWAL (CONTOH) ====================

-- Admin: username = admin, password = admin123 (hash bcrypt)
INSERT INTO admin (username, password, nama_admin) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama');

-- Anggota contoh
INSERT INTO anggota (nama, alamat, no_hp) VALUES
('Budi Santoso', 'Jl. Merdeka No.12, Jakarta', '081234567890'),
('Siti Aminah', 'Jl. Pendidikan No.5, Bandung', '085678901234'),
('Agus Wijaya', 'Perumahan Cendekia Blok A3, Surabaya', '089876543210');

-- Buku contoh
INSERT INTO buku (judul, pengarang, kategori, tahun_terbit, stok, cover_buku, deskripsi) VALUES
('Pemrograman Web dengan PHP', 'Andi Wijaya', 'Teknologi', 2023, 5, NULL, 'Buku panduan lengkap pemrograman web menggunakan PHP dan MySQL.'),
('Dasar-Dasar Database', 'Siti Aminah', 'Informatika', 2022, 3, NULL, 'Konsep fundamental database relasional dan SQL.'),
('Algoritma dan Struktur Data', 'Rina Mahardika', 'Ilmu Komputer', 2021, 4, NULL, 'Pembahasan algoritma sorting, searching, dan struktur data.'),
('Manajemen Perpustakaan', 'Fajar Nugroho', 'Manajemen', 2020, 2, NULL, 'Tata kelola perpustakaan modern berbasis teknologi.'),
('Literasi Digital', 'Dewi Lestari', 'Pendidikan', 2024, 6, NULL, 'Panduan meningkatkan kemampuan literasi digital untuk siswa dan guru.'),
('Keamanan Siber', 'Budi Santoso', 'Teknologi', 2023, 3, NULL, 'Prinsip dasar keamanan jaringan dan data.'),
('Kecerdasan Buatan', 'Dr. Irwan Setiawan', 'Teknologi', 2024, 2, NULL, 'Pengantar AI dan machine learning dengan studi kasus.'),
('Pendidikan Karakter', 'Prof. Dr. Ani Mulyani', 'Pendidikan', 2022, 5, NULL, 'Membangun karakter generasi muda melalui pendidikan.');

-- Peminjaman contoh (beberapa status dipinjam, beberapa dikembalikan)
INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status) VALUES
(1, 1, '2025-01-10', '2025-01-17', 'dikembalikan'),
(2, 3, '2025-01-15', '2025-01-22', 'dipinjam'),
(3, 5, '2025-01-18', '2025-01-25', 'dipinjam'),
(1, 2, '2025-01-20', '2025-01-27', 'dipinjam'),
(2, 7, '2025-01-05', '2025-01-12', 'dikembalikan');

-- Rating & review contoh
INSERT INTO rating_review (id_buku, nama_pengunjung, rating, review) VALUES
(1, 'Budi Santoso', 5, 'Sangat membantu dalam belajar PHP.'),
(1, 'Ani Wijaya', 4, 'Penjelasan cukup jelas, ada sedikit typo.'),
(3, 'Citra Kirana', 5, 'Buku algoritma terbaik yang pernah saya baca.'),
(5, 'Dedi Setiawan', 4, 'Materi literasi digital up to date.');

-- Update total_rating di tabel buku berdasarkan data rating_review
UPDATE buku b 
SET total_rating = (
    SELECT ROUND(AVG(rating), 1) 
    FROM rating_review 
    WHERE id_buku = b.id_buku
),
jumlah_rating = (
    SELECT COUNT(*) 
    FROM rating_review 
    WHERE id_buku = b.id_buku
)
WHERE EXISTS (SELECT 1 FROM rating_review WHERE id_buku = b.id_buku);

-- ==================== SELESAI ====================