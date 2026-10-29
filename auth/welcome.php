<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules (Given Middle Surname, Mixed Case)
require_once __DIR__ . '/../includes/logo.php';

$registerError = '';

// ---------- Kinahanglan naa nay na-verify nga MCRO record gikan sa Search Full Name ----------
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
$hasMatchedRecord = !empty($_SESSION['matched_record_id']);
$hasScanVerified = !empty($_SESSION['identity_scan_verified']);
$suggestedName = gg_display_name($_GET['name'] ?? ($_SESSION['search_full_name'] ?? ''));

// ---------- Kuhaon ang sex/civil status/mother/father gikan sa na-verify nga MCRO record ----------
// (dili gikan sa OCR/ID scan - daghang government ID walay naka-print nga
// civil status o parents' names, mao nga ang official record ang gigamit.)
$matchedRecord = null;
if ($hasMatchedRecord) {
    $stmt = $pdo->prepare("SELECT sex, civil_status, mother_name, father_name FROM civil_registry_records WHERE record_id = ?");
    $stmt->execute([$_SESSION['matched_record_id']]);
    $matchedRecord = $stmt->fetch();
}

// Kung naa nay na-verify nga ngalan pero wala pa na-scan ang ID, ipadala una
// sa Scan ID step (dili diretso motugot og Register).
if ($hasMatchedRecord && !$hasScanVerified && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../identity-verification/scan-id.php');
    exit;
}

