-- GeoGene - Forgot Password security upgrade (run ONCE in phpMyAdmin > SQL).
-- config/password-reset.php also does this automatically, so you only need
-- this file if the database user is not allowed to ALTER tables.
-- Safe: registration OTPs (email_verification) keep working unchanged.

ALTER TABLE otp_codes MODIFY otp_code VARCHAR(255) NOT NULL;          -- room for password_hash() of the code
ALTER TABLE otp_codes ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE otp_codes ADD COLUMN request_ip VARCHAR(45) NULL;
-- If phpMyAdmin says "Duplicate column name 'attempts'" (or request_ip), that line was already applied - ignore it.
