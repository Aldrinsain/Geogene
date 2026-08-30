<?php
session_start();
require_once __DIR__ . '/../includes/logo.php';
// Kung naka-login na, diretso sa dashboard imbis makita pag-usab ang intro
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/dashboard.php');
    exit;
}
?>