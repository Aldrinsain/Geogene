<?php
// GeoGene - Staff "Sign in with Google" handler (called by login.php with a Google access token).
// Only signs in an EXISTING Civil Registry Staff (office_head) account - never creates one.
// Always answers with JSON, even if something goes wrong (never an HTTP 500 / HTML error page).
ob_start();
session_start();
header('Content-Type: application/json');

function gg_json_out(array $data): void
{
    if (ob_get_length()) ob_clean();     // drop any stray warning text so the JSON stays valid
    echo json_encode($data);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/google-helpers.php';

    $input = json_decode(file_get_contents('php://input'), true);
    gg_json_out(gg_google_portal_login($pdo, (string) ($input['access_token'] ?? ''), 'office_head', 'dashboard.php'));
} catch (Throwable $e) {
    error_log('[GeoGene google] staff login crashed: ' . $e->getMessage());
    gg_json_out(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
