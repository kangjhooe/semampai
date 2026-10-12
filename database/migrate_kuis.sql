CREATE TABLE IF NOT EXISTS kuis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  kelas_id INT UNSIGNED NULL DEFAULT NULL,
  judul VARCHAR(200) NOT NULL,
  kode VARCHAR(8) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  acak_soal TINYINT(1) NOT NULL DEFAULT 0,
  acak_opsi TINYINT(1) NOT NULL DEFAULT 0,
  timer_menit SMALLINT UNSIGNED NULL DEFAULT NULL,
  izinkan_ulang TINYINT(1) NOT NULL DEFAULT 0,
  tampil_pembahasan TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY kuis_kode_unique (kode),
  KEY kuis_user_id_index (user_id),
  KEY kuis_kelas_id_index (kelas_id),
  CONSTRAINT kuis_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT kuis_kelas_id_foreign
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kuis_soal (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kuis_id INT UNSIGNED NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  pertanyaan TEXT NOT NULL,
  opsi_json JSON NOT NULL,
  jawaban_index TINYINT UNSIGNED NOT NULL,
  gambar_path VARCHAR(255) NULL DEFAULT NULL,
  gambar_url TEXT NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY kuis_soal_kuis_id_index (kuis_id),
  CONSTRAINT kuis_soal_kuis_id_foreign
    FOREIGN KEY (kuis_id) REFERENCES kuis (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kuis_hasil (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kuis_id INT UNSIGNED NOT NULL,
  nama_peserta VARCHAR(120) NOT NULL,
  siswa_id INT UNSIGNED NULL DEFAULT NULL,
  skor SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  total SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  jawaban_json JSON NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY kuis_hasil_kuis_id_index (kuis_id),
  KEY kuis_hasil_siswa_id_index (siswa_id),
  KEY kuis_hasil_created_index (created_at),
  CONSTRAINT kuis_hasil_kuis_id_foreign
    FOREIGN KEY (kuis_id) REFERENCES kuis (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT kuis_hasil_siswa_id_foreign
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
