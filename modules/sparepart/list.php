<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();

$pdo = get_pdo();

// Deteksi request AJAX (live search) — hanya kembalikan fragment HTML hasil pencarian,
// tanpa header/navbar/footer, supaya bisa disuntikkan langsung ke halaman via JavaScript.
$isAjax = (($_GET['ajax'] ?? '') === '1');

$q          = trim($_GET['q'] ?? '');
$merk       = trim($_GET['merk'] ?? '');
$jenisMotor = trim($_GET['jenis_motor'] ?? '');
$kategori   = trim($_GET['kategori'] ?? '');
$stokFilter = trim($_GET['stok_filter'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));

$allowedPerPage = [24, 48, 100, 200, 500, 1000];
$perPage = (int)($_GET['per_page'] ?? 24);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 24;
}

$result = sparepart_list($pdo, [
    'q' => $q, 'merk' => $merk, 'jenis_motor' => $jenisMotor,
    'kategori' => $kategori, 'stok_filter' => $stokFilter,
    'page' => $page, 'per_page' => $perPage,
]);

function build_query(array $override = []): string
{
    $params = array_merge($_GET, $override);
    unset($params['ajax']);
    return http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
}

// ============================================================
// Render fragment "hasil" (jumlah + grid kartu + pagination).
// Fragment inilah yang akan dipasang ke <div id="resultsContainer">,
// baik saat load halaman pertama kali MAUPUN saat live search via AJAX.
// ============================================================
ob_start();
?>
<p class="text-muted small mb-2">Ditemukan <?= (int)$result['total'] ?> sparepart</p>

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
  <?php foreach ($result['data'] as $sp): ?>
    <div class="col">
      <div class="sp-card" onclick="window.location='<?= base_url('modules/sparepart/detail.php?id=' . (int)$sp['id']) ?>'">
        <div class="sp-card__img-wrap">
          <img src="<?= e(foto_url($sp['foto'])) ?>" alt="<?= e($sp['nama_sparepart']) ?>" loading="lazy">
        </div>
        <div class="sp-card__body">
          <span class="sp-card__kode"><?= e($sp['kode_custom']) ?></span>
          <div class="sp-card__nama"><?= e($sp['nama_sparepart']) ?></div>
          <div class="sp-card__meta"><?= e($sp['merk']) ?> &middot; <?= e($sp['jenis_motor']) ?></div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <span class="sp-card__harga"><?= rupiah($sp['harga_jual']) ?></span>
            <span class="badge <?= $sp['stok'] <= 0 ? 'bg-danger' : ($sp['stok'] <= 5 ? 'bg-warning text-dark' : 'bg-success') ?> sp-card__stok-badge">
              Stok: <?= (int)$sp['stok'] ?>
            </span>
          </div>
        </div>
        <div class="sp-card__footer">
          <a href="<?= base_url('modules/barcode/generate.php?id=' . (int)$sp['id']) ?>"
             class="btn btn-sm btn-outline-dark w-100" onclick="event.stopPropagation()">
             <i class="bi bi-upc"></i> Cetak Barcode
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (empty($result['data'])): ?>
    <div class="col-12">
      <div class="text-center text-muted py-5">
        <i class="bi bi-inboxes" style="font-size:3rem;"></i>
        <p class="mt-2">Belum ada sparepart yang cocok. Coba ubah pencarian/filter, atau tambah sparepart baru.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($result['totalPages'] > 1):
  $totalPages = $result['totalPages'];
  $curPage    = $result['page'];
  $windowSize = 2;
  $start = max(1, $curPage - $windowSize);
  $end   = min($totalPages, $curPage + $windowSize);
?>
<nav class="mt-4 no-print">
  <ul class="pagination justify-content-center flex-wrap">
    <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= build_query(['page' => max(1, $curPage - 1)]) ?>">&laquo; Prev</a>
    </li>

    <?php if ($start > 1): ?>
      <li class="page-item"><a class="page-link" href="?<?= build_query(['page' => 1]) ?>">1</a></li>
      <?php if ($start > 2): ?>
        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
      <?php endif; ?>
    <?php endif; ?>

    <?php for ($p = $start; $p <= $end; $p++): ?>
      <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
        <a class="page-link" href="?<?= build_query(['page' => $p]) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
      <?php if ($end < $totalPages - 1): ?>
        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
      <?php endif; ?>
      <li class="page-item"><a class="page-link" href="?<?= build_query(['page' => $totalPages]) ?>"><?= $totalPages ?></a></li>
    <?php endif; ?>

    <li class="page-item <?= $curPage >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= build_query(['page' => min($totalPages, $curPage + 1)]) ?>">Next &raquo;</a>
    </li>
  </ul>
  <p class="text-center text-muted small">Halaman <?= $curPage ?> dari <?= $totalPages ?> (<?= (int)$result['total'] ?> total sparepart)</p>
</nav>
<?php endif; ?>
<?php
$resultsFragment = ob_get_clean();

// Jika ini request AJAX, langsung kirim fragment-nya saja dan berhenti (tanpa layout halaman).
if ($isAjax) {
    header('Content-Type: text/html; charset=UTF-8');
    echo $resultsFragment;
    exit;
}

