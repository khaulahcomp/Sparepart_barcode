<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';
require_login();

$pdo = get_pdo();

// Ambil semua data sparepart (atau sesuai filter di URL)
$q          = trim($_GET['q'] ?? '');
$merk       = trim($_GET['merk'] ?? '');
$jenisMotor = trim($_GET['jenis_motor'] ?? '');
$kategori   = trim($_GET['kategori'] ?? '');
$stokFilter = trim($_GET['stok_filter'] ?? '');

$result = sparepart_list_all($pdo, [
    'q' => $q, 'merk' => $merk, 'jenis_motor' => $jenisMotor,
    'kategori' => $kategori, 'stok_filter' => $stokFilter,
]);

// Siapkan baris export: header + data
$rows = [
    ['Kode Custom','Nama Sparepart','Merk','Jenis Motor','Kategori','Satuan','Harga Beli','Harga Jual','Stok','Lokasi Rak','Keterangan'],
];

foreach ($result as $sp) {
    $rows[] = [
        $sp['kode_custom'],
        $sp['nama_sparepart'],
        $sp['merk'] ?? '',
        $sp['jenis_motor'] ?? '',
        $sp['kategori'] ?? '',
        $sp['satuan'] ?? 'PCS',
        $sp['harga_beli'],
        $sp['harga_jual'],
        (int)$sp['stok'],
        $sp['lokasi_rak'] ?? '',
        $sp['keterangan'] ?? '',
    ];
}

// Buat file sementara
$tmpPath = tempnam(sys_get_temp_dir(), 'exp') . '.xlsx';
XlsxLite::write($rows, $tmpPath, 'Sparepart');

// Download
$fileName = 'export_sparepart_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
header('Content-Length: ' . filesize($tmpPath));
readfile($tmpPath);
unlink($tmpPath);
exit;
