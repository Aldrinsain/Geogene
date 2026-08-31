<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Normalizes a name for matching: uppercase, letters+spaces only, single
 * spaces. Every relative lookup on this page goes through this so
 * "juan dela cruz", "JUAN DELA CRUZ", and "Juan  Dela   Cruz" all match.
 */
function ft_normalize(string $s): string
{
    $s = strtoupper($s);
    $s = preg_replace('/[^A-Z\s]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function ft_full_name(array $r): string
{
    if (!empty($r['full_name'])) return $r['full_name'];
    return trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
}

// ---------- Load ALL civil registry records once (small dataset, simplest + fastest) ----------
$allRecords = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();

$nameIndex = []; // normalized full name -> record (first match wins)
foreach ($allRecords as $r) {
    $key = ft_normalize(ft_full_name($r));
    if ($key !== '' && !isset($nameIndex[$key])) {
        $nameIndex[$key] = $r;
    }
}

function ft_find_by_name(array $nameIndex, ?string $name)
{
    if (!$name) return null;
    $key = ft_normalize($name);
    return $nameIndex[$key] ?? null;
}

// ---------- Search ----------
$search = trim($_GET['q'] ?? '');
$searchResults = [];
if ($search !== '') {
    $searchNorm = ft_normalize($search);
    foreach ($allRecords as $r) {
        if ($searchNorm !== '' && str_contains(ft_normalize(ft_full_name($r)), $searchNorm)) {
            $searchResults[] = $r;
            if (count($searchResults) >= 8) break;
        }
    }
}

// ---------- Build the family graph for the selected person ----------
$selfRecord = null;
$tree = null;

if (isset($_GET['record_id'])) {
    $rid = (int) $_GET['record_id'];
    foreach ($allRecords as $r) {
        if ((int) $r['record_id'] === $rid) { $selfRecord = $r; break; }
    }
}

if ($selfRecord) {
    $selfName = ft_normalize(ft_full_name($selfRecord));

    $father = ft_find_by_name($nameIndex, $selfRecord['father_name'] ?? null);
    $mother = ft_find_by_name($nameIndex, $selfRecord['mother_name'] ?? null);
    $spouse = ft_find_by_name($nameIndex, $selfRecord['spouse_name'] ?? null);

    // Siblings: other records sharing BOTH parents (only when both are on file)
    $siblings = [];
    $selfFatherNorm = ft_normalize($selfRecord['father_name'] ?? '');
    $selfMotherNorm = ft_normalize($selfRecord['mother_name'] ?? '');
    if ($selfFatherNorm && $selfMotherNorm) {
        foreach ($allRecords as $r) {
            if ((int) $r['record_id'] === (int) $selfRecord['record_id']) continue;
            if (ft_normalize($r['father_name'] ?? '') === $selfFatherNorm && ft_normalize($r['mother_name'] ?? '') === $selfMotherNorm) {
                $siblings[] = $r;
            }
        }
    }

    // Children: other records that list self as father or mother
    $children = [];
    foreach ($allRecords as $r) {
        if ((int) $r['record_id'] === (int) $selfRecord['record_id']) continue;
        if (ft_normalize($r['father_name'] ?? '') === $selfName || ft_normalize($r['mother_name'] ?? '') === $selfName) {
            $children[] = $r;
        }
    }

    // Helper: given a parent's own record, find grandparents + that parent's siblings (aunts/uncles)
    $sideOf = function (?array $parentRecord) use ($allRecords, $nameIndex) {
        if (!$parentRecord) return ['grandfather' => null, 'grandmother' => null, 'siblings' => []];

        $gf = ft_find_by_name($nameIndex, $parentRecord['father_name'] ?? null);
        $gm = ft_find_by_name($nameIndex, $parentRecord['mother_name'] ?? null);

        $pFatherNorm = ft_normalize($parentRecord['father_name'] ?? '');
        $pMotherNorm = ft_normalize($parentRecord['mother_name'] ?? '');
        $pNorm = ft_normalize(ft_full_name($parentRecord));

        $sibs = [];
        if ($pFatherNorm && $pMotherNorm) {
            foreach ($allRecords as $r) {
                if (ft_normalize(ft_full_name($r)) === $pNorm) continue;
                if (ft_normalize($r['father_name'] ?? '') === $pFatherNorm && ft_normalize($r['mother_name'] ?? '') === $pMotherNorm) {
                    $sibs[] = $r;
                }
            }
        }

        return ['grandfather' => $gf, 'grandmother' => $gm, 'siblings' => $sibs];
    };

    $paternal = $sideOf($father);
    $maternal = $sideOf($mother);

    $tree = [
        'self' => $selfRecord, 'father' => $father, 'mother' => $mother, 'spouse' => $spouse,
        'siblings' => $siblings, 'children' => $children,
        'paternal' => $paternal, 'maternal' => $maternal,
    ];
}

$pageTitle = 'Generate Family Tree';
$pageSub = 'Search a person to view or generate their family lineage - powered by Civil Registry Records.';
$activeStaffNav = 'tree';
require_once __DIR__ . '/includes/staff-header.php';
?>

<style>
  @media print {
    .sidebar, .topbar, .ft-search-panel { display:none !important; }
  }

  .ft-node{
    display:flex; flex-direction:column; align-items:center; gap:6px;
    padding:10px 12px; border-radius:12px; min-width:110px; text-align:center;
    background:#fff; border:1.5px solid var(--border);
  }
  .ft-node.self{ background:var(--primary-light); border-color:var(--primary); }
  .ft-node.unmapped{ background:#f7f7f7; border-style:dashed; color:var(--text-muted); }
  .ft-avatar{
    width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:14px; background:var(--primary-light); color:var(--primary); flex-shrink:0;
  }
  .ft-node.self .ft-avatar{ background:var(--primary); color:#fff; }
  .ft-node.unmapped .ft-avatar{ background:#e6e6e6; color:#999; }
  .ft-name{ font-size:12px; font-weight:700; color:var(--text-main); line-height:1.3; }
  .ft-node.unmapped .ft-name{ color:var(--text-muted); font-weight:600; }
  .ft-role{ font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.03em; }

  .ft-tier{ display:flex; justify-content:center; align-items:flex-start; gap:34px; flex-wrap:wrap; }
  .ft-couple{ display:flex; align-items:center; gap:10px; }
  .ft-couple .plus{ color:var(--text-muted); font-size:13px; font-weight:700; }

  .ft-connector{ width:2px; background:var(--border); margin:0 auto; }
  .ft-vline{ height:22px; }
  .ft-side{ display:flex; flex-direction:column; align-items:center; }

  .ft-hline-wrap{ position:relative; display:flex; justify-content:center; padding-top:22px; }
  .ft-hline{
    position:absolute; top:0; left:50%; transform:translateX(-50%);
    height:2px; background:var(--border);
  }

  .ft-group-title{ font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.04em; margin-bottom:12px; text-align:center; }
</style>

<div class="panel ft-search-panel">
  <form method="GET" class="table-toolbar">
    <div class="search-box">
      <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" name="q" placeholder="Search full name..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
  </form>

  <?php if ($search && $searchResults): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
      <?php foreach ($searchResults as $p): ?>
        <a href="?q=<?= urlencode($search) ?>&record_id=<?= $p['record_id'] ?>" class="btn btn-outline btn-sm">
          <?= htmlspecialchars(ft_full_name($p)) ?>
          <span class="badge <?= $p['registry_type'] === 'birth' ? 'green' : ($p['registry_type'] === 'marriage' ? 'blue' : 'gray') ?>" style="margin-left:6px;"><?= ucfirst($p['registry_type']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php elseif ($search): ?>
    <p style="font-size:12.5px;color:var(--text-muted);margin-top:10px;">No matching person was found in Civil Registry Records for that name.</p>
  <?php endif; ?>
</div>

<?php if ($tree): ?>
<?php
  function ftNode($record, $role = '', $isSelf = false) {
      if (!$record) {
          echo '<div class="ft-node unmapped"><div class="ft-avatar">?</div><div class="ft-name">Not on file</div>' .
               ($role ? '<div class="ft-role">' . htmlspecialchars($role) . '</div>' : '') . '</div>';
          return;
      }
      $name = ft_full_name($record);
      $initial = strtoupper(substr($name, 0, 1) ?: '?');
      $link = '?q=' . urlencode($search) . '&record_id=' . $record['record_id'];
      echo '<a href="' . $link . '" style="text-decoration:none;">
              <div class="ft-node ' . ($isSelf ? 'self' : '') . '">
                <div class="ft-avatar">' . $initial . '</div>
                <div class="ft-name">' . htmlspecialchars($name) . '</div>' .
                ($role ? '<div class="ft-role">' . htmlspecialchars($role) . '</div>' : '') .
              '</div>
            </a>';
  }
?>

<div class="panel">
  <div class="panel-header">
    <span class="panel-title">Family Tree: <?= htmlspecialchars(ft_full_name($tree['self'])) ?></span>
    <button class="btn btn-outline btn-sm" onclick="window.print()">🖨 Print / Export Tree</button>
  </div>

  <div style="overflow-x:auto;padding:10px 0 6px;">
  <div style="display:flex;flex-direction:column;align-items:center;gap:0;min-width:640px;">

    <?php $hasPaternal = $tree['paternal']['grandfather'] || $tree['paternal']['grandmother'];
          $hasMaternal = $tree['maternal']['grandfather'] || $tree['maternal']['grandmother']; ?>

    <?php if ($hasPaternal || $hasMaternal): ?>
      <!-- TIER 1: Grandparents (paternal + maternal side by side) -->
      <div class="ft-tier" style="margin-bottom:0;">
        <?php if ($hasPaternal): ?>
          <div class="ft-side">
            <div class="ft-group-title">Paternal Grandparents</div>
            <div class="ft-couple">
              <?php ftNode($tree['paternal']['grandfather'], 'Grandfather'); ?>
              <span class="plus">+</span>
              <?php ftNode($tree['paternal']['grandmother'], 'Grandmother'); ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($hasMaternal): ?>
          <div class="ft-side">
            <div class="ft-group-title">Maternal Grandparents</div>
            <div class="ft-couple">
              <?php ftNode($tree['maternal']['grandfather'], 'Grandfather'); ?>
              <span class="plus">+</span>
              <?php ftNode($tree['maternal']['grandmother'], 'Grandmother'); ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="ft-connector ft-vline"></div>
    <?php endif; ?>

    <!-- TIER 2: Father's generation (Father + his siblings) and Mother's generation (Mother + her siblings) -->
    <div class="ft-tier">
      <?php if ($tree['father'] || $tree['paternal']['siblings']): ?>
        <div class="ft-side">
          <div class="ft-group-title">Father's Side</div>
          <div style="display:flex;gap:16px;">
            <?php ftNode($tree['father'], 'Father'); ?>
            <?php foreach ($tree['paternal']['siblings'] as $aunt_uncle): ?>
              <?php ftNode($aunt_uncle, 'Uncle/Aunt'); ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($tree['mother'] || $tree['maternal']['siblings']): ?>
        <div class="ft-side">
          <div class="ft-group-title">Mother's Side</div>
          <div style="display:flex;gap:16px;">
            <?php ftNode($tree['mother'], 'Mother'); ?>
            <?php foreach ($tree['maternal']['siblings'] as $aunt_uncle): ?>
              <?php ftNode($aunt_uncle, 'Uncle/Aunt'); ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($tree['father'] || $tree['mother']): ?>
      <div class="ft-connector ft-vline"></div>
      <!-- TIER 3: Father + Mother as a couple -->
      <div class="ft-couple">
        <?php ftNode($tree['father'], 'Father'); ?>
        <span class="plus">+</span>
        <?php ftNode($tree['mother'], 'Mother'); ?>
      </div>
      <div class="ft-connector ft-vline"></div>
    <?php endif; ?>

    <!-- TIER 4: Me + Siblings (+ Spouse if married) -->
    <div class="ft-tier">
      <?php foreach ($tree['siblings'] as $sib): ?>
        <?php ftNode($sib, 'Sibling'); ?>
      <?php endforeach; ?>
      <?php ftNode($tree['self'], '', true); ?>
      <?php if ($tree['spouse']): ?>
        <span class="plus" style="align-self:center;">+</span>
        <?php ftNode($tree['spouse'], 'Spouse'); ?>
      <?php endif; ?>
    </div>

    <?php if ($tree['children']): ?>
      <div class="ft-connector ft-vline"></div>
      <div class="ft-tier">
        <?php foreach ($tree['children'] as $child): ?>
          <?php ftNode($child, 'Child'); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$hasPaternal && !$hasMaternal && !$tree['father'] && !$tree['mother'] && !$tree['siblings'] && !$tree['children'] && !$tree['spouse']): ?>
      <p style="font-size:12.5px;color:var(--text-muted);padding:20px 0;">
        No family relationships found on file yet for this person (Father's Name / Mother's Name fields are
        blank, or their relatives haven't been imported into Civil Registry Records).
      </p>
    <?php endif; ?>

  </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
