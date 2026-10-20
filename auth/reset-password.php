<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot-password.php');
    exit;
}

$userId = $_SESSION['reset_user_id'];
$email  = $_SESSION['reset_email'] ?? '';
$error  = '';
$success = false;
$loginUrl = '../auth/login.php';

// ---------- RESEND CODE ----------
if (isset($_GET['resend'])) {
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)");
    $stmt->execute([$userId, $otp, $expiresAt]);

    require_once __DIR__ . '/../config/mail.php';
    $stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmtName->execute([$userId]);
    $name = $stmtName->fetchColumn();
    $emailSent = send_password_reset_email($email, $name, $otp);

    if (!$emailSent) {
        $_SESSION['dev_otp_preview'] = $otp;
        $error = 'A new code has been sent. (Dev mode: shown below because SMTP is not set up yet.)';
    } else {
        unset($_SESSION['dev_otp_preview']);
        $error = 'A new code has been sent to your email.';
    }
}

// ---------- SUBMIT: VERIFY OTP + SET NEW PASSWORD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp      = implode('', $_POST['otp'] ?? []);
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($enteredOtp) !== 6) {
        $error = 'Please enter the complete 6-digit code.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'The new password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The two passwords you entered do not match.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM otp_codes
            WHERE user_id = ? AND otp_code = ? AND purpose = 'password_reset'
              AND is_used = 0 AND expires_at >= ?
            ORDER BY otp_id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $enteredOtp, date('Y-m-d H:i:s')]);
        $otpRow = $stmt->fetch();

        if (!$otpRow) {
            $error = 'The code is incorrect or has expired. Please try again.';
        } else {
            $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?")->execute([$otpRow['otp_id']]);

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$newHash, $userId]);

            // Send them back to whichever login page fits their role -
            // this same flow serves both regular users and staff.
            $stmtRole = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmtRole->execute([$userId]);
            $role = $stmtRole->fetchColumn();
            $loginUrl = in_array($role, ['office_head', 'admin'], true) ? '../login.php' : '../auth/login.php';

            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['dev_otp_preview']);
            $success = true;
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
      <p class="error-text" style="text-align:center;"><?= htmlspecialchars($error) ?></p>
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

      <button type="submit" class="btn-primary" style="margin-top:18px;">Reset Password</button>
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
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((box, i) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    if (box.value && i < boxes.length - 1) {
      boxes[i + 1].focus();
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
