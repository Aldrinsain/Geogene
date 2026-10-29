<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/name-format.php';

// ---------- Pull ALL civil registry records + their linked account (if any) ----------
// Every record with a barangay that matches a known coordinate is plottable
// on the map - this list stays in sync with Civil Registry Records
// automatically since it's a live query (add/edit/delete a record there and
// it immediately reflects here, no separate sync step needed).
$stmt = $pdo->query("
    SELECT
        cr.record_id, cr.registry_type, cr.registry_number,
        cr.first_name, cr.middle_name, cr.last_name, cr.sex,
        cr.date_of_event, cr.barangay, cr.zone, cr.father_name, cr.mother_name, cr.spouse_name,
        u.user_id, u.full_name AS account_name, u.identity_verified
    FROM civil_registry_records cr
    LEFT JOIN users u ON u.matched_record_id = cr.record_id
    ORDER BY cr.barangay, cr.last_name
");
$allRecords = $stmt->fetchAll();

// ---------- Barangay coordinates (Poblacion / centroid points) ----------
// Keyed by a lowercased/trimmed version of the barangay name so records
// typed with different casing (e.g. "dicklum" vs "Dicklum") still match.
$barangayCoords = [];
foreach ($pdo->query("SELECT name, latitude, longitude FROM barangays") as $b) {
    $barangayCoords[strtolower(trim($b['name']))] = [$b['latitude'], $b['longitude'], $b['name']];
}

// Common real-world spelling variants that staff might type in Civil
// Registry Records but that don't exactly match the "official" name
// stored above - point them at the same coordinate instead of silently
// failing to plot. (Add more pairs here if another variant shows up.)
$barangayAliases = [
    'santo nino'       => 'sto. niño',
    'santo niño'       => 'sto. niño',
    'tankulan'         => 'tankulan (pob.)',
    'poblacion'        => 'tankulan (pob.)',
    'diclum'           => 'dicklum',
    'lingi-on'         => 'lingion',
    'lingi on'         => 'lingion',
];
foreach ($barangayAliases as $alias => $canonical) {
    if (!isset($barangayCoords[$alias]) && isset($barangayCoords[$canonical])) {
        $barangayCoords[$alias] = $barangayCoords[$canonical];
    }
}

// Records whose barangay text doesn't match any known barangay at all
// (not even a casing difference) can't be plotted - surface that count so
// staff know to fix the barangay spelling on those records.
$unmappedCount = 0;
foreach ($allRecords as $r) {
    $key = strtolower(trim($r['barangay'] ?? ''));
    if ($key === '' || !isset($barangayCoords[$key])) $unmappedCount++;
}

$totalIndividuals = count($allRecords);
$totalVerifiedAccounts = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM users WHERE identity_verified = 1")->fetchColumn();
$totalBarangays = (int) $pdo->query("SELECT COUNT(*) FROM barangays WHERE latitude IS NOT NULL")->fetchColumn();

$hasCoordsMigration = $totalBarangays > 0;

$pageTitle = 'Geographic Lineage Map';
$pageSub = 'Visualize the geographic distribution of civil registry records across Manolo Fortich.';
$activeStaffNav = 'map';
require_once __DIR__ . '/includes/staff-header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  /* position + z-index here traps Leaflet's internal controls (zoom +/-,
     attribution, etc.) inside this element's own stacking context so they
     can never paint on top of the sticky topbar header above, no matter
     how far the page is scrolled. */
  #staffMap{width:100%;height:520px;border-radius:12px;position:relative;z-index:0;}
  .ft-marker-label{
    background:#fff;border:1.5px solid var(--primary,#1c6b3f);border-radius:6px;
    padding:2px 8px;font-size:11.5px;font-weight:700;color:#1a1a1a;
    box-shadow:0 1px 4px rgba(0,0,0,0.18);white-space:nowrap;
  }
  .ft-marker-label::before{border-top-color:var(--primary,#1c6b3f);}
  .map-legend{
    display:flex;gap:18px;flex-wrap:wrap;align-items:center;
    font-size:12px;color:var(--text-secondary);margin-bottom:14px;
  }
  .map-legend .lg-item{display:flex;align-items:center;gap:6px;}
  .map-legend .lg-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
  .record-popup{font-size:12.5px;line-height:1.6;min-width:190px;}
  .record-popup .rp-name{font-size:13.5px;font-weight:800;color:#1a1a1a;margin-bottom:4px;}
  .record-popup .rp-row{display:flex;justify-content:space-between;gap:10px;}
  .record-popup .rp-label{color:#8a8a8a;}
  .record-popup .rp-badge{
    display:inline-block;font-size:10.5px;font-weight:700;padding:2px 8px;
    border-radius:20px;margin-bottom:6px;
  }
  .record-popup .rp-badge.birth{background:#e4f5e9;color:#1b6e3c;}
  .record-popup .rp-badge.marriage{background:#e7f1fc;color:#3a8ee0;}
  .record-popup .rp-badge.death{background:#f0f0f0;color:#6b6b6b;}


  /* Family books counter (right side of the search row) */
  .map-search-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;}
  .map-search-row .map-search-wrap{margin-bottom:0;flex:1 1 320px;}
  .family-count{
    display:flex;align-items:center;gap:10px;background:var(--bg-page);border:1px solid var(--border);
    border-radius:12px;padding:7px 14px;flex-shrink:0;
  }
  .family-count .fc-num{
    min-width:44px;text-align:center;font-size:22px;font-weight:800;line-height:1;color:var(--text-muted);
    background:#fff;border:1px solid var(--border);border-radius:999px;padding:6px 12px;transition:all .2s ease;
  }
  .family-count.active .fc-num{color:#fff;background:#7c3aed;border-color:#7c3aed;}
  .family-count .fc-label{font-size:11.5px;font-weight:700;color:var(--text-secondary);line-height:1.3;}
  .family-count .fc-label small{display:block;font-weight:500;color:var(--text-muted);font-size:10.5px;}
  body.dark-mode .family-count .fc-num{background:#1c2420;}
  body.dark-mode .family-count.active .fc-num{background:#7c3aed;}

  .map-search-wrap{position:relative;margin-bottom:16px;max-width:420px;}
  .map-search-box{
    display:flex;align-items:center;gap:8px;background:var(--bg-page);border:1px solid var(--border);
    border-radius:10px;padding:9px 14px;
  }
  .map-search-box svg{width:16px;height:16px;color:var(--text-muted);flex-shrink:0;}
  .map-search-box input{
    border:none;background:transparent;outline:none;font-size:13.5px;color:var(--text-main);width:100%;
  }
  .map-search-box input::placeholder{color:var(--text-muted);}
  .map-search-clear{cursor:pointer;color:var(--text-muted);font-size:15px;line-height:1;flex-shrink:0;display:none;}
  .map-search-results{
    position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid var(--border);
    border-radius:10px;box-shadow:0 10px 26px rgba(0,0,0,0.14);max-height:260px;overflow-y:auto;
    z-index:500;display:none;
  }
  .map-search-results.show{display:block;}
  .map-search-item{
    display:flex;justify-content:space-between;align-items:center;gap:10px;
    padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f0f0;font-size:13px;
  }
  .map-search-item:last-child{border-bottom:none;}
  .map-search-item:hover{background:var(--bg-page);}
  .map-search-item .msi-name{font-weight:700;color:var(--text-main);}
  .map-search-item .msi-meta{font-size:11px;color:var(--text-muted);}
  .map-search-empty{padding:16px 14px;font-size:12.5px;color:var(--text-muted);text-align:center;}
  .marker-pulse{animation:markerPulseGreen 1.2s ease-in-out 3;}
  .marker-family-pulse{animation:markerPulseBlue 1.2s ease-in-out 3;}
  @keyframes markerPulseGreen{
    0%{filter:drop-shadow(0 0 0 rgba(22,163,74,0.7));}
    50%{filter:drop-shadow(0 0 9px rgba(22,163,74,0.95));}
    100%{filter:drop-shadow(0 0 0 rgba(22,163,74,0));}
  }
  @keyframes markerPulseBlue{
    0%{filter:drop-shadow(0 0 0 rgba(37,99,235,0.7));}
    50%{filter:drop-shadow(0 0 9px rgba(37,99,235,0.95));}
    100%{filter:drop-shadow(0 0 0 rgba(37,99,235,0));}
  }
  .pin-marker-icon{background:transparent !important;border:none !important;}

  .family-panel{
    margin-top:14px;background:#faf5ff;border:1px solid #e6d6fb;border-radius:12px;
    padding:14px 16px;display:none;
  }
  .family-panel.show{display:block;}
  .family-panel .fp-title{
    font-size:12.5px;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.03em;
    margin-bottom:10px;display:flex;align-items:center;gap:6px;
  }
  .family-panel .fp-empty{font-size:12.5px;color:var(--text-muted);}
  .family-list{display:flex;flex-direction:column;gap:6px;}
  .family-item{
    display:flex;justify-content:space-between;align-items:center;gap:10px;
    background:#fff;border:1px solid #eee0fb;border-radius:9px;padding:9px 12px;
    cursor:pointer;transition:border-color .15s ease,background .15s ease;
  }
  .family-item:hover{border-color:#a855f7;background:#fdfaff;}
  .family-item .fi-name{font-size:13px;font-weight:700;color:var(--text-main);}
  .family-item .fi-meta{font-size:11px;color:var(--text-muted);margin-top:1px;}
  .family-item .fi-relation{
    font-size:10.5px;font-weight:700;color:#7c3aed;background:#f3e8ff;
    padding:3px 9px;border-radius:20px;flex-shrink:0;white-space:nowrap;
  }
  .family-item.unmapped{cursor:default;opacity:.75;}
  .family-item.unmapped:hover{border-color:#eee0fb;background:#fff;}

  /* ---------- Dark mode ---------- */
  body.dark-mode .map-search-results{ background:#1c2420; border-color:var(--border); }
  body.dark-mode .map-search-item{ border-bottom-color:var(--border); }
  body.dark-mode .family-panel{ background:#241a33; border-color:#3d2a57; }
  body.dark-mode .family-panel .fp-title{ color:#c9a3ff; }
  body.dark-mode .family-item{ background:#1c2420; border-color:#3d2a57; }
  body.dark-mode .family-item:hover{ border-color:#a855f7; background:#2a1f3d; }
  body.dark-mode .family-item.unmapped:hover{ border-color:#3d2a57; background:#1c2420; }
  body.dark-mode .family-item .fi-relation{ color:#e9d5ff; background:#3d2a57; }
</style>

<?php if (!$hasCoordsMigration): ?>
  <div class="error-text">
    The barangay coordinates have not been imported yet. Please import
    <strong>migrations/add-barangay-coordinates.sql</strong> sa phpMyAdmin (SQL tab)
    first so the markers appear on the map.
  </div>
<?php elseif ($unmappedCount > 0): ?>
  <div class="error-text">
    <?= $unmappedCount ?> record<?= $unmappedCount === 1 ? '' : 's' ?> could not be placed on the map because
    their Barangay field doesn't match any barangay on file (check for typos in
    <a href="civil-registry.php" style="color:inherit;text-decoration:underline;">Civil Registry Records</a>).
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-icon green"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path d="M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.01.75.75 0 0 0 .42-.643 4.875 4.875 0 0 0-6.957-4.611 8.586 8.586 0 0 1 1.71 5.157v.003Z" />
</svg>
</div><div><div class="stat-num"><?= number_format($totalIndividuals) ?></div><div class="stat-label">Individuals Searchable on Map</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
</svg>
</div><div><div class="stat-num"><?= number_format($totalVerifiedAccounts) ?></div><div class="stat-label">Verified Accounts</div></div></div>
  <div class="stat-card"><div class="stat-icon orange"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
  <path fill-rule="evenodd" d="M8.161 2.58a1.875 1.875 0 0 1 1.678 0l4.993 2.498c.106.052.23.052.336 0l3.869-1.935A1.875 1.875 0 0 1 21.75 4.82v12.485c0 .71-.401 1.36-1.037 1.677l-4.875 2.437a1.875 1.875 0 0 1-1.676 0l-4.994-2.497a.375.375 0 0 0-.336 0l-3.868 1.935A1.875 1.875 0 0 1 2.25 19.18V6.695c0-.71.401-1.36 1.036-1.677l4.875-2.437ZM9 6a.75.75 0 0 1 .75.75V15a.75.75 0 0 1-1.5 0V6.75A.75.75 0 0 1 9 6Zm6.75 3a.75.75 0 0 0-1.5 0v8.25a.75.75 0 0 0 1.5 0V9Z" clip-rule="evenodd" />
</svg>
</div><div><div class="stat-num">22</div><div class="stat-label">Barangays, Manolo Fortich</div></div></div>
</div>

<div class="panel">
  <div class="panel-header"><span class="panel-title">MANOLO FORTICH MAP</span></div>
 
  <div class="map-search-row">
  <div class="map-search-wrap">
    <div class="map-search-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="mapSearchInput" placeholder="Search full name or exact name to locate on the map..." autocomplete="off">
      <span class="map-search-clear" id="mapSearchClear">✕</span>
    </div>
    <div class="map-search-results" id="mapSearchResults"></div>
  </div>

  <div class="family-count" id="familyCount" title="Number of family records (books) on the map">
    <span class="fc-num" id="familyCountNum">0</span>
    <span class="fc-label">Family Records<br>on Map<small id="familyCountSub">Search a name</small></span>
  </div>
  </div>

  <div class="map-legend">
    <div class="lg-item"><span class="lg-dot" style="background:#1b6e3c;"></span> Birth Record</div>
    <div class="lg-item"><span class="lg-dot" style="background:#3a8ee0;"></span> Marriage Record</div>
    <div class="lg-item"><span class="lg-dot" style="background:#8a8a8a;"></span> Death Record</div>
  </div>

  <div id="staffMap"></div>

  <div class="family-panel" id="familyPanel">
    <div class="fp-title" id="fpTitle">👪 Family Members</div>
    <div class="family-list" id="familyList"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('staffMap').setView([8.3675, 124.8656], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors', maxZoom: 18
}).addTo(map);

const barangayCoords = <?= json_encode($barangayCoords) ?>; // keyed by lowercased barangay name -> [lat, lng, properName]
const records = <?= json_encode($allRecords) ?>;

const typeColors = { birth: '#1b6e3c', marriage: '#3a8ee0', death: '#8a8a8a' };
const typeDot = {
  birth:    { fill: '#BBF7D0', stroke: '#16A34A' },
  marriage: { fill: '#BFDBFE', stroke: '#2563EB' },
  death:    { fill: '#E5E7EB', stroke: '#6B7280' },
};
const barangayCounts = {};
const markerIndex = []; // { name, marker, record } - used by the search box below

/* Name helpers - the SAME standard as Civil Registry Records and Family Tree
   (includes/name-format.php): "Given Name Middle Name Surname" in Mixed Case,
   exactly how the certificate prints it. */
function ggTitleCase(str) {
  str = String(str || '').replace(/\s+/g, ' ').trim().toLowerCase();
  return str.replace(/(^|[\s\-.'’(\/])(\p{L})/gu, (m, a, b) => a + b.toUpperCase())
            .replace(/\b(Ii|Iii|Iv)\b/g, m => m.toUpperCase());
}
// Any stored name (old "SURNAME, GIVEN MIDDLE" or new) -> "Given Middle Surname"
function displayName(str) {
  str = String(str || '').trim();
  const i = str.indexOf(',');
  if (i !== -1) str = (str.slice(i + 1).trim() + ' ' + str.slice(0, i).trim()).trim();
  return ggTitleCase(str);
}
function fullNameOf(r) {
  return ggTitleCase([r.first_name, r.middle_name, r.last_name]
    .map(v => (v || '').trim()).filter(Boolean).join(' '));
}

/* ---------- Custom pin marker icon (matches the requested pin+dot design) ---------- */
function makePinIcon(dotFill, dotStroke, big) {
  const size = big ? 34 : 26;
  const html = `
    <svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M20 10C20 14.4183 12 22 12 22C12 22 4 14.4183 4 10C4 5.58172 7.58172 2 12 2C16.4183 2 20 5.58172 20 10Z" stroke="#000000" stroke-width="1.5" fill="#ffffff"/>
      <path d="M12 11C12.5523 11 13 10.5523 13 10C13 9.44772 12.5523 9 12 9C11.4477 9 11 9.44772 11 10C11 10.5523 11.4477 11 12 11Z" fill="${dotFill}" stroke="${dotStroke}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;
  return L.divIcon({
    html,
    className: 'pin-marker-icon',
    iconSize: [size, size],
    iconAnchor: [size / 2, size],
    popupAnchor: [0, -size],
  });
}

records.forEach(r => {
  const bgyKey = (r.barangay || '').toLowerCase().trim();
  const base = barangayCoords[bgyKey];
  if (!base || !base[0] || !base[1]) return; // barangay text doesn't match any known barangay - can't plot it

  barangayCounts[bgyKey] = (barangayCounts[bgyKey] || 0) + 1;
  const idx = barangayCounts[bgyKey];

  // Scatter multiple people within the same barangay so pins don't stack
  // exactly on top of each other - but the offset must stay BOUNDED no
  // matter how many hundreds of people share a barangay, otherwise (as
  // happened before) it keeps growing outward with every additional
  // person and can end up placing someone's pin in a totally different
  // barangay, or even outside Manolo Fortich entirely. So: cycle through
  // a fixed set of rings up to a hard MAX_RADIUS, reusing ring positions
  // once we run out, instead of letting the radius grow forever.
  const RING_CAPACITY = 12;         // points placed per ring before starting a new one
  const RING_COUNT = 5;             // number of concentric rings before cycling repeats
  const MAX_RADIUS = 0.0022;        // ~245m - stays safely inside a barangay, never spills into the next one
  const posInRing = (idx - 1) % RING_CAPACITY;
  const ringIndex = Math.floor((idx - 1) / RING_CAPACITY) % RING_COUNT;
  const cycleNumber = Math.floor((idx - 1) / (RING_CAPACITY * RING_COUNT));
  const angle = (posInRing / RING_CAPACITY) * 2 * Math.PI + ringIndex * 0.35 + cycleNumber * 0.15;
  const radius = ((ringIndex + 1) / RING_COUNT) * MAX_RADIUS;
  const lat = parseFloat(base[0]) + radius * Math.cos(angle);
  const lng = parseFloat(base[1]) + radius * Math.sin(angle);

  const fullName = fullNameOf(r);
  const barangayLabel = base[2] || r.barangay;

  const dotColors = typeDot[r.registry_type] || typeDot.birth;
  // NOTE: marker is created here but deliberately NOT added to the map yet
  // (.addTo(map) happens later, only once this person is found via search).
  // The map should start blank - everyone gets plotted on demand, not all
  // at once on page load.
  const marker = L.marker([lat, lng], { icon: makePinIcon(dotColors.fill, dotColors.stroke) });

  const popupHtml = `
    <div class="record-popup">
      <span class="rp-badge ${r.registry_type}">${r.registry_type.charAt(0).toUpperCase() + r.registry_type.slice(1)}</span>
      <div class="rp-name">${fullName}</div>
      <div class="rp-row"><span class="rp-label">Registry No.</span><span>${r.registry_number || '\u2014'}</span></div>
      <div class="rp-row"><span class="rp-label">Date</span><span>${r.date_of_event || '\u2014'}</span></div>
      <div class="rp-row"><span class="rp-label">Barangay</span><span>${barangayLabel || '\u2014'}</span></div>
      ${r.zone ? `<div class="rp-row"><span class="rp-label">Zone/Purok</span><span>${r.zone}</span></div>` : ''}
      ${r.father_name ? `<div class="rp-row"><span class="rp-label">Father</span><span>${displayName(r.father_name)}</span></div>` : ''}
      ${r.mother_name ? `<div class="rp-row"><span class="rp-label">Mother</span><span>${displayName(r.mother_name)}</span></div>` : ''}
      ${r.account_name
        ? `<div class="rp-row" style="margin-top:6px;border-top:1px solid #eee;padding-top:6px;"><span class="rp-label">Account</span><span>${r.account_name}${Number(r.identity_verified) === 1 ? ' \u2713' : ' (unverified)'}</span></div>`
        : `<div class="rp-row" style="margin-top:6px;border-top:1px solid #eee;padding-top:6px;"><span class="rp-label">Account</span><span style="color:#999;">No linked account</span></div>`}
    </div>
  `;
  marker.bindPopup(popupHtml);

  // Persistent name label next to the pin (not hidden behind a click) -
  // shows automatically as soon as this marker is placed on the map
  // (i.e. right after a search finds this person).
  marker.bindTooltip(fullName, {
    permanent: true, direction: 'top', offset: [0, -28],
    className: 'ft-marker-label',
  });

  markerIndex.push({ name: fullName, marker, lat, lng, barangay: barangayLabel, zone: r.zone || '', type: r.registry_type, record: r });
});

// ---------- Which markers are currently shown on the map ----------
// Starts empty (blank map) - markers get added only once a search finds
// that person, and get cleared again at the start of the next fresh search.
const shownMarkers = new Set();

function showMarkerPin(entry) {
  if (shownMarkers.has(entry)) return;
  entry.marker.addTo(map);
  shownMarkers.add(entry);
}

function clearShownMarkers() {
  shownMarkers.forEach(entry => entry.marker.remove());
  shownMarkers.clear();
}

/* ---------- Family finder: parents, children, siblings, spouse,
   grandparents, and cousins - matched by name across all civil registry
   records (father_name/mother_name/spouse_name are free-text fields, so
   this works by comparing normalized full names). ---------- */
function normalizeName(s) {
  // order- and case-insensitive: "Abao, Xander Sarzosa" == "XANDER SARZOSA ABAO"
  return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase()
    .replace(/[^A-Z\s]/g, ' ').split(/\s+/).filter(Boolean).sort().join(' ');
}

// Quick lookup: normalized full name -> markerIndex entry (only for people
// who are actually plotted on the map, i.e. have a matching barangay).
const nameToEntry = {};
markerIndex.forEach(e => {
  const key = normalizeName(e.name);
  if (key && !nameToEntry[key]) nameToEntry[key] = e;
});

// Search index built from EVERY civil registry record (not just the ones
// that made it onto the map). Someone can be searched and found as soon as
// they're in Civil Registry Records - whether or not their barangay has
// been filled in with a value that matches a known location yet.
const searchIndex = [];
records.forEach(r => {
  const name = fullNameOf(r);
  if (!name.trim()) return;
  searchIndex.push({ name, record: r, entry: nameToEntry[normalizeName(name)] || null });
});

function findRelatives(person) {
  const selfName = normalizeName(fullNameOf(person));
  const fatherName = normalizeName(person.father_name);
  const motherName = normalizeName(person.mother_name);

  const found = [];
  const seen = new Set();

  function addRelative(name, relation) {
    const norm = normalizeName(name);
    if (!norm || norm === selfName) return;
    const key = norm + '|' + relation;
    if (seen.has(key)) return;
    seen.add(key);
    found.push({ name: displayName(name), relation, entry: nameToEntry[norm] || null });
  }

  if (person.father_name) addRelative(person.father_name, 'Father');
  if (person.mother_name) addRelative(person.mother_name, 'Mother');
  if (person.spouse_name) addRelative(person.spouse_name, 'Spouse');

  records.forEach(r => {
    if (r.record_id === person.record_id) return;
    const rName = fullNameOf(r);
    const rFather = normalizeName(r.father_name);
    const rMother = normalizeName(r.mother_name);
    const rSpouse = normalizeName(r.spouse_name);

    // Children: this person is listed as the OTHER record's father or mother
    if (selfName && (rFather === selfName || rMother === selfName)) {
      addRelative(rName, 'Child');
    }
    // Siblings: share both parents (only when both are actually on file)
    if (fatherName && motherName && rFather === fatherName && rMother === motherName) {
      addRelative(rName, 'Sibling');
    }
    // Spouse, reverse direction (the other record lists this person as spouse)
    if (selfName && rSpouse === selfName) {
      addRelative(rName, 'Spouse');
    }
  });

  // Grandparents: find the parent's OWN record (if on file), then read
  // that record's own father_name/mother_name.
  function addGrandparents(parentName, sideLabel) {
    const parentNorm = normalizeName(parentName);
    if (!parentNorm) return null;
    const parentRecord = records.find(r => normalizeName(fullNameOf(r)) === parentNorm);
    if (!parentRecord) return null;
    if (parentRecord.father_name) addRelative(parentRecord.father_name, sideLabel + ' Grandfather');
    if (parentRecord.mother_name) addRelative(parentRecord.mother_name, sideLabel + ' Grandmother');
    return parentRecord;
  }
  const fatherRecord = addGrandparents(person.father_name, 'Paternal');
  const motherRecord = addGrandparents(person.mother_name, 'Maternal');

  // Cousins: children of this person's aunts/uncles (siblings of father or mother).
  function addCousinsVia(parentRecord) {
    if (!parentRecord) return;
    const parentNorm = normalizeName(fullNameOf(parentRecord));
    const gFather = normalizeName(parentRecord.father_name);
    const gMother = normalizeName(parentRecord.mother_name);
    if (!gFather || !gMother) return;

    records.forEach(auntUncle => {
      const auName = normalizeName(fullNameOf(auntUncle));
      if (auName === parentNorm) return; // skip the parent themself
      const auFather = normalizeName(auntUncle.father_name);
      const auMother = normalizeName(auntUncle.mother_name);
      if (auFather !== gFather || auMother !== gMother) return; // not a sibling of the parent

      records.forEach(cousin => {
        const cFather = normalizeName(cousin.father_name);
        const cMother = normalizeName(cousin.mother_name);
        if (cFather === auName || cMother === auName) {
          addRelative(fullNameOf(cousin), 'Cousin');
        }
      });
    });
  }
  addCousinsVia(fatherRecord);
  addCousinsVia(motherRecord);

  // Extended family fallback: everyone else on file who shares this
  // person's surname (apilyedo). The precise checks above only match
  // when father_name/mother_name/spouse_name text matches another
  // record's full name exactly, so relatives entered with a slightly
  // different spelling, missing suffix, etc. would otherwise be left
  // out. This catches them and still lets the map plot + label them.
  const selfLast = normalizeName(person.last_name);
  if (selfLast) {
    const namesAlreadyFound = new Set(found.map(f => normalizeName(f.name)));
    records.forEach(r => {
      if (r.record_id === person.record_id) return;
      if (normalizeName(r.last_name) !== selfLast) return;
      if (namesAlreadyFound.has(normalizeName(fullNameOf(r)))) return; // already shown under a precise relation
      addRelative(fullNameOf(r), 'Relative (Same Surname)');
    });
  }

  return found;
}

function updateFamilyCount(onMap, totalRelatives, hasSubject) {
  const box = document.getElementById('familyCount');
  document.getElementById('familyCountNum').textContent = onMap;
  box.classList.toggle('active', onMap > 0);
  const sub = document.getElementById('familyCountSub');
  if (!hasSubject) sub.textContent = 'Search a name';
  else if (totalRelatives > onMap) sub.textContent = 'of ' + totalRelatives + ' relatives';
  else sub.textContent = onMap === 1 ? 'family record' : 'family records';
}

function renderFamilyPanel(record, subjectUnmapped) {
  const panel = document.getElementById('familyPanel');
  const list = document.getElementById('familyList');
  if (!record) { panel.classList.remove('show'); updateFamilyCount(0, 0, false); return; }

  const relatives = findRelatives(record);
  const noticeHtml = subjectUnmapped
    ? `<div class="fp-empty" style="border-bottom:1px solid #eee;margin-bottom:8px;padding-bottom:10px;">
         📍 <strong>${fullNameOf(record)}</strong> has no barangay/location on file yet, so they can't be
         pinned on the map themselves. Update their barangay in Civil Registry Records to plot them.
       </div>`
    : '';

  // Count each distinct family record that is actually plotted on the map
  const mappedFamily = new Set(relatives.filter(r => r.entry).map(r => r.entry));
  updateFamilyCount(mappedFamily.size, relatives.length, true);

  if (!relatives.length) {
    list.innerHTML = noticeHtml || '<div class="fp-empty">No family relations on file for this person yet (father/mother/spouse fields are blank, or their relatives aren\'t in the civil registry).</div>';
    panel.classList.add('show');
    return;
  }

  list.innerHTML = noticeHtml + relatives.map((rel, i) => `
    <div class="family-item ${rel.entry ? '' : 'unmapped'}" data-idx="${i}">
      <div>
        <div class="fi-name">${rel.name}</div>
        <div class="fi-meta">${rel.entry ? [rel.entry.barangay, rel.entry.zone].filter(Boolean).join(' / ') || 'On the map' : 'Not on file / no map location yet'}</div>
      </div>
      <span class="fi-relation">${rel.relation}</span>
    </div>
  `).join('');

  list.querySelectorAll('.family-item').forEach(item => {
    const rel = relatives[Number(item.dataset.idx)];
    if (!rel.entry) return;
    item.addEventListener('click', () => highlightMarker(rel.entry, true));
  });

  // Auto-color every found relative's pin BLUE right away, so you can see
  // the whole connected family on the map without clicking each one.
  relatives.forEach(rel => {
    if (!rel.entry) return;
    showMarkerPin(rel.entry); // place this relative's pin on the map (it starts off it)
    rel.entry.marker.setIcon(makePinIcon(RELATIVE_DOT.fill, RELATIVE_DOT.stroke, true));
    highlightedRelativeEntries.push(rel.entry);
  });

  panel.classList.add('show');

  // Even though the searched person themself has no pin, if any of their
  // relatives DO have one, fly the map to fit all of them so they're not
  // left staring at an unchanged view.
  if (subjectUnmapped) {
    const mappedRelBounds = relatives.filter(r => r.entry).map(r => [r.entry.lat, r.entry.lng]);
    if (mappedRelBounds.length) {
      map.flyToBounds(mappedRelBounds, { padding: [60, 60], maxZoom: 15, duration: 0.8 });
    }
  }
}

// Selecting a person who exists in Civil Registry Records but has no
// barangay match yet (so no pin to fly to) - still surface their family
// panel and, if possible, fly to any relatives who ARE mapped.
function selectUnmappedSubject(record) {
  if (activeMarkerEl) {
    activeMarkerEl.classList.remove('marker-pulse');
    activeMarkerEl.classList.remove('marker-family-pulse');
    activeMarkerEl = null;
  }
  clearShownMarkers(); // fresh search - wipe the map back to blank first
  resetHighlights();
  renderFamilyPanel(record, true);
}

let activeMarkerEl = null;
let highlightedRelativeEntries = []; // track relative markers so we can revert their icon on the next search

const SUBJECT_DOT = { fill: '#86EFAC', stroke: '#15803D' }; // vivid green - the person you searched for
const RELATIVE_DOT = { fill: '#BFDBFE', stroke: '#2563EB' }; // blue - their connected family members

function resetHighlights() {
  highlightedRelativeEntries.forEach(entry => {
    const dotColors = typeDot[entry.record.registry_type] || typeDot.birth;
    entry.marker.setIcon(makePinIcon(dotColors.fill, dotColors.stroke));
  });
  highlightedRelativeEntries = [];
}

function highlightMarker(entry, isFamilyClick) {
  if (activeMarkerEl) {
    activeMarkerEl.classList.remove('marker-pulse');
    activeMarkerEl.classList.remove('marker-family-pulse');
  }

  if (!isFamilyClick) {
    // A fresh "main" search - wipe the map back to blank and clear any
    // relative highlighting from a previous search before showing the new one.
    clearShownMarkers();
    resetHighlights();
  }

  showMarkerPin(entry); // actually place this pin on the map (it starts off it)

  const dot = isFamilyClick ? RELATIVE_DOT : SUBJECT_DOT;
  entry.marker.setIcon(makePinIcon(dot.fill, dot.stroke, true));
  if (isFamilyClick) highlightedRelativeEntries.push(entry);

  map.flyTo([entry.lat, entry.lng], 16, { duration: 0.8 });
  setTimeout(() => {
    entry.marker.openPopup();
    const el = entry.marker.getElement();
    if (el) {
      el.classList.add(isFamilyClick ? 'marker-family-pulse' : 'marker-pulse');
      activeMarkerEl = el;
    }
  }, 850);
  renderFamilyPanel(entry.record);
}

/* ---------- Search: find a person by name and jump the map to them ---------- */
(function () {
  const input = document.getElementById('mapSearchInput');
  const results = document.getElementById('mapSearchResults');
  const clearBtn = document.getElementById('mapSearchClear');

  function normalize(s) {
    return (s || '').toLowerCase().trim().replace(/\s+/g, ' ');
  }

  function renderResults(matches, query) {
    if (!query) { results.classList.remove('show'); results.innerHTML = ''; return; }

    if (!matches.length) {
      results.innerHTML = `<div class="map-search-empty">No record found matching "${query}". Try a different spelling.</div>`;
      results.classList.add('show');
      return;
    }

    results.innerHTML = matches.slice(0, 8).map((m, i) => `
      <div class="map-search-item" data-idx="${i}">
        <span class="msi-name">${m.name}</span>
        <span class="msi-meta">${m.entry ? (m.entry.barangay || 'On the map') : 'No location on file yet'} \u00b7 ${m.record.registry_type}</span>
      </div>
    `).join('');
    results.classList.add('show');

    results.querySelectorAll('.map-search-item').forEach(item => {
      item.addEventListener('click', () => {
        const m = matches[Number(item.dataset.idx)];
        input.value = m.name;
        results.classList.remove('show');
        clearBtn.style.display = 'block';
        if (m.entry) {
          highlightMarker(m.entry);
        } else {
          selectUnmappedSubject(m.record);
        }
      });
    });
  }

  // Search in ANY order and ANY case, partial words allowed:
  // "abao xan", "Xander Sarzosa Abao", "ABAO, XANDER" all find "Xander Sarzosa Abao".
  function nameMatches(name, q) {
    const hay = normalizeName(name);
    const toks = normalizeName(q).split(' ').filter(Boolean);
    return toks.length > 0 && toks.every(t => hay.includes(t));
  }

  input.addEventListener('input', () => {
    const query = normalize(input.value);
    clearBtn.style.display = input.value ? 'block' : 'none';
    if (!query) { renderResults([], ''); return; }

    const matches = searchIndex.filter(m => nameMatches(m.name, input.value));
    renderResults(matches, input.value.trim());
  });

  input.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const query = normalize(input.value);
    if (!query) return;

    const exact = searchIndex.find(m => normalizeName(m.name) === normalizeName(input.value));
    const match = exact || searchIndex.find(m => nameMatches(m.name, input.value));

    if (match) {
      results.classList.remove('show');
      if (match.entry) {
        highlightMarker(match.entry);
      } else {
        selectUnmappedSubject(match.record);
      }
    } else {
      renderResults([], input.value.trim());
    }
  });

  clearBtn.addEventListener('click', () => {
    input.value = '';
    clearBtn.style.display = 'none';
    results.classList.remove('show');
    input.focus();

    // Back to a blank map - no one plotted until the next search.
    if (activeMarkerEl) {
      activeMarkerEl.classList.remove('marker-pulse');
      activeMarkerEl.classList.remove('marker-family-pulse');
      activeMarkerEl = null;
    }
    clearShownMarkers();
    resetHighlights();
    document.getElementById('familyPanel').classList.remove('show');
    updateFamilyCount(0, 0, false);
    map.setView([8.3675, 124.8656], 12);
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.map-search-wrap')) results.classList.remove('show');
  });

  // Opened from the top search bar ("Locate X on the Map") -> run this same search straight away
  const ggQ = new URLSearchParams(window.location.search).get('q');
  if (ggQ) {
    setTimeout(() => {
      input.value = ggQ;
      input.dispatchEvent(new Event('input'));
      input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
    }, 500);
  }
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>