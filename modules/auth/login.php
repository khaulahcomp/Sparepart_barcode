<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (current_user()) {
    redirect('modules/sparepart/list.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $pdo = get_pdo();
    $user = attempt_login($pdo, $username, $password);
    if ($user) {
        $_SESSION['user'] = $user;
        session_regenerate_id(true);
        redirect('modules/sparepart/list.php');
    } else {
        $error = 'Username atau password salah.';
    }
}
$pageTitle = 'Login';
include __DIR__ . '/../../includes/header.php';
?>
<div class="row justify-content-center mt-5">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-1 text-center"><i class="bi bi-upc-scan"></i> <?= e(APP_NAME) ?></h4>
        <p class="text-muted text-center mb-4">Silakan login untuk melanjutkan</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-muted small text-center mt-3 mb-0">Default: admin / admin123</p>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
