-- Run ni nga file sa phpMyAdmin (SQL tab) kung naa nay existing database ninyo
-- Kini ra ang BAG-ONG changes para sa Google Login (dili na kinahanglan i-rerun ang tibuok migration)

ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE AFTER password_hash;
