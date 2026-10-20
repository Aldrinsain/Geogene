<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google-oauth.php';

if (isset($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('office_head','admin')");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if (!$staff || !password_verify($password, $staff['password_hash'])) {
            $error = 'Incorrect email or password, or this account is not authorized as staff.';
        } else {
            $_SESSION['staff_id'] = $staff['user_id'];
            $_SESSION['staff_name'] = $staff['full_name'];
            $_SESSION['staff_role'] = $staff['role'];

            $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
                ->execute([$staff['user_id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

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
<title>GeoGene Staff - Login</title>
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

  .login-title{text-align:center;font-size:17px;font-weight:800;color:#fff;text-shadow:0 1px 6px rgba(0,0,0,0.25);}
  .login-sub{text-align:center;font-size:12px;color:rgba(255,255,255,0.8);margin:4px 0 22px;}

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

  /* ---- OR divider ---- */
  .divider{
    display:flex;align-items:center;gap:10px;
    margin:22px 0 16px;
  }
  .divider::before,.divider::after{
    content:'';flex:1;height:1px;
    background:rgba(255,255,255,0.3);
  }
  .divider span{
    font-size:11px;font-weight:600;
    color:rgba(255,255,255,0.7);
    letter-spacing:.5px;
  }

  .google-btn{
    width:100%;
    display:flex;align-items:center;justify-content:center;gap:8px;
    background:rgba(255,255,255,0.9);
    color:#2b2b2b;border:1px solid rgba(255,255,255,0.6);
    border-radius:9px;padding:11px;
    font-size:13.5px;font-weight:600;cursor:pointer;
    transition:background .2s ease;
  }
  .google-btn:hover{background:#fff;}
  .google-btn svg{width:17px;height:17px;}
</style>
<script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body<?= $error ? ' class="has-alert"' : '' ?>>

<div class="brand">
  <div class="brand-sub">Civil Registry Office &middot; Manolo Fortich</div>
  <div class="brand-title">GeoGene Staff Portal</div>
</div>

<div class="login-card">
  <div class="login-logo">
    <img src="../assets/images/geogene-logo.png" alt="GeoGene Logo" class="logo-img">
  </div>

  <form method="POST" action="login.php">
    <div class="form-row">
      <label>Staff Email</label>
      <input type="email" name="email" placeholder="staff@geogene.gov.ph" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
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
    <button type="submit" class="login-btn">Login</button>
  </form>

  <div class="divider"><span>OR</span></div>

  <label style="display:block;font-size:12px;font-weight:600;color:rgba(255,255,255,0.9);margin-bottom:8px;">Institutional Email</label>
  <button type="button" class="google-btn" id="googleSignIn">
    <svg viewBox="0 0 48 48">
      <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.6 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/>
      <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
      <path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.3-5.1l-6.6-5.6C29.6 35.4 26.9 36 24 36c-5.3 0-9.7-3.4-11.3-8.1l-6.6 5.1C9.6 39.6 16.2 44 24 44z"/>
      <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.7l6.6 5.6C41.4 36 44 30.5 44 24c0-1.3-.1-2.7-.4-3.5z"/>
    </svg>
    Sign in with Google
  </button>
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

  /* ---------- Sign in with Google ---------- */
  function showGoogleError(message) {
    let box = document.getElementById('googleErrorMsg');
    if (!box) {
      box = document.createElement('p');
      box.id = 'googleErrorMsg';
      box.style.cssText = 'color:#ffd6d6;font-size:11.5px;text-align:center;margin-top:10px;';
      document.getElementById('googleSignIn').insertAdjacentElement('afterend', box);
    }
    box.textContent = message;
  }

  const googleClientId = <?= json_encode(GOOGLE_CLIENT_ID) ?>;
  const googleBtn = document.getElementById('googleSignIn');

  if (!googleClientId || googleClientId.indexOf('PASTE-YOUR-CLIENT-ID') !== -1) {
    // Not configured yet - keep the button visible but explain what's missing
    // instead of silently doing nothing.
    googleBtn.addEventListener('click', () => {
      showGoogleError('Google Sign-In is not set up yet. See config/google-oauth.php.');
    });
  } else {
    window.addEventListener('load', () => {
      if (typeof google === 'undefined' || !google.accounts || !google.accounts.oauth2) {
        showGoogleError('Could not load Google Sign-In. Check your internet connection and reload.');
        return;
      }

      const tokenClient = google.accounts.oauth2.initTokenClient({
        client_id: googleClientId,
        scope: 'openid email profile',
        callback: (tokenResponse) => {
          if (!tokenResponse || !tokenResponse.access_token) {
            showGoogleError('Google sign-in was cancelled or failed. Please try again.');
            return;
          }

          googleBtn.disabled = true;
          const originalHtml = googleBtn.innerHTML;
          googleBtn.innerHTML = 'Signing in...';

          fetch('google-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ access_token: tokenResponse.access_token }),
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                window.location.href = data.redirect || 'dashboard.php';
              } else {
                showGoogleError(data.message || 'Sign-in failed. Please try again.');
                googleBtn.disabled = false;
                googleBtn.innerHTML = originalHtml;
              }
            })
            .catch(() => {
              showGoogleError('Unable to reach the server. Please try again.');
              googleBtn.disabled = false;
              googleBtn.innerHTML = originalHtml;
            });
        },
      });

      googleBtn.addEventListener('click', () => tokenClient.requestAccessToken());
    });
  }
</script>

</body>
</html>