<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logo.php';

$stmt = $pdo->prepare("SELECT full_name, profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$activeNav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Home</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
  .app-header{justify-content:space-between;}
  .greet-title-sub{font-size:12.5px;color:var(--text-secondary);line-height:1.5;margin:-14px 0 22px;}
  .greet-wrap{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
  .greet-avatar{
    width:46px;height:46px;border-radius:50%;
    background:var(--primary-light);color:var(--primary);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:16px;flex-shrink:0;overflow:hidden;
  }
  .greet-avatar img{width:100%;height:100%;object-fit:cover;}
  .greet-sub{font-size:12px;color:var(--text-muted);}
  .greet-name{font-size:16px;font-weight:700;color:var(--text-main);}

  .quick-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-bottom:20px;
  }
  .quick-card{
    border-radius:16px;
    padding:16px;
    color:#fff;
    text-decoration:none;
    display:flex;
    flex-direction:column;
    gap:24px;
    min-height:100px;
    position:relative;
    overflow:hidden;
  }
  .quick-card .qc-icon{font-size:22px;display:inline-flex;}
  .quick-card .qc-icon svg{width:24px;height:24px;display:block;}
  .quick-card .qc-label{font-weight:700;font-size:14.5px;}
  .quick-card .qc-desc{font-size:11px;opacity:.9;}
  .qc-tree{background:linear-gradient(135deg,#5cffa3,#39FF88);color:#0B132B;}
  .qc-tree .qc-desc{color:rgba(11,19,43,0.7);}
  .qc-map{background:linear-gradient(135deg,#5a9bd8,#3a6ea8);}
  .qc-profile{background:linear-gradient(135deg,#e0a94e,#c48325);}
  .qc-report{background:linear-gradient(135deg,#d16b6b,#a94040);}

  .section-title{font-size:15px;font-weight:700;margin:22px 0 12px;color:var(--text-main);}
  .content-scroll{padding-bottom:80px;}
</style>
</head>
<body>

<div class="phone">
  <div class="content-scroll">
    <div class="app-header"><span style="display:flex;align-items:center;gap:8px;"><img src="../assets/images/geogene-logo.png" alt="GeoGene" width="24" height="24" style="object-fit:contain;"> GeoGene</span></div>

    <div class="greet-wrap">
      <div class="greet-avatar">
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo">
        <?php else: ?>
          <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="greet-sub">Welcome,</div>
        <div class="greet-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?>!</div>
      </div>
    </div>
    <p class="greet-title-sub">Explore your family lineage and discover your ancestral heritage.</p>

    <div class="section-title">Quick Access</div>
    <div class="quick-grid">
      <a href="../family-tree/tree.php" class="quick-card qc-tree">
        <span class="qc-icon"><svg
      width="24"
      height="24"
      style="position: relative; top: -5px;"
      viewBox="-133.12 -133.12 1290.24 1290.24"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      stroke="none"
      transform="rotate(90)"
    >
      <path
        d="M768 298.666667h170.666667v170.666667h-170.666667z
           M768 85.333333h170.666667v170.666667h-170.666667z
           M426.666667 192h170.666666v170.666667h-170.666667z
           M426.666667 661.333333h170.666667v170.666667h-170.666667z
           M768 768h170.666667v170.666667h-170.666667z
           M768 554.666667h170.666667v170.666667h-170.666667z"
        fill="<?= $activeNav === 'tree' ? '#081ab7' : '#ffffff' ?>"
      />
    </svg></span>
        <div><div class="qc-label">Family Tree</div><div class="qc-desc">View your lineage</div></div>
      </a>
      <a href="../map-view/map.php" class="quick-card qc-map">
        <span class="qc-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
  <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.362 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.235 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
</svg>
</span>
        <div><div class="qc-label">Map View</div><div class="qc-desc">See geographic spread</div></div>
      </a>
      <a href="../profile/profile.php" class="quick-card qc-profile">
        <span class="qc-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
  <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
</svg>
</span>
        <div><div class="qc-label">My Profile</div><div class="qc-desc">Manage your account</div></div>
      </a>


  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

</body>
</html>