ALTER TABLE bahan_ajar
  MODIFY sumber ENUM('upload', 'gdrive', 'youtube', 'onedrive', 'canva', 'vimeo') NOT NULL;
