
dashboard.php
100%
<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$totalRecords = (int) $pdo->query("SELECT COUNT(*) FROM civil_registry_records")->fetchColumn();
$verifiedUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 1 AND role = 'user'")->fetchColumn();
$pendingVerification = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE identity_verified = 0 AND email_verified = 1 AND role = 'user'")->fetchColumn();
$totalReports = (int) $pdo->query("SHOW TABLES LIKE 'staff_reports'")->rowCount() ? (int) $pdo->query("SELECT COUNT(*) FROM staff_reports")->fetchColumn() : 0;

$recentUsers = $pdo->query("SELECT full_name, email, status, last_seen, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Last 6 months of new user registrations, month-by-month (for the trend chart)
$monthlyRegs = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthlyRegs[$ym] = ['label' => date('M', strtotime("-$i months")), 'count' => 0];
}
$regRows = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM users
    WHERE role = 'user' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY ym
")->fetchAll();
foreach ($regRows as $row) {
    if (isset($monthlyRegs[$row['ym']])) {
        $monthlyRegs[$row['ym']]['count'] = (int) $row['cnt'];
    }
}
$regLabels = array_column($monthlyRegs, 'label');
$regCounts = array_column($monthlyRegs, 'count');

// Accounts created per month (last 12 months, all roles) - same data as the Admin dashboard chart
$accMonths = [];
$accStart = new DateTime('first day of this month');
$accStart->modify('-11 months');
for ($i = 0; $i < 12; $i++) {
    $d = (clone $accStart)->modify("+$i months");
    $accMonths[$d->format('Y-m')] = ['label' => $d->format('M y'), 'total' => 0, 'users' => 0];
}
$accRows = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt, SUM(role = 'user') AS u
    FROM users
    WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
    GROUP BY ym
")->fetchAll();
foreach ($accRows as $r) {
    if (isset($accMonths[$r['ym']])) {
        $accMonths[$r['ym']]['total'] = (int) $r['cnt'];
        $accMonths[$r['ym']]['users'] = (int) $r['u'];
    }
}
$accLabels = array_column($accMonths, 'label');
$accTotals = array_column($accMonths, 'total');
$accSum    = array_sum($accTotals);
$accUsers  = array_sum(array_column($accMonths, 'users'));

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
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;">
  <div class="panel">
    <div class="panel-header"><span class="panel-title">New User Registrations</span></div>
    <div style="position:relative;height:260px;">
      <canvas id="regChart"></canvas>
    </div>
  </div>
  <div class="panel">
    <div class="panel-header"><span class="panel-title">Verified vs Pending</span></div>
    <div style="position:relative;height:260px;display:flex;align-items:center;justify-content:center;">
      <canvas id="verifyChart"></canvas>
    </div>
  </div>
</div>

<div class="panel" style="margin-top:20px;">
  <div class="panel-header">
    <span class="panel-title">Accounts Created per Month (last 12 months)</span>
    <span style="font-size:12px;color:var(--text-secondary);"><?= number_format($accSum) ?> total &middot; <?= number_format($accUsers) ?> users &middot; <?= number_format($accSum - $accUsers) ?> staff/admin</span>
  </div>
  <div style="position:relative;height:260px;">
    <canvas id="accChart"></canvas>
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

    <a href="map.php" class="quick-tile"><div class="qt-icon">🗺</div><div class="qt-label">View Map</div></a>
  </div>
</div>

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

<script src="../assets/js/chart.umd.min.js"></script>
<script>
(function () {
  var regLabels = <?= json_encode($regLabels) ?>;
  var regCounts = <?= json_encode($regCounts) ?>;
  var verifiedCount = <?= (int) $verifiedUsers ?>;
  var pendingCount = <?= (int) $pendingVerification ?>;
  var accLabels = <?= json_encode($accLabels) ?>;
  var accTotals = <?= json_encode($accTotals) ?>;

  function cssVar(name) {
    return getComputedStyle(document.body).getPropertyValue(name).trim();
  }

  var regChart, verifyChart, accChart;

  function buildCharts() {
    var textColor = cssVar('--text-secondary') || '#6b6b6b';
    var gridColor = cssVar('--border') || '#e5e7eb';
    var primary = cssVar('--primary') || '#1b6e3c';

    if (regChart) regChart.destroy();
    if (verifyChart) verifyChart.destroy();
    if (accChart) accChart.destroy();

    regChart = new Chart(document.getElementById('regChart'), {
      type: 'line',
      data: {
        labels: regLabels,
        datasets: [{
          label: 'New users',
          data: regCounts,
          borderColor: primary,
          backgroundColor: primary + '26',
          fill: true,
          tension: 0.35,
          pointRadius: 3,
          pointBackgroundColor: primary
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
          y: { beginAtZero: true, ticks: { precision: 0, color: textColor, font: { size: 11 } }, grid: { color: gridColor } }
        }
      }
    });

    verifyChart = new Chart(document.getElementById('verifyChart'), {
      type: 'doughnut',
      data: {
        labels: ['Verified', 'Pending'],
        datasets: [{
          data: [verifiedCount, pendingCount],
          backgroundColor: [primary, gridColor],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { size: 12 }, boxWidth: 10, padding: 14 } }
        }
      }
    });

    // Shows the number on top of each bar
    var barValueLabels = {
      id: 'barValueLabels',
      afterDatasetsDraw: function (chart) {
        var ctx = chart.ctx;
        ctx.save();
        ctx.fillStyle = cssVar('--text-primary') || textColor;
        ctx.font = '700 11px sans-serif';
        ctx.textAlign = 'center';
        chart.getDatasetMeta(0).data.forEach(function (bar, i) {
          ctx.fillText(String(chart.data.datasets[0].data[i]), bar.x, bar.y - 6);
        });
        ctx.restore();
      }
    };

    accChart = new Chart(document.getElementById('accChart'), {
      type: 'bar',
      data: {
        labels: accLabels,
        datasets: [{
          label: 'New accounts',
          data: accTotals,
          backgroundColor: primary,
          borderRadius: 4,
          maxBarThickness: 34
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 18 } },
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
          y: { beginAtZero: true, ticks: { precision: 0, color: textColor, font: { size: 11 } }, grid: { color: gridColor } }
        }
      },
      plugins: [barValueLabels]
    });
  }

  buildCharts();

  // Redraw with fresh colors whenever the light/dark toggle flips body.dark-mode
  new MutationObserver(buildCharts).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
Displaying dashboard.php.