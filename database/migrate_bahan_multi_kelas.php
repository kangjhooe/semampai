<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::connection();

    $sql = file_get_contents(__DIR__ . '/migrate_bahan_multi_kelas.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca migrate_bahan_multi_kelas.sql');
    }
    $db->exec($sql);
    echo "OK: tabel bahan_ajar_kelas siap.\n";

    $hasKelasId = false;
    $cols = $db->query('SHOW COLUMNS FROM bahan_ajar LIKE \'kelas_id\'')->fetchAll();
    if ($cols !== []) {
        $hasKelasId = true;
    }

    if ($hasKelasId) {
        $db->exec(
            'INSERT IGNORE INTO bahan_ajar_kelas (bahan_id, kelas_id)
             SELECT id, kelas_id FROM bahan_ajar WHERE kelas_id IS NOT NULL'
        );
        echo "OK: data kelas lama disalin ke bahan_ajar_kelas.\n";

        $fkName = null;
        $fkStmt = $db->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'bahan_ajar'
               AND COLUMN_NAME = 'kelas_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );
        $fkRow = $fkStmt->fetch();
        if ($fkRow && !empty($fkRow['CONSTRAINT_NAME'])) {
            $fkName = (string) $fkRow['CONSTRAINT_NAME'];
        }

        if ($fkName !== null) {
            $db->exec('ALTER TABLE bahan_ajar DROP FOREIGN KEY `' . str_replace('`', '``', $fkName) . '`');
            echo "OK: foreign key {$fkName} dihapus.\n";
        }

        $db->exec('ALTER TABLE bahan_ajar DROP COLUMN kelas_id');
        echo "OK: kolom bahan_ajar.kelas_id dihapus.\n";
    } else {
        echo "SKIP: kolom bahan_ajar.kelas_id sudah tidak ada.\n";
    }

    echo "Selesai: bahan ajar mendukung multi-kelas.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
