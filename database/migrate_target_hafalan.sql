CREATE TABLE IF NOT EXISTS target_hafalan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kelas_id INT UNSIGNED NOT NULL,
  judul VARCHAR(150) NOT NULL DEFAULT 'Target hafalan',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY target_hafalan_kelas_unique (kelas_id),
  CONSTRAINT target_hafalan_kelas_id_foreign
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS target_hafalan_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  target_id INT UNSIGNED NOT NULL,
  surat_nomor TINYINT UNSIGNED NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY target_item_unique (target_id, surat_nomor),
  KEY target_item_target_id_index (target_id),
  CONSTRAINT target_hafalan_item_target_id_foreign
    FOREIGN KEY (target_id) REFERENCES target_hafalan (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
