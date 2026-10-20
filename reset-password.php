<?php
// GeoGene - Civil Registry STAFF reset-password. Pairs with forgot-password.php.
// Separate from auth/reset-password.php (regular citizen/user accounts).
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['staff_reset_user_id'])) {
    header('Location: forgot-password.php');
    exit;
}

$userId = $_SESSION['staff_reset_user_id'];
$email  = $_SESSION['staff_reset_email'] ?? '';
$error  = '';
$success = false;

if (isset($_GET['resend'])) {
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)")
        ->execute([$userId, $otp, $expiresAt]);

    require_once __DIR__ . '/config/mail.php';
    $stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmtName->execute([$userId]);
    $name = $stmtName->fetchColumn();
    $emailSent = send_password_reset_email($email, $name, $otp);

    if (!$emailSent) {
        $_SESSION['staff_reset_otp_preview'] = $otp;
        $error = 'A new code has been sent. (Dev mode: shown below.)';
    } else {
        unset($_SESSION['staff_reset_otp_preview']);
        $error = 'A new code has been sent to your email.';
    }
}

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
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ? AND role = 'office_head'")->execute([$newHash, $userId]);

            unset($_SESSION['staff_reset_user_id'], $_SESSION['staff_reset_email'], $_SESSION['staff_reset_otp_preview']);
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
<title>GeoGene - Staff Reset Password</title>
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
    width:400px;background:rgba(255,255,255,0.14);border-radius:20px;padding:34px 32px 30px;
    box-shadow:0 30px 60px rgba(0,0,0,0.45);backdrop-filter:blur(22px) saturate(160%);
    -webkit-backdrop-filter:blur(22px) saturate(160%);border:1px solid rgba(255,255,255,0.35);
  }
  .back-link{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,0.85);text-decoration:none;font-size:12.5px;font-weight:600;margin-bottom:18px;}
  .icon-circle{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;border:1px solid rgba(255,255,255,0.35);}
  .login-title{text-align:center;font-size:18px;font-weight:800;color:#fff;text-shadow:0 1px 6px rgba(0,0,0,0.25);}
  .login-sub{text-align:center;font-size:12.5px;color:rgba(255,255,255,0.85);margin:6px 0 22px;line-height:1.5;}

  .otp-wrap{display:flex;gap:8px;justify-content:center;margin-bottom:18px;}
  .otp-box{
    width:42px;height:50px;text-align:center;font-size:19px;font-weight:700;color:#fff;
    border:1.5px solid rgba(255,255,255,0.4);border-radius:9px;background:rgba(255,255,255,0.18);
    outline:none;backdrop-filter:blur(4px);
  }
  .otp-box:focus{border-color:#a8e6bd;background:rgba(236,236,236,0.67);color:#111;}

  .form-row{margin-bottom:14px;}
  .form-row label{display:block;font-size:12px;font-weight:600;color:rgba(255,255,255,0.9);margin-bottom:6px;}
  .input-wrap{position:relative;}
  .form-row input{
    width:100%;border:1.5px solid rgba(255,255,255,0.4);border-radius:9px;padding:11px 13px;
    font-size:13.5px;outline:none;background:rgba(255,255,255,0.18);color:#fff;
    backdrop-filter:blur(4px);transition:border-color .2s ease, background .2s ease;
  }
  .form-row input::placeholder{color:rgba(230,230,230,0.65);}
  .form-row input:focus{border-color:#a8e6bd;background:rgba(236,236,236,0.67);}

  .toggle-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.75);}
  .toggle-eye svg{width:18px;height:18px;}

  .login-btn{
    width:100%;background:rgba(1,2,2,0.85);color:#fff;border:1px solid rgba(255,255,255,0.35);
    border-radius:9px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;
    backdrop-filter:blur(6px);transition:background .2s ease;text-decoration:none;display:block;text-align:center;
  }
  .login-btn:hover{background:rgba(23,194,92,0.95);}

  .error-text{color:#ffd6d6;font-size:12.5px;text-align:center;margin-bottom:14px;}
  .footer-text{text-align:center;font-size:12.5px;color:rgba(255,255,255,0.8);margin-top:16px;}
  .footer-text a{color:#a8e6bd;font-weight:700;text-decoration:none;}
  .dev-otp-box{background:rgba(255,248,225,0.92);border:1px solid #f0c95a;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#7a5c00;text-align:center;}
</style>
</head>
<body>

<div class="brand">
  <div class="brand-sub">Civil Registry Office &middot; Manolo Fortich</div>
  <div class="brand-title">GeoGene</div>
</div>

<div class="login-card">
  <a href="forgot-password.php" class="back-link">&larr; Back</a>

  <?php if (!$success): ?>
    <div class="icon-circle">🔑</div>
    <h1 class="login-title">Reset Your Password</h1>
    <p class="login-sub">Enter the 6-digit code sent to<br><strong><?= htmlspecialchars($email) ?></strong> and choose a new password.</p>

    <?php if ($error): ?><p class="error-text"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <?php if (!empty($_SESSION['staff_reset_otp_preview'])): ?>
      <div class="dev-otp-box">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['staff_reset_otp_preview']) ?></strong>
      </div>
    <?php endif; ?>

    <form method="POST" action="reset-password.php" id="resetForm">
      <div class="otp-wrap">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" name="otp[]" maxlength="1" inputmode="numeric" class="otp-box" autocomplete="off">
        <?php endfor; ?>
      </div>

      <div class="form-row">
        <label>New Password</label>
        <div class="input-wrap">
          <input type="password" id="newPassword" name="new_password" placeholder="At least 8 characters" required minlength="8">
          <button type="button" class="toggle-eye" data-target="newPassword" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          </button>
        </div>
      </div>

      <div class="form-row">
        <label>Confirm New Password</label>
        <div class="input-wrap">
          <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-type your new password" required minlength="8">
          <button type="button" class="toggle-eye" data-target="confirmPassword" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn" style="border:1px solid rgba(255,255,255,0.35);">Reset Password</button>
    </form>

    <p class="footer-text">Didn't receive the code? <a href="reset-password.php?resend=1">Resend Code</a></p>
  <?php else: ?>
    <div class="icon-circle">✔</div>
    <h1 class="login-title">Password Reset!</h1>
    <p class="login-sub">Your staff password has been changed. You can now log in with your new password.</p>
    <a href="login.php" class="login-btn">Back to Login</a>
  <?php endif; ?>
</div>

<script>
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((box, i) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
  });
});

const eyeOpen  = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle>';
const eyeClosed = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

document.querySelectorAll('.toggle-eye').forEach((btn) => {
  const input = document.getElementById(btn.dataset.target);
  const svg = btn.querySelector('svg');
  btn.addEventListener('click', () => {
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    svg.innerHTML = isHidden ? eyeClosed : eyeOpen;
  });
});
</script>

</body>
</html>
