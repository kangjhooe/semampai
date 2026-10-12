<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    $cols = [
        'izinkan_ulang' => 'ALTER TABLE kuis ADD COLUMN izinkan_ulang TINYINT(1) NOT NULL DEFAULT 0 AFTER timer_menit',
        'tampil_pembahasan' => 'ALTER TABLE kuis ADD COLUMN tampil_pembahasan TINYINT(1) NOT NULL DEFAULT 1 AFTER izinkan_ulang',
    ];

    foreach ($cols as $name => $sql) {
        $check = $pdo->query("SHOW COLUMNS FROM kuis LIKE " . $pdo->quote($name));
        if ($check && $check->fetch()) {
            echo "OK: kolom kuis.{$name} sudah ada.\n";
        } else {
            $pdo->exec($sql);
            echo "OK: kolom kuis.{$name} ditambahkan.\n";
        }
    }

    $siswaCol = $pdo->query("SHOW COLUMNS FROM kuis_hasil LIKE 'siswa_id'");
    if ($siswaCol && $siswaCol->fetch()) {
        echo "OK: kolom kuis_hasil.siswa_id sudah ada.\n";
    } else {
        $pdo->exec(
            'ALTER TABLE kuis_hasil
             ADD COLUMN siswa_id INT UNSIGNED NULL DEFAULT NULL AFTER nama_peserta,
             ADD KEY kuis_hasil_siswa_id_index (siswa_id),
             ADD CONSTRAINT kuis_hasil_siswa_id_foreign
               FOREIGN KEY (siswa_id) REFERENCES siswa (id)
               ON UPDATE CASCADE
               ON DELETE SET NULL'
        );
        echo "OK: kolom kuis_hasil.siswa_id ditambahkan.\n";
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
