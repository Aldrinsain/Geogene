-- ============================================================
-- GeoGene - COMBINED MIGRATION (all-in-one, single SQL file)
-- Municipal Civil Registry Office (MCRO) - Manolo Fortich
--
-- This is the ONLY SQL file you need for this project. It already
-- includes: users (with address/civil_status columns), OTP,
-- civil registry records, identity verification, persons,
-- relationships, barangays, system_ratings, login logs,
-- user settings, and staff reports - plus seed data below.
--
-- I-import ni ISAHAN nga file sa phpMyAdmin (SQL tab).
-- Kung naa na kay existing geogene_db nga gikan sa daan/partial
-- import, i-DROP una ang database para klaro nga sugod (optional):
--   DROP DATABASE IF EXISTS geogene_db;
-- ============================================================

CREATE DATABASE IF NOT EXISTS geogene_db;
USE geogene_db;

-- ------------------------------------------------------------
-- 1. USERS (system accounts / applicants)
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','office_head','admin') DEFAULT 'user',
    status ENUM('pending','active','suspended') DEFAULT 'pending',
    email_verified TINYINT(1) DEFAULT 0,
    identity_verified TINYINT(1) DEFAULT 0,
    matched_record_id INT DEFAULT NULL,   -- MCRO record matched during Search Full Name (pre-registration)
    gender ENUM('male','female','prefer_not_to_say') DEFAULT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    civil_status ENUM('single','married','widowed','divorced') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    last_seen DATETIME DEFAULT NULL,      -- updated on every authenticated request; used to show online/offline status
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. EMAIL OTP VERIFICATION
-- ------------------------------------------------------------
CREATE TABLE otp_codes (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('email_verification','password_reset','login_2fa') DEFAULT 'email_verification',
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 3. CIVIL REGISTRY RECORDS (MCRO master data, used for
--    "Search Full Name" identity check + family tree source)
-- ------------------------------------------------------------
CREATE TABLE civil_registry_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    registry_type ENUM('birth','marriage','death') NOT NULL,
    registry_number VARCHAR(50) UNIQUE,
    book_no VARCHAR(20) DEFAULT NULL,
    page_no VARCHAR(20) DEFAULT NULL,
    city_municipality VARCHAR(100) DEFAULT NULL,
    date_prepared DATE DEFAULT NULL,
    full_name VARCHAR(255) DEFAULT NULL,   -- authoritative full name as imported/entered; used for all name-matching (family tree, map search)
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    sex ENUM('male','female'),
    civil_status ENUM('single','married','widowed','divorced') DEFAULT NULL,
    date_of_event DATE,               -- birth date / marriage date / death date
    place_of_event VARCHAR(255),      -- street / barangay, Manolo Fortich
    father_name VARCHAR(200),
    mother_name VARCHAR(200),
    spouse_name VARCHAR(200),
    barangay VARCHAR(100),
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- FK: users.matched_record_id -> civil_registry_records
-- (added after civil_registry_records exists, avoids table order issues)
ALTER TABLE users
  ADD CONSTRAINT fk_users_matched_record
  FOREIGN KEY (matched_record_id) REFERENCES civil_registry_records(record_id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- 4. IDENTITY VERIFICATION LOGS (name search + ID scan)
-- ------------------------------------------------------------
CREATE TABLE identity_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    matched_record_id INT DEFAULT NULL,
    search_full_name VARCHAR(200),
    id_image_path VARCHAR(255),
    verification_method ENUM('name_search','id_scan') NOT NULL,
    status ENUM('pending','verified','failed') DEFAULT 'pending',
    verified_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (matched_record_id) REFERENCES civil_registry_records(record_id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- 5. PERSONS (family tree nodes - linked to registry or manual)
-- ------------------------------------------------------------
CREATE TABLE persons (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,              -- linked system account, if any
    record_id INT DEFAULT NULL,            -- linked civil registry record, if any
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    sex ENUM('male','female'),
    birth_date DATE,
    death_date DATE DEFAULT NULL,
    status ENUM('living','deceased') DEFAULT 'living',
    barangay VARCHAR(100),
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (record_id) REFERENCES civil_registry_records(record_id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- 6. RELATIONSHIPS (edges between persons in the family tree)
-- ------------------------------------------------------------
CREATE TABLE relationships (
    relationship_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    related_person_id INT NOT NULL,
    relationship_type ENUM('parent','child','spouse','sibling') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES persons(person_id) ON DELETE CASCADE,
    FOREIGN KEY (related_person_id) REFERENCES persons(person_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7. BARANGAYS (22 barangays of Manolo Fortich, for map + filters)
-- ------------------------------------------------------------
CREATE TABLE barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7)
);

-- ------------------------------------------------------------
-- 8. SYSTEM RATINGS (users rate the GeoGene app/system, 1-5 stars)
-- ------------------------------------------------------------
CREATE TABLE system_ratings (
    rating_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL UNIQUE,
    stars       TINYINT NOT NULL,
    feedback    TEXT DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 9. SESSIONS / LOGIN LOGS (audit trail)
-- ------------------------------------------------------------
CREATE TABLE login_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 10. USER SETTINGS (theme preference, notifications, etc.)
-- ------------------------------------------------------------
CREATE TABLE user_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    dark_mode TINYINT(1) DEFAULT 0,
    email_notifications TINYINT(1) DEFAULT 1,
    profile_visibility ENUM('public','private') DEFAULT 'private',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 11. STAFF REPORTS (Staff Portal - Reports page)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(150) NOT NULL,
    description TEXT,
    generated_by INT,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- 12. INCREMENTAL / OPTIONAL MIGRATIONS
--     (para sa mga naa nay existing geogene_db gikan sa DAAN nga
--     schema, nga wala pa niini nga columns. SAFE ni sila i-run
--     bisan pila ka beses o bisan fresh install - naay check una
--     kung naa na ba ang column ganahon dugangon, kung naa na,
--     i-skip lang. Kombinasyon ni sa upat ka separate migration
--     files (add_civil_status_to_civil_registry.sql,
--     add_last_seen_to_users.sql, add_registry_book_page_fullname.sql,
--     clear_civil_registry_records.sql) - dili na nimo kinahanglan
--     i-run separately kung ni-import ka ani nga ISAHAN nga file.
-- ============================================================

-- ---- 12a. users.last_seen (online/offline status sa Dashboard) ----
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_seen');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN last_seen DATETIME DEFAULT NULL AFTER profile_photo',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---- 12b. civil_registry_records.civil_status ----
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'civil_status');
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE civil_registry_records ADD COLUMN civil_status ENUM('single','married','widowed','divorced') DEFAULT NULL AFTER sex",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---- 12c. civil_registry_records: book_no / page_no / city_municipality / date_prepared / full_name ----
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'book_no');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN book_no VARCHAR(20) DEFAULT NULL AFTER registry_number',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'page_no');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN page_no VARCHAR(20) DEFAULT NULL AFTER book_no',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'city_municipality');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN city_municipality VARCHAR(100) DEFAULT NULL AFTER page_no',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'date_prepared');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN date_prepared DATE DEFAULT NULL AFTER city_municipality',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'full_name');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN full_name VARCHAR(255) DEFAULT NULL AFTER date_prepared',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill full_name for any existing records nga wala pa (mao ra gihapon
-- ni ang gibuhat sa add_registry_book_page_fullname.sql, safe i-re-run)
UPDATE civil_registry_records
SET full_name = TRIM(CONCAT_WS(' ', first_name, middle_name, last_name, suffix))
WHERE full_name IS NULL OR full_name = '';

-- ---- 12d. (OPTIONAL / DESTRUCTIVE) Clear all civil_registry_records ----
-- Gikan sa clear_civil_registry_records.sql. Gi-COMMENT OUT ni by default
-- kay mag-delete ni tanan existing civil registry records. I-UNCOMMENT
-- lang ug i-run manually kung ganahan gyud nimo i-empty ang table
-- (e.g. para mahawan ang sample/demo rows before Import Excel / Add Record).
--
-- DELETE FROM civil_registry_records;
-- ALTER TABLE civil_registry_records AUTO_INCREMENT = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- ------------------------------------------------------------
-- Seed: 22 barangays of Manolo Fortich (names only - add lat/lng later)
-- ------------------------------------------------------------
INSERT INTO barangays (name) VALUES
('Alae'),('Agusan Canyon'),('Baclayon'),('Bantuanon'),('Diclum'),
('Damilag'),('Dahilayan'),('Dalirig'),('Guilang-guilang'),('Kalugmanan'),
('Lindaban'),('Lingi-on'),('Lunocan'),('Maluko'),('Mambatangan'),
('Mampayag'),('Mantibugao'),('Minsuro'),('San Miguel'),('Sankanan'),
('Sinaysayan'),('Ticala');

-- ------------------------------------------------------------
-- Seed: Sample Civil Registry Records (for testing / matching)
-- Kini una i-insert PAG-UNA sa mga user nga naay matched_record_id,
-- para dunay ma-match ang subquery sa ubos.
-- ------------------------------------------------------------
-- (Wala nay sample/demo civil registry records dinhi - ang tibuok
-- civil_registry_records table gitipigan nga BLANGKO sa fresh install.
-- I-populate kini gamit ang "Import Excel" feature sa Staff Portal
-- (Civil Registry Records > Import Excel), o pinaagi sa "+ Add Record".)

-- ------------------------------------------------------------
-- Sample regular user #1 (Search Name + ID scan already verified)
--   Email:    aldrin123@gmail.com
--   Password: (hash preserved from original schema.sql)
-- ------------------------------------------------------------
INSERT INTO users
(full_name, email, phone_number, password_hash, role, status, email_verified, identity_verified, matched_record_id)
VALUES
(
  'Aldrin Piloton Sain',
  'aldrin123@gmail.com',
  '09123456789',
  '$2b$10$K3P8MD/4p7D0Yzm32HzVBuU7KyYo7im5g9gpgoXSXgyS97ERcHqPi',
  'user',
  'active',
  1,
  1,
  (SELECT record_id FROM civil_registry_records WHERE first_name='Aldrin' AND last_name='Sain' LIMIT 1)
);

-- ------------------------------------------------------------
-- Sample Staff Account (Staff Portal login)
--   Email:    staff@geogene.gov.ph
--   Password: staff123
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role, status, email_verified, identity_verified)
VALUES (
  'Juan Dela Cruz',
  'staff@geogene.gov.ph',
  '$2b$10$cnmAZ6AH0p/CLFV87daeYOC2cQeTzGCTG2mvFeTI6ogHEAuG7sP5a', -- staff123
  'office_head',
  'active',
  1,
  1
)
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), status = VALUES(status), email_verified = VALUES(email_verified), identity_verified = VALUES(identity_verified);

