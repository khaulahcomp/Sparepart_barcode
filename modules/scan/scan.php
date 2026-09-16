<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_login();
$pageTitle = 'Scan Barcode';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <h4><i class="bi bi-upc"></i> Scan Barcode Sparepart</h4>
        <p class="text-muted">Arahkan scanner USB ke label barcode, atau ketik manual lalu tekan Enter.</p>
        <input type="text" id="scanInput" class="form-control form-control-lg text-center font-monospace mb-3"
               placeholder="Tunggu scan / ketik Kode Custom..." autocomplete="off">
        <div id="scanResult"></div>
      </div>
    </div>
  </div>
</div>

<script>
const scanInput = document.getElementById('scanInput');
const resultBox = document.getElementById('scanResult');
const baseUrl = "<?= base_url('modules/scan/scan_ajax.php') ?>";

scanInput.addEventListener('keydown', function (e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    const kode = scanInput.value.trim();
    scanInput.value = '';
    if (!kode) return;
    resultBox.innerHTML = '<div class="text-muted">Mencari...</div>';
    fetch(baseUrl + '?kode=' + encodeURIComponent(kode))
      .then(r => r.json())
      .then(data => {
        if (data.found) {
          const sp = data.sparepart;
          resultBox.innerHTML = `
            <div class="card text-start mt-3">
              <div class="row g-0">
                <div class="col-4">
                  <img src="${sp.foto_url}" class="img-fluid rounded-start" style="height:150px;width:100%;object-fit:cover;">
                </div>
                <div class="col-8">
                  <div class="card-body">
                    <span class="sp-card__kode">${sp.kode_custom}</span>
                    <h5 class="card-title mt-1">${sp.nama_sparepart}</h5>
                    <p class="card-text mb-1"><strong>Merk:</strong> ${sp.merk ?? '-'} &middot; <strong>Motor:</strong> ${sp.jenis_motor ?? '-'}</p>
                    <p class="card-text mb-1"><strong>Harga Jual:</strong> ${sp.harga_jual_fmt}</p>
                    <p class="card-text mb-1"><strong>Stok:</strong> ${sp.stok} &middot; <strong>Lokasi:</strong> ${sp.lokasi_rak ?? '-'}</p>
                    <a href="${sp.detail_url}" class="btn btn-sm btn-primary mt-2">Lihat Detail</a>
                  </div>
                </div>
              </div>
            </div>`;
        } else {
          resultBox.innerHTML = `
            <div class="alert alert-warning mt-3">
              <i class="bi bi-exclamation-triangle"></i> Barcode belum terdaftar: <strong>${kode}</strong>
              ${data.can_add ? `<br><a href="${data.add_url}" class="btn btn-sm btn-outline-dark mt-2">Tambahkan sebagai Sparepart Baru</a>` : ''}
            </div>`;
        }
      });
  }
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
