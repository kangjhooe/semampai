<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $sql = file_get_contents(__DIR__ . '/migrate_bahan_ajar.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca migrate_bahan_ajar.sql');
    }

    Database::connection()->exec($sql);
    echo "OK: tabel bahan_ajar siap.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
