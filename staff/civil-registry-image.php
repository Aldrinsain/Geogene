<?php
/**
 * civil-registry-image.php?f=scan_3_front1_1700000000_123.jpg
 * Serves ONE certificate scan photo, but only to a logged-in staff member / admin.
 * (uploads/civil-registry-scans/ itself is closed to direct web access by its .htaccess.)
 */
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/includes/scan-url.php';

// Don't hold the session lock while a page loads many photos at once.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

$f = basename(str_replace('\\', '/', (string) ($_GET['f'] ?? '')));
if (!preg_match(GG_SCAN_FILE_PATTERN, $f)) {
    http_response_code(404);
    exit;
}
$dir  = realpath(__DIR__ . '/../uploads/civil-registry-scans');
$full = $dir ? realpath($dir . DIRECTORY_SEPARATOR . $f) : false;
if (!$full || strpos($full, $dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) {
    http_response_code(404);
    exit;
}
$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
readfile($full);
