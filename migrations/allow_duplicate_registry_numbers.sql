-- GeoGene - allow the SAME Registry No. on more than one record (run once in phpMyAdmin > SQL).
-- Real civil-registry books repeat numbers (several births can share one). The staff pages already
-- do this automatically; use this file only if the database user is not allowed to ALTER tables.
-- Each statement is safe: if phpMyAdmin says "Can't DROP ... check that column/key exists", that
-- index is already gone - just continue with the next line.

ALTER TABLE civil_registry_records DROP INDEX registry_number;
ALTER TABLE civil_registry_records DROP INDEX uq_registry_type_number;

-- keep searching by Registry No. fast (a normal, NON-unique index)
ALTER TABLE civil_registry_records ADD INDEX idx_registry_number (registry_number);
