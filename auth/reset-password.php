<?php
session_start();
require_once __DIR__ . '/../config/password-reset.php';   // loads db.php + mail.php too

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot-password.php');
    exit;
}

$userId = (int) $_SESSION['reset_user_id'];
$email  = $_SESSION['reset_email'] ?? '';
$error  = '';
$notice = '';
$success = false;
$loginUrl = '../auth/login.php';

// ---------- RESEND CODE (60s cooldown + hourly limits are enforced inside) ----------
if (isset($_GET['resend'])) {
    $result = gg_pr_request_code($pdo, $email);
    if ($result['status'] === 'sent') {
        $notice = 'A new code has been sent to your email.';
    } else {
        $error = $result['message'] ?: 'Could not resend the code. Please try again.';
    }
}

// ---------- SUBMIT: VERIFY CODE + SET NEW PASSWORD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp      = preg_replace('/\D/', '', implode('', (array) ($_POST['otp'] ?? [])));
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($enteredOtp) !== 6) {
        $error = 'Please enter the complete 6-digit code.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'The new password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The two passwords you entered do not match.';
    } else {
        $res = gg_pr_verify_and_reset($pdo, $userId, $enteredOtp, $newPassword);
        if ($res['ok']) {
            // Send them back to whichever login page fits their role.
            $loginUrl = in_array($res['role'], ['office_head', 'admin'], true) ? '../login.php' : '../auth/login.php';
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['dev_otp_preview'], $_SESSION['pr_last_request']);
            $success = true;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Reset Password</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .title{text-align:center;margin:0 auto 8px;color:#f2f4fa;font-weight:800;}
  .subtitle{text-align:center;margin:0 auto 22px;color:#8a93b8;font-size:14px;}
</style>
</head>
<body>

<div class="phone">
  <div class="app-header">
    <a href="forgot-password.php" class="back-arrow">&larr;</a>
    <span>GeoGene</span>
  </div>

  <?php if (!$success): ?>
    <div class="icon-circle green">🔑</div>
    <h1 class="title">Reset Your Password</h1>
    <p class="subtitle">Enter the 6-digit code sent to<br><strong><?= htmlspecialchars($email) ?></strong> and choose a new password.</p>

    <?php if ($error): ?>
      <p class="error-text" style="text-align:center;margin-bottom:12px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($notice): ?>
      <p class="success-text" style="text-align:center;margin-bottom:12px;"><?= htmlspecialchars($notice) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['dev_otp_preview'])): ?>
      <div style="background:#fff8e1;border:1px solid #f0c95a;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12.5px;color:#7a5c00;text-align:center;">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['dev_otp_preview']) ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" action="reset-password.php" id="resetForm">
      <div class="otp-wrap">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" name="otp[]" maxlength="1" inputmode="numeric" class="otp-box" autocomplete="off">
        <?php endfor; ?>
      </div>

      <label>New Password</label>
      <input type="password" name="new_password" class="form-input" placeholder="At least 8 characters" required minlength="8">

      <label style="margin-top:12px;display:block;">Confirm New Password</label>
      <input type="password" name="confirm_password" class="form-input" placeholder="Re-type your new password" required minlength="8">

      <button type="submit" class="btn-primary" id="rpBtn" style="margin-top:18px;">Reset Password</button>
    </form>

    <p class="footer-text">Didn't receive the code? <a href="reset-password.php?resend=1">Resend Code</a></p>
  <?php else: ?>
    <div class="icon-circle green">✔</div>
    <h1 class="title">Password Reset!</h1>
    <p class="subtitle">Your password has been changed. You can now log in with your new password.</p>
    <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn-primary" style="text-decoration:none;display:block;text-align:center;">Back to Login</a>
  <?php endif; ?>
</div>

<script>
const rpForm = document.getElementById('resetForm');
if (rpForm) {
  rpForm.addEventListener('submit', () => {
    const b = document.getElementById('rpBtn');
    b.disabled = true;
    b.textContent = 'Resetting...';
  });
}
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((box, i) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    if (box.value && i < boxes.length - 1) {
      boxes[i + 1].focus();
    }
  });
  box.addEventListener('paste', (e) => {
    const t = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    if (t.length > 1) {
      e.preventDefault();
      t.split('').forEach((d, k) => { if (boxes[k]) boxes[k].value = d; });
      boxes[Math.min(t.length, boxes.length) - 1].focus();
    }
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) {
      boxes[i - 1].focus();
    }
  });
});
</script>
</body>
</html>
