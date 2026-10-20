<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/name-format.php';

// ---------- SEARCH / FILTER / PAGINATION ----------
// Tanang filters mo-combine (AND) - Type + Barangay + Year + Status + Date + Sex + Search.
$search      = trim($_GET['q'] ?? '');
$searchBy    = $_GET['qby'] ?? 'all';
$typeFilter  = $_GET['type'] ?? '';
$brgyFilter  = trim($_GET['barangay'] ?? '');
$yearFilter  = $_GET['year'] ?? '';
$statusFilter= $_GET['status'] ?? '';
$sexFilter   = $_GET['sex'] ?? '';
$dpFilter    = $_GET['dp'] ?? '';
$dpFrom      = $_GET['dp_from'] ?? '';
$dpTo        = $_GET['dp_to'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// whitelist - para dili ma-abuse ang query string
if (!in_array($typeFilter, ['birth', 'marriage', 'death'], true)) $typeFilter = '';
if (!in_array($statusFilter, ['verified', 'pending'], true)) $statusFilter = '';
if (!in_array($sexFilter, ['male', 'female'], true)) $sexFilter = '';
if (!in_array($searchBy, ['all', 'name', 'registry', 'page', 'barangay'], true)) $searchBy = 'all';
if (!in_array($dpFilter, ['today', 'week', 'month', 'year', 'custom'], true)) $dpFilter = '';
if ($yearFilter !== 'older' && !preg_match('/^\d{4}$/', (string) $yearFilter)) $yearFilter = '';
$validDate = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d) && strtotime($d) !== false;
if (!$validDate($dpFrom)) $dpFrom = '';
if (!$validDate($dpTo)) $dpTo = '';

$where = [];
$params = [];

if ($search !== '') {
    $like = "%$search%";
    $nameSql = "(CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR CONCAT_WS(' ', last_name, first_name, middle_name) LIKE ? OR CONCAT_WS(', ', last_name, CONCAT_WS(' ', first_name, middle_name)) LIKE ? OR full_name LIKE ?)";
    switch ($searchBy) {
        case 'name':
            $where[] = $nameSql;
            array_push($params, $like, $like, $like, $like);
            break;
        case 'registry':
            $where[] = "registry_number LIKE ?";
            $params[] = $like;
            break;
        case 'page':
            $where[] = "page_no LIKE ?";
            $params[] = $like;
            break;
        case 'barangay':
            $where[] = "barangay LIKE ?";
            $params[] = $like;
            break;
        default: // all
            $where[] = "($nameSql OR registry_number LIKE ? OR page_no LIKE ? OR barangay LIKE ?)";
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }
}
if ($typeFilter !== '') { $where[] = "registry_type = ?"; $params[] = $typeFilter; }
if ($brgyFilter !== '') { $where[] = "barangay = ?"; $params[] = $brgyFilter; }
if ($sexFilter !== '')  { $where[] = "sex = ?"; $params[] = $sexFilter; }
if ($statusFilter === 'verified') { $where[] = "verified_at IS NOT NULL"; }
if ($statusFilter === 'pending')  { $where[] = "verified_at IS NULL"; }

if ($yearFilter === 'older') {
    $where[] = "YEAR(date_of_event) < ?";
    $params[] = (int) date('Y') - 3; // older than the last 4 years shown in the dropdown
} elseif ($yearFilter !== '') {
    $where[] = "YEAR(date_of_event) = ?";
    $params[] = (int) $yearFilter;
}

