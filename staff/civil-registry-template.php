<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="civil-registry-import-template.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens accented characters correctly

fputcsv($out, [
    'Registry Type', 'Registry Number', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 'Sex',
    'Date of Event', 'Place of Event', "Father's Name", "Mother's Name", "Spouse's Name", 'Barangay', 'Zone', 'Remarks',
]);

// One example row so staff can see the expected format.
fputcsv($out, [
    'Birth', 'BR-2026-00001', 'Juan', 'Santos', 'Dela Cruz', '', 'Male',
    '2026-01-15', 'Manolo Fortich, Bukidnon', 'Pedro Dela Cruz', 'Maria Santos', '', 'Alae', 'Zone 2', '',
]);

fclose($out);
exit;
