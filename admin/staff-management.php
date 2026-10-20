<?php
require_once __DIR__ . '/includes/admin-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity-log.php';
require_once __DIR__ . '/../config/account-status.php';

$error = '';
$success = '';

// ---------- ADD STAFF ACCOUNT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_staff') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '' || strlen($password) < 6) {
        $error = 'Full name, a valid email, and a password (min. 6 characters) are required.';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, role, status, email_verified, identity_verified)
                VALUES (?, ?, ?, 'office_head', 'active', 1, 1)
            ")->execute([$fullName, $email, $hash]);

            geogene_log_activity($pdo, (int) $_SESSION['staff_id'], 'Added Staff Account', 'Staff Management', $fullName . ' (' . $email . ')', 'admin');
            $success = 'Staff account for ' . htmlspecialchars($fullName) . ' was created successfully.';
        }
    }
}

// ---------- ENABLE / DISABLE STAFF ----------
if (isset($_GET['toggle'])) {
    $uid = (int) $_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status, full_name FROM users WHERE user_id = ? AND role = 'office_head'");
    $stmt->execute([$uid]);
    $target = $stmt->fetch();
    if ($target) {
        // active -> disabled (stored as the valid ENUM value); anything else -> active again
        $newStatus = $target['status'] === 'active' ? GEOGENE_DB_DISABLED : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $uid]);
        geogene_log_activity($pdo, (int) $_SESSION['staff_id'], 'Managed Staff (' . ($newStatus === 'active' ? 'Enabled' : 'Disabled') . ')', 'Staff Management', $target['full_name'], 'admin');
    }
    header('Location: staff-management.php');
    exit;
}

// ---------- LIST STAFF ----------
$search = trim($_GET['q'] ?? '');
$where = ["role = 'office_head'"];
$params = [];
if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$staffList = $stmt->fetchAll();

$totalStaff = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'office_head'")->fetchColumn();
$activeStaffCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'office_head' AND status = 'active'")->fetchColumn();

// ---------- RECENT STAFF ACTIVITY (monitoring preview) ----------
// staff/*.php files are never touched by the Admin module, so this is
// built from their existing LOGIN history (login_logs) - the same table
// the Staff portal has always written to.
$hasActivityLog = (bool) $pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();
$recentActivity = $pdo->query("
    SELECT u.full_name, 'Logged in' AS activity, NULL AS module, NULL AS description, l.login_at AS created_at
    FROM login_logs l
    JOIN users u ON u.user_id = l.user_id
    WHERE u.role = 'office_head'
    ORDER BY l.login_at DESC
    LIMIT 12
")->fetchAll();

function is_online_am(?string $lastSeen): bool
{
    return $lastSeen && (time() - strtotime($lastSeen)) <= 300;
}

$pageTitle = 'Staff Management';
$pageSub = 'Add, view, and manage Civil Registry Staff accounts.';
$activeAdminNav = 'staff';
require_once __DIR__ . '/includes/admin-header.php';
?>

<?php
// Icons for the Enable / Disable buttons. Stroke = currentColor, so they follow the
// button colour and stay clearly visible in BOTH light mode and dark mode.
$iconBan   = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M5.3 5.3l13.4 13.4"/></svg>';
$iconCheck = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M8 12.5l2.7 2.7L16 9.5"/></svg>';
?>
<style>
  .stat-icon.green, .stat-icon.blue, .stat-icon.orange, .stat-icon.red{
    background:#fff; border:1px solid var(--border); color:#1a1a1a;
  }

  /* ---- Enable / Disable buttons (clear in light AND dark mode) ---- */
  .staff-act{
    display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;
    font-size:12px;font-weight:700;line-height:1;text-decoration:none;white-space:nowrap;
    border:1px solid transparent;cursor:pointer;transition:background .15s,border-color .15s;
  }
  .staff-act svg{flex:none;display:block;}
  .staff-act.disable{color:#b42318;background:#fff5f4;border-color:#f1b5af;}
  .staff-act.disable:hover{background:#fde8e6;border-color:#e0786f;}
  .staff-act.enable{color:#15803d;background:#f0faf3;border-color:#a9dcbc;}
  .staff-act.enable:hover{background:#dff3e6;border-color:#5fb77f;}
  body.dark-mode .staff-act.disable{color:#ff9d94;background:#2d1916;border-color:#80322b;}
  body.dark-mode .staff-act.disable:hover{background:#3d201c;border-color:#b3463d;}
  body.dark-mode .staff-act.enable{color:#7ee2a8;background:#14291d;border-color:#2e7048;}
  body.dark-mode .staff-act.enable:hover{background:#1b3a28;border-color:#3f9a62;}

  /* ---- Status badges readable in dark mode ---- */
  body.dark-mode .badge.green{background:#14291d;color:#7ee2a8;}
  body.dark-mode .badge.red{background:#2d1916;color:#ff9d94;}
  body.dark-mode .badge.orange{background:#2c2413;color:#f6c453;}

  /* ---- Add Staff modal: follows the theme (was always white => unreadable in dark mode) ---- */
  .gg-modal-overlay{position:fixed;inset:0;background:rgba(10,15,12,0.6);align-items:center;justify-content:center;z-index:100;}
  .gg-modal{
    background:var(--bg-card);color:var(--text-main);border:1px solid var(--border);
    border-radius:16px;padding:26px;width:420px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,0.4);
  }
  .gg-modal h3{font-size:15px;font-weight:800;margin:0 0 16px;color:var(--text-main);}
  .gg-modal label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--text-main);}
  .gg-modal input{
    width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;
    background:var(--bg-page);color:var(--text-main);
  }
  .gg-modal input::placeholder{color:var(--text-muted);}
  .gg-modal input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(27,110,60,0.22);}
  /* browser autofill: keep the themed colours instead of the blue/black autofill box */
  .gg-modal input:-webkit-autofill,
  .gg-modal input:-webkit-autofill:focus{
    -webkit-box-shadow:0 0 0 1000px var(--bg-page) inset;-webkit-text-fill-color:var(--text-main);caret-color:var(--text-main);
  }
</style>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon green"><?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-group" width="24" height="24" viewBox="0 0 24 24"><path d="M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z" /></svg></div>
    <div><div class="stat-num"><?= number_format((int) $totalStaff) ?></div><div class="stat-label">Total Staff Accounts</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><?xml version="1.0" encoding="UTF-8"?><svg version="1.1" width="36" height="36"  viewBox="0 0 36 36" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <title>computer-outline-badged</title>
    <path d="M1,25v3.4A2.6,2.6,0,0,0,3.6,31H32.34a2.6,2.6,0,0,0,2.6-2.6V25Zm32,3.4a.6.6,0,0,1-.6.6H3.56a.6.6,0,0,1-.6-.6V26.53h9.95a1.64,1.64,0,0,0,1.5,1h7.13a1.64,1.64,0,0,0,1.5-1H33Z" class="clr-i-outline--badged clr-i-outline-path-1--badged"></path><path d="M22.5,6a7.52,7.52,0,0,1,.07-1H5.5A1.5,1.5,0,0,0,4,6.5V23H6V7H22.57A7.52,7.52,0,0,1,22.5,6Z" class="clr-i-outline--badged clr-i-outline-path-2--badged"></path><path d="M30,13.5V23h2V13.22A7.49,7.49,0,0,1,30,13.5Z" class="clr-i-outline--badged clr-i-outline-path-3--badged"></path><path d="M23.13,9H8V22.88H9.6V10.6H24.08A7.49,7.49,0,0,1,23.13,9Z" class="clr-i-outline--badged clr-i-outline-path-4--badged"></path><circle cx="30" cy="6" r="5" class="clr-i-outline--badged clr-i-outline-path-5--badged clr-i-badge"></circle>
    <rect x="0" y="0" width="36" height="36" fill-opacity="0"/>
</svg>
</div>
    <div><div class="stat-num"><?= number_format((int) $activeStaffCount) ?></div><div class="stat-label">Active Staff</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M2.22 2.22c.3-.3.77-.3 1.06 0l18.5 18.5a.75.75 0 0 1-1.06 1.06L17.94 19H2.75a.75.75 0 0 1 0-1.5h13.69l-1.5-1.5H6.25C5.01 16 4 15 4 13.75v-7.5q0-.53.22-.97l-2-2a.75.75 0 0 1 0-1.06M17.75 4C18.99 4 20 5 20 6.25v7.5c0 .83-.45 1.56-1.12 1.95L7.18 4z"/></svg></div>
    <div><div class="stat-num"><?= number_format((int) $totalStaff - (int) $activeStaffCount) ?></div><div class="stat-label">Disabled Staff</div></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">All Staff Accounts (<?= number_format(count($staffList)) ?>)</span>
    <button type="button" class="btn btn-primary btn-sm" id="openAddStaffBtn">+ Add Staff Account</button>
  </div>

  <?php if ($error): ?><p style="color:#c0392b;font-size:12.5px;margin-bottom:12px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p style="color:#1b6e3c;font-size:12.5px;margin-bottom:12px;"><?= $success ?></p><?php endif; ?>

  <form method="GET" class="table-toolbar">
    <div class="search-box"><span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span><input type="text" name="q" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>"></div>
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
  </form>

  <table class="data-table">
    <thead><tr><th>Full Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Online</th><th>Date Added</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (!$staffList): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:30px;">No staff accounts found.</td></tr>
      <?php else: foreach ($staffList as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td><?= htmlspecialchars($s['phone_number'] ?: '—') ?></td>
          <td><span class="badge <?= geogene_account_badge_class($s['status']) ?>"><?= geogene_account_status_label($s['status']) ?></span></td>
          <td><span class="activity-dot <?= is_online_am($s['last_seen']) ? 'green' : 'silver' ?>" title="<?= is_online_am($s['last_seen']) ? 'Online' : 'Offline' ?>"></span></td>
          <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
          <td>
            <?php $isOn = ($s['status'] === 'active'); ?>
            <a href="?toggle=<?= (int) $s['user_id'] ?>" class="staff-act <?= $isOn ? 'disable' : 'enable' ?>"
               title="<?= $isOn ? 'Disable this account' : 'Enable this account' ?>"
               aria-label="<?= $isOn ? 'Disable' : 'Enable' ?> <?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>"
               onclick="return confirm('<?= $isOn ? 'Disable this staff account? They will no longer be able to log in.' : 'Enable this staff account?' ?>');">
              <?= $isOn ? $iconBan : $iconCheck ?><span><?= $isOn ? 'Disable' : 'Enable' ?></span>
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">Staff Activity Monitoring (recent)</span>
    <a href="reports-staff-logs.php" class="btn btn-outline btn-sm">Full Report</a>
  </div>
  <?php if (!$recentActivity): ?>
    <p style="font-size:12.5px;color:var(--text-muted);">No staff activity recorded yet.</p>
  <?php else: foreach ($recentActivity as $a): ?>
    <div class="activity-item">
      <span class="activity-dot green"></span>
      <div>
        <div class="activity-text"><strong><?= htmlspecialchars($a['full_name']) ?></strong> &mdash; <?= htmlspecialchars($a['activity']) ?><?= !empty($a['module']) ? ' (' . htmlspecialchars($a['module']) . ')' : '' ?><?= !empty($a['description']) ? ': ' . htmlspecialchars($a['description']) : '' ?></div>
        <div class="activity-time"><?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<!-- Add Staff Modal -->
<div id="addStaffOverlay" class="gg-modal-overlay" style="display:none;">
  <div class="gg-modal" role="dialog" aria-modal="true" aria-labelledby="addStaffTitle">
    <h3 id="addStaffTitle">Add Staff Account</h3>
    <form method="POST" id="addStaffForm" autocomplete="off">
      <input type="hidden" name="action" value="add_staff">
      <div class="form-row" style="margin-bottom:12px;">
        <label for="asFullName">Full Name</label>
        <input type="text" id="asFullName" name="full_name" required autocomplete="off">
      </div>
      <div class="form-row" style="margin-bottom:12px;">
        <label for="asEmail">Email Address</label>
        <input type="email" id="asEmail" name="email" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
      </div>
      <div class="form-row" style="margin-bottom:18px;">
        <label for="asPassword">Temporary Password</label>
        <input type="password" id="asPassword" name="password" required minlength="6" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn btn-outline btn-sm" id="closeAddStaffBtn">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Create Account</button>
      </div>
    </form>
  </div>
</div>

<script>
  var addStaffOverlay = document.getElementById('addStaffOverlay');
  document.getElementById('openAddStaffBtn')?.addEventListener('click', () => {
    var f = document.getElementById('addStaffForm');
    if (f) f.reset();                       // wipe anything the browser autofilled (admin email / saved password)
    addStaffOverlay.style.display = 'flex';
    setTimeout(function () { document.getElementById('asFullName')?.focus(); }, 30);
  });
  document.getElementById('closeAddStaffBtn')?.addEventListener('click', () => addStaffOverlay.style.display = 'none');
  addStaffOverlay.addEventListener('click', (e) => { if (e.target === addStaffOverlay) addStaffOverlay.style.display = 'none'; });
  <?php if ($error || $success): ?>addStaffOverlay.style.display = '<?= $error ? 'flex' : 'none' ?>';<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
