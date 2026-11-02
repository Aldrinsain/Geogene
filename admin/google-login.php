<?php
// GeoGene - Admin "Sign in with Google" handler (called by admin/login.php with a Google access token).
// Uses the ADMIN session cookie (same as admin/login.php) and only signs in an EXISTING admin account.
// Always answers with JSON, even if something goes wrong (never an HTTP 500 / HTML error page).
ob_start();
header('Content-Type: application/json');

function gg_json_out(array $data): void
{
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';             // also loads config/session-names.php
    require_once __DIR__ . '/../config/google-helpers.php';

    session_name(GEOGENE_ADMIN_SESSION_NAME);
    session_start();

    $input = json_decode(file_get_contents('php://input'), true);
    gg_json_out(gg_google_portal_login($pdo, (string) ($input['access_token'] ?? ''), 'admin', 'dashboard.php'));
} catch (Throwable $e) {
    error_log('[GeoGene google] admin login crashed: ' . $e->getMessage());
    gg_json_out(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
