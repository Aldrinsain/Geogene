<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

// ---------- One row per Registry Type, straight from Civil Registry Records ----------
// This page reads the exact same table that "Civil Registry Records" and
// "Import Excel" write to, so a record saved anywhere always shows up
// here immediately - there's only ever one source of truth.
$types = [
    'birth'    => 'Birth',
    'marriage' => 'Marriage',
    'death'    => 'Death',
];

$rows = [];
foreach ($types as $key => $label) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total, MAX(created_at) AS last_saved
        FROM civil_registry_records
        WHERE registry_type = ?
    ");
    $stmt->execute([$key]);
    $r = $stmt->fetch();
    $rows[] = [
        'key' => $key,
        'label' => $label,
        'total' => (int) $r['total'],
        'last_saved' => $r['last_saved'],
    ];
}

$staffName = $_SESSION['staff_name'] ?? 'Staff';

$pageTitle = 'All Records';
$pageSub = 'All saved civil registry records, grouped by type.';
$activeStaffNav = 'all-records';
require_once __DIR__ . '/includes/staff-header.php';
?>

<style>
  .ar-folder-icon{width:22px;height:22px;flex-shrink:0;}
  .ar-type-cell{display:flex;align-items:center;gap:10px;font-weight:700;}
  .ar-count-tag{font-size:11px;color:var(--text-muted);font-weight:500;margin-left:6px;}
</style>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">All Saved Records</span>
  </div>

  <table class="data-table">
    <thead>
      <tr><th>Type</th><th>Date Saved or Modified</th><th>User</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <div class="ar-type-cell">
              <svg class="ar-folder-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" fill="#F5B94D" stroke="#D89B2C" stroke-width="1.2"/>
              </svg>
              <?= htmlspecialchars($row['label']) ?>
              <span class="ar-count-tag">(<?= number_format($row['total']) ?>)</span>
            </div>
          </td>
          <td><?= $row['last_saved'] ? date('m/d/Y', strtotime($row['last_saved'])) : '—' ?></td>
          <td><?= htmlspecialchars($staffName) ?></td>
          <td>
            <a href="civil-registry.php?type=<?= $row['key'] ?>" class="btn btn-primary btn-sm" style="text-decoration:none;">View</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
