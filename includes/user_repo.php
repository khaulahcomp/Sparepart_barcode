<?php
declare(strict_types=1);

/**
 * Kumpulan fungsi akses data user/akun login.
 * Semua query menggunakan prepared statement (PDO) untuk keamanan.
 * Password TIDAK PERNAH disimpan atau dibandingkan dalam bentuk plain text -
 * selalu lewat password_hash()/password_verify() (bcrypt), sama seperti attempt_login()
 * di includes/auth.php.
 */

function user_username_exists(PDO $pdo, string $username, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $stmt->execute([$username, $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
    }
    return (bool) $stmt->fetch();
}

function user_find_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Daftar semua user untuk halaman Kelola Pengguna (tanpa password_hash).
 */
function user_list(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, username, nama_lengkap, role, is_active, created_at FROM users ORDER BY username ASC');
    return $stmt->fetchAll();
}

/**
 * Hitung jumlah admin yang masih AKTIF, opsional exclude 1 id tertentu.
 * Dipakai untuk mencegah sistem kehilangan admin terakhir (self-lockout).
 */
function user_count_active_admin(PDO $pdo, ?int $excludeId = null): int
{
    if ($excludeId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
        $stmt->execute([$excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        $stmt->execute();
    }
    return (int) $stmt->fetchColumn();
}

function user_create(PDO $pdo, string $username, string $plainPassword, string $namaLengkap, string $role): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, nama_lengkap, role, is_active) VALUES (?,?,?,?,1)'
    );
    $stmt->execute([
        $username,
        password_hash($plainPassword, PASSWORD_DEFAULT),
        $namaLengkap,
        $role,
    ]);
    return (int) $pdo->lastInsertId();
}

function user_update_profile(PDO $pdo, int $id, string $username, string $namaLengkap): void
{
    $stmt = $pdo->prepare('UPDATE users SET username = ?, nama_lengkap = ? WHERE id = ?');
    $stmt->execute([$username, $namaLengkap, $id]);
}

function user_update_role(PDO $pdo, int $id, string $role): void
{
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, $id]);
}

function user_update_password(PDO $pdo, int $id, string $plainPassword): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
}

function user_set_active(PDO $pdo, int $id, bool $active): void
{
    $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, $id]);
}
