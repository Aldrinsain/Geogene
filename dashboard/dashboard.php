<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logo.php';

$stmt = $pdo->prepare("SELECT full_name, profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$activeNav = 'dashboard';
?>