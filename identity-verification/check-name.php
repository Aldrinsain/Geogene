<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$fullName = trim($_GET['full_name'] ?? '');
if ($fullName === '') {
    echo json_encode(['found' => false]);
    exit;
}
