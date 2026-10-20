-- Dugang og column para sa IKADUHANG hulagway (2nd photo) sa FRONT ug BACK
-- side sa "Scan & Upload Certificate" feature.
--
-- Sanglit ang camera-shot nga hulagway kay dali ra ma-blur o dili klaro sa
-- pipila ka bahin, gitugotan na karon ang staff nga mag-upload og 2 ka
-- litrato matag side (Front x2, Back x2 = 4 ka litrato tanan sa usa ka
-- pagpadala/submit). Ang OCR modagan sa TANAN nga na-upload nga litrato ug
-- i-combine ang nabasa nga text para mas kompleto/sakto ang auto-fill -
-- kung ang usa ka litrato blur sa usa ka bahin, kadaghanan mabasa gihapon
-- gikan sa ikaduhang litrato.
--
--   scan_image_path_2      -> 2nd photo sa FRONT side
--   scan_image_path_back_2 -> 2nd photo sa BACK side
--
-- Safe i-re-run - dili mag-error kung naa na ang column.

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_2');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN scan_image_path_2 VARCHAR(255) DEFAULT NULL AFTER scan_image_path',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'scan_image_path_back_2');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN scan_image_path_back_2 VARCHAR(255) DEFAULT NULL AFTER scan_image_path_back',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
