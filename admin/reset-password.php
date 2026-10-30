<?php
// GeoGene - ADMIN reset-password. Pairs with forgot-password.php.
// Logic lives in config/password-reset.php (hashed code, 10 min expiry, single use, 5 tries).
session_start();
require_once __DIR__ . '/../config/password-reset.php';   // loads db.php + mail.php too

$ctx = gg_pr_context('admin');
if (!isset($_SESSION[$ctx['uid']])) {
    header('Location: forgot-password.php');
    exit;
}

$userId = (int) $_SESSION[$ctx['uid']];
$email  = $_SESSION[$ctx['email']] ?? '';
$error  = '';
$notice = '';
$success = false;

// Resend (60s cooldown + hourly limits are enforced inside the helper)
if (isset($_GET['resend'])) {
    $result = gg_pr_request_code($pdo, $email, 'admin');
    if ($result['status'] === 'sent') {
        $notice = 'A new code has been sent to your email.';
    } else {
        $error = $result['message'] ?: 'Could not resend the code. Please try again.';
    }
}

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
        $res = gg_pr_verify_and_reset($pdo, $userId, $enteredOtp, $newPassword, 'admin');
        if ($res['ok']) {
            gg_pr_clear_session('admin');
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
<title>GeoGene - Admin Reset Password</title>
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
    background:url('../assets/images/municipal.png') center center/cover no-repeat;
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
    <?php if ($notice): ?><p class="error-text" style="color:#b8ffd6;"><?= htmlspecialchars($notice) ?></p><?php endif; ?>

    <?php if (!empty($_SESSION['admin_reset_otp_preview'])): ?>
      <div class="dev-otp-box">
        ⚠ <strong>DEV MODE</strong> — SMTP email is not set up yet.<br>
        Your reset code: <strong style="font-size:16px;letter-spacing:3px;"><?= htmlspecialchars($_SESSION['admin_reset_otp_preview']) ?></strong>
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

      <button type="submit" class="login-btn" id="rpBtn" style="border:1px solid rgba(255,255,255,0.35);">Reset Password</button>
    </form>

    <p class="footer-text">Didn't receive the code? <a href="reset-password.php?resend=1">Resend Code</a></p>
  <?php else: ?>
    <div class="icon-circle">✔</div>
    <h1 class="login-title">Password Reset!</h1>
    <p class="login-sub">Your admin password has been changed. You can now log in with your new password.</p>
    <a href="login.php" class="login-btn">Back to Admin Login</a>
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
    if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
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
