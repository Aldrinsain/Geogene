<?php
// GeoGene Staff - Save theme (light/dark) preference for the logged-in staff account.
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$staffId = $_SESSION['staff_id'];
$darkMode = isset($_POST['dark_mode']) && $_POST['dark_mode'] === '1' ? 1 : 0;

// Upsert: insert a settings row if the staff account doesn't have one yet.
$stmt = $pdo->prepare("SELECT setting_id FROM user_settings WHERE user_id = ?");
$stmt->execute([$staffId]);

if ($stmt->fetchColumn()) {
    $pdo->prepare("UPDATE user_settings SET dark_mode = ? WHERE user_id = ?")
        ->execute([$darkMode, $staffId]);
} else {
    $pdo->prepare("INSERT INTO user_settings (user_id, dark_mode) VALUES (?, ?)")
        ->execute([$staffId, $darkMode]);
}

echo json_encode(['success' => true]);