-- Sample seed reports so the Reports page isn't empty on first load
INSERT INTO staff_reports (report_type, description, generated_by)
SELECT 'Civil Registry Summary', 'Summary of all registry records', user_id FROM users WHERE email='staff@geogene.gov.ph' LIMIT 1;
INSERT INTO staff_reports (report_type, description, generated_by)
SELECT 'Verified Records Report', 'List of all verified records', user_id FROM users WHERE email='staff@geogene.gov.ph' LIMIT 1;

-- ------------------------------------------------------------
-- Test Login Account #2 (pre-verified, skips Search Name / Scan ID)
--   Email:    aldrin123@example.com
--   Password: aldrin123
-- ------------------------------------------------------------
INSERT INTO users
(full_name, email, phone_number, password_hash, role, status, email_verified, identity_verified, matched_record_id)
VALUES
(
  'Aldrin Piloton Sain',
  'aldrin123@example.com',
  '09123456789',
  '$2b$10$K3P8MD/4p7D0Yzm32HzVBuU7KyYo7im5g9gpgoXSXgyS97ERcHqPi', -- aldrin123
  'user',
  'active',
  1,
  1,
  (SELECT record_id FROM civil_registry_records WHERE first_name='Aldrin' AND last_name='Sain' LIMIT 1)
);

