-- Dugang og mga column sa civil_registry_records para sa bag-ong
-- "Scan & Upload Certificate" feature (OCR auto-fill + staff verify workflow).
--   scan_image_path   -> path sa gi-upload nga certificate image (birth/marriage/death)
--   certificate_data  -> tanang detalyado nga fields sa OFFICIAL na form (JSON),
--                        gamiton sa exact-format nga print/view sa certificate
--   verified_by       -> unsang staff account ang ni-click "Mark as Verified"
--   verified_at       -> kanus-a na-verify/na-save ang record
-- Safe i-re-run - dili mag-error kung naa na ang column.

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN scan_image_path VARCHAR(255) DEFAULT NULL AFTER remarks',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'certificate_data');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN certificate_data LONGTEXT DEFAULT NULL AFTER scan_image_path',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'verified_by');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN verified_by INT DEFAULT NULL AFTER certificate_data',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'verified_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN verified_at DATETIME DEFAULT NULL AFTER verified_by',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
