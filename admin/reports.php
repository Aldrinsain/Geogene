<?php
require_once __DIR__ . '/includes/admin-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$totalUserLogs = (int) $pdo->query("
    SELECT COUNT(*) FROM login_logs l JOIN users u ON u.user_id = l.user_id WHERE u.role = 'user'
")->fetchColumn();

$hasActivityLog = (bool) $pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();
$totalStaffLogs = $hasActivityLog
    ? (int) $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE role IN ('office_head','admin')")->fetchColumn()
    : 0;

$pageTitle = 'Reports';
$pageSub = 'Generate and print User Logs and Staff Activity Logs.';
$activeAdminNav = 'reports';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="quick-tiles" style="grid-template-columns:repeat(2,1fr);">
  <a href="reports-user-logs.php" class="quick-tile" style="padding:26px 18px;">
    <div class="qt-icon" style="height:auto;margin-bottom:12px;font-size:30px;"><svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-person-fill-up" viewBox="0 0 16 16">
  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.354-5.854 1.5 1.5a.5.5 0 0 1-.708.708L13 11.707V14.5a.5.5 0 0 1-1 0v-2.793l-.646.647a.5.5 0 0 1-.708-.708l1.5-1.5a.5.5 0 0 1 .708 0M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
  <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4"/>
</svg></div>
    <div class="qt-label" style="font-size:14px;font-weight:800;">User Logs</div>
    <div style="font-size:12px;color:var(--text-secondary);margin-top:6px;"><?= number_format((int) $totalUserLogs) ?> entries · Search, filter, print</div>
  </a>
  <a href="reports-staff-logs.php" class="quick-tile" style="padding:26px 18px;">
    <div class="qt-icon" style="height:auto;margin-bottom:12px;font-size:30px;"><?xml version="1.0" encoding="utf-8"?>
<!-- Generator: Adobe Illustrator 16.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="50px" height="50px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
<g>
	<path d="M16.197,12.512c1.683-1.271,2.777-3.283,2.777-5.551c0-3.838-3.122-6.961-6.96-6.961c-3.838,0-6.96,3.123-6.96,6.961
		c0,2.268,1.095,4.279,2.778,5.551C5.44,13.77,3.66,16.253,3.66,18.605V24h16.68v-5.395C20.34,16.245,18.581,13.762,16.197,12.512z
		 M6.038,6.961c0-1.074,0.289-2.081,0.787-2.953c0.23,0.283,0.478,0.544,0.757,0.761c1.081,0.84,2.506,1.201,4.063,1.25
		c0.123,0.003,0.246,0.007,0.37,0.007c0.459,0,0.921-0.025,1.377-0.087c0.371-0.049,0.855-0.271,1.216-0.245
		c0.378,0.027,1.317,0.384,1.456,0.771c0.009-0.211-0.057-0.402-0.095-0.606c0.083,0.136,0.208,0.228,0.362,0.254
		c0.007-0.16,0.004-0.326,0.011-0.486c0.081,0.043,0.272,0.063,0.346,0.123c-0.039-0.031,0.022-1.159,0.056-1.254
		c0.066-0.193,0.237-0.385,0.408-0.573c0.53,0.893,0.84,1.929,0.84,3.04c0,3.296-2.681,5.978-5.977,5.978
		C8.719,12.938,6.038,10.257,6.038,6.961z M15.502,16.622l1.587,1.559l-3.615,3.927l-1.305,1.418
		c-0.003,0.005-0.006,0.007-0.01,0.012c-0.029,0.036-0.055,0.069-0.076,0.095c-0.037,0.055-0.058,0.086-0.058,0.086
		c-0.001-0.003-0.002-0.006-0.004-0.009c-0.002,0.002-0.007,0.009-0.007,0.009l-0.011-0.012l-0.011,0.012l0.006-0.019l-0.186-0.201
		l-1.428-1.551l-3.468-3.767l1.608-1.579l0.29-0.347l-0.45-0.15l-2.545-0.745l2.238-2.67l0.004-0.007
		c0.966,0.67,2.108,1.1,3.342,1.208l-0.271,0.287l-0.422,0.51l0.996,1.158c0,0-0.636,0.739-0.948,3.844l1.236,3.967
		c0.003,0.006,0.007,0.013,0.01,0.02l1.263-4.052c-0.321-3.081-0.995-3.778-0.995-3.778l0.975-1.134l-0.766-0.813
		c1.284-0.087,2.475-0.517,3.476-1.208l0.045,0.063l2.185,2.606l-2.504,0.733l-0.49,0.162L15.502,16.622z"/>
</g>
</svg>
</div>
    <div class="qt-label" style="font-size:14px;font-weight:800;">Staff Activity Logs</div>
    <div style="font-size:12px;color:var(--text-secondary);margin-top:6px;"><?= number_format((int) $totalStaffLogs) ?> entries · Search, filter, print</div>
  </a>
</div>

<p style="font-size:12px;color:var(--text-secondary);margin-top:16px;">User Logs and Staff Activity Logs are kept separate, as required for MCRO reporting.</p>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
