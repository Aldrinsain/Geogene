<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$recordId = (int) ($_GET['id'] ?? 0);

if ($recordId <= 0) {
    header('Location: civil-registry.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
$stmt->execute([$recordId]);
$row = $stmt->fetch();

if ($row === false || empty($row)) {
    header('Location: civil-registry.php');
    exit;
}

/**
 * Safely reads a field from the fetched row no matter what columns
 * actually exist in the live database right now. Using this everywhere
 * below guarantees this page can NEVER throw an "undefined array key"
 * warning again, even if a column was renamed, dropped, or never added.
 */
function field($row, $key, $default = '')
{
    return array_key_exists($key, $row) && $row[$key] !== null ? $row[$key] : $default;
}

$registryType = field($row, 'registry_type', 'birth');

$typeLabels = [
    'birth'    => ['badge' => 'green', 'dateLabel' => 'Date of Birth', 'placeLabel' => 'Place of Birth'],
    'marriage' => ['badge' => 'blue',  'dateLabel' => 'Date of Marriage', 'placeLabel' => 'Place of Marriage'],
    'death'    => ['badge' => 'gray',  'dateLabel' => 'Date of Death', 'placeLabel' => 'Place of Death'],
];
$typeInfo = $typeLabels[$registryType] ?? $typeLabels['birth'];

$fullName = field($row, 'full_name') ?: trim(field($row, 'first_name') . ' ' . field($row, 'middle_name') . ' ' . field($row, 'last_name') . ' ' . field($row, 'suffix'));
$fullName = $fullName !== '' ? $fullName : 'Unnamed Record';

$dateOfEvent = field($row, 'date_of_event');
$dateOfEventDisplay = $dateOfEvent ? date('F j, Y', strtotime($dateOfEvent)) : '—';

$createdAt = field($row, 'created_at');
$createdAtDisplay = $createdAt ? date('F j, Y', strtotime($createdAt)) : '—';

$pageTitle = 'View Civil Registry Record';
$pageSub = 'Complete details for this MCRO civil registry record.';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>

<style>
  @media print {
    .sidebar, .topbar, .back-to-profile, .cr-view-actions, #toastStack { display:none !important; }
    body, .main-content, .content-area { background:#fff !important; margin:0 !important; padding:0 !important; }
    .cr-doc { box-shadow:none !important; border:none !important; margin:0 !important; max-width:100% !important; }
  }

  .cr-doc-wrap{display:flex;justify-content:center;padding:10px 0 40px;}
  .cr-doc{
    background:#fff;color:#1a1a1a;width:100%;max-width:816px; /* ~8.5in at 96dpi */
    min-height:1056px; /* ~11in */
    box-shadow:0 8px 30px rgba(0,0,0,0.15);border:1px solid #e2e2e2;
    padding:56px 64px;box-sizing:border-box;font-family:Georgia,'Times New Roman',serif;
  }
  .cr-doc-letterhead{display:flex;align-items:center;gap:14px;border-bottom:3px double #1a1a1a;padding-bottom:14px;margin-bottom:22px;}
  .cr-doc-letterhead img{width:52px;height:52px;object-fit:contain;}
  .cr-doc-letterhead .lh-title{font-size:13px;letter-spacing:.04em;color:#444;text-transform:uppercase;}
  .cr-doc-letterhead .lh-office{font-size:19px;font-weight:700;color:#111;margin-top:2px;}
  .cr-doc-letterhead .lh-sub{font-size:11.5px;color:#666;margin-top:2px;}

  .cr-doc-cert-title{text-align:center;margin:6px 0 26px;}
  .cr-doc-cert-title h1{font-size:20px;letter-spacing:.03em;text-transform:uppercase;margin:0 0 4px;}
  .cr-doc-cert-title .cr-doc-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:4px 12px;border:1px solid #1a1a1a;border-radius:20px;}

  .cr-doc-section{margin-bottom:20px;}
  .cr-doc-section-title{font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#333;border-bottom:1px solid #ccc;padding-bottom:5px;margin-bottom:10px;}
  .cr-doc-grid{display:grid;grid-template-columns:1fr 1fr;column-gap:28px;row-gap:10px;}
  .cr-doc-field .f-label{font-size:10.5px;color:#777;text-transform:uppercase;letter-spacing:.03em;}
  .cr-doc-field .f-value{font-size:14.5px;font-weight:600;color:#151515;margin-top:2px;min-height:18px;}
  .cr-doc-field.full{grid-column:1 / -1;}

  .cr-doc-footer{margin-top:44px;padding-top:14px;border-top:1px solid #ccc;display:flex;justify-content:space-between;font-size:10.5px;color:#888;}

  .cr-view-actions{display:flex;gap:10px;justify-content:center;margin:18px 0 4px;}
</style>

<a href="civil-registry.php" class="back-to-profile" style="display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;color:var(--text-secondary);text-decoration:none;font-size:13px;font-weight:600;">
  <span style="width:32px;height:32px;border-radius:50%;background:var(--bg-card);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
  </span>
  Back to Civil Registry Records
</a>

<div class="cr-view-actions">
  <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Print Record</button>
</div>

<div class="cr-doc-wrap">
  <div class="cr-doc" id="printArea">

    <div class="cr-doc-letterhead">
      <img src="../assets/images/geogene-logo.png" alt="GeoGene">
      <div>
        <div class="lh-title">Republic of the Philippines &nbsp;·&nbsp; Municipality of Manolo Fortich, Bukidnon</div>
        <div class="lh-office">Municipal Civil Registrar's Office (MCRO)</div>
        <div class="lh-sub">GeoGene Civil Registry Management System</div>
      </div>
    </div>

    <div class="cr-doc-cert-title">
      <h1><?= htmlspecialchars($fullName) ?></h1>
      <span class="cr-doc-badge"><?= htmlspecialchars(ucfirst($registryType)) ?> Record</span>
    </div>

    <div class="cr-doc-section">
      <div class="cr-doc-section-title">Registry Details</div>
      <div class="cr-doc-grid">
        <div class="cr-doc-field"><div class="f-label">Book No.</div><div class="f-value"><?= htmlspecialchars(field($row, 'book_no', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Page No.</div><div class="f-value"><?= htmlspecialchars(field($row, 'page_no', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">City/Municipality</div><div class="f-value"><?= htmlspecialchars(field($row, 'city_municipality', '—')) ?></div></div>
        <?php $datePrepared = field($row, 'date_prepared'); ?>
        <div class="cr-doc-field"><div class="f-label">Date Prepared</div><div class="f-value"><?= $datePrepared ? date('F j, Y', strtotime($datePrepared)) : '—' ?></div></div>
      </div>
    </div>

    <div class="cr-doc-section">
      <div class="cr-doc-section-title">Personal Information</div>
      <div class="cr-doc-grid">
        <div class="cr-doc-field"><div class="f-label">Last Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'last_name', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">First Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'first_name', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Middle Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'middle_name', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Suffix</div><div class="f-value"><?= htmlspecialchars(field($row, 'suffix', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Gender</div><div class="f-value"><?= htmlspecialchars(ucfirst(field($row, 'sex', '—'))) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Civil Status</div><div class="f-value"><?= htmlspecialchars(ucfirst(field($row, 'civil_status', '—'))) ?></div></div>
      </div>
    </div>

    <div class="cr-doc-section">
      <div class="cr-doc-section-title"><?= htmlspecialchars(ucfirst($registryType)) ?> Details</div>
      <div class="cr-doc-grid">
        <div class="cr-doc-field"><div class="f-label"><?= $typeInfo['dateLabel'] ?></div><div class="f-value"><?= htmlspecialchars($dateOfEventDisplay) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Registry Number</div><div class="f-value"><?= htmlspecialchars(field($row, 'registry_number', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Street</div><div class="f-value"><?= htmlspecialchars(field($row, 'place_of_event', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Barangay</div><div class="f-value"><?= htmlspecialchars(field($row, 'barangay', '—')) ?></div></div>
        <?php if ($registryType === 'marriage' && field($row, 'spouse_name') !== ''): ?>
          <div class="cr-doc-field full"><div class="f-label">Spouse's Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'spouse_name', '—')) ?></div></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (field($row, 'father_name') !== '' || field($row, 'mother_name') !== ''): ?>
    <div class="cr-doc-section">
      <div class="cr-doc-section-title">Parents</div>
      <div class="cr-doc-grid">
        <div class="cr-doc-field"><div class="f-label">Father's Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'father_name', '—')) ?></div></div>
        <div class="cr-doc-field"><div class="f-label">Mother's Name</div><div class="f-value"><?= htmlspecialchars(field($row, 'mother_name', '—')) ?></div></div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (field($row, 'remarks') !== ''): ?>
    <div class="cr-doc-section">
      <div class="cr-doc-section-title">Remarks</div>
      <div class="f-value" style="font-weight:400;"><?= nl2br(htmlspecialchars(field($row, 'remarks'))) ?></div>
    </div>
    <?php endif; ?>

    <div class="cr-doc-footer">
      <span>Date Registered: <?= htmlspecialchars($createdAtDisplay) ?></span>
      <span>Record ID: MCRO-<?= str_pad((string) $recordId, 6, '0', STR_PAD_LEFT) ?></span>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
