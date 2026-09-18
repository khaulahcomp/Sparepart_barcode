<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/user_repo.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/settings/users.php');
}
csrf_verify();

$pdo    = get_pdo();
$id     = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$me     = current_user();
$isSelf = $id && (int)$id === (int)$me['id'];

$username     = trim($_POST['username'] ?? '');
$namaLengkap  = trim($_POST['nama_lengkap'] ?? '');
$password     = (string)($_POST['password'] ?? '');
$passwordConf = (string)($_POST['password_confirm'] ?? '');
$role         = $_POST['role'] ?? 'staff';

$redirectBack = $id ? "modules/settings/user_form.php?id=$id" : 'modules/settings/user_form.php';

// ---- VALIDASI ----
$errors = [];
if (!is_valid_username($username)) {
    $errors[] = 'Username tidak valid. Gunakan huruf/angka/titik/underscore, 3-50 karakter, tanpa spasi.';
} elseif (user_username_exists($pdo, $username, $id)) {
    $errors[] = "Username \"$username\" sudah dipakai user lain. Username wajib unik.";
}
if ($namaLengkap === '') {
    $errors[] = 'Nama Lengkap wajib diisi.';
} elseif (mb_strlen($namaLengkap) > 100) {
    $errors[] = 'Nama Lengkap maksimal 100 karakter.';
}
if (!in_array($role, ['admin', 'staff'], true)) {
    $errors[] = 'Role tidak valid.';
}

// Password wajib diisi untuk user baru, opsional (reset) kalau edit
if (!$id && $password === '') {
    $errors[] = 'Password wajib diisi untuk user baru.';
}
if ($password !== '') {
    if (!is_valid_password($password)) {
        $errors[] = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordConf) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
}

$current = $id ? user_find_by_id($pdo, $id) : null;
if ($id && !$current) {
    flash_set('error', 'User tidak ditemukan.');
    redirect('modules/settings/users.php');
}

// Safeguard 1: admin tidak bisa mengubah role akun sendiri (cegah kunci-diri-sendiri di tengah sesi)
if ($isSelf && $current && $current['role'] !== $role) {
    $errors[] = 'Tidak bisa mengubah role akun sendiri. Minta admin lain untuk mengubahnya.';
}

// Safeguard 2: kalau user ini admin aktif dan mau diturunkan jadi staff, pastikan masih ada admin aktif lain
if ($id && $current && $current['role'] === 'admin' && (int)$current['is_active'] === 1 && $role !== 'admin') {
    if (user_count_active_admin($pdo, $id) < 1) {
        $errors[] = 'Tidak bisa mengubah role admin aktif terakhir. Minimal harus ada 1 admin aktif di sistem.';
    }
}

if ($errors) {
    foreach ($errors as $err) {
        flash_set('error', $err);
    }
    redirect($redirectBack);
}

if ($id) {
    user_update_profile($pdo, $id, $username, $namaLengkap);
    if (!$isSelf) {
        user_update_role($pdo, $id, $role);
    }
    if ($password !== '') {
        user_update_password($pdo, $id, $password);
    }
    if ($isSelf) {
        // Sinkronkan session supaya navbar & sapaan langsung update tanpa perlu login ulang
        $_SESSION['user']['username']     = $username;
        $_SESSION['user']['nama_lengkap'] = $namaLengkap;
    }
    flash_set('success', 'User "' . $username . '" berhasil diperbarui.');
} else {
    user_create($pdo, $username, $password, $namaLengkap, $role);
    flash_set('success', 'User "' . $username . '" berhasil ditambahkan.');
}

redirect('modules/settings/users.php');