// Date Prepared (same fallback sa gipakita sa table: date_prepared, else created_at)
$dpExpr = "COALESCE(date_prepared, DATE(created_at))";
$today = date('Y-m-d');
if ($dpFilter === 'today') {
    $where[] = "$dpExpr = ?"; $params[] = $today;
} elseif ($dpFilter === 'week') {
    $where[] = "$dpExpr BETWEEN ? AND ?";
    $params[] = date('Y-m-d', strtotime('monday this week'));
    $params[] = date('Y-m-d', strtotime('sunday this week'));
} elseif ($dpFilter === 'month') {
    $where[] = "$dpExpr BETWEEN ? AND ?";
    $params[] = date('Y-m-01');
    $params[] = date('Y-m-t');
} elseif ($dpFilter === 'year') {
    $where[] = "$dpExpr BETWEEN ? AND ?";
    $params[] = date('Y-01-01');
    $params[] = date('Y-12-31');
} elseif ($dpFilter === 'custom') {
    if ($dpFrom !== '') { $where[] = "$dpExpr >= ?"; $params[] = $dpFrom; }
    if ($dpTo !== '')   { $where[] = "$dpExpr <= ?"; $params[] = $dpTo; }
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Active filters (gawas sa search) - para sa badge sa Filter button
$activeFilterCount = count(array_filter([
    $typeFilter, $brgyFilter, $yearFilter, $statusFilter, $sexFilter, $dpFilter,
]));
$hasAnyFilter = $activeFilterCount > 0 || $search !== '';

// Query string nga gina-carry sa pagination / delete / export
$filterQS = array_filter([
    'q' => $search, 'qby' => $searchBy !== 'all' ? $searchBy : '', 'type' => $typeFilter,
    'barangay' => $brgyFilter, 'year' => $yearFilter, 'status' => $statusFilter,
    'sex' => $sexFilter, 'dp' => $dpFilter,
    'dp_from' => $dpFilter === 'custom' ? $dpFrom : '', 'dp_to' => $dpFilter === 'custom' ? $dpTo : '',
], fn($v) => $v !== '' && $v !== null);

// ---------- EXPORT (CSV sa current filtered results) ----------
if (isset($_GET['export'])) {
    $exStmt = $pdo->prepare("SELECT * FROM civil_registry_records $whereSql ORDER BY last_name ASC, first_name ASC");
    $exStmt->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="civil-registry-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM para sakto ang Excel
    fputcsv($out, ['Page No.', 'Registry No.', 'Barangay', 'Date Prepared', 'Full Name', 'Type', 'Date of Event', 'Status']);
    while ($r = $exStmt->fetch()) {
        $dp = $r['date_prepared'] ?: $r['created_at'];
        fputcsv($out, [
            $r['page_no'], $r['registry_number'], $r['barangay'],
            $dp ? date('Y-m-d', strtotime($dp)) : '',
            formatFullName($r['last_name'], $r['first_name'], $r['middle_name'], $r['suffix']) ?: $r['full_name'],
            ucfirst($r['registry_type']), $r['date_of_event'],
            !empty($r['verified_at']) ? 'Verified' : 'Pending',
        ]);
    }
    fclose($out);
    exit;
}

// Dropdown options gikan sa tinuod nga data (dili hardcoded)
$barangayOptions = $pdo->query("SELECT DISTINCT barangay FROM civil_registry_records WHERE barangay IS NOT NULL AND barangay <> '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
$curYear = (int) date('Y');
$yearOptions = range($curYear, $curYear - 3);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM civil_registry_records $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

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
  <?php if (!empty($_SESSION['import_skipped_details'])): ?>
    <div class="import-alert" style="background:#fdeceb;border-color:#f3c8c4;color:#7a2e26;">
      ⚠ <strong>Skipped rows detail</strong> - these have the same Registry No. as a record already saved
      (Registry No. must be unique). Fix the Registry No. in your spreadsheet and re-upload just these rows:
      <ul style="margin:8px 0 0 18px;padding:0;">
        <?php foreach ($_SESSION['import_skipped_details'] as $d): ?>
          <li>Excel row <?= (int) $d['row'] ?>: <?= htmlspecialchars($d['name']) ?> - Registry No. <strong><?= htmlspecialchars($d['registry_number']) ?></strong> is already used by another record.</li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['import_skipped_details']); // flash message - shown once ?>
  <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['verified'])): ?>
  <div class="import-alert">✅ Certificate verified and saved to Civil Registry Records.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div class="import-alert">🗑️ Record deleted.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted_all'])): ?>
  <div class="import-alert">🗑️ <?= (int) $_GET['deleted_all'] ?> record<?= ((int) $_GET['deleted_all']) === 1 ? '' : 's' ?> deleted.</div>
<?php endif; ?>

<?php if (isset($_GET['delete_all_error'])): ?>
  <div class="import-alert" style="background:#fdeceb;border-color:#f3c8c4;color:#7a2e26;">⚠ Delete all was cancelled — the confirmation text didn't match.</div>
