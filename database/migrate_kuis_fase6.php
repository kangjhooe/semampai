<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    $addCol = static function (PDO $pdo, string $table, string $name, string $ddl) use (&$echo): void {
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
        'mode_latihan',
        'ALTER TABLE kuis ADD COLUMN mode_latihan TINYINT(1) NOT NULL DEFAULT 0 AFTER tampil_pembahasan'
    );

    $addCol(
        $pdo,
        'kuis_soal',
        'tipe',
        "ALTER TABLE kuis_soal ADD COLUMN tipe ENUM('pilihan_ganda','isian') NOT NULL DEFAULT 'pilihan_ganda' AFTER pertanyaan"
    );

    $addCol(
        $pdo,
        'kuis_soal',
        'jawaban_teks',
        'ALTER TABLE kuis_soal ADD COLUMN jawaban_teks VARCHAR(255) NULL DEFAULT NULL AFTER jawaban_index'
    );

    $addCol(
        $pdo,
        'kuis_soal',
        'timer_detik',
        'ALTER TABLE kuis_soal ADD COLUMN timer_detik SMALLINT UNSIGNED NULL DEFAULT NULL AFTER jawaban_teks'
    );

    $addCol(
        $pdo,
        'bahan_ajar',
        'kuis_id',
        'ALTER TABLE bahan_ajar ADD COLUMN kuis_id INT UNSIGNED NULL DEFAULT NULL AFTER embed_url'
    );

    $fk = $pdo->query(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'bahan_ajar'
           AND COLUMN_NAME = 'kuis_id'
           AND REFERENCED_TABLE_NAME = 'kuis'
         LIMIT 1"
    );
    if ($fk && $fk->fetch()) {
        echo "OK: FK bahan_ajar.kuis_id sudah ada.\n";
    } else {
        $pdo->exec(
            'ALTER TABLE bahan_ajar
             ADD KEY bahan_ajar_kuis_id_index (kuis_id),
             ADD CONSTRAINT bahan_ajar_kuis_id_foreign
               FOREIGN KEY (kuis_id) REFERENCES kuis (id)
               ON UPDATE CASCADE
               ON DELETE SET NULL'
        );
        echo "OK: FK bahan_ajar.kuis_id ditambahkan.\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
