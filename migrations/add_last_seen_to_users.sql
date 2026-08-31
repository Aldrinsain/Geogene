-- Run this once in phpMyAdmin (SQL tab) if you already have a GeoGene
-- database and don't want to re-import the whole "Geogene combined
-- migration.sql" file. It adds the last_seen column used to show whether
-- a user is currently online (green dot) or offline (silver dot) on the
-- staff Dashboard.

ALTER TABLE users
ADD COLUMN last_seen DATETIME DEFAULT NULL
AFTER profile_photo;
