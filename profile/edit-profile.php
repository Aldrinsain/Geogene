<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("
    SELECT u.*, cr.date_of_event AS birth_date, cr.sex, cr.barangay AS registry_barangay
    FROM users u
    LEFT JOIN civil_registry_records cr ON cr.record_id = u.matched_record_id
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$barangays = $pdo->query("SELECT name FROM barangays ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone_number'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $gender      = $_POST['gender'] ?? '';
    $civilStatus = $_POST['civil_status'] ?? '';
    $barangay    = $_POST['barangay'] ?? '';

    $validGenders = ['male', 'female', 'prefer_not_to_say'];
    $validCivilStatuses = ['single', 'married', 'widowed', 'divorced'];
    if (!in_array($gender, $validGenders, true)) $gender = null;
    if (!in_array($civilStatus, $validCivilStatuses, true)) $civilStatus = null;
    if ($dateOfBirth === '') $dateOfBirth = null;

    if ($fullName === '') {
        $error = 'Need Full Name.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Palihug ibutang ang husto nga Email Address.';
    } else {
        // Make sure the new email isn't already used by another account.
        $dupCheck = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $dupCheck->execute([$email, $userId]);
        if ($dupCheck->fetch()) {
            $error = 'Gigamit na ni ibang account ang email nga ini.';
        } else {
            $emailChanged = strcasecmp($email, $user['email']) !== 0;
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, phone_number = ?, date_of_birth = ?, gender = ?, civil_status = ?
                    " . ($emailChanged ? ", email_verified = 0" : "") . "
                WHERE user_id = ?
            ");
            $stmt->execute([$fullName, $email, $phone, $dateOfBirth, $gender, $civilStatus, $userId]);
            $_SESSION['full_name'] = $fullName;
            $success = 'Na-update na ang imong profile.';
            $user['full_name'] = $fullName;
            $user['email'] = $email;
            $user['phone_number'] = $phone;
            $user['date_of_birth'] = $dateOfBirth;
            $user['gender'] = $gender;
            $user['civil_status'] = $civilStatus;
            $user['registry_barangay'] = $barangay;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Edit Profile</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
  .content-scroll{padding-bottom:30px;}
  .photo-wrap{text-align:center;margin:10px 0 24px;}
  .photo-circle-wrap{position:relative;width:84px;height:84px;margin:0 auto 8px;}
  .photo-circle{
    width:84px;height:84px;border-radius:50%;
    background:linear-gradient(135deg, rgba(57,255,136,0.28), rgba(57,255,136,0.08));color:var(--green);
    display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;
    border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);overflow:hidden;
  }
  .photo-circle img{width:100%;height:100%;object-fit:cover;}
  .photo-cam{
    position:absolute;bottom:-2px;right:-2px;width:26px;height:26px;border-radius:50%;
    background:var(--primary);color:#0B132B;display:flex;align-items:center;justify-content:center;
    font-size:12px;border:2px solid #fff;cursor:pointer;z-index:2;
  }
  .photo-cam svg{width:13px;height:13px;}
  .change-photo-label{font-size:12.5px;color:var(--green);font-weight:600;cursor:pointer;}

  .form-label-icon{display:flex;align-items:center;gap:6px;}
  .input-with-icon{position:relative;}
  .input-with-icon .field-icon{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;
    display:flex;align-items:center;justify-content:center;width:16px;height:16px;line-height:0;
  }
  .input-with-icon .field-icon svg{width:16px !important;height:16px !important;display:block;}
  .input-with-icon .form-input{padding-left:34px;}

  .gender-row{display:flex;gap:18px;margin-top:6px;}
  .gender-opt{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-main);}
  .gender-opt input{accent-color:var(--green);}

  .addr-group{display:flex;flex-direction:column;gap:12px;}
  .btn-row{display:flex;gap:10px;margin-top:8px;}
  .btn-row .btn-primary,.btn-row .btn-outline{margin-top:0;}
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="content-scroll">
    <div class="app-header">
      <a href="profile.php" class="back-arrow">&larr;</a>
      <span>My Profile</span>
    </div>

    <div class="photo-wrap">
      <div class="photo-circle-wrap">
        <div class="photo-circle" id="editPhotoCircle">
          <?php if (!empty($user['profile_photo'])): ?>
            <img id="editPhotoImg" src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo">
          <?php else: ?>
            <span id="editPhotoInitial"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <span class="photo-cam" id="editPhotoCamBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
        </span>
      </div>
      <span class="change-photo-label" id="editPhotoLabel">Change Photo</span>
      <div class="avatar-upload-status" id="editPhotoStatus" style="font-size:11.5px;color:var(--text-muted);min-height:14px;margin-top:4px;"></div>
      <input type="file" id="editPhotoInput" accept="image/*" hidden>
    </div>

    <?php if ($error): ?><p class="error-text"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success-text"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="POST" action="edit-profile.php">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <div class="input-with-icon">
          <span class="field-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
  <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
</svg>
</span>
          <input type="text" name="full_name" class="form-input" placeholder="Enter full name"
                 value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-with-icon">
          <span class="field-icon">✉</span>
          <input type="email" name="email" class="form-input" placeholder="Enter email address"
                 value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Contact Number *</label>
        <div class="input-with-icon">
          <span class="field-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
  <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5Z" clip-rule="evenodd" />
</svg>
</span>
          <input type="text" name="phone_number" class="form-input" placeholder="Enter contact number"
                 value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <div class="input-with-icon">
          <span class="field-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
  <path d="M5.75 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM7.25 8.25A.75.75 0 0 1 8 7.5h2.25a.75.75 0 0 1 0 1.5H8a.75.75 0 0 1-.75-.75ZM5.75 9.5a.75.75 0 0 0 0 1.5H8a.75.75 0 0 0 0-1.5H5.75Z" />
  <path fill-rule="evenodd" d="M4.75 1a.75.75 0 0 0-.75.75V3a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2V1.75a.75.75 0 0 0-1.5 0V3h-5V1.75A.75.75 0 0 0 4.75 1ZM3.5 7a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1V7Z" clip-rule="evenodd" />
</svg>
</span>
          <?php $dobValue = $user['date_of_birth'] ?: $user['birth_date']; ?>
          <input type="date" name="date_of_birth" class="form-input"
                 value="<?= $dobValue ? date('Y-m-d', strtotime($dobValue)) : '' ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Gender</label>
        <div class="gender-row">
          <label class="gender-opt"><input type="radio" name="gender" value="male" <?= ($user['gender'] ?? $user['sex'] ?? '') === 'male' ? 'checked' : '' ?>> Male</label>
          <label class="gender-opt"><input type="radio" name="gender" value="female" <?= ($user['gender'] ?? $user['sex'] ?? '') === 'female' ? 'checked' : '' ?>> Female</label>
          <label class="gender-opt"><input type="radio" name="gender" value="prefer_not_to_say" <?= empty($user['gender']) && empty($user['sex']) ? 'checked' : '' ?>> Prefer not to say</label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Civil Status</label>
        <select name="civil_status" class="form-input">
          <option value="">-- Select --</option>
          <?php foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($user['civil_status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Address</label>
        <div class="addr-group">
          <select class="form-input" disabled style="background:rgba(255,255,255,0.03);color:var(--text-muted);">
            <option>Bukidnon</option>
          </select>
          <select class="form-input" disabled style="background:rgba(255,255,255,0.03);color:var(--text-muted);">
            <option>Manolo Fortich</option>
          </select>
          <select name="barangay" class="form-input">
            <?php foreach ($barangays as $b): ?>
              <option value="<?= htmlspecialchars($b['name']) ?>" <?= ($user['registry_barangay'] ?? '') === $b['name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="btn-row">
        <a href="profile.php" class="btn-outline" style="text-decoration:none;text-align:center;">Cancel</a>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const camBtn = document.getElementById('editPhotoCamBtn');
  const label  = document.getElementById('editPhotoLabel');
  const fileIn = document.getElementById('editPhotoInput');
  const circle = document.getElementById('editPhotoCircle');
  const status = document.getElementById('editPhotoStatus');

  const openPicker = () => fileIn.click();
  camBtn.addEventListener('click', openPicker);
  label.addEventListener('click', openPicker);

  fileIn.addEventListener('change', () => {
    const file = fileIn.files[0];
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
      status.textContent = 'File is too big. Max 3MB.';
      status.style.color = 'var(--danger)';
      fileIn.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('photo', file);
    status.textContent = 'Uploading...';
    status.style.color = 'var(--text-secondary)';

    fetch('upload-photo.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          status.textContent = data.message || 'Upload failed. Please try again.';
          status.style.color = 'var(--danger)';
          return;
        }
        circle.innerHTML = '<img id="editPhotoImg" src="' + data.url + '?t=' + Date.now() + '" alt="Profile photo">';
        status.textContent = 'Photo updated.';
        status.style.color = 'var(--green)';
        setTimeout(() => { status.textContent = ''; }, 2500);
      })
      .catch(() => {
        status.textContent = 'Unable to connect to the server. Please try again.';
        status.style.color = 'var(--danger)';
      })
      .finally(() => { fileIn.value = ''; });
  });
})();
</script>

</body>
</html>
