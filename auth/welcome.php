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