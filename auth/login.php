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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Login</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
  .title {
    text-align: center;
    margin: 0 auto 8px;
    color: #f2f4fa;
    font-weight: 800;
}

.subtitle {
    text-align: center;
    margin: 0 auto 22px;
    color: #8a93b8;
    font-size: 14px;
}
</style>
</head>
<body>

<div class="phone">
  <div class="app-header">
  </div>
  <h1 class="title">Welcome Back!</h1> 
<p class="subtitle">Login to continue</p>

  <?php if ($error): ?>
    <p class="error-text"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST" action="login.php" autocomplete="off">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-input" placeholder="you@example.com" required
             autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
  <label class="form-label">Password</label>

  <div class="input-icon-wrap">
    <input
      type="password"
      name="password"
      id="password"
      class="form-input"
      placeholder="Enter your password"
      autocomplete="new-password"
      readonly onfocus="this.removeAttribute('readonly');"
      required
    >

    <span class="input-icon" onclick="togglePw('password', this)">
      
      <!-- EYE OPEN -->
      <svg class="eye-icon"
           xmlns="http://www.w3.org/2000/svg"
           width="24"
           height="24"
           viewBox="0 0 24 24"
           fill="#b4b5b3">

        <path d="M21.25 9.14969C18.94 5.51969 15.56 3.42969 12 3.42969C10.22 3.42969 8.49 3.94969 6.91 4.91969C5.33 5.89969 3.91 7.32969 2.75 9.14969C1.75 10.7197 1.75 13.2697 2.75 14.8397C5.06 18.4797 8.44 20.5597 12 20.5597C13.78 20.5597 15.51 20.0397 17.09 19.0697C18.67 18.0897 20.09 16.6597 21.25 14.8397C22.25 13.2797 22.25 10.7197 21.25 9.14969ZM12 16.0397C9.76 16.0397 7.96 14.2297 7.96 11.9997C7.96 9.76969 9.76 7.95969 12 7.95969C14.24 7.95969 16.04 9.76969 16.04 11.9997C16.04 14.2297 14.24 16.0397 12 16.0397Z"/>

        <path d="M11.9999 9.14062C10.4299 9.14062 9.1499 10.4206 9.1499 12.0006C9.1499 13.5706 10.4299 14.8506 11.9999 14.8506C13.5699 14.8506 14.8599 13.5706 14.8599 12.0006C14.8599 10.4306 13.5699 9.14062 11.9999 9.14062Z"/>
      </svg>

    </span>
  </div>
</div>

    <p style="text-align:right;margin:-6px 0 18px;">
      <a href="forgot-password.php" style="font-size:12.5px;color:var(--primary);text-decoration:none;">Forgot Password?</a>
    </p>

    <button type="submit" class="btn-primary">Login</button>
  </form>

  <div class="divider">OR</div>
  <button type="button" class="btn-google" onclick="alert('Google login placeholder - i-configure kung gusto gamiton.')">
    <span class="google-icon">
    <svg width="20" height="20" viewBox="0 0 24 24">
      <path fill="#4285F4"
        d="M21.35 12.27c0-.71-.06-1.4-.18-2.05H12v3.88h5.24a4.48 4.48 0 0 1-1.94 2.94v2.44h3.14c1.84-1.69 2.91-4.18 2.91-7.21z"/>
      <path fill="#34A853"
        d="M12 21.82c2.63 0 4.84-.87 6.45-2.35l-3.14-2.44c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.29v2.52A9.74 9.74 0 0 0 12 21.82z"/>
      <path fill="#FBBC05"
        d="M6.54 13.92a5.85 5.85 0 0 1 0-3.74V7.66H3.29a9.77 9.77 0 0 0 0 8.78l3.25-2.52z"/>
      <path fill="#EA4335"
        d="M12 6.15c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.19 14.63 2.18 12 2.18a9.74 9.74 0 0 0-8.71 5.48l3.25 2.52C7.31 7.87 9.46 6.15 12 6.15z"/>
    </svg>
  </span> Continue with Google
  </button>

  <p class="footer-text">Don't have an account? <a href="../identity-verification/search-name.php">Create an Account</a></p>
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