-- =============================================
-- MIGRASI: SISTEM PELAPORAN KEUANGAN MASJID
-- Non-Profit Accounting
-- =============================================

-- 1. APPROVAL TRANSAKSI
ALTER TABLE transaksi
  ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  ADD COLUMN approved_by INT DEFAULT NULL,
  ADD COLUMN approved_at DATETIME DEFAULT NULL,
  ADD COLUMN rejected_note TEXT DEFAULT NULL,
  ADD COLUMN bukti_file VARCHAR(255) DEFAULT NULL,
  ADD INDEX idx_transaksi_status (status);

-- 2. PENYUSUTAN ASET
ALTER TABLE inventaris
  ADD COLUMN umur_ekonomis INT DEFAULT NULL COMMENT 'Dalam tahun',
  ADD COLUMN nilai_residu DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN metode_penyusutan ENUM('garis_lurus','saldo_menurun') DEFAULT 'garis_lurus',
  ADD COLUMN akumulasi_penyusutan DECIMAL(15,2) DEFAULT 0,
  ADD COLUMN tgl_perolehan DATE DEFAULT NULL,
  ADD COLUMN akun_aset_id INT DEFAULT NULL,
  ADD COLUMN akun_akumulasi_id INT DEFAULT NULL,
  ADD COLUMN akun_beban_penyusutan_id INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS jurnal_penyusutan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventaris_id INT NOT NULL,
  tanggal DATE NOT NULL,
  beban_penyusutan DECIMAL(15,2) DEFAULT 0,
  akumulasi_penyusutan DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inventaris_id) REFERENCES inventaris(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. SISTEM ANGGARAN
CREATE TABLE IF NOT EXISTS anggaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  akun_id INT NOT NULL,
  jenis ENUM('bulanan','tahunan') NOT NULL DEFAULT 'bulanan',
  bulan INT DEFAULT NULL,
  tahun INT NOT NULL,
  jumlah DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (akun_id) REFERENCES akun(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. AUDIT LOG DETAIL
ALTER TABLE audit_logs
  ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL,
  ADD COLUMN user_agent TEXT DEFAULT NULL,
  ADD COLUMN before_value TEXT DEFAULT NULL,
  ADD COLUMN after_value TEXT DEFAULT NULL;

-- 5. TABEL CATATAN ATAS LAPORAN KEUANGAN (CALK)
CREATE TABLE IF NOT EXISTS calk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  konten TEXT,
  urutan INT DEFAULT 0,
  tahun INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. KLASIFIKASI DANA
ALTER TABLE akun
  ADD COLUMN jenis_dana ENUM('terikat','tidak_terikat','neto') DEFAULT 'tidak_terikat',
  ADD COLUMN is_aktif TINYINT(1) DEFAULT 1;

-- 7. SETTING BARU
INSERT IGNORE INTO settings (key_name, value) VALUES
('session_timeout', '3600'),
('max_login_attempts', '5'),
('lockout_duration', '900'),
('tahun_operasi', '2025'),
('api_token', SHA2(UUID(), 256)),
('saldo_awal_kas', '50000000');

-- 8. AKUN BARU UNTUK PENYUSUTAN
INSERT IGNORE INTO akun (kode_akun, nama_akun, kategori, saldo_normal, jenis_dana) VALUES
('1-1299', 'Akumulasi Penyusutan', 'Aset', 'Kredit', 'tidak_terikat'),
('5-2100', 'Beban Penyusutan', 'Beban', 'Debit', 'tidak_terikat');

-- 9. UPDATE AKUN LAMA DENGAN JENIS DANA DEFAULT
UPDATE akun SET jenis_dana = 'tidak_terikat' WHERE jenis_dana IS NULL AND kategori IN ('Aset', 'Kewajiban', 'Beban');
UPDATE akun SET jenis_dana = 'neto' WHERE jenis_dana IS NULL AND kategori = 'Aset Neto';
UPDATE akun SET jenis_dana = 'terikat' WHERE jenis_dana IS NULL AND kategori = 'Dana Khusus';
UPDATE akun SET jenis_dana = 'tidak_terikat' WHERE jenis_dana IS NULL AND kategori = 'Pendapatan';
