<?php
// GeoGene Staff - Auth Guard. Include this at the top of any staff-protected page.
require_once __DIR__ . '/../../config/session-names.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Admin now has its OWN separate login/session (see config/session-names.php)
// so an Admin's browser normally has NO staff_id under this (default)
// session. But the Admin portal still embeds these exact Staff pages -
// Civil Registry Records, Family Tree, Map View, photo upload, theme
// preference - inside an iframe on the Admin's own pages (see
// admin/includes/embed-shared-page.php). So if this session has no
// staff_id, check whether the browser has a valid Admin session instead,
// and use that identity for this one request. This never touches or
// overwrites a real Staff member's own session.
if (!isset($_SESSION['staff_id']) && !empty($_COOKIE[GEOGENE_ADMIN_SESSION_NAME])) {
    $thisSessionName = session_name();
    $thisSessionId = session_id();
    session_write_close();

    session_name(GEOGENE_ADMIN_SESSION_NAME);
    session_id($_COOKIE[GEOGENE_ADMIN_SESSION_NAME]);
    session_start();
    $borrowedAdminId = (($_SESSION['staff_role'] ?? '') === 'admin') ? ($_SESSION['staff_id'] ?? null) : null;
    session_write_close();

    // Switch back to this (Staff-portal) session for the rest of the request.
    session_name($thisSessionName);
    session_id($thisSessionId);
    session_start();

    if ($borrowedAdminId) {
        $_SESSION['staff_id'] = $borrowedAdminId;
        // CRITICAL: close the session RIGHT NOW so this borrowed identity
        // only exists in memory for the REST OF THIS ONE REQUEST, and is
        // NEVER written back to this visitor's own session file. Without
        // this, if this visitor actually turns out to be a genuine Staff
        // member whose own session merely expired/was cleared (not really
        // an Admin viewing through the iframe), the line above would
        // otherwise PERMANENTLY save the Admin's identity into their
        // session - showing them someone else's account on every future
        // page load until they explicitly logged out and back in. This
        // keeps the borrow exactly as safe as the comment above promises.
        session_write_close();
    }
}

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Guard against a stale/invalid session: this can happen if the database
// was re-imported/reset while a browser was still logged in with an old
// staff_id, OR if a citizen account's id somehow ended up in a staff
// session. Citizens (role='user') and MCRO staff (role='office_head'/'admin')
// are separate accounts and must never share access between the two portals.
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/account-status.php';
$stmt = $pdo->prepare("SELECT user_id, status FROM users WHERE user_id = ? AND role IN ('office_head','admin')");
$stmt->execute([$_SESSION['staff_id']]);
$guardRow = $stmt->fetch();
if (!$guardRow) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?expired=1');
    exit;
}
// Disabled by the Admin while logged in -> log them out right away.
if (geogene_account_is_disabled($guardRow['status'])) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?disabled=1');
    exit;
}

// Make sure the Civil Registry table has every column this version of the system uses
// (adds ONLY what is missing; e.g. date_of_birth). Checked once per login session.
require_once __DIR__ . '/schema-ensure.php';
if (empty($_SESSION['gg_schema_ok'])) {
    gg_ensure_registry_schema($pdo);
    try {
        if (!gg_registry_missing_columns($pdo) && session_status() === PHP_SESSION_ACTIVE) $_SESSION['gg_schema_ok'] = 1;
    } catch (Throwable $e) { /* try again next time */ }
}

// Presence heartbeat: bump last_seen so the dashboard can tell who's
// currently online. Throttled via the session (only writes once per
// minute) so this doesn't add a database write to every single page load.
if (empty($_SESSION['last_seen_ping']) || (time() - $_SESSION['last_seen_ping']) > 60) {
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ?")->execute([$_SESSION['staff_id']]);
    $_SESSION['last_seen_ping'] = time();
}
