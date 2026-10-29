<?php
// Expects: $pageTitle, $pageSub (optional), $activeAdminNav to be set before including this file.
// Expects $pdo (from config/db.php) and session staff_* vars to already be available.
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSub = $pageSub ?? '';
$activeAdminNav = $activeAdminNav ?? '';
$adminName = $_SESSION['staff_name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1));
$adminPhoto = null;
$adminEmail = '';
$adminDarkMode = 0;
if (isset($pdo, $_SESSION['staff_id'])) {
    $stmt = $pdo->prepare("SELECT profile_photo, email FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['staff_id']]);
    $adminRow = $stmt->fetch();
    $adminPhoto = $adminRow['profile_photo'] ?? null;
    $adminEmail = $adminRow['email'] ?? '';

    $stmt = $pdo->prepare("SELECT dark_mode FROM user_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['staff_id']]);
    $adminDarkMode = (int) ($stmt->fetchColumn() ?: 0);
}

// ---------- Notifications (bell dropdown) - Admin-relevant items ----------
// NOTE: this file is require_once'd from many pages that already declare
// their OWN $pendingUsers / $hasActivityLog / $recentActivity variables
// (e.g. dashboard.php, user-monitoring.php). Because require_once shares
// the caller's variable scope, these MUST use distinct, namespaced names
// below, or they silently clobber the calling page's own variables.
$adminNotifications = [];
if (isset($pdo)) {
    // Users awaiting identity verification (Admin monitors this too)
    $__notifPendingUsers = $pdo->query("
        SELECT user_id, full_name, created_at
        FROM users
        WHERE identity_verified = 0 AND email_verified = 1 AND role = 'user'
        ORDER BY created_at DESC LIMIT 5
    ")->fetchAll();
    foreach ($__notifPendingUsers as $u) {
        $adminNotifications[] = [
            'type'  => 'pending',
            'icon'  => '<img src="../assets/images/notif-icons/pending.png" alt="" style="width:22px;height:22px;display:block;">',
            'text'  => htmlspecialchars($u['full_name']) . ' is awaiting identity verification.',
            'time'  => $u['created_at'],
            'link'  => 'user-monitoring.php',
        ];
    }

    // Recent staff activity (Admin monitors staff, not just users)
    $__notifHasActivityLog = (bool) $pdo->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();
    if ($__notifHasActivityLog) {
        $__notifRecentActivity = $pdo->query("
            SELECT a.activity, a.created_at, u.full_name
            FROM activity_logs a
            JOIN users u ON u.user_id = a.user_id
            WHERE a.role IN ('office_head','admin')
            ORDER BY a.created_at DESC LIMIT 5
        ")->fetchAll();
        foreach ($__notifRecentActivity as $a) {
            $adminNotifications[] = [
                'type'  => 'staff_activity',
                'icon'  => '<span style="width:26px;height:26px;border-radius:50%;background:#e4f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12.75 11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z" stroke="#1b6e3c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>',
                'text'  => '<strong>' . htmlspecialchars($a['full_name']) . '</strong> ' . htmlspecialchars(strtolower($a['activity'])),
                'time'  => $a['created_at'],
                'link'  => 'reports-staff-logs.php',
            ];
        }
    }

    usort($adminNotifications, fn($a, $b) => strtotime($b['time']) <=> strtotime($a['time']));
    $adminNotifications = array_slice($adminNotifications, 0, 8);
    foreach ($adminNotifications as &$n) {
        $n['id'] = substr(md5($n['type'] . '|' . $n['text'] . '|' . $n['time']), 0, 12);
    }
    unset($n);
}
$adminNotifCount = count($adminNotifications);

function adminTimeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene Admin - <?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="../staff/assets/css/staff.css?v=<?= (int) @filemtime(__DIR__ . '/../../staff/assets/css/staff.css') ?>">
<link rel="stylesheet" href="../staff/assets/css/global-search.css?v=<?= (int) @filemtime(__DIR__ . '/../../staff/assets/css/global-search.css') ?>">
<?php require_once __DIR__ . '/../../staff/includes/global-search-config.php'; echo gg_search_script('admin'); ?>
<script src="../staff/assets/js/global-search.js?v=<?= (int) @filemtime(__DIR__ . '/../../staff/assets/js/global-search.js') ?>" defer></script>
<style>
  @media print {
    .sidebar, .topbar { display: none !important; }
    .main-area { margin-left: 0 !important; }
  }
  .sidebar-item .si-icon svg {
    width: 20px;
    height: 20px;
}

.sidebar-item .si-icon svg path {
    fill: #ffffff !important;
}
.si-icon svg,
.si-icon svg * {
    fill: #fff !important;
}
</style>
</head>
<body>
<script>
  (function () {
    var saved = localStorage.getItem('geogene-admin-theme');
    var dbPrefersDark = <?= $adminDarkMode ? 'true' : 'false' ?>;
    var isDark = saved ? (saved === 'dark') : dbPrefersDark;
    if (isDark) document.body.classList.add('dark-mode');
    if (!saved) localStorage.setItem('geogene-admin-theme', isDark ? 'dark' : 'light');
  })();
</script>

<div class="staff-shell">
  <aside class="sidebar" id="staffSidebar">
    <script>if(localStorage.getItem('adminSidebarCollapsed')==='1')document.getElementById('staffSidebar').classList.add('collapsed');</script>
    <div class="sidebar-logo">
      <img src="../assets/images/geogene-logo.png"
     alt="GeoGene Logo"
     class="sidebar-logo-img"
     style="width:80px;height:80px;object-fit:contain;display:block;transform:translateX(-15px);">
      <div class="logo-text">GeoGene<br><span style="font-weight:400;font-size:10px;opacity:.7;">Admin Portal</span></div>
      <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar" aria-label="Collapse sidebar">
        <svg class="ico-collapse" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m10 15-3-3 3-3"/></svg>
        <svg class="ico-expand" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg>
      </button>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-label">Main</div>
      <a href="dashboard.php" data-label="Dashboard" class="sidebar-item <?= $activeAdminNav === 'dashboard' ? 'active' : '' ?>"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
  <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
</svg>
</span> Dashboard</a>

      <div class="sidebar-section-label">Personnel</div>
      <a href="staff-management.php" data-label="Staff Management" class="sidebar-item <?= $activeAdminNav === 'staff' ? 'active' : '' ?>"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
  <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
</svg>
</span> Staff Management</a>
      <a href="user-monitoring.php" data-label="User Monitoring" class="sidebar-item <?= $activeAdminNav === 'users' ? 'active' : '' ?>"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
</svg>
</span> User Monitoring</a>

      <div class="sidebar-section-label">Records &amp; Genealogy</div>
      <a href="civil-registry.php" data-label="Civil Registry Records" class="sidebar-item <?= $activeAdminNav === 'civil-registry' ? 'active' : '' ?>"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M4.125 3C3.089 3 2.25 3.84 2.25 4.875V18a3 3 0 0 0 3 3h15a3 3 0 0 1-3-3V4.875C17.25 3.839 16.41 3 15.375 3H4.125ZM12 9.75a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5H12Zm-.75-2.25a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H12a.75.75 0 0 1-.75-.75ZM6 12.75a.75.75 0 0 0 0 1.5h7.5a.75.75 0 0 0 0-1.5H6Zm-.75 3.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5H6a.75.75 0 0 1-.75-.75ZM6 6.75a.75.75 0 0 0-.75.75v3c0 .414.336.75.75.75h3a.75.75 0 0 0 .75-.75v-3A.75.75 0 0 0 9 6.75H6Z" clip-rule="evenodd" />
  <path d="M18.75 6.75h1.875c.621 0 1.125.504 1.125 1.125V18a1.5 1.5 0 0 1-3 0V6.75Z" />
</svg>
</span> Civil Registry Records</a>
      <a href="family-tree.php" data-label="Family Tree" class="sidebar-item <?= $activeAdminNav === 'family-tree' ? 'active' : '' ?>"><span class="si-icon">
        <svg viewBox="-102.4 -102.4 1228.80 1228.80" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="currentColor" transform="rotate(90)" width="30" height="30">
            <path d="M853.333333 192V149.333333h-192v106.666667H320v234.666667H170.666667v42.666666h149.333333v234.666667h341.333333v106.666667h192v-42.666667h-149.333333v-170.666667h149.333333v-42.666666h-192v106.666667H362.666667V298.666667h298.666666v106.666666h192v-42.666666h-149.333333V192z" fill="currentColor"></path>
            <path d="M85.333333 426.666667h170.666667v170.666666H85.333333z" fill="currentColor"></path>
            <path d="M768 298.666667h170.666667v170.666667h-170.666667z M768 85.333333h170.666667v170.666667h-170.666667z M426.666667 192h170.666667v170.666667h-170.666667z M426.666667 661.333333h170.666667v170.666667h-170.666667z M768 768h170.666667v170.666667h-170.666667z M768 554.666667h170.666667v170.666666h-170.666667z" fill="currentColor"></path>
        </svg>
    </span> Family Tree</a>
      <a href="map.php" data-label="Geographic Map" class="sidebar-item <?= $activeAdminNav === 'map' ? 'active' : '' ?>"><span class="si-icon"><?xml version="1.0" ?><svg enable-background="new 0 0 64 64" height="64px" version="1.1" viewBox="0 0 64 64" width="64px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="_x31_-Call"/><g id="_x32_-24_Hours_Call"/><g id="_x33_-Sending_Call"/><g id="_x34_-Incoming_Call"/><g id="_x35_-Talk_On_Phone"/><g id="_x36_-Block_Call"/><g id="_x37_-Hold_Call"/><g id="_x38_-Mute_Call"/><g id="_x39_-Phone_Book"/><g id="_x31_0-Add_Contact"/><g id="_x31_1-Delete_Contact"/><g id="_x31_2-Mail"/><g id="_x31_3-Message_Read"/><g id="_x31_4-Email"/><g id="_x31_5-Send_Mail"/><g id="_x31_6-Important_Mail"/><g id="_x31_7-Mail_Chain"/><g id="_x31_8-Messaging"/><g id="_x31_9-Sending"/><g id="_x32_0-Chat"/><g id="_x32_1-Mail_Attachment"/><g id="_x32_2-Mobile_Link"/><g id="_x32_3-Smartphone"/><g id="_x32_4-Social_Media"/><g id="_x32_5-Support"/><g id="_x32_6-Links"/><g id="_x32_7-Phone"/><g id="_x32_8-Phone-2"/><g id="_x32_9-Map"><g><path d="M42.1669617,27.8752136c-0.1367188,0.0634766-0.2802734,0.0927734-0.4208984,0.0927734    c-0.3769531,0-0.7382813-0.2138672-0.9082031-0.578125c-0.2324219-0.5009766-0.0146484-1.0957031,0.4853516-1.3291016    c0.4144287-0.1923218,0.8406372-0.3479614,1.2683105-0.4966431l-0.8757935-12.34729l-8.7255249,4.5574341v17.2896729    c0.4571533-0.6592407,0.8743286-1.3790283,1.2724609-2.0891113l0.09375-0.1679688    c0.2705078-0.4814453,0.8789063-0.6523438,1.3623047-0.3818359c0.4814453,0.2705078,0.6523438,0.8798828,0.3818359,1.3613281    l-0.09375,0.1669922c-0.4580078,0.8173828-0.9775391,1.7421875-1.5830078,2.5664063    c-0.1962891,0.2666016-0.4990234,0.4072266-0.8066406,0.4072266c-0.2060547,0-0.4130859-0.0634766-0.5917969-0.1943359    c-0.0146484-0.0108032-0.0213013-0.0273438-0.0351563-0.0387573v13.7617188l11.0264282-4.7995605l-1.2771606-18.0056152    C42.5489807,27.7270203,42.3542175,27.7881165,42.1669617,27.8752136z M39.7304382,29.3595886    c-0.7451172,0.5800781-1.4296875,1.2509766-2.0341797,1.9951172c-0.1982422,0.2431641-0.4863281,0.3691406-0.7773438,0.3691406    c-0.2216797,0-0.4443359-0.0732422-0.6298828-0.2236328c-0.4287109-0.3486328-0.4931641-0.9785156-0.1455078-1.4072266    c0.7001953-0.8613281,1.4941406-1.6386719,2.3583984-2.3115234c0.4375-0.3408203,1.0644531-0.2597656,1.4033203,0.1748047    C40.2441101,28.3927917,40.1659851,29.0207214,39.7304382,29.3595886z"/><path d="M60.914032,49.3722839l-4.4852905-18.0750732c-0.9718628-0.7611694-3.1273804-2.4161377-3.4199829-2.6065674    c-0.4628906-0.3007813-0.59375-0.9208984-0.2919922-1.3837891c0.3007813-0.4609375,0.9179688-0.5927734,1.3837891-0.2919922    c0.1951294,0.1270142,0.8366699,0.6096802,1.5526123,1.1567993l-2.2303467-8.987854    c-0.0654297-0.2626953-0.234375-0.4873047-0.46875-0.6240234l-9.2354736-5.3598022l0.8400879,11.8435059    c0.5176392-0.0552368,0.9985962,0.2779541,1.0955811,0.7965698c0.1025391,0.5429688-0.2548828,1.0654297-0.7978516,1.1669922    c-0.0523682,0.0098877-0.1033325,0.0290527-0.1556396,0.0396729l1.3199463,18.6085205l13.619873,5.0812988    c0.1132813,0.0429688,0.2324219,0.0634766,0.3496094,0.0634766c0.0068359,0,0.0117188,0,0.0195313,0c0.5527344,0,1-0.4472656,1-1    C61.0097351,49.646698,60.9755554,49.5021667,60.914032,49.3722839z M51.6982117,26.802948    c-0.1425781,0.4150391-0.5302734,0.6748047-0.9453125,0.6748047c-0.1083984,0-0.2177734-0.0175781-0.3251953-0.0546875    c-0.8994141-0.3095703-1.828125-0.5019531-2.7607422-0.5722656c-0.5507813-0.0410156-0.9638672-0.5214844-0.921875-1.0722656    c0.0410156-0.5507813,0.5273438-0.9658203,1.0722656-0.921875c1.1035156,0.0830078,2.2011719,0.3105469,3.2607422,0.6757813    C51.6005554,25.7121277,51.8778992,26.2814636,51.6982117,26.802948z"/><path d="M21.2291546,27.8159485c0.1592407,0.0896606,0.303833,0.2077637,0.3967896,0.3805542    c0.3544922,0.6591797,0.6484375,1.34375,0.9335938,2.0048828c0.1240234,0.2880859,0.2480469,0.5771484,0.3759766,0.8623047    c0.2275391,0.5039063,0.0039063,1.0957031-0.5,1.3222656c-0.1337891,0.0605469-0.2734375,0.0888672-0.4101563,0.0888672    c-0.3818359,0-0.7460938-0.21875-0.9121094-0.5888672c-0.0507813-0.1123657-0.098999-0.2262573-0.1486816-0.3392944    l-1.0007935,14.109375l11.0264282,4.7995605V39.0934143c-0.0373535,0.0092773-0.0728149,0.0274048-0.1103516,0.0357056    c-0.3251953,0.0732422-0.6708984,0.1113281-1.0224609,0.1113281c-0.6064453,0-1.2353516-0.1132813-1.8691406-0.3369141    c-0.5205078-0.1845703-0.7939453-0.7558594-0.6103516-1.2763672c0.1845703-0.5214844,0.7529297-0.7910156,1.2763672-0.6103516    c0.6435547,0.2285156,1.2568359,0.2802734,1.7910156,0.1601563c0.171875-0.0380859,0.3330078-0.0908203,0.4882813-0.1572266    c0.0184326-0.0079346,0.0379639-0.005188,0.0566406-0.0119629V17.7742615l-8.7255249-4.5574341L21.2291546,27.8159485z     M24.380827,33.5148621c0.6015625,0.7822266,1.2822266,1.5058594,1.9677734,2.0908203    c0.4199219,0.359375,0.4697266,0.9902344,0.1113281,1.4101563c-0.1982422,0.2314453-0.4785156,0.3505859-0.7607422,0.3505859    c-0.2304688,0-0.4609375-0.0791016-0.6494141-0.2392578c-0.7890625-0.6748047-1.5693359-1.5019531-2.2548828-2.3935547    c-0.3359375-0.4375-0.2548828-1.0654297,0.1835938-1.4023438C23.4159832,32.9953308,24.0429363,33.0754089,24.380827,33.5148621z"/><path d="M11.0263357,18.5597839c-0.234375,0.1367188-0.4033203,0.3613281-0.46875,0.6240234L7.3009324,32.3065491    c0.4449463-0.5317383,0.8218999-0.9863892,0.9089971-1.1061401c0.3242188-0.4443359,0.9482422-0.5449219,1.3974609-0.2177734    c0.4453125,0.3251953,0.5439453,0.9511719,0.2177734,1.3974609c-0.1689453,0.2314453-0.8076172,0.9941406-1.8398442,2.2226563    c-0.6743164,0.8021851-1.5742798,1.8728027-1.7877197,2.1499023L3.0194993,49.5588074    c-0.0888672,0.3603516,0.0283203,0.7402344,0.3046875,0.9873047c0.1865234,0.1660156,0.4248047,0.2539063,0.6660156,0.2539063    c0.1171875,0,0.2363281-0.0205078,0.3496094-0.0634766l13.619873-5.0812988l1.3095703-18.4627075    c-0.1432495,0.0742798-0.3013306,0.1143188-0.4616699,0.1143188c-0.1982422,0-0.3974609-0.0585938-0.5722656-0.1806641    c-0.6376953-0.4453125-1.3212891-0.5566406-2.1171875-0.3535156c-0.5322256,0.1396484-1.0800772-0.1855469-1.2167959-0.7207031    s0.1855469-1.0800781,0.7207031-1.2167969c1.3564444-0.3486328,2.6484365-0.1240234,3.7597647,0.6523438    c0.0032349,0.0022583,0.0046387,0.0059204,0.0078125,0.0082397l0.8721924-12.2957764L11.0263357,18.5597839z     M13.7929373,28.0392761c-0.6689453,0.5263672-1.3740234,1.2041016-2.0986328,2.0136719    c-0.1972656,0.2207031-0.4707031,0.3330078-0.7451172,0.3330078c-0.2373047,0-0.4755859-0.0839844-0.6669922-0.2548828    c-0.4111328-0.3681641-0.4462891-1-0.078125-1.4121094c0.8007813-0.8955078,1.5917969-1.6523438,2.3505859-2.25    c0.4355469-0.3427734,1.0634766-0.2666016,1.4042969,0.1660156C14.3007498,27.0695496,14.226531,27.6984558,13.7929373,28.0392761    z"/></g></g><g id="_x33_0-Location"/><g id="_x33_1-Write"/><g id="_x33_2-World"/><g id="_x33_3-Home"/><g id="_x33_4-Calendar"/><g id="_x33_5-Website"/><g id="_x33_6-Info"/><g id="_x33_7-Bull_Horn"/><g id="_x33_8-Profile"/><g id="_x33_9-Quote__x26__Answer"/><g id="_x34_0-Notification"/><g id="_x34_1-Share"/><g id="_x34_2-Time"/></svg>
</span> Geographic Map</a>

      <div class="sidebar-section-label">Reports</div>
      <a href="reports.php" data-label="Reports" class="sidebar-item <?= $activeAdminNav === 'reports' ? 'active' : '' ?>"><span class="si-icon"><svg id="Layer_1" style="enable-background:new 0 0 64 64;" version="1.1" viewBox="0 0 64 64" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M43.98502,0h-36.88v64h49.78998V12.91998L43.98502,0z M54.89499,62H9.10501V2h33.45996v12.33002h12.33002V62z"/><polygon points="47.50497,37.14001 41.94498,37.14001 41.94498,56.27002 39.06497,56.27002 39.06497,40.14001 33.50497,40.14001    33.50497,56.27002 30.62497,56.27002 30.62497,43.14001 25.06497,43.14001 25.06497,56.27002 22.18497,56.27002 22.18497,46.14001    16.62497,46.14001 16.62497,56.27002 13.51498,56.27002 13.51498,58.27002 50.49496,58.27002 50.49496,56.27002 47.50497,56.27002     "/><rect height="2" width="18.3916" x="16.64163" y="9.78815"/><rect height="2" width="34.41699" x="13.65823" y="24.51764"/><rect height="2" width="34.41699" x="13.65823" y="29.62213"/><rect height="2" width="34.41699" x="13.65823" y="19.41315"/></g></svg>
</span> Reports</a>
    </nav>

    <div class="sidebar-footer">
      <a href="profile.php" data-label="My Profile" class="sidebar-item <?= $activeAdminNav === 'profile' ? 'active' : '' ?>" style="padding-left:0;"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
</svg>
</span> My Profile</a>

      <div class="theme-toggle-row">
        <span class="theme-toggle-label"><span class="si-icon" id="themeToggleIcon" role="button" tabindex="0" title="Toggle light/dark mode" style="cursor:pointer;">☀️</span><span id="themeToggleText">Light Mode</span></span>
        <label class="theme-switch">
          <input type="checkbox" id="themeToggleInput">
          <span class="theme-switch-track"><span class="theme-switch-thumb"></span></span>
        </label>
      </div>

      <a href="logout.php" data-label="Logout" class="sidebar-item" style="padding-left:0;color:#ff8b96;"><span class="si-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ffffff">
<path d="M15.24 22.2705H15.11C10.67 22.2705 8.53002 20.5205 8.16002 16.6005C8.12002 16.1905 8.42002 15.8205 8.84002 15.7805C9.24002 15.7405 9.62002 16.0505 9.66002 16.4605C9.95002 19.6005 11.43 20.7705 15.12 20.7705H15.25C19.32 20.7705 20.76 19.3305 20.76 15.2605V8.74047C20.76 4.67047 19.32 3.23047 15.25 3.23047H15.12C11.41 3.23047 9.93002 4.42047 9.66002 7.62047C9.61002 8.03047 9.26002 8.34047 8.84002 8.30047C8.42002 8.27047 8.12001 7.90047 8.15001 7.49047C8.49001 3.51047 10.64 1.73047 15.11 1.73047H15.24C20.15 1.73047 22.25 3.83047 22.25 8.74047V15.2605C22.25 20.1705 20.15 22.2705 15.24 22.2705Z" fill="white" style="fill: var(--fillg);"/>
<path d="M15.0001 12.75H3.62012C3.21012 12.75 2.87012 12.41 2.87012 12C2.87012 11.59 3.21012 11.25 3.62012 11.25H15.0001C15.4101 11.25 15.7501 11.59 15.7501 12C15.7501 12.41 15.4101 12.75 15.0001 12.75Z" fill="white" style="fill: var(--fillg);"/>
<path d="M5.84994 16.0998C5.65994 16.0998 5.46994 16.0298 5.31994 15.8798L1.96994 12.5298C1.67994 12.2398 1.67994 11.7598 1.96994 11.4698L5.31994 8.11984C5.60994 7.82984 6.08994 7.82984 6.37994 8.11984C6.66994 8.40984 6.66994 8.88984 6.37994 9.17984L3.55994 11.9998L6.37994 14.8198C6.66994 15.1098 6.66994 15.5898 6.37994 15.8798C6.23994 16.0298 6.03994 16.0998 5.84994 16.0998Z" fill="white" style="fill: var(--fillg);"/>
</svg></span> Logout</a>
    </div>
  </aside>

  <div class="main-area">
    <div class="topbar">
      <button class="icon-btn" style="display:none;" id="sidebarToggle"></button>
      <div class="topbar-search">
        <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
        <input type="text" id="ggSearchInput" placeholder="Search..." aria-label="Search the system">
      </div>
      <div class="topbar-right">
        <div class="topbar-badge">Admin</div>

        <div class="notif-wrap" id="notifWrap" style="position:relative;">
          <button type="button" class="bell-btn" id="notifBtn" title="Notifications" aria-label="Notifications">
            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 8.4C18 6.70261 17.3679 5.07475 16.2426 3.87452C15.1174 2.67428 13.5913 2 12 2C10.4087 2 8.88258 2.67428 7.75736 3.87452C6.63214 5.07475 6 6.70261 6 8.4C6 15.8667 3 18 3 18H21C21 18 18 15.8667 18 8.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
              <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <span class="bell-dot" id="bellDot" style="display:<?= $adminNotifCount > 0 ? 'flex' : 'none' ?>;"><?= $adminNotifCount > 9 ? '9+' : $adminNotifCount ?></span>
          </button>

          <div class="notif-dropdown" id="notifDropdown"
            style="display:none;position:absolute;top:calc(100% + 10px);right:0;width:320px;max-width:88vw;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,0.16);z-index:60;overflow:hidden;">

            <div style="padding:12px 14px 8px;display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:14px;font-weight:800;color:#1a1a1a;">Notifications</span>
              <div style="position:relative;">
                <button type="button" id="notifMenuBtn" aria-label="Notification options" style="background:none;border:none;cursor:pointer;font-size:18px;color:#6b6b6b;padding:2px 6px;line-height:1;border-radius:6px;">⋯</button>
                <div id="notifMenuDropdown" style="display:none;position:absolute;top:calc(100% + 4px);right:0;width:170px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 10px 26px rgba(0,0,0,0.18);z-index:70;padding:6px;">
                  <button type="button" id="markAllReadBtn" style="display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;text-align:left;cursor:pointer;font-size:12.5px;font-weight:600;color:#1a1a1a;padding:8px 9px;border-radius:8px;">✔ Mark all as read</button>
                </div>
              </div>
            </div>

            <div style="display:flex;gap:18px;padding:0 14px;border-bottom:1px solid #e5e7eb;">
              <button type="button" class="notif-tab active" data-tab="all" style="background:none;border:none;padding:8px 0;font-size:12.5px;font-weight:700;color:#1a1a1a;cursor:pointer;border-bottom:2px solid #1b6e3c;">All</button>
              <button type="button" class="notif-tab" data-tab="unread" style="background:none;border:none;padding:8px 0;font-size:12.5px;font-weight:700;color:#8a8f8a;cursor:pointer;border-bottom:2px solid transparent;">Unread</button>
            </div>

            <div style="max-height:340px;overflow-y:auto;" id="notifList">
              <?php if (!$adminNotifications): ?>
                <div style="padding:26px 14px;text-align:center;font-size:12.5px;color:#8a8f8a;">No new notifications at this time.</div>
              <?php else: foreach ($adminNotifications as $n): ?>
                <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-item" data-id="<?= $n['id'] ?>" style="display:flex;gap:10px;align-items:flex-start;padding:11px 14px;text-decoration:none;border-bottom:1px solid #f0f2f0;">
                  <span style="flex-shrink:0;"><?= $n['icon'] ?></span>
                  <span style="flex:1;">
                    <span class="notif-item-text" style="display:block;font-size:12.5px;font-weight:600;color:#1a1a1a;line-height:1.4;"><?= $n['text'] ?></span>
                    <span style="display:block;font-size:11px;color:#8a8f8a;margin-top:2px;"><?= adminTimeAgo($n['time']) ?></span>
                  </span>
                  <span class="notif-unread-dot" style="width:7px;height:7px;border-radius:50%;background:#1b6e3c;flex-shrink:0;margin-top:5px;"></span>
                </a>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>

        <div class="avatar-wrap" id="avatarWrap" style="position:relative;">
          <button type="button" class="topbar-avatar" id="avatarBtn" title="<?= htmlspecialchars($adminName) ?>"
            style="display:flex;align-items:center;gap:9px;background:none;border:none;padding:2px;cursor:pointer;">
            <span style="width:34px;height:34px;border-radius:50%;background:var(--primary-light,#e4f5e9);color:var(--primary,#1b6e3c);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
              <?php if ($adminPhoto): ?>
                <img src="../<?= htmlspecialchars($adminPhoto) ?>" alt="Profile" id="avatarImg" width="34" height="34" style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php else: ?>
                <span id="avatarInitial"><?= htmlspecialchars($adminInitial) ?></span>
              <?php endif; ?>
            </span>
            <span style="text-align:left;line-height:1.25;">
              <span style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:700;color:var(--text-main,#1a1a1a);white-space:nowrap;">
                <?= htmlspecialchars($adminName) ?>
                <svg id="avatarChevron" width="10" height="10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;transition:transform .15s ease;"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <span style="display:block;font-size:11px;color:var(--text-secondary,#8a8f8a);white-space:nowrap;"><?= htmlspecialchars($adminEmail) ?></span>
            </span>
          </button>

          <div class="avatar-dropdown" id="avatarDropdown"
            style="display:none;position:absolute;top:calc(100% + 10px);right:0;width:220px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,0.16);z-index:60;overflow:hidden;padding:6px;">
            <div class="avatar-dropdown-header" style="padding:10px 10px 8px;border-bottom:1px solid #e5e7eb;margin-bottom:6px;">
              <div class="avatar-dropdown-name" style="font-size:13px;font-weight:800;color:#1a1a1a;"><?= htmlspecialchars($adminName) ?></div>
              <div class="avatar-dropdown-role" style="font-size:11px;color:#1b6e3c;margin-top:2px;">🛡 Office Head / Admin</div>
            </div>
            <button type="button" class="avatar-dropdown-item" id="viewPhotoBtn" style="display:flex;align-items:center;gap:9px;width:100%;background:none;border:none;text-align:left;cursor:pointer;font-size:12.5px;font-weight:600;color:#1a1a1a;padding:9px 10px;border-radius:8px;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:17px;height:17px;flex-shrink:0;">
                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
              </svg>
              View Profile
            </button>
            <button type="button" class="avatar-dropdown-item" id="changePhotoBtn" style="display:flex;align-items:center;gap:9px;width:100%;background:none;border:none;text-align:left;cursor:pointer;font-size:12.5px;font-weight:600;color:#1a1a1a;padding:9px 10px;border-radius:8px;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:17px;height:17px;flex-shrink:0;">
                <path d="M12 9a3.75 3.75 0 1 0 0 7.5A3.75 3.75 0 0 0 12 9Z" />
                <path fill-rule="evenodd" d="M9.344 3.071a49.52 49.52 0 0 1 5.312 0c.967.052 1.83.585 2.332 1.39l.821 1.317c.24.383.645.643 1.11.71.386.054.77.113 1.152.177 1.432.239 2.429 1.493 2.429 2.909V18a3 3 0 0 1-3 3h-15a3 3 0 0 1-3-3V9.574c0-1.416.997-2.67 2.429-2.909.382-.064.766-.123 1.151-.178a1.56 1.56 0 0 0 1.11-.71l.822-1.315a2.942 2.942 0 0 1 2.332-1.39ZM6.75 12.75a5.25 5.25 0 1 1 10.5 0 5.25 5.25 0 0 1-10.5 0Zm12-1.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
              </svg>
              Change Profile Photo
            </button>
            <a href="profile.php" class="avatar-dropdown-item" style="display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;text-align:left;cursor:pointer;font-size:12.5px;font-weight:600;color:#1a1a1a;padding:9px 10px;border-radius:8px;">👤 My Profile</a>
          </div>
        </div>
        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" hidden>
      </div>
    </div>

    <!-- Profile picture preview modal -->
    <div class="photo-modal-overlay" id="photoModalOverlay"
      style="display:none;position:fixed;inset:0;background:rgba(15,20,15,0.55);align-items:center;justify-content:center;z-index:100;">
      <div class="photo-modal-box" style="background:#fff;border-radius:20px;padding:28px 26px 24px;width:380px;max-width:92vw;text-align:center;position:relative;box-shadow:0 24px 60px rgba(0,0,0,0.35);">
        <button type="button" class="photo-modal-close" id="photoModalClose" style="position:absolute;top:14px;right:14px;width:28px;height:28px;border-radius:50%;border:none;background:#f4f6f5;color:#6b6b6b;cursor:pointer;font-size:13px;">✕</button>
        <img src="" alt="Profile" id="photoModalImg" class="photo-modal-img" width="280" height="280" style="width:280px;height:280px;max-width:100%;border-radius:20px;object-fit:cover;border:4px solid #e4f5e9;margin:8px auto 16px;display:block;">
        <div class="photo-modal-name" style="font-size:12.5px;font-weight:700;color:#1a1a1a;"><?= htmlspecialchars($adminName) ?></div>
        <button type="button" class="btn btn-primary" id="photoModalChangeBtn" style="margin-top:10px;font-size:12px;padding:8px 16px;">Change Photo</button>
      </div>
    </div>

    <div class="content-area">
      <?php if (empty($suppressPageHeading)): ?>
      <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
      <?php if ($pageSub): ?><p class="page-sub"><?= htmlspecialchars($pageSub) ?></p><?php endif; ?>
      <?php endif; ?>
