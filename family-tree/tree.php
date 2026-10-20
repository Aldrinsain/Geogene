<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules (Given Middle Surname, Mixed Case, order-insensitive match)

// Never let the browser (or any proxy) cache a stale copy of this page -
// genealogy data changes, and old cached copies could show an outdated
// tree layout.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$userId = $_SESSION['user_id'];
$activeNav = 'tree';

// Matching ignores CASE and ORDER: "Abao, Xander Sarzosa" (older records),
// "XANDER SARZOSA ABAO" and "Xander Sarzosa Abao" are the same person.
function ft_normalize(string $s): string
{
    return gg_name_key($s);
}

// Displayed as "Given Middle Surname" in Mixed Case.
function ft_full_name(array $r): string
{
    $fromParts = formatFullName($r['last_name'] ?? '', $r['first_name'] ?? '', $r['middle_name'] ?? '');
    if ($fromParts !== '') return $fromParts;
    return gg_display_name($r['full_name'] ?? '');
}

$stmt = $pdo->prepare("SELECT matched_record_id, full_name, identity_verified FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$account = $stmt->fetch();

$selfRecord = null;
if ($account) {
    if (!empty($account['matched_record_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
        $stmt->execute([$account['matched_record_id']]);
        $selfRecord = $stmt->fetch() ?: null;
    }
    if (!$selfRecord && !empty($account['identity_verified']) && !empty($account['full_name'])) {
        $wanted = ft_normalize($account['full_name']);
        $all = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();
        foreach ($all as $r) {
            if (ft_normalize(ft_full_name($r)) === $wanted) { $selfRecord = $r; break; }
        }
    }
}

$tree = null;

if ($selfRecord) {
    $allRecords = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();
    $nameIndex = [];
    foreach ($allRecords as $r) {
        $key = ft_normalize(ft_full_name($r));
        if ($key !== '' && !isset($nameIndex[$key])) $nameIndex[$key] = $r;
    }
    $ft_find_by_name = function (?string $name) use ($nameIndex) {
        if (!$name) return null;
        $key = ft_normalize($name);
        return $nameIndex[$key] ?? null;
    };
    $ft_virtual = function (?string $nameText) {
        $nameText = trim((string) $nameText);
        if ($nameText === '') return null;
        return ['record_id' => null, 'full_name' => $nameText, 'virtual' => true];
    };

    $selfName = ft_normalize(ft_full_name($selfRecord));

    $father = $ft_find_by_name($selfRecord['father_name'] ?? null) ?? $ft_virtual($selfRecord['father_name'] ?? null);
    $mother = $ft_find_by_name($selfRecord['mother_name'] ?? null) ?? $ft_virtual($selfRecord['mother_name'] ?? null);

    $marriages = [];
    foreach ($allRecords as $r) {
        if (($r['registry_type'] ?? '') !== 'marriage') continue;
        $rFullNorm = ft_normalize(ft_full_name($r));
        $rSpouseNorm = ft_normalize($r['spouse_name'] ?? '');
        $partnerName = null;
        if ($rFullNorm === $selfName) {
            $partnerName = $r['spouse_name'] ?? null;
        } elseif ($rSpouseNorm !== '' && $rSpouseNorm === $selfName) {
            $partnerName = ft_full_name($r);
        }
        if ($partnerName === null || trim($partnerName) === '') continue;
        $partnerRecord = $ft_find_by_name($partnerName) ?? $ft_virtual($partnerName);
        if (!$partnerRecord) continue;
        $marriages[] = ['record' => $partnerRecord, 'date' => $r['date_of_event'] ?? null];
    }
    $spouseFallback = $ft_find_by_name($selfRecord['spouse_name'] ?? null) ?? $ft_virtual($selfRecord['spouse_name'] ?? null);
    if (!$marriages && $spouseFallback) $marriages[] = ['record' => $spouseFallback, 'date' => null];
    $seenPartner = [];
    $allSpouses = [];
    foreach ($marriages as $m) {
        $key = ft_normalize(ft_full_name($m['record']));
        if ($key === '' || isset($seenPartner[$key])) continue;
        $seenPartner[$key] = true;
        $allSpouses[] = $m;
    }
    usort($allSpouses, function ($a, $b) {
        if ($a['date'] === $b['date']) return 0;
        if (!$a['date']) return 1;
        if (!$b['date']) return -1;
        return strcmp($b['date'], $a['date']);
    });
    foreach ($allSpouses as $i => &$m) { $m['role'] = $i === 0 ? 'Spouse' : 'Ex-Spouse'; }
    unset($m);

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

    $children = [];
    foreach ($allRecords as $r) {
        if (ft_normalize($r['father_name'] ?? '') === $selfName || ft_normalize($r['mother_name'] ?? '') === $selfName) {
            $children[] = $r;
        }
    }

    $sideOf = function (?array $parentRecord) use ($allRecords, $ft_find_by_name, $ft_virtual) {
        if (!$parentRecord || !empty($parentRecord['virtual'])) {
            return ['grandfather' => null, 'grandmother' => null, 'siblings' => []];
        }
        $gf = $ft_find_by_name($parentRecord['father_name'] ?? null) ?? $ft_virtual($parentRecord['father_name'] ?? null);
        $gm = $ft_find_by_name($parentRecord['mother_name'] ?? null) ?? $ft_virtual($parentRecord['mother_name'] ?? null);

        $pFatherNorm = ft_normalize($parentRecord['father_name'] ?? '');
        $pMotherNorm = ft_normalize($parentRecord['mother_name'] ?? '');
        $sibs = [];
        if ($pFatherNorm && $pMotherNorm) {
            foreach ($allRecords as $r) {
                if ((int) $r['record_id'] === (int) $parentRecord['record_id']) continue;
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
        'self' => $selfRecord, 'father' => $father, 'mother' => $mother, 'allSpouses' => $allSpouses,
        'siblings' => $siblings, 'children' => $children,
        'paternal' => $paternal, 'maternal' => $maternal,
    ];
}

function ftNode($record, $role = '', $isSelf = false, $gen = 1, $rel = '') {
    static $seq = 0;
    $seq++;
    $id = 'ftn' . $seq;

    if (!$record) {
        echo '<div class="tree-node unmapped" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
                <div class="tree-avatar">?</div>
                <div class="tree-name">Not on file</div>' .
                ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '') .
              '</div>';
        return $id;
    }

    $name = ft_full_name($record);
    $initial = strtoupper(substr($name, 0, 1) ?: '?');

    if (!empty($record['virtual'])) {
        echo '<div class="tree-node unmapped" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
                <div class="tree-avatar">' . $initial . '</div>
                <div class="tree-name">' . htmlspecialchars($name) . '</div>' .
                ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '') .
                '<div class="tree-role">(name on file only)</div>
              </div>';
        return $id;
    }

    $cls = $isSelf ? 'tree-node self' : 'tree-node';
    echo '<div class="' . $cls . '" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
            <div class="tree-avatar">' . $initial . '</div>
            <div class="tree-name">' . htmlspecialchars($name) . '</div>' .
            ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '') .
          '</div>';
    return $id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Family Tree</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .phone{padding-bottom:0;overflow:visible;min-height:100dvh;}
  .content-scroll{padding-bottom:90px;}
  /* Pin this to the real device screen (not just the ".phone" box) so the
     page underneath can grow taller than one screen and scroll normally,
     while the bottom nav stays floating at the bottom the whole time. */
  .bottom-nav{position:fixed;bottom:14px;left:14px;right:14px;}
  .app-header{justify-content:space-between;}
  .filter-icon{font-size:16px;color:var(--text-main);cursor:pointer;}

  .tree-diagram{display:flex;flex-direction:column;align-items:center;gap:34px;min-width:340px;position:relative;z-index:1;}
  .tree-tier{display:flex;justify-content:center;align-items:flex-start;gap:22px;position:relative;flex-wrap:wrap;}
  .tree-side{display:flex;flex-direction:column;align-items:center;}
  .tree-group-title{font-size:9.5px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;text-align:center;}
  .tree-couple{display:flex;align-items:center;gap:8px;}
  .tree-couple .plus{color:var(--text-muted);font-size:12px;font-weight:700;}

  .tree-node{
    display:flex;flex-direction:column;align-items:center;gap:6px;
    width:66px;text-align:center;position:relative;z-index:2;
  }
  .tree-avatar{
    width:44px;height:44px;border-radius:50%;
    background:var(--blue-lighter);color:var(--text-muted);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:15px;border:2px solid var(--border);box-shadow:0 1px 6px rgba(0,0,0,0.15);
  }
  .tree-node.self .tree-avatar{background:var(--primary);color:#fff;}
  .tree-name{font-size:10px;font-weight:600;color:var(--text-main);line-height:1.2;}
  .tree-role{font-size:8.5px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.02em;}
  .tree-node.unmapped .tree-avatar{background:var(--bg-page);border-color:var(--border);color:var(--text-muted);}
  .tree-node.unmapped .tree-name{color:var(--text-muted);}

  .tree-connectors{position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;overflow:visible;z-index:0;}

  #treeWrap{
    background:var(--blue-light);border:1px solid var(--border);border-radius:20px;
    padding:20px 16px;box-shadow: 0 4px 5px rgba(0,0,0,0.14), 0 6px 10px rgba(0,0,0,0.12);
  }

  .zoom-bar {
    position: fixed; bottom: 105px; left: 50%; transform: translateX(-50%);
    background: var(--primary); color: #fff; border-radius: 20px;
    display: flex; align-items: center; gap: 14px; padding: 6px 16px;
    font-size: 13px; font-weight: 600; z-index: 10;
  }
  .zoom-bar button { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; padding: 0 4px; }

  .empty-state{text-align:center;padding:60px 16px;color:var(--text-secondary);font-size:13px;line-height:1.6;}
  .empty-state .e-icon{font-size:34px;margin-bottom:12px;}

  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.35);display:none;align-items:center;justify-content:center;z-index:2000;}
  .modal-overlay.active{display:flex;}
  .filter-modal{
    background:var(--blue-light);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:20px;backdrop-filter:blur(20px);color:var(--text-main);
    width:88%;max-width:320px;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.5);
  }
  .filter-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
  .filter-modal-header h3{margin:0;font-size:16px;font-weight:700;}
  .filter-modal-header .close-x{cursor:pointer;font-size:16px;color:var(--text-muted);}
  .filter-section-label{font-size:12px;font-weight:700;color:var(--text-main);margin:14px 0 8px;}
  .filter-check-row{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:13px;color:var(--text-main);}
  .filter-check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--green);}
  .filter-modal-actions{display:flex;gap:10px;margin-top:18px;}
  .filter-modal-actions button{flex:1;margin-top:0;}
  .e-state{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;

    min-height:300px; /* adjust kung kinahanglan */
}

