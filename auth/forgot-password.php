<?php
session_start();
require_once __DIR__ . '/../config/password-reset.php';   // loads db.php + mail.php too

$error = '';
$sent = false;
$devCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = gg_pr_request_code($pdo, (string) ($_POST['email'] ?? ''));

    if ($result['status'] === 'sent' || $result['status'] === 'unknown') {
        // "unknown" looks identical to "sent" on purpose, so this form can't be
        // used to check which emails have an account.
        $sent = true;
        $devCode = $result['dev_code'] ?? null;
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
<title>GeoGene - Forgot Password</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .title{text-align:center;margin:0 auto 8px;color:#f2f4fa;font-weight:800;}
  .subtitle{text-align:center;margin:0 auto 22px;color:#8a93b8;font-size:14px;}
  .icon-circle.green svg {
    width: 50px;
    height: 50px;
}
</style>
</head>
<body>

<div class="phone">
  <div class="app-header">
    <a href="login.php" class="back-arrow">&larr;</a>
    <span>GeoGene</span>
  </div>

  <?php if (!$sent): ?>
    <div class="icon-circle green"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><style>.cls-1{fill:#ffb74d;}.cls-2{fill:#ef9014;}</style></defs><title>Key</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M44,0A20,20,0,1,0,64,20,20,20,0,0,0,44,0Zm4,20a4,4,0,1,1,4-4A4,4,0,0,1,48,20Z"/><polygon class="cls-1" points="40 34 24 50 20 50 20 54 16 54 16 58 10 58 10 64 0 64 0 54 30 24 40 34"/><polygon class="cls-2" points="29 45 24 50 20 50 20 54 16 54 16 58 10 58 10 64 0 64 24 40 29 45"/><path class="cls-2" d="M44,4A16,16,0,1,0,60,20,16,16,0,0,0,44,4Zm0,30A14,14,0,1,1,58,20,14,14,0,0,1,44,34Z"/><polygon class="cls-2" points="4 54 24 34 24 36 4 56 4 54"/></g></g></svg></div>
    <h1 class="title">Forgot Password?</h1>
    <p class="subtitle">Enter the email on your account and we'll send you a reset code.</p>

    <?php if ($error): ?>
      <p class="error-text" style="text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="forgot-password.php" id="fpForm">
      <label>Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="Enter your email" required maxlength="150" autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <button type="submit" class="btn-primary" id="fpBtn" style="margin-top:18px;">Send Reset Code</button>
    </form>

    <p class="footer-text">Remembered your password? <a href="login.php">Back to Login</a></p>
  <?php else: ?>
    <div class="icon-circle green">✉</div>
    <h1 class="title">Check Your Email</h1>
    <p class="subtitle">If that email has an account, we've sent a 6-digit reset code to it. The code expires in 10 minutes. Don't forget to check your spam folder.</p>
    <p class="success-text" style="text-align:center;margin-bottom:16px;">&#10004; Reset code sent. Check your inbox.</p>

    <?php if (!empty($devCode)): ?>
      <div style="background:#fff8e1;border:1px solid #f0c95a;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12.5px;color:#7a5c00;text-align:center;">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($devCode) ?></strong>
      </div>
    <?php endif; ?>

    <a href="reset-password.php" class="btn-primary" style="text-decoration:none;display:block;text-align:center;">Enter Reset Code</a>
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
