-- Jenis setoran: setoran baru vs muroja'ah (pengulangan)
ALTER TABLE hafalan
  ADD COLUMN jenis ENUM('setoran', 'murojaah') NOT NULL DEFAULT 'setoran'
    AFTER status;

ALTER TABLE hafalan
  ADD KEY hafalan_jenis_created (jenis, created_at);
