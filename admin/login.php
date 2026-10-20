<?php
// GeoGene - Admin login entry point. Uses its OWN session cookie
// (GEOGENE_ADMIN_SESSION_NAME, see config/session-names.php) so an Admin
// and a Civil Registry Staff member can be logged in at the same time, in
// the same browser, without either login affecting the other.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/account-status.php';

session_name(GEOGENE_ADMIN_SESSION_NAME);
session_start();

if (isset($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please log in again.';
}
if (isset($_GET['unauthorized'])) {
    $error = 'That account is no longer an Admin account.';
}
if (isset($_GET['disabled'])) {
    $error = GEOGENE_DISABLED_MESSAGE;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $error = 'Incorrect email or password, or this account is not authorized as Admin.';
        } elseif (geogene_account_is_disabled($admin['status'])) {
            $error = GEOGENE_DISABLED_MESSAGE;
        } else {
            session_regenerate_id(true);
            $_SESSION['staff_id'] = $admin['user_id'];
            $_SESSION['staff_name'] = $admin['full_name'];
            $_SESSION['staff_role'] = $admin['role'];

            $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
                ->execute([$admin['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

            header('Location: dashboard.php');
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
<title>GeoGene - Admin Login</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0;}
  html,body{height:100%;}
  body{
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    position:relative;
    overflow:hidden;
    padding:20px;
}
body::before{
    content:'';
    position:fixed;
    inset:0;
    background:url('../assets/images/municipal.png') center center/cover no-repeat;
    filter:blur(10px);
    transform:scale(1.1);
    z-index:-2;
}

body::after{
    content:'';
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.30);
    z-index:-1;
}


  .brand{
    text-align:center;
    margin-bottom:22px;
  }
  .brand-sub{
    font-size:13px;
    color:rgba(255,255,255,0.75);
    letter-spacing:.2px;
  }
  .brand-title{
    font-size:24px;
    font-weight:800;
    color:#fff;
    margin-top:2px;
  }

  /* ---- Glassmorphism panel ---- */
  .login-card{
    width:380px;
    background:rgba(255,255,255,0.14);
    border-radius:20px;
    padding:34px 32px 30px;
    box-shadow:0 30px 60px rgba(0,0,0,0.45);
    backdrop-filter:blur(22px) saturate(160%);
    -webkit-backdrop-filter:blur(22px) saturate(160%);
    border:1px solid rgba(255,255,255,0.35);
  }

  .login-logo{
    width:84px;height:84px;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 18px;
  }
  .login-logo img{
    width:170%;height:170%;
    object-fit:contain;
    margin-top:-30px;
    filter:drop-shadow(0 3px 6px rgba(0,0,0,0.25));
    animation:logoBreathe 4.2s ease-in-out infinite;
  }
  @keyframes logoBreathe{
    0%   { transform:scale(1); }
    50%  { transform:scale(1.02); }
    100% { transform:scale(1); }

  }

  .login-badge{
    text-align:center;font-size:11px;font-weight:800;letter-spacing:1px;
    color:#1b6e3c;background:#e8f7ee;display:inline-block;
    padding:4px 12px;border-radius:20px;margin:0 auto 14px;
  }
  .login-badge-wrap{text-align:center;}

  .form-row{margin-bottom:14px;}
  .form-row label{display:block;font-size:12px;font-weight:600;color:rgba(255,255,255,0.9);margin-bottom:6px;}

  .input-wrap{position:relative;}
  .form-row input{
    width:100%;
    border:1.5px solid rgba(255,255,255,0.4);
    border-radius:9px;
    padding:11px 13px;
    font-size:13.5px;
    outline:none;
    background:rgba(255,255,255,0.18);
    color:#fff;
    backdrop-filter:blur(4px);
    transition:border-color .2s ease, background .2s ease;
  }
  .form-row input::placeholder{color:rgba(205, 198, 198, 0.65);}
  .form-row input:focus{border-color:#a8e6bd;background:rgba(236, 236, 236, 0.67);}

  .toggle-eye{
    position:absolute;
    right:12px;top:50%;
    transform:translateY(-50%);
    background:none;border:none;cursor:pointer;
    padding:2px;
    display:flex;align-items:center;justify-content:center;
    color:rgba(0, 0, 0, 0.75);
  }
  .toggle-eye:hover{color: #b5aeae58;;}
  .toggle-eye svg{width:18px;height:18px;}

  .login-btn{
    width:100%;
    background:rgba(1, 2, 2, 0.85);
    color:#fff;border:1px solid rgba(255,255,255,0.35);
    border-radius:9px;padding:13px;
    font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;
    backdrop-filter:blur(6px);
    transition:background .2s ease;
  }
  .login-btn:hover{background:rgba(23, 194, 92, 0.95);}

  /* ---- Native-style alert modal ---- */
  .alert-overlay{
    position:fixed;inset:0;
    background:rgba(0,0,0,0.35);
    display:flex;align-items:flex-start;justify-content:center;
    padding-top:8vh;
    z-index:50;
  }
  .alert-box{
    width:380px;max-width:90vw;
    background:#fff;
    border-radius:10px;
    padding:22px 24px 20px;
    box-shadow:0 20px 45px rgba(0,0,0,0.4);
    animation:alertPop .15s ease-out;
  }
  @keyframes alertPop{
    from{ transform:scale(0.96); opacity:0; }
    to{ transform:scale(1); opacity:1; }
  }
  .alert-title{
    font-size:14px;font-weight:700;color:#1a1a1a;margin-bottom:10px;
  }
  .alert-msg{
    font-size:13px;color:#c0392b;margin-bottom:20px;
  }
  .alert-ok-wrap{
    display:flex;justify-content:flex-end;
  }
  .alert-ok{
    background:#1b6e3c;color:#fff;
    border:none;border-radius:20px;
    padding:8px 26px;
    font-size:13px;font-weight:700;
    cursor:pointer;
    transition:background .2s ease;
  }
  .alert-ok:hover{background:#155c31;}
  body.has-alert .login-card{
    filter:grayscale(0.6) brightness(0.85);
    pointer-events:none;
  }
  body.has-alert .brand{
    filter:grayscale(0.6) brightness(0.85);
  }
</style>
</head>
<body<?= $error ? ' class="has-alert"' : '' ?>>

<div class="brand">
  <div class="brand-sub">Civil Registry Office &middot; Manolo Fortich</div>
  <div class="brand-title">GeoGene</div>
</div>

<div class="login-card">
  <div class="login-logo">
    <img src="../assets/images/geogene-logo.png" alt="GeoGene Logo" class="logo-img">
  </div>

  <form method="POST" action="login.php">
    <div class="form-row">
      <label>Email</label>
      <input type="email" name="email" placeholder="admin@geogene.gov.ph" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-row">
      <label>Password</label>
      <div class="input-wrap">
        <input type="password" id="password" name="password" placeholder="Enter your password" required value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
        <button type="button" class="toggle-eye" id="togglePassword" aria-label="Show password">
          <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
      </div>
    </div>
    <p style="text-align:right;margin:-6px 0 14px;">
      <a href="forgot-password.php" style="font-size:12px;color:rgba(255,255,255,0.85);text-decoration:none;">Forgot Password?</a>
    </p>
    <button type="submit" class="login-btn">Login</button>
  </form>

  <p style="text-align:center;margin-top:16px;">
    <a href="../login.php" style="font-size:12px;color:rgba(255,255,255,0.85);text-decoration:none;">Are you Civil Registry Staff? Login here &rarr;</a>
  </p>
</div>

<?php if ($error): ?>
<div class="alert-overlay" id="alertOverlay">
  <div class="alert-box">
    <div class="alert-title"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'geogene') ?> says</div>
    <div class="alert-msg"><?= htmlspecialchars($error) ?></div>
    <div class="alert-ok-wrap">
      <button type="button" class="alert-ok" id="alertOk">OK</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
  <?php if ($error): ?>
  document.getElementById('alertOk').addEventListener('click', () => {
    document.getElementById('alertOverlay').remove();
    document.body.classList.remove('has-alert');
  });
  <?php endif; ?>

  const toggleBtn = document.getElementById('togglePassword');
  const pwdInput  = document.getElementById('password');
  const eyeIcon   = document.getElementById('eyeIcon');

  const eyeOpen  = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle>';
  const eyeClosed = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

  toggleBtn.addEventListener('click', () => {
    const isHidden = pwdInput.type === 'password';
    pwdInput.type = isHidden ? 'text' : 'password';
    eyeIcon.innerHTML = isHidden ? eyeClosed : eyeOpen;
    toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
  });
</script>

</body>
</html>
