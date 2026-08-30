<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$activeNav = 'tree';

$stmt = $pdo->prepare("SELECT * FROM persons WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$self = $stmt->fetch();

$parents = $grandparents = $children = $spouses = $siblings = [];

if ($self) {
    $selfId = $self['person_id'];

    $fetchByType = function ($fromId, $type) use ($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.* FROM relationships r
            JOIN persons p ON p.person_id = r.related_person_id
            WHERE r.person_id = ? AND r.relationship_type = ?
        ");
        $stmt->execute([$fromId, $type]);
        return $stmt->fetchAll();
    };

    $parents  = $fetchByType($selfId, 'parent');
    $children = $fetchByType($selfId, 'child');
    $spouses  = $fetchByType($selfId, 'spouse');
    $siblings = $fetchByType($selfId, 'sibling');

    // Grandparents = parents of parents
    foreach ($parents as $p) {
        $grandparents = array_merge($grandparents, $fetchByType($p['person_id'], 'parent'));
    }
}

function nodeHtml($p, $gen, $rel, $isSelf = false) {
    $initial = strtoupper(substr($p['first_name'], 0, 1));
    $name = htmlspecialchars($p['first_name'] . ' ' . $p['last_name']);
    $cls = $isSelf ? 'tree-node self' : 'tree-node';
    echo '<div class="' . $cls . '" data-gen="' . $gen . '" data-rel="' . $rel . '">
            <div class="tree-avatar">' . $initial . '</div>
            <div class="tree-name">' . $name . '</div>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Family Tree</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
  .phone{padding-bottom:0;}
  .content-scroll{padding-bottom:70px;max-height:640px;overflow:auto;}
  .app-header{justify-content:space-between;}
  .filter-icon{font-size:16px;color:var(--text-main);cursor:pointer;}
  .search-bar{position:relative;margin:14px 0 20px;}
  .search-bar input{padding-left:36px;}
  .search-bar .s-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);}

  /* ---------- TREE DIAGRAM ---------- */
  .tree-diagram{display:flex;flex-direction:column;align-items:center;gap:0;min-width:340px;}
  .tree-tier{display:flex;justify-content:center;gap:28px;position:relative;padding:14px 0;}
  .tree-tier.pair{gap:50px;}

  .tree-node{
    display:flex;flex-direction:column;align-items:center;gap:6px;
    width:64px;text-align:center;position:relative;z-index:2;
  }
  .tree-avatar{
    width:44px;height:44px;border-radius:50%;
    background:#26315c;color:var(--text-muted);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:15px;border:2px solid var(--blue-light);box-shadow:0 1px 6px rgba(0,0,0,0.3);
  }
  .tree-node.self .tree-avatar{background:var(--primary);color:#fff;}
  .tree-name{font-size:10.5px;font-weight:600;color:var(--text-main);line-height:1.2;}

  /* connector lines using absolutely positioned divs */
  .connector-v{width:2px;background:#2a3868;margin:0 auto;}
  .connector-h{height:2px;background:#2a3868;}
  .tier-connect{position:relative;height:24px;width:100%;}
  .tier-connect .stem{position:absolute;top:0;width:2px;background:#2a3868;height:100%;}
  .tier-connect .bar{position:absolute;top:0;height:2px;background:#2a3868;}

  .zoom-bar {
    position: absolute;
    bottom: 105px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--primary);
    color: #fff;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 6px 16px;
    font-size: 13px;
    font-weight: 600;
    z-index: 10;
}

.zoom-bar button {
    background: none;
    border: none;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    padding: 0 4px;
}

  .empty-state{text-align:center;padding:60px 10px;color:var(--text-secondary);}
  .empty-state .e-icon{font-size:40px;margin-bottom:10px;}

  /* ---------- FILTER MODAL ---------- */
  .modal-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,0.35);
    display:none;align-items:center;justify-content:center;z-index:2000;
  }
  .modal-overlay.active{display:flex;}
  .filter-modal{
    background:var(--blue-light);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:20px;backdrop-filter:blur(20px);color:var(--text-main);
    width:88%;max-width:320px;max-height:80vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,0.5);
  }
  .filter-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
  .filter-modal-header h3{margin:0;font-size:16px;font-weight:700;}
  .filter-modal-header .close-x{cursor:pointer;font-size:16px;color:var(--text-muted);}
  .filter-section-label{font-size:12px;font-weight:700;color:var(--text-main);margin:14px 0 8px;}
  .filter-check-row{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:13px;color:var(--text-main);}
  .filter-check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--green);}
  .filter-modal-actions{display:flex;gap:10px;margin-top:18px;}
  .filter-modal-actions button{flex:1;margin-top:0;}
</style>
</head>
<body>

<div class="phone">
  <div class="content-scroll">
    <div class="app-header"><span>Family Tree</span><span class="filter-icon" onclick="openFilterModal()">⚙</span></div>

    <div class="search-bar">
      <span class="s-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" id="filterInput" class="form-input" placeholder="Search name...">
    </div>

    <?php if (!$self || (!$parents && !$children && !$spouses && !$siblings)): ?>
      <div class="empty-state">
        <div class="e-icon"></div>
        <p>No family members found.</p>
      </div>
    <?php else: ?>

      <div id="treeWrap" style="overflow-x:auto;">
        <div class="tree-diagram">

          <?php if ($grandparents): ?>
            <div class="tree-tier pair">
              <?php foreach (array_slice($grandparents, 0, 2) as $g) nodeHtml($g, 3, 'grandparents'); ?>
            </div>
            <div class="connector-v" style="height:16px;"></div>
          <?php endif; ?>

          <?php if ($parents): ?>
            <div class="tree-tier pair">
              <?php foreach (array_slice($parents, 0, 2) as $p) nodeHtml($p, 2, 'parents'); ?>
            </div>
            <div class="connector-v" style="height:16px;"></div>
          <?php endif; ?>

          <div class="tree-tier" style="gap:22px;">
            <?php foreach ($siblings as $s) nodeHtml($s, 1, 'siblings'); ?>
            <?php nodeHtml($self, 1, 'self', true); ?>
            <?php foreach ($spouses as $sp) nodeHtml($sp, 1, 'spouse'); ?>
          </div>

          <?php if ($children): ?>
            <div class="connector-v" style="height:16px;"></div>
            <div class="tree-tier" style="gap:22px;">
              <?php foreach ($children as $c) nodeHtml($c, 0, 'children'); ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

    <?php endif; ?>

    
  </div>

  <div class="zoom-bar" >
    <button onclick="zoom(-10)">−</button>
    <span id="zoomLabel">100%</span>
    <button onclick="zoom(10)">+</button>
  </div>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

<!-- FILTER MODAL -->
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