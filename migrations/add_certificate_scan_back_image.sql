-- Dugang og column para sa BACK side nga scanned image sa certificate
-- (ang scan_image_path nga naa na kay para sa FRONT side ra).
-- Ang orihinal na Certificate of Death/Marriage forms kay naay Front ug
-- Back nga side sa long bond paper, mao nga kinahanglan puwede mo-upload
-- ang staff og duha ka images (front + back) sa Scan & Upload Certificate.
-- Safe i-re-run - dili mag-error kung naa na ang column.

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_back');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN scan_image_path_back VARCHAR(255) DEFAULT NULL AFTER scan_image_path',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
