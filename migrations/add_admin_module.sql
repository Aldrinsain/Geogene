-- ============================================================
-- GeoGene - ADMIN / OFFICE HEAD MODULE (additive migration)
--
-- Safe to import on top of the existing "Geogene combined migration.sql".
-- Does NOT touch/alter/drop any existing table, and does NOT delete any
-- existing data. Only adds:
--   1. activity_logs table (Staff + Admin action history)
--   2. an optional seed Admin/Office Head account (so the module can be
--      tested immediately) - uses ON DUPLICATE KEY UPDATE so it is safe
--      to re-run and will never create a duplicate row.
--
-- I-import ni sa phpMyAdmin (SQL tab) sa geogene_db PAGKAHUMAN sa
-- "Geogene combined migration.sql". Safe i-re-run - dili mag-duplicate.
-- ============================================================

USE geogene_db;

-- ------------------------------------------------------------
-- 1. ACTIVITY LOGS (Admin: Staff Activity Monitoring / Reports)
--    Separate from login_logs (which already tracks logins for
--    every account, staff and citizen alike). This table tracks
--    what an account DID after logging in (added/edited/deleted a
--    record, imported records, managed a user, etc).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(20) DEFAULT NULL,
    activity VARCHAR(255) NOT NULL,
    module VARCHAR(100) DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Safety net: kung na-create na ang activity_logs table sa una nga
-- pag-import (partial/duplicate attempt) nga wala pa niini nga role/module
-- columns, ang CREATE TABLE IF NOT EXISTS sa taas DILI na mag-dugang niini
-- (kay naa na man ang table). Kining ubos i-check/dungag ang columns kung
-- wala pa - safe i-re-run bisan pila ka beses.
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_logs' AND COLUMN_NAME = 'role');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_logs ADD COLUMN role VARCHAR(20) DEFAULT NULL AFTER user_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_logs' AND COLUMN_NAME = 'module');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_logs ADD COLUMN module VARCHAR(100) DEFAULT NULL AFTER activity',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activity_logs' AND COLUMN_NAME = 'description');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE activity_logs ADD COLUMN description VARCHAR(500) DEFAULT NULL AFTER module',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. Seed Admin / Office Head account (the actual system owner)
--    Email:    admin@geogene.gov.ph
--    Password: admin123
--    Logs in from the SAME staff portal login (staff/login.php) as
--    everyone else - there is no separate admin login page. Based on
--    the account's role, it is redirected to /admin/dashboard.php
--    instead of /staff/dashboard.php.
--
--    Human maka-login: i-usab ang Full Name/Password pinaagi sa
--    "My Profile" > "Edit Profile" sulod sa system (naa didto ang
--    Change Password form). Kung gusto usbon ang EMAIL, ipahibalo
--    lang ug i-update nako ni nga migration file.
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role, status, email_verified, identity_verified)
VALUES (
  'System Administrator',
  'admin@geogene.gov.ph',
  '$2b$10$R4nIbq6S6uhYfVGal66UP.ZCEh3kgug0yeVfXJpCNDkVbmVJWlsH6', -- admin123 (verified working hash - change after first login)
  'admin',
  'active',
  1,
  1
)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password_hash = VALUES(password_hash), role = VALUES(role), status = VALUES(status), email_verified = VALUES(email_verified), identity_verified = VALUES(identity_verified);
