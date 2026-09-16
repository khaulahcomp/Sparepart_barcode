<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';
require_login();

$rows = [
    ['Kode Custom','Nama Sparepart','Merk','Jenis Motor','Kategori','Satuan','Harga Beli','Harga Jual','Stok','Lokasi Rak','Keterangan'],
    ['JL-BEAT-KR-001','Kampas Rem Depan Beat','JASINDO','Honda Beat','Kampas Rem','PCS',25000,35000,20,'A-01','-'],
];

$tmpPath = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
XlsxLite::write($rows, $tmpPath, 'Template');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template_import_sparepart.xlsx"');
header('Content-Length: ' . filesize($tmpPath));
readfile($tmpPath);
unlink($tmpPath);
exit;
