<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();

$pdo = get_pdo();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$sp  = null;
if ($id) {
    $sp = sparepart_find_by_id($pdo, $id);
    if (!$sp) {
        flash_set('error', 'Sparepart tidak ditemukan.');
        redirect('modules/sparepart/list.php');
    }
}
$prefillKode = trim($_GET['prefill_kode'] ?? '');
$pageTitle = $id ? 'Edit Sparepart' : 'Tambah Sparepart';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-box-seam"></i> <?= e($pageTitle) ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?= base_url('modules/sparepart/save.php') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Kode Custom <span class="text-danger">*</span></label>
              <input type="text" name="kode_custom" class="form-control font-monospace"
                     value="<?= e($sp['kode_custom'] ?? $prefillKode) ?>" required
                     pattern="[A-Za-z0-9_\-]{2,50}"
                     title="Huruf, angka, strip (-), underscore (_). Tanpa spasi. 2-50 karakter."
                     <?= $id ? '' : '' ?>>
              <div class="form-text">Wajib unik. Contoh: <code>JL-BEAT-KR-001</code>. Ini akan menjadi identitas barcode.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Sparepart <span class="text-danger">*</span></label>
              <input type="text" name="nama_sparepart" class="form-control" value="<?= e($sp['nama_sparepart'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Merk</label>
              <input type="text" name="merk" class="form-control" value="<?= e($sp['merk'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Jenis Motor</label>
              <input type="text" name="jenis_motor" class="form-control" value="<?= e($sp['jenis_motor'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kategori</label>
              <input type="text" name="kategori" class="form-control" value="<?= e($sp['kategori'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Satuan</label>
              <input type="text" name="satuan" class="form-control" value="<?= e($sp['satuan'] ?? 'PCS') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Harga Beli</label>
              <input type="number" step="0.01" min="0" name="harga_beli" class="form-control" value="<?= e((string)($sp['harga_beli'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Harga Jual</label>
              <input type="number" step="0.01" min="0" name="harga_jual" class="form-control" value="<?= e((string)($sp['harga_jual'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Stok</label>
              <input type="number" min="0" name="stok" class="form-control" value="<?= e((string)($sp['stok'] ?? '0')) ?>">
              <div class="form-text">Stok bukan jumlah record — tetap 1 baris data ini saja.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Lokasi/Rak</label>
              <input type="text" name="lokasi_rak" class="form-control" value="<?= e($sp['lokasi_rak'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Foto Sparepart</label>
              <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
              <?php if (!empty($sp['foto'])): ?>
                <div class="form-text">Foto saat ini: <?= e($sp['foto']) ?> (upload baru untuk mengganti)</div>
              <?php endif; ?>
            </div>

            <div class="col-12">
              <label class="form-label">Keterangan</label>
              <textarea name="keterangan" class="form-control" rows="2"><?= e($sp['keterangan'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= base_url('modules/sparepart/list.php') ?>" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
