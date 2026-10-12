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
  no_wa VARCHAR(20) NOT NULL,
  role ENUM('guru', 'admin') NOT NULL DEFAULT 'guru',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_nik_unique (nik),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL,
  `value` TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kelas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  npsn VARCHAR(8) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tahun_ajaran VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY kelas_user_npsn_nama_tahun_unique (user_id, npsn, nama, tahun_ajaran),
  KEY kelas_user_id_index (user_id),
  KEY kelas_npsn_index (npsn),
  CONSTRAINT kelas_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT kelas_npsn_foreign
    FOREIGN KEY (npsn) REFERENCES sekolah (npsn)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
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
  jenis ENUM('setoran', 'murojaah') NOT NULL DEFAULT 'setoran',
  catatan VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY hafalan_siswa_id_index (siswa_id),
  KEY hafalan_user_id_index (user_id),
  KEY hafalan_siswa_status_created (siswa_id, status, created_at),
  KEY hafalan_jenis_created (jenis, created_at),
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

CREATE TABLE IF NOT EXISTS bahan_ajar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  judul VARCHAR(200) NOT NULL,
  sumber ENUM('upload', 'gdrive', 'youtube', 'onedrive', 'canva', 'vimeo') NOT NULL,
  file_path VARCHAR(255) NULL DEFAULT NULL,
  file_mime VARCHAR(100) NULL DEFAULT NULL,
  file_ext VARCHAR(10) NULL DEFAULT NULL,
  original_url TEXT NULL DEFAULT NULL,
  embed_url TEXT NULL DEFAULT NULL,
  kuis_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY bahan_user_id_index (user_id),
  KEY bahan_ajar_kuis_id_index (kuis_id),
  CONSTRAINT bahan_user_id_foreign
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  mode_latihan TINYINT(1) NOT NULL DEFAULT 0,
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
  tipe ENUM('pilihan_ganda', 'isian') NOT NULL DEFAULT 'pilihan_ganda',
  opsi_json JSON NOT NULL,
  jawaban_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
  jawaban_teks VARCHAR(255) NULL DEFAULT NULL,
  timer_detik SMALLINT UNSIGNED NULL DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS kuis_live_sesi (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kuis_live_peserta (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kuis_live_jawaban (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
