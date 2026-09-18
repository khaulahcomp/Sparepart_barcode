<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_once __DIR__ . '/../../includes/barcode128.php';
require_login();

$pdo = get_pdo();
$q = trim($_GET['q'] ?? '');

/**
 * Kalibrasi grid untuk lembar stiker A4 pre-cut (mis. label barcode 32x19mm
 * yang dijual di marketplace). Nilai default di bawah adalah TEBAKAN AWAL saja
 * (6 kolom x 15 baris, tanpa jarak antar label) — WAJIB dicek dulu dengan
 * "Cetak Grid Uji" di kertas HVS biasa, ukur pakai penggaris, lalu sesuaikan
 * angkanya sebelum cetak ke lembar stiker asli. Nilai tersimpan otomatis
 * untuk pencetakan berikutnya.
 */
$calibDefaults = [
    'cols'        => 6,
    'rows'        => 15,
    'label_w'     => 32,
    'label_h'     => 19,
    'margin_top'  => 6,
    'margin_left' => 9,
    'gap_x'       => 0,
    'gap_y'       => 0,
];

function label_a4_calib_get(PDO $pdo, array $defaults): array
{
    $out = [];
    foreach ($defaults as $key => $def) {
        $raw = setting_get($pdo, 'label_a4_' . $key, (string)$def);
        $out[$key] = is_numeric($raw) ? (float)$raw : (float)$def;
    }
    return $out;
}

$didPrint   = false;
$testGrid   = false;
$printItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'print';

    foreach ($calibDefaults as $key => $def) {
        $val = $_POST['calib_' . $key] ?? $def;
        $val = is_numeric($val) ? (float)$val : (float)$def;
        if ($key === 'cols' || $key === 'rows') {
            $val = max(1, (int)$val);
        } else {
            $val = max(0, $val);
        }
        setting_set($pdo, 'label_a4_' . $key, (string)$val);
    }
    $calib = label_a4_calib_get($pdo, $calibDefaults);

    if ($action === 'test_grid') {
        $testGrid = true;
        $didPrint = true;
    } else {
        $ids     = $_POST['sparepart_id'] ?? [];
        $jumlahs = $_POST['jumlah'] ?? [];
        $user    = current_user();

        foreach ($ids as $i => $spId) {
            $spId = (int)$spId;
            $jml  = max(0, (int)($jumlahs[$i] ?? 0));
            if ($spId <= 0 || $jml <= 0) continue;
            $sp = sparepart_find_by_id($pdo, $spId);
            if (!$sp) continue;
            $jml = min($jml, 2000);
            sparepart_print_history_add($pdo, $sp['id'], $sp['kode_custom'], $sp['nama_sparepart'], $jml, $user['nama_lengkap']);
            $printItems[] = ['sp' => $sp, 'jumlah' => $jml];
        }

        if ($printItems) {
            $didPrint = true;
        } else {
            flash_set('error', 'Pilih minimal satu sparepart dan isi jumlah cetak.');
        }
    }
} else {
    $calib = label_a4_calib_get($pdo, $calibDefaults);
}

$list = [];
if (!$didPrint) {
    $result = sparepart_list($pdo, ['q' => $q, 'per_page' => 100]);
    $list = $result['data'];
}

$pageTitle = 'Cetak Label A4 (Stiker Sheet)';
?>

<?php if (!$didPrint): ?>
<div class="mb-3 no-print">
  <a href="<?= base_url('modules/sparepart/list.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<h4><i class="bi bi-grid-3x2"></i> Cetak Label A4 (Lembar Stiker Pre-Cut)</h4>
<p class="text-muted">
  Untuk mencetak barcode langsung ke lembar stiker A4 (mis. label 32x19mm, HVS doff, yang sudah ada garis potongnya)
  memakai printer biasa (contoh: Epson L-series). Posisi tiap label mengikuti kalibrasi di bawah, jadi harus dicek dulu
  sebelum cetak ke lembar stiker asli.
