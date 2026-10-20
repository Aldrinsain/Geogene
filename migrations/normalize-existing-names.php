<?php
/**
 * GeoGene - ONE-TIME clean-up of names that are ALREADY saved
 * ------------------------------------------------------------
 * New records are saved as   "Given Name  Middle Name  Surname"   in Mixed Case.
 * Older records may still be  "SAIN, ALDRIN PILOTON"  (Surname first, ALL CAPS).
 * The system already DISPLAYS and MATCHES both correctly, so running this is
 * optional - it just rewrites the saved text so the database itself is clean too
 * (search, export, phpMyAdmin, etc.).
 *
 * What it changes (civil_registry_records):
 *   first_name / middle_name / last_name  -> Mixed Case
 *   full_name                             -> "Given Middle Surname"
 *   father_name / mother_name / spouse_name / wife_father_name / wife_mother_name
 *                                         -> "Given Middle Surname", Mixed Case
 *   barangay                              -> exact spelling from the barangays table
 *   place_of_event                        -> Mixed Case
 * and users.full_name for citizen accounts -> "Given Middle Surname", Mixed Case.
 *
 * It never deletes anything and never touches dates, registry numbers or scans.
 *
 * HOW TO RUN (XAMPP): open "XAMPP Control Panel" > Shell, then:
 *     cd C:\xampp\htdocs\geogene
 *     php migrations\normalize-existing-names.php --dry-run     (preview only)
 *     php migrations\normalize-existing-names.php               (apply)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this from the command line.\n"); }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';

$dry = in_array('--dry-run', $argv, true);
echo $dry ? "DRY RUN - nothing will be changed.\n\n" : "Applying changes...\n\n";

$cols = ['first_name', 'middle_name', 'last_name', 'full_name', 'father_name', 'mother_name',
         'spouse_name', 'wife_father_name', 'wife_mother_name', 'barangay', 'place_of_event'];
$have = array_column($pdo->query("SHOW COLUMNS FROM civil_registry_records")->fetchAll(), 'Field');
$cols = array_values(array_intersect($cols, $have));

$changedRows = 0; $shown = 0;
$rows = $pdo->query("SELECT record_id, " . implode(', ', $cols) . " FROM civil_registry_records")->fetchAll();
foreach ($rows as $r) {
    $new = [];
    foreach (['first_name', 'middle_name', 'last_name'] as $c) if (isset($r[$c])) $new[$c] = gg_title_case($r[$c]);
    foreach (['first_name', 'last_name'] as $c) if (!isset($new[$c]) || $new[$c] === '') $new[$c] = $r[$c] ?? '';
    if (in_array('full_name', $cols, true)) {
        $fn = formatFullName($new['last_name'] ?? '', $new['first_name'] ?? '', $new['middle_name'] ?? '');
        $new['full_name'] = $fn !== '' ? $fn : gg_display_name($r['full_name'] ?? '');
    }
    foreach (['father_name', 'mother_name', 'spouse_name', 'wife_father_name', 'wife_mother_name'] as $c) {
        if (in_array($c, $cols, true)) $new[$c] = gg_clean_person_name($r[$c] ?? '');
    }
    if (in_array('barangay', $cols, true))       $new['barangay'] = gg_canonical_barangay($pdo, $r['barangay'] ?? '');
    if (in_array('place_of_event', $cols, true)) $new['place_of_event'] = trim((string) ($r['place_of_event'] ?? '')) === '' ? null : gg_title_case($r['place_of_event']);

    $set = []; $vals = [];
    foreach ($new as $c => $v) {
        $old = $r[$c] ?? null;
        if ((string) $old !== (string) $v) { $set[] = "$c = ?"; $vals[] = ($v === '' ? null : $v); }
    }
    if (!$set) continue;
    $changedRows++;
    if ($shown++ < 8) echo sprintf("  #%d  %s  ->  %s\n", $r['record_id'], $r['full_name'] ?? '', $new['full_name'] ?? '');
    if (!$dry) {
        $vals[] = $r['record_id'];
        $pdo->prepare("UPDATE civil_registry_records SET " . implode(', ', $set) . " WHERE record_id = ?")->execute($vals);
    }
}
echo "\nCivil registry records " . ($dry ? 'that WOULD change' : 'updated') . ": $changedRows of " . count($rows) . "\n";

$users = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'user'")->fetchAll();
$uChanged = 0;
foreach ($users as $u) {
    $n = gg_display_name($u['full_name']);
    if ($n !== '' && $n !== $u['full_name']) {
        $uChanged++;
        if (!$dry) $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?")->execute([$n, $u['user_id']]);
    }
}
echo "Citizen account names " . ($dry ? 'that WOULD change' : 'updated') . ": $uChanged of " . count($users) . "\n";
echo $dry ? "\nRun again without --dry-run to apply.\n" : "\nDone.\n";
