<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_role(['admin']);

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $newName = trim($_POST['app_name'] ?? '');

    if ($newName === '') {
        flash_set('error', 'Nama aplikasi tidak boleh kosong.');
    } elseif (mb_strlen($newName) > 100) {
        flash_set('error', 'Nama aplikasi maksimal 100 karakter.');
    } else {
        setting_set($pdo, 'app_name', $newName);
        flash_set('success', 'Nama aplikasi berhasil diperbarui.');
    }
    redirect('modules/settings/general.php');
}

$currentName = app_name($pdo);
$pageTitle = 'Pengaturan Umum';
?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-gear"></i> Pengaturan Umum</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Nama Aplikasi</label>
            <input type="text" name="app_name" class="form-control" value="<?= e($currentName) ?>" maxlength="100" required>
            <div class="form-text">Nama ini akan tampil di judul tab browser dan navbar di seluruh halaman.</div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
