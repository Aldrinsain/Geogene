<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$type = $_GET['type'] ?? '';
if (in_array($type, ['birth', 'marriage', 'death'], true) && !empty($_SESSION['scan_draft'])) {
    $_SESSION['scan_draft']['type'] = $type;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
