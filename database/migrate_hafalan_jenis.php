<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();
    $check = $pdo->query("SHOW COLUMNS FROM hafalan LIKE 'jenis'");
    if ($check && $check->fetch()) {
        echo "OK: kolom jenis sudah ada.\n";
        exit;
    }

    $sql = file_get_contents(__DIR__ . '/migrate_hafalan_jenis.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca migrate_hafalan_jenis.sql');
    }
    $pdo->exec($sql);
    echo "OK: kolom hafalan.jenis siap.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