<?php endif; ?>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">All Records (<?= number_format($totalRows) ?>)</span>
    <div style="display:flex;gap:10px;">
      <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteAllModal').style.display='flex'">🗑️ Delete All Records</button>
      <a href="civil-registry-import.php" class="btn btn-outline">⬆ Import Excel</a>
      <a href="civil-registry-bulk-scan.php" class="btn btn-outline"><svg xmlns="http://www.w3.org/2000/svg"
     viewBox="38.25 0 440.75 516.375"
     width="32"
     height="32"
     fill="currentColor"
     preserveAspectRatio="xMidYMid meet">
  <g>
    <rect x="153" y="497.25" width="38.25" height="19.125"/>
    <rect x="76.5" y="401.625" width="19.125" height="38.25"/>
    <path d="M344.25,133.875h95.625L325.125,0v114.75C325.125,126.225,334.688,133.875,344.25,133.875z"/>
    <rect x="210.375" y="497.25" width="38.25" height="19.125"/>
    <path d="M95.625,478.125V459H76.5v19.125c0,21.037,17.212,38.25,38.25,38.25h19.125V497.25H114.75C105.188,497.25,95.625,489.6,95.625,478.125z"/>
    <path d="M439.875,153H344.25c-21.037,0-38.25-17.212-38.25-38.25V0H114.75C93.712,0,76.5,17.212,76.5,38.25v248.625h363.375V153z"/>
    <rect x="76.5" y="344.25" width="19.125" height="38.25"/>
    <rect x="420.75" y="401.625" width="19.125" height="38.25"/>
    <rect x="420.75" y="344.25" width="19.125" height="38.25"/>
    <polygon points="459,267.75 459,306 57.375,306 57.375,267.75 38.25,267.75 38.25,363.375 57.375,363.375 57.375,325.125 459,325.125 459,363.375 478.125,363.375 478.125,267.75"/>
    <path d="M420.75,478.125c0,11.475-7.65,19.125-19.125,19.125H382.5v19.125h19.125c21.037,0,38.25-17.213,38.25-38.25V459H420.75V478.125z"/>
    <rect x="267.75" y="497.25" width="38.25" height="19.125"/>
    <rect x="325.125" y="497.25" width="38.25" height="19.125"/>
  </g>
