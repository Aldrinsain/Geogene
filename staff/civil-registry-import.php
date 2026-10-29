<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/CivilRegistryImport.php';

// ------------------------------------------------------------------
// Request handling
// ------------------------------------------------------------------
$error = '';
$preview = null;
$step = $_POST['step'] ?? 'upload';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'parse') {
    if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please choose a valid .xlsx or .csv file to upload.';
    } else {
        try {
            $result = import_parse_uploaded_file($_FILES['import_file']['tmp_name'], $_FILES['import_file']['name']);
            $_SESSION['import_preview'] = $result;
            $preview = $result;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'confirm') {
    $result = $_SESSION['import_preview'] ?? null;
    if (!$result || empty($result['valid'])) {
        $error = 'Nothing to import - please upload the file again.';
    } else {
        // If the staff explicitly confirmed/overrode the Registry Type for
        // this whole batch, apply it to every row before saving - this is
        // what guarantees the types never get mixed up on save.
        $typeOverride = $_POST['registry_type_override'] ?? '';
        if (in_array($typeOverride, ['birth', 'marriage', 'death'], true)) {
            foreach ($result['valid'] as &$item) {
                $item['record']['registry_type'] = $typeOverride;
            }
            unset($item);
        }

        $stmt = $pdo->prepare("
            INSERT INTO civil_registry_records (registry_type, registry_number, book_no, page_no,
            city_municipality, date_prepared, full_name, first_name, middle_name,
            last_name, suffix, sex, date_of_event, place_of_event, father_name, mother_name, spouse_name,
            barangay, zone, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $imported = 0;
        $skipped = 0;
        $skippedDetails = [];
        foreach ($result['valid'] as $item) {
            $r = $item['record'];
            try {
                $stmt->execute([
                    $r['registry_type'], $r['registry_number'], $r['book_no'] ?? null, $r['page_no'] ?? null,
                    $r['city_municipality'] ?? null, $r['date_prepared'] ?? null, $r['full_name'] ?? null,
                    $r['first_name'], $r['middle_name'],
                    $r['last_name'], $r['suffix'], $r['sex'], $r['date_of_event'], $r['place_of_event'],
                    $r['father_name'], $r['mother_name'], $r['spouse_name'], gg_canonical_barangay($pdo, $r['barangay']), $r['zone'] ?? null, $r['remarks'],
                ]);
                $imported++;
            } catch (PDOException $e) {
                // Most likely a duplicate registry_number (UNIQUE constraint) - skip and keep going,
                // but remember exactly which row + registry number so the staff can see why afterward
                // instead of just a mystery count of "skipped" rows.
                $skipped++;
                $skippedDetails[] = [
                    'row' => $item['row'],
                    'name' => $r['full_name'] ?: trim($r['first_name'] . ' ' . $r['last_name']),
                    'registry_number' => $r['registry_number'] ?: '(blank)',
                ];
            }
        }

        unset($_SESSION['import_preview']);
        if ($skippedDetails) {
            $_SESSION['import_skipped_details'] = $skippedDetails;
        }
        header('Location: civil-registry.php?imported=' . $imported . '&skipped=' . $skipped);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'cancel') {
    unset($_SESSION['import_preview']);
} else {
    // Fresh GET load - clear out any stale preview from an abandoned session.
    unset($_SESSION['import_preview']);
}

$pageTitle = 'Import Civil Registry Records';
$pageSub = 'Bulk-upload records from an Excel (.xlsx) or CSV file instead of adding them one by one.';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>

<?php if ($error): ?>
  <div class="error-text"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!$preview): ?>

  <div class="panel" style="max-width:760px;">
    <div class="panel-header">
      <span class="panel-title">Upload File</span>
      <a href="civil-registry-template.php" class="btn btn-outline btn-sm">⬇ Download Template</a>
    </div>

    <div class="upload-info-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <span>Upload 1 supported file (.xlsx or .csv). Max 10 MB. Only the first sheet is read, and the first row must contain column headers.</span>
    </div>

    <form method="POST" enctype="multipart/form-data" id="importForm">
      <input type="hidden" name="step" value="parse">
      <div class="upload-dropzone" id="dropzone">
        <div id="dzIdle">
          <svg class="upload-cloud-icon" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 18a4.6 4.4 0 0 1 0-9 5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"/>
            <polyline points="9 15 12 12 15 15"/>
            <line x1="12" y1="12" x2="12" y2="21"/>
          </svg>
          <div>
            <button type="button" class="btn btn-primary upload-browse-btn" id="browseBtn">Browse</button>
          </div>
          <div class="upload-hint">or drag a .xlsx / .csv file here</div>
        </div>
        <div id="dzSelected" style="display:none;">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div class="upload-filename" id="fileNameLabel"></div>
          <button type="button" class="btn btn-outline btn-sm" id="changeFileBtn" style="margin-top:10px;">Choose a different file</button>
        </div>
        <input type="file" name="import_file" id="fileInput" accept=".xlsx,.csv" required style="display:none;">
      </div>
      <div style="display:flex;gap:10px;margin-top:16px;">
        <button type="submit" class="btn btn-primary">Upload &amp; Preview</button>
        <a href="civil-registry.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>

  <script>
    (function () {
      const dropzone = document.getElementById('dropzone');
      const fileInput = document.getElementById('fileInput');
      const browseBtn = document.getElementById('browseBtn');
      const changeFileBtn = document.getElementById('changeFileBtn');
      const dzIdle = document.getElementById('dzIdle');
      const dzSelected = document.getElementById('dzSelected');
      const fileNameLabel = document.getElementById('fileNameLabel');

      function showSelected(file) {
        fileNameLabel.textContent = file.name;
        dzIdle.style.display = 'none';
        dzSelected.style.display = 'block';
      }
      function showIdle() {
        fileInput.value = '';
        dzSelected.style.display = 'none';
        dzIdle.style.display = 'block';
      }

      browseBtn.addEventListener('click', () => fileInput.click());
      changeFileBtn.addEventListener('click', (e) => { e.stopPropagation(); showIdle(); });
      dropzone.addEventListener('click', (e) => {
        if (dzIdle.style.display !== 'none' && e.target === dropzone) fileInput.click();
      });
      fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) showSelected(fileInput.files[0]);
      });

      ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => {
          e.preventDefault(); e.stopPropagation();
          dropzone.classList.add('dragover');
        });
      });
      ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => {
          e.preventDefault(); e.stopPropagation();
          dropzone.classList.remove('dragover');
        });
      });
      dropzone.addEventListener('drop', (e) => {
        const file = e.dataTransfer.files[0];
        if (file) {
          fileInput.files = e.dataTransfer.files;
          showSelected(file);
        }
      });
    })();
  </script>

  <div class="panel" style="max-width:760px;">
    <span class="panel-title">Expected Columns</span>
    <p style="color:var(--text-secondary);font-size:13px;margin:8px 0 12px;">
      Column names are flexible (not case-sensitive, spaces optional). Upload your Birth, Marriage, or Death
      register file as-is - the <strong>type is detected automatically</strong> from whichever name column is
      present (e.g. "Name of Child" = Birth). Extra columns in your file are ignored.
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
      <?php foreach (['Barangay', 'Zone', 'City/Municipality', 'Book No.', 'Registry No.'] as $col): ?>
        <span class="badge gray"><?= htmlspecialchars($col) ?></span>
      <?php endforeach; ?>
    </div>
    <p style="color:var(--text-secondary);font-size:12.5px;font-weight:700;margin:0 0 6px;">For Birth records:</p>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
      <?php foreach (['Date of Birth', 'Name of Child*', "Name of Mother", "Name of Father"] as $col): ?>
        <span class="badge green"><?= htmlspecialchars($col) ?></span>
      <?php endforeach; ?>
    </div>
    <p style="color:var(--text-secondary);font-size:12.5px;font-weight:700;margin:0 0 6px;">For Marriage records:</p>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
      <?php foreach (['Date of Marriage', 'Name of Wife*', 'Name of Husband*'] as $col): ?>
        <span class="badge blue"><?= htmlspecialchars($col) ?></span>
      <?php endforeach; ?>
    </div>
    <p style="color:var(--text-secondary);font-size:12.5px;font-weight:700;margin:0 0 6px;">For Death records:</p>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach (['Date of Death', 'Name of Deceased*'] as $col): ?>
        <span class="badge gray"><?= htmlspecialchars($col) ?></span>
      <?php endforeach; ?>
    </div>
    <p style="color:var(--text-muted);font-size:11.5px;margin-top:12px;">
      * At least one name column is required per file. Older-style files with separate
      First Name / Last Name columns are also still supported.
    </p>
  </div>

