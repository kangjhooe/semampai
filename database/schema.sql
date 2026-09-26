CREATE DATABASE IF NOT EXISTS semampai
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE semampai;

CREATE TABLE IF NOT EXISTS sekolah (
  npsn VARCHAR(8) NOT NULL,
  nama_sekolah VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (npsn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nik VARCHAR(16) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  npsn VARCHAR(8) NOT NULL,
  no_wa VARCHAR(20) NOT NULL,
  role ENUM('guru', 'admin') NOT NULL DEFAULT 'guru',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_nik_unique (nik),
  UNIQUE KEY users_email_unique (email),
  KEY users_npsn_index (npsn),
  CONSTRAINT users_npsn_foreign
    FOREIGN KEY (npsn) REFERENCES sekolah (npsn)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL,
  `value` TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kelas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tahun_ajaran VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY kelas_user_nama_tahun_unique (user_id, nama, tahun_ajaran),
  KEY kelas_user_id_index (user_id),
  CONSTRAINT kelas_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS siswa (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kelas_id INT UNSIGNED NOT NULL,
  nama VARCHAR(255) NOT NULL,
  nisn VARCHAR(10) NOT NULL,
  tempat_lahir VARCHAR(100) NOT NULL,
  tanggal_lahir DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY siswa_nisn_unique (nisn),
  KEY siswa_kelas_id_index (kelas_id),
  CONSTRAINT siswa_kelas_id_foreign
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