</svg> Bulk Certificate Scan</a>
      <a href="civil-registry-form.php" class="btn btn-primary">+ Add Record</a>
    </div>
  </div>

  <form method="GET" class="table-toolbar filter-wrap" id="filterForm">
    <div class="search-box">
      <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" name="q" id="filterSearch" placeholder="Search full name, registry number, page number, or barangay..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
    </div>
    <button type="button" class="btn btn-outline btn-sm filter-toggle<?= $activeFilterCount ? ' has-active' : '' ?>" id="filterToggle" aria-expanded="false" aria-controls="filterPanel">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="14" height="14" style="vertical-align:-2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8.25V19.5l-4 1.5v-8.25L3 4.5Z"/></svg>
      Filter<?php if ($activeFilterCount): ?><span class="filter-count"><?= $activeFilterCount ?></span><?php endif; ?>
    </button>
    <?php if ($hasAnyFilter): ?>
      <a href="civil-registry.php" class="btn btn-outline btn-sm" title="Clear all filters">✕ Reset</a>
    <?php endif; ?>
    <a href="civil-registry.php?<?= htmlspecialchars(http_build_query($filterQS + ['export' => 1])) ?>" class="btn btn-outline btn-sm" title="Download the current results as CSV">⬇ Export</a>

    <div class="filter-panel" id="filterPanel" hidden>
      <div class="filter-panel-head">
        <strong>Filter Records</strong>
        <button type="button" class="filter-close" id="filterClose" aria-label="Close filter">&times;</button>
      </div>
      <div class="filter-grid">
        <label>Record Type
          <select name="type">
            <option value="">All Types</option>
            <option value="birth" <?= $typeFilter === 'birth' ? 'selected' : '' ?>>Birth Records</option>
            <option value="marriage" <?= $typeFilter === 'marriage' ? 'selected' : '' ?>>Marriage Records</option>
            <option value="death" <?= $typeFilter === 'death' ? 'selected' : '' ?>>Death Records</option>
          </select>
        </label>
        <label>Barangay
          <select name="barangay">
            <option value="">All Barangays</option>
            <?php foreach ($barangayOptions as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= $brgyFilter === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Year of Event
          <select name="year">
            <option value="">All Years</option>
            <?php foreach ($yearOptions as $y): ?>
              <option value="<?= $y ?>" <?= (string) $yearFilter === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
            <option value="older" <?= $yearFilter === 'older' ? 'selected' : '' ?>>Older Records</option>
          </select>
        </label>
        <label>Date Prepared
          <select name="dp" id="dpSelect">
            <option value="">All Dates</option>
            <option value="today" <?= $dpFilter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= $dpFilter === 'week' ? 'selected' : '' ?>>This Week</option>
            <option value="month" <?= $dpFilter === 'month' ? 'selected' : '' ?>>This Month</option>
            <option value="year" <?= $dpFilter === 'year' ? 'selected' : '' ?>>This Year</option>
            <option value="custom" <?= $dpFilter === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
          </select>
        </label>
        <div class="filter-custom" id="dpCustom" style="<?= $dpFilter === 'custom' ? '' : 'display:none;' ?>">
          <label>From <input type="date" name="dp_from" value="<?= htmlspecialchars($dpFrom) ?>"></label>
          <label>To <input type="date" name="dp_to" value="<?= htmlspecialchars($dpTo) ?>"></label>
        </div>
        <label>Status
          <select name="status">
            <option value="">All Status</option>
            <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
          </select>
        </label>
        <label>Sex
          <select name="sex">
            <option value="">All</option>
            <option value="male" <?= $sexFilter === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $sexFilter === 'female' ? 'selected' : '' ?>>Female</option>
          </select>
        </label>
        <label class="filter-full">Search By
          <select name="qby">
            <option value="all" <?= $searchBy === 'all' ? 'selected' : '' ?>>All fields</option>
            <option value="name" <?= $searchBy === 'name' ? 'selected' : '' ?>>Full Name</option>
            <option value="registry" <?= $searchBy === 'registry' ? 'selected' : '' ?>>Registry Number</option>
            <option value="page" <?= $searchBy === 'page' ? 'selected' : '' ?>>Page Number</option>
            <option value="barangay" <?= $searchBy === 'barangay' ? 'selected' : '' ?>>Barangay</option>
          </select>
        </label>
      </div>
      <div class="filter-actions">
        <a href="civil-registry.php" class="btn btn-outline">↻ Clear Filters</a>
        <button type="submit" class="btn btn-primary">Apply Filter</button>
      </div>
    </div>
  </form>

  <div class="table-scroll">
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
          <th>Name of Mother (W)</th>
          <th>Name of Mother (H)</th>
          <th>Name of Father (W)</th>
          <th>Name of Father (H)</th>
        <?php elseif ($typeFilter === 'death'): ?>
          <th>Name of Deceased</th>
          <th>Sex</th>
          <th>Date of Death</th>
          <th>Date of Birth</th>
          <th>Age at Time of Death</th>
          <th>Place of Death</th>
          <th>Civil Status</th>
          <th>Name of Father</th>
          <th>Maiden Name of Mother</th>
          <th>Cause of Death</th>
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
      $colCount = 4 + ($typeFilter === 'birth' ? 4 : ($typeFilter === 'marriage' ? 7 : ($typeFilter === 'death' ? 10 : 3))) + 1;
      if (!$records):
      ?>
        <tr><td colspan="<?= $colCount ?>" style="text-align:center;color:var(--text-muted);padding:30px;">No records found.</td></tr>
      <?php else: foreach ($records as $i => $r):
        $pageNo = $offset + $i + 1;
        // Always recompute the display name fresh from first/middle/last
        // (not the stored full_name column) - so older records saved before
        // the Suffix-in-name fix still display correctly without needing a
        // one-time data cleanup.
        $fullName = formatFullName($r['last_name'], $r['first_name'], $r['middle_name'], $r['suffix']) ?: $r['full_name'];
        $datePrepared = $r['date_prepared'] ?: $r['created_at'];

        // Marriage: use "sex" to figure out kinsa ang Wife/Husband sa this record's
        // primary registrant vs the spouse_name field.
        if ($r['sex'] === 'female') {
            $wifeName = $fullName;
            $husbandName = gg_display_name($r['spouse_name']) ?: '—';
        } else {
            $husbandName = $fullName;
            $wifeName = gg_display_name($r['spouse_name']) ?: '—';
        }
      ?>
        <tr>
          <td><?= htmlspecialchars($r['page_no'] ?: (string) $pageNo) ?></td>
          <td><?= htmlspecialchars($r['registry_number'] ?: '—') ?></td>
          <td><?= htmlspecialchars($r['barangay'] ?: '—') ?><?= !empty($r['zone']) ? '<div style="font-size:11px;color:var(--text-muted);">' . htmlspecialchars($r['zone']) . '</div>' : '' ?></td>
          <td><?= $datePrepared ? date('M d, Y', strtotime($datePrepared)) : '—' ?></td>

          <?php if ($typeFilter === 'birth'): ?>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['mother_name']) ?: '—') ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['father_name']) ?: '—') ?></td>
          <?php elseif ($typeFilter === 'marriage'): ?>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= htmlspecialchars($wifeName) ?></td>
            <td><?= htmlspecialchars($husbandName) ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['wife_mother_name'] ?? '') ?: '—') ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['mother_name']) ?: '—') ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['wife_father_name'] ?? '') ?: '—') ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['father_name']) ?: '—') ?></td>
          <?php elseif ($typeFilter === 'death'): ?>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><?= $r['sex'] ? ucfirst($r['sex']) : '—' ?></td>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
            <td><?= !empty($r['date_of_birth']) ? date('M d, Y', strtotime($r['date_of_birth'])) : '—' ?></td>
            <td><?= htmlspecialchars($r['age_at_death'] ?? '' ?: '—') ?></td>
            <td><?= htmlspecialchars($r['place_of_event'] ?: '—') ?></td>
            <td><?= $r['civil_status'] ? ucfirst($r['civil_status']) : '—' ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['father_name']) ?: '—') ?></td>
            <td><?= htmlspecialchars(gg_display_name($r['mother_name']) ?: '—') ?></td>
            <td><?= htmlspecialchars($r['cause_of_death'] ?? '' ?: '—') ?></td>
          <?php else: ?>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><span class="badge <?= $r['registry_type'] === 'birth' ? 'green' : ($r['registry_type'] === 'marriage' ? 'blue' : 'gray') ?>"><?= ucfirst($r['registry_type']) ?></span></td>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
          <?php endif; ?>

          <td style="white-space:nowrap;">
            <a href="civil-registry-view.php?id=<?= $r['record_id'] ?>" class="icon-btn view" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </a>
            <?php if (!empty($r['certificate_data'])): ?>
              <a href="civil-registry-print.php?id=<?= $r['record_id'] ?>" class="icon-btn" title="Print Exact Certificate">🖨️</a>
            <?php endif; ?>
            <form method="POST" action="civil-registry-delete.php" style="display:inline;" onsubmit="return confirm('Delete this record (<?= htmlspecialchars(addslashes($fullName)) ?>)? This cannot be undone.');">
              <input type="hidden" name="record_id" value="<?= $r['record_id'] ?>">
              <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($filterQS)) ?>">
              <input type="hidden" name="page" value="<?= $page ?>">
              <button type="submit" class="icon-btn danger" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <div class="pagination">
    <span>Showing <?= count($records) ?> of <?= number_format($totalRows) ?> records</span>
    <div class="page-btns">
      <?php $pageQS = fn($p) => '?' . http_build_query($filterQS + ['page' => $p]); ?>

      <?php if ($page > 1): ?>
        <a href="<?= $pageQS($page - 1) ?>"><button title="Previous page">&lsaquo;</button></a>
      <?php else: ?>
        <button disabled>&lsaquo;</button>
      <?php endif; ?>

      <span class="page-indicator"><?= $page ?> / <?= $totalPages ?></span>

      <?php if ($page < $totalPages): ?>
        <a href="<?= $pageQS($page + 1) ?>"><button title="Next page">&rsaquo;</button></a>
      <?php else: ?>
        <button disabled>&rsaquo;</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Delete-All confirmation modal - requires typing "DELETE ALL" so 628
     records can't be wiped out by a stray click. -->
