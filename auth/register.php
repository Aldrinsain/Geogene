<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules (Given Middle Surname, Mixed Case)

// I-require nga naa nay na-verify nga MCRO record gikan sa Search Full Name step
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
if (empty($_SESSION['matched_record_id'])) {
    header('Location: ../identity-verification/search-name.php');
    exit;
}
$matchedRecordId = $_SESSION['matched_record_id'];
$suggestedName = gg_display_name($_GET['name'] ?? ($_SESSION['search_full_name'] ?? ''));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = gg_display_name(trim($_POST['full_name'] ?? ''));   // "Given Middle Surname", Mixed Case
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Password and confirm password do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        // Check kung naa nay account sa email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'An account is already registered with this email.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Naay ma-auto-fill nga Date of Birth / Barangay kung na-basa kini
            // gikan sa ID scan (identity-verification/complete-verification.php)
            // - dili na kinahanglan i-type pag-usab sa user dinhi sa Register.
            $ocrDob = $_SESSION['ocr_date_of_birth'] ?? null;
            $ocrBarangay = $_SESSION['ocr_barangay'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, status, email_verified, matched_record_id, date_of_birth, barangay)
                VALUES (?, ?, ?, 'pending', 0, ?, ?, ?)
            ");
            $stmt->execute([$fullName, $email, $hash, $matchedRecordId, $ocrDob, $ocrBarangay]);
            $userId = $pdo->lastInsertId();

            // Generate 6-digit OTP
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'email_verification', ?)");
            $stmt->execute([$userId, $otp, $expiresAt]);

            require_once __DIR__ . '/../config/mail.php';
            $emailSent = send_otp_email($email, $fullName, $otp);

            unset($_SESSION['matched_record_id'], $_SESSION['search_full_name'], $_SESSION['ocr_date_of_birth'], $_SESSION['ocr_barangay']);
            $_SESSION['pending_user_id'] = $userId;
            $_SESSION['pending_email'] = $email;
            if (!$emailSent) {
                // DEV MODE fallback: PHPMailer wala pa na-setup, ipakita ang OTP
                // diretso sa verify-email.php screen para makapadayon og testing.
                if (GEOGENE_SHOW_DEV_OTP) $_SESSION['dev_otp_preview'] = $otp;
            }

            header('Location: verify-email.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Register Account</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
</head>
<body>

<div class="phone">
  <div class="app-header">
    <a href="../identity-verification/search-name.php" class="back-arrow">&larr;</a>
    <span>GeoGene</span>
  </div>

  <h1 class="title">Create Your Account</h1>
  <p class="subtitle">Confirm your name using the official MCRO records before creating your GeoGene account.</p>

  <?php if ($error): ?>
    <p class="error-text"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST" action="register.php" autocomplete="off">
    <!-- Decoy fields: browsers try to autofill the first username/password-looking
         inputs they see, so we give them harmless ones to fill instead, hidden
         from the person, leaving the real fields below untouched. -->
    <input type="text" name="fake_username" autocomplete="username" tabindex="-1" aria-hidden="true"
           style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;">
    <input type="password" name="fake_password" autocomplete="new-password" tabindex="-1" aria-hidden="true"
           style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;">

    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input type="text" name="full_name" class="form-input" placeholder="Juan Dela Cruz" required
             autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
             value="<?= htmlspecialchars($_POST['full_name'] ?? $suggestedName) ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="you@example.com" required
             autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="input-icon-wrap">
        <input type="password" name="password" id="password" class="form-input" placeholder="At least 8 characters"
               autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" required>
        <span class="input-icon" onclick="togglePw('password', this)">👁</span>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <div class="input-icon-wrap">
        <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Re-enter password"
               autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" required>
        <span class="input-icon" onclick="togglePw('confirm_password', this)">👁</span>
      </div>
    </div>

    <button type="submit" class="btn-primary">Create an Account</button>
  </form>

  <p class="footer-text">Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
function togglePw(id, el) {
  const input = document.getElementById(id);
  const svg = el.querySelector('.eye-icon');

  if (input.type === 'password') {
    input.type = 'text';

    svg.innerHTML = `
      <path d="M21.2501 9.14906C20.7601 8.36906 20.2001 7.66906 19.6201 7.03906L15.8501 10.8091C15.9701 11.1791 16.0401 11.5791 16.0401 11.9991C16.0401 14.2391 14.2301 16.0391 12.0001 16.0391C11.5801 16.0391 11.1801 15.9691 10.8101 15.8491L7.3501 19.3091C8.8101 20.1291 10.3901 20.5591 12.0001 20.5591C13.7801 20.5591 15.5101 20.0391 17.0901 19.0691C18.6701 18.0891 20.0901 16.6591 21.2501 14.8391C22.2501 13.2791 22.2501 10.7191 21.2501 9.14906Z"/>
      <path d="M14.0201 9.98062L9.98014 14.0206C9.47014 13.5006 9.14014 12.7806 9.14014 12.0006C9.14014 10.4306 10.4201 9.14062 12.0001 9.14062C12.7801 9.14062 13.5001 9.47062 14.0201 9.98062Z"/>
      <path d="M18.25 5.74969L14.86 9.13969C14.13 8.39969 13.12 7.95969 12 7.95969C9.76 7.95969 7.96 9.76969 7.96 11.9997C7.96 13.1197 8.41 14.1297 9.14 14.8597L5.76 18.2497H5.75C4.64 17.3497 3.62 16.1997 2.75 14.8397C1.75 13.2697 1.75 10.7197 2.75 9.14969C3.91 7.32969 5.33 5.89969 6.91 4.91969C8.49 3.95969 10.22 3.42969 12 3.42969C14.23 3.42969 16.39 4.24969 18.25 5.74969Z"/>
      <path d="M14.8601 12.0001C14.8601 13.5701 13.5801 14.8601 12.0001 14.8601C11.9401 14.8601 11.8901 14.8601 11.8301 14.8401L14.8401 11.8301C14.8601 11.8901 14.8601 11.9401 14.8601 12.0001Z"/>
      <path d="M21.7699 2.23086C21.4699 1.93086 20.9799 1.93086 20.6799 2.23086L2.22988 20.6909C1.92988 20.9909 1.92988 21.4809 2.22988 21.7809C2.37988 21.9209 2.56988 22.0009 2.76988 22.0009C2.96988 22.0009 3.15988 21.9209 3.30988 21.7709L21.7699 3.31086C22.0799 3.01086 22.0799 2.53086 21.7699 2.23086Z"/>
    `;
  } else {
    input.type = 'password';

    svg.innerHTML = `
      <path d="M21.25 9.14969C18.94 5.51969 15.56 3.42969 12 3.42969C10.22 3.42969 8.49 3.94969 6.91 4.91969C5.33 5.89969 3.91 7.32969 2.75 9.14969C1.75 10.7197 1.75 13.2697 2.75 14.8397C5.06 18.4797 8.44 20.5597 12 20.5597C13.78 20.5597 15.51 20.0397 17.09 19.0697C18.67 18.0897 20.09 16.6597 21.25 14.8397C22.25 13.2797 22.25 10.7197 21.25 9.14969ZM12 16.0397C9.76 16.0397 7.96 14.2297 7.96 11.9997C7.96 9.76969 9.76 7.95969 12 7.95969C14.24 7.95969 16.04 9.76969 16.04 11.9997C16.04 14.2297 14.24 16.0397 12 16.0397Z"/>
      <path d="M11.9999 9.14062C10.4299 9.14062 9.1499 10.4206 9.1499 12.0006C9.1499 13.5706 10.4299 14.8506 11.9999 14.8506C13.5699 14.8506 14.8599 13.5706 14.8599 12.0006C14.8599 10.4306 13.5699 9.14062 11.9999 9.14062Z"/>
    `;
  }
}
</script>
</body>
</html>
