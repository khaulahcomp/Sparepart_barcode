<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_once __DIR__ . '/../../includes/barcode128.php';
require_login();

$pdo = get_pdo();
$q = trim($_GET['q'] ?? '');

$didPrint = false;
$printItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ids = $_POST['sparepart_id'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];
    $labelSize = $_POST['label_size'] ?? '40x25';
    $user = current_user();

    foreach ($ids as $i => $spId) {
        $spId = (int)$spId;
        $jml  = max(0, (int)($jumlahs[$i] ?? 0));
        if ($spId <= 0 || $jml <= 0) continue;
        $sp = sparepart_find_by_id($pdo, $spId);
        if (!$sp) continue;
        $jml = min($jml, 500);
        sparepart_print_history_add($pdo, $sp['id'], $sp['kode_custom'], $sp['nama_sparepart'], $jml, $user['nama_lengkap']);
        $printItems[] = ['sp' => $sp, 'jumlah' => $jml];
    }
    if ($printItems) {
        $didPrint = true;
    } else {
        flash_set('error', 'Pilih minimal satu sparepart dan isi jumlah cetak.');
    }
}

// Lebar area cetak barcode per ukuran label (mm label dikurangi padding kiri-kanan)
$labelContentWidthMM = [
    '30x20' => 30 - 4,
    '40x25' => 40 - 4,
    '50x30' => 50 - 4,
    '32x19' => 32 - 2,
];

// Data untuk form pemilihan (hanya perlu jika belum print)
$list = [];
if (!$didPrint) {
    $result = sparepart_list($pdo, ['q' => $q, 'per_page' => 100]);
    $list = $result['data'];
}
$pageTitle = 'Cetak Massal Barcode';
?>

<?php if (!$didPrint): ?>
<div class="mb-3 no-print">
  <a href="<?= base_url('modules/sparepart/list.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<h4><i class="bi bi-printer"></i> Cetak Massal Barcode (Bulk Print)</h4>
<p class="text-muted">Pilih beberapa sparepart sekaligus, tentukan jumlah cetak masing-masing.</p>

<form method="get" class="mb-3">
  <div class="input-group" style="max-width:400px;">
    <input type="text" name="q" class="form-control" placeholder="Cari sparepart..." value="<?= e($q) ?>">
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
  </div>
</form>

<form method="post">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Ukuran Label</label>
    <select name="label_size" class="form-select" style="max-width:250px;">
      <option value="30x20">30 x 20 mm</option>
      <option value="40x25" selected>40 x 25 mm</option>
      <option value="50x30">50 x 30 mm</option>
      <option value="32x19">32 x 19 mm — Stiker HVS Doff A4 (84 label/lembar)</option>
    </select>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead>
        <tr><th></th><th>Kode</th><th>Nama</th><th>Stok</th><th style="width:140px;">Jumlah Cetak</th></tr>
      </thead>
      <tbody>
        <?php foreach ($list as $sp): ?>
          <tr>
            <td><input type="checkbox" class="form-check-input chk-item" name="sparepart_id[]" value="<?= (int)$sp['id'] ?>"></td>
            <td><span class="sp-card__kode"><?= e($sp['kode_custom']) ?></span></td>
            <td><?= e($sp['nama_sparepart']) ?></td>
            <td><?= (int)$sp['stok'] ?></td>
            <td><input type="number" name="jumlah[]" min="0" max="500" class="form-control form-control-sm" placeholder="0"></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
          <tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-printer"></i> CETAK SEMUA BARCODE</button>
</form>

<?php else: ?>
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h6 class="mb-0">Preview Cetak Massal</h6>
    <div class="d-flex gap-2">
      <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> PRINT BARCODE</button>
      <a href="<?= base_url('modules/barcode/bulk_print.php') ?>" class="btn btn-outline-secondary">KEMBALI</a>
    </div>
  </div>
  <?php if (($labelSize ?? '') === '32x19'):
    $totalLabelSheet = array_sum(array_column($printItems, 'jumlah')); ?>
  <p class="text-muted small no-print mb-2"><i class="bi bi-info-circle"></i> Mode presisi 84 label/lembar A4 aktif — gunakan kertas stiker HVS Doff A4 32x19mm (mis. BLUEPRINT GL-A44) dan pastikan pengaturan printer "Scale: 100% / Actual size" (jangan "Fit to page") saat mencetak.</p>
    <?php if ($totalLabelSheet > 84): ?>
    <p class="text-warning small no-print mb-2"><i class="bi bi-exclamation-triangle"></i> Total (<?= $totalLabelSheet ?>) lebih dari 84 (kapasitas 1 lembar fisik). Grid di lembar ke-2 dst berisiko bergeser saat dicetak lintas-halaman. Disarankan cetak bertahap maks. 84 label per lembar untuk hasil paling presisi.</p>
    <?php endif; ?>
  <?php endif; ?>
  <div class="print-page">
    <div class="label-sheet<?= ($labelSize ?? '') === '32x19' ? ' sheet-32x19' : '' ?>">
      <?php
      $notFittingCodes = [];
      $bulkTargetWidthMM = $labelContentWidthMM[$labelSize ?? '40x25'] ?? (40 - 4);
      foreach ($printItems as $item):
        $sp = $item['sp'];
        $barcodeResult = Barcode128::renderSVGFit($sp['kode_custom'], $bulkTargetWidthMM, 55, true, 10);
        if (!$barcodeResult['fits']) {
            $notFittingCodes[$sp['kode_custom']] = true;
        }
        for ($i = 0; $i < $item['jumlah']; $i++): ?>
        <div class="barcode-label label-<?= e($labelSize ?? '40x25') ?>">
          <?= $barcodeResult['svg'] ?>
          <?php if (strlen($sp['nama_sparepart']) <= 28): ?>
            <div class="lbl-nama"><?= e($sp['nama_sparepart']) ?></div>
          <?php endif; ?>
        </div>
      <?php endfor; endforeach; ?>
      <?php if (!empty($notFittingCodes)): ?>
      <p class="text-warning small no-print mb-2 mt-2"><i class="bi bi-exclamation-triangle"></i> Kode berikut cukup panjang sehingga barcode sedikit melebihi kotak label agar tetap bisa dipindai scanner: <code><?= e(implode(', ', array_keys($notFittingCodes))) ?></code>. Untuk hasil paling rapi, pilih ukuran label yang lebih besar.</p>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
