<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

// Only accept POST (a plain link/GET could be triggered accidentally by a
// browser prefetch or a crawler - deletion should never happen from a GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: civil-registry.php');
    exit;
}

$id = (int) ($_POST['record_id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM civil_registry_records WHERE record_id = ?');
    $stmt->execute([$id]);
}

// Send the staff member back to exactly where they were (same search /
// type filter / page) instead of resetting the list to page 1.
$redirectParams = [];
// Ibalik ang tanang active filters (type, barangay, year, status, date, sex, search...)
$allowedKeys = ['q', 'qby', 'type', 'barangay', 'year', 'status', 'sex', 'dp', 'dp_from', 'dp_to'];
if (!empty($_POST['return_qs'])) {
    parse_str((string) $_POST['return_qs'], $prev);
    foreach ($allowedKeys as $k) {
        if (isset($prev[$k]) && is_string($prev[$k]) && $prev[$k] !== '') $redirectParams[$k] = $prev[$k];
    }
}
// legacy fields (kung naay old form nga wala pa ma-refresh)
if (empty($redirectParams['q']) && !empty($_POST['q']))       $redirectParams['q'] = $_POST['q'];
if (empty($redirectParams['type']) && !empty($_POST['type'])) $redirectParams['type'] = $_POST['type'];
if (!empty($_POST['page'])) $redirectParams['page'] = $_POST['page'];
$redirectParams['deleted'] = 1;

header('Location: civil-registry.php?' . http_build_query($redirectParams));
exit;
