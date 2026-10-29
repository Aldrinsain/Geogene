<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/account-status.php';

if (isset($_GET['toggle'])) {
    $uid = (int) $_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $cur = $stmt->fetchColumn();
    $newStatus = $cur === 'active' ? GEOGENE_DB_DISABLED : 'active';   // 'suspended' = Disabled (valid ENUM value)
    $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $uid]);
    // come back to the SAME search / filters the staff member was looking at
    $keep = $_GET; unset($keep['toggle']);
    header('Location: user-management.php' . ($keep ? '?' . http_build_query($keep) : ''));
    exit;
}

// ---------- Search + filters ----------
$search         = trim($_GET['q'] ?? '');
$statusFilter   = $_GET['status'] ?? '';
$identityFilter = in_array($_GET['identity'] ?? '', ['verified', 'unverified'], true) ? $_GET['identity'] : '';
$dateFrom       = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$dateTo         = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
if ($dateFrom && $dateTo && $dateFrom > $dateTo) { [$dateFrom, $dateTo] = [$dateTo, $dateFrom]; }
$sortMap = [
    'newest'    => 'created_at DESC',
    'oldest'    => 'created_at ASC',
    'name_asc'  => 'full_name ASC',
    'name_desc' => 'full_name DESC',
];
$sortKey = isset($sortMap[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = ["role = 'user'"];
$params = [];
if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter === 'disabled') {
    $where[] = geogene_sql_disabled('status');
} elseif (in_array($statusFilter, ['active', 'pending'], true)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
} else {
    $statusFilter = '';
}
if ($identityFilter === 'verified')   $where[] = "identity_verified = 1";
if ($identityFilter === 'unverified') $where[] = "(identity_verified = 0 OR identity_verified IS NULL)";
if ($dateFrom) { $where[] = "DATE(created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "DATE(created_at) <= ?"; $params[] = $dateTo; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY {$sortMap[$sortKey]}, user_id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Link builder: current filters + changes (pagination, chips, reset all use it)
$curQuery = array_filter([
    'q' => $search, 'status' => $statusFilter, 'identity' => $identityFilter,
    'from' => $dateFrom, 'to' => $dateTo, 'sort' => $sortKey === 'newest' ? '' : $sortKey,
], fn($v) => $v !== '');
$umUrl = function (array $change = [], array $drop = []) use ($curQuery) {
    $q = array_merge($curQuery, $change);
    foreach ($drop as $d) unset($q[$d]);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return 'user-management.php' . ($q ? '?' . http_build_query($q) : '');
};
$advancedCount = ($identityFilter ? 1 : 0) + ($dateFrom ? 1 : 0) + ($dateTo ? 1 : 0) + ($sortKey !== 'newest' ? 1 : 0);
$sortLabels = ['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name_asc' => 'Name A–Z', 'name_desc' => 'Name Z–A'];
$chips = [];
if ($search !== '')       $chips[] = ['Search: “' . $search . '”', $umUrl([], ['q', 'page'])];
if ($statusFilter !== '') $chips[] = ['Status: ' . ($statusFilter === 'disabled' ? 'Disabled' : ucfirst($statusFilter)), $umUrl([], ['status', 'page'])];
if ($identityFilter)      $chips[] = ['Identity: ' . ucfirst($identityFilter), $umUrl([], ['identity', 'page'])];
if ($dateFrom)            $chips[] = ['From: ' . date('M d, Y', strtotime($dateFrom)), $umUrl([], ['from', 'page'])];
if ($dateTo)              $chips[] = ['To: ' . date('M d, Y', strtotime($dateTo)), $umUrl([], ['to', 'page'])];
if ($sortKey !== 'newest') $chips[] = ['Sort: ' . $sortLabels[$sortKey], $umUrl([], ['sort', 'page'])];

// ---------- EXPORT TO EXCEL / CSV: every user that matches the current search + filters (not just this page) ----------
if (isset($_GET['export'])) {
    require_once __DIR__ . '/includes/export-helper.php';
    $exStmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY {$sortMap[$sortKey]}, user_id DESC");
    $exStmt->execute($params);
    $exRows = [];
    while ($r = $exStmt->fetch()) $exRows[] = gg_users_export_row($r);
    gg_export_send(
        $_GET['export'] === 'xlsx' ? 'xlsx' : 'csv',
        'users-' . date('Ymd-His'),
        gg_users_export_headers(), $exRows,
        ['sheet' => 'Users', 'date_cols' => [4, 7]]
    );
}

$pageTitle = 'User Management';
$pageSub = 'Manage all registered GeoGene user accounts.';
$activeStaffNav = 'users';
require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">All Users (<?= number_format($totalRows) ?>)</span>
    <span style="display:inline-flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= htmlspecialchars($umUrl(['export' => 'xlsx'], ['page'])) ?>" class="btn btn-outline btn-sm" title="Download ALL <?= number_format($totalRows) ?> user(s) in this list as an Excel file (.xlsx)">⬇ Export Excel</a>
      <a href="<?= htmlspecialchars($umUrl(['export' => 'csv'], ['page'])) ?>" class="btn btn-outline btn-sm" title="Download ALL <?= number_format($totalRows) ?> user(s) in this list as a CSV file">⬇ Export CSV</a>
    </span>
  </div>

  <style>
    .um-filter-btn{position:relative;display:inline-flex;align-items:center;gap:6px;}
    .um-filter-btn .um-count{background:var(--primary);color:#fff;border-radius:999px;font-size:10.5px;font-weight:800;min-width:18px;height:18px;line-height:18px;text-align:center;padding:0 5px;}
    .um-filter-btn.open{border-color:var(--primary);color:var(--primary);}
    .um-panel{flex-basis:100%;display:none;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px 14px;align-items:end;
      background:var(--bg-page);border:1px solid var(--border);border-radius:12px;padding:14px 16px;}
    .um-panel.open{display:grid;}
    .um-panel label{display:block;font-size:11.5px;font-weight:700;color:var(--text-secondary);margin-bottom:5px;}
    .um-panel select,.um-panel input[type=date]{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:var(--bg-card);color:var(--text-main);}
    body.dark-mode .um-panel input[type=date]{color-scheme:dark;}
    .um-panel-actions{display:flex;gap:8px;justify-content:flex-end;grid-column:1/-1;}
    .um-chips{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:-4px 0 14px;}
    .um-chip{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:5px 6px 5px 11px;border-radius:999px;
      background:var(--primary-light);color:var(--primary);border:1px solid var(--border);}
    .um-chip a{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;text-decoration:none;color:inherit;font-weight:800;}
    .um-chip a:hover{background:rgba(0,0,0,.12);}
    .um-clear{font-size:12px;font-weight:700;color:var(--text-secondary);text-decoration:underline;}
  </style>

  <form method="GET" class="table-toolbar" id="umFilterForm">
    <div class="search-box"><span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email...">
    </div>
    <select name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="disabled" <?= $statusFilter === 'disabled' ? 'selected' : '' ?>>Disabled</option>
    </select>
    <button type="button" class="btn btn-outline btn-sm um-filter-btn <?= $advancedCount ? 'open' : '' ?>" id="umFilterToggle" aria-expanded="<?= $advancedCount ? 'true' : 'false' ?>" aria-controls="umFilterPanel">
      Filter <?php if ($advancedCount): ?><span class="um-count"><?= $advancedCount ?></span><?php endif; ?>
    </button>

    <div class="um-panel <?= $advancedCount ? 'open' : '' ?>" id="umFilterPanel">
      <div>
        <label for="umIdentity">Identity verification</label>
        <select name="identity" id="umIdentity">
          <option value="">All</option>
          <option value="verified" <?= $identityFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
          <option value="unverified" <?= $identityFilter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
        </select>
      </div>
      <div>
        <label for="umFrom">Registered from</label>
        <input type="date" name="from" id="umFrom" value="<?= htmlspecialchars($dateFrom) ?>">
      </div>
      <div>
        <label for="umTo">Registered until</label>
        <input type="date" name="to" id="umTo" value="<?= htmlspecialchars($dateTo) ?>">
      </div>
      <div>
        <label for="umSort">Sort by</label>
        <select name="sort" id="umSort">
          <?php foreach ($sortLabels as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= $sortKey === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="um-panel-actions">
        <a class="btn btn-outline btn-sm" href="user-management.php">Reset all</a>
        <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
      </div>
    </div>
  </form>

  <?php if ($chips): ?>
    <div class="um-chips">
      <?php foreach ($chips as [$label, $removeUrl]): ?>
        <span class="um-chip"><?= htmlspecialchars($label) ?> <a href="<?= htmlspecialchars($removeUrl) ?>" title="Remove this filter" aria-label="Remove filter">×</a></span>
      <?php endforeach; ?>
      <a class="um-clear" href="user-management.php">Clear all</a>
    </div>
  <?php endif; ?>
  <script>
    (function () {
      var btn = document.getElementById('umFilterToggle'), panel = document.getElementById('umFilterPanel');
      if (!btn || !panel) return;
      var hasActive = <?= $advancedCount > 0 ? 'true' : 'false' ?>;
      btn.addEventListener('click', function () {
        var open = panel.classList.toggle('open');
        btn.classList.toggle('open', open || hasActive);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    })();
  </script>

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
            <a href="?<?= htmlspecialchars(http_build_query(array_merge($curQuery, ['toggle' => $u['user_id']]))) ?>" class="icon-btn <?= $u['status'] === 'active' ? 'danger' : '' ?>"
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
    <span>Showing <?= $totalRows ? number_format($offset + 1) . '–' . number_format($offset + count($users)) : 0 ?> of <?= number_format($totalRows) ?> users</span>
    <div class="page-btns">
      <?php
        $pgFrom = max(1, $page - 3); $pgTo = min($totalPages, $page + 3);
        if ($pgFrom > 1) { echo '<a href="' . htmlspecialchars($umUrl(['page' => 1])) . '"><button>1</button></a>'; if ($pgFrom > 2) echo '<span style="padding:0 4px;">…</span>'; }
        for ($p = $pgFrom; $p <= $pgTo; $p++): ?>
        <a href="<?= htmlspecialchars($umUrl(['page' => $p])) ?>">
          <button class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></button>
        </a>
      <?php endfor;
        if ($pgTo < $totalPages) { if ($pgTo < $totalPages - 1) echo '<span style="padding:0 4px;">…</span>'; echo '<a href="' . htmlspecialchars($umUrl(['page' => $totalPages])) . '"><button>' . $totalPages . '</button></a>'; }
      ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>