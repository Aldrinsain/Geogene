<?php
// GeoGene - Admin logout. Only clears the Admin session cookie
// (GEOGENE_ADMIN_SESSION_NAME) - a Staff session on this same browser
// (different cookie) is completely unaffected.
require_once __DIR__ . '/../config/session-names.php';

$defaultSessionName = ini_get('session.name') ?: 'PHPSESSID';

session_name(GEOGENE_ADMIN_SESSION_NAME);
session_start();
$adminId = $_SESSION['staff_id'] ?? null;
session_unset();
session_destroy();

// The shared modules embedded inside the Admin portal (Civil Registry
// Records, Family Tree, Map View) borrow the Staff portal's own session
// (default cookie) to authenticate the Admin while viewing them - see
// admin/includes/embed-shared-page.php and
// staff/includes/staff-auth-guard.php. If that borrowed identity still
// belongs to this same Admin, clear it too so nothing is left behind
// after logout. A real Staff member's own session (different user id) is
// left completely untouched.
session_name($defaultSessionName);
if (isset($_COOKIE[$defaultSessionName])) {
    session_id($_COOKIE[$defaultSessionName]);
}
session_start();
if ($adminId && ($_SESSION['staff_id'] ?? null) == $adminId) {
    unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_role']);
}

header('Location: login.php');
exit;
