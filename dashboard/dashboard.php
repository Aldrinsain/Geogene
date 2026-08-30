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