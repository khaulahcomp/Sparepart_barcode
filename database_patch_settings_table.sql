-- =========================================================
-- PATCH: tabel `settings` (key-value store)
-- Dipakai oleh: Pengaturan Umum (nama aplikasi) & Cetak Label A4 (kalibrasi grid).
-- Aman dijalankan di database yang SUDAH terinstall & sedang berjalan:
-- hanya menambah 1 tabel baru, tidak menyentuh tabel/data yang sudah ada.
--
-- Cara pakai: cPanel -> phpMyAdmin -> pilih database -> tab SQL -> paste isi file ini -> Go.
-- (Tidak perlu dijalankan lagi jika sudah pernah import database.sql versi terbaru,
--  karena tabel ini juga sudah ditambahkan di sana.)
-- =========================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
