<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/user_repo.php';
require_login();

$pdo  = get_pdo();
$me   = current_user();
$myId = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'profile') {
        $username    = trim($_POST['username'] ?? '');
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');

        $errors = [];
        if (!is_valid_username($username)) {
            $errors[] = 'Username tidak valid. Gunakan huruf/angka/titik/underscore, 3-50 karakter, tanpa spasi.';
        } elseif (user_username_exists($pdo, $username, $myId)) {
            $errors[] = "Username \"$username\" sudah dipakai user lain. Username wajib unik.";
        }
        if ($namaLengkap === '') {
            $errors[] = 'Nama Lengkap wajib diisi.';
        } elseif (mb_strlen($namaLengkap) > 100) {
            $errors[] = 'Nama Lengkap maksimal 100 karakter.';
        }

        if ($errors) {
            foreach ($errors as $err) flash_set('error', $err);
        } else {
            user_update_profile($pdo, $myId, $username, $namaLengkap);
            // Sinkronkan session supaya navbar & sapaan langsung update tanpa perlu login ulang
            $_SESSION['user']['username']     = $username;
            $_SESSION['user']['nama_lengkap'] = $namaLengkap;
            flash_set('success', 'Username & nama berhasil diperbarui.');
        }
        redirect('modules/settings/profile.php');
    }

    if ($formAction === 'password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $newPasswordConf = (string)($_POST['new_password_confirm'] ?? '');

        // Ambil ulang langsung dari DB - session hasil login TIDAK menyimpan password_hash.
        $fresh = user_find_by_id($pdo, $myId);

        $errors = [];
        if (!$fresh || !password_verify($currentPassword, $fresh['password_hash'])) {
            $errors[] = 'Password saat ini salah.';
        }
        if (!is_valid_password($newPassword)) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } elseif ($newPassword !== $newPasswordConf) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        }

        if ($errors) {
            foreach ($errors as $err) flash_set('error', $err);
        } else {
            user_update_password($pdo, $myId, $newPassword);
            session_regenerate_id(true);
            flash_set('success', 'Password berhasil diubah.');
        }
        redirect('modules/settings/profile.php');
    }
}

$fresh = user_find_by_id($pdo, $myId) ?? $me;
$pageTitle = 'Profil Saya';
?>
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-person-circle"></i> Ganti Username</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="profile">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control font-monospace"
                   value="<?= e($fresh['username']) ?>" required
                   pattern="[A-Za-z0-9_.]{3,50}"
                   title="Huruf, angka, titik (.), underscore (_). Tanpa spasi. 3-50 karakter.">
            <div class="form-text">Dipakai untuk login. Wajib unik.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= e($fresh['nama_lengkap']) ?>" required maxlength="100">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Username</button>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-key"></i> Ganti Password</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="password">
          <div class="mb-3">
            <label class="form-label">Password Saat Ini</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
            <div class="form-text">Minimal 6 karakter.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="new_password_confirm" class="form-control" required minlength="6" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