.e-icon{
    margin-bottom:20px;
}

.e-icon svg,
.e-icon img{
    width:48px;
    height:48px;
}
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="content-scroll">
    <div class="app-header"><span>Family Tree</span><span class="filter-icon" onclick="openFilterModal()">⚙</span></div>

    <?php if (!$tree): ?>
      <div class="empty-state">
        <div class="e-icon"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Layer_1" style="" version="1.1" viewBox="0 0 100.353 100.352" xml:space="preserve" preserveAspectRatio="xMidYMid meet"><g ><path d="M58.23,69.992l14.993-24.108c0.049-0.078,0.09-0.16,0.122-0.245c2.589-4.222,3.956-9.045,3.956-13.969   c0-14.772-12.018-26.79-26.79-26.79S23.72,16.898,23.72,31.67c0,4.925,1.369,9.75,3.96,13.975c0.03,0.074,0.065,0.146,0.107,0.216   l14.455,24.191c-11.221,1.586-18.6,6.2-18.6,11.797c0,6.935,11.785,12.366,26.829,12.366S77.3,88.783,77.3,81.849   C77.301,76.226,69.578,71.509,58.23,69.992z M30.373,44.294c-2.39-3.804-3.653-8.169-3.653-12.624   c0-13.118,10.672-23.79,23.791-23.79c13.118,0,23.79,10.672,23.79,23.79c0,4.457-1.263,8.822-3.652,12.624   c-0.05,0.08-0.091,0.163-0.124,0.249L54.685,70.01c-0.238,0.365-0.285,0.448-0.576,0.926l-4,6.432L30.507,44.564   C30.472,44.471,30.427,44.38,30.373,44.294z M50.472,91.215c-14.043,0-23.829-4.937-23.829-9.366c0-4.02,7.37-7.808,17.283-8.981   l4.87,8.151c0.269,0.449,0.751,0.726,1.274,0.73c0.004,0,0.009,0,0.013,0c0.518,0,1-0.268,1.274-0.708l5.12-8.232   C66.548,73.9,74.3,77.784,74.3,81.849C74.301,86.279,64.515,91.215,50.472,91.215z" style="fill:#10B981"/><path d="M60.213,31.67c0-5.371-4.37-9.741-9.741-9.741s-9.741,4.37-9.741,9.741s4.37,9.741,9.741,9.741   C55.843,41.411,60.213,37.041,60.213,31.67z M43.731,31.67c0-3.717,3.024-6.741,6.741-6.741s6.741,3.024,6.741,6.741   s-3.023,6.741-6.741,6.741S43.731,35.387,43.731,31.67z" style="fill:#10B981"/></g></svg></div>
        <p>Your account isn't linked to a Civil Registry record yet, so we can't show your family tree.
        Complete identity verification to unlock this.</p>
      </div>
    <?php else: ?>

      <div id="treeWrap" style="overflow-x:auto;position:relative;">
        <svg class="tree-connectors"></svg>
        <div class="tree-diagram">

          <?php
            $ftEdges = [];
            $idSelf = $idFather = $idMother = null;
            $idPatGF = $idPatGM = $idMatGF = $idMatGM = null;
            $idSibs = $idPatSibs = $idMatSibs = [];
            $spouseNodes = [];
            $hasPaternal = $tree['paternal']['grandfather'] || $tree['paternal']['grandmother'];
            $hasMaternal = $tree['maternal']['grandfather'] || $tree['maternal']['grandmother'];
          ?>

          <!-- TIER 1 (top): the person being searched - shown FIRST, with
               their ancestors fanning out below (Parents, then
               Grandparents), like a standard ancestor/pedigree chart. -->
          <div class="tree-tier">
            <?php foreach ($tree['siblings'] as $sib): ?>
              <?php $idSibs[] = ftNode($sib, 'Sibling', false, 1, 'siblings'); ?>
            <?php endforeach; ?>
            <?php $idSelf = ftNode($tree['self'], '', true, 1, 'self'); ?>
            <?php foreach ($tree['allSpouses'] as $m): ?>
              <span class="plus" style="align-self:center;">+</span>
              <?php $spId = ftNode($m['record'], $m['role'], false, 1, 'spouse'); ?>
              <?php $spouseNodes[] = ['id' => $spId, 'record' => $m['record']]; ?>
            <?php endforeach; ?>
          </div>
          <?php foreach ($spouseNodes as $sp) $ftEdges[] = ['type' => 'couple', 'a' => $idSelf, 'b' => $sp['id']]; ?>

          <?php if ($tree['father'] || $tree['mother'] || $tree['paternal']['siblings'] || $tree['maternal']['siblings']): ?>
            <div class="tree-tier">
              <?php foreach ($tree['paternal']['siblings'] as $au): ?>
                <?php $idPatSibs[] = ftNode($au, "Uncle/Aunt", false, 2, 'parents'); ?>
              <?php endforeach; ?>
              <?php if ($tree['father'] || $tree['mother']): ?>
                <div class="tree-couple">
                  <?php $idFather = ftNode($tree['father'], 'Father', false, 2, 'parents'); ?>
                  <?php if ($tree['father'] && $tree['mother']): ?><span class="plus">+</span><?php endif; ?>
                  <?php $idMother = ftNode($tree['mother'], 'Mother', false, 2, 'parents'); ?>
                </div>
              <?php endif; ?>
              <?php foreach ($tree['maternal']['siblings'] as $au): ?>
                <?php $idMatSibs[] = ftNode($au, "Uncle/Aunt", false, 2, 'parents'); ?>
              <?php endforeach; ?>
            </div>
            <?php
              if ($tree['father'] && $tree['mother']) $ftEdges[] = ['type' => 'couple', 'a' => $idFather, 'b' => $idMother];
              // Self/Siblings render ABOVE, so they are "parents" of this
              // edge (visually above) and Father/Mother are the "children"
              // side (visually below) - keeps the connector pointing the
              // right way in this top-down layout.
              $selfGenIds = array_values(array_filter(array_merge([$idSelf], $idSibs)));
              if (($idFather || $idMother) && $selfGenIds) $ftEdges[] = ['type' => 'parent-child', 'parents' => $selfGenIds, 'children' => array_values(array_filter([$idFather, $idMother]))];
            ?>
          <?php endif; ?>

          <!-- TIER 3: Grandparents - oldest generation shown, renders LAST/lowest. -->
          <?php if ($hasPaternal || $hasMaternal): ?>
            <div class="tree-tier">
              <?php if ($hasPaternal): ?>
                <div class="tree-side">
                  <div class="tree-group-title">Paternal Grandparents</div>
                  <div class="tree-couple">
                    <?php $idPatGF = ftNode($tree['paternal']['grandfather'], 'Grandfather', false, 3, 'grandparents'); ?>
                    <span class="plus">+</span>
                    <?php $idPatGM = ftNode($tree['paternal']['grandmother'], 'Grandmother', false, 3, 'grandparents'); ?>
                  </div>
                </div>
              <?php endif; ?>
              <?php if ($hasMaternal): ?>
                <div class="tree-side">
                  <div class="tree-group-title">Maternal Grandparents</div>
                  <div class="tree-couple">
                    <?php $idMatGF = ftNode($tree['maternal']['grandfather'], 'Grandfather', false, 3, 'grandparents'); ?>
                    <span class="plus">+</span>
                    <?php $idMatGM = ftNode($tree['maternal']['grandmother'], 'Grandmother', false, 3, 'grandparents'); ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            <?php
              if ($idPatGF && $idPatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idPatGF, 'b' => $idPatGM];
              if ($idMatGF && $idMatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idMatGF, 'b' => $idMatGM];
              // Father's generation renders ABOVE the grandparents now, so
              // IT is the "parents" side (visually above) and grandparents
              // are the "children" side (visually below).
              $patChildren = array_values(array_filter(array_merge($idFather ? [$idFather] : [], $idPatSibs)));
              if (($idPatGF || $idPatGM) && $patChildren) $ftEdges[] = ['type' => 'parent-child', 'parents' => $patChildren, 'children' => array_values(array_filter([$idPatGF, $idPatGM]))];
              $matChildren = array_values(array_filter(array_merge($idMother ? [$idMother] : [], $idMatSibs)));
              if (($idMatGF || $idMatGM) && $matChildren) $ftEdges[] = ['type' => 'parent-child', 'parents' => $matChildren, 'children' => array_values(array_filter([$idMatGF, $idMatGM]))];
            ?>
          <?php endif; ?>

          <!-- Children (descendants) - unchanged: Self is still visually
               ABOVE their children, so this edge direction stays as-is. -->
          <?php if ($tree['children']): ?>
            <div class="tree-tier">
              <?php $idKids = []; ?>
              <?php foreach ($tree['children'] as $child): ?>
                <?php $idKids[] = ftNode($child, 'Child', false, 0, 'children'); ?>
              <?php endforeach; ?>
            </div>
            <?php
              $selfNameNorm = ft_normalize(ft_full_name($tree['self']));
              $kidsBySpouse = []; $kidsUnmatched = [];
              foreach ($tree['children'] as $i => $child) {
                  $otherParentName = ft_normalize($child['father_name'] ?? '') === $selfNameNorm
                      ? ft_normalize($child['mother_name'] ?? '') : ft_normalize($child['father_name'] ?? '');
                  $matchedSpouseId = null;
                  foreach ($spouseNodes as $sp) {
                      if ($otherParentName !== '' && ft_normalize(ft_full_name($sp['record'])) === $otherParentName) { $matchedSpouseId = $sp['id']; break; }
                  }
                  if ($matchedSpouseId) $kidsBySpouse[$matchedSpouseId][] = $idKids[$i];
                  else $kidsUnmatched[] = $idKids[$i];
              }
              foreach ($kidsBySpouse as $spId => $kidIds) $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf, $spId], 'children' => $kidIds];
              if ($kidsUnmatched) $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf], 'children' => $kidsUnmatched];
            ?>
          <?php endif; ?>

        </div>
      </div>

      <script>window.__ftEdges = <?= json_encode($ftEdges) ?>;</script>

    <?php endif; ?>

  </div>

  <?php if ($tree): ?>
  <div class="zoom-bar">
    <button onclick="zoom(-10)">−</button>
    <span id="zoomLabel">100%</span>
    <button onclick="zoom(10)">+</button>
  </div>
  <?php endif; ?>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

