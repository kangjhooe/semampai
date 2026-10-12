<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();
    $check = $pdo->query("SHOW COLUMNS FROM kuis LIKE 'timer_menit'");
    if ($check && $check->fetch()) {
        echo "OK: kolom timer_menit sudah ada.\n";
        exit;
    }

    $sql = file_get_contents(__DIR__ . '/migrate_kuis_timer.sql');
    if ($sql === false) {
        throw new RuntimeException('Tidak bisa membaca migrate_kuis_timer.sql');
    }

    $pdo->exec($sql);
    echo "OK: kolom timer_menit ditambahkan.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
