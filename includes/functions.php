<?php
declare(strict_types=1);

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

function base_url(string $path = ''): string
{
    if (defined('APP_BASE_URL') && APP_BASE_URL !== '') {
        return rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $root   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    // Naikkan satu level jika file dipanggil dari dalam /modules/xxx/
    $root = preg_replace('#/modules/[a-zA-Z_]+$#', '', $root);
    return $scheme . '://' . $host . $root . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function is_valid_kode_custom(string $kode): bool
{
    // Huruf, angka, strip, underscore. 2-50 karakter. Tidak boleh spasi.
    return (bool) preg_match('/^[A-Za-z0-9_\-]{2,50}$/', $kode);
}

function is_valid_username(string $username): bool
{
    // Huruf, angka, titik, underscore. 3-50 karakter. Tidak boleh spasi.
    return (bool) preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username);
}

function is_valid_password(string $password): bool
{
    // Batas minimal saja (bukan aturan kompleksitas) - cukup untuk tool internal.
    return mb_strlen($password) >= 6;
}

/**
 * Validasi & simpan upload foto sparepart.
 * Mengembalikan nama file relatif (di dalam uploads/sparepart/) atau null jika tidak ada file.
 * Melempar Exception jika file tidak valid.
 */
function handle_foto_upload(array $file, string $kodeCustomForName = ''): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto gagal (kode error: ' . $file['error'] . ')');
    }

    $maxSize = 3 * 1024 * 1024; // 3MB
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Ukuran foto maksimal 3MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
    }

    $ext = $allowed[$mime];
    $baseName = $kodeCustomForName !== ''
        ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $kodeCustomForName)
        : 'sparepart';
    $fileName = $baseName . '_' . uniqid() . '.' . $ext;
    $destDir  = __DIR__ . '/../uploads/sparepart/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }
    $destPath = $destDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Gagal menyimpan file foto ke server.');
    }

    return $fileName;
}

function foto_url(?string $fotoFileName): string
{
    if (!$fotoFileName || !file_exists(__DIR__ . '/../uploads/sparepart/' . $fotoFileName)) {
        return base_url('assets/img/placeholder.png');
    }
    return base_url('uploads/sparepart/' . rawurlencode($fotoFileName));
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'error' ? 'danger' : e($f['type']);
        $html .= '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">'
            . e($f['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Sesi tidak valid (CSRF check gagal). Silakan muat ulang halaman dan coba lagi.');
    }
}
