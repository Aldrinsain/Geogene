<?php
// GeoGene - Civil Registry STAFF forgot-password entry point.
// Logic lives in config/password-reset.php (shared, secure, never throws a 500).
session_start();
require_once __DIR__ . '/config/password-reset.php';   // loads db.php + mail.php too

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = gg_pr_request_code($pdo, (string) ($_POST['email'] ?? ''), 'staff');

    if ($result['status'] === 'sent' || $result['status'] === 'unknown') {
        // "unknown" looks identical to "sent" on purpose, so this form can't be
        // used to check which emails have Civil Registry STAFF access.
        $sent = true;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Staff Forgot Password</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    position:relative;overflow:hidden;padding:20px;
  }
  body::before{
    content:'';position:fixed;inset:0;
    background:url('assets/images/municipal.png') center center/cover no-repeat;
    filter:blur(10px);transform:scale(1.1);z-index:-2;
  }
  body::after{content:'';position:fixed;inset:0;background:rgba(0,0,0,.30);z-index:-1;}

  .brand{text-align:center;margin-bottom:22px;}
  .brand-sub{font-size:13px;color:rgba(255,255,255,0.75);letter-spacing:.2px;}
  .brand-title{font-size:24px;font-weight:800;color:#fff;margin-top:2px;}

  .login-card{
    width:380px;background:rgba(255,255,255,0.14);border-radius:20px;padding:34px 32px 30px;
    box-shadow:0 30px 60px rgba(0,0,0,0.45);backdrop-filter:blur(22px) saturate(160%);
    -webkit-backdrop-filter:blur(22px) saturate(160%);border:1px solid rgba(255,255,255,0.35);
  }

  .back-link{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,0.85);text-decoration:none;font-size:12.5px;font-weight:600;margin-bottom:18px;}

   .icon-wrapper{
    display:flex;
    justify-content:center;
    align-items:center;
    margin-bottom:15px;
}

.reset-icon{
    width:60px;
    height:60px;

  }

  .login-title{text-align:center;font-size:18px;font-weight:800;color:#fff;text-shadow:0 1px 6px rgba(0,0,0,0.25);}
  .login-sub{text-align:center;font-size:12.5px;color:rgba(255,255,255,0.85);margin:6px 0 22px;line-height:1.5;}

  .form-row{margin-bottom:14px;}
  .form-row label{display:block;font-size:12px;font-weight:600;color:rgba(255,255,255,0.9);margin-bottom:6px;}
  .form-row input{
    width:100%;border:1.5px solid rgba(255,255,255,0.4);border-radius:9px;padding:11px 13px;
    font-size:13.5px;outline:none;background:rgba(255,255,255,0.18);color:#fff;
    backdrop-filter:blur(4px);transition:border-color .2s ease, background .2s ease;
  }
  .form-row input::placeholder{color:rgba(230,230,230,0.65);}
  .form-row input:focus{border-color:#a8e6bd;background:rgba(236,236,236,0.67);}

  .login-btn{
    width:100%;background:rgba(1,2,2,0.85);color:#fff;border:1px solid rgba(255,255,255,0.35);
    border-radius:9px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;
    backdrop-filter:blur(6px);transition:background .2s ease;text-decoration:none;display:block;text-align:center;
  }
  .login-btn:hover{background:rgba(23,194,92,0.95);}

  .error-text{color:#ffd6d6;font-size:12.5px;text-align:center;margin-bottom:14px;}
  .footer-text{text-align:center;font-size:12.5px;color:rgba(255,255,255,0.8);margin-top:16px;}
  .footer-text a{color:#a8e6bd;font-weight:700;text-decoration:none;}

  .dev-otp-box{
    background:rgba(255,248,225,0.92);border:1px solid #f0c95a;border-radius:10px;
    padding:10px 14px;margin-bottom:16px;font-size:12px;color:#7a5c00;text-align:center;
  }
</style>
</head>
<body>

<div class="brand">
  <div class="brand-sub">Civil Registry Office &middot; Manolo Fortich</div>
  <div class="brand-title">GeoGene</div>
</div>

<div class="login-card">

  <?php if (!$sent): ?>
    <div class="icon-wrapper">
    <!-- SVG Icon -->
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="mdi-account-key" width="70" height="70" viewBox="0 0 24 24"><path d="M11,10V12H10V14H8V12H5.83C5.42,13.17 4.31,14 3,14A3,3 0 0,1 0,11A3,3 0 0,1 3,8C4.31,8 5.42,8.83 5.83,10H11M3,10A1,1 0 0,0 2,11A1,1 0 0,0 3,12A1,1 0 0,0 4,11A1,1 0 0,0 3,10M16,14C18.67,14 24,15.34 24,18V20H8V18C8,15.34 13.33,14 16,14M16,12A4,4 0 0,1 12,8A4,4 0 0,1 16,4A4,4 0 0,1 20,8A4,4 0 0,1 16,12Z" /></svg>
</div>
    <h1 class="login-title">Forgot Password</h1>
    <p class="login-sub">Enter your staff email address and we'll send a 6-digit reset code to it.</p>

    <?php if ($error): ?><p class="error-text"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST" action="forgot-password.php" id="fpForm">
      <div class="form-row">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@geogene.gov.ph" required maxlength="150" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <button type="submit" class="login-btn" id="fpBtn" style="border:1px solid rgba(255,255,255,0.35);">Send Reset Code</button>
    </form>

    <p class="footer-text">Remembered your password? <a href="login.php">Back to Login</a></p>
  <?php else: ?>
    <div class="icon-circle">✉</div>
    <h1 class="login-title">Check Your Email</h1>
    <p class="login-sub">If that email matches a staff account, we've sent a 6-digit reset code to it.</p>

    <?php if (!empty($_SESSION['staff_reset_otp_preview'])): ?>
      <div class="dev-otp-box">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['staff_reset_otp_preview']) ?></strong>
      </div>
    <?php endif; ?>

    <a href="reset-password.php" class="login-btn">Enter Reset Code</a>
    <p class="footer-text">Didn't get it? <a href="forgot-password.php">Try again</a></p>
  <?php endif; ?>
</div>

<script>
// Loading state: stops double-clicks and shows that something is happening.
var fpForm = document.getElementById('fpForm');
if (fpForm) {
  fpForm.addEventListener('submit', function () {
    var b = document.getElementById('fpBtn');
    b.disabled = true;
    b.textContent = 'Sending code...';
  });
}
</script>
</body>
</html>