-- Default settings row for Test Account #2
INSERT INTO user_settings (user_id, dark_mode)
VALUES ((SELECT user_id FROM users WHERE email='aldrin123@example.com'), 0);
-- ============================================================
-- GeoGene - Barangay Coordinates for Manolo Fortich, Bukidnon
-- I-import ni sa phpMyAdmin (SQL tab) sa geogene_db.
--
-- NOTE: Kini nga mga coordinates approximate/estimated placements
-- para sa matag barangay sa sulod sa Manolo Fortich (base sa
-- pangkinatibuk-ang lokasyon/direksyon gikan sa Poblacion).
-- Para sa final defense/production, mas maayo nga i-verify/i-refine
-- pinaagi sa tinuod nga GPS survey o Google Maps geocoding sa
-- eksakto nga barangay hall/centroid sa matag usa.
-- ============================================================
USE geogene_db;

UPDATE barangays SET latitude = 8.4090, longitude = 124.8510 WHERE name = 'Alae';
UPDATE barangays SET latitude = 8.3520, longitude = 124.9480 WHERE name = 'Agusan Canyon';
UPDATE barangays SET latitude = 8.3390, longitude = 124.8310 WHERE name = 'Baclayon';
UPDATE barangays SET latitude = 8.2980, longitude = 124.8720 WHERE name = 'Bantuanon';
UPDATE barangays SET latitude = 8.3750, longitude = 124.8450 WHERE name = 'Diclum';
UPDATE barangays SET latitude = 8.3616, longitude = 124.8088 WHERE name = 'Damilag';
UPDATE barangays SET latitude = 8.2200, longitude = 124.8600 WHERE name = 'Dahilayan';
UPDATE barangays SET latitude = 8.3010, longitude = 124.8990 WHERE name = 'Dalirig';
UPDATE barangays SET latitude = 8.3810, longitude = 124.8700 WHERE name = 'Guilang-guilang';
UPDATE barangays SET latitude = 8.4030, longitude = 124.9020 WHERE name = 'Kalugmanan';
UPDATE barangays SET latitude = 8.3520, longitude = 124.8780 WHERE name = 'Lindaban';
UPDATE barangays SET latitude = 8.3290, longitude = 124.8520 WHERE name = 'Lingi-on';
UPDATE barangays SET latitude = 8.3620, longitude = 124.8330 WHERE name = 'Lunocan';
UPDATE barangays SET latitude = 8.2870, longitude = 124.8310 WHERE name = 'Maluko';
UPDATE barangays SET latitude = 8.2680, longitude = 124.8790 WHERE name = 'Mambatangan';
UPDATE barangays SET latitude = 8.3080, longitude = 124.9210 WHERE name = 'Mampayag';
UPDATE barangays SET latitude = 8.2780, longitude = 124.8020 WHERE name = 'Mantibugao';
UPDATE barangays SET latitude = 8.4180, longitude = 124.8830 WHERE name = 'Minsuro';
UPDATE barangays SET latitude = 8.3650, longitude = 124.8670 WHERE name = 'San Miguel';
UPDATE barangays SET latitude = 8.3420, longitude = 124.8970 WHERE name = 'Sankanan';
UPDATE barangays SET latitude = 8.3910, longitude = 124.8280 WHERE name = 'Sinaysayan';
UPDATE barangays SET latitude = 8.2580, longitude = 124.8420 WHERE name = 'Ticala';
-- ==== Google OAuth support (dugang para sa Continue with Google) ====
ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE AFTER password_hash;