// ---------- HANDLE REGISTER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['matched_record_id'])) {
        $registerError = 'You must first search for your full name in the MCRO records.';
    } elseif (empty($_SESSION['identity_scan_verified'])) {
        $registerError = 'You must first complete the Scan ID verification.';
    } else {
        $fullName = gg_display_name(trim($_POST['full_name'] ?? ''));   // "Given Middle Surname", Mixed Case
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '') {
            $registerError = 'Please fill in all fields.';
        } elseif ($password !== $confirm) {
            $registerError = 'Password and confirm password do not match.';
        } elseif (strlen($password) < 8) {
            $registerError = 'Password must be at least 8 characters.';
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $registerError = 'An account is already registered with this email.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $matchedRecordId = $_SESSION['matched_record_id'];

                // Gikan sa na-verify nga MCRO record - dili gikan sa user input
                // mismo, aron mapadayon ang integrity sa official records.
                $stmt = $pdo->prepare("SELECT sex, civil_status FROM civil_registry_records WHERE record_id = ?");
                $stmt->execute([$matchedRecordId]);
                $recordDetails = $stmt->fetch();
                $gender = $recordDetails['sex'] ?? null;
                $civilStatus = $recordDetails['civil_status'] ?? null;

                // identity_verified = 1 dayon kay na-human na ang Scan ID
                // step una pa ni nga Register (Search Name -> Scan ID -> Register).
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, status, email_verified, identity_verified, matched_record_id, gender, civil_status) VALUES (?, ?, ?, 'pending', 0, 1, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $hash, $matchedRecordId, $gender, $civilStatus]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("
                    INSERT INTO identity_verifications (user_id, verification_method, status, verified_at)
                    VALUES (?, 'id_scan', 'verified', NOW())
                ");
                $stmt->execute([$userId]);

                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) VALUES (?, ?, 'email_verification', ?)");
                $stmt->execute([$userId, $otp, $expiresAt]);

                require_once __DIR__ . '/../config/mail.php';
                $emailSent = send_otp_email($email, $fullName, $otp);

                unset($_SESSION['matched_record_id'], $_SESSION['search_full_name'], $_SESSION['identity_scan_verified']);
                $_SESSION['pending_user_id'] = $userId;
                $_SESSION['pending_email'] = $email;
                if (!$emailSent) {
                    if (GEOGENE_SHOW_DEV_OTP) $_SESSION['dev_otp_preview'] = $otp;
                }

                header('Location: verify-email.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Create Your Account</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{overflow:hidden;height:100%;max-width:100%;}
  body{
    min-height:100vh;height:100vh;height:100dvh;
    display:flex;align-items:center;justify-content:center;
    background:#070c1c;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  }

  .phone{
    width:393px;height:720px;max-height:100vh;max-height:100dvh;
    border-radius:32px;
    overflow:hidden;
    position:relative;
    box-shadow:0 20px 50px rgba(0,0,0,0.4);
    background:#0B132B;
    display:flex;flex-direction:column;
  }
  @media (max-width: 480px){
    body{ height:100vh;height:100dvh; }
    .phone{ width:100%;height:100vh;height:100dvh;border-radius:0;box-shadow:none; }
  }
  @media (max-height:700px){
    .auth-screen{padding:16px 22px 18px;}
    .auth-header{margin-bottom:12px;}
    .auth-logo{width:60px;height:60px;margin-bottom:8px;}
    .field-group{margin-bottom:8px;}
    .terms-row{margin:4px 0 10px;}
  }

  .auth-screen{
    position:relative;background:#0B132B;display:flex;flex-direction:column;
    padding:20px 26px 24px;
    animation:cardEnter .6s cubic-bezier(.65,0,.35,1) both;
    flex:1;justify-content:flex-start;
    overflow-y:auto;
  }
  @keyframes cardEnter{ from{transform:translateY(24px);opacity:0;} to{transform:translateY(0);opacity:1;} }

  .back-arrow{
    width:38px;height:38px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
    color:#f2f4fa;text-decoration:none;
    backdrop-filter:blur(10px);
    transition:background .2s ease, transform .2s ease;
    margin-bottom:18px;
  }
  .back-arrow:hover{background:rgba(57,255,136,0.12);transform:translateY(-1px);}
  .back-arrow svg{width:17px;height:17px;}

  .auth-header{text-align:center;margin-bottom:22px;}
  .auth-logo{
    width:76px;height:76px;border-radius:22px;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 14px;background:#101b3c;
    box-shadow:
      8px 8px 18px rgba(0,0,0,0.5),
      -8px -8px 18px rgba(255,255,255,0.03);
    border:1px solid rgba(57,255,136,0.15);
    animation:logoMotion .7s cubic-bezier(.34,1.56,.64,1) .1s both, logoFloat 3s ease-in-out 1s infinite;
  }
  @keyframes logoMotion{
    0%{opacity:0;transform:translateY(16px) scale(.8);}
    70%{opacity:1;transform:translateY(-3px) scale(1.05);}
    100%{opacity:1;transform:translateY(0) scale(1);}
  }
  @keyframes logoFloat{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-4px);} }

  .auth-title{font-size:19px;font-weight:800;color:#f2f4fa;margin-bottom:6px;}
  .auth-sub{font-size:12px;color:#8a93b8;line-height:1.5;max-width:270px;margin:0 auto;}

  .field-group{
    position:relative;display:flex;align-items:center;gap:10px;
    background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
    border-radius:14px;padding:0 14px;margin-bottom:12px;
    transition:border-color .2s ease, background .2s ease;
  }
  .field-group:focus-within{border-color:rgba(57,255,136,0.45);background:rgba(255,255,255,0.08);}
  .field-icon{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;color:#8a93b8;}
  .field-icon svg{width:18px;height:18px;display:block;}
  .field-input{
    flex:1;min-width:0;background:transparent;border:none;outline:none;
    color:#f2f4fa;font-size:13.5px;padding:13px 0;font-family:inherit;
  }
  .field-input::placeholder{color:#6b7391;}
  .pw-toggle{display:flex;align-items:center;flex-shrink:0;cursor:pointer;color:#8a93b8;}
  .pw-toggle svg{width:18px;height:18px;display:block;}

  .terms-row{display:flex;align-items:flex-start;gap:8px;font-size:11px;color:#8a93b8;line-height:1.5;margin:6px 0 18px;}
  .terms-row input{margin-top:2px;accent-color:#39FF88;flex-shrink:0;}

  .submit-btn{
    width:100%;background:linear-gradient(160deg,#5cffa3,#39FF88);color:#0B132B;border:none;
    border-radius:14px;padding:14px;font-size:14.5px;font-weight:800;cursor:pointer;
    box-shadow:0 1px 0 rgba(255,255,255,.5) inset, 0 10px 20px rgba(57,255,136,0.28);
    transition:transform .15s ease;
  }
  .submit-btn:hover{transform:translateY(-2px);}
  .submit-btn:active{transform:scale(0.98);}
  .submit-btn:disabled{opacity:.5;cursor:not-allowed;box-shadow:none;}
  .submit-btn:disabled:hover{transform:none;}

  .switch-line{text-align:center;font-size:12.5px;color:#8a93b8;margin-top:18px;}

  .form-error{
    background:rgba(255,92,108,0.12);color:#ff8b96;font-size:11.5px;border-radius:10px;
    padding:9px 12px;margin-bottom:14px;border:1px solid rgba(255,92,108,0.25);
  }
  .warn-banner{
    background:rgba(255,196,84,0.1);color:#ffd27a;font-size:11px;border-radius:10px;
    padding:10px 12px;margin-bottom:16px;line-height:1.5;border:1px solid rgba(255,196,84,0.25);
  }
  .warn-banner a{color:#ffd27a;font-weight:700;}

  .readonly-info-box{
    background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:10px 14px;margin-bottom:12px;
  }
  .ri-row{
    display:flex;justify-content:space-between;align-items:center;
    padding:6px 0;font-size:12px;
    border-bottom:1px solid rgba(255,255,255,0.06);
  }
  .ri-row:last-child{border-bottom:none;}
  .ri-label{color:#8a93b8;}
  .ri-value{color:#f2f4fa;font-weight:600;text-align:right;}
</style>
</head>
<body>

<div class="phone">
  <div class="auth-screen">
    <a href="intro.php" class="back-arrow">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    </a>

    <div class="auth-header">
      <div class="auth-logo">
<svg role="img" focusable="false" aria-hidden="true"
     xmlns="http://www.w3.org/2000/svg"
     width="48" height="48" viewBox="0 0 14 14">
  <g transform="rotate(90 7 7)">
    <path fill="#C8E6C9" d="M4.3 7.3v3.3h4.8v1.5h2.7v-.6H9.7V9.1h2.1v-.6H9.1V10H4.9V4h4.2v1.5h2.7v-.6H9.7V2.5h2.1v-.6H9.1v1.5H4.3v3.3H2.2v.6z"/>
    <path fill="#81C784" d="M1 5.8h2.4v2.4H1z"/>
    <path fill="#43A047" d="M10.6 4H13v2.4h-2.4zm0-3H13v2.4h-2.4zM5.8 2.5h2.4v2.4H5.8zm0 6.6h2.4v2.4H5.8zm4.8 1.5H13V13h-2.4zm0-3H13V10h-2.4z"/>
  </g>
</svg>
</div>
      <div class="auth-title">Create Your Account</div>
      <p class="auth-sub">Confirm your name using the official MCRO records before creating your GeoGene account.</p>
    </div>

    <form method="POST" action="welcome.php">
      <?php if ($registerError): ?>
        <div class="form-error"><?= htmlspecialchars($registerError) ?></div>
      <?php endif; ?>

      <?php if (!$hasMatchedRecord): ?>
        <div class="warn-banner">
          ⚠ You must first search and verify your full name in the
          <a href="../identity-verification/search-name.php">MCRO records</a> before you can create an account.
        </div>
      <?php endif; ?>

      <div class="field-group">
        <span class="field-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
            <g clip-path="url(#clip0_3111_32659)">
              <path opacity="0.4" d="M21.09 21.5C21.09 21.78 20.87 22 20.59 22H3.41003C3.13003 22 2.91003 21.78 2.91003 21.5C2.91003 17.36 6.99003 14 12 14C13.03 14 14.03 14.14 14.95 14.41C14.36 15.11 14 16.02 14 17C14 17.75 14.21 18.46 14.58 19.06C14.78 19.4 15.04 19.71 15.34 19.97C16.04 20.61 16.97 21 18 21C19.12 21 20.13 20.54 20.85 19.8C21.01 20.34 21.09 20.91 21.09 21.5Z" fill="white" style="fill: var(--fillg);"/>
              <path d="M21.88 16.0398C21.78 15.6498 21.62 15.2598 21.4 14.9098C21.25 14.6498 21.05 14.3998 20.83 14.1698C20.11 13.4498 19.17 13.0598 18.21 13.0098C17.12 12.9398 16.01 13.3398 15.17 14.1698C14.38 14.9598 13.98 16.0098 14 17.0598C14.01 18.0598 14.41 19.0598 15.17 19.8298C15.7 20.3598 16.35 20.7098 17.04 20.8698C17.42 20.9698 17.82 21.0098 18.22 20.9798C19.17 20.9398 20.1 20.5598 20.83 19.8298C21.86 18.7998 22.21 17.3498 21.88 16.0398ZM19.6 18.5998C19.31 18.8898 18.83 18.8898 18.54 18.5998L17.99 18.0498L17.46 18.5798C17.17 18.8698 16.69 18.8698 16.4 18.5798C16.11 18.2798 16.11 17.8098 16.4 17.5198L16.93 16.9898L16.42 16.4898C16.13 16.1898 16.13 15.7198 16.42 15.4198C16.72 15.1298 17.19 15.1298 17.49 15.4198L17.99 15.9298L18.52 15.3998C18.81 15.1098 19.28 15.1098 19.58 15.3998C19.87 15.6898 19.87 16.1698 19.58 16.4598L19.05 16.9898L19.6 17.5398C19.89 17.8298 19.89 18.3098 19.6 18.5998Z" fill="white" style="fill: var(--fillg);"/>
              <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="white" style="fill: var(--fillg);"/>
            </g>
            <defs><clipPath id="clip0_3111_32659"><rect width="24" height="24" fill="white"/></clipPath></defs>
          </svg>
        </span>
        <input type="text" name="full_name" class="field-input" placeholder="Full Name"
               value="<?= htmlspecialchars($suggestedName) ?>" required>
      </div>

      <?php if ($matchedRecord): ?>
      <!-- Kining mga sunod nga fields kay auto-filled gikan sa na-verify nga MCRO
           record (dili gikan sa ID scan mismo) - dili na ma-edit sa user kay
           kini ang OFFICIAL nga datos gikan sa civil registry. -->
      <div class="readonly-info-box">
        <div class="ri-row">
          <span class="ri-label">Sex</span>
          <span class="ri-value"><?= htmlspecialchars(ucfirst($matchedRecord['sex'] ?? 'N/A')) ?></span>
        </div>
        <div class="ri-row">
          <span class="ri-label">Civil Status</span>
          <span class="ri-value"><?= htmlspecialchars(ucfirst($matchedRecord['civil_status'] ?? 'N/A')) ?></span>
        </div>
        <div class="ri-row">
          <span class="ri-label">Mother's Name</span>
          <span class="ri-value"><?= htmlspecialchars($matchedRecord['mother_name'] ?? 'N/A') ?></span>
        </div>
        <div class="ri-row">
          <span class="ri-label">Father's Name</span>
          <span class="ri-value"><?= htmlspecialchars($matchedRecord['father_name'] ?? 'N/A') ?></span>
        </div>
      </div>
      <?php endif; ?>

      <div class="field-group">
        <span class="field-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b4b5b3" stroke-width="1.8">
            <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 6l10 7 10-7"/>
          </svg>
        </span>
        <input type="email" name="email" class="field-input" placeholder="Email Address" required>
      </div>

      <div class="field-group">
        <span class="field-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
            <g clip-path="url(#clip0_4418_9529)">
              <path d="M6 10V8C6 4.69 7 2 12 2C17 2 18 4.69 18 8V10" stroke="#b4b5b3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M12 18.5C13.3807 18.5 14.5 17.3807 14.5 16C14.5 14.6193 13.3807 13.5 12 13.5C10.6193 13.5 9.5 14.6193 9.5 16C9.5 17.3807 10.6193 18.5 12 18.5Z" stroke="#b4b5b3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M17 22H7C3 22 2 21 2 17V15C2 11 3 10 7 10H17C21 10 22 11 22 15V17C22 21 21 22 17 22Z" stroke="#b4b5b3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </g>
            <defs><clipPath id="clip0_4418_9529"><rect width="24" height="24" fill="white"/></clipPath></defs>
          </svg>
        </span>
        <input type="password" name="password" id="signupPassword" class="field-input" placeholder="Password (at least 8 characters)" required>
        <span class="pw-toggle" onclick="toggleFieldPw('signupPassword', this)">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
            <g clip-path="url(#clip0_4418_8295)">
              <path d="M21.25 9.14969C18.94 5.51969 15.56 3.42969 12 3.42969C10.22 3.42969 8.49 3.94969 6.91 4.91969C5.33 5.89969 3.91 7.32969 2.75 9.14969C1.75 10.7197 1.75 13.2697 2.75 14.8397C5.06 18.4797 8.44 20.5597 12 20.5597C13.78 20.5597 15.51 20.0397 17.09 19.0697C18.67 18.0897 20.09 16.6597 21.25 14.8397C22.25 13.2797 22.25 10.7197 21.25 9.14969ZM12 16.0397C9.76 16.0397 7.96 14.2297 7.96 11.9997C7.96 9.76969 9.76 7.95969 12 7.95969C14.24 7.95969 16.04 9.76969 16.04 11.9997C16.04 14.2297 14.24 16.0397 12 16.0397Z" fill="white" style="fill: var(--fillg);"/>
              <path d="M11.9999 9.14062C10.4299 9.14062 9.1499 10.4206 9.1499 12.0006C9.1499 13.5706 10.4299 14.8506 11.9999 14.8506C13.5699 14.8506 14.8599 13.5706 14.8599 12.0006C14.8599 10.4306 13.5699 9.14062 11.9999 9.14062Z" fill="white" style="fill: var(--fillg);"/>
            </g>
            <defs><clipPath id="clip0_4418_8295"><rect width="24" height="24" fill="white"/></clipPath></defs>
          </svg>
        </span>
      </div>

      <div class="field-group">
        <span class="field-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
            <g clip-path="url(#clip0_4418_4653)">
              <path opacity="0.4" d="M12.0001 17.3498C12.9003 17.3498 13.6301 16.6201 13.6301 15.7198C13.6301 14.8196 12.9003 14.0898 12.0001 14.0898C11.0999 14.0898 10.3701 14.8196 10.3701 15.7198C10.3701 16.6201 11.0999 17.3498 12.0001 17.3498Z" fill="white" style="fill: var(--fillg);"/>
              <path d="M16.65 9.43945H7.35C3.25 9.43945 2 10.6895 2 14.7895V16.6495C2 20.7495 3.25 21.9995 7.35 21.9995H16.65C20.75 21.9995 22 20.7495 22 16.6495V14.7895C22 10.6895 20.75 9.43945 16.65 9.43945ZM12 18.7395C10.33 18.7395 8.98 17.3795 8.98 15.7195C8.98 14.0595 10.33 12.6995 12 12.6995C13.67 12.6995 15.02 14.0595 15.02 15.7195C15.02 17.3795 13.67 18.7395 12 18.7395Z" fill="white" style="fill: var(--fillg);"/>
              <path opacity="0.4" d="M7.12021 9.45V8.28C7.12021 5.35 7.95021 3.4 12.0002 3.4C16.0502 3.4 16.8802 5.35 16.8802 8.28V9.45C17.3902 9.46 17.8502 9.48 18.2802 9.54V8.28C18.2802 5.58 17.6302 2 12.0002 2C6.37021 2 5.72021 5.58 5.72021 8.28V9.53C6.14021 9.48 6.61021 9.45 7.12021 9.45Z" fill="white" style="fill: var(--fillg);"/>
            </g>
            <defs><clipPath id="clip0_4418_4653"><rect width="24" height="24" fill="white"/></clipPath></defs>
          </svg>
        </span>
        <input type="password" name="confirm_password" id="signupConfirmPassword" class="field-input" placeholder="Confirm Password" required>
        <span class="pw-toggle" onclick="toggleFieldPw('signupConfirmPassword', this)">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
            <g clip-path="url(#clip0_4418_8295)">
              <path d="M21.25 9.14969C18.94 5.51969 15.56 3.42969 12 3.42969C10.22 3.42969 8.49 3.94969 6.91 4.91969C5.33 5.89969 3.91 7.32969 2.75 9.14969C1.75 10.7197 1.75 13.2697 2.75 14.8397C5.06 18.4797 8.44 20.5597 12 20.5597C13.78 20.5597 15.51 20.0397 17.09 19.0697C18.67 18.0897 20.09 16.6597 21.25 14.8397C22.25 13.2797 22.25 10.7197 21.25 9.14969ZM12 16.0397C9.76 16.0397 7.96 14.2297 7.96 11.9997C7.96 9.76969 9.76 7.95969 12 7.95969C14.24 7.95969 16.04 9.76969 16.04 11.9997C16.04 14.2297 14.24 16.0397 12 16.0397Z" fill="white" style="fill: var(--fillg);"/>
              <path d="M11.9999 9.14062C10.4299 9.14062 9.1499 10.4206 9.1499 12.0006C9.1499 13.5706 10.4299 14.8506 11.9999 14.8506C13.5699 14.8506 14.8599 13.5706 14.8599 12.0006C14.8599 10.4306 13.5699 9.14062 11.9999 9.14062Z" fill="white" style="fill: var(--fillg);"/>
            </g>
            <defs><clipPath id="clip0_4418_8295"><rect width="24" height="24" fill="white"/></clipPath></defs>
          </svg>
        </span>
      </div>

      <label class="terms-row">
        <input type="checkbox" required>
        <span>I confirm my name matches the official MCRO records before creating my GeoGene account.</span>
      </label>

      <button type="submit" class="submit-btn" <?= !$hasMatchedRecord ? 'disabled' : '' ?>>Register Account</button>
      <p class="switch-line">Already have an account? <a href="login.php" style="color:#39FF88;font-weight:700;text-decoration:none;">Login</a></p>
    </form>
  </div>
</div>

<script>
const fieldEyeOpenSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
  <g clip-path="url(#clip0_4418_8295)">
    <path d="M21.25 9.14969C18.94 5.51969 15.56 3.42969 12 3.42969C10.22 3.42969 8.49 3.94969 6.91 4.91969C5.33 5.89969 3.91 7.32969 2.75 9.14969C1.75 10.7197 1.75 13.2697 2.75 14.8397C5.06 18.4797 8.44 20.5597 12 20.5597C13.78 20.5597 15.51 20.0397 17.09 19.0697C18.67 18.0897 20.09 16.6597 21.25 14.8397C22.25 13.2797 22.25 10.7197 21.25 9.14969ZM12 16.0397C9.76 16.0397 7.96 14.2297 7.96 11.9997C7.96 9.76969 9.76 7.95969 12 7.95969C14.24 7.95969 16.04 9.76969 16.04 11.9997C16.04 14.2297 14.24 16.0397 12 16.0397Z" fill="white" style="fill: var(--fillg);"/>
    <path d="M11.9999 9.14062C10.4299 9.14062 9.1499 10.4206 9.1499 12.0006C9.1499 13.5706 10.4299 14.8506 11.9999 14.8506C13.5699 14.8506 14.8599 13.5706 14.8599 12.0006C14.8599 10.4306 13.5699 9.14062 11.9999 9.14062Z" fill="white" style="fill: var(--fillg);"/>
  </g>
  <defs><clipPath id="clip0_4418_8295"><rect width="24" height="24" fill="white"/></clipPath></defs>
</svg>`;

const fieldEyeOffSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#b4b5b3">
  <g clip-path="url(#clip0_4418_4643)">
    <path opacity="0.4" d="M21.2501 9.14906C20.7601 8.36906 20.2001 7.66906 19.6201 7.03906L15.8501 10.8091C15.9701 11.1791 16.0401 11.5791 16.0401 11.9991C16.0401 14.2391 14.2301 16.0391 12.0001 16.0391C11.5801 16.0391 11.1801 15.9691 10.8101 15.8491L7.3501 19.3091C8.8101 20.1291 10.3901 20.5591 12.0001 20.5591C13.7801 20.5591 15.5101 20.0391 17.0901 19.0691C18.6701 18.0891 20.0901 16.6591 21.2501 14.8391C22.2501 13.2791 22.2501 10.7191 21.2501 9.14906Z" fill="white" style="fill: var(--fillg);"/>
    <path d="M14.0201 9.98062L9.98014 14.0206C9.47014 13.5006 9.14014 12.7806 9.14014 12.0006C9.14014 10.4306 10.4201 9.14062 12.0001 9.14062C12.7801 9.14062 13.5001 9.47062 14.0201 9.98062Z" fill="white" style="fill: var(--fillg);"/>
    <path opacity="0.4" d="M18.25 5.74969L14.86 9.13969C14.13 8.39969 13.12 7.95969 12 7.95969C9.76 7.95969 7.96 9.76969 7.96 11.9997C7.96 13.1197 8.41 14.1297 9.14 14.8597L5.76 18.2497H5.75C4.64 17.3497 3.62 16.1997 2.75 14.8397C1.75 13.2697 1.75 10.7197 2.75 9.14969C3.91 7.32969 5.33 5.89969 6.91 4.91969C8.49 3.95969 10.22 3.42969 12 3.42969C14.23 3.42969 16.39 4.24969 18.25 5.74969Z" fill="white" style="fill: var(--fillg);"/>
    <path d="M14.8601 12.0001C14.8601 13.5701 13.5801 14.8601 12.0001 14.8601C11.9401 14.8601 11.8901 14.8601 11.8301 14.8401L14.8401 11.8301C14.8601 11.8901 14.8601 11.9401 14.8601 12.0001Z" fill="white" style="fill: var(--fillg);"/>
    <path d="M21.7699 2.23086C21.4699 1.93086 20.9799 1.93086 20.6799 2.23086L2.22988 20.6909C1.92988 20.9909 1.92988 21.4809 2.22988 21.7809C2.37988 21.9209 2.56988 22.0009 2.76988 22.0009C2.96988 22.0009 3.15988 21.9209 3.30988 21.7709L21.7699 3.31086C22.0799 3.01086 22.0799 2.53086 21.7699 2.23086Z" fill="white" style="fill: var(--fillg);"/>
  </g>
  <defs><clipPath id="clip0_4418_4643"><rect width="24" height="24" fill="white"/></clipPath></defs>
</svg>`;

function toggleFieldPw(id, el) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    el.innerHTML = fieldEyeOffSVG;
  } else {
    input.type = 'password';
    el.innerHTML = fieldEyeOpenSVG;
  }
}
</script>
</body>
</html>