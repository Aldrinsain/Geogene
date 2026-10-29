<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();

$success = ''; $error = '';
$pwSuccess = ''; $pwError = '';

// Check whether the address/civil_status columns exist (optional migration)
$hasAddressCol = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'address'")->rowCount();
$hasCivilStatusCol = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'civil_status'")->rowCount();

// ---------- FORM 1: Profile details (left panel) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $civilStatus = $_POST['civil_status'] ?? '';

    $emailTaken = false;
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $chk = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
        $chk->execute([$email, $_SESSION['staff_id']]);
        $emailTaken = (bool) $chk->fetchColumn();
    }

    if ($fullName === '') {
        $error = 'Full Name is required.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Please enter a valid email address.';
    } elseif ($emailTaken) {
        $error = 'That email address is already used by another account.';
    } else {
        $setParts = ['full_name = ?', 'email = ?', 'phone_number = ?'];
        $params = [$fullName, $email, $phone];

        if ($hasAddressCol) { $setParts[] = 'address = ?'; $params[] = $address; }
        if (in_array($gender, ['male', 'female', 'prefer_not_to_say'])) { $setParts[] = 'gender = ?'; $params[] = $gender; }
        if ($hasCivilStatusCol && in_array($civilStatus, ['single','married','widowed','divorced'])) {
            $setParts[] = 'civil_status = ?'; $params[] = $civilStatus;
        }

        $params[] = $_SESSION['staff_id'];
        $pdo->prepare("UPDATE users SET " . implode(', ', $setParts) . " WHERE user_id = ?")->execute($params);

        $_SESSION['staff_name'] = $fullName;
        $success = 'Your profile has been updated successfully.';

        $_SESSION['staff_email'] = $email;

        $staff['full_name'] = $fullName;
        $staff['email'] = $email;
        $staff['phone_number'] = $phone;
        if ($hasAddressCol) $staff['address'] = $address;
        $staff['gender'] = $gender;
        if ($hasCivilStatusCol) $staff['civil_status'] = $civilStatus;
    }
}

// ---------- FORM 2: Security / Password update (right panel) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'password') {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, $staff['password_hash'])) {
        $pwError = 'The current password you entered is incorrect.';
    } elseif (strlen($newPw) < 8) {
        $pwError = 'New password must be at least 8 characters long.';
    } elseif ($newPw !== $confirmPw) {
        $pwError = 'New Password and Confirm Password do not match.';
    } else {
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$hash, $_SESSION['staff_id']]);
        $pwSuccess = 'Your password has been updated successfully.';
    }
}

// ---------- Account info for right panel ----------
$lastLoginStmt = $pdo->prepare("SELECT login_at FROM login_logs WHERE user_id = ? ORDER BY login_at DESC LIMIT 1 OFFSET 1");
$lastLoginStmt->execute([$_SESSION['staff_id']]);
$lastLogin = $lastLoginStmt->fetchColumn();
if (!$lastLogin) {
    $s2 = $pdo->prepare("SELECT login_at FROM login_logs WHERE user_id = ? ORDER BY login_at DESC LIMIT 1");
    $s2->execute([$_SESSION['staff_id']]);
    $lastLogin = $s2->fetchColumn();
}

$pageTitle = 'Edit Staff Profile';
$activeStaffNav = 'profile';
require_once __DIR__ . '/includes/staff-header.php';
?>

