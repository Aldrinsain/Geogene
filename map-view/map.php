<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];
$activeNav = 'map';

// Kuhaon tanan nga persons nga naay coordinates (linked sa family tree niini nga user)
$stmt = $pdo->prepare("
    SELECT DISTINCT p2.person_id, p2.first_name, p2.last_name, p2.barangay, p2.latitude, p2.longitude, p2.status
    FROM persons p1
    JOIN relationships r ON r.person_id = p1.person_id
    JOIN persons p2 ON p2.person_id = r.related_person_id
    WHERE p1.user_id = ?
    UNION
    SELECT person_id, first_name, last_name, barangay, latitude, longitude, status
    FROM persons WHERE user_id = ?
");
$stmt->execute([$userId, $userId]);
$people = $stmt->fetchAll();

// Barangay center points para sa fallback pin (kung walay eksakto nga lat/lng ang tawo)
$stmt2 = $pdo->query("SELECT name, latitude, longitude FROM barangays");
$barangayCoords = [];
foreach ($stmt2->fetchAll() as $b) {
    $barangayCoords[$b['name']] = [$b['latitude'], $b['longitude']];
}

// Manolo Fortich town center (fallback kung wala gyuy barangay coordinates na naka-set)
$centerLat = 8.3725;
$centerLng = 124.8694;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Map View</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  .phone{display:flex;flex-direction:column;height:100vh;height:100dvh;overflow:hidden;}
  .filter-icon{font-size:16px;color:var(--text-main);cursor:pointer;}

  .map-top{padding:20px 20px 0;flex-shrink:0;}
  .map-top .app-header{margin:0 0 14px;justify-content:space-between;}
  .map-search{position:relative;margin-bottom:14px;}
  .map-search input{padding-left:36px;}
  .map-search .s-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);}

  /* ---------- Map contained in its own rounded panel/card ---------- */
  .map-panel-wrap{flex:1;min-height:0;padding:0 20px 20px;display:flex;flex-direction:column;}
  .map-card{
    position:relative;flex:1;min-height:0;border-radius:20px;overflow:hidden;
    border:1px solid rgba(255,255,255,0.08);
    box-shadow:0 12px 30px rgba(0,0,0,0.35);
  }
  #map{width:100%;height:100%;}

  .legend-box{
    position:absolute;bottom:14px;left:14px;
    background:rgba(21,34,68,0.9);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:12px 14px;
    box-shadow:0 8px 24px rgba(0,0,0,0.4);
    font-size:11.5px;z-index:1000;color:var(--text-main);
  }
  .legend-title{font-weight:700;margin-bottom:8px;font-size:12px;color:var(--text-main);}
  .legend-item{display:flex;align-items:center;gap:8px;margin-bottom:5px;}
  .legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
  .locate-btn{
    position:absolute;bottom:14px;right:14px;z-index:1000;
    width:44px;height:44px;border-radius:50%;
    background:linear-gradient(160deg,#5cffa3,var(--green));
    color:var(--blue);display:flex;align-items:center;justify-content:center;
    font-size:17px;box-shadow:0 8px 20px rgba(57,255,136,0.35);cursor:pointer;border:none;
    animation:pulseGlow 2.5s ease-in-out infinite;
  }
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="map-top">
    <div class="app-header"><span>Map View</span><span class="filter-icon" onclick="openFilterModal()">⚙</span></div>
    <div class="map-search">
      <span class="s-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" id="mapSearch" class="form-input" placeholder="Search name...">
    </div>
  </div>

  <div class="map-panel-wrap">
    <div class="map-card">
      <div id="map"></div>

      <div class="legend-box">
        <div class="legend-title">Legend</div>
        <div class="legend-item"><span class="legend-dot" style="background:#2563eb;"></span> Your Location</div>
        <div class="legend-item"><span class="legend-dot" style="background:#1b6e3c;"></span> Immediate Family</div>
        <div class="legend-item"><span class="legend-dot" style="background:#e0a94e;"></span> Extended Family</div>
        <div class="legend-item"><span class="legend-dot" style="background:#888;opacity:.5;"></span> Barangay Boundary</div>
      </div>

      <button class="locate-btn" onclick="locateMe()" title="My Location">🎯</button>
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
    <label class="filter-check-row"><input type="checkbox" class="show-check" value="head" checked> Family Head</label>
    <label class="filter-check-row"><input type="checkbox" class="show-check" value="boundary" checked> Barangay Boundary</label>
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
const people = <?= json_encode($people) ?>;

const bounds = [];
const markerList = [];

people.forEach((p, idx) => {
  let lat = p.latitude, lng = p.longitude;

  if ((!lat || !lng) && p.barangay && barangayCoords[p.barangay] && barangayCoords[p.barangay][0]) {
    lat = barangayCoords[p.barangay][0];
    lng = barangayCoords[p.barangay][1];
  }
  if (!lat || !lng) return;

  const isImmediate = idx < 3;
  const color = p.status === 'deceased' ? '#888' : (isImmediate ? '#1b6e3c' : '#e0a94e');

  const marker = L.marker([lat, lng], {
    icon: L.divIcon({
      className: '',
      html: `<div style="background:${color};width:14px;height:14px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3);"></div>`,
      iconSize: [14, 14],
      iconAnchor: [7, 14]
    })
  }).addTo(map);

  marker.bindTooltip(`${p.first_name} ${p.last_name}`, { permanent: true, direction: 'top', offset: [0, -10], className: 'map-name-label' });
  marker.bindPopup(`<strong>${p.first_name} ${p.last_name}</strong><br>${p.barangay || 'Manolo Fortich'}`);

  bounds.push([lat, lng]);
  markerList.push({ name: `${p.first_name} ${p.last_name}`.toLowerCase(), marker, category: isImmediate ? 'immediate' : 'extended' });
});

if (bounds.length) {
  map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
}

function locateMe() {
  map.locate({ setView: true, maxZoom: 14 });
}
map.on('locationfound', (e) => {
  L.circleMarker(e.latlng, { radius: 8, fillColor: '#2563eb', color: '#fff', weight: 2, fillOpacity: 1 })
    .addTo(map).bindPopup('You are here').openPopup();
});

document.getElementById('mapSearch').addEventListener('input', function () {
  const term = this.value.toLowerCase();
  const match = markerList.find(m => term && m.name.includes(term));
  if (match) {
    map.setView(match.marker.getLatLng(), 15);
    match.marker.openPopup();
  }
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
    const shouldShow = allowed.includes(m.category) || (m.category === 'immediate' && allowed.includes('head'));
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
    font-size:10px;font-weight:600;padding:2px 6px;border-radius:6px;
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