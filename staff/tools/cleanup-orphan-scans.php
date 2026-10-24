<?php
/**
 * Removes certificate scan photos that no record uses.
 *
 * Every photo staff scan is stored right away, even if the row is later removed from the bulk review table or
 * the batch is never saved, so unused photos (with personal data on them) pile up in uploads/civil-registry-scans.
 * This finds the photos that NO record points to and are older than N days (a photo from an unsaved batch that is
 * still in someone's "Continue unsaved batch" draft is protected by that age limit).
 *
 * Run from a terminal (it refuses to run from the web):
 *   php staff/tools/cleanup-orphan-scans.php             -> only LISTS what it would delete (dry run)
 *   php staff/tools/cleanup-orphan-scans.php --delete    -> really deletes
 *   php staff/tools/cleanup-orphan-scans.php --days=14   -> age limit in days (default 7)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Command line only.\n");
}
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/scan-url.php';

$days = 7;
$doDelete = false;
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--delete') $doDelete = true;
    elseif (preg_match('/^--days=(\d+)$/', $a, $m)) $days = max(1, (int) $m[1]);
    else { fwrite(STDERR, "Unknown option: $a\n"); exit(1); }
}

$dir = realpath(__DIR__ . '/../../uploads/civil-registry-scans');
if (!$dir) { echo "Folder uploads/civil-registry-scans not found - nothing to do.\n"; exit(0); }

// every photo name any record points to
$used = [];
foreach (['scan_image_path', 'scan_image_path_2', 'scan_image_path_back', 'scan_image_path_back_2'] as $col) {
    try {
        $st = $pdo->query("SELECT $col FROM civil_registry_records WHERE $col IS NOT NULL AND $col <> ''");
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $p) $used[basename(str_replace('\\', '/', (string) $p))] = true;
    } catch (Throwable $e) {
        echo "(column $col not found - skipped)\n";
    }
}

$cutoff = time() - $days * 86400;
$orphans = []; $bytes = 0; $kept = 0; $young = 0;
foreach (scandir($dir) as $f) {
    if (!preg_match(GG_SCAN_FILE_PATTERN, $f)) continue;      // never touches anything that isn't a scan photo
    if (isset($used[$f])) { $kept++; continue; }
    $full = $dir . DIRECTORY_SEPARATOR . $f;
    if (filemtime($full) > $cutoff) { $young++; continue; }
    $orphans[] = $full; $bytes += filesize($full);
}

printf("Photos used by a record : %d\n", $kept);
printf("Unused but newer than %d day(s) (kept): %d\n", $days, $young);
printf("Unused and older        : %d  (%.1f MB)\n", count($orphans), $bytes / 1048576);
if (!$orphans) exit(0);
if (!$doDelete) {
    foreach (array_slice($orphans, 0, 15) as $o) echo "  would delete: " . basename($o) . "\n";
    if (count($orphans) > 15) echo "  ... and " . (count($orphans) - 15) . " more\n";
    echo "Dry run only. Add --delete to remove them.\n";
    exit(0);
}
$deleted = 0;
foreach ($orphans as $o) if (@unlink($o)) $deleted++;
echo "Deleted $deleted of " . count($orphans) . " file(s).\n";
