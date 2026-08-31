-- Run this once in phpMyAdmin (SQL tab) if you already have a GeoGene
-- database and don't want to re-import the whole "Geogene combined
-- migration.sql" file. It just adds the new Civil Status field used by
-- the "Add Civil Registry Record" form.

ALTER TABLE civil_registry_records
ADD COLUMN civil_status ENUM('single','married','widowed','divorced') DEFAULT NULL
AFTER sex;
