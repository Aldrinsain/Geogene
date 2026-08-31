<?php
// GeoGene - Auth Guard. Include this at the top of any protected page.
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Guard against a stale/invalid session: this can happen if the database
// was re-imported/reset while a browser was still logged in with an old
// user_id, OR if a staff (MCRO) account's id somehow ended up in a citizen
// session. Citizens (role='user') and MCRO staff (role='office_head'/'admin')
// are separate accounts and must never share access between the two portals.
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'user'");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    session_unset();
    session_destroy();
    header('Location: ../auth/login.php?expired=1');
    exit;
}

// Presence heartbeat: bump last_seen so staff can tell who's currently
// online. Throttled via the session (only writes once per minute) so this
// doesn't add a database write to every single page load.
if (empty($_SESSION['last_seen_ping']) || (time() - $_SESSION['last_seen_ping']) > 60) {
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    $_SESSION['last_seen_ping'] = time();
}

// Load the logged-in user's theme preference (used by <body class="dark-bg|light-bg">)
$stmtTheme = $pdo->prepare("SELECT dark_mode FROM user_settings WHERE user_id = ?");
$stmtTheme->execute([$_SESSION['user_id']]);
$isDark = (bool) $stmtTheme->fetchColumn();