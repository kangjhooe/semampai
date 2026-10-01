CREATE TABLE IF NOT EXISTS bahan_ajar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kelas_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  judul VARCHAR(200) NOT NULL,
  sumber ENUM('upload', 'gdrive', 'youtube') NOT NULL,
  file_path VARCHAR(255) NULL DEFAULT NULL,
  file_mime VARCHAR(100) NULL DEFAULT NULL,
  file_ext VARCHAR(10) NULL DEFAULT NULL,
  original_url TEXT NULL DEFAULT NULL,
  embed_url TEXT NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY bahan_kelas_id_index (kelas_id),
  KEY bahan_user_id_index (user_id),
  CONSTRAINT bahan_kelas_id_foreign
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT bahan_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
