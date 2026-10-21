-- Database Pomparan Manik Raja
-- Jalankan file ini untuk instalasi baru maupun untuk menambahkan kolom
-- yang dibutuhkan versi aplikasi saat ini (MySQL 8 / MariaDB).
CREATE DATABASE IF NOT EXISTS marga_manik
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE marga_manik;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  email_verified_at DATETIME DEFAULT NULL,
  session_version INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS email VARCHAR(190) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS session_version INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS admin_password_resets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_password_resets_admin (admin_id, created_at),
  INDEX idx_admin_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_email_verifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  email VARCHAR(190) NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_email_verifications_admin (admin_id, created_at),
  INDEX idx_admin_email_verifications_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_recovery_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_ip_hash CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_recovery_requests_ip_date (request_ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  panggoaran VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  father_name VARCHAR(150) DEFAULT NULL,
  grandfather_name VARCHAR(150) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  husband_name VARCHAR(150) DEFAULT NULL,
  husband_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  wife_name VARCHAR(150) DEFAULT NULL,
  wife_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  children_count INT UNSIGNED NOT NULL DEFAULT 0,
  wilayah_id INT DEFAULT NULL,
  daerah_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wilayah (
  id INT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  INDEX idx_wilayah_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daerah (
  id INT PRIMARY KEY,
  wilayah_id INT NOT NULL,
  jenis ENUM('kota','kabupaten') NOT NULL,
  nama VARCHAR(150) NOT NULL,
  INDEX idx_daerah_wilayah (wilayah_id),
  INDEX idx_daerah_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT DEFAULT NULL,
  full_name VARCHAR(150) NOT NULL,
  panggoaran VARCHAR(150) DEFAULT NULL,
  member_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  phone VARCHAR(40) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  father_name VARCHAR(150) DEFAULT NULL,
  grandfather_name VARCHAR(150) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  husband_name VARCHAR(150) DEFAULT NULL,
  husband_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  wife_name VARCHAR(150) DEFAULT NULL,
  wife_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  children_count INT UNSIGNED NOT NULL DEFAULT 0,
  wilayah_id INT DEFAULT NULL,
  daerah_id INT DEFAULT NULL,
  deleted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_members_registration (registration_id),
  INDEX idx_members_deleted_name (deleted_at, full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE members
  ADD COLUMN IF NOT EXISTS registration_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS panggoaran VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS member_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  ADD COLUMN IF NOT EXISTS phone VARCHAR(40) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS grandfather_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS husband_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS husband_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  ADD COLUMN IF NOT EXISTS wife_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS wife_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  ADD COLUMN IF NOT EXISTS children_count INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS wilayah_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS daerah_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  category ENUM('berita','sejarah') NOT NULL DEFAULT 'berita',
  image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_posts_category_created (category, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS officers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  officer_group_id INT DEFAULT NULL,
  slot_key VARCHAR(40) DEFAULT NULL,
  slot_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
  parent_officer_id INT DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  position_name VARCHAR(100) NOT NULL,
  period VARCHAR(80) DEFAULT NULL,
  level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat',
  region VARCHAR(150) DEFAULT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  INDEX idx_officers_level_region (level, region),
  INDEX idx_officers_parent (parent_officer_id),
  INDEX idx_officers_group_slots (officer_group_id, slot_key, slot_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS officer_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat',
  region VARCHAR(150) DEFAULT NULL,
  period VARCHAR(80) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_officer_groups_level_name (level, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  type ENUM('foto','video') NOT NULL DEFAULT 'foto',
  category ENUM('beranda','album') NOT NULL DEFAULT 'album',
  album_name VARCHAR(200) DEFAULT NULL,
  video_url VARCHAR(500) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_type_category_created (type, category, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade kolom pada database lama tanpa menghapus data yang sudah ada.
ALTER TABLE registrations
  ADD COLUMN IF NOT EXISTS panggoaran VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS husband_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS husband_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  ADD COLUMN IF NOT EXISTS wife_name VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS wife_status ENUM('hidup','meninggal') NOT NULL DEFAULT 'hidup',
  ADD COLUMN IF NOT EXISTS children_count INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS wilayah_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS daerah_id INT DEFAULT NULL;

-- Data yang ditolak disimpan terpisah dari antrean pendaftar aktif.
CREATE TABLE IF NOT EXISTS temporary_registrations LIKE registrations;
ALTER TABLE temporary_registrations
  ADD COLUMN IF NOT EXISTS moved_to_temporary_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS decided_by INT DEFAULT NULL;

-- Jejak keputusan menyimpan salinan lengkap pendaftaran sebelum antrean dibersihkan.
CREATE TABLE IF NOT EXISTS registration_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  decision ENUM('approved','rejected') NOT NULL,
  registration_data LONGTEXT NOT NULL,
  decided_by INT DEFAULT NULL,
  decided_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_registration_logs_decided (decided_at),
  INDEX idx_registration_logs_decision (decision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE posts
  ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL;

ALTER TABLE officers
  ADD COLUMN IF NOT EXISTS parent_officer_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS officer_group_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS slot_key VARCHAR(40) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS slot_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat',
  ADD COLUMN IF NOT EXISTS region VARCHAR(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL;

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
  MODIFY COLUMN level ENUM('pusat','regional','daerah') NOT NULL DEFAULT 'pusat';

ALTER TABLE gallery
  ADD COLUMN IF NOT EXISTS type ENUM('foto','video') NOT NULL DEFAULT 'foto',
  ADD COLUMN IF NOT EXISTS category ENUM('beranda','album') NOT NULL DEFAULT 'album',
  ADD COLUMN IF NOT EXISTS album_name VARCHAR(200) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL,
  MODIFY COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- Migrasikan keputusan lama ke arsip, lalu kosongkan antrean aktif.
START TRANSACTION;

INSERT INTO members (
  registration_id, full_name, panggoaran, member_status, phone, email, city,
  father_name, grandfather_name, notes, status, husband_name,
  husband_status, wife_name, wife_status, children_count, wilayah_id, daerah_id
)
SELECT r.id, r.full_name, r.panggoaran, 'hidup', r.phone, r.email, r.city,
       r.father_name, r.grandfather_name, r.notes, 'active', r.husband_name,
       r.husband_status, r.wife_name, r.wife_status, r.children_count,
       r.wilayah_id, r.daerah_id
FROM registrations r
LEFT JOIN members m ON m.registration_id = r.id
WHERE r.status = 'approved' AND m.id IS NULL;

INSERT INTO temporary_registrations (
  id, full_name, panggoaran, phone, email, city, father_name, grandfather_name, notes,
  status, husband_name, husband_status, wife_name, wife_status,
  children_count, wilayah_id, daerah_id, created_at
)
SELECT r.id, r.full_name, r.panggoaran, r.phone, r.email, r.city, r.father_name,
       r.grandfather_name, r.notes, 'rejected', r.husband_name,
       r.husband_status, r.wife_name, r.wife_status, r.children_count,
       r.wilayah_id, r.daerah_id, r.created_at
FROM registrations r
LEFT JOIN temporary_registrations t ON t.id = r.id
WHERE r.status = 'rejected' AND t.id IS NULL;

INSERT INTO registration_logs (registration_id, decision, registration_data, decided_by)
SELECT r.id, r.status, JSON_OBJECT(
         'id', r.id, 'full_name', r.full_name, 'panggoaran', r.panggoaran, 'phone', r.phone,
         'email', r.email, 'city', r.city, 'father_name', r.father_name,
         'grandfather_name', r.grandfather_name, 'notes', r.notes,
         'status', r.status, 'husband_name', r.husband_name,
         'husband_status', r.husband_status, 'wife_name', r.wife_name,
         'wife_status', r.wife_status, 'children_count', r.children_count,
         'wilayah_id', r.wilayah_id, 'daerah_id', r.daerah_id,
         'created_at', r.created_at
       ), NULL
FROM registrations r
LEFT JOIN registration_logs l
  ON l.registration_id = r.id AND l.decision = r.status
WHERE r.status IN ('approved','rejected') AND l.id IS NULL;

DELETE FROM registrations WHERE status IN ('approved','rejected');
COMMIT;

INSERT INTO posts (title, body, category)
SELECT 'Selamat Datang di Website Pomparan Manik Raja',
       'Website ini menjadi ruang informasi, komunikasi, dan dokumentasi kegiatan komunitas Pomparan Manik Raja. Silakan sesuaikan tulisan ini melalui dashboard admin.',
       'berita'
WHERE NOT EXISTS (SELECT 1 FROM posts);
