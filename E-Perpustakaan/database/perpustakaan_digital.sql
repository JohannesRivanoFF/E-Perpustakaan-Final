CREATE DATABASE IF NOT EXISTS perpustakaan_digital
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE perpustakaan_digital;

CREATE TABLE IF NOT EXISTS anggota (
  id_anggota INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  alamat TEXT NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_anggota_no_hp (no_hp)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS buku (
  id_buku INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(160) NOT NULL,
  pengarang VARCHAR(120) NOT NULL,
  kategori VARCHAR(80) NOT NULL DEFAULT 'Umum',
  tahun_terbit YEAR NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  cover_buku VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_buku_judul_pengarang (judul, pengarang)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS peminjaman (
  id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
  id_anggota INT NOT NULL,
  id_buku INT NOT NULL,
  tanggal_pinjam DATE NOT NULL,
  tanggal_kembali DATE NOT NULL,
  status ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_peminjaman_anggota
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_peminjaman_buku
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_peminjaman_transaksi (id_anggota, id_buku, tanggal_pinjam)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin (
  id_admin INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama_admin VARCHAR(120) NOT NULL,
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

ALTER TABLE buku
  ADD COLUMN IF NOT EXISTS kategori VARCHAR(80) NOT NULL DEFAULT 'Umum' AFTER pengarang,
  ADD COLUMN IF NOT EXISTS stok INT NOT NULL DEFAULT 0 AFTER tahun_terbit,
  ADD COLUMN IF NOT EXISTS cover_buku VARCHAR(255) DEFAULT NULL AFTER stok;

ALTER TABLE peminjaman
  ADD COLUMN IF NOT EXISTS status ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam' AFTER tanggal_kembali;

DELIMITER $$

DROP TRIGGER IF EXISTS before_insert_peminjaman_check_stok$$
CREATE TRIGGER before_insert_peminjaman_check_stok
BEFORE INSERT ON peminjaman
FOR EACH ROW
BEGIN
  DECLARE stok_tersedia INT DEFAULT 0;

  IF NEW.status = 'dipinjam' THEN
    SELECT stok INTO stok_tersedia
    FROM buku
    WHERE id_buku = NEW.id_buku;

    IF stok_tersedia <= 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok buku tidak tersedia';
    END IF;
  END IF;
END$$

DROP TRIGGER IF EXISTS before_update_peminjaman_check_stok$$
CREATE TRIGGER before_update_peminjaman_check_stok
BEFORE UPDATE ON peminjaman
FOR EACH ROW
BEGIN
  DECLARE stok_tersedia INT DEFAULT 0;

  IF OLD.status = 'dikembalikan' AND NEW.status = 'dipinjam' THEN
    SELECT stok INTO stok_tersedia
    FROM buku
    WHERE id_buku = NEW.id_buku;

    IF stok_tersedia <= 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok buku tidak tersedia';
    END IF;
  END IF;
END$$

DROP TRIGGER IF EXISTS after_insert_peminjaman_kurangi_stok$$
CREATE TRIGGER after_insert_peminjaman_kurangi_stok
AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
  IF NEW.status = 'dipinjam' THEN
    UPDATE buku
    SET stok = stok - 1
    WHERE id_buku = NEW.id_buku;
  END IF;
END$$

DROP TRIGGER IF EXISTS after_update_peminjaman_sync_stok$$
CREATE TRIGGER after_update_peminjaman_sync_stok
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
  IF OLD.status = 'dipinjam' AND NEW.status = 'dikembalikan' THEN
    UPDATE buku
    SET stok = stok + 1
    WHERE id_buku = NEW.id_buku;
  ELSEIF OLD.status = 'dikembalikan' AND NEW.status = 'dipinjam' THEN
    UPDATE buku
    SET stok = stok - 1
    WHERE id_buku = NEW.id_buku;
  END IF;
END$$

DELIMITER ;

INSERT INTO admin (username, password, nama_admin) VALUES
('admin', '$2y$10$W1HlaNKl3ewx7S6Ywd.y...Q7TfCBGx3MhPSCFzM1OhOFu/ZH4v42', 'Administrator')
ON DUPLICATE KEY UPDATE
  password = VALUES(password),
  nama_admin = VALUES(nama_admin);

INSERT INTO anggota (nama, alamat, no_hp) VALUES
('Alya Putri', 'Jl. Melati No. 12, Jakarta', '081234567801'),
('Bima Pratama', 'Jl. Pendidikan No. 5, Bandung', '081234567802'),
('Citra Lestari', 'Jl. Literasi No. 8, Surabaya', '081234567803'),
('Danu Saputra', 'Jl. Merdeka No. 21, Yogyakarta', '081234567804')
ON DUPLICATE KEY UPDATE
  nama = VALUES(nama),
  alamat = VALUES(alamat);

INSERT INTO buku (judul, pengarang, kategori, tahun_terbit, stok, cover_buku) VALUES
('Algoritma Cerdas', 'Rina Mahardika', 'Teknologi', 2023, 6, NULL),
('Basis Data Modern', 'Aditya Prakoso', 'Teknologi', 2022, 5, NULL),
('Manajemen Pendidikan', 'Sinta Lestari', 'Pendidikan', 2021, 7, NULL),
('Web Development', 'Fajar Nugroho', 'Pemrograman', 2024, 4, NULL),
('Analisis Sistem Informasi', 'Maya Kartika', 'Sistem Informasi', 2020, 5, NULL),
('Pengantar Jaringan Komputer', 'Rizky Ananda', 'Jaringan', 2023, 6, NULL)
ON DUPLICATE KEY UPDATE
  kategori = VALUES(kategori),
  tahun_terbit = VALUES(tahun_terbit),
  stok = VALUES(stok);

INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status) VALUES
(1, 1, '2026-05-01', '2026-05-10', 'dikembalikan'),
(2, 2, '2026-05-11', '2026-05-25', 'dipinjam'),
(3, 4, '2026-05-15', '2026-05-28', 'dipinjam')
ON DUPLICATE KEY UPDATE
  tanggal_kembali = VALUES(tanggal_kembali),
  status = VALUES(status);

UPDATE buku
SET stok = CASE judul
  WHEN 'Algoritma Cerdas' THEN 6
  WHEN 'Basis Data Modern' THEN 5
  WHEN 'Manajemen Pendidikan' THEN 7
  WHEN 'Web Development' THEN 4
  WHEN 'Analisis Sistem Informasi' THEN 5
  WHEN 'Pengantar Jaringan Komputer' THEN 6
  ELSE stok
END
WHERE judul IN (
  'Algoritma Cerdas',
  'Basis Data Modern',
  'Manajemen Pendidikan',
  'Web Development',
  'Analisis Sistem Informasi',
  'Pengantar Jaringan Komputer'
);

UPDATE buku b
LEFT JOIN (
  SELECT id_buku, COUNT(*) AS jumlah
  FROM peminjaman
  WHERE status = 'dipinjam'
  GROUP BY id_buku
) p ON p.id_buku = b.id_buku
SET b.stok = GREATEST(b.stok - COALESCE(p.jumlah, 0), 0)
WHERE b.judul IN (
  'Algoritma Cerdas',
  'Basis Data Modern',
  'Manajemen Pendidikan',
  'Web Development',
  'Analisis Sistem Informasi',
  'Pengantar Jaringan Komputer'
);
