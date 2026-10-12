<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

/**
 * @return list<array{CONSTRAINT_NAME: string}>
 */
function fk_on_column(PDO $db, string $table, string $column): array
{
    $stmt = $db->prepare(
        "SELECT DISTINCT CONSTRAINT_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column
           AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return $stmt->fetchAll();
}

function column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        "SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column
         LIMIT 1"
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (bool) $stmt->fetchColumn();
}

function index_exists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare(
        "SELECT 1
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND INDEX_NAME = :index
         LIMIT 1"
    );
    $stmt->execute(['table' => $table, 'index' => $index]);

    return (bool) $stmt->fetchColumn();
}

try {
    $db = Database::connection();

    $sql = file_get_contents(__DIR__ . '/migrate_multi_sekolah.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca migrate_multi_sekolah.sql');
    }
    $db->exec($sql);
    echo "OK: tabel user_sekolah siap.\n";

    $hasUsersNpsn = column_exists($db, 'users', 'npsn');
    if ($hasUsersNpsn) {
        $db->exec(
            'INSERT IGNORE INTO user_sekolah (user_id, npsn)
             SELECT id, npsn FROM users WHERE npsn IS NOT NULL AND npsn <> \'\''
        );
        echo "OK: keanggotaan sekolah dari users.npsn disalin.\n";
    } else {
        echo "SKIP: users.npsn sudah tidak ada (data pivot diasumsikan ada).\n";
    }

    if (!column_exists($db, 'kelas', 'npsn')) {
        $db->exec('ALTER TABLE kelas ADD COLUMN npsn VARCHAR(8) NULL AFTER user_id');
        echo "OK: kolom kelas.npsn ditambahkan.\n";
    } else {
        echo "SKIP: kolom kelas.npsn sudah ada.\n";
    }

    if ($hasUsersNpsn) {
        $db->exec(
            'UPDATE kelas k
             INNER JOIN users u ON u.id = k.user_id
             SET k.npsn = u.npsn
             WHERE k.npsn IS NULL OR k.npsn = \'\''
        );
        echo "OK: kelas.npsn diisi dari sekolah guru.\n";
    }

    $orphan = (int) $db->query(
        "SELECT COUNT(*) FROM kelas WHERE npsn IS NULL OR npsn = ''"
    )->fetchColumn();
    if ($orphan > 0) {
        throw new RuntimeException(
            "Ada {$orphan} kelas tanpa NPSN. Perbaiki data sebelum lanjut migrasi."
        );
    }

    $db->exec('ALTER TABLE kelas MODIFY npsn VARCHAR(8) NOT NULL');
    echo "OK: kelas.npsn NOT NULL.\n";

    if (index_exists($db, 'kelas', 'kelas_user_nama_tahun_unique')) {
        $db->exec('ALTER TABLE kelas DROP INDEX kelas_user_nama_tahun_unique');
        echo "OK: unique lama kelas dihapus.\n";
    }

    if (!index_exists($db, 'kelas', 'kelas_user_npsn_nama_tahun_unique')) {
        $db->exec(
            'ALTER TABLE kelas
             ADD UNIQUE KEY kelas_user_npsn_nama_tahun_unique (user_id, npsn, nama, tahun_ajaran)'
        );
        echo "OK: unique (user_id, npsn, nama, tahun_ajaran) ditambah.\n";
    }

    if (!index_exists($db, 'kelas', 'kelas_npsn_index')) {
        $db->exec('ALTER TABLE kelas ADD KEY kelas_npsn_index (npsn)');
        echo "OK: index kelas_npsn_index ditambah.\n";
    }

    $kelasFk = fk_on_column($db, 'kelas', 'npsn');
    if ($kelasFk === []) {
        $db->exec(
            'ALTER TABLE kelas
             ADD CONSTRAINT kelas_npsn_foreign
               FOREIGN KEY (npsn) REFERENCES sekolah (npsn)
               ON UPDATE CASCADE
               ON DELETE RESTRICT'
        );
        echo "OK: FK kelas.npsn ditambah.\n";
    } else {
        echo "SKIP: FK kelas.npsn sudah ada.\n";
    }

    if ($hasUsersNpsn) {
        foreach (fk_on_column($db, 'users', 'npsn') as $fk) {
            $name = (string) $fk['CONSTRAINT_NAME'];
            $db->exec('ALTER TABLE users DROP FOREIGN KEY `' . str_replace('`', '``', $name) . '`');
            echo "OK: FK users.{$name} dihapus.\n";
        }

        if (index_exists($db, 'users', 'users_npsn_index')) {
            $db->exec('ALTER TABLE users DROP INDEX users_npsn_index');
            echo "OK: index users_npsn_index dihapus.\n";
        }

        $db->exec('ALTER TABLE users DROP COLUMN npsn');
        echo "OK: kolom users.npsn dihapus.\n";
    }

    echo "Selesai: guru dapat terhubung ke lebih dari satu sekolah.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
