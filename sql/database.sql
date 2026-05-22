-- =============================================
-- SISTEM PELAPORAN KEUANGAN MASJID
-- Database: keuangan_masjid
-- =============================================

CREATE DATABASE IF NOT EXISTS keuangan_masjid DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE keuangan_masjid;

-- =============================================
-- TABEL USERS
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'bendahara') NOT NULL DEFAULT 'bendahara',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL AKUN (COA - Chart of Accounts)
-- =============================================
CREATE TABLE akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_akun VARCHAR(20) NOT NULL UNIQUE,
    nama_akun VARCHAR(100) NOT NULL,
    kategori ENUM('Aset', 'Kewajiban', 'Aset Neto', 'Pendapatan', 'Beban', 'Dana Khusus') NOT NULL,
    saldo_normal ENUM('Debit', 'Kredit') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL DANA KHUSUS
-- =============================================
CREATE TABLE dana_khusus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_dana VARCHAR(20) NOT NULL UNIQUE,
    nama_dana VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    saldo DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL INVENTARIS
-- =============================================
CREATE TABLE inventaris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100) NOT NULL,
    tahun_pembelian YEAR DEFAULT NULL,
    harga DECIMAL(15,2) DEFAULT 0,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Baik',
    lokasi VARCHAR(100) DEFAULT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL TRANSAKSI (Header Jurnal)
-- =============================================
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    keterangan TEXT,
    sumber_dana VARCHAR(100) DEFAULT NULL,
    metode_bayar VARCHAR(50) DEFAULT NULL,
    tujuan VARCHAR(200) DEFAULT NULL,
    dana_khusus_id INT DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dana_khusus_id) REFERENCES dana_khusus(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- TABEL JURNAL DETAIL (Entry Jurnal)
-- =============================================
CREATE TABLE jurnal_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    akun_id INT NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    kredit DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (akun_id) REFERENCES akun(id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL AUDIT LOGS
-- =============================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    aksi VARCHAR(50) NOT NULL,
    tabel VARCHAR(50) NOT NULL,
    record_id INT,
    detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- TABEL PERIODE AKUNTANSI
-- =============================================
CREATE TABLE periode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    nama VARCHAR(100),
    status ENUM('aktif', 'tutup') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (bulan, tahun)
) ENGINE=InnoDB;

-- =============================================
-- TABEL SETTINGS
-- =============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT
) ENGINE=InnoDB;

-- =============================================
-- (Semua data seed telah dihapus.
--  Aplikasi bersih tanpa data awal.)
-- =============================================
