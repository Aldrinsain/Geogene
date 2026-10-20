-- GeoGene - OPTIONAL: Registry No. only has to be unique WITHIN the same registry type
-- ---------------------------------------------------------------------------------
-- Until now the database refused ANY two records with the same Registry No. - even a
-- Birth and a Marriage record, which are numbered in separate registry books. If Save
-- fails with  'Registry No. "..." is already saved for ... (marriage record #..)'  while
-- you are saving a birth (or the other way around), run this file once in phpMyAdmin.
-- Safe to run more than once.

SET @has_old = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records'
      AND INDEX_NAME = 'registry_number');
SET @sql = IF(@has_old > 0, 'ALTER TABLE civil_registry_records DROP INDEX registry_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_new = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records'
      AND INDEX_NAME = 'uq_registry_type_number');
SET @sql = IF(@has_new = 0,
    'ALTER TABLE civil_registry_records ADD UNIQUE KEY uq_registry_type_number (registry_type, registry_number)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
