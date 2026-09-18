<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/user_repo.php';
require_role(['admin']);

$pdo   = get_pdo();
$users = user_list($pdo);
$me    = current_user();
$pageTitle = 'Kelola Pengguna';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-people"></i> Kelola Pengguna</h4>
  <a href="<?= base_url('modules/settings/user_form.php') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Tambah User</a>
</div>
<p class="text-muted">Akun yang dinonaktifkan langsung tidak bisa login lagi, tapi riwayat cetak & data lain tetap tersimpan (tidak dihapus).</p>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr>
        <th>Username</th>
        <th>Nama Lengkap</th>
        <th>Role</th>
        <th>Status</th>
        <th>Dibuat</th>
        <th style="width:230px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): $isSelf = (int)$u['id'] === (int)$me['id']; ?>
        <tr>
          <td>
            <span class="sp-card__kode"><?= e($u['username']) ?></span>
            <?php if ($isSelf): ?><span class="badge bg-secondary">Anda</span><?php endif; ?>
          </td>
          <td><?= e($u['nama_lengkap']) ?></td>
          <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : 'info' ?>"><?= e($u['role']) ?></span></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge bg-success">Aktif</span>
            <?php else: ?>
              <span class="badge bg-secondary">Nonaktif</span>
            <?php endif; ?>
          </td>
          <td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
          <td class="d-flex gap-1 flex-wrap">
            <a href="<?= base_url('modules/settings/user_form.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
            <?php if (!$isSelf): ?>
              <a href="<?= base_url('modules/settings/user_toggle_active.php?id=' . $u['id']) ?>"
                 class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?> btn-confirm-delete"
                 data-confirm-msg="<?= $u['is_active'] ? 'Nonaktifkan akun ' . e($u['username']) . '? User ini tidak akan bisa login lagi sampai diaktifkan kembali.' : 'Aktifkan kembali akun ' . e($u['username']) . '?' ?>">
                <i class="bi bi-<?= $u['is_active'] ? 'slash-circle' : 'check-circle' ?>"></i> <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
              </a>
            <?php else: ?>
              <span class="text-muted small align-self-center">Tidak bisa ubah status sendiri</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