<div class="modal-overlay" id="filterModal">
  <div class="filter-modal">
    <div class="filter-modal-header">
      <h3>Filter Family Tree</h3>
      <span class="close-x" onclick="closeFilterModal()">✕</span>
    </div>

    <div class="filter-section-label">Generation</div>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="3" checked> Grandparents</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="2" checked> Parents</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="1" checked> You / Siblings / Spouse</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="0" checked> Children</label>

    <div class="filter-section-label">Relationship</div>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="parents" checked> Parents</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="grandparents" checked> Grandparents</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="siblings" checked> Siblings</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="children" checked> Children</label>

    <div class="filter-modal-actions">
      <button type="button" class="btn-outline" onclick="resetFilter()">Reset</button>
      <button type="button" class="btn-primary" onclick="applyFilter()">Apply Filter</button>
    </div>
  </div>
</div>

<script>
function openFilterModal() { document.getElementById('filterModal').classList.add('active'); }
function closeFilterModal() { document.getElementById('filterModal').classList.remove('active'); }

function drawFamilyTreeConnectors() {
  const container = document.getElementById('treeWrap');
  const svg = document.querySelector('.tree-connectors');
  if (!container || !svg || !window.__ftEdges) return;

  const containerRect = container.getBoundingClientRect();
  svg.setAttribute('width', container.scrollWidth);
  svg.setAttribute('height', container.scrollHeight);
  svg.innerHTML = '';

  const ns = 'http://www.w3.org/2000/svg';
  const STROKE = getComputedStyle(document.body).getPropertyValue('--text-muted').trim() || '#3a4a80';

  function nodeRect(id) {
    const el = container.querySelector('[data-ft-node="' + id + '"]');
    if (!el || el.offsetParent === null || el.style.display === 'none') return null;
    const r = el.getBoundingClientRect();
    return {
      left: r.left - containerRect.left, right: r.right - containerRect.left,
      top: r.top - containerRect.top, bottom: r.bottom - containerRect.top,
      centerX: r.left - containerRect.left + r.width / 2,
      centerY: r.top - containerRect.top + r.height / 2,
    };
  }

  function drawPath(d) {
    const p = document.createElementNS(ns, 'path');
    p.setAttribute('d', d);
    p.setAttribute('stroke', STROKE);
    p.setAttribute('stroke-width', '2');
    p.setAttribute('fill', 'none');
    svg.appendChild(p);
  }

  window.__ftEdges.forEach(edge => {
    if (edge.type === 'couple') {
      const a = nodeRect(edge.a), b = nodeRect(edge.b);
      if (!a || !b) return;
      const y = (a.centerY + b.centerY) / 2;
      const x1 = a.centerX < b.centerX ? a.right : a.left;
      const x2 = a.centerX < b.centerX ? b.left : b.right;
      drawPath(`M ${x1} ${y} L ${x2} ${y}`);
      return;
    }
    if (edge.type === 'parent-child') {
      const parentRects = edge.parents.map(nodeRect).filter(Boolean);
      const childRects = edge.children.map(nodeRect).filter(Boolean);
      if (!parentRects.length || !childRects.length) return;

      const originY = Math.max(...parentRects.map(r => r.bottom));
      const childTopY = Math.min(...childRects.map(r => r.top));
      const busY = originY + (childTopY - originY) / 2;

      // Bus spans however wide it needs to, to reach EVERY parent AND
      // every child - not just whichever side happens to have more than
      // one member (a generation of several siblings can sit on either
      // side of this edge, depending on the tier order).
      const allXs = parentRects.map(r => r.centerX).concat(childRects.map(r => r.centerX));
      const busLeft = Math.min(...allXs);
      const busRight = Math.max(...allXs);

      parentRects.forEach(p => drawPath(`M ${p.centerX} ${p.bottom} L ${p.centerX} ${busY}`));
      if (busRight > busLeft) drawPath(`M ${busLeft} ${busY} L ${busRight} ${busY}`);
      childRects.forEach(c => drawPath(`M ${c.centerX} ${busY} L ${c.centerX} ${c.top}`));
    }
  });
}

