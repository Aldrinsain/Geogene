<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$totalRecords = (int) $pdo->query("SELECT COUNT(*) FROM civil_registry_records")->fetchColumn();
$verifiedUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 1 AND role = 'user'")->fetchColumn();
$pendingVerification = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 0 AND email_verified = 1 AND role = 'user'")->fetchColumn();
$totalReports = (int) $pdo->query("SHOW TABLES LIKE 'staff_reports'")->rowCount() ? (int) $pdo->query("SELECT COUNT(*) FROM staff_reports")->fetchColumn() : 0;

$recentUsers = $pdo->query("SELECT full_name, email, status, last_seen, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentRatings = (int) $pdo->query("SHOW TABLES LIKE 'system_ratings'")->rowCount()
    ? $pdo->query("
        SELECT sr.stars, sr.feedback, sr.created_at, u.full_name, u.last_seen
        FROM system_ratings sr JOIN users u ON u.user_id = sr.user_id
        WHERE u.role = 'user'
        ORDER BY sr.created_at DESC LIMIT 5
      ")->fetchAll()
    : [];

// A user counts as "online" if we've seen a request from them in the last
// 5 minutes (last_seen is bumped by the auth-guard heartbeat on every page).
function is_online(?string $lastSeen): bool
{
    return $lastSeen && (time() - strtotime($lastSeen)) <= 300;
}

$pageTitle = 'Dashboard';
$pageSub = 'Manage genealogical records and monitor verifications.';
$activeStaffNav = 'dashboard';
require_once __DIR__ . '/includes/staff-header.php';
?>

<style>
  /* Sigurado nga uniform ang size sa tanan quick-tile icons (SVG man o img) */
  .qt-icon{display:flex;align-items:center;justify-content:center;height:30px;margin-bottom:8px;}
  .qt-icon svg{width:26px;height:26px;display:block;}
  .qt-icon img{width:28px;height:28px;display:block;}
  .stat-icon.img-box, .stat-icon.green, .stat-icon.blue, .stat-icon.orange, .stat-icon.red{
    background:#fff !important;
    border:1px solid var(--border);
    color:#1a1a1a !important;
  }
  .stat-icon.img-box img{width:22px;height:22px;}
</style>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon img-box"><img src="../assets/images/records-icon.gif" alt="Records" width="22" height="22"></div>
    <div><div class="stat-num"><?= number_format($totalRecords) ?></div><div class="stat-label">Total Civil Registry Records</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon img-box"><img src="../assets/images/verified-icon.gif" alt="Verified" width="22" height="22"></div>
    <div><div class="stat-num"><?= number_format($verifiedUsers) ?></div><div class="stat-label">Verified Users</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon img-box"><img src="../assets/images/pending-icon.gif" alt="Pending" width="22" height="22"></div>
    <div><div class="stat-num"><?= number_format($pendingVerification) ?></div><div class="stat-label">Pending Verification</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon img-box"><img src="../assets/images/wired-flat-237-star-morph-select.gif" alt="Star" width="22" height="22"></div>
    <div><div class="stat-num"><?= number_format($totalReports) ?></div><div class="stat-label">Ratings</div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><span class="panel-title">Quick Actions</span></div>
  <div class="quick-tiles">
    <a href="civil-registry.php?action=add" class="quick-tile">
      <div class="qt-icon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 13V19C4 20.1046 4.89543 21 6 21H18C19.1046 21 20 20.1046 20 19V13" stroke="#1a1a1a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M12 3L12 15M12 15L8.5 11.5M12 15L15.5 11.5" stroke="#1a1a1a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      </div>
      <div class="qt-label">Import Record</div>
    </a>

    <a href="verification-monitoring.php" class="quick-tile">
      <div class="qt-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="#1a1a1a">
          <path fill-rule="evenodd" d="M8.5 1.709a.75.75 0 0 0-1 0 8.963 8.963 0 0 1-4.84 2.217.75.75 0 0 0-.654.72 10.499 10.499 0 0 0 5.647 9.672.75.75 0 0 0 .694-.001 10.499 10.499 0 0 0 5.647-9.672.75.75 0 0 0-.654-.719A8.963 8.963 0 0 1 8.5 1.71Zm2.34 5.504a.75.75 0 0 0-1.18-.926L7.394 9.17l-1.156-.99a.75.75 0 1 0-.976 1.138l1.75 1.5a.75.75 0 0 0 1.078-.106l2.75-3.5Z" clip-rule="evenodd" />
        </svg>
      </div>
      <div class="qt-label">Verify User</div>
    </a>

    <a href="reports.php?action=generate" class="quick-tile">
      <div class="qt-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1a1a1a">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </svg>
      </div>
      <div class="qt-label">Rating</div>
    </a>

    <a href="map.php" class="quick-tile"><div class="qt-icon">🗺</div><div class="qt-label">View Map</div></a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="panel">
    <div class="panel-header"><span class="panel-title">Recently Registered Users</span></div>
    <?php if (!$recentUsers): ?>
      <p style="font-size:12.5px;color:var(--text-muted);">No newly registered users yet.</p>
    <?php else: foreach ($recentUsers as $u): ?>
      <div class="activity-item">
        <span class="activity-dot <?= is_online($u['last_seen']) ? 'green' : 'silver' ?>" title="<?= is_online($u['last_seen']) ? 'Online' : 'Offline' ?>"></span>
        <div>
          <div class="activity-text"><strong><?= htmlspecialchars($u['full_name']) ?></strong> registered an account</div>
          <div class="activity-time"><?= date('M d, Y g:i A', strtotime($u['created_at'])) ?></div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="panel">
    <div class="panel-header"><span class="panel-title">Recent User Ratings</span></div>
    <?php if (!$recentRatings): ?>
      <p style="font-size:12.5px;color:var(--text-muted);">No ratings submitted yet.</p>
    <?php else: foreach ($recentRatings as $r): ?>
      <div class="activity-item">
        <span class="activity-dot <?= is_online($r['last_seen']) ? 'green' : 'silver' ?>" title="<?= is_online($r['last_seen']) ? 'Online' : 'Offline' ?>"></span>
        <div>
          <div class="activity-text"><strong><?= htmlspecialchars($r['full_name']) ?></strong> rated GeoGene <?= str_repeat('★', (int) $r['stars']) . str_repeat('☆', 5 - (int) $r['stars']) ?></div>
          <div class="activity-time"><?= date('M d, Y g:i A', strtotime($r['created_at'])) ?></div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>