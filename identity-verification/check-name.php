<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$fullName = trim($_GET['full_name'] ?? '');
if ($fullName === '') {
    echo json_encode(['found' => false]);
    exit;
}
// Split "examplename" into parts for a loose match against the registry
$parts = preg_split('/\s+/', $fullName);
$searchTerm = '%' . implode('%', $parts) . '%';

$stmt = $pdo->prepare("
    SELECT record_id, first_name, middle_name, last_name, barangay, date_of_event
    FROM civil_registry_records
    WHERE CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?
    LIMIT 1
");
$stmt->execute([$searchTerm]);
$record = $stmt->fetch();
// I-remember ang na-search nga ngalan (para naa pa gihapon kung mo-balik ang user)
$_SESSION['search_full_name'] = $fullName;

if ($record) {
    $_SESSION['matched_record_id'] = $record['record_id'];
    echo json_encode(['found' => true, 'record' => $record]);
} else {
    unset($_SESSION['matched_record_id']);
    echo json_encode(['found' => false]);
}
