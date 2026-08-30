<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/logo.php';

$registerError = '';

// ---------- Kinahanglan naa nay na-verify nga MCRO record gikan sa Search Full Name ----------
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
$hasMatchedRecord = !empty($_SESSION['matched_record_id']);
$hasScanVerified = !empty($_SESSION['identity_scan_verified']);
$suggestedName = $_GET['name'] ?? ($_SESSION['search_full_name'] ?? '');

// Kung naa nay na-verify nga ngalan pero wala pa na-scan ang ID, ipadala una
// sa Scan ID step (dili diretso motugot og Register).
if ($hasMatchedRecord && !$hasScanVerified && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../identity-verification/scan-id.php');
    exit;
}

// ---------- HANDLE REGISTER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['matched_record_id'])) {
        $registerError = 'Kinahanglan una nga i-search ang imong full name sa MCRO records.';
    } elseif (empty($_SESSION['identity_scan_verified'])) {
        $registerError = 'Kinahanglan una nga makompleto ang Scan ID verification.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '') {
            $registerError = 'Palihug pun-a ang tanan nga fields.';
        } elseif ($password !== $confirm) {
            $registerError = 'Dili magtugma ang password ug confirm password.';
        } elseif (strlen($password) < 8) {
            $registerError = 'Ang password dapat at least 8 characters.';
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $registerError = 'Naay account na nga naka-register ani nga email.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $matchedRecordId = $_SESSION['matched_record_id'];

                // identity_verified = 1 dayon kay na-human na ang Scan ID
                // step una pa ni nga Register (Search Name -> Scan ID -> Register).
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, status, email_verified, identity_verified, matched_record_id) VALUES (?, ?, ?, 'pending', 0, 1, ?)");
                $stmt->execute([$fullName, $email, $hash, $matchedRecordId]);
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
                    $_SESSION['dev_otp_preview'] = $otp;
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
    flex:1;justify-content:center;
    overflow:hidden;
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
</style>