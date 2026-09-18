-- =========================================================
-- DATABASE: sparepart_barcode
-- Aplikasi Manajemen Sparepart Lokal + Generator Barcode CODE128
-- Kompatibel MySQL 5.7+ / MariaDB 10.2+
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Tabel: users
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User default: username = admin, password = admin123
-- WAJIB diganti setelah instalasi pertama! (hash bcrypt di bawah = "admin123")
INSERT INTO `users` (`username`,`password_hash`,`nama_lengkap`,`role`)
VALUES ('admin', '$2y$10$b1wi/RLIHo.GnNljaQOtWuSrJjqH7FEiCkjkvfk6ATCHxrr54WdZu', 'Administrator', 'admin');

-- ---------------------------------------------------------
-- Tabel: spareparts
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `spareparts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_custom` VARCHAR(50) NOT NULL,
  `nama_sparepart` VARCHAR(150) NOT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `merk` VARCHAR(80) DEFAULT NULL,
  `jenis_motor` VARCHAR(80) DEFAULT NULL,
  `kategori` VARCHAR(80) DEFAULT NULL,
  `satuan` VARCHAR(30) DEFAULT 'PCS',
  `harga_beli` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `harga_jual` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `stok` INT NOT NULL DEFAULT 0,
  `lokasi_rak` VARCHAR(50) DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kode_custom` (`kode_custom`),
  KEY `idx_nama` (`nama_sparepart`),
  KEY `idx_merk` (`merk`),
  KEY `idx_jenis_motor` (`jenis_motor`),
  KEY `idx_kategori` (`kategori`),
  FULLTEXT KEY `ft_nama` (`nama_sparepart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabel: sparepart_foto_tambahan
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sparepart_foto_tambahan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sparepart_id` INT UNSIGNED NOT NULL,
  `path_foto` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sparepart_id` (`sparepart_id`),
  CONSTRAINT `fk_foto_sparepart` FOREIGN KEY (`sparepart_id`) REFERENCES `spareparts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabel: barcode_print_history
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `barcode_print_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sparepart_id` INT UNSIGNED NOT NULL,
  `kode_custom` VARCHAR(50) NOT NULL,
  `nama_sparepart_snapshot` VARCHAR(150) NOT NULL,
  `jumlah` INT NOT NULL,
  `printed_by` VARCHAR(100) NOT NULL,
  `printed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sparepart_id` (`sparepart_id`),
  KEY `idx_kode_custom` (`kode_custom`),
  CONSTRAINT `fk_history_sparepart` FOREIGN KEY (`sparepart_id`) REFERENCES `spareparts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabel: settings (key-value store)
-- Dipakai oleh: Pengaturan Umum (nama aplikasi) & Cetak Label A4 (kalibrasi grid label).
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Contoh data (boleh dihapus setelah testing)
-- ---------------------------------------------------------
INSERT INTO `spareparts`
(`kode_custom`,`nama_sparepart`,`merk`,`jenis_motor`,`kategori`,`satuan`,`harga_beli`,`harga_jual`,`stok`,`lokasi_rak`,`keterangan`)
VALUES
('JL-BEAT-KR-001','Kampas Rem Depan Beat','JASINDO','Honda Beat','Kampas Rem','PCS',25000,35000,20,'A-01','-'),
('JL-VARIO-KR-002','Kampas Rem Vario','JASINDO','Honda Vario','Kampas Rem','PCS',27000,38000,15,'A-02','-'),
('JL-SCOOPY-OLI-003','Oli Scoopy 0.8L','FEDERAL','Honda Scoopy','Oli Mesin','BOTOL',30000,42000,5,'B-01','-');
