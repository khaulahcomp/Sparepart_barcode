<?php
/**
 * KONFIGURASI DATABASE
 * Isi sesuai data database MySQL di cPanel Anda.
 * Biasanya nama database & user diberi prefix seperti: cpaneluser_namadb
 */
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'sparepart_barcode');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Nama aplikasi (tampil di header)
define('APP_NAME', 'Sparepart Barcode Manager');

// Base URL aplikasi TANPA trailing slash.
// Contoh: https://sparepart.tokosayaraya.com
// Kosongkan '' untuk deteksi otomatis (biasanya sudah cukup).
define('APP_BASE_URL', '');

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Periksa config/database.php. (' . htmlspecialchars($e->getMessage()) . ')');
        }
    }
    return $pdo;
}
