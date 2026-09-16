<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';

header('Content-Type: application/json');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['found' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo  = get_pdo();
$kode = trim($_GET['kode'] ?? '');

if ($kode === '') {
    echo json_encode(['found' => false, 'can_add' => false]);
    exit;
}

$sp = sparepart_find_by_kode($pdo, $kode);

if ($sp) {
    echo json_encode([
        'found' => true,
        'sparepart' => [
            'kode_custom'    => $sp['kode_custom'],
            'nama_sparepart' => $sp['nama_sparepart'],
            'merk'           => $sp['merk'],
            'jenis_motor'    => $sp['jenis_motor'],
            'harga_jual_fmt' => rupiah($sp['harga_jual']),
            'stok'           => (int)$sp['stok'],
            'lokasi_rak'     => $sp['lokasi_rak'],
            'foto_url'       => foto_url($sp['foto']),
            'detail_url'     => base_url('modules/sparepart/detail.php?id=' . $sp['id']),
        ],
    ]);
} else {
    $user = current_user();
    $canAdd = in_array($user['role'], ['admin', 'staff'], true);
    echo json_encode([
        'found' => false,
        'can_add' => $canAdd,
        'add_url' => $canAdd ? base_url('modules/sparepart/form.php?prefill_kode=' . rawurlencode($kode)) : null,
    ]);
}
