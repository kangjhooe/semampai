<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    $acak = $pdo->query("SHOW COLUMNS FROM kuis LIKE 'acak_opsi'");
    if (!$acak || !$acak->fetch()) {
        $pdo->exec(
            'ALTER TABLE kuis
             ADD COLUMN acak_opsi TINYINT(1) NOT NULL DEFAULT 0
             AFTER acak_soal'
        );
        echo "OK: kolom kuis.acak_opsi ditambahkan.\n";
    } else {
        echo "OK: kolom kuis.acak_opsi sudah ada.\n";
    }

    $path = $pdo->query("SHOW COLUMNS FROM kuis_soal LIKE 'gambar_path'");
    if (!$path || !$path->fetch()) {
        $pdo->exec(
            'ALTER TABLE kuis_soal
             ADD COLUMN gambar_path VARCHAR(255) NULL DEFAULT NULL
             AFTER jawaban_index'
        );
        echo "OK: kolom kuis_soal.gambar_path ditambahkan.\n";
    } else {
        echo "OK: kolom kuis_soal.gambar_path sudah ada.\n";
    }

    $url = $pdo->query("SHOW COLUMNS FROM kuis_soal LIKE 'gambar_url'");
    if (!$url || !$url->fetch()) {
        $pdo->exec(
            'ALTER TABLE kuis_soal
             ADD COLUMN gambar_url TEXT NULL DEFAULT NULL
             AFTER gambar_path'
        );
        echo "OK: kolom kuis_soal.gambar_url ditambahkan.\n";
    } else {
        echo "OK: kolom kuis_soal.gambar_url sudah ada.\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
