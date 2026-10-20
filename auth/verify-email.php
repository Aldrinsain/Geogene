<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['pending_user_id'])) {
    header('Location: welcome.php');
    exit;
}

$userId = $_SESSION['pending_user_id'];
$email  = $_SESSION['pending_email'] ?? '';
$error  = '';
$success = false;

// ---------- RESEND OTP ----------
if (isset($_GET['resend'])) {
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'email_verification', ?)");
    $stmt->execute([$userId, $otp, $expiresAt]);

    require_once __DIR__ . '/../config/mail.php';
    $stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmtName->execute([$userId]);
    $name = $stmtName->fetchColumn();
    $emailSent = send_otp_email($email, $name, $otp);

    if (!$emailSent) {
        $_SESSION['dev_otp_preview'] = $otp;
        $error = 'A new code has been sent. (Dev mode: shown below because SMTP is not set up yet.)';
    } else {
        unset($_SESSION['dev_otp_preview']);
        $error = 'A new code has been sent to your email.';
    }
}

// ---------- VERIFY OTP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = implode('', $_POST['otp'] ?? []);

    if (strlen($enteredOtp) !== 6) {
        $error = 'Please enter the complete 6-digit code.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM otp_codes
            WHERE user_id = ? AND otp_code = ? AND purpose = 'email_verification'
              AND is_used = 0 AND expires_at >= ?
            ORDER BY otp_id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $enteredOtp, date('Y-m-d H:i:s')]);
        $otpRow = $stmt->fetch();

        if (!$otpRow) {
            $error = 'The code is incorrect or has expired. Please try again.';
        } else {
            $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE otp_id = ?")->execute([$otpRow['otp_id']]);
            $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active' WHERE user_id = ?")->execute([$userId]);

            // ---------- AUTO-LOGIN: diretso na sa Dashboard, dili na kinahanglan
            // mag-login pa separately human ma-verify ang email ----------
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch();

            unset($_SESSION['pending_user_id'], $_SESSION['pending_email'], $_SESSION['dev_otp_preview']);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['identity_verified'] = $user['identity_verified'];

            $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
                ->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

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
<title>GeoGene - Verify Email</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
</head>
<body>

<div class="phone">
  <div class="app-header">
    <a href="welcome.php" class="back-arrow">&larr;</a>
    <span>GeoGene</span>
  </div>

  <?php if (!$success): ?>
    <div class="icon-circle green">✉</div>
    <h1 class="title center">Enter Verification Code</h1>
    <p class="subtitle center">We sent a 6-digit code to<br><strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if ($error): ?>
      <p class="error-text" style="text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['dev_otp_preview'])): ?>
      <div style="background:#fff8e1;border:1px solid #f0c95a;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12.5px;color:#7a5c00;text-align:center;">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your OTP code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['dev_otp_preview']) ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" action="verify-email.php" id="otpForm">
      <div class="otp-wrap">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" name="otp[]" maxlength="1" inputmode="numeric" class="otp-box" autocomplete="off">
        <?php endfor; ?>
      </div>
      <button type="submit" class="btn-primary">Verify Code</button>
    </form>

    <p class="footer-text">Didn't receive the code? <a href="verify-email.php?resend=1">Resend Code</a></p>
  <?php else: ?>
    <div class="icon-circle green">✔</div>
    <h1 class="title center">Email Verified!</h1>
    <p class="subtitle center">Your email has been successfully verified. Welcome to GeoGene, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? '')[0] ?? '') ?>!</p>
    <a href="../dashboard/dashboard.php" class="btn-primary" style="text-decoration:none;">Continue to Dashboard</a>
  <?php endif; ?>
</div>

<script>
// Auto-advance sa sunod nga OTP box, ug auto-submit sa human ma-fill tanan
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((box, i) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    if (box.value && i < boxes.length - 1) {
      boxes[i + 1].focus();
    }
    const allFilled = [...boxes].every(b => b.value.length === 1);
    if (allFilled) {
      document.getElementById('otpForm').submit();
    }
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) {
      boxes[i - 1].focus();
    }
  });
});
if (boxes.length) boxes[0].focus();
</script>
</body>
</html>