let __ftRedrawTimer = null;
function ftScheduleRedraw() {
  clearTimeout(__ftRedrawTimer);
  __ftRedrawTimer = setTimeout(drawFamilyTreeConnectors, 60);
}

function applyFilter() {
  const allowedGens = [...document.querySelectorAll('.gen-check:checked')].map(c => c.value);
  const allowedRels = [...document.querySelectorAll('.rel-check:checked')].map(c => c.value);

  document.querySelectorAll('.tree-node').forEach(node => {
    const gen = node.dataset.gen;
    const rel = node.dataset.rel;
    const genOk = rel === 'self' || allowedGens.includes(gen);
    const relOk = rel === 'self' || rel === 'spouse' || allowedRels.includes(rel);
    node.style.display = (genOk && relOk) ? '' : 'none';
  });

  closeFilterModal();
  drawFamilyTreeConnectors();
}

function resetFilter() {
  document.querySelectorAll('.gen-check, .rel-check').forEach(c => c.checked = true);
  document.querySelectorAll('.tree-node').forEach(node => node.style.display = '');
  drawFamilyTreeConnectors();
}

let zoomLevel = 100;
function zoom(delta) {
  zoomLevel = Math.max(50, Math.min(150, zoomLevel + delta));
  const label = document.getElementById('zoomLabel');
  if (label) label.textContent = zoomLevel + '%';
  const diagram = document.querySelector('.tree-diagram');
  if (diagram) diagram.style.transform = 'scale(' + (zoomLevel / 100) + ')';
  ftScheduleRedraw();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', drawFamilyTreeConnectors);
} else {
  drawFamilyTreeConnectors();
}
window.addEventListener('load', drawFamilyTreeConnectors);
window.addEventListener('resize', ftScheduleRedraw);
</script>
</body>
</html>
