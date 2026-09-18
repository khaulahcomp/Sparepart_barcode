<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/user_repo.php';
require_role(['admin']);

$pdo = get_pdo();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$u   = null;
if ($id) {
    $u = user_find_by_id($pdo, $id);
    if (!$u) {
        flash_set('error', 'User tidak ditemukan.');
        redirect('modules/settings/users.php');
    }
}
$me     = current_user();
$isSelf = $id && (int)$id === (int)$me['id'];
$pageTitle = $id ? 'Edit User' : 'Tambah User';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-person-gear"></i> <?= e($pageTitle) ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?= base_url('modules/settings/user_save.php') ?>">
          <?= csrf_field() ?>
          <?php if ($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control font-monospace"
                     value="<?= e($u['username'] ?? '') ?>" required
                     pattern="[A-Za-z0-9_.]{3,50}"
                     title="Huruf, angka, titik (.), underscore (_). Tanpa spasi. 3-50 karakter.">
              <div class="form-text">Dipakai untuk login. Wajib unik.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" name="nama_lengkap" class="form-control" value="<?= e($u['nama_lengkap'] ?? '') ?>" required maxlength="100">
            </div>

            <div class="col-md-6">
              <label class="form-label"><?= $id ? 'Password Baru' : 'Password' ?> <?= $id ? '' : '<span class="text-danger">*</span>' ?></label>
              <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
              <div class="form-text"><?= $id ? 'Kosongkan jika tidak ingin mengubah password user ini.' : 'Minimal 6 karakter.' ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" name="password_confirm" class="form-control" minlength="6" autocomplete="new-password">
            </div>

            <div class="col-md-6">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                <option value="staff" <?= ($u['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= ($u['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
              <?php if ($isSelf): ?>
                <input type="hidden" name="role" value="<?= e($u['role']) ?>">
                <div class="form-text">Tidak bisa mengubah role akun sendiri (mencegah kunci-diri-sendiri). Minta admin lain untuk mengubahnya.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= base_url('modules/settings/users.php') ?>" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
