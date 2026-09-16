<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_role(['admin']);

$pdo = get_pdo();
$id = (int)($_GET['id'] ?? 0);
if ($id && sparepart_find_by_id($pdo, $id)) {
    sparepart_soft_delete($pdo, $id);
    flash_set('success', 'Sparepart berhasil dihapus.');
} else {
    flash_set('error', 'Sparepart tidak ditemukan.');
}
redirect('modules/sparepart/list.php');
