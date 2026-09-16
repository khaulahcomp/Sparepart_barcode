<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/sparepart/list.php');
}
csrf_verify();

$pdo = get_pdo();
$id  = !empty($_POST['id']) ? (int)$_POST['id'] : null;

$kode = trim($_POST['kode_custom'] ?? '');
$nama = trim($_POST['nama_sparepart'] ?? '');

// ---- VALIDASI ----
$errors = [];
if (!is_valid_kode_custom($kode)) {
    $errors[] = 'Kode Custom tidak valid. Gunakan huruf/angka/strip/underscore, 2-50 karakter, tanpa spasi.';
}
if ($nama === '') {
    $errors[] = 'Nama Sparepart wajib diisi.';
}
if ($kode !== '' && sparepart_kode_exists($pdo, $kode, $id)) {
    $errors[] = "Kode Custom \"$kode\" sudah digunakan sparepart lain. Kode wajib unik.";
}
$hargaBeli = (float)($_POST['harga_beli'] ?? 0);
$hargaJual = (float)($_POST['harga_jual'] ?? 0);
$stok      = (int)($_POST['stok'] ?? 0);
if ($hargaBeli < 0 || $hargaJual < 0 || $stok < 0) {
    $errors[] = 'Harga dan stok tidak boleh bernilai negatif.';
}

if ($errors) {
    foreach ($errors as $err) {
        flash_set('error', $err);
    }
    redirect($id ? "modules/sparepart/form.php?id=$id" : 'modules/sparepart/form.php');
}

// ---- UPLOAD FOTO (opsional) ----
$fotoName = null;
try {
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fotoName = handle_foto_upload($_FILES['foto'], $kode);
    }
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    redirect($id ? "modules/sparepart/form.php?id=$id" : 'modules/sparepart/form.php');
}

$data = [
    'kode_custom'    => $kode,
    'nama_sparepart' => $nama,
    'merk'           => trim($_POST['merk'] ?? '') ?: null,
    'jenis_motor'    => trim($_POST['jenis_motor'] ?? '') ?: null,
    'kategori'       => trim($_POST['kategori'] ?? '') ?: null,
    'satuan'         => trim($_POST['satuan'] ?? '') ?: 'PCS',
    'harga_beli'     => $hargaBeli,
    'harga_jual'     => $hargaJual,
    'stok'           => $stok,
    'lokasi_rak'     => trim($_POST['lokasi_rak'] ?? '') ?: null,
    'keterangan'     => trim($_POST['keterangan'] ?? '') ?: null,
    'foto'           => $fotoName,
];

if ($id) {
    sparepart_update($pdo, $id, $data);
    flash_set('success', 'Sparepart berhasil diperbarui.');
    redirect("modules/sparepart/detail.php?id=$id");
} else {
    $newId = sparepart_create($pdo, $data);
    flash_set('success', 'Sparepart baru berhasil ditambahkan.');
    redirect("modules/sparepart/detail.php?id=$newId");
}
