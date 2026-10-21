-- Jalankan sekali untuk mengaktifkan kolom foto pengurus.
USE marga_manik;

ALTER TABLE officers
  ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL;
