<?php
/**
 * GeoGene - makes sure civil_registry_records has EVERY column the system uses.
 * ---------------------------------------------------------------------------
 * Some databases were set up before later features existed (for example without
 * date_of_birth, wife_father_name, scan_image_path, certificate_data ...). Saving
 * a record then failed with:  Unknown column 'date_of_birth' in 'field list'.
 *
 * gg_ensure_registry_schema() looks at the table and ADDS whatever is missing (nothing is
 * deleted or changed). It is safe to run any number of times and is called automatically
 * from the staff guard, so no migration has to be run by hand.
 * The same list is available as a plain SQL file:
 *     migrations/add_all_missing_registry_columns.sql
 */

if (!function_exists('gg_allow_duplicate_registry_numbers')) {
    /**
     * Real civil-registry books repeat Registry Nos. (several births can carry the same number),
     * so civil_registry_records must NOT reject a second record with the same Registry No.
     * This drops every UNIQUE index that contains registry_number (the old single-column one and
     * the optional (registry_type, registry_number) one) and keeps a normal, non-unique index so
     * searching by Registry No. stays fast. Nothing else is touched; safe to run any number of times.
     * Never throws: if the DB user may not ALTER, it is only logged (run
     * migrations/allow_duplicate_registry_numbers.sql in phpMyAdmin instead).
     */
    function gg_allow_duplicate_registry_numbers(PDO $pdo): bool
    {
        static $done = null;
        if ($done !== null) return $done;
        try {
            $rows = $pdo->query("SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX
                                 FROM information_schema.STATISTICS
                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records'
                                   AND INDEX_NAME <> 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) return $done = true;            // table missing/has no indexes: nothing to do

            $idx = [];
            foreach ($rows as $r) {
                $idx[$r['INDEX_NAME']]['unique'] = ((int) $r['NON_UNIQUE'] === 0);
                $idx[$r['INDEX_NAME']]['cols'][(int) $r['SEQ_IN_INDEX']] = strtolower($r['COLUMN_NAME']);
            }
            foreach ($idx as $name => $info) {
                if ($info['unique'] && in_array('registry_number', $info['cols'], true)) {
                    $pdo->exec("ALTER TABLE civil_registry_records DROP INDEX `" . str_replace('`', '', $name) . "`");
                    unset($idx[$name]);
                }
            }
            $hasLookup = false;
            foreach ($idx as $info) {
                if (($info['cols'][1] ?? '') === 'registry_number') { $hasLookup = true; break; }
            }
            if (!$hasLookup) {
                $pdo->exec("ALTER TABLE civil_registry_records ADD INDEX idx_registry_number (registry_number)");
            }
            return $done = true;
        } catch (Throwable $e) {
            error_log('[GeoGene import] could not allow duplicate Registry Nos: ' . $e->getMessage()
                . ' -> run migrations/allow_duplicate_registry_numbers.sql in phpMyAdmin');
            return $done = false;
        }
    }
}

if (!function_exists('gg_ensure_registry_schema')) {
    /** column => definition (everything the Civil Registry pages read or write) */
    function gg_registry_required_columns(): array
    {
        return [
            'book_no'                => "VARCHAR(20) DEFAULT NULL",
            'page_no'                => "VARCHAR(20) DEFAULT NULL",
            'city_municipality'      => "VARCHAR(100) DEFAULT NULL",
            'date_prepared'          => "DATE DEFAULT NULL",
            'full_name'              => "VARCHAR(255) DEFAULT NULL",
            'suffix'                 => "VARCHAR(20) DEFAULT NULL",
            'sex'                    => "ENUM('male','female') DEFAULT NULL",
            'civil_status'           => "ENUM('single','married','widowed','divorced') DEFAULT NULL",
            'date_of_event'          => "DATE DEFAULT NULL",
            'date_of_birth'          => "DATE DEFAULT NULL",
            'place_of_event'         => "VARCHAR(255) DEFAULT NULL",
            'father_name'            => "VARCHAR(200) DEFAULT NULL",
            'mother_name'            => "VARCHAR(200) DEFAULT NULL",
            'spouse_name'            => "VARCHAR(200) DEFAULT NULL",
            'wife_father_name'       => "VARCHAR(255) DEFAULT NULL",
            'wife_mother_name'       => "VARCHAR(255) DEFAULT NULL",
            'age_at_death'           => "VARCHAR(20) DEFAULT NULL",
            'cause_of_death'         => "VARCHAR(255) DEFAULT NULL",
            'barangay'               => "VARCHAR(100) DEFAULT NULL",
            'zone'                   => "VARCHAR(50) DEFAULT NULL",
            'remarks'                => "TEXT DEFAULT NULL",
            'scan_image_path'        => "VARCHAR(255) DEFAULT NULL",
            'scan_image_path_2'      => "VARCHAR(255) DEFAULT NULL",
            'scan_image_path_back'   => "VARCHAR(255) DEFAULT NULL",
            'scan_image_path_back_2' => "VARCHAR(255) DEFAULT NULL",
            'certificate_data'       => "LONGTEXT DEFAULT NULL",
            'verified_by'            => "INT DEFAULT NULL",
            'verified_at'            => "DATETIME DEFAULT NULL",
        ];
    }

    /** Names of required columns that the table does not have yet. */
    function gg_registry_missing_columns(PDO $pdo): array
    {
        $have = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civil_registry_records'")
                    ->fetchAll(PDO::FETCH_COLUMN);
        if (!$have) return [];                                  // table not created yet: nothing to fix here
        $have = array_flip(array_map('strtolower', $have));
        $missing = [];
        foreach (gg_registry_required_columns() as $col => $def) {
            if (!isset($have[strtolower($col)])) $missing[] = $col;
        }
        return $missing;
    }

    /**
     * Adds the missing columns. Returns the names of the columns it added
     * (an empty array when the table was already complete).
     * Never throws: if the database user is not allowed to ALTER, the problem is only logged
     * and the normal (clear) error is shown when the record is saved.
     */
    function gg_ensure_registry_schema(PDO $pdo): array
    {
        static $done = null;
        if ($done !== null) return $done;
        $added = [];
        try {
            $defs = gg_registry_required_columns();
            foreach (gg_registry_missing_columns($pdo) as $col) {
                try {
                    $pdo->exec("ALTER TABLE civil_registry_records ADD COLUMN `$col` " . $defs[$col]);
                    $added[] = $col;
                } catch (Throwable $e) {
                    error_log("gg_ensure_registry_schema: could not add $col: " . $e->getMessage());
                }
            }
            if (in_array('full_name', $added, true)) {
                // new full_name column: fill it, in "Given Middle Surname" order
                $pdo->exec("UPDATE civil_registry_records
                            SET full_name = TRIM(CONCAT_WS(' ', first_name, middle_name, last_name))
                            WHERE full_name IS NULL OR full_name = ''");
            }
            if ($added) error_log('gg_ensure_registry_schema: added columns: ' . implode(', ', $added));
        } catch (Throwable $e) {
            error_log('gg_ensure_registry_schema failed: ' . $e->getMessage());
        }
        return $done = $added;
    }
}
