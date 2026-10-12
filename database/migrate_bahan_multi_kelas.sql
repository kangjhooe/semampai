CREATE TABLE IF NOT EXISTS bahan_ajar_kelas (
  bahan_id INT UNSIGNED NOT NULL,
  kelas_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (bahan_id, kelas_id),
  KEY bahan_ajar_kelas_kelas_id_index (kelas_id),
  CONSTRAINT bahan_ajar_kelas_bahan_id_foreign
    FOREIGN KEY (bahan_id) REFERENCES bahan_ajar (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT bahan_ajar_kelas_kelas_id_foreign
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
