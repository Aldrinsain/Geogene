<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/account-status.php';

if (isset($_GET['toggle'])) {
    $uid = (int) $_GET['toggle'];
    // Citizens only (role='user'). Staff/Admin accounts can ONLY be enabled/disabled
    // by an Admin in admin/staff-management.php - never from the Staff portal.
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ? AND role = 'user'");
    $stmt->execute([$uid]);
    $cur = $stmt->fetchColumn();
    if ($cur !== false) {
        $newStatus = $cur === 'active' ? GEOGENE_DB_DISABLED : 'active';   // 'suspended' = Disabled (valid ENUM value)
        $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'user'")->execute([$newStatus, $uid]);
    }
    header('Location: user-management.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = ["role = 'user'"];
$params = [];
if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter === 'disabled') {
    $where[] = geogene_sql_disabled('status');
} elseif ($statusFilter !== '') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'User Management';
$pageSub = 'Manage all registered GeoGene user accounts.';
$activeStaffNav = 'users';
require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="panel">
  <div class="panel-header"><span class="panel-title">All Users (<?= number_format($totalRows) ?>)</span></div>

  <form method="GET" class="table-toolbar">
    <div class="search-box"><span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span><input type="text" name="q" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>"></div>
    <select name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="disabled" <?= $statusFilter === 'disabled' ? 'selected' : '' ?>>Disabled</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
  </form>

  <table class="data-table">
    <thead><tr><th>Full Name</th><th>Email Address</th><th>Identity</th><th>Status</th><th>Date Registered</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">No users found.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= $u['identity_verified'] ? '<span class="badge green">Verified</span>' : '<span class="badge orange">Unverified</span>' ?></td>
          <td><span class="badge <?= geogene_account_badge_class($u['status']) ?>"><?= geogene_account_status_label($u['status']) ?></span></td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="?toggle=<?= $u['user_id'] ?>" class="icon-btn <?= $u['status'] === 'active' ? 'danger' : '' ?>"
               title="<?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?>"
               onclick="return confirm('<?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?> this account?');">
              <?= $u['status'] === 'active' ? '<svg viewBox="-6.4 -6.4 140.80 140.80" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--noto" preserveAspectRatio="xMidYMid meet" fill="#000000" stroke="#000000" stroke-width="0.00128"><g id="SVGRepo_bgCarrier" stroke-width="0"><rect x="-6.4" y="-6.4" width="140.80" height="140.80" rx="70.4" fill="#f5f5f5" strokewidth="0"></rect></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.256"></g><g id="SVGRepo_iconCarrier"><path d="M64 4C30.9 4 4 30.9 4 64s26.9 60 60 60s60-26.9 60-60S97.1 4 64 4zm50 60c0 12-4.2 23-11.3 31.6L32.4 25.3C41 18.2 52 14 64 14c27.6 0 50 22.4 50 50zM14 64c0-12 4.2-23 11.3-31.6l70.3 70.3C87 109.8 76 114 64 114c-27.6 0-50-22.4-50-50z" fill="#c33"></path><path d="M60.1 7C47 7 35 11.5 25.4 19l-.1-.1l-1.1 1.1C11.9 30.3 4 45.8 4 63.1c0 31 25.1 56.1 56.1 56.1s56.1-25.1 56.1-56.1S91.1 7 60.1 7zM32.4 25.3C41 18.2 52 14 64 14c27.6 0 50 22.4 50 50c0 12-4.2 23-11.3 31.6l-70.6-70l.3-.3zM14 64c0-11.8 4.1-22.7 11-31.2l70.6 69.9C87 109.8 76 114 64 114c-27.6 0-50-22.4-50-50z" fill="#f44336"></path><path d="M45 10.9c1.7-.4 4.2-1.6 5.9-1.1c1 .3 1.9 1.5 1.5 2.5c-.3.8-1.1 1.3-2 1.5c-4.1 1.1-7.7 2.3-11.3 4.1C32 21.6 25.6 27.2 21 33.7c-1.9 2.7-3.4 5.5-5.8 7.8c-.2.2-.5.4-.8.4c-.2 0-.4 0-.5-.1c-1.2-.5-1.4-1.1-1.2-2.3c.2-1 .5-2 1-2.9c.9-1.8 2.2-3.4 3.3-5.1c1.9-2.9 4.1-5.6 6.6-8c6.8-6.3 15.6-11.1 21.4-12.6z" fill="#ff8a80"></path><path fill="#c33" d="M32.4 25.3l-2 2l71.1 71.1l1.8-2.2z"></path></g></svg>' :
                 '<svg viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000" stroke-width="0.00024000000000000003"><g id="SVGRepo_bgCarrier" stroke-width="0"><rect x="0" y="0" width="24.00" height="24.00" rx="12" fill="#f2f2f2" strokewidth="0"></rect></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="#1f8f00"></path> </g></svg>' ?>
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <div class="pagination">
    <span>Showing <?= count($users) ?> of <?= number_format($totalRows) ?> users</span>
    <div class="page-btns">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $p ?>">
          <button class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></button>
        </a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>