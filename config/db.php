<?php
// GeoGene - Database Connection
// LOCAL (XAMPP): walay kinahanglan usbon - gamit ang default (root, walay password).
// SERVER (Hostinger): ibutang ang DB_HOST / DB_NAME / DB_USER / DB_PASS sa  .env  file
// (sa root sa project - dili i-commit sa Git). Tan-awa ang .env.example.

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/session-names.php';
require_once __DIR__ . '/env.php';          // gibasa ang .env

$DB_HOST = defined('DB_HOST') ? DB_HOST : 'localhost';
$DB_NAME = defined('DB_NAME') ? DB_NAME : 'geogene_db';
$DB_USER = defined('DB_USER') ? DB_USER : 'root';
$DB_PASS = defined('DB_PASS') ? DB_PASS : '';   // default XAMPP root password is empty

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Ayaw ipakita ang detalye (host/user) sa browser - i-log ra sa server.
    error_log('GeoGene DB connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check the .env file (DB_HOST, DB_NAME, DB_USER, DB_PASS).');
}
