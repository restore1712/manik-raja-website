-- Jalankan sekali pada database marga_manik untuk mengaktifkan relasi atasan-bawahan.
ALTER TABLE officers
  ADD COLUMN parent_officer_id INT DEFAULT NULL,
  ADD INDEX idx_officers_parent (parent_officer_id);
