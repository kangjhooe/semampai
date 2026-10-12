<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    $addCol = static function (PDO $pdo, string $table, string $name, string $ddl): void {
        $check = $pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $pdo->quote($name));
        if ($check && $check->fetch()) {
            echo "OK: {$table}.{$name} sudah ada.\n";
            return;
        }
        $pdo->exec($ddl);
        echo "OK: {$table}.{$name} ditambahkan.\n";
    };

    $addCol(
        $pdo,
        'kuis',
        'metode_nilai',
        "ALTER TABLE kuis ADD COLUMN metode_nilai ENUM('tetap','kecepatan','gabungan') NOT NULL DEFAULT 'tetap' AFTER mode_latihan"
    );

    $addCol(
        $pdo,
        'kuis_soal',
        'poin',
        'ALTER TABLE kuis_soal ADD COLUMN poin SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER timer_detik'
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
