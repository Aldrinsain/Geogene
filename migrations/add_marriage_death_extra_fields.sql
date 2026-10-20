-- Dugang og mga column para sa gi-simplify nga Add/Edit Record fields:
--
--   MARRIAGE - ang existing father_name/mother_name column kay para sa
--   HUSBAND's parents ra (naa na daan). Kining duha ka bag-o kay para sa
--   WIFE's parents:
--     wife_father_name  -> Name of Father (W)
--     wife_mother_name  -> Name of Mother (W)
--
--   DEATH - dugang detalye sa namatay nga tawo:
--     date_of_birth  -> Date of Birth (lahi sa date_of_event, nga mao ang
--                       gigamit para sa Date of Death)
--     age_at_death   -> Age at Time of Death
--     cause_of_death -> Cause of Death
--   (Place of Death reuses the existing generic "place_of_event" column -
--    walay bag-ong column gikinahanglan para niini.)
--
-- Safe i-re-run - dili mag-error kung naa na ang column.

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'wife_father_name');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN wife_father_name VARCHAR(255) DEFAULT NULL AFTER spouse_name',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'wife_mother_name');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN wife_mother_name VARCHAR(255) DEFAULT NULL AFTER wife_father_name',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'date_of_birth');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN date_of_birth DATE DEFAULT NULL AFTER date_of_event',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'age_at_death');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN age_at_death VARCHAR(20) DEFAULT NULL AFTER date_of_birth',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records' AND COLUMN_NAME = 'cause_of_death');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE civil_registry_records ADD COLUMN cause_of_death VARCHAR(255) DEFAULT NULL AFTER age_at_death',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
