-- Dugang og "zone" (Zone/Purok/Sitio) column sa civil_registry_records,
-- para sa mas detalyado nga location info gamit sa Geographic Map (Barangay + Zone).
-- Safe i-re-run - dili mag-error kung naa na ang column.

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'zone');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN zone VARCHAR(50) DEFAULT NULL AFTER barangay',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
