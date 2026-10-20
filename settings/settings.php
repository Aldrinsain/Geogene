<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT full_name, identity_verified, profile_photo FROM users WHERE user_id = ?");
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
<title>GeoGene - Settings</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .content-scroll{padding-bottom:80px;}
  .settings-avatar-wrap{text-align:center;margin:10px 0 26px;}
  .settings-avatar{
    width:74px;height:74px;border-radius:50%;
    background:linear-gradient(135deg, rgba(57,255,136,0.28), rgba(57,255,136,0.08));color:var(--green);
    display:flex;align-items:center;justify-content:center;
    font-size:26px;font-weight:700;margin:0 auto 10px;
    border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);
    overflow:hidden;
  }
  .settings-avatar img{width:100%;height:100%;object-fit:cover;}
  .settings-name{font-weight:700;font-size:15.5px;}
  .badge-verified{
    display:inline-flex;align-items:center;gap:5px;margin-top:6px;
    background:var(--primary-light);color:var(--green);
    font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;
  }

  .settings-item{
    display:flex;align-items:center;gap:14px;
    padding:14px 4px;border-bottom:1px solid var(--border);
    text-decoration:none;color:inherit;cursor:pointer;
  }
  .settings-item .si-icon{
    width:36px;height:36px;border-radius:10px;
    background:var(--primary-light);color:var(--green);
    display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;
  }
  .settings-item .si-label{font-size:14px;font-weight:600;flex:1;}
  .settings-item .si-chevron{color:var(--text-muted);font-size:16px;}

  .logout-btn{
    width:100%;margin-top:30px;background:var(--danger);color:#fff;
    border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:600;
    display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;
    text-decoration:none;
  }
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="content-scroll">
    <div class="app-header">
      <a href="../profile/profile.php" class="back-arrow">&larr;</a>
      <span>Settings</span>
    </div>

    <div class="settings-avatar-wrap">
      <div class="settings-avatar">
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo">
        <?php else: ?>
          <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="settings-name"><?= htmlspecialchars($user['full_name']) ?></div>
      <?php if ($user['identity_verified']): ?>
        <span class="badge-verified">✔ Verified User</span>
      <?php endif; ?>
    </div>

    <a href="../profile/edit-profile.php" class="settings-item">
      <div class="si-icon">👤</div>
      <div class="si-label">Edit Profile</div>
      <span class="si-chevron">›</span>
    </a>

    <a href="change-password.php" class="settings-item">
      <div class="si-icon">🔒</div>
      <div class="si-label">Change Password</div>
      <span class="si-chevron">›</span>
    </a>

    <a href="privacy-policy.php" class="settings-item">
      <div class="si-icon">📄</div>
      <div class="si-label">Privacy Policy</div>
      <span class="si-chevron">›</span>
    </a>

    <a href="help-support.php" class="settings-item">
      <div class="si-icon">🎧</div>
      <div class="si-label">Help &amp; Support</div>
      <span class="si-chevron">›</span>
    </a>

    <a href="../auth/logout.php" class="logout-btn">⏻ Logout</a>
  </div>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

</body>
</html>
