<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Palihug i-type ang email ug password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Sayop ang email o password.';
        } elseif (!$user['email_verified']) {
            $_SESSION['pending_user_id'] = $user['user_id'];
            $_SESSION['pending_email'] = $user['email'];
            header('Location: verify-email.php');
            exit;
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['identity_verified'] = $user['identity_verified'];

            $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
                ->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

            if (!$user['identity_verified']) {
                header('Location: ../identity-verification/scan-id.php');
            } else {
                header('Location: ../dashboard/dashboard.php');
            }
            exit;
        }
    }
}
?>