-- GeoGene - adds EVERY column the Civil Registry pages use, if it is missing
-- ---------------------------------------------------------------------------
-- Fixes:  Unknown column 'date_of_birth' in 'field list'   (and the same for any other column)
-- while saving a Birth / Marriage / Death record.
--
-- You normally do NOT need this file: the system now adds missing columns by itself the first time
-- a staff member opens any staff page (see staff/includes/schema-ensure.php). Use it only if you
-- prefer to do it by hand in phpMyAdmin (Import / SQL tab). Safe to run more than once:
-- columns that already exist are skipped and nothing is deleted or changed.


SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'book_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `book_no` VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'page_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `page_no` VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'city_municipality');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `city_municipality` VARCHAR(100) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'date_prepared');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `date_prepared` DATE DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'full_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `full_name` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'suffix');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `suffix` VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'sex');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `sex` ENUM(''male'',''female'') DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'civil_status');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `civil_status` ENUM(''single'',''married'',''widowed'',''divorced'') DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'date_of_event');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `date_of_event` DATE DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'date_of_birth');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `date_of_birth` DATE DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'place_of_event');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `place_of_event` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'father_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `father_name` VARCHAR(200) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'mother_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `mother_name` VARCHAR(200) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'spouse_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `spouse_name` VARCHAR(200) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'wife_father_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `wife_father_name` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'wife_mother_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `wife_mother_name` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'age_at_death');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `age_at_death` VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'cause_of_death');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `cause_of_death` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'barangay');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `barangay` VARCHAR(100) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'zone');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `zone` VARCHAR(50) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'remarks');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `remarks` TEXT DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `scan_image_path` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_2');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `scan_image_path_2` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_back');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `scan_image_path_back` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_back_2');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `scan_image_path_back_2` VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'certificate_data');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `certificate_data` LONGTEXT DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'verified_by');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `verified_by` INT DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'verified_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE civil_registry_records ADD COLUMN `verified_at` DATETIME DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- fill full_name where it is empty ("Given Middle Surname")
UPDATE civil_registry_records
SET full_name = TRIM(CONCAT_WS(' ', first_name, middle_name, last_name))
WHERE full_name IS NULL OR full_name = '';
