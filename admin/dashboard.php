<?php
require_once __DIR__ . '/includes/admin-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$hasActivityLog = (bool) $pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();

// ---------- USER STATISTICS ----------
$totalUsers   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$activeUsers  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'")->fetchColumn();
$pendingUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'pending'")->fetchColumn();
$userActivePct = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;

// ---------- STAFF STATISTICS ----------
// Note: staff.php files are never touched by the Admin module, so "staff
// activity" here is measured from data that already existed before the
// Admin module: their LOGIN history (login_logs). activity_logs (if
// present) only ever records actions ADMIN performs on the Staff Management
// page itself - it is never written to from inside the Staff portal.
$totalStaff  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'office_head'")->fetchColumn();
$activeStaff = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'office_head' AND status = 'active'")->fetchColumn();

$mostActiveStaff = $pdo->query("
    SELECT u.full_name, COUNT(*) AS login_count
    FROM login_logs l
    JOIN users u ON u.user_id = l.user_id
    WHERE u.role = 'office_head'
    GROUP BY l.user_id
    ORDER BY login_count DESC
    LIMIT 5
")->fetchAll();

// ---------- SYSTEM ACTIVITY (recent, combined) ----------
// Recent staff/admin logins (login_logs, existing/untouched) + recent
// Admin actions on Staff Management (activity_logs, Admin-only).
$recentActivity = $pdo->query("
    SELECT * FROM (
        SELECT u.full_name, u.role, 'Logged in' AS activity, NULL AS module, l.login_at AS created_at
        FROM login_logs l JOIN users u ON u.user_id = l.user_id
        WHERE u.role IN ('office_head','admin')
    ) combined
    ORDER BY created_at DESC LIMIT 8
")->fetchAll();
if ($hasActivityLog) {
    $adminActionRows = $pdo->query("
        SELECT u.full_name, a.role, a.activity, a.module, a.created_at
        FROM activity_logs a JOIN users u ON u.user_id = a.user_id
        ORDER BY a.created_at DESC LIMIT 8
    ")->fetchAll();
    $recentActivity = array_merge($recentActivity, $adminActionRows);
    usort($recentActivity, fn($x, $y) => strtotime($y['created_at']) <=> strtotime($x['created_at']));
    $recentActivity = array_slice($recentActivity, 0, 8);
}

// ---------- CHARTS ----------
// Last 6 months: new user registrations vs staff/admin login volume
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $months[$ym] = ['label' => date('M', strtotime("-$i months")), 'users' => 0, 'staffActions' => 0];
}
$userRegRows = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM users WHERE role = 'user' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY ym
")->fetchAll();
foreach ($userRegRows as $r) {
    if (isset($months[$r['ym']])) $months[$r['ym']]['users'] = (int) $r['cnt'];
}
$staffLoginRows = $pdo->query("
    SELECT DATE_FORMAT(l.login_at, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM login_logs l JOIN users u ON u.user_id = l.user_id
    WHERE u.role IN ('office_head','admin') AND l.login_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY ym
")->fetchAll();
foreach ($staffLoginRows as $r) {
    if (isset($months[$r['ym']])) $months[$r['ym']]['staffActions'] = (int) $r['cnt'];
}
$chartLabels = array_column($months, 'label');
$chartUsers = array_column($months, 'users');
$chartStaffActions = array_column($months, 'staffActions');

$totalRecords = (int) $pdo->query("SELECT COUNT(*) FROM civil_registry_records")->fetchColumn();

$pageTitle = 'Admin Dashboard';
$pageSub = 'Municipal Civil Registry Office - Manolo Fortich · System overview.';
$activeAdminNav = 'dashboard';
require_once __DIR__ . '/includes/admin-header.php';
require_once __DIR__ . '/includes/svg-charts.php';
?>

<style>
  .qt-icon{display:flex;align-items:center;justify-content:center;height:30px;margin-bottom:8px;}
  .qt-icon svg{width:26px;height:26px;display:block;}
  .stat-icon.img-box, .stat-icon.green, .stat-icon.blue, .stat-icon.orange, .stat-icon.red{
    background:#fff !important;
    border:1px solid var(--border);
    color:#1a1a1a !important;
  }
</style>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon green"><?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-group-outline" width="24" height="24" viewBox="0 0 24 24"><path d="M12,5A3.5,3.5 0 0,0 8.5,8.5A3.5,3.5 0 0,0 12,12A3.5,3.5 0 0,0 15.5,8.5A3.5,3.5 0 0,0 12,5M12,7A1.5,1.5 0 0,1 13.5,8.5A1.5,1.5 0 0,1 12,10A1.5,1.5 0 0,1 10.5,8.5A1.5,1.5 0 0,1 12,7M5.5,8A2.5,2.5 0 0,0 3,10.5C3,11.44 3.53,12.25 4.29,12.68C4.65,12.88 5.06,13 5.5,13C5.94,13 6.35,12.88 6.71,12.68C7.08,12.47 7.39,12.17 7.62,11.81C6.89,10.86 6.5,9.7 6.5,8.5C6.5,8.41 6.5,8.31 6.5,8.22C6.2,8.08 5.86,8 5.5,8M18.5,8C18.14,8 17.8,8.08 17.5,8.22C17.5,8.31 17.5,8.41 17.5,8.5C17.5,9.7 17.11,10.86 16.38,11.81C16.5,12 16.63,12.15 16.78,12.3C16.94,12.45 17.1,12.58 17.29,12.68C17.65,12.88 18.06,13 18.5,13C18.94,13 19.35,12.88 19.71,12.68C20.47,12.25 21,11.44 21,10.5A2.5,2.5 0 0,0 18.5,8M12,14C9.66,14 5,15.17 5,17.5V19H19V17.5C19,15.17 14.34,14 12,14M4.71,14.55C2.78,14.78 0,15.76 0,17.5V19H3V17.07C3,16.06 3.69,15.22 4.71,14.55M19.29,14.55C20.31,15.22 21,16.06 21,17.07V19H24V17.5C24,15.76 21.22,14.78 19.29,14.55M12,16C13.53,16 15.24,16.5 16.23,17H7.77C8.76,16.5 10.47,16 12,16Z" /></svg></div>
    <div><div class="stat-num"><?= number_format((int) $totalUsers) ?></div><div class="stat-label">Total Registered Users</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">✔</div>
    <div><div class="stat-num"><?= number_format((int) $activeUsers) ?></div><div class="stat-label">Active Users (<?= $userActivePct ?>%)</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-box-multiple" width="24" height="24" viewBox="0 0 24 24"><path d="M4,6H2V20A2,2 0 0,0 4,22H18V20H4V6M20,2A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H8A2,2 0 0,1 6,16V4A2,2 0 0,1 8,2H20M17,7A3,3 0 0,0 14,4A3,3 0 0,0 11,7A3,3 0 0,0 14,10A3,3 0 0,0 17,7M8,15V16H20V15C20,13 16,11.9 14,11.9C12,11.9 8,13 8,15Z" /></svg></div>
    <div><div class="stat-num"><?= number_format((int) $totalStaff) ?></div><div class="stat-label">Total Staff Accounts</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><?xml version="1.0" ?><svg height="24" id="svg8" version="1.1" viewBox="0 0 6.3499998 6.3500002" width="24" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:svg="http://www.w3.org/2000/svg"><defs id="defs2"/><g id="layer1"><path d="M 3.1708661,3.2974124 C 1.907863,3.2997936 0.86304935,4.299744 0.79995125,5.5634239 A 0.26460945,0.26460945 0 0 0 1.0655682,5.8404096 H 5.2864993 A 0.26460945,0.26460945 0 0 0 5.5500489,5.5634239 C 5.48687,4.2981438 4.4396051,3.2975523 3.1750001,3.2974124 Z" id="path1332" style="color:#000000;font-style:normal;font-variant:normal;font-weight:normal;font-stretch:normal;font-size:medium;line-height:normal;font-family:sans-serif;font-variant-ligatures:normal;font-variant-position:normal;font-variant-caps:normal;font-variant-numeric:normal;font-variant-alternates:normal;font-variant-east-asian:normal;font-feature-settings:normal;font-variation-settings:normal;text-indent:0;text-align:start;text-decoration:none;text-decoration-line:none;text-decoration-style:solid;text-decoration-color:#000000;letter-spacing:normal;word-spacing:normal;text-transform:none;writing-mode:lr-tb;direction:ltr;text-orientation:mixed;dominant-baseline:auto;baseline-shift:baseline;text-anchor:start;white-space:normal;shape-padding:0;shape-margin:0;inline-size:0;clip-rule:nonzero;display:inline;overflow:visible;visibility:visible;isolation:auto;mix-blend-mode:normal;color-interpolation:sRGB;color-interpolation-filters:linearRGB;solid-color:#000000;solid-opacity:1;vector-effect:none;fill:#000000;fill-opacity:1;fill-rule:nonzero;stroke:none;stroke-width:0.529167;stroke-linecap:butt;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none;stroke-dashoffset:0;stroke-opacity:1;paint-order:stroke fill markers;color-rendering:auto;image-rendering:auto;shape-rendering:auto;text-rendering:auto;enable-background:accumulate;stop-color:#000000"/><path d="m 3.1750901,0.5095872 c -0.669506,0 -1.2153281,0.54839 -1.2153321,1.21864 -1.9e-6,0.6702501 0.5458221,1.2205301 1.2153321,1.2205301 0.6695099,0 1.217222,-0.55028 1.2172191,-1.2205301 -4e-6,-0.67025 -0.5477132,-1.21864 -1.2172191,-1.21864 z" id="path1292" style="color:#000000;font-style:normal;font-variant:normal;font-weight:normal;font-stretch:normal;font-size:medium;line-height:normal;font-family:sans-serif;font-variant-ligatures:normal;font-variant-position:normal;font-variant-caps:normal;font-variant-numeric:normal;font-variant-alternates:normal;font-variant-east-asian:normal;font-feature-settings:normal;font-variation-settings:normal;text-indent:0;text-align:start;text-decoration:none;text-decoration-line:none;text-decoration-style:solid;text-decoration-color:#000000;letter-spacing:normal;word-spacing:normal;text-transform:none;writing-mode:lr-tb;direction:ltr;text-orientation:mixed;dominant-baseline:auto;baseline-shift:baseline;text-anchor:start;white-space:normal;shape-padding:0;shape-margin:0;inline-size:0;clip-rule:nonzero;display:inline;overflow:visible;visibility:visible;isolation:auto;mix-blend-mode:normal;color-interpolation:sRGB;color-interpolation-filters:linearRGB;solid-color:#000000;solid-opacity:1;vector-effect:none;fill:#000000;fill-opacity:1;fill-rule:nonzero;stroke:none;stroke-width:0.529167;stroke-linecap:butt;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none;stroke-dashoffset:0;stroke-opacity:1;paint-order:stroke fill markers;color-rendering:auto;image-rendering:auto;shape-rendering:auto;text-rendering:auto;enable-background:accumulate;stop-color:#000000"/></g></svg></div>
    <div><div class="stat-num"><?= number_format((int) $activeStaff) ?></div><div class="stat-label">Active Staff</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;">
  <div class="panel">
    <div class="panel-header"><span class="panel-title">User Registrations vs Staff/Admin Logins (last 6 months)</span></div>
    <div style="height:260px;">
      <?= geogene_svg_line_chart($chartLabels, [
            ['label' => 'New Users', 'color' => '#1b6e3c', 'data' => $chartUsers],
            ['label' => 'Staff/Admin Logins', 'color' => '#3b82f6', 'data' => $chartStaffActions],
          ]) ?>
    </div>
    <?= geogene_chart_legend([
          ['label' => 'New Users', 'color' => '#1b6e3c'],
          ['label' => 'Staff/Admin Logins', 'color' => '#3b82f6'],
        ]) ?>
  </div>
  <div class="panel">
    <div class="panel-header"><span class="panel-title">User Status</span></div>
    <div style="height:190px;display:flex;align-items:center;justify-content:center;">
      <?= geogene_svg_donut_chart([
            ['label' => 'Active', 'value' => $activeUsers, 'color' => '#1b6e3c'],
            ['label' => 'Pending', 'value' => $pendingUsers, 'color' => '#f59e0b'],
            ['label' => 'Disabled', 'value' => max(0, $totalUsers - $activeUsers - $pendingUsers), 'color' => '#ef4444'],
          ]) ?>
    </div>
    <?= geogene_chart_legend([
          ['label' => 'Active', 'color' => '#1b6e3c'],
          ['label' => 'Pending', 'color' => '#f59e0b'],
          ['label' => 'Disabled', 'color' => '#ef4444'],
        ]) ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="panel">
    <div class="panel-header"><span class="panel-title">Most Active Staff</span></div>
    <?php if (!$mostActiveStaff): ?>
      <p style="font-size:12.5px;color:var(--text-muted);">No staff activity recorded yet.</p>
    <?php else: foreach ($mostActiveStaff as $s): ?>
      <div class="activity-item">
        <span class="activity-dot green"></span>
        <div>
          <div class="activity-text"><strong><?= htmlspecialchars($s['full_name']) ?></strong></div>
          <div class="activity-time"><?= (int) $s['login_count'] ?> login(s) recorded</div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="panel">
    <div class="panel-header"><span class="panel-title">Recent System Activity</span></div>
    <?php if (!$recentActivity): ?>
      <p style="font-size:12.5px;color:var(--text-muted);">No recent activity recorded yet.</p>
    <?php else: foreach ($recentActivity as $a): ?>
      <div class="activity-item">
        <span class="activity-dot <?= ($a['role'] ?? '') === 'admin' ? 'blue' : 'green' ?>"></span>
        <div>
          <div class="activity-text"><strong><?= htmlspecialchars($a['full_name']) ?></strong> &mdash; <?= htmlspecialchars($a['activity']) ?><?= !empty($a['module']) ? ' (' . htmlspecialchars($a['module']) . ')' : '' ?></div>
          <div class="activity-time"><?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><span class="panel-title">Quick Access</span></div>
  <div class="quick-tiles">
    <a href="staff-management.php" class="quick-tile"><div class="qt-icon"><?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-group" width="24" height="24" viewBox="0 0 24 24"><path d="M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z" /></svg></div><div class="qt-label">Staff Management</div></a>
    <a href="user-monitoring.php" class="quick-tile"><div class="qt-icon"><?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-multiple-check" width="24" height="24" viewBox="0 0 24 24"><path d="M19 17V19H7V17S7 13 13 13 19 17 19 17M16 8A3 3 0 1 0 13 11A3 3 0 0 0 16 8M19.2 13.06A5.6 5.6 0 0 1 21 17V19H24V17S24 13.55 19.2 13.06M18 5A2.91 2.91 0 0 0 17.11 5.14A5 5 0 0 1 17.11 10.86A2.91 2.91 0 0 0 18 11A3 3 0 0 0 18 5M7.34 8.92L8.5 10.33L3.75 15.08L1 12.08L2.16 10.92L3.75 12.5L7.34 8.92" /></svg></div><div class="qt-label">User Monitoring</div></a>
    <a href="civil-registry.php" class="quick-tile"><div class="qt-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" id="records">
            <path fill="currentColor" d="M4 9h4v2H4V9z"></path>
            <path fill="currentColor" d="M16 2h-1V0H5v2H3v1.25L2.4 4H1v1.75L0 7v9h12l4-5V2zM2 5h8v2H2V5zm9 10H1V8h10v7zm1-8h-1V4H4V3h8v4zm2-2.5l-1 1.25V2H6V1h8v3.5z"></path>
        </svg></div><div class="qt-label">Civil Registry (<?= number_format((int) $totalRecords) ?>)</div></a>
    <a href="family-tree.php" class="quick-tile"><div class="qt-icon"><svg
            viewBox="-102.4 -102.4 1228.80 1228.80"
            class="icon"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
            fill="currentColor"
            transform="rotate(90)"
            width="40"
            height="40">

            <path
                d="M853.333333 192V149.333333h-192v106.666667H320v234.666667H170.666667v42.666666h149.333333v234.666667h341.333333v106.666667h192v-42.666667h-149.333333v-170.666667h149.333333v-42.666666h-192v106.666667H362.666667V298.666667h298.666666v106.666666h192v-42.666666h-149.333333V192z"
                fill="currentColor">
            </path>

            <path
                d="M85.333333 426.666667h170.666667v170.666666H85.333333z"
                fill="currentColor">
            </path>

            <path
                d="M768 298.666667h170.666667v170.666667h-170.666667z
                   M768 85.333333h170.666667v170.666667h-170.666667z
                   M426.666667 192h170.666667v170.666667h-170.666667z
                   M426.666667 661.333333h170.666667v170.666667h-170.666667z
                   M768 768h170.666667v170.666667h-170.666667z
                   M768 554.666667h170.666667v170.666666h-170.666667z"
                fill="currentColor">
            </path>

        </svg></div><div class="qt-label">Family Tree</div></a>
    <a href="map.php" class="quick-tile"><div class="qt-icon">🗺</div><div class="qt-label">Map View</div></a>
    <a href="reports.php" class="quick-tile"><div class="qt-icon"><?xml version="1.0" ?><svg id="Layer_1" style="enable-background:new 0 0 64 64;" version="1.1" viewBox="0 0 64 64" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M43.98502,0h-36.88v64h49.78998V12.91998L43.98502,0z M54.89499,62H9.10501V2h33.45996v12.33002h12.33002V62z"/><polygon points="47.50497,37.14001 41.94498,37.14001 41.94498,56.27002 39.06497,56.27002 39.06497,40.14001 33.50497,40.14001    33.50497,56.27002 30.62497,56.27002 30.62497,43.14001 25.06497,43.14001 25.06497,56.27002 22.18497,56.27002 22.18497,46.14001    16.62497,46.14001 16.62497,56.27002 13.51498,56.27002 13.51498,58.27002 50.49496,58.27002 50.49496,56.27002 47.50497,56.27002     "/><rect height="2" width="18.3916" x="16.64163" y="9.78815"/><rect height="2" width="34.41699" x="13.65823" y="24.51764"/><rect height="2" width="34.41699" x="13.65823" y="29.62213"/><rect height="2" width="34.41699" x="13.65823" y="19.41315"/></g></svg></div><div class="qt-label">Reports</div></a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