</p>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h6><i class="bi bi-rulers"></i> Kalibrasi Grid Label</h6>
    <p class="text-muted small mb-3">
      Nilai di bawah adalah perkiraan awal untuk lembar 32x19mm. Karena ukuran/jarak persis bisa berbeda tergantung
      merek stiker yang Anda beli, <strong>klik "Cetak Grid Uji" dulu</strong> di kertas HVS biasa (bukan stiker),
      tumpuk hasilnya di atas lembar stiker asli sambil diterawang ke cahaya untuk mengecek posisi, lalu sesuaikan
      angka di bawah sampai pas. Nilai otomatis tersimpan untuk cetak berikutnya.
    </p>
    <form method="post" id="formLabelA4">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <label class="form-label small">Jumlah Kolom</label>
          <input type="number" name="calib_cols" class="form-control form-control-sm" min="1" step="1" value="<?= e((string)(int)$calib['cols']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Jumlah Baris</label>
          <input type="number" name="calib_rows" class="form-control form-control-sm" min="1" step="1" value="<?= e((string)(int)$calib['rows']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Lebar Label (mm)</label>
          <input type="number" name="calib_label_w" class="form-control form-control-sm" min="1" step="0.1" value="<?= e((string)$calib['label_w']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Tinggi Label (mm)</label>
          <input type="number" name="calib_label_h" class="form-control form-control-sm" min="1" step="0.1" value="<?= e((string)$calib['label_h']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Margin Atas (mm)</label>
          <input type="number" name="calib_margin_top" class="form-control form-control-sm" min="0" step="0.1" value="<?= e((string)$calib['margin_top']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Margin Kiri (mm)</label>
          <input type="number" name="calib_margin_left" class="form-control form-control-sm" min="0" step="0.1" value="<?= e((string)$calib['margin_left']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Jarak Antar Kolom (mm)</label>
          <input type="number" name="calib_gap_x" class="form-control form-control-sm" min="0" step="0.1" value="<?= e((string)$calib['gap_x']) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small">Jarak Antar Baris (mm)</label>
          <input type="number" name="calib_gap_y" class="form-control form-control-sm" min="0" step="0.1" value="<?= e((string)$calib['gap_y']) ?>">
        </div>
      </div>
      <div class="mt-3 d-flex gap-2 flex-wrap">
        <button type="submit" name="action" value="test_grid" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-printer"></i> Simpan &amp; Cetak Grid Uji (kertas biasa)
        </button>
      </div>
      <p class="text-muted small mt-2 mb-0">
        Saat mencetak, di dialog print browser pilih ukuran kertas <strong>A4</strong> dan margin <strong>Tanpa margin / None</strong>
        agar posisi label paling akurat.
      </p>

      <hr class="my-4">

      <h6><i class="bi bi-upc-scan"></i> Pilih Sparepart &amp; Jumlah</h6>
      <div class="mb-3" style="max-width:400px;">
        <div class="input-group">
          <input type="text" class="form-control" id="searchLocal" placeholder="Cari di daftar di bawah...">
        </div>
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
                <td><input type="number" name="jumlah[]" min="0" max="2000" class="form-control form-control-sm" placeholder="0"></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($list)): ?>
              <tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <button type="submit" name="action" value="print" class="btn btn-success btn-lg">
        <i class="bi bi-printer"></i> CETAK LABEL A4
      </button>
    </form>
  </div>
</div>

<script>
document.getElementById('searchLocal')?.addEventListener('input', function () {
  var kw = this.value.toLowerCase();
  document.querySelectorAll('#formLabelA4 tbody tr').forEach(function (tr) {
    tr.style.display = tr.textContent.toLowerCase().includes(kw) ? '' : 'none';
  });
});
</script>

<?php else: ?>

<?php
$calib['cols'] = max(1, (int)$calib['cols']);
$calib['rows'] = max(1, (int)$calib['rows']);
$perSheet = $calib['cols'] * $calib['rows'];

$labels = [];
if ($testGrid) {
    for ($i = 1; $i <= $perSheet; $i++) {
        $labels[] = ['test' => true, 'no' => $i];
    }
} else {
    foreach ($printItems as $item) {
        for ($i = 0; $i < $item['jumlah']; $i++) {
            $labels[] = ['sp' => $item['sp']];
        }
    }
}
$sheets = array_chunk($labels, $perSheet);
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h6 class="mb-0">
    <?php if ($testGrid): ?>
      Preview Grid Uji — <?= count($sheets) ? count($sheets[0]) : 0 ?> kotak, cetak di kertas HVS biasa lalu cocokkan ke lembar stiker
    <?php else: ?>
      Preview Cetak Label A4 — <?= count($labels) ?> label / <?= count($sheets) ?> lembar
    <?php endif; ?>
  </h6>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> PRINT</button>
    <a href="<?= base_url('modules/barcode/print_a4_label.php') ?>" class="btn btn-outline-secondary">KEMBALI</a>
  </div>
</div>

<?php foreach ($sheets as $sheet): ?>
  <div class="a4-sheet">
    <?php foreach ($sheet as $idx => $lbl):
        $col  = $idx % $calib['cols'];
        $row  = intdiv($idx, $calib['cols']);
        $left = $calib['margin_left'] + $col * ($calib['label_w'] + $calib['gap_x']);
        $top  = $calib['margin_top']  + $row * ($calib['label_h'] + $calib['gap_y']);
    ?>
      <div class="a4-label" style="left:<?= e((string)$left) ?>mm; top:<?= e((string)$top) ?>mm; width:<?= e((string)$calib['label_w']) ?>mm; height:<?= e((string)$calib['label_h']) ?>mm;">
        <?php if (!empty($lbl['test'])): ?>
          <span class="a4-label__no"><?= (int)$lbl['no'] ?></span>
        <?php else:
            $sp = $lbl['sp']; ?>
          <?= Barcode128::renderSVG($sp['kode_custom'], 2, 40, true, 6) ?>
          <?php if (mb_strlen($sp['nama_sparepart']) <= 24): ?>
            <div class="a4-label__nama"><?= e($sp['nama_sparepart']) ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
