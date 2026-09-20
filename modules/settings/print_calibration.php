<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_role(['admin']);

$pdo = get_pdo();

/**
 * BUGFIX: margin grid label 32x19mm (84 label/lembar) dulu di-hardcode di
 * assets/css/custom.css (--m-top: 15.5mm; --m-left: 9mm;) berdasarkan
 * ASUMSI kertas stiker punya margin simetris hasil hitungan matematis
 * (210mm - 6*32mm)/2 = 9mm, (297mm - 14*19mm)/2 = 15.5mm.
 *
 * Bug: tidak semua kertas stiker fisik dicetak pabrik dengan margin
 * simetris itu. Kalau kertas Anda label-nya sudah mepet sampai ke
 * ujung atas/bawah/kiri/kanan kertas (tanpa jarak sama sekali), maka
 * margin 15.5mm/9mm bawaan sistem membuat hasil cetak bergeser dan
 * barcode jatuh di celah antar-stiker, bukan di atas stikernya.
 *
 * Perbaikannya: 4 nilai ini sekarang disimpan di tabel settings (bukan
 * hardcode di CSS) dan bisa diubah dari halaman ini kapan pun kertas
 * stiker yang dipakai berganti batch/merk, tanpa perlu edit source code.
 */
const PC_DEFAULTS = [
    'print_cal_32x19_margin_top'  => '0',
    'print_cal_32x19_margin_left' => '0',
    'print_cal_32x19_gap_x'       => '0',
    'print_cal_32x19_gap_y'       => '0',
];

function pc_clean_mm(string $raw, float $min = 0, float $max = 40): string
{
    $v = (float) str_replace(',', '.', trim($raw));
    if (!is_finite($v)) $v = 0.0;
    $v = max($min, min($max, $v));
    return number_format($v, 2, '.', '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['form_action'] ?? '') === 'reset') {
        foreach (PC_DEFAULTS as $key => $default) {
            setting_set($pdo, $key, $default);
        }
        flash_set('success', 'Kalibrasi dikembalikan ke default pabrik sistem (0mm, mepet penuh — cocok untuk kertas stiker 32x19mm yang tanpa jarak dari tepi kertas).');
    } else {
        $values = [
            'print_cal_32x19_margin_top'  => pc_clean_mm($_POST['margin_top'] ?? '0'),
            'print_cal_32x19_margin_left' => pc_clean_mm($_POST['margin_left'] ?? '0'),
            'print_cal_32x19_gap_x'       => pc_clean_mm($_POST['gap_x'] ?? '0', 0, 10),
            'print_cal_32x19_gap_y'       => pc_clean_mm($_POST['gap_y'] ?? '0', 0, 10),
        ];
        foreach ($values as $key => $val) {
            setting_set($pdo, $key, $val);
        }
        flash_set('success', 'Kalibrasi cetak berhasil disimpan. Cetak 1 lembar percobaan untuk memastikan barcode pas di atas label.');
    }
    redirect('modules/settings/print_calibration.php');
}

$current = [];
foreach (PC_DEFAULTS as $key => $default) {
    $current[$key] = setting_get($pdo, $key, $default);
}

$pageTitle = 'Kalibrasi Cetak Label';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-rulers"></i> Kalibrasi Cetak Label 32x19mm (84 label/lembar A4)</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small">
          Nilai di bawah ini menentukan jarak grid barcode dari tepi kertas A4 saat mencetak
          menggunakan ukuran label <strong>32x19mm — 84 label/lembar</strong>. Jika kertas stiker
          fisik yang Anda pakai memang <strong>tanpa jarak dari ujung atas/bawah/kiri/kanan kertas</strong>,
          isi semua kolom dengan <code>0</code> (ini juga nilai default sistem sekarang).
          Jika hasil cetak masih meleset beberapa mm ke satu arah, sesuaikan angkanya lalu cetak
          ulang 1 lembar percobaan &mdash; tidak perlu edit kode program.
        </p>
        <div class="alert alert-warning small">
          <i class="bi bi-exclamation-triangle"></i>
          Pastikan juga di kotak dialog print browser, opsi <strong>Margins</strong> diset ke
          <strong>None / Tanpa Batas</strong> dan <strong>Scale</strong> diset ke
          <strong>100% / Ukuran Asli</strong> (bukan &ldquo;Fit to page&rdquo;). Kalau opsi Margins
          browser tidak di-set None, browser bisa menambahkan margin sendiri di luar kendali
          sistem ini, walaupun kalibrasi di sini sudah 0.
        </div>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="save">
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label">Margin Atas (mm)</label>
              <input type="text" name="margin_top" class="form-control" value="<?= e($current['print_cal_32x19_margin_top']) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Margin Kiri (mm)</label>
              <input type="text" name="margin_left" class="form-control" value="<?= e($current['print_cal_32x19_margin_left']) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Jarak Antar Kolom (mm)</label>
              <input type="text" name="gap_x" class="form-control" value="<?= e($current['print_cal_32x19_gap_x']) ?>" required>
              <div class="form-text">Isi &gt; 0 hanya jika stiker fisik punya celah antar kolom.</div>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Jarak Antar Baris (mm)</label>
              <input type="text" name="gap_y" class="form-control" value="<?= e($current['print_cal_32x19_gap_y']) ?>" required>
              <div class="form-text">Isi &gt; 0 hanya jika stiker fisik punya celah antar baris.</div>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Kalibrasi</button>
            <a href="<?= base_url('modules/barcode/bulk_print.php') ?>" class="btn btn-outline-success"><i class="bi bi-printer"></i> Coba Cetak Sekarang</a>
          </div>
        </form>
        <form method="post" class="mt-2" onsubmit="return confirm('Kembalikan kalibrasi ke default sistem (0mm semua)?');">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="reset">
          <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset ke Default (0mm)</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
