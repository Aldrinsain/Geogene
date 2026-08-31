<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$darkMode = isset($_POST['dark_mode']) ? (int) $_POST['dark_mode'] : 0;

$stmt = $pdo->prepare("
    INSERT INTO user_settings (user_id, dark_mode) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE dark_mode = VALUES(dark_mode)
");
$stmt->execute([$userId, $darkMode]);

echo json_encode(['success' => true]);
