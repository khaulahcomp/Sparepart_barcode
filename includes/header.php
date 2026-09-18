<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
$user = current_user();
$appNameDisplay = app_name(get_pdo());
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appNameDisplay) ?><?= isset($pageTitle) ? ' - ' . e($pageTitle) : '' ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/custom.css') ?>" rel="stylesheet">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= base_url('modules/sparepart/list.php') ?>">
      <i class="bi bi-upc-scan"></i> <?= e($appNameDisplay) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/sparepart/list.php') ?>"><i class="bi bi-grid-3x3-gap"></i> Katalog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/sparepart/form.php') ?>"><i class="bi bi-plus-circle"></i> Tambah Sparepart</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/scan/scan.php') ?>"><i class="bi bi-upc"></i> Scan Barcode</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/barcode/bulk_print.php') ?>"><i class="bi bi-printer"></i> Cetak Massal</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/barcode/history.php') ?>"><i class="bi bi-clock-history"></i> Riwayat Cetak</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('modules/excel/import.php') ?>"><i class="bi bi-file-earmark-excel"></i> Import/Export</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-gear"></i> Pengaturan</a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('modules/settings/profile.php') ?>"><i class="bi bi-person-circle"></i> Profil Saya (Username &amp; Password)</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= base_url('modules/settings/users.php') ?>"><i class="bi bi-people"></i> Kelola Pengguna</a></li>
            <li><a class="dropdown-item" href="<?= base_url('modules/settings/general.php') ?>"><i class="bi bi-sliders"></i> Pengaturan Umum</a></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
      <span class="navbar-text text-light me-3">
        <i class="bi bi-person-circle"></i> <?= e($user['nama_lengkap']) ?> (<?= e($user['role']) ?>)
      </span>
      <a href="<?= base_url('modules/auth/logout.php') ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container-fluid py-3">
<?= flash_render() ?>
