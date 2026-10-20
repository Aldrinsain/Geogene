<?php
require_once __DIR__ . '/includes/admin-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$hasActivityLog = (bool) $pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();

$search = trim($_GET['q'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo = trim($_GET['to'] ?? '');

$logs = [];
if ($hasActivityLog) {
    // Staff Activity Logs = office_head/admin actions from activity_logs,
    // PLUS their login events from login_logs - kept entirely separate
    // from the citizen User Logs report.
    $sql = "
        SELECT * FROM (
            SELECT u.full_name, u.role, a.activity, a.module, a.description, a.created_at AS event_time
            FROM activity_logs a JOIN users u ON u.user_id = a.user_id
            WHERE a.role IN ('office_head','admin')
            UNION ALL
            SELECT u.full_name, u.role, 'Logged in' AS activity, 'Auth' AS module, NULL AS description, l.login_at AS event_time
            FROM login_logs l JOIN users u ON u.user_id = l.user_id
            WHERE u.role IN ('office_head','admin')
        ) AS combined
        WHERE 1=1
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND full_name LIKE ?";
        $params[] = "%$search%";
    }
    if ($dateFrom !== '') {
        $sql .= " AND event_time >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $sql .= " AND event_time <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }
    $sql .= " ORDER BY event_time DESC LIMIT 500";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

$pageTitle = 'Staff Activity Logs';
$pageSub = 'Civil Registry Staff / Office Head account activity.';
$activeAdminNav = 'reports';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">Staff Activity Logs (<?= number_format(count($logs)) ?>)</span>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">🖨 Print Report</button>
  </div>

  <?php if (!$hasActivityLog): ?>
    <p style="font-size:12.5px;color:var(--text-muted);">
      Activity logging is not set up yet. Import <code>migrations/add_admin_module.sql</code> to enable Staff Activity Logs.
    </p>
  <?php endif; ?>

  <form method="GET" class="table-toolbar no-print">
    <div class="search-box"><span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span><input type="text" name="q" placeholder="Search staff name..." value="<?= htmlspecialchars($search) ?>"></div>
    <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:12.5px;">
    <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:12.5px;">
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
  </form>

  <div class="print-only" style="display:none;margin-bottom:14px;">
    <h2 style="font-size:16px;">GeoGene - Staff Activity Logs Report</h2>
    <p style="font-size:12px;color:#555;">Municipal Civil Registry Office · Manolo Fortich &middot; Generated <?= date('F j, Y g:i A') ?></p>
  </div>

  <table class="data-table">
    <thead><tr><th>Staff Name</th><th>Role</th><th>Activity</th><th>Module</th><th>Date</th><th>Time</th></tr></thead>
    <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px;">No staff activity log entries found.</td></tr>
      <?php else: foreach ($logs as $l): ?>
        <tr>
          <td><?= htmlspecialchars($l['full_name']) ?></td>
          <td><?= ($l['role'] ?? '') === 'admin' ? 'Admin / Office Head' : 'Civil Staff' ?></td>
          <td><?= htmlspecialchars($l['activity']) ?><?= !empty($l['description']) ? ' &mdash; ' . htmlspecialchars($l['description']) : '' ?></td>
          <td><?= htmlspecialchars($l['module'] ?? '' ?: '—') ?></td>
          <td><?= date('M d, Y', strtotime($l['event_time'])) ?></td>
          <td><?= date('g:i A', strtotime($l['event_time'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<style>
  @media print {
    .no-print, .panel-header .btn { display: none !important; }
    .print-only { display: block !important; }
    .content-area { padding: 0 !important; }
    .panel { box-shadow: none !important; border: none !important; }
  }
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
