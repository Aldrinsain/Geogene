<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/account-status.php';

// ---------- APPROVE / REJECT ACTIONS ----------
if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE users SET identity_verified = 1 WHERE user_id = ?")->execute([(int) $_GET['approve']]);
    header('Location: verification-monitoring.php');
    exit;
}
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE users SET identity_verified = 0, status = '" . GEOGENE_DB_DISABLED . "' WHERE user_id = ?")->execute([(int) $_GET['reject']]);
    header('Location: verification-monitoring.php');
    exit;
}

$verifiedCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 1 AND role='user'")->fetchColumn();
$pendingCount  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 0 AND email_verified = 1 AND role='user'")->fetchColumn();
$totalUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

$search = trim($_GET['q'] ?? '');
$where = "role = 'user'";
$params = [];
if ($search !== '') {
    $where .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
// ---------- EXPORT TO CSV / EXCEL (everything that matches the current search, not just the first 25) ----------
if (isset($_GET['export'])) {
    require_once __DIR__ . '/includes/export-helper.php';
    $exStmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
    $exStmt->execute($params);
    $exRows = [];
    while ($r = $exStmt->fetch()) $exRows[] = gg_users_export_row($r);
    gg_export_send(
        $_GET['export'] === 'xlsx' ? 'xlsx' : 'csv',
        'verification-monitoring-' . date('Ymd-His'),
        gg_users_export_headers(), $exRows,
        ['sheet' => 'Users', 'date_cols' => [4, 7]]
    );
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT 25");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Verification Monitoring';
$pageSub = 'Monitor and manage identity verification requests.';
$activeStaffNav = 'verification';
require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-icon img-box"><img src="../assets/images/verified-icon.gif" alt="Verified" width="32" height="32"></div><div><div class="stat-num"><?= $verifiedCount ?></div><div class="stat-label">Verified</div></div></div>
  <div class="stat-card"><div class="stat-icon img-box"><img src="../assets/images/pending-icon.gif" alt="Pending" width="32" height="32"></div><div><div class="stat-num"><?= $pendingCount ?></div><div class="stat-label">Pending Review</div></div></div>
  <div class="stat-card"><div class="stat-icon img-box"><img src="../assets/images/icons8-people.gif" alt="Family" width="32" height="32"></div><div><div class="stat-num"><?= $totalUsers ?></div><div class="stat-label">Total Registered</div></div></div>
</div>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">User Verification Status</span>
    <span style="display:inline-flex;gap:8px;flex-wrap:wrap;">
      <a href="verification-monitoring.php?<?= htmlspecialchars(http_build_query(array_filter(['q' => $search, 'export' => 'xlsx']))) ?>"
         class="btn btn-outline btn-sm" title="Download ALL users<?= $search !== '' ? ' matching “' . htmlspecialchars($search) . '”' : '' ?> as an Excel file (.xlsx)">⬇ Export Excel</a>
      <a href="verification-monitoring.php?<?= htmlspecialchars(http_build_query(array_filter(['q' => $search, 'export' => 'csv']))) ?>"
         class="btn btn-outline btn-sm" title="Download ALL users<?= $search !== '' ? ' matching “' . htmlspecialchars($search) . '”' : '' ?> as a CSV file">⬇ Export CSV</a>
    </span>
  </div>

  <form method="GET" class="table-toolbar">
    <div class="search-box"><span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span><input type="text" name="q" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>"></div>
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
  </form>

  <table class="data-table">
    <thead><tr><th>Full Name</th><th>Email Address</th><th>Status</th><th>Identity Verified</th><th>Date Registered</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">No users found.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= geogene_account_badge_class($u['status']) ?>"><?= geogene_account_status_label($u['status']) ?></span></td>
          <td>
            <?php if ($u['identity_verified']): ?>
              <span class="badge green">✔ Verified</span>
            <?php else: ?>
              <span class="badge orange">Pending</span>
            <?php endif; ?>
          </td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if (!$u['identity_verified']): ?>
              <a href="?approve=<?= $u['user_id'] ?>" class="icon-btn" title="Approve">✅</a>
              <a href="?reject=<?= $u['user_id'] ?>" class="icon-btn danger" title="Reject" onclick="return confirm('Are you sure you want to reject and disable this user?');">✕</a>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:11px;">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>