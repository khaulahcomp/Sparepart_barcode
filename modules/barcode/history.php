<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();

$pdo = get_pdo();
$history = sparepart_print_history_list($pdo, null, 300);
$pageTitle = 'Riwayat Cetak Barcode';
?>
<h4><i class="bi bi-clock-history"></i> Riwayat Cetak Barcode</h4>
<div class="table-responsive mt-3">
  <table class="table table-striped bg-white">
    <thead><tr><th>Tanggal</th><th>Kode Custom</th><th>Nama Sparepart</th><th>Jumlah</th><th>User</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($history as $h): ?>
        <tr>
          <td><?= e(date('d-m-Y H:i', strtotime($h['printed_at']))) ?></td>
          <td><span class="sp-card__kode"><?= e($h['kode_custom']) ?></span></td>
          <td><?= e($h['nama_sparepart_snapshot']) ?></td>
          <td><?= (int)$h['jumlah'] ?></td>
          <td><?= e($h['printed_by']) ?></td>
          <td>
            <a href="<?= base_url('modules/barcode/generate.php?id=' . (int)$h['sparepart_id'] . '&jumlah=' . (int)$h['jumlah'] . '&auto_print=1') ?>"
               class="btn btn-sm btn-outline-secondary">Cetak Ulang</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($history)): ?>
        <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat cetak.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
