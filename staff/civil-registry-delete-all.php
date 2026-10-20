<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

// Only accept POST (a plain link/GET could be triggered accidentally by a
// browser prefetch or a crawler - a full wipe should never happen from a GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: civil-registry.php');
    exit;
}

// Extra safety net on top of the modal's typed confirmation: the request
// itself must carry the exact confirm phrase, so this endpoint can't be
// triggered by a bare form post from somewhere else.
$confirmText = trim($_POST['confirm'] ?? '');
if ($confirmText !== 'DELETE ALL') {
    header('Location: civil-registry.php?delete_all_error=1');
    exit;
}

$deletedCount = (int) $pdo->query('SELECT COUNT(*) FROM civil_registry_records')->fetchColumn();
$pdo->exec('DELETE FROM civil_registry_records');

header('Location: civil-registry.php?deleted_all=' . $deletedCount);
exit;
