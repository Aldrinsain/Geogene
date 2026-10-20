<?php
require_once __DIR__ . '/includes/admin-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$admin = $stmt->fetch();

$pageTitle = 'Admin Profile';
$activeAdminNav = 'profile';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="panel" style="max-width:640px;">
  <div style="display:flex;align-items:center;gap:18px;margin-bottom:22px;">
    <div style="width:70px;height:70px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;overflow:hidden;border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);">
      <?php if (!empty($admin['profile_photo'])): ?>
        <img src="../<?= htmlspecialchars($admin['profile_photo']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
      <?php else: ?>
        <?= strtoupper(substr($admin['full_name'], 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-size:17px;font-weight:800;"><?= htmlspecialchars($admin['full_name']) ?></div>
      <span class="badge green">System Admin</span>
    </div>
  </div>

  <table class="data-table" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
    <tbody>
      <tr><td style="color:var(--text-secondary);width:180px;">Role</td><td>Admin</td></tr>
      <tr><td style="color:var(--text-secondary);">Email Address</td><td><?= htmlspecialchars($admin['email']) ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Contact Number</td><td><?= htmlspecialchars($admin['phone_number'] ?: 'Not set') ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Address</td><td><?= htmlspecialchars($admin['address'] ?? '') ?: 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Gender</td><td><?= !empty($admin['gender']) ? ucwords(str_replace('_', ' ', $admin['gender'])) : 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Civil Status</td><td><?= !empty($admin['civil_status']) ? ucfirst($admin['civil_status']) : 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Account Status</td><td><span class="badge green"><?= ucfirst($admin['status']) ?></span></td></tr>
      <tr><td style="color:var(--text-secondary);">Admin Since</td><td><?= date('F j, Y', strtotime($admin['created_at'])) ?></td></tr>
    </tbody>
  </table>

  <a href="edit-profile.php" class="btn btn-primary" style="margin-top:20px;">Edit Profile</a>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
