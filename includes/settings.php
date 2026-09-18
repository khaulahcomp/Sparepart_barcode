<?php
declare(strict_types=1);

/**
 * Ambil satu nilai setting dari database. Fallback ke $default jika:
 * - key belum ada di tabel settings, ATAU
 * - tabel settings belum dibuat sama sekali (migrasi belum dijalankan)
 * Di-cache per request supaya tidak query berulang untuk key yang sama.
 */
function setting_get(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        $cache[$key] = ($val !== false && $val !== null) ? $val : $default;
    } catch (Throwable $e) {
        // Tabel settings belum ada — jangan bikin aplikasi error, pakai default saja.
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function setting_set(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/**
 * Nama aplikasi yang tampil di judul tab & navbar.
 * Fallback ke konstanta APP_NAME (config/database.php) jika belum pernah diset di database.
 */
function app_name(PDO $pdo): string
{
    $fallback = defined('APP_NAME') ? APP_NAME : 'Sparepart Barcode Manager';
    return setting_get($pdo, 'app_name', $fallback);
}
