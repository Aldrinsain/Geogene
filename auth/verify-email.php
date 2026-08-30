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
        $error = 'Gi-resend na ang bag-o nga code. (Dev mode: makita sa ubos kay wala pa na-setup ang SMTP.)';
    } else {
        unset($_SESSION['dev_otp_preview']);
        $error = 'Gi-resend na ang bag-o nga code sa imong email.';
    }
}

// ---------- VERIFY OTP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = implode('', $_POST['otp'] ?? []);

    if (strlen($enteredOtp) !== 6) {
        $error = 'Palihug i-type ang tibuok 6-digit code.';
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
            $error = 'Sayop o na-expire na ang code. Palihug sulayi og usab.';
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