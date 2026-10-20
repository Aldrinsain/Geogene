<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, cr.date_of_event AS birth_date, cr.barangay AS registry_barangay
    FROM users u
    LEFT JOIN civil_registry_records cr ON cr.record_id = u.matched_record_id
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmtS = $pdo->prepare("SELECT dark_mode FROM user_settings WHERE user_id = ?");
$stmtS->execute([$userId]);
$isDark = (bool) $stmtS->fetchColumn();

$activeNav = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - My Profile</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .content-scroll{padding-bottom:80px;}
  .app-header{justify-content:space-between;}
  .menu-dots{font-size:18px;color:var(--text-main);cursor:pointer;}

  .profile-avatar-wrap{text-align:center;margin:14px 0 24px;}
  .profile-avatar{
    width:88px;height:88px;border-radius:50%;
    background:linear-gradient(135deg, rgba(57,255,136,0.28), rgba(218, 98, 13, 0.08));color:var(--green);
    display:flex;align-items:center;justify-content:center;
    font-size:32px;font-weight:700;margin:0 auto 12px;
    border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);
    overflow:hidden;
  }
  .profile-avatar img{width:100%;height:100%;object-fit:cover;}
  .profile-name{font-size:19px;font-weight:700;color:var(--text-main);}
  .badge-verified{
    display:inline-flex;align-items:center;gap:5px;margin-top:8px;
    background:var(--primary-light);color:var(--green);
    font-size:11.5px;font-weight:700;padding:4px 12px;border-radius:20px;
  }
  .badge-unverified{
    display:inline-flex;align-items:center;gap:5px;margin-top:8px;
    background:#fbe7e7;color:var(--danger);
    font-size:11.5px;font-weight:700;padding:4px 12px;border-radius:20px;
  }

  .info-card{
    border:1.5px solid var(--primary);
    border-radius:16px;
    padding:6px 16px;
    margin-bottom:22px;
    box-shadow: 0 4px 5px rgba(0,0,0,0.14), 0 6px 10px rgba(0,0,0,0.12);
  }
  .info-row{
    display:flex;align-items:center;gap:12px;
    padding:13px 0;border-bottom:1px solid var(--border);
  }
  .info-row:last-child{border-bottom:none;}
  .info-icon{
    width:34px;
    height:34px;
    min-width:34px;
    min-height:34px;
    flex:0 0 34px;
    border-radius:50%;
    background:var(--primary-light);
    color:var(--green);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    line-height:1;
    overflow:hidden;
  }
  .info-icon svg{
    width:18px !important;
    height:18px !important;
    max-width:18px;
    max-height:18px;
    display:block;
    flex:0 0 18px;
  }
  .info-text .info-label{font-size:11px;color:var(--text-muted);margin-bottom:2px;}
  .info-text .info-value{font-size:13.5px;font-weight:600;color:var(--text-main);}
  .info-row.status .info-icon{background:var(--primary-light);color:var(--green);}

  body.light-bg{background:#e8e8e6;}
  body.dark-bg{background:#0b0f1f;}
  body{transition:background .5s ease;}

  .theme-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:10px 4px 12px;
    margin-bottom:8px;
  }
  .theme-label{
    font-size:13px;
    font-weight:600;
    color:var(--text-main);
    line-height:1.1;
  }
  .theme-control{
    display:flex;
    align-items:center;
    gap:7px;
    cursor:pointer;
    user-select:none;
  }
  .theme-state{
    min-width:22px;
    text-align:right;
    font-size:10px;
    font-weight:700;
    color:var(--text-muted);
    letter-spacing:.3px;
  }
  .scene{
    width:46px;
    height:24px;
    border-radius:14px;
    position:relative;
    overflow:hidden;
    cursor:pointer;
    border:1px solid rgba(0,0,0,0.10);
    transition:all .25s ease;
    flex:0 0 46px;
  }
  .scene.day{background:linear-gradient(180deg,#5bc8f5 0%,#a8dffa 100%);}
  .scene.night{background:linear-gradient(180deg,#0d1b3e 0%,#1a2e6e 100%);}
  .knob{
    position:absolute;
    top:2px;
    left:3px;
    width:18px;
    height:18px;
    border-radius:50%;
    transition:all .25s cubic-bezier(.4,0,.2,1);
  }
  .knob.day{left:3px;background:radial-gradient(circle at 35% 35%,#ffe066,#f5c518);}
  .knob.night{left:25px;background:radial-gradient(circle at 38% 30%,#d0d8f0,#9aa8c8);}
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="content-scroll">
    <div class="app-header"><span>My Profile</span><span class="menu-dots">⋮</span></div>

    <div class="profile-avatar-wrap">
      <div class="profile-avatar" id="profileAvatarCircle">
        <?php if (!empty($user['profile_photo'])): ?>
          <img id="profileAvatarImg" src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo">
        <?php else: ?>
          <span id="profileAvatarInitial"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
        <?php endif; ?>
      </div>
      <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
      <?php if ($user['identity_verified']): ?>
        <span class="badge-verified">Verified User</span>
      <?php else: ?>
        <span class="badge-unverified">Not Verified</span>
      <?php endif; ?>
    </div>

    <div class="info-card">
      <div class="info-row">
        <div class="info-icon">✉</div>
        <div class="info-text">
          <div class="info-label">Email Address</div>
          <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
  <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5Z" clip-rule="evenodd" />
</svg>
</div>
        <div class="info-text">
          <div class="info-label">Contact Number</div>
          <div class="info-value"><?= htmlspecialchars($user['phone_number'] ?: 'Not set') ?></div>
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
  <path d="M5.25 12a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H6a.75.75 0 0 1-.75-.75V12ZM6 13.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V14a.75.75 0 0 0-.75-.75H6ZM7.25 12a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H8a.75.75 0 0 1-.75-.75V12ZM8 13.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V14a.75.75 0 0 0-.75-.75H8ZM9.25 10a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H10a.75.75 0 0 1-.75-.75V10ZM10 11.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V12a.75.75 0 0 0-.75-.75H10ZM9.25 14a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H10a.75.75 0 0 1-.75-.75V14ZM12 9.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V10a.75.75 0 0 0-.75-.75H12ZM11.25 12a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H12a.75.75 0 0 1-.75-.75V12ZM12 13.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V14a.75.75 0 0 0-.75-.75H12ZM13.25 10a.75.75 0 0 1 .75-.75h.01a.75.75 0 0 1 .75.75v.01a.75.75 0 0 1-.75.75H14a.75.75 0 0 1-.75-.75V10ZM14 11.25a.75.75 0 0 0-.75.75v.01c0 .414.336.75.75.75h.01a.75.75 0 0 0 .75-.75V12a.75.75 0 0 0-.75-.75H14Z" />
  <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
</svg>
</div>
        <div class="info-text">
          <div class="info-label">Date of Birth</div>
          <?php $dob = $user['date_of_birth'] ?: $user['birth_date']; ?>
          <div class="info-value"><?= $dob ? date('F j, Y', strtotime($dob)) : 'Not set' ?></div>
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon">⚧</div>
        <div class="info-text">
          <div class="info-label">Gender</div>
          <div class="info-value"><?= !empty($user['gender']) ? ucwords(str_replace('_', ' ', $user['gender'])) : 'Not set' ?></div>
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0,0,256,256">
<g fill="#1fb141" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(8.53333,8.53333)"><path d="M15,3c-6.627,0 -12,5.373 -12,12c0,6.627 5.373,12 12,12c6.627,0 12,-5.373 12,-12c0,-6.627 -5.373,-12 -12,-12zM21.707,12.707l-7.56,7.56c-0.188,0.188 -0.442,0.293 -0.707,0.293c-0.265,0 -0.52,-0.105 -0.707,-0.293l-3.453,-3.453c-0.391,-0.391 -0.391,-1.023 0,-1.414c0.391,-0.391 1.023,-0.391 1.414,0l2.746,2.746l6.853,-6.853c0.391,-0.391 1.023,-0.391 1.414,0c0.391,0.391 0.391,1.023 0,1.414z"></path></g></g>
</svg></div>
        <div class="info-text">
          <div class="info-label">Civil Status</div>
          <div class="info-value"><?= !empty($user['civil_status']) ? ucfirst($user['civil_status']) : 'Not set' ?></div>
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
  <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
</svg>
</div>
        <div class="info-text">
          <div class="info-label">Address</div>
          <?php $displayBarangay = $user['barangay'] ?: $user['registry_barangay']; ?>
          <div class="info-value"><?= htmlspecialchars(($displayBarangay ? $displayBarangay . ', ' : '') . 'Manolo Fortich, Bukidnon') ?></div>
        </div>
      </div>
      <div class="info-row status">
        <div class="info-icon">✔</div>
        <div class="info-text">
          <div class="info-label">Account Status</div>
          <div class="info-value"><?= ucfirst($user['status']) ?></div>
        </div>
      </div>
    </div>

    <div class="theme-row">
      <div class="theme-control" onclick="toggleDark()" role="button" tabindex="0" aria-label="Toggle dark mode">
        <span class="theme-state" id="themeState"><?= $isDark ? 'ON' : 'OFF' ?></span>
        <div class="scene <?= $isDark ? 'night' : 'day' ?>" id="scene">
          <div class="knob <?= $isDark ? 'night' : 'day' ?>" id="knob"></div>
        </div>
      </div>
    </div>

    <a href="edit-profile.php" class="btn-primary" style="text-decoration:none;">Edit Profile</a>
    <a href="../settings/settings.php" class="btn-outline" style="text-decoration:none;text-align:center;display:block;">Settings</a>
  </div>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

<script>
let dark = <?= $isDark ? 'true' : 'false' ?>;

function toggleDark() {
  dark = !dark;
  const scene = document.getElementById('scene');
  const knob = document.getElementById('knob');
  const themeState = document.getElementById('themeState');

  scene.className = 'scene ' + (dark ? 'night' : 'day');
  knob.className = 'knob ' + (dark ? 'night' : 'day');
  document.body.className = dark ? 'dark-bg' : 'light-bg';
  themeState.textContent = dark ? 'ON' : 'OFF';

  fetch('../settings/update-settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'dark_mode=' + (dark ? 1 : 0)
  });
}

document.querySelector('.theme-control')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    toggleDark();
  }
});
</script>


</body>
</html>
