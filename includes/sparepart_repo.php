<?php
declare(strict_types=1);

/**
 * Kumpulan fungsi akses data sparepart.
 * Semua query menggunakan prepared statement (PDO) untuk keamanan.
 */

function sparepart_kode_exists(PDO $pdo, string $kode, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = $pdo->prepare('SELECT id FROM spareparts WHERE kode_custom = ? AND id != ? LIMIT 1');
        $stmt->execute([$kode, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM spareparts WHERE kode_custom = ? LIMIT 1');
        $stmt->execute([$kode]);
    }
    return (bool) $stmt->fetch();
}

function sparepart_find_by_kode(PDO $pdo, string $kode): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM spareparts WHERE kode_custom = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$kode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function sparepart_find_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM spareparts WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
/**
 * Bangun klausa WHERE + argumen prepared statement dari filter (dipakai bersama
 * oleh sparepart_list dan sparepart_list_all supaya logika filter selalu konsisten).
 */
function sparepart_build_where(array $params): array
{
    $where = ['is_active = 1'];
    $args  = [];

    if (!empty($params['q'])) {
        $where[] = '(nama_sparepart LIKE ? OR kode_custom LIKE ?)';
        $like = '%' . $params['q'] . '%';
        $args[] = $like;
        $args[] = $like;
    }
    if (!empty($params['merk'])) {
        $where[] = 'merk = ?';
        $args[] = $params['merk'];
    }
    if (!empty($params['jenis_motor'])) {
        $where[] = 'jenis_motor = ?';
        $args[] = $params['jenis_motor'];
    }
    if (!empty($params['kategori'])) {
        $where[] = 'kategori = ?';
        $args[] = $params['kategori'];
    }
    if (!empty($params['stok_filter'])) {
        if ($params['stok_filter'] === 'habis') {
            $where[] = 'stok <= 0';
        } elseif ($params['stok_filter'] === 'menipis') {
            $where[] = 'stok > 0 AND stok <= 5';
        } elseif ($params['stok_filter'] === 'tersedia') {
            $where[] = 'stok > 5';
        }
    }

    return ['sql' => implode(' AND ', $where), 'args' => $args];
}

/**
 * List sparepart dengan search + filter + pagination (server-side, aman untuk 20rb+ data).
 * per_page dibatasi maksimal 1000 (cukup untuk kebutuhan browsing "ratusan per halaman"
 * di UI, sekaligus menjaga browser tidak lag me-render ribuan kartu sekaligus).
 * Untuk EXPORT (butuh SEMUA baris terfilter tanpa batas), gunakan sparepart_list_all().
 *
 * @return array{data: array, total: int, page: int, perPage: int, totalPages: int}
 */
function sparepart_list(PDO $pdo, array $params): array
{
    $page    = max(1, (int)($params['page'] ?? 1));
    $perPage = min(1000, max(1, (int)($params['per_page'] ?? 24)));
    $offset  = ($page - 1) * $perPage;

    $w = sparepart_build_where($params);
    $whereSql = $w['sql'];
    $args = $w['args'];

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spareparts WHERE $whereSql");
    $countStmt->execute($args);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT * FROM spareparts WHERE $whereSql ORDER BY updated_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $data = $stmt->fetchAll();

    return [
        'data'       => $data,
        'total'      => $total,
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => (int)ceil($total / $perPage),
    ];
}

/**
 * Ambil SEMUA baris yang cocok dengan filter, TANPA LIMIT/pagination sama sekali.
 * Khusus dipakai untuk EXPORT EXCEL — jumlah baris yang di-export akan selalu
 * sama persis dengan "Ditemukan X sparepart" di Katalog, berapa pun jumlahnya
 * (termasuk 20.000+), tidak terpengaruh oleh pengaturan "views per page".
 */
function sparepart_list_all(PDO $pdo, array $params): array
{
    $w = sparepart_build_where($params);
    $stmt = $pdo->prepare("SELECT * FROM spareparts WHERE {$w['sql']} ORDER BY updated_at DESC");
    $stmt->execute($w['args']);
    return $stmt->fetchAll();
}

function sparepart_distinct_values(PDO $pdo, string $column): array
{
    $allowed = ['merk', 'jenis_motor', 'kategori'];
    if (!in_array($column, $allowed, true)) {
        throw new InvalidArgumentException('Kolom tidak diizinkan.');
    }
    $stmt = $pdo->query("SELECT DISTINCT $column FROM spareparts WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function sparepart_create(PDO $pdo, array $d): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO spareparts
        (kode_custom, nama_sparepart, foto, merk, jenis_motor, kategori, satuan, harga_beli, harga_jual, stok, lokasi_rak, keterangan)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $d['kode_custom'], $d['nama_sparepart'], $d['foto'] ?? null, $d['merk'] ?? null,
        $d['jenis_motor'] ?? null, $d['kategori'] ?? null, $d['satuan'] ?? 'PCS',
        $d['harga_beli'] ?? 0, $d['harga_jual'] ?? 0, $d['stok'] ?? 0,
        $d['lokasi_rak'] ?? null, $d['keterangan'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function sparepart_update(PDO $pdo, int $id, array $d): void
{
    $fields = [
        'kode_custom = ?', 'nama_sparepart = ?', 'merk = ?', 'jenis_motor = ?',
        'kategori = ?', 'satuan = ?', 'harga_beli = ?', 'harga_jual = ?',
        'stok = ?', 'lokasi_rak = ?', 'keterangan = ?',
    ];
    $args = [
        $d['kode_custom'], $d['nama_sparepart'], $d['merk'] ?? null, $d['jenis_motor'] ?? null,
        $d['kategori'] ?? null, $d['satuan'] ?? 'PCS', $d['harga_beli'] ?? 0, $d['harga_jual'] ?? 0,
        $d['stok'] ?? 0, $d['lokasi_rak'] ?? null, $d['keterangan'] ?? null,
    ];
    if (!empty($d['foto'])) {
        $fields[] = 'foto = ?';
        $args[] = $d['foto'];
    }
    $args[] = $id;
    $sql = 'UPDATE spareparts SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
}

function sparepart_soft_delete(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('UPDATE spareparts SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
}

function sparepart_print_history_add(PDO $pdo, int $sparepartId, string $kode, string $nama, int $jumlah, string $printedBy): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO barcode_print_history (sparepart_id, kode_custom, nama_sparepart_snapshot, jumlah, printed_by)
         VALUES (?,?,?,?,?)'
    );
    $stmt->execute([$sparepartId, $kode, $nama, $jumlah, $printedBy]);
}

function sparepart_print_history_list(PDO $pdo, ?int $sparepartId = null, int $limit = 100): array
{
    if ($sparepartId) {
        $stmt = $pdo->prepare('SELECT * FROM barcode_print_history WHERE sparepart_id = ? ORDER BY printed_at DESC LIMIT ?');
        $stmt->bindValue(1, $sparepartId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM barcode_print_history ORDER BY printed_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}
