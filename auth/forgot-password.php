<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)")
                ->execute([$user['user_id'], $otp, $expiresAt]);

            require_once __DIR__ . '/../config/mail.php';
            $emailSent = send_password_reset_email($email, $user['full_name'], $otp);

            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_email'] = $email;

            if (!$emailSent) {
                if (GEOGENE_SHOW_DEV_OTP) $_SESSION['dev_otp_preview'] = $otp;
            } else {
                unset($_SESSION['dev_otp_preview']);
            }
        }

        // Same "sent" state whether or not the email was found, so this
        // form can't be used to check which emails have accounts.
        $sent = true;
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

    <form method="POST" action="forgot-password.php">
      <label>Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <button type="submit" class="btn-primary" style="margin-top:18px;">Send Reset Code</button>
    </form>

    <p class="footer-text">Remembered your password? <a href="login.php">Back to Login</a></p>
  <?php else: ?>
    <div class="icon-circle green">✉</div>
    <h1 class="title">Check Your Email</h1>
    <p class="subtitle">If that email has an account, we've sent a 6-digit reset code to it.</p>

    <?php if (!empty($_SESSION['dev_otp_preview'])): ?>
      <div style="background:#fff8e1;border:1px solid #f0c95a;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12.5px;color:#7a5c00;text-align:center;">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['dev_otp_preview']) ?></strong>
      </div>
    <?php endif; ?>

    <a href="reset-password.php" class="btn-primary" style="text-decoration:none;display:block;text-align:center;">Enter Reset Code</a>
    <p class="footer-text">Didn't get it? <a href="forgot-password.php">Try again</a></p>
  <?php endif; ?>
</div>

</body>
</html>
