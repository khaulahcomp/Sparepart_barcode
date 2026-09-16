<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$sp  = sparepart_find_by_id($pdo, $id);
if (!$sp) {
    flash_set('error', 'Sparepart tidak ditemukan.');
    redirect('modules/sparepart/list.php');
}
$history = sparepart_print_history_list($pdo, $id, 20);
$pageTitle = $sp['nama_sparepart'];
?>
<div class="mb-3">
  <a href="<?= base_url('modules/sparepart/list.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Katalog</a>
</div>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <img src="<?= e(foto_url($sp['foto'])) ?>" class="card-img-top" style="aspect-ratio:1/1;object-fit:cover;" alt="<?= e($sp['nama_sparepart']) ?>">
    </div>
  </div>
  <div class="col-md-7">
    <span class="sp-card__kode fs-6"><?= e($sp['kode_custom']) ?></span>
    <h3 class="mt-2"><?= e($sp['nama_sparepart']) ?></h3>
    <table class="table table-sm">
      <tr><th style="width:160px;">Merk</th><td><?= e($sp['merk'] ?: '-') ?></td></tr>
      <tr><th>Jenis Motor</th><td><?= e($sp['jenis_motor'] ?: '-') ?></td></tr>
      <tr><th>Kategori</th><td><?= e($sp['kategori'] ?: '-') ?></td></tr>
      <tr><th>Satuan</th><td><?= e($sp['satuan']) ?></td></tr>
      <tr><th>Harga Beli</th><td><?= rupiah($sp['harga_beli']) ?></td></tr>
      <tr><th>Harga Jual</th><td class="fw-bold text-danger"><?= rupiah($sp['harga_jual']) ?></td></tr>
      <tr><th>Stok</th><td><?= (int)$sp['stok'] ?> <?= e($sp['satuan']) ?></td></tr>
      <tr><th>Lokasi/Rak</th><td><?= e($sp['lokasi_rak'] ?: '-') ?></td></tr>
      <tr><th>Keterangan</th><td><?= e($sp['keterangan'] ?: '-') ?></td></tr>
    </table>

    <div class="d-flex gap-2 flex-wrap mb-3">
      <a href="<?= base_url('modules/sparepart/form.php?id=' . $id) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
      <?php if (current_user()['role'] === 'admin'): ?>
      <a href="<?= base_url('modules/sparepart/delete.php?id=' . $id) ?>" class="btn btn-outline-danger btn-confirm-delete" data-confirm-msg="Hapus sparepart ini?"><i class="bi bi-trash"></i> Hapus</a>
      <?php endif; ?>
    </div>

    <div class="card card-body bg-light">
      <h6 class="mb-2"><i class="bi bi-upc"></i> Cetak Barcode</h6>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-dark" href="<?= base_url('modules/barcode/generate.php?id=' . $id . '&jumlah=1') ?>">Cetak 1</a>
        <a class="btn btn-dark" href="<?= base_url('modules/barcode/generate.php?id=' . $id . '&jumlah=5') ?>">Cetak 5</a>
        <a class="btn btn-dark" href="<?= base_url('modules/barcode/generate.php?id=' . $id . '&jumlah=10') ?>">Cetak 10</a>
        <a class="btn btn-dark" href="<?= base_url('modules/barcode/generate.php?id=' . $id . '&jumlah=20') ?>">Cetak 20</a>
        <a class="btn btn-outline-dark" href="<?= base_url('modules/barcode/generate.php?id=' . $id) ?>">Cetak Custom...</a>
      </div>
    </div>
  </div>
</div>

<div class="mt-4">
  <h5><i class="bi bi-clock-history"></i> Riwayat Cetak Barcode</h5>
  <div class="table-responsive">
    <table class="table table-striped table-sm bg-white">
      <thead><tr><th>Tanggal</th><th>Jumlah</th><th>User</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat cetak.</td></tr>
        <?php endif; ?>
        <?php foreach ($history as $h): ?>
          <tr>
            <td><?= e(date('d-m-Y H:i', strtotime($h['printed_at']))) ?></td>
            <td><?= (int)$h['jumlah'] ?></td>
            <td><?= e($h['printed_by']) ?></td>
            <td><a href="<?= base_url('modules/barcode/generate.php?id=' . $id . '&jumlah=' . (int)$h['jumlah'] . '&auto_print=1') ?>" class="btn btn-sm btn-outline-secondary">Cetak Ulang</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
