-- Run this once in phpMyAdmin (SQL tab) if you already have a GeoGene
-- database. Adds the columns needed to match the real MCRO Excel formats
-- (Book No., Page No., City/Municipality, Date Prepared) plus a `full_name`
-- column that becomes the single source of truth for name-matching across
-- Civil Registry Records, Generate Family Tree, and the Geographic Map.

ALTER TABLE civil_registry_records
ADD COLUMN book_no VARCHAR(20) DEFAULT NULL AFTER registry_number,
ADD COLUMN page_no VARCHAR(20) DEFAULT NULL AFTER book_no,
ADD COLUMN city_municipality VARCHAR(100) DEFAULT NULL AFTER page_no,
ADD COLUMN date_prepared DATE DEFAULT NULL AFTER city_municipality,
ADD COLUMN full_name VARCHAR(255) DEFAULT NULL AFTER date_prepared;

-- Backfill full_name for any records that already exist (imported earlier,
-- or added manually) so the whole table has a consistent full_name value
-- to match against.
UPDATE civil_registry_records
SET full_name = TRIM(CONCAT_WS(' ', first_name, middle_name, last_name, suffix))
WHERE full_name IS NULL OR full_name = '';
