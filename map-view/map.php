<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules

$userId = $_SESSION['user_id'];
$activeNav = 'map';

// ---------- Find the logged-in user's own Civil Registry record ----------
// (set once their account is matched/verified against MCRO records - see
// users.matched_record_id). Everything on this map - their own pin, plus
// family/relatives - is derived from Civil Registry Records, the SAME
// source and matching logic the staff Geographic Map uses (not the older
// persons/relationships tables this page used before).
$stmt = $pdo->prepare("SELECT matched_record_id, full_name, identity_verified FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$account = $stmt->fetch();

// Matching ignores CASE and ORDER ("Abao, Xander Sarzosa" == "XANDER SARZOSA ABAO").
function mv_normalize(string $s): string
{
    return gg_name_key($s);
}

// Displayed as "Given Middle Surname" in Mixed Case.
function mv_full_name(array $r): string
{
    $fromParts = formatFullName($r['last_name'] ?? '', $r['first_name'] ?? '', $r['middle_name'] ?? '');
    if ($fromParts !== '') return $fromParts;
    return gg_display_name($r['full_name'] ?? '');
}

$selfRecord = null;
if ($account) {
    if (!empty($account['matched_record_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
        $stmt->execute([$account['matched_record_id']]);
        $selfRecord = $stmt->fetch() ?: null;
    }
    // Fallback: accounts that got identity_verified=1 through a path that
    // never set matched_record_id (e.g. manual staff approval via
    // Verification Monitoring) still get auto-detected here, as long as
    // their account name matches a real Civil Registry record.
    if (!$selfRecord && !empty($account['identity_verified']) && !empty($account['full_name'])) {
        $wanted = mv_normalize($account['full_name']);
        $all = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();
        foreach ($all as $r) {
            if (mv_normalize(mv_full_name($r)) === $wanted) { $selfRecord = $r; break; }
        }
    }
}

// [ 'record' => ..., 'relation' => 'You'|'Father'|..., 'category' => 'self'|'immediate'|'extended' ]
$people = [];

if ($selfRecord) {
    $allRecords = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();
    $nameIndex = [];
    foreach ($allRecords as $r) {
        $key = mv_normalize(mv_full_name($r));
        if ($key !== '' && !isset($nameIndex[$key])) $nameIndex[$key] = $r;
    }
    $findByName = function (?string $name) use ($nameIndex) {
        if (!$name) return null;
        $key = mv_normalize($name);
        return $nameIndex[$key] ?? null;
    };

    $selfName = mv_normalize(mv_full_name($selfRecord));
    $people[] = ['record' => $selfRecord, 'relation' => 'You', 'category' => 'self'];

    $addedIds = [(int) $selfRecord['record_id']];
    $addPerson = function ($rec, $relation, $category) use (&$people, &$addedIds) {
        if (!$rec) return;
        $id = (int) ($rec['record_id'] ?? 0);
        if ($id && in_array($id, $addedIds, true)) return;
        if ($id) $addedIds[] = $id;
        $people[] = ['record' => $rec, 'relation' => $relation, 'category' => $category];
    };

    $addPerson($findByName($selfRecord['father_name'] ?? null), 'Father', 'immediate');
    $addPerson($findByName($selfRecord['mother_name'] ?? null), 'Mother', 'immediate');
    $addPerson($findByName($selfRecord['spouse_name'] ?? null), 'Spouse', 'immediate');

    // Siblings: share the same Father's Name + Mother's Name text as self.
    $selfFatherNorm = mv_normalize($selfRecord['father_name'] ?? '');
    $selfMotherNorm = mv_normalize($selfRecord['mother_name'] ?? '');
    if ($selfFatherNorm && $selfMotherNorm) {
        foreach ($allRecords as $r) {
            if ((int) $r['record_id'] === (int) $selfRecord['record_id']) continue;
            if (mv_normalize($r['father_name'] ?? '') === $selfFatherNorm && mv_normalize($r['mother_name'] ?? '') === $selfMotherNorm) {
                $addPerson($r, 'Sibling', 'immediate');
            }
        }
    }

    // Children: self listed as their father or mother.
    foreach ($allRecords as $r) {
        if (mv_normalize($r['father_name'] ?? '') === $selfName || mv_normalize($r['mother_name'] ?? '') === $selfName) {
            $addPerson($r, 'Child', 'immediate');
        }
    }

    // Same-surname relatives (extended - a shared surname alone, not a
    // confirmed father/mother/spouse/sibling/child relationship).
    $selfLastNorm = mv_normalize($selfRecord['last_name'] ?? '');
    if ($selfLastNorm !== '') {
        foreach ($allRecords as $r) {
            if (in_array((int) $r['record_id'], $addedIds, true)) continue;
            if (mv_normalize($r['last_name'] ?? '') === $selfLastNorm) {
                $addPerson($r, 'Relative', 'extended');
            }
        }
    }
}

// PRIVACY: nobody's actual name leaves the server at all - not even the
// logged-in user's own. Every marker (including "YOU") is identified only
// by relation + barangay/zone, so there is nothing identifying to leak
// even via browser devtools.
$peopleForJs = array_map(function ($p) {
    $r = $p['record'];
    $entry = [
        'relation' => $p['relation'],
        'category' => $p['category'],
        'barangay' => $r['barangay'] ?? '',
    ];
    if ($p['category'] === 'self') {
        $entry['zone'] = $r['zone'] ?? '';
    }
    return $entry;
}, $people);

// Barangay center points (same list + spelling aliases as the staff map)
$stmt2 = $pdo->query("SELECT name, latitude, longitude FROM barangays");
$barangayCoords = [];
foreach ($stmt2->fetchAll() as $b) {
    $barangayCoords[strtolower(trim($b['name']))] = [$b['latitude'], $b['longitude'], $b['name']];
}
$barangayAliases = [
    'santo nino' => 'sto. niño', 'santo niño' => 'sto. niño',
    'tankulan' => 'tankulan (pob.)', 'poblacion' => 'tankulan (pob.)',
    'diclum' => 'dicklum', 'lingi-on' => 'lingion', 'lingi on' => 'lingion',
];
foreach ($barangayAliases as $alias => $canonical) {
    if (!isset($barangayCoords[$alias]) && isset($barangayCoords[$canonical])) {
        $barangayCoords[$alias] = $barangayCoords[$canonical];
    }
}

// Manolo Fortich town center (fallback view when there's nothing to plot yet)
$centerLat = 8.3675;
$centerLng = 124.8656;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Map View</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  .phone{display:flex;flex-direction:column;height:100dvh;height:100dvh;overflow:hidden;}
  .filter-icon{font-size:16px;color:var(--text-main);cursor:pointer;}

  .map-top{padding:20px 20px 0;flex-shrink:0;}
  .map-top .app-header{margin:0 0 14px;justify-content:space-between;}
  .map-search{position:relative;margin-bottom:14px;}
  .map-search input{padding-left:36px;}
  .map-search .s-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);}

  /* ---------- Map contained in its own rounded panel/card ---------- */
  .map-panel-wrap{
    flex:1;
    min-height:0;
    padding:0 0 85px 0; /* space para sa bottom nav */
    display:flex;
    flex-direction:column;
}

.map-card{
    position:relative;
    flex:1;
    min-height:100%;
    width:calc(100% + 40px);
    margin:0 -20px; /* tangtang ang gap sa left ug right */
    border-radius:0; /* optional kung gusto gyud lapat */
    overflow:hidden;
    border:none;
    box-shadow:none;
}

#map{
    width:100%;
    height:100%;
}

  .locate-btn{
    position:absolute;bottom:14px;right:14px;z-index:1000;
    width:44px;height:44px;border-radius:50%;
    background:linear-gradient(160deg,#5cffa3,var(--green));
    color:var(--blue);display:flex;align-items:center;justify-content:center;
    font-size:17px;box-shadow:0 8px 20px rgba(57,255,136,0.35);cursor:pointer;border:none;
    animation:pulseGlow 2.5s ease-in-out infinite;
  }
  .map-unmatched-banner{
    position:absolute;top:14px;left:14px;right:14px;z-index:1000;
    background:var(--nav-bg);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    border:1px solid var(--border);border-radius:14px;padding:12px 14px;
    font-size:12px;line-height:1.5;color:var(--text-main);box-shadow: 0 4px 5px rgba(0,0,0,0.14), 0 6px 10px rgba(0,0,0,0.12);
  }
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="map-top">
    <div class="app-header"><span>Map View</span><span class="filter-icon" onclick="openFilterModal()">⚙</span></div>
  </div>

  <div class="map-panel-wrap">
    <div class="map-card">
      <div id="map"></div>

      <?php if (!$selfRecord): ?>
        <div class="map-unmatched-banner">
          Your account isn't linked to a Civil Registry record yet, so we can't show your location or
          family on the map. Complete identity verification to unlock this.
        </div>
      <?php endif; ?>

      <button class="locate-btn" onclick="locateMe()" title="My Location"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg></button>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

<!-- FILTER MODAL -->
<div class="modal-overlay" id="filterModal">
  <div class="filter-modal">
    <div class="filter-modal-header">
      <h3>Map View</h3>
      <span class="close-x" onclick="closeFilterModal()">✕</span>
    </div>

    <div class="filter-section-label">Show on map</div>
    <label class="filter-check-row"><input type="checkbox" class="show-check" value="immediate" checked> Immediate Family</label>
    <label class="filter-check-row"><input type="checkbox" class="show-check" value="extended" checked> Extended Family</label>
    <label class="filter-check-row"><input type="checkbox" id="showAllCheck" checked> Show All Families</label>

    <div class="filter-section-label">Sort By</div>
    <label class="filter-check-row"><input type="radio" name="sortBy" value="name" checked> Family Name</label>
    <label class="filter-check-row"><input type="radio" name="sortBy" value="barangay"> Barangay</label>
    <label class="filter-check-row"><input type="radio" name="sortBy" value="distance"> Distance</label>

    <div class="filter-modal-actions">
      <button type="button" class="btn-outline" onclick="resetMapFilter()">Reset</button>
      <button type="button" class="btn-primary" onclick="applyMapFilter()">Apply Filter</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map', { zoomControl: false }).setView([<?= $centerLat ?>, <?= $centerLng ?>], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors',
  maxZoom: 18
}).addTo(map);

