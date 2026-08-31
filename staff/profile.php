<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();

$pageTitle = 'Staff Profile';
$activeStaffNav = 'profile';
require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="panel" style="max-width:640px;">
  <div style="display:flex;align-items:center;gap:18px;margin-bottom:22px;">
    <div style="width:70px;height:70px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;overflow:hidden;border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,0.1);">
      <?php if (!empty($staff['profile_photo'])): ?>
        <img src="../<?= htmlspecialchars($staff['profile_photo']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
      <?php else: ?>
        <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-size:17px;font-weight:800;"><?= htmlspecialchars($staff['full_name']) ?></div>
      <span class="badge green">Civil Registry Staff</span>
    </div>
  </div>

  <table class="data-table" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
    <tbody>
      <tr><td style="color:var(--text-secondary);width:180px;">Role</td><td><?= $staff['role'] === 'office_head' ? 'Staff' : ucwords(str_replace('_', ' ', $staff['role'])) ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Email Address</td><td><?= htmlspecialchars($staff['email']) ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Contact Number</td><td><?= htmlspecialchars($staff['phone_number'] ?: 'Not set') ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Address</td><td><?= htmlspecialchars($staff['address'] ?? '') ?: 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Gender</td><td><?= !empty($staff['gender']) ? ucwords(str_replace('_', ' ', $staff['gender'])) : 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Civil Status</td><td><?= !empty($staff['civil_status']) ? ucfirst($staff['civil_status']) : 'Not set' ?></td></tr>
      <tr><td style="color:var(--text-secondary);">Account Status</td><td><span class="badge green"><?= ucfirst($staff['status']) ?></span></td></tr>
      <tr><td style="color:var(--text-secondary);">Staff Since</td><td><?= date('F j, Y', strtotime($staff['created_at'])) ?></td></tr>
    </tbody>
  </table>

  <a href="edit-profile.php" class="btn btn-primary" style="margin-top:20px;">Edit Profile</a>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>