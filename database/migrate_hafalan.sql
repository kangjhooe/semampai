CREATE TABLE IF NOT EXISTS hafalan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  surat_nomor TINYINT UNSIGNED NOT NULL,
  surat_nama VARCHAR(64) NOT NULL,
  ayat_awal SMALLINT UNSIGNED NOT NULL,
  ayat_akhir SMALLINT UNSIGNED NOT NULL,
  status ENUM('lancar', 'ulang') NOT NULL,
  catatan VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY hafalan_siswa_id_index (siswa_id),
  KEY hafalan_user_id_index (user_id),
  KEY hafalan_siswa_status_created (siswa_id, status, created_at),
  CONSTRAINT hafalan_siswa_id_foreign
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT hafalan_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