L.control.zoom({ position: 'bottomleft' }).addTo(map);

const barangayCoords = <?= json_encode($barangayCoords) ?>;
const people = <?= json_encode($peopleForJs) ?>;
const barangayCounts = {};

const bounds = [];
const markerList = [];

// ---------- Map pin icons ----------
// Green pin = the logged-in account owner's own location ("You").
// Orange pin = everyone else (family/relatives) - same icon for all of
// them regardless of relation, on purpose, so no name/relation can be
// inferred from the pin itself either.
function pinIcon(color) {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="34" height="34">`
    + `<circle cx="256" cy="192" r="32" fill="${color}"/>`
    + `<path d="M256,32C167.78,32,96,100.65,96,185c0,40.17,18.31,93.59,54.42,158.78,29,52.34,62.55,99.67,80,123.22a31.75,31.75,0,0,0,51.22,0c17.42-23.55,51-70.88,80-123.22C397.69,278.61,416,225.19,416,185,416,100.65,344.22,32,256,32Zm0,224a64,64,0,1,1,64-64A64.07,64.07,0,0,1,256,256Z" fill="${color}"/>`
    + `</svg>`;
  return L.divIcon({
    className: '',
    html: svg,
    iconSize: [34, 34],
    iconAnchor: [17, 34],
    popupAnchor: [0, -30]
  });
}
const youPinIcon   = pinIcon('#0ADB96');
const otherPinIcon = pinIcon('#F97316');
let selfMarker = null;
let selfLatLng = null;

people.forEach((p) => {
  const bgyKey = (p.barangay || '').toLowerCase().trim();
  const base = barangayCoords[bgyKey];
  if (!base || !base[0] || !base[1]) return; // barangay text doesn't match any known barangay - can't plot it

  barangayCounts[bgyKey] = (barangayCounts[bgyKey] || 0) + 1;
  const idx = barangayCounts[bgyKey];

  // Scatter multiple people within the same barangay so pins don't stack
  // exactly on top of each other - bounded so it can never drift into a
  // different barangay, same approach as the staff Geographic Map.
  const RING_CAPACITY = 12, RING_COUNT = 5, MAX_RADIUS = 0.0022; // ~245m cap
  const posInRing = (idx - 1) % RING_CAPACITY;
  const ringIndex = Math.floor((idx - 1) / RING_CAPACITY) % RING_COUNT;
  const cycleNumber = Math.floor((idx - 1) / (RING_CAPACITY * RING_COUNT));
  const angle = (posInRing / RING_CAPACITY) * 2 * Math.PI + ringIndex * 0.35 + cycleNumber * 0.15;
  const radius = ((ringIndex + 1) / RING_COUNT) * MAX_RADIUS;
  const lat = parseFloat(base[0]) + radius * Math.cos(angle);
  const lng = parseFloat(base[1]) + radius * Math.sin(angle);

  const isSelf = p.category === 'self';

  const marker = L.marker([lat, lng], {
    icon: isSelf ? youPinIcon : otherPinIcon
  }).addTo(map);

  if (isSelf) {
    // This is the account owner's own pin - showing "YOU" here is fine,
    // it's their own data. Everyone else stays label-free (see below).
    marker.bindTooltip('YOU', { permanent: true, direction: 'top', offset: [0, -30], className: 'map-name-label' });
    selfMarker = marker;
    selfLatLng = [lat, lng];
  }
  // PRIVACY: nobody ELSE's name/relation is ever shown on the map - not as
  // a permanent label, and not even on tap. For every other pin, the
  // orange color alone is the only signal shown; no popup/tooltip is bound.

  bounds.push([lat, lng]);
  markerList.push({ marker, category: p.category });
});

if (bounds.length) {
  map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
}

function locateMe() {
  // Zoom straight to the account owner's own pin (their Civil Registry
  // address) rather than raw device GPS - it's the same "YOU" pin already
  // on the map, so the label is right there once we zoom in.
  if (selfLatLng) {
    map.flyTo(selfLatLng, 16);
    if (selfMarker) selfMarker.openTooltip();
    return;
  }
  // No matched record yet (nothing to zoom to) - fall back to the device's
  // actual GPS location instead.
  map.locate({ setView: true, maxZoom: 14 });
}
map.on('locationfound', (e) => {
  if (selfLatLng) return; // we already have a real "YOU" pin - no need for a second generic one
  L.circleMarker(e.latlng, { radius: 8, fillColor: '#0ADB96', color: '#fff', weight: 2, fillOpacity: 1 })
    .addTo(map).bindPopup('You are here').openPopup();
});

// Fix sa Leaflet rendering size kung na-init sa "flex" nga container
setTimeout(() => map.invalidateSize(), 200);

// ---------- FILTER MODAL ----------
function openFilterModal() { document.getElementById('filterModal').classList.add('active'); }
function closeFilterModal() { document.getElementById('filterModal').classList.remove('active'); }

document.getElementById('showAllCheck').addEventListener('change', function () {
  document.querySelectorAll('.show-check').forEach(c => c.checked = this.checked);
});

function applyMapFilter() {
  const allowed = [...document.querySelectorAll('.show-check:checked')].map(c => c.value);
  markerList.forEach(m => {
    // The logged-in user's own pin always stays visible regardless of filters.
    const shouldShow = m.category === 'self' || allowed.includes(m.category);
    if (shouldShow) {
      if (!map.hasLayer(m.marker)) m.marker.addTo(map);
    } else {
      if (map.hasLayer(m.marker)) map.removeLayer(m.marker);
    }
  });
  closeFilterModal();
}

function resetMapFilter() {
  document.querySelectorAll('.show-check').forEach(c => c.checked = true);
  document.getElementById('showAllCheck').checked = true;
  document.querySelector('input[name="sortBy"][value="name"]').checked = true;
  markerList.forEach(m => { if (!map.hasLayer(m.marker)) m.marker.addTo(map); });
}
</script>
<style>
  .map-name-label{
    background:#fff;border:none;box-shadow:0 1px 4px rgba(0,0,0,.2);
    font-size:10px;font-weight:700;padding:2px 6px;border-radius:6px;color:#0ADB96;
  }
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
  .filter-check-row input{width:16px;height:16px;accent-color:var(--primary);}
  .filter-modal-actions{display:flex;gap:10px;margin-top:18px;}
  .filter-modal-actions button{flex:1;margin-top:0;}</style>
</body>
</html>