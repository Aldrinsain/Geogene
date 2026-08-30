<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// I-require nga naa nay na-verify nga MCRO record gikan sa Search Full Name step
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
if (empty($_SESSION['matched_record_id'])) {
    header('Location: ../identity-verification/search-name.php');
    exit;
}
$matchedRecordId = $_SESSION['matched_record_id'];
$suggestedName = $_GET['name'] ?? ($_SESSION['search_full_name'] ?? '');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Palihug pun-a ang tanan nga fields.';
    } elseif ($password !== $confirm) {
        $error = 'Dili magtugma ang password ug confirm password.';
    } elseif (strlen($password) < 8) {
        $error = 'Ang password dapat at least 8 characters.';
    } else {
        // Check kung naa nay account sa email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Naay account na nga naka-register ani nga email.';
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
                $_SESSION['dev_otp_preview'] = $otp;
            }

            header('Location: verify-email.php');
            exit;
        }
    }
}
?>