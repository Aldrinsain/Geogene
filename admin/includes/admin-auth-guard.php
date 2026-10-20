<?php
// GeoGene Admin - Auth Guard. Include this at the top of any admin-only page.
// Admin has its OWN session, completely separate from the Staff/Civil
// Registry session - it uses a different session cookie name
// (GEOGENE_ADMIN_SESSION_NAME) so an Admin and a Staff member can both be
// logged in at the same time, in the same browser, without one login
// affecting the other.
require_once __DIR__ . '/../../config/session-names.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(GEOGENE_ADMIN_SESSION_NAME);
    session_start();
}

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/account-status.php';
$stmt = $pdo->prepare("SELECT user_id, role, status FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$row = $stmt->fetch();

if (!$row || $row['role'] !== 'admin') {
    // Stale/invalid session (e.g. DB re-imported while a browser was still
    // logged in, or this account was demoted/removed from Admin). Only
    // this Admin session is cleared - the Staff session (different
    // cookie), if any, on this same browser is completely untouched.
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
if (geogene_account_is_disabled($row['status'])) {
    session_unset();
    session_destroy();
    header('Location: login.php?disabled=1');
    exit;
}

// Presence heartbeat, same pattern as the Staff portal.
if (empty($_SESSION['last_seen_ping']) || (time() - $_SESSION['last_seen_ping']) > 60) {
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ?")->execute([$_SESSION['staff_id']]);
    $_SESSION['last_seen_ping'] = time();
}
