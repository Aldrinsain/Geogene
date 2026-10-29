<?php
// GeoGene - Database Connection
// Works on BOTH Hostinger (production) and local XAMPP.
// Real values live in config/secrets.php (not in Git). Without secrets.php it
// falls back to the XAMPP defaults so local development keeps working.

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/session-names.php';
require_once __DIR__ . '/env.php';

$IS_PRODUCTION = defined('GEOGENE_PRODUCTION') && GEOGENE_PRODUCTION;
if ($IS_PRODUCTION) {
    // Never show PHP errors / stack traces to visitors on the live site.
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$DB_HOST = defined('DB_HOST') ? DB_HOST : 'localhost';
$DB_NAME = defined('DB_NAME') ? DB_NAME : 'geogene_db';
$DB_USER = defined('DB_USER') ? DB_USER : 'root';
$DB_PASS = defined('DB_PASS') ? DB_PASS : '';   // default XAMPP root password is empty

// Hosts to try in order. On Hostinger the app and the database are on the same
// server, so 'localhost' is normally right; the remote hostname is a backup.
$DB_HOSTS = [$DB_HOST];
if (defined('DB_HOST_FALLBACK') && DB_HOST_FALLBACK !== '' && DB_HOST_FALLBACK !== $DB_HOST) {
    $DB_HOSTS[] = DB_HOST_FALLBACK;
}

$pdo = null;
$lastError = null;
foreach ($DB_HOSTS as $tryHost) {
    try {
        $pdo = new PDO(
            "mysql:host=$tryHost;dbname=$DB_NAME;charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
        break;
    } catch (PDOException $e) {
        $lastError = $e;
        error_log("[GeoGene] DB connect to '$tryHost' failed: " . $e->getMessage());
    }
}

if (!$pdo) {
    http_response_code(500);
    if ($IS_PRODUCTION) {
        die('Service temporarily unavailable. Please try again later.');
    }
    die('Database connection failed: ' . ($lastError ? $lastError->getMessage() : 'unknown error'));
}
