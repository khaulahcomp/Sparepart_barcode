<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/user_repo.php';
require_role(['admin']);

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$me  = current_user();

// Safeguard 1: tidak bisa mengubah status akun sendiri (cegah kunci-diri-sendiri di tengah sesi)
if ($id === (int)$me['id']) {
    flash_set('error', 'Tidak bisa mengubah status akun Anda sendiri.');
    redirect('modules/settings/users.php');
}

$target = user_find_by_id($pdo, $id);
if (!$target) {
    flash_set('error', 'User tidak ditemukan.');
    redirect('modules/settings/users.php');
}

$newActive = !$target['is_active'];

// Safeguard 2: tidak bisa menonaktifkan admin aktif terakhir di sistem
if (!$newActive && $target['role'] === 'admin' && user_count_active_admin($pdo, $id) < 1) {
    flash_set('error', 'Tidak bisa menonaktifkan admin aktif terakhir. Minimal harus ada 1 admin aktif di sistem.');
    redirect('modules/settings/users.php');
}

user_set_active($pdo, $id, $newActive);
flash_set('success', 'User "' . $target['username'] . '" berhasil ' . ($newActive ? 'diaktifkan' : 'dinonaktifkan') . '.');
redirect('modules/settings/users.php');