<div id="deleteAllModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,.2);">
    <h3 style="margin:0 0 8px;color:var(--danger);">⚠ Delete ALL records?</h3>
    <p style="font-size:13.5px;color:var(--text-secondary);margin:0 0 14px;line-height:1.5;">
      This will permanently delete <strong>all <?= number_format($totalRows) ?> civil registry records</strong>
      (not just this page or filter) — including any that are linked to citizen accounts. This cannot be undone.
      Type <strong>DELETE ALL</strong> below to confirm.
    </p>
    <form method="POST" action="civil-registry-delete-all.php">
      <input type="text" id="deleteAllConfirmInput" name="confirm" autocomplete="off" placeholder="Type DELETE ALL"
             style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;margin-bottom:14px;box-sizing:border-box;"
             oninput="document.getElementById('deleteAllSubmitBtn').disabled = (this.value !== 'DELETE ALL')">
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('deleteAllModal').style.display='none'; document.getElementById('deleteAllConfirmInput').value=''; document.getElementById('deleteAllSubmitBtn').disabled=true;">Cancel</button>
        <button type="submit" id="deleteAllSubmitBtn" class="btn btn-danger" disabled>Delete All Records</button>
      </div>
    </form>
  </div>
</div>


<style>
  .filter-wrap{position:relative;}
  .filter-toggle{display:inline-flex;align-items:center;gap:6px;}
  .filter-toggle.has-active{border-color:var(--primary);color:var(--primary);background:var(--primary-light);}
  .filter-count{background:var(--primary);color:#fff;border-radius:999px;font-size:10.5px;min-width:17px;height:17px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;}
  .filter-panel{position:absolute;top:calc(100% + 8px);right:0;width:min(440px,100%);background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.16);padding:16px 18px 18px;z-index:50;}
  .filter-panel[hidden]{display:none;}
  .filter-panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:14px;}
  .filter-close{background:none;border:none;font-size:22px;line-height:1;cursor:pointer;color:var(--text-secondary);}
  .filter-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .filter-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;color:var(--text-secondary);font-weight:600;}
  .filter-grid select,.filter-grid input[type=date]{width:100%;border:1px solid var(--border);border-radius:9px;padding:9px 10px;font-size:13px;background:#fff;color:var(--text-main);box-sizing:border-box;}
  .filter-grid select:focus,.filter-grid input:focus{outline:2px solid var(--primary-light);border-color:var(--primary);}
  .filter-full,.filter-custom{grid-column:1 / -1;}
  .filter-custom{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .filter-actions{display:flex;gap:10px;margin-top:16px;}
  .filter-actions .btn{flex:1;justify-content:center;text-align:center;}
  body.dark-mode .filter-panel{background:var(--bg-card);border-color:var(--border);}
  body.dark-mode .filter-grid select,body.dark-mode .filter-grid input[type=date]{background:var(--bg-page);}
  @media (max-width:560px){.filter-panel{position:static;width:100%;margin-top:8px;}.filter-grid{grid-template-columns:1fr;}}
</style>
<script>
(function () {
  var toggle = document.getElementById('filterToggle');
  var panel  = document.getElementById('filterPanel');
  var close  = document.getElementById('filterClose');
  var dp     = document.getElementById('dpSelect');
  var custom = document.getElementById('dpCustom');
  var box    = document.querySelector('.panel');
  if (!toggle || !panel) return;

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    // Kung naka-embed (Admin iframe), i-reserve ang height para dili ma-clip ang dropdown panel
    if (box) box.style.minHeight = open ? (panel.offsetHeight + 190) + 'px' : '';
  }
  toggle.addEventListener('click', function () { setOpen(panel.hidden); });
  close.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  document.addEventListener('click', function (e) {
    if (!panel.hidden && !panel.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
  });
  if (dp) dp.addEventListener('change', function () {
    custom.style.display = dp.value === 'custom' ? '' : 'none';
  });
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>

<?php if (isset($_GET['print'])): ?>
<style>
  @media print {
    .sidebar, .topbar, .table-toolbar, .pagination, .btn, .icon-btn, .panel-header a, .panel-header button { display:none !important; }
  }
</style>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
<?php endif; ?>