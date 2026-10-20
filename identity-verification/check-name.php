<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules (Given Middle Surname, Mixed Case)
header('Content-Type: application/json');

$fullName = trim($_GET['full_name'] ?? '');
if ($fullName === '') {
    echo json_encode(['found' => false]);
    exit;
}

// "Juan Santos Dela Cruz" - every typed word must be in the person's name, in ANY
// order and ANY case ("DELA CRUZ, JUAN" and "juan dela cruz" find the same record).
$fullName = gg_display_name($fullName);                     // Given Middle Surname, Mixed Case
$parts = array_values(array_filter(preg_split('/[\s,\/]+/', $fullName), fn($p) => $p !== ''));
if (!$parts) { echo json_encode(['found' => false]); exit; }

$where = [];
$params = [];
foreach ($parts as $p) {
    $where[] = "CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?";
    $params[] = '%' . $p . '%';
}
$stmt = $pdo->prepare("
    SELECT record_id, first_name, middle_name, last_name, barangay, date_of_event
    FROM civil_registry_records
    WHERE " . implode(' AND ', $where) . "
    LIMIT 25
");
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Prefer the record whose name is EXACTLY what was typed (same words, any order)
$record = $candidates[0] ?? null;
$wantKey = gg_name_key($fullName);
foreach ($candidates as $c) {
    if (gg_name_key($c['first_name'] . ' ' . $c['middle_name'] . ' ' . $c['last_name']) === $wantKey) { $record = $c; break; }
}

// Remember the searched name (so it is still there if the user comes back)
$_SESSION['search_full_name'] = $fullName;

if ($record) {
    $_SESSION['matched_record_id'] = $record['record_id'];
    echo json_encode(['found' => true, 'record' => $record]);
} else {
    unset($_SESSION['matched_record_id']);
    echo json_encode(['found' => false]);
}
