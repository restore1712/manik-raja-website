-- Jalankan sekali untuk memakai kelompok pengurus dan form jabatan.
USE marga_manik;

CREATE TABLE IF NOT EXISTS officer_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat',
  region VARCHAR(150) DEFAULT NULL,
  period VARCHAR(80) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_officer_groups_level_name (level, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE officers
  MODIFY COLUMN level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat',
  ADD COLUMN IF NOT EXISTS officer_group_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS slot_key VARCHAR(40) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS slot_number TINYINT UNSIGNED NOT NULL DEFAULT 1;

SET @has_officer_group_index = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'officers'
    AND index_name = 'idx_officers_group_slots'
);
SET @officer_group_index_sql = IF(
  @has_officer_group_index = 0,
  'ALTER TABLE officers ADD INDEX idx_officers_group_slots (officer_group_id, slot_key, slot_number)',
  'SELECT 1'
);
PREPARE officer_group_index_stmt FROM @officer_group_index_sql;
EXECUTE officer_group_index_stmt;
DEALLOCATE PREPARE officer_group_index_stmt;

-- Buat satu kelompok untuk setiap tingkat/cakupan lama, lalu tautkan data lama.
INSERT INTO officer_groups (name, level, region, period)
SELECT
  CASE
    WHEN level = 'pusat' THEN 'Pengurus Pusat'
    WHEN level = 'regional' THEN CONCAT('Pengurus ', COALESCE(NULLIF(region, ''), 'Regional'))
    ELSE CONCAT('Pengurus Daerah ', COALESCE(NULLIF(region, ''), ''))
  END,
  level,
  region,
  MAX(period)
FROM officers
GROUP BY level, region;

UPDATE officers o
JOIN officer_groups g
  ON g.level = o.level AND g.region <=> o.region
SET o.officer_group_id = g.id,
    o.slot_key = 'legacy',
    o.slot_number = o.id
WHERE o.officer_group_id IS NULL;

-- Siapkan tiga slot penasehat dan masing-masing satu slot jabatan lainnya.
INSERT INTO officers (officer_group_id, slot_key, slot_number, name, position_name, level, region)
SELECT g.id, slots.slot_key, slots.slot_number, '', slots.position_name, g.level, g.region
FROM officer_groups g
CROSS JOIN (
  SELECT 'penasehat' AS slot_key, 1 AS slot_number, 'Penasehat' AS position_name
  UNION ALL SELECT 'penasehat', 2, 'Penasehat'
  UNION ALL SELECT 'penasehat', 3, 'Penasehat'
  UNION ALL SELECT 'ketua', 1, 'Ketua'
  UNION ALL SELECT 'wakil_ketua', 1, 'Wakil Ketua'
  UNION ALL SELECT 'sekretaris', 1, 'Sekretaris'
  UNION ALL SELECT 'wakil_sekretaris', 1, 'Wakil Sekretaris'
  UNION ALL SELECT 'bendahara', 1, 'Bendahara'
  UNION ALL SELECT 'wakil_bendahara', 1, 'Wakil Bendahara'
) slots
WHERE NOT EXISTS (
  SELECT 1 FROM officers o
  WHERE o.officer_group_id = g.id
    AND o.slot_key = slots.slot_key
    AND o.slot_number = slots.slot_number
);
