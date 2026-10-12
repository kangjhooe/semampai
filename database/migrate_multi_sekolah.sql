-- Relasi guru ↔ banyak sekolah + kelas terikat NPSN.
-- Jalankan via migrate_multi_sekolah.php (idempotent).

CREATE TABLE IF NOT EXISTS user_sekolah (
  user_id INT UNSIGNED NOT NULL,
  npsn VARCHAR(8) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, npsn),
  KEY user_sekolah_npsn_index (npsn),
  CONSTRAINT user_sekolah_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT user_sekolah_npsn_foreign
    FOREIGN KEY (npsn) REFERENCES sekolah (npsn)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
