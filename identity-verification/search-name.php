<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logo.php';
$fullName = $_SESSION['search_full_name'] ?? '';
?>