<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sparepart_repo.php';
require_once __DIR__ . '/../../includes/xlsx_lite.php';
require_login();

$pdo = get_pdo();
$pageTitle = 'Import & Export Excel';

$reportSukses = 0;
$reportGagal  = 0;
$reportErrorRows = [];
$didImport = false;

/** Ekstrak baris tabel dari file upload (.xlsx via XlsxLite, .csv via fgetcsv). */
function extract_rows_from_upload(array $file): array
{
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'xlsx') {
        return XlsxLite::read($file['tmp_name']);
    }
    if ($ext === 'csv') {
        $rows = [];
        if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
            // Deteksi delimiter sederhana: koma atau titik koma
            $firstLine = fgets($handle);
            rewind($handle);
            $delim = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            while (($data = fgetcsv($handle, 0, $delim)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }
    if ($ext === 'xls') {
        throw new RuntimeException('Format .xls (Excel lama/biner) belum didukung di versi ini. Silakan simpan ulang file sebagai .xlsx atau .csv lalu upload kembali.');
    }
    throw new RuntimeException('Format file tidak didukung. Gunakan .xlsx atau .csv.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_import'])) {
    csrf_verify();
    $strategy = $_POST['duplicate_strategy'] ?? 'skip'; // skip | update | cancel

    try {
        if ($_FILES['file_import']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file gagal.');
        }
        $rows = extract_rows_from_upload($_FILES['file_import']);
        if (count($rows) < 2) {
            throw new RuntimeException('File kosong atau tidak memiliki data (hanya header).');
        }

        // Kolom wajib sesuai template
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $rows[0]);
        $expectedMap = [
            'kode custom' => 'kode_custom', 'nama sparepart' => 'nama_sparepart', 'merk' => 'merk',
            'jenis motor' => 'jenis_motor', 'kategori' => 'kategori', 'satuan' => 'satuan',
            'harga beli' => 'harga_beli', 'harga jual' => 'harga_jual', 'stok' => 'stok',
            'lokasi rak' => 'lokasi_rak', 'keterangan' => 'keterangan',
        ];
        $colIndex = [];
        foreach ($header as $i => $h) {
            if (isset($expectedMap[$h])) {
                $colIndex[$expectedMap[$h]] = $i;
            }
        }
        if (!isset($colIndex['kode_custom']) || !isset($colIndex['nama_sparepart'])) {
            throw new RuntimeException('Kolom "Kode Custom" dan "Nama Sparepart" wajib ada di file (sesuai template).');
        }

        $dataRows = array_slice($rows, 1);
        $seenInFile = [];

        // Jika strategy = cancel, kita cek dulu semua baris tanpa insert apapun bila ada 1 error
        $pdo->beginTransaction();
        foreach ($dataRows as $rIdx => $row) {
            $lineNo = $rIdx + 2; // +2 karena baris 1 = header, index mulai 0
            $get = fn(string $key) => trim((string)($row[$colIndex[$key]] ?? ''));

            $kode = $get('kode_custom');
            $nama = $get('nama_sparepart');

            if ($kode === '' && $nama === '') {
                continue; // baris kosong, lewati diam-diam
            }

            $rowErrors = [];
            if (!is_valid_kode_custom($kode)) {
                $rowErrors[] = 'Kode Custom tidak valid/kosong';
            }
            if ($nama === '') {
                $rowErrors[] = 'Nama Sparepart kosong';
            }
            $hargaBeli = $get('harga_beli');
            $hargaJual = $get('harga_jual');
            $stok      = $get('stok');
            if ($hargaBeli !== '' && !is_numeric($hargaBeli)) $rowErrors[] = 'Harga Beli bukan angka';
            if ($hargaJual !== '' && !is_numeric($hargaJual)) $rowErrors[] = 'Harga Jual bukan angka';
            if ($stok !== '' && !is_numeric($stok)) $rowErrors[] = 'Stok bukan angka';

            if (isset($seenInFile[$kode])) {
                $rowErrors[] = "Kode Custom \"$kode\" duplikat di dalam file ini sendiri (baris {$seenInFile[$kode]})";
            }

            if ($rowErrors) {
                $reportGagal++;
                $reportErrorRows[] = ['line' => $lineNo, 'kode' => $kode, 'errors' => $rowErrors];
                continue;
            }
            $seenInFile[$kode] = $lineNo;

            $existing = sparepart_find_by_kode($pdo, $kode);
            $data = [
                'kode_custom'    => $kode,
                'nama_sparepart' => $nama,
                'merk'           => $get('merk') ?: null,
                'jenis_motor'    => $get('jenis_motor') ?: null,
                'kategori'       => $get('kategori') ?: null,
                'satuan'         => $get('satuan') ?: 'PCS',
                'harga_beli'     => $hargaBeli !== '' ? (float)$hargaBeli : 0,
                'harga_jual'     => $hargaJual !== '' ? (float)$hargaJual : 0,
                'stok'           => $stok !== '' ? (int)$stok : 0,
                'lokasi_rak'     => $get('lokasi_rak') ?: null,
                'keterangan'     => $get('keterangan') ?: null,
            ];

            if ($existing) {
                if ($strategy === 'skip') {
                    $reportGagal++;
                    $reportErrorRows[] = ['line' => $lineNo, 'kode' => $kode, 'errors' => ['Kode sudah ada di database — dilewati sesuai pilihan Anda']];
                    continue;
                } elseif ($strategy === 'update') {
                    sparepart_update($pdo, (int)$existing['id'], $data);
                    $reportSukses++;
                } elseif ($strategy === 'cancel') {
                    throw new RuntimeException("Kode \"$kode\" (baris $lineNo) sudah ada di database. Import dibatalkan sesuai pilihan \"Batalkan jika ada duplikat\".");
                }
            } else {
                sparepart_create($pdo, $data);
                $reportSukses++;
            }
        }
        $pdo->commit();
        $didImport = true;
        flash_set('success', "Import selesai: $reportSukses berhasil, $reportGagal gagal.");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'Import dibatalkan: ' . $e->getMessage());
    }
}
?>
<h4><i class="bi bi-file-earmark-excel"></i> Import & Export Data Sparepart</h4>

<div class="row g-4 mt-1">
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5><i class="bi bi-upload"></i> Import Excel/CSV</h5>
        <p class="text-muted small">Format kolom: Kode Custom | Nama Sparepart | Merk | Jenis Motor | Kategori | Satuan | Harga Beli | Harga Jual | Stok | Lokasi Rak | Keterangan</p>
        <a href="<?= base_url('modules/excel/template.php') ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download"></i> DOWNLOAD TEMPLATE EXCEL</a>

        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">File (.xlsx atau .csv)</label>
            <input type="file" name="file_import" class="form-control" accept=".xlsx,.csv" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Jika Kode Custom sudah ada di database:</label>
            <select name="duplicate_strategy" class="form-select">
              <option value="skip">Lewati baris duplikat (data lama tidak berubah)</option>
              <option value="update">Update data lama dengan data baru dari file</option>
              <option value="cancel">Batalkan seluruh proses import</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> IMPORT EXCEL</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5><i class="bi bi-download"></i> Export Data ke Excel</h5>
        <p class="text-muted small">Mengunduh seluruh data sparepart (atau sesuai filter pencarian terakhir di Katalog) dalam format .xlsx.</p>
        <a href="<?= base_url('modules/excel/export.php' . (isset($_GET['q']) ? '?' . http_build_query($_GET) : '')) ?>" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> EXPORT EXCEL</a>
      </div>
    </div>
  </div>
</div>

<?php if ($didImport): ?>
<div class="card shadow-sm mt-4">
  <div class="card-body">
    <h5>Ringkasan Import</h5>
    <p><span class="badge bg-success">Berhasil: <?= $reportSukses ?></span> <span class="badge bg-danger">Gagal: <?= $reportGagal ?></span></p>
    <?php if ($reportErrorRows): ?>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Baris</th><th>Kode</th><th>Alasan Gagal</th></tr></thead>
        <tbody>
          <?php foreach ($reportErrorRows as $er): ?>
            <tr><td><?= (int)$er['line'] ?></td><td><?= e($er['kode']) ?></td><td><?= e(implode(', ', $er['errors'])) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