<?php else: ?>

  <div class="panel">
    <div class="panel-header">
      <span class="panel-title">Preview</span>
      <div style="display:flex;gap:8px;">
        <span class="badge green"><?= count($preview['valid']) ?> ready to import</span>
        <?php if ($preview['invalid']): ?>
          <span class="badge red"><?= count($preview['invalid']) ?> will be skipped</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($preview['invalid']): ?>
      <p style="color:var(--text-secondary);font-size:13px;">
        The rows below are missing a First Name or Last Name and won't be imported. You can still import the
        valid rows now, then fix and re-upload the rest later.
      </p>
      <table class="data-table" style="margin-bottom:20px;">
        <thead><tr><th>Excel Row</th><th>First Name</th><th>Last Name</th><th>Issue</th></tr></thead>
        <tbody>
          <?php foreach (array_slice($preview['invalid'], 0, 20) as $item): ?>
            <tr>
              <td><?= (int) $item['row'] ?></td>
              <td><?= htmlspecialchars($item['record']['first_name']) ?></td>
              <td><?= htmlspecialchars($item['record']['last_name']) ?></td>
              <td><span class="badge red"><?= htmlspecialchars($item['reason']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (count($preview['invalid']) > 20): ?>
        <p style="color:var(--text-muted);font-size:12px;">...and <?= count($preview['invalid']) - 20 ?> more.</p>
      <?php endif; ?>
    <?php endif; ?>

    <?php
      // ---------- Detected Columns ----------
      // Shows exactly which column in the uploaded file each recognized
      // field was read from, so a mis-detected or missing column (e.g. a
      // typo'd header, or a "Barangay" column that didn't get picked up)
      // is obvious right away instead of only showing up later as a
      // silent blank field on every row.
      $colMap = $preview['colMap'] ?? [];
      $fieldLabels = [
          'barangay' => 'Barangay', 'zone' => 'Zone/Purok/Sitio', 'city_municipality' => 'City/Municipality',
          'book_no' => 'Book No.', 'registry_number' => 'Registry No.',
          'date_of_event' => 'Date of Birth/Marriage/Death',
          'name_of_child' => 'Name of Child', 'name_of_wife' => 'Name of Wife',
          'name_of_husband' => 'Name of Husband', 'name_of_deceased' => 'Name of Deceased',
          'full_name' => 'Full Name', 'first_name' => 'First Name', 'last_name' => 'Last Name',
          'mother_name' => 'Name of Mother', 'father_name' => 'Name of Father',
      ];
    ?>
    <details style="margin:14px 0 18px;">
      <summary style="cursor:pointer;font-size:12.5px;font-weight:700;color:var(--text-secondary);">
        Detected Columns (click to check if something looks off, like a missing Barangay)
      </summary>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
        <?php foreach ($fieldLabels as $field => $label):
          $found = isset($colMap[$field]);
        ?>
          <span class="badge <?= $found ? 'green' : 'gray' ?>" title="<?= $found ? ('Column ' . import_col_index_to_letter($colMap[$field])) : 'Not found in your file' ?>">
            <?= $found ? '✓' : '—' ?> <?= htmlspecialchars($label) ?><?= $found ? ' (Col ' . import_col_index_to_letter($colMap[$field]) . ')' : '' ?>
          </span>
        <?php endforeach; ?>
      </div>
      <?php if (!isset($colMap['barangay'])): ?>
        <p style="font-size:12px;color:var(--danger);margin-top:10px;">
          ⚠ No "Barangay" column was detected in this file - every imported record below will have a blank
          Barangay (won't show up on the Geographic Map). Check that your file has a column headed exactly
          "Barangay" (or "Brgy") in its first row, then re-upload.
        </p>
      <?php endif; ?>
    </details>

    <span class="panel-title">Records to Import (<?= count($preview['valid']) ?>)</span>

    <?php
      // Tally how many rows were auto-detected as each type, so we can
      // show a clear summary and pre-select the right option below.
      $typeCounts = ['birth' => 0, 'marriage' => 0, 'death' => 0];
      foreach ($preview['valid'] as $item) {
          $t = $item['record']['registry_type'] ?? 'birth';
          if (isset($typeCounts[$t])) $typeCounts[$t]++;
      }
      arsort($typeCounts);
      $majorityType = array_key_first($typeCounts);
      $majorityLabel = ucfirst($majorityType);
    ?>

    <div style="background:var(--bg-page);border:1px solid var(--border);border-radius:9px;padding:14px 16px;margin:10px 0 16px;">
      <label class="form-label" style="margin-bottom:6px;display:block;">Confirm Registry Type for this batch</label>
      <select name="registry_type_override" id="typeOverride" class="form-input" style="max-width:280px;" form="confirmForm">
        <option value="">Keep type detected per row</option>
        <option value="birth" <?= $majorityType === 'birth' ? 'selected' : '' ?>>Birth</option>
        <option value="marriage" <?= $majorityType === 'marriage' ? 'selected' : '' ?>>Marriage</option>
        <option value="death" <?= $majorityType === 'death' ? 'selected' : '' ?>>Death</option>
      </select>
      <p style="font-size:12px;color:var(--text-secondary);margin:8px 0 0;">
        Detected: <strong><?= $typeCounts[$majorityType] ?> of <?= count($preview['valid']) ?></strong> row(s) as
        <strong><?= $majorityLabel ?></strong>. Only change this if the type shown below is wrong for this file -
        selecting a type here applies it to <strong>every row</strong> in this batch so nothing gets mixed up.
      </p>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Registry No.</th><th>Full Name</th><th>Type</th><th>Barangay / Zone</th><th>Date of Event</th></tr>
      </thead>
      <tbody id="previewRows">
        <?php if (!$preview['valid']): ?>
          <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:30px;">No valid rows found.</td></tr>
        <?php else: foreach (array_slice($preview['valid'], 0, 50) as $item): $r = $item['record']; ?>
          <tr>
            <td><?= htmlspecialchars($r['registry_number'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['full_name'] ?: trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'])) ?></td>
            <td><span class="badge type-badge <?= $r['registry_type'] === 'birth' ? 'green' : ($r['registry_type'] === 'marriage' ? 'blue' : 'gray') ?>"><?= ucfirst($r['registry_type']) ?></span></td>
            <td><?= htmlspecialchars($r['barangay'] ?: '—') ?><?= $r['zone'] ? ' / ' . htmlspecialchars($r['zone']) : '' ?></td>
            <td><?= $r['date_of_event'] ? date('M d, Y', strtotime($r['date_of_event'])) : '—' ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
    <?php if (count($preview['valid']) > 50): ?>
      <p style="color:var(--text-muted);font-size:12px;margin-top:10px;">...and <?= count($preview['valid']) - 50 ?> more rows not shown here, but they will be imported.</p>
    <?php endif; ?>

    <script>
      document.getElementById('typeOverride').addEventListener('change', function () {
        const labels = { birth: 'Birth', marriage: 'Marriage', death: 'Death' };
        const classes = { birth: 'green', marriage: 'blue', death: 'gray' };
        const badges = document.querySelectorAll('#previewRows .type-badge');
        if (!this.value) return; // "Keep type detected per row" - leave badges as-is
        badges.forEach(b => {
          b.textContent = labels[this.value];
          b.classList.remove('green', 'blue', 'gray');
          b.classList.add(classes[this.value]);
        });
      });
    </script>

    <div style="display:flex;gap:10px;margin-top:20px;">
      <form method="POST" id="confirmForm">
        <input type="hidden" name="step" value="confirm">
        <button type="submit" class="btn btn-primary" <?= !$preview['valid'] ? 'disabled' : '' ?>>
          Save <?= count($preview['valid']) ?> Record<?= count($preview['valid']) === 1 ? '' : 's' ?>
        </button>
      </form>
      <form method="POST">
        <input type="hidden" name="step" value="cancel">
        <button type="submit" class="btn btn-outline">Start Over</button>
      </form>
      <a href="civil-registry.php" class="btn btn-outline">Cancel</a>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
