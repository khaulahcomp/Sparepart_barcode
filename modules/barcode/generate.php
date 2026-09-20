<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_once __DIR__ . '/../../includes/barcode128.php';
require_login();

$pdo = get_pdo();
$id  = (int)($_GET['id'] ?? 0);
$sp  = sparepart_find_by_id($pdo, $id);
if (!$sp) {
    flash_set('error', 'Sparepart tidak ditemukan.');
    redirect('modules/sparepart/list.php');
}

$jumlah    = max(0, (int)($_GET['jumlah'] ?? 0));
$labelSize = $_GET['label_size'] ?? '40x25';
$autoPrint = !empty($_GET['auto_print']);
$maxCetakSekali = 500; // batas wajar sekali generate agar browser tidak berat

// Lebar area cetak barcode per ukuran label (mm label dikurangi padding kiri-kanan 2mm x2 = 4mm)
$labelContentWidthMM = [
    '30x20' => 30 - 4,
    '40x25' => 40 - 4,
    '50x30' => 50 - 4,
    '32x19' => 32 - 2, // padding sheet 32x19 hanya 1mm per sisi
];
$barcodeTargetWidthMM = $labelContentWidthMM[$labelSize] ?? (40 - 4);
$barcodeFitsLabel = true; // diisi ulang saat render di bawah

$didGenerate = $jumlah > 0;

if ($didGenerate) {
    $jumlah = min($jumlah, $maxCetakSekali);
    // Simpan riwayat cetak (tidak mengubah stok)
    $user = current_user();
    sparepart_print_history_add($pdo, $sp['id'], $sp['kode_custom'], $sp['nama_sparepart'], $jumlah, $user['nama_lengkap']);
}

$pageTitle = 'Generator Barcode';
?>
<div class="mb-3 no-print">
  <a href="<?= base_url('modules/sparepart/detail.php?id=' . $id) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card shadow-sm no-print mb-4">
  <div class="card-body">
    <h5><i class="bi bi-upc"></i> Generator Barcode Sparepart Lokal</h5>
    <div class="row align-items-end g-3 mt-1">
      <div class="col-md-4">
        <label class="form-label">Sparepart</label>
        <div class="form-control-plaintext">
          <span class="sp-card__kode"><?= e($sp['kode_custom']) ?></span><br>
          <strong><?= e($sp['nama_sparepart']) ?></strong>
        </div>
      </div>
      <form method="get" class="row g-2 col-md-8">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="col-md-4">
          <label class="form-label">Jumlah Cetak</label>
          <div class="btn-group w-100 mb-2 flex-wrap" role="group">
            <?php foreach ([1,5,10,20,50] as $qOpt): ?>
              <a href="?id=<?= $id ?>&jumlah=<?= $qOpt ?>&label_size=<?= e($labelSize) ?>" class="btn btn-sm btn-outline-dark"><?= $qOpt ?></a>
            <?php endforeach; ?>
          </div>
          <input type="number" name="jumlah" min="1" max="<?= $maxCetakSekali ?>" class="form-control" placeholder="Jumlah custom" value="<?= $jumlah ?: '' ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Ukuran Label</label>
          <select name="label_size" class="form-select">
            <option value="30x20" <?= $labelSize === '30x20' ? 'selected' : '' ?>>30 x 20 mm</option>
            <option value="40x25" <?= $labelSize === '40x25' ? 'selected' : '' ?>>40 x 25 mm</option>
            <option value="50x30" <?= $labelSize === '50x30' ? 'selected' : '' ?>>50 x 30 mm</option>
            <option value="32x19" <?= $labelSize === '32x19' ? 'selected' : '' ?>>32 x 19 mm — Stiker HVS Doff A4 (84 label/lembar)</option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-magic"></i> Generate Barcode</button>
        </div>
      </form>
    </div>
    <p class="text-muted small mt-2 mb-0">Maks. <?= $maxCetakSekali ?> label sekali generate agar browser tetap ringan. Untuk jumlah lebih besar, generate bertahap.</p>
  </div>
</div>

<?php if ($didGenerate): ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h6 class="mb-0">Preview: <?= $jumlah ?> label — <?= e($sp['kode_custom']) ?></h6>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> PRINT BARCODE</button>
    <a href="<?= base_url('modules/sparepart/detail.php?id=' . $id) ?>" class="btn btn-outline-secondary">KEMBALI</a>
  </div>
</div>


<?php if ($labelSize === '32x19'): ?>
<p class="text-muted small no-print mb-2"><i class="bi bi-info-circle"></i> Mode presisi 84 label/lembar A4 aktif — gunakan kertas stiker HVS Doff A4 32x19mm (mis. BLUEPRINT GL-A44) dan pastikan pengaturan printer "Scale: 100% / Actual size" (jangan "Fit to page") saat mencetak.</p>
  <?php if ($jumlah > 84): ?>
  <p class="text-warning small no-print mb-2"><i class="bi bi-exclamation-triangle"></i> Jumlah (<?= $jumlah ?>) lebih dari 84 (kapasitas 1 lembar fisik). Grid di lembar ke-2 dst berisiko bergeser saat dicetak lintas-halaman. Disarankan cetak bertahap maks. 84 label per lembar untuk hasil paling presisi.</p>
  <?php endif; ?>
<?php endif; ?>
<div class="print-page">
  <div class="label-sheet<?= $labelSize === '32x19' ? ' sheet-32x19' : '' ?>">
    <?php
    $barcodeResult   = Barcode128::renderSVGFit($sp['kode_custom'], $barcodeTargetWidthMM, 55, true, 10);
    $barcodeFitsLabel = $barcodeResult['fits'];
    for ($i = 0; $i < $jumlah; $i++): ?>
      <div class="barcode-label label-<?= e($labelSize) ?>">
        <?= $barcodeResult['svg'] ?>
        <?php if (strlen($sp['nama_sparepart']) <= 28): ?>
          <div class="lbl-nama"><?= e($sp['nama_sparepart']) ?></div>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
    <?php if (!$barcodeFitsLabel): ?>
    <p class="text-warning small no-print mb-2 mt-2"><i class="bi bi-exclamation-triangle"></i> Kode <code><?= e($sp['kode_custom']) ?></code> cukup panjang sehingga barcode sedikit melebihi kotak label <?= e($labelSize) ?> mm agar tetap bisa dipindai scanner. Untuk hasil paling rapi, pilih ukuran label yang lebih besar.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($autoPrint): ?>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