// ============================================================
// Load penuh (bukan AJAX): render layout lengkap + form filter + hasil.
// ============================================================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/settings.php';

$merkList     = sparepart_distinct_values($pdo, 'merk');
$jenisList    = sparepart_distinct_values($pdo, 'jenis_motor');
$kategoriList = sparepart_distinct_values($pdo, 'kategori');
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Katalog Sparepart Lokal</h4>
  <div class="d-flex gap-2">
    <a id="exportLink" href="<?= base_url('modules/excel/export.php?' . build_query(['page' => null])) ?>" class="btn btn-outline-success">
      <i class="bi bi-file-earmark-excel"></i> Export Excel (sesuai filter)
    </a>
    <a href="<?= base_url('modules/sparepart/form.php') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Sparepart</a>
  </div>
</div>

<form method="get" id="filterForm" class="card card-body mb-3 shadow-sm">
  <div class="row g-2">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
        <input type="text" id="searchInput" name="q" class="form-control" autocomplete="off"
               placeholder="Cari / scan kode / nama sparepart... (hasil otomatis muncul)" value="<?= e($q) ?>">
      </div>
    </div>
    <div class="col-md-2">
      <select name="merk" class="form-select">
        <option value="">Semua Merk</option>
        <?php foreach ($merkList as $m): ?>
          <option value="<?= e($m) ?>" <?= $merk === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="jenis_motor" class="form-select">
        <option value="">Semua Jenis Motor</option>
        <?php foreach ($jenisList as $j): ?>
          <option value="<?= e($j) ?>" <?= $jenisMotor === $j ? 'selected' : '' ?>><?= e($j) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="kategori" class="form-select">
        <option value="">Semua Kategori</option>
        <?php foreach ($kategoriList as $k): ?>
          <option value="<?= e($k) ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= e($k) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <select name="stok_filter" class="form-select">
        <option value="">Semua Stok</option>
        <option value="tersedia" <?= $stokFilter === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
        <option value="menipis" <?= $stokFilter === 'menipis' ? 'selected' : '' ?>>Menipis</option>
        <option value="habis" <?= $stokFilter === 'habis' ? 'selected' : '' ?>>Habis</option>
      </select>
    </div>
    <div class="col-md-1">
      <select name="per_page" class="form-select">
        <?php foreach ($allowedPerPage as $pp): ?>
          <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?>/hal</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
    </div>
  </div>
</form>

<div id="resultsContainer"><?= $resultsFragment ?></div>

<script>
(function () {
  var form             = document.getElementById('filterForm');
  var searchInput      = document.getElementById('searchInput');
  var resultsContainer = document.getElementById('resultsContainer');
  var exportLink       = document.getElementById('exportLink');
  var ajaxUrl          = window.location.pathname; // modules/sparepart/list.php
  var debounceTimer    = null;

  function buildQueryFromForm() {
    var formData = new FormData(form);
    var params = new URLSearchParams();
    formData.forEach(function (value, key) {
      if (value !== '') params.append(key, value);
    });
    params.set('page', '1'); // reset ke halaman 1 tiap kali search/filter berubah
    return params.toString();
  }

  function attachResultLinkHandlers() {
    // Supaya klik nomor halaman (pagination) juga tetap AJAX, tidak reload penuh
    var links = resultsContainer.querySelectorAll('a.page-link');
    links.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var url = new URL(a.href, window.location.href);
        loadResults(url.search.replace(/^\?/, ''));
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }

  function updateExportLink(queryString) {
    if (exportLink) {
      exportLink.href = "<?= base_url('modules/excel/export.php') ?>?" + queryString.replace(/([&?])page=\d*&?/, '$1').replace(/&$/, '');
    }
  }

  function loadResults(queryString, pushState) {
    if (pushState === undefined) pushState = true;
    resultsContainer.style.opacity = '0.5';

    fetch(ajaxUrl + '?' + queryString + '&ajax=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        resultsContainer.innerHTML = html;
        resultsContainer.style.opacity = '1';
        attachResultLinkHandlers();
        updateExportLink(queryString);
        if (pushState) {
          var newUrl = window.location.pathname + '?' + queryString;
          history.pushState({ q: queryString }, '', newUrl);
        }
      })
      .catch(function () {
        resultsContainer.style.opacity = '1';
      });
  }

  // Live search: mengetik ATAU scan barcode langsung mencari otomatis
  // (debounce 350ms supaya tidak fetch di setiap huruf saat mengetik cepat/scan)
  searchInput.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      loadResults(buildQueryFromForm());
    }, 350);
  });

  // Dropdown filter (merk/jenis motor/kategori/stok/jumlah per halaman): langsung cari saat diubah
  form.querySelectorAll('select').forEach(function (sel) {
    sel.addEventListener('change', function () {
      clearTimeout(debounceTimer);
      loadResults(buildQueryFromForm());
    });
  });

  // Submit form (klik tombol cari ATAU tekan Enter setelah scan) tetap via AJAX, tidak reload halaman
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearTimeout(debounceTimer);
    loadResults(buildQueryFromForm());
  });

  // Tombol Back/Forward browser tetap berfungsi
  window.addEventListener('popstate', function () {
    var params = new URLSearchParams(window.location.search);
    loadResults(params.toString(), false);
  });

  attachResultLinkHandlers();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
