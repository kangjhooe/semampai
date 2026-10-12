ALTER TABLE kuis
  ADD COLUMN acak_opsi TINYINT(1) NOT NULL DEFAULT 0
  AFTER acak_soal;

ALTER TABLE kuis_soal
  ADD COLUMN gambar_path VARCHAR(255) NULL DEFAULT NULL
  AFTER jawaban_index,
  ADD COLUMN gambar_url TEXT NULL DEFAULT NULL
  AFTER gambar_path;
