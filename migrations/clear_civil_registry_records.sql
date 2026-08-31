-- Run this once in phpMyAdmin (SQL tab) to clear out ALL existing civil
-- registry records (the sample/demo/testing rows) so the table starts
-- empty, ready for your real data via Import Excel or Add Record.
--
-- Safe to run: any user account that was linked to one of these records
-- (matched_record_id) will simply have that link cleared (set to NULL)
-- instead of causing an error - it's not deleting any user accounts.

DELETE FROM civil_registry_records;

-- Optional: reset the auto-increment counter so new records start again
-- from record_id 1 (purely cosmetic, safe to skip if you don't care).
ALTER TABLE civil_registry_records AUTO_INCREMENT = 1;
