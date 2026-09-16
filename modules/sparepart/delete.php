<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_role(['admin']);

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);

$sp = $id ? sparepart_find_by_id($pdo, $id) : null;

if (!$sp) {
    flash_set('error', 'Sparepart tidak ditemukan.');
    redirect('modules/sparepart/list.php');
}

// 1) Ambil dulu semua nama file foto (foto utama + foto tambahan) SEBELUM baris dihapus
$photoFiles = sparepart_get_all_photo_filenames($pdo, $id);

// 2) Hapus baris dari database secara permanen.
//    barcode_print_history & sparepart_foto_tambahan ikut terhapus otomatis (FK ON DELETE CASCADE)
sparepart_hard_delete($pdo, $id);

// 3) Hapus file foto fisik dari folder uploads/sparepart/
$uploadDir = __DIR__ . '/../../uploads/sparepart/';
$deletedCount = 0;
$failedFiles  = [];
foreach ($photoFiles as $fileName) {
    $path = $uploadDir . basename($fileName); // basename() jaga-jaga terhadap path traversal
    if (is_file($path)) {
        if (@unlink($path)) {
            $deletedCount++;
        } else {
            $failedFiles[] = $fileName;
        }
    }
}

$msg = "Sparepart \"{$sp['nama_sparepart']}\" ({$sp['kode_custom']}) berhasil dihapus permanen dari database";
if ($deletedCount > 0) {
    $msg .= " beserta $deletedCount file foto.";
} else {
    $msg .= '.';
}
flash_set('success', $msg);

if ($failedFiles) {
    flash_set('error', 'Namun ada file foto yang gagal dihapus dari server (mungkin sudah tidak ada / permission folder uploads/ bermasalah): ' . implode(', ', $failedFiles));
}

redirect('modules/sparepart/list.php');
