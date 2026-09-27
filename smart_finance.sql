-- =========================================
-- Database: smart_finance
-- Aplikasi Pencatatan Keuangan Siswa
-- =========================================

CREATE DATABASE IF NOT EXISTS smart_finance
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_finance;

-- =========================================
-- Tabel: users
-- Menampung akun siswa & guru
-- =========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa','guru','admin') NOT NULL,
    nis_nip VARCHAR(30) DEFAULT NULL,
    kelas VARCHAR(20) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Tabel: guru_siswa
-- Relasi guru yang memantau siswa tertentu
-- =========================================
CREATE TABLE guru_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    siswa_id INT NOT NULL,
    CONSTRAINT fk_guru_siswa_guru FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_guru_siswa_siswa FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guru_siswa (guru_id, siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Tabel: kategori
-- Kategori pemasukan / pengeluaran
-- =========================================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    jenis ENUM('pemasukan','pengeluaran') NOT NULL,
    siswa_id INT DEFAULT NULL,
    CONSTRAINT fk_kategori_siswa FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Tabel: transaksi
-- Pencatatan pemasukan & pengeluaran siswa
-- (bukti/lampiran digabung di sini, 1 file per transaksi)
-- =========================================
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    kategori_id INT NOT NULL,
    jenis ENUM('pemasukan','pengeluaran') NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL,
    keterangan TEXT,
    tanggal_transaksi DATE NOT NULL,
    bukti_file VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_siswa FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Tabel: catatan_guru
-- Feedback/komentar guru terhadap transaksi siswa (opsional)
-- =========================================
CREATE TABLE catatan_guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    guru_id INT NOT NULL,
    komentar TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_catatan_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    CONSTRAINT fk_catatan_guru FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Data awal: akun admin default
-- Email: admin@smart.com | Password: smart101
-- =========================================
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@smart.com', '$2b$12$HefFk.9M0eCJEOaVPpdojO50zF3a/daZiHZnbrLcPleoGg.u8ktVe', 'admin');

-- =========================================
-- Data dummy: 1 akun guru & 2 akun siswa
-- Password semua akun dummy: password123
-- =========================================
INSERT INTO users (nama, email, password, role, nis_nip, kelas) VALUES
('Budi Santoso', 'budi.guru@smart.com', '$2b$12$QeYQtOyqvPihTt9T.GVyOen3VP5gO3RvVFCSBdar6UX0DrR76O8Sa', 'guru', '198501012010011001', NULL),
('Ani Wulandari', 'ani.siswa@smart.com', '$2b$12$QeYQtOyqvPihTt9T.GVyOen3VP5gO3RvVFCSBdar6UX0DrR76O8Sa', 'siswa', '2024001', 'XII RPL 1'),
('Rizky Pratama', 'rizky.siswa@smart.com', '$2b$12$QeYQtOyqvPihTt9T.GVyOen3VP5gO3RvVFCSBdar6UX0DrR76O8Sa', 'siswa', '2024002', 'XII RPL 1');

-- Relasi guru pemantau untuk kedua siswa dummy di atas
INSERT INTO guru_siswa (guru_id, siswa_id)
SELECT g.id, s.id FROM users g, users s
WHERE g.email = 'budi.guru@smart.com' AND s.email = 'ani.siswa@smart.com';

INSERT INTO guru_siswa (guru_id, siswa_id)
SELECT g.id, s.id FROM users g, users s
WHERE g.email = 'budi.guru@smart.com' AND s.email = 'rizky.siswa@smart.com';

-- =========================================
-- Data awal (opsional): kategori default sistem
-- =========================================
INSERT INTO kategori (nama_kategori, jenis, siswa_id) VALUES
('Uang Saku', 'pemasukan', NULL),
('Beasiswa', 'pemasukan', NULL),
('Hadiah', 'pemasukan', NULL),
('Jajan', 'pengeluaran', NULL),
('Transport', 'pengeluaran', NULL),
('Buku & Alat Tulis', 'pengeluaran', NULL),
('Tabungan', 'pengeluaran', NULL);