<style>
  .profile-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;}
  @media (max-width:900px){ .profile-grid{grid-template-columns:1fr;} }
  .civil-status-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
  .civil-status-opt{
    display:flex;align-items:center;gap:8px;border:1.5px solid var(--border);border-radius:9px;
    padding:10px 12px;font-size:13px;cursor:pointer;transition:border-color .15s ease,background .15s ease;
  }
  .civil-status-opt:has(input:checked){border-color:var(--primary);background:var(--primary-light);}
  .civil-status-opt input{accent-color:var(--primary);}
  .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px;}
  .info-row:last-child{border-bottom:none;}
  .info-row .ir-label{color:var(--text-secondary);}
  .info-row .ir-value{font-weight:700;}
  .pw-field-wrap{position:relative;}
  .pw-field-wrap input{padding-right:38px;}
  .pw-eye{
    position:absolute;right:12px;top:50%;transform:translateY(-50%);
    cursor:pointer;font-size:14px;color:var(--text-secondary);user-select:none;
  }

  /* ---------- Back to Profile button ---------- */
  .back-to-profile{
    display:inline-flex;align-items:center;gap:8px;margin-bottom:16px;
    color:var(--text-secondary);text-decoration:none;font-size:13px;font-weight:600;
    transition:color .15s ease;
  }
  .back-to-profile:hover{color:var(--primary);}
  .back-to-profile .back-icon-circle{
    width:32px;height:32px;border-radius:50%;flex-shrink:0;
    background:var(--bg-card);border:1px solid var(--border);
    display:flex;align-items:center;justify-content:center;color:var(--text-main);
    transition:background .15s ease,color .15s ease;
  }
  .back-to-profile:hover .back-icon-circle{background:var(--primary-light);color:var(--primary);border-color:var(--primary);}
  .back-to-profile .back-icon-circle svg{width:17px;height:17px;}

  /* ---------- Toast popup notification ---------- */
  #toastStack{
    position:fixed;top:22px;right:22px;z-index:9999;
    display:flex;flex-direction:column;gap:10px;align-items:flex-end;
  }
  .toast{
    display:flex;align-items:flex-start;gap:12px;
    background:#fff;border-radius:12px;padding:14px 16px;
    min-width:280px;max-width:360px;
    box-shadow:0 12px 32px rgba(0,0,0,0.16);
    border-left:4px solid var(--primary);
    opacity:0;transform:translateX(30px);
    animation:toastIn .35s ease forwards;
  }
  .toast.toast-error{border-left-color:var(--danger);}
  .toast .toast-icon{
    width:28px;height:28px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;
    background:var(--primary-light);color:var(--primary);
  }
  .toast.toast-error .toast-icon{background:#fdeceb;color:var(--danger);}
  .toast .toast-body{flex:1;}
  .toast .toast-title{font-size:13px;font-weight:800;color:var(--text-main);margin-bottom:2px;}
  .toast .toast-msg{font-size:12px;color:var(--text-secondary);line-height:1.4;}
  .toast .toast-close{cursor:pointer;color:var(--text-muted);font-size:15px;line-height:1;flex-shrink:0;}
  .toast.toast-out{animation:toastOut .3s ease forwards;}
  @keyframes toastIn{ to{opacity:1;transform:translateX(0);} }
  @keyframes toastOut{ to{opacity:0;transform:translateX(30px);} }
  body.dark-mode .toast{background:var(--bg-card);box-shadow:0 12px 32px rgba(0,0,0,0.45);}
  body.dark-mode .toast .toast-close{color:var(--text-secondary);}
</style>

<div id="toastStack"></div>
<div class="profile-grid">

  <!-- ============ LEFT: Photo (untouched) + Profile Form ============ -->
  <div class="panel">
    <a href="profile.php" class="back-to-profile">
      <span class="back-icon-circle">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </span>
    </a>
    <div style="text-align:center;margin-bottom:22px;">
      <div style="position:relative;width:88px;height:88px;margin:0 auto 8px;">
        <div id="editPhotoCircle" style="width:88px;height:88px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;overflow:hidden;border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);">
          <?php if (!empty($staff['profile_photo'])): ?>
            <img id="editPhotoImg" src="../<?= htmlspecialchars($staff['profile_photo']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <span id="editPhotoInitial"><?= strtoupper(substr($staff['full_name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <button type="button" id="editPhotoCamBtn" title="Change photo" style="position:absolute;bottom:-2px;right:-2px;width:28px;height:28px;border-radius:50%;background:#d9d9d9;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid #fff;cursor:pointer;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color:#fff; class="size-6">
    <path d="M12 9a3.75 3.75 0 1 0 0 7.5A3.75 3.75 0 0 0 12 9Z" />
    <path fill-rule="evenodd" d="M9.344 3.071a49.52 49.52 0 0 1 5.312 0c.967.052 1.83.585 2.332 1.39l.821 1.317c.24.383.645.643 1.11.71.386.054.77.113 1.152.177 1.432.239 2.429 1.493 2.429 2.909V18a3 3 0 0 1-3 3h-15a3 3 0 0 1-3-3V9.574c0-1.416.997-2.67 2.429-2.909.382-.064.766-.123 1.151-.178a1.56 1.56 0 0 0 1.11-.71l.822-1.315a2.942 2.942 0 0 1 2.332-1.39ZM6.75 12.75a5.25 5.25 0 1 1 10.5 0 5.25 5.25 0 0 1-10.5 0Zm12-1.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
  </svg>
  </button>
        <input type="file" id="editPhotoInput" accept="image/jpeg,image/png,image/webp" hidden>
      </div>
      <div id="editPhotoStatus" style="font-size:12px;color:var(--text-secondary);">Click the camera icon to change your photo</div>
    </div>

    <form method="POST">
      <input type="hidden" name="form_action" value="profile">

      <div class="form-row">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($staff['full_name']) ?>" required>
      </div>
      <div class="form-row">
        <label>Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" maxlength="150" required>
      </div>
      <div class="form-row">
        <label>Contact Number</label>
        <input type="text" name="phone_number" value="<?= htmlspecialchars($staff['phone_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
      </div>
      <div class="form-row">
        <label>Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($staff['address'] ?? '') ?>" placeholder="Zone / Barangay / Municipality">
      </div>
      <div class="form-row">
        <label>Gender</label>
        <select name="gender">
          <option value="">-- Select --</option>
          <option value="male" <?= ($staff['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= ($staff['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
          <option value="prefer_not_to_say" <?= ($staff['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
        </select>
      </div>
      <div class="form-row">
        <label>Civil Status</label>
        <div class="civil-status-grid">
          <?php foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'] as $val => $label): ?>
            <label class="civil-status-opt">
              <input type="radio" name="civil_status" value="<?= $val ?>" <?= ($staff['civil_status'] ?? '') === $val ? 'checked' : '' ?>>
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:6px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="profile.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>

  <!-- ============ RIGHT: Account Info + Security ============ -->
  <div>
    <div class="panel">
      <div class="panel-header"><span class="panel-title">Account Information</span></div>
      <div class="info-row"><span class="ir-label">Role</span><span class="ir-value"><?= $staff['role'] === 'admin' ? 'System Admin' : 'Civil Registry Staff' ?></span></div>
      <div class="info-row"><span class="ir-label">Account Status</span><span class="ir-value"><span class="badge green"><?= ucfirst($staff['status']) ?></span></span></div>
      <div class="info-row"><span class="ir-label">Last Login</span><span class="ir-value"><?= $lastLogin ? date('F j, Y', strtotime($lastLogin)) : '—' ?></span></div>
      <div class="info-row"><span class="ir-label">Account Created</span><span class="ir-value"><?= date('F j, Y', strtotime($staff['created_at'])) ?></span></div>
    </div>

    <div class="panel">
      <div class="panel-header"><span class="panel-title">Security Settings</span></div>

      <form method="POST">
        <input type="hidden" name="form_action" value="password">
        <div class="form-row">
          <label>Current Password</label>
          <div class="pw-field-wrap">
            <input type="password" name="current_password" id="pwCurrent" required>
            <span class="pw-eye" onclick="togglePwField('pwCurrent', this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg></span>
          </div>
        </div>
        <div class="form-row">
          <label>New Password</label>
          <div class="pw-field-wrap">
            <input type="password" name="new_password" id="pwNew" required>
            <span class="pw-eye" onclick="togglePwField('pwNew', this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg></span>
          </div>
        </div>
        <div class="form-row">
          <label>Confirm Password</label>
          <div class="pw-field-wrap">
            <input type="password" name="confirm_password" id="pwConfirm" required>
            <span class="pw-eye" onclick="togglePwField('pwConfirm', this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg></span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Update Password</button>
      </form>
    </div>
  </div>
</div>

<script>
function togglePwField(id, el) {
  const eyeOpenSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
  </svg>`;
  const eyeClosedSVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M19.5 16L17.0248 12.6038" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
    <path d="M12 17.5V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
    <path d="M4.5 16L6.96895 12.6124" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
    <path d="M3 8C6.6 16 17.4 16 21 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
  </svg>`;

  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    el.innerHTML = eyeClosedSVG;
  } else {
    input.type = 'password';
    el.innerHTML = eyeOpenSVG;
  }
}
</script>

<script>
function showToast(title, message, type) {
  const stack = document.getElementById('toastStack');
  const toast = document.createElement('div');
  toast.className = 'toast' + (type === 'error' ? ' toast-error' : '');
  toast.innerHTML = `
    <div class="toast-icon">${type === 'error' ? '✕' : '<svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z" fill="#02d42c"></path> </g></svg>'}</div>
    <div class="toast-body">
      <div class="toast-title">${title}</div>
      <div class="toast-msg">${message}</div>
    </div>
    <span class="toast-close" onclick="dismissToast(this.parentElement)">✕</span>
  `;
  stack.appendChild(toast);
  setTimeout(() => dismissToast(toast), 4000);
}

function dismissToast(toast) {
  if (!toast || toast.classList.contains('toast-out')) return;
  toast.classList.add('toast-out');
  setTimeout(() => toast.remove(), 300);
}

<?php if ($success): ?>
showToast('Profile Updated', <?= json_encode($success) ?>, 'success');
<?php endif; ?>
<?php if ($error): ?>
showToast('Update Failed', <?= json_encode($error) ?>, 'error');
<?php endif; ?>
<?php if ($pwSuccess): ?>
showToast('Password Updated', <?= json_encode($pwSuccess) ?>, 'success');
<?php endif; ?>
<?php if ($pwError): ?>
showToast('Password Update Failed', <?= json_encode($pwError) ?>, 'error');
<?php endif; ?>
</script>

<script>
(function () {
  const camBtn   = document.getElementById('editPhotoCamBtn');
  const fileIn   = document.getElementById('editPhotoInput');
  const circle   = document.getElementById('editPhotoCircle');
  const status   = document.getElementById('editPhotoStatus');

  camBtn.addEventListener('click', () => fileIn.click());

  fileIn.addEventListener('change', () => {
    const file = fileIn.files[0];
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
      showToast('Upload Failed', 'File size exceeds 3MB limit. Please select a smaller image.', 'error');
      fileIn.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('photo', file);
    status.textContent = 'Uploading...';
    status.style.color = 'var(--text-secondary)';

    fetch('upload-profile-photo.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        status.textContent = 'Click the camera icon to change your photo';
        status.style.color = 'var(--text-secondary)';
        if (!data.success) {
          showToast('Upload Failed', data.message || 'Upload Failed.', 'error');
          return;
        }
        circle.innerHTML = '<img src="' + data.url + '?t=' + Date.now() + '" alt="Profile" style="width:100%;height:100%;object-fit:cover;">';
        showToast('Photo Updated', 'Profile photo changed successfully.', 'success');
      })
      .catch(() => {
        status.textContent = 'Click the camera icon to change your photo';
        status.style.color = 'var(--text-secondary)';
        showToast('Upload Failed', 'Unable to connect to the server. Please try again.', 'error');
      })
      .finally(() => { fileIn.value = ''; });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
