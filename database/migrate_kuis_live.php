<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS kuis_live_sesi (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          kuis_id INT UNSIGNED NOT NULL,
          user_id INT UNSIGNED NOT NULL,
          pin CHAR(6) NOT NULL,
          status ENUM('lobby','question','reveal','leaderboard','podium','ended') NOT NULL DEFAULT 'lobby',
          current_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
          soal_order_json JSON NOT NULL,
          question_started_at INT UNSIGNED NULL DEFAULT NULL,
          question_ends_at INT UNSIGNED NULL DEFAULT NULL,
          state_version INT UNSIGNED NOT NULL DEFAULT 1,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY kuis_live_sesi_pin_unique (pin),
          KEY kuis_live_sesi_kuis_id_index (kuis_id),
          KEY kuis_live_sesi_user_id_index (user_id),
          KEY kuis_live_sesi_status_index (status),
          CONSTRAINT kuis_live_sesi_kuis_id_foreign
            FOREIGN KEY (kuis_id) REFERENCES kuis (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
          CONSTRAINT kuis_live_sesi_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: tabel kuis_live_sesi.\n";

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS kuis_live_peserta (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          sesi_id INT UNSIGNED NOT NULL,
          token CHAR(64) NOT NULL,
          nama VARCHAR(120) NOT NULL,
          siswa_id INT UNSIGNED NULL DEFAULT NULL,
          skor INT UNSIGNED NOT NULL DEFAULT 0,
          joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY kuis_live_peserta_token_unique (token),
          KEY kuis_live_peserta_sesi_id_index (sesi_id),
          KEY kuis_live_peserta_siswa_id_index (siswa_id),
          CONSTRAINT kuis_live_peserta_sesi_id_foreign
            FOREIGN KEY (sesi_id) REFERENCES kuis_live_sesi (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
          CONSTRAINT kuis_live_peserta_siswa_id_foreign
            FOREIGN KEY (siswa_id) REFERENCES siswa (id)
            ON UPDATE CASCADE
            ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: tabel kuis_live_peserta.\n";

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS kuis_live_jawaban (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          sesi_id INT UNSIGNED NOT NULL,
          peserta_id INT UNSIGNED NOT NULL,
          soal_id INT UNSIGNED NOT NULL,
          pilih_json JSON NULL DEFAULT NULL,
          benar TINYINT(1) NOT NULL DEFAULT 0,
          poin SMALLINT UNSIGNED NOT NULL DEFAULT 0,
          ms_jawab INT UNSIGNED NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY kuis_live_jawaban_peserta_soal_unique (peserta_id, soal_id),
          KEY kuis_live_jawaban_sesi_id_index (sesi_id),
          KEY kuis_live_jawaban_soal_id_index (soal_id),
          CONSTRAINT kuis_live_jawaban_sesi_id_foreign
            FOREIGN KEY (sesi_id) REFERENCES kuis_live_sesi (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
          CONSTRAINT kuis_live_jawaban_peserta_id_foreign
            FOREIGN KEY (peserta_id) REFERENCES kuis_live_peserta (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
          CONSTRAINT kuis_live_jawaban_soal_id_foreign
            FOREIGN KEY (soal_id) REFERENCES kuis_soal (id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: tabel kuis_live_jawaban.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal migrate: ' . $e->getMessage() . "\n";
}
