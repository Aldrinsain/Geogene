<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$recordId = (int) ($_GET['id'] ?? 0);
$record = [
    'registry_type' => 'birth', 'registry_number' => '', 'first_name' => '', 'middle_name' => '',
    'last_name' => '', 'suffix' => '', 'sex' => 'male', 'civil_status' => '', 'date_of_event' => '',
    'place_of_event' => '', 'father_name' => '', 'mother_name' => '', 'spouse_name' => '',
    'barangay' => '', 'remarks' => ''
];
$isEdit = false;

if ($recordId) {
    $stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
    $stmt->execute([$recordId]);
    $found = $stmt->fetch();
    if ($found) { $record = $found; $isEdit = true; }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['middle_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '') . ' ' . ($_POST['suffix'] ?? ''));
    $fullName = preg_replace('/\s+/', ' ', $fullName);

    $data = [
        $_POST['registry_type'], $_POST['registry_number'] ?: null, $fullName ?: null, $_POST['first_name'], $_POST['middle_name'] ?: null,
        $_POST['last_name'], $_POST['suffix'] ?: null, $_POST['sex'] ?: null, $_POST['civil_status'] ?: null,
        $_POST['date_of_event'] ?: null, $_POST['place_of_event'] ?: null, $_POST['father_name'] ?: null,
        $_POST['mother_name'] ?: null, $record['spouse_name'] ?: null, $_POST['barangay'] ?: null,
        $record['remarks'] ?: null
    ];

    if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
        $error = 'First Name and Last Name are required.';
        $record = $_POST;
    } else {
        if ($isEdit) {
            $stmt = $pdo->prepare("
                UPDATE civil_registry_records SET registry_type=?, registry_number=?, full_name=?, first_name=?, middle_name=?,
                last_name=?, suffix=?, sex=?, civil_status=?, date_of_event=?, place_of_event=?, father_name=?,
                mother_name=?, spouse_name=?, barangay=?, remarks=? WHERE record_id = ?
            ");
            $stmt->execute(array_merge($data, [$recordId]));
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO civil_registry_records (registry_type, registry_number, full_name, first_name, middle_name,
                last_name, suffix, sex, civil_status, date_of_event, place_of_event, father_name, mother_name,
                spouse_name, barangay, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($data);
        }
        header('Location: civil-registry.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Civil Registry Record' : 'Add Civil Registry Record';
$pageSub = 'Fill in the official MCRO civil registry details.';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>

<div class="panel">
  <?php if ($error): ?><div class="error-text"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST">
    <div class="form-grid-2" style="background:var(--bg-page);border:1px solid var(--border);border-radius:9px;padding:12px 14px;margin-bottom:16px;">
      <div class="form-row">
        <label>Registry Type *</label>
        <select name="registry_type" required>
          <option value="birth" <?= $record['registry_type'] === 'birth' ? 'selected' : '' ?>>Birth</option>
          <option value="marriage" <?= $record['registry_type'] === 'marriage' ? 'selected' : '' ?>>Marriage</option>
          <option value="death" <?= $record['registry_type'] === 'death' ? 'selected' : '' ?>>Death</option>
        </select>
      </div>
      <div class="form-row">
        <label>Registry Number</label>
        <input type="text" name="registry_number" value="<?= htmlspecialchars($record['registry_number'] ?? '') ?>" placeholder="ex. BR-2026-00123">
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-row"><label>Last Name *</label><input type="text" name="last_name" required value="<?= htmlspecialchars($record['last_name'] ?? '') ?>"></div>
      <div class="form-row"><label>First Name *</label><input type="text" name="first_name" required value="<?= htmlspecialchars($record['first_name'] ?? '') ?>"></div>
    </div>
    <div class="form-grid-2">
      <div class="form-row"><label>Middle Name</label><input type="text" name="middle_name" value="<?= htmlspecialchars($record['middle_name'] ?? '') ?>"></div>
      <div class="form-row"><label>Suffix</label><input type="text" name="suffix" placeholder="Jr., Sr., III" value="<?= htmlspecialchars($record['suffix'] ?? '') ?>"></div>
    </div>

    <div class="form-grid-2">
      <div class="form-row">
        <label>Gender</label>
        <select name="sex">
          <option value="male" <?= ($record['sex'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= ($record['sex'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
        </select>
      </div>
      <div class="form-row">
        <label>Civil Status</label>
        <select name="civil_status">
          <option value="">-- Select --</option>
          <?php foreach (['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($record['civil_status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-row"><label>Street</label><input type="text" name="place_of_event" value="<?= htmlspecialchars($record['place_of_event'] ?? '') ?>"></div>
      <div class="form-row"><label>Barangay</label><input type="text" name="barangay" value="<?= htmlspecialchars($record['barangay'] ?? '') ?>"></div>
    </div>

    <div class="form-row"><label>Date of Birth</label><input type="date" name="date_of_event" value="<?= htmlspecialchars($record['date_of_event'] ?? '') ?>"></div>

    <div class="form-grid-2">
      <div class="form-row"><label>Father's Name</label><input type="text" name="father_name" value="<?= htmlspecialchars($record['father_name'] ?? '') ?>"></div>
      <div class="form-row"><label>Mother's Name</label><input type="text" name="mother_name" value="<?= htmlspecialchars($record['mother_name'] ?? '') ?>"></div>
    </div>

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Record' ?></button>
      <a href="civil-registry.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>