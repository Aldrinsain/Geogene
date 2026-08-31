<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $error = 'Sayop ang current password.';
    } elseif (strlen($new) < 8) {
        $error = 'Ang bag-o nga password dapat at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'Dili magtugma ang bag-o nga password ug confirm password.';
    } else {
        $newHash = password_hash($new, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$newHash, $userId]);
        $success = 'Na-update na ang imong password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Change Password</title>
<link rel="stylesheet" href="../assets/css/main.css">
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="app-header">
    <a href="settings.php" class="back-arrow">&larr;</a>
    <span>GeoGene</span>
  </div>

  <h1 class="title">Change Password</h1>

  <?php if ($error): ?><p class="error-text"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="success-text"><?= htmlspecialchars($success) ?></p><?php endif; ?>

  <form method="POST" action="change-password.php">
    <div class="form-group">
      <label class="form-label">Current Password</label>
      <input type="password" name="current_password" class="form-input" required>
    </div>
    <div class="form-group">
      <label class="form-label">New Password</label>
      <input type="password" name="new_password" class="form-input" required>
    </div>
    <div class="form-group">
      <label class="form-label">Confirm New Password</label>
      <input type="password" name="confirm_password" class="form-input" required>
    </div>
    <button type="submit" class="btn-primary">Update Password</button>
  </form>
</div>

</body>
</html>
