<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

// ---------- SEARCH / FILTER / PAGINATION ----------
$search = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR full_name LIKE ? OR registry_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($typeFilter !== '') {
    $where[] = "registry_type = ?";
    $params[] = $typeFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM civil_registry_records $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM civil_registry_records $whereSql ORDER BY last_name ASC, first_name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = 'Civil Registry Records';
$pageSub = 'Manage and search MCRO civil registry records.';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>

<?php if (isset($_GET['imported'])):
  $importedCount = (int) $_GET['imported'];
  $skippedCount = (int) ($_GET['skipped'] ?? 0);
?>
  <div class="import-alert">
    ✅ Successfully imported <strong><?= $importedCount ?></strong> record<?= $importedCount === 1 ? '' : 's' ?> from your file.
    <?php if ($skippedCount): ?>
      <?= $skippedCount ?> row<?= $skippedCount === 1 ? '' : 's' ?> were skipped (likely a duplicate registry number already in the system).
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">All Records (<?= number_format($totalRows) ?>)</span>
    <div style="display:flex;gap:10px;">
      <a href="civil-registry-import.php" class="btn btn-outline">⬆ Import Excel</a>
      <a href="civil-registry-form.php" class="btn btn-primary">+ Add Record</a>
    </div>
  </div>

  <form method="GET" class="table-toolbar">
    <div class="search-box">
      <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" name="q" placeholder="Search full name..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <option value="birth" <?= $typeFilter === 'birth' ? 'selected' : '' ?>>Birth</option>
      <option value="marriage" <?= $typeFilter === 'marriage' ? 'selected' : '' ?>>Marriage</option>
      <option value="death" <?= $typeFilter === 'death' ? 'selected' : '' ?>>Death</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
  </form>

  <table class="data-table">
    <thead>
      <tr>
        <th style="width:60px;">Page No.</th>
        <th>Registry No.</th>
        <th>Barangay</th>
        <th>Date Prepared</th>
        <?php if ($typeFilter === 'birth'): ?>
          <th>Date of Birth</th>
          <th>Name of Child</th>
          <th>Name of Mother</th>
          <th>Name of Father</th>
        <?php elseif ($typeFilter === 'marriage'): ?>
          <th>Date of Marriage</th>
          <th>Name of Wife</th>
          <th>Name of Husband</th>
          <th>Name of Mother</th>
          <th>Name of Father</th>
        <?php elseif ($typeFilter === 'death'): ?>
          <th>Date of Death</th>
          <th>Name of Deceased</th>
          <th>Name of Mother</th>
          <th>Name of Father</th>
        <?php else: ?>
          <th>Full Name</th>
          <th>Type</th>
          <th>Date of Event</th>
        <?php endif; ?>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $colCount = $typeFilter === 'marriage' ? 9 : ($typeFilter === '' ? 8 : 8);
      if (!$records):
      ?>
        <tr><td colspan="<?= $colCount ?>" style="text-align:center;color:var(--text-muted);padding:30px;">No records found.</td></tr>
      <?php else: foreach ($records as $i => $r):
        $pageNo = $offset + $i + 1;
        $fullName = $r['full_name'] ?: trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']);
        $datePrepared = $r['date_prepared'] ?: $r['created_at'];

        // Marriage: use "sex" to figure out kinsa ang Wife/Husband sa this record's
        // primary registrant vs the spouse_name field.
        if ($r['sex'] === 'female') {
            $wifeName = $fullName;
            $husbandName = $r['spouse_name'] ?: '—';
        } else {
            $husbandName = $fullName;
            $wifeName = $r['spouse_name'] ?: '—';
        }
      ?>
        <tr>
          <td><?= htmlspecialchars($r['page_no'] ?: (string) $pageNo) ?></td>
          <td><?= htmlspecialchars($r['registry_number'] ?: '—') ?></td>
          <td><?= htmlspecialchars($r['barangay'] ?: '—') ?></td>
          <td><?= $datePrepared ? date('M d, Y', strtotime($datePrepared)) : '—' ?></td>

          <?php if ($typeFilter === 'birth'): ?>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><?= htmlspecialchars($r['mother_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['father_name'] ?: '—') ?></td>
          <?php elseif ($typeFilter === 'marriage'): ?>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= htmlspecialchars($wifeName) ?></td>
            <td><?= htmlspecialchars($husbandName) ?></td>
            <td><?= htmlspecialchars($r['mother_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['father_name'] ?: '—') ?></td>
          <?php elseif ($typeFilter === 'death'): ?>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><?= htmlspecialchars($r['mother_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['father_name'] ?: '—') ?></td>
          <?php else: ?>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><span class="badge <?= $r['registry_type'] === 'birth' ? 'green' : ($r['registry_type'] === 'marriage' ? 'blue' : 'gray') ?>"><?= ucfirst($r['registry_type']) ?></span></td>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
          <?php endif; ?>

          <td>
            <a href="civil-registry-view.php?id=<?= $r['record_id'] ?>" class="icon-btn view" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <div class="pagination">
    <span>Showing <?= count($records) ?> of <?= number_format($totalRows) ?> records</span>
    <div class="page-btns">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?q=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&page=<?= $p ?>">
          <button class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></button>
        </a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>

<?php if (isset($_GET['print'])): ?>
<style>
  @media print {
    .sidebar, .topbar, .table-toolbar, .pagination, .btn, .icon-btn, .panel-header a, .panel-header button { display:none !important; }
  }
</style>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
<?php endif; ?>