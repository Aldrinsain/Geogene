<?php
/**
 * civil-registry-bulk-scan.php
 * -----------------------------
 * Lets staff select MANY certificate photos at once (one photo = one
 * record's FRONT side) and scan every single one in ONE batch, instead of
 * repeating "Choose File -> Scan -> Add Record" one certificate at a time.
 *
 * Flow:
 *   1. Staff picks ONE Registry Type for the whole batch (a batch is
 *      realistically a stack of the same kind of certificate - Birth,
 *      Marriage, or Death - scanned together) and selects any number of
 *      photos.
 *   2. "Scan All" runs client-side: each photo is sent, ONE AT A TIME in
 *      the background (so the server never has to hold dozens of photos
 *      in memory at once), to the SAME civil-registry-scan-inline.php
 *      endpoint the single Add Record page already uses - so it gets the
 *      exact same box-by-box (Child/Mother/Father, Husband/Wife,
 *      Deceased) reading already fixed there, automatically, with no
 *      separate extraction logic to maintain here.
 *   3. Every photo's result becomes one editable row in a review table -
 *      staff fixes anything the scan got wrong right there, or removes a
 *      row entirely (e.g. a blurry duplicate).
 *   4. "Save All" sends every remaining row in ONE request to
 *      civil-registry-bulk-save.php, which inserts them all and reports
 *      back how many saved vs. failed (and why).
 *
 * This page only handles the FRONT photo of each certificate (bulk mode
 * trades the optional Back-side photo for speed across many records) -
 * anything that needs a Back-side scan, or per-field certainty checking,
 * should still go through the single Add/Edit Record page.
 */
require_once __DIR__ . '/includes/staff-auth-guard.php';

$pageTitle = 'Bulk Scan Certificates';
$pageSub = 'Select several certificate photos at once, scan them all, review, then save in one go.';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>

<!-- Same in-browser OCR used by the single Add Record page - reads each
     photo (downscaled, so it stays fast) right in the browser and sends
     the recognized text along with the upload, instead of leaving the
     server to shell out to Tesseract on every one of possibly dozens of
     full-resolution photos one at a time. See assets/js/cert-ocr-client.js. -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="assets/js/cert-ocr-client.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/cert-ocr-client.js') ?>"></script>

<div class="panel">
  <div class="form-row">
    <label>Registry Type for this batch *</label>
    <select id="bulkType">
      <option value="birth">Birth</option>
      <option value="marriage">Marriage</option>
      <option value="death">Death</option>
    </select>
    <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">
      All photos in one batch should be the SAME certificate type (a stack of Birth certificates, for example).
      Scanning a different type? Finish and save this batch first, then start a new one.
    </p>
  </div>

  <div class="form-row">
    <label>Certificate Photos (FRONT of each certificate) *</label>
    <input type="file" id="bulkFiles" accept="image/*" multiple>
    <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">
      Select as many photos as you need in one go - there's no limit on how many files or how large each one is.
    </p>
  </div>

  <div class="form-row">
    <button type="button" class="btn btn-primary" id="bulkScanBtn">📷 Scan All</button>
    <span id="bulkScanProgress" style="margin-left:12px;font-size:13px;color:var(--text-muted);"></span>
  </div>
</div>

<div class="panel" id="bulkRestoreBanner" style="display:none;background:#fff8e1;border-color:#ffe4a3;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
    <div style="font-size:13px;color:#7a5c00;">
      ℹ️ There is an unsaved batch from a previous session (<span id="bulkRestoreCount">0</span> records,
      <span id="bulkRestoreWhen"></span>) - the tab was probably refreshed or closed before "Save All" was clicked.
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0;">
      <button type="button" class="btn btn-primary btn-sm" id="bulkRestoreContinue">Continue</button>
      <button type="button" class="btn btn-outline btn-sm" id="bulkRestoreDiscard">Discard</button>
    </div>
  </div>
</div>

<div class="panel" id="bulkReviewPanel" style="display:none;">
  <div class="panel-header">
    <span class="panel-title">Review before saving (<span id="bulkRowCount">0</span>)</span>
    <div style="display:flex;gap:10px;">
      <button type="button" class="btn btn-outline" id="bulkSaveBtn">💾 Save All</button>
    </div>
  </div>
  <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 12px;">
    Fix any field the scan got wrong before saving - the ✕ button removes a row from this batch (nothing is saved
    for it) without affecting the others.
  </p>
  <div style="overflow-x:auto;">
    <table class="data-table" id="bulkTable">
      <thead><tr id="bulkTableHead"></tr></thead>
      <tbody id="bulkTableBody"></tbody>
    </table>
  </div>
  <div id="bulkSaveResult" style="margin-top:14px;font-size:13.5px;"></div>
</div>

<style>
  #bulkTable td, #bulkTable th { padding: 8px 10px; vertical-align: top; }
  #bulkTable input, #bulkTable select { width: 100%; min-width: 120px; padding: 6px 8px; font-size: 12.5px; }
  #bulkTable .bulkThumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); display: block; }
  #bulkTable tr.bulk-row-error input { border-color: #d9534f; }
  #bulkTable tr.bulk-row-saved { opacity: 0.55; }
  .bulkThumb { cursor: zoom-in; }
  .bulk-retry-btn { display: block; margin-top: 4px; padding: 2px 8px; font-size: 12px; border: 1px solid #999; border-radius: 4px; background: #fff; cursor: pointer; }
  #bulkViewer { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,.82); flex-direction: column; }
  #bulkViewer .bv-bar { display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; color: #fff; font-size: 13px; }
  #bulkViewer .bv-close { background: #fff; border: 0; border-radius: 6px; padding: 6px 12px; cursor: pointer; }
  #bulkViewer .bv-stage { flex: 1; overflow: auto; display: flex; align-items: flex-start; justify-content: center; padding: 8px; }
  #bulkViewer img { max-width: 100%; max-height: calc(100vh - 70px); object-fit: contain; cursor: zoom-in; }
  #bulkViewer img.bv-zoom { max-width: none; max-height: none; width: 180%; cursor: zoom-out; }
  .bulk-remove-btn { background: none; border: none; color: #d9534f; font-size: 16px; cursor: pointer; line-height: 1; }
  .bulk-row-status { font-size: 11px; margin-top: 4px; }
</style>

<script>
(function () {
  // One entry per selected photo, in the same order the files were picked.
  // fields holds exactly what gets sent to civil-registry-bulk-save.php.
  var rows = [];
  var rowSeq = 0;

  var typeSelect = document.getElementById('bulkType');
  var fileInput = document.getElementById('bulkFiles');
  var scanBtn = document.getElementById('bulkScanBtn');
  var progressEl = document.getElementById('bulkScanProgress');
  var reviewPanel = document.getElementById('bulkReviewPanel');
  var tableHead = document.getElementById('bulkTableHead');
  var tableBody = document.getElementById('bulkTableBody');
  var rowCountEl = document.getElementById('bulkRowCount');
  var saveBtn = document.getElementById('bulkSaveBtn');
  var saveResultEl = document.getElementById('bulkSaveResult');
  var restoreBanner = document.getElementById('bulkRestoreBanner');

  // Usa ra ka photo at a time ang gi-OCR. Kini ang nag-ayo sa "libog ang
  // OCR" nga problema sa bulk scan: kadtong 3-ka-dungan nga workers
  // nag-ilog sa CPU/RAM, ug ang usa ka photo's OCR result mahimong
  // ma-contaminate sa lain. Seryoso ug klaro na karon ang progress.
  var CONCURRENCY = 1;

  // ---------------------------------------------------------------
  // Draft persistence (localStorage) - so a refresh, an accidental tab
  // close, or a browser crash mid-batch doesn't throw away everything
  // already scanned. Only rows that FINISHED scanning (already uploaded,
  // already have a server image_path) are saved - a File the browser
  // hasn't uploaded yet cannot survive a refresh no matter what (that's a
  // browser security limit, not something any code here can work around),
  // so those still need to be re-selected and re-scanned after a restore.
  // ---------------------------------------------------------------
  var DRAFT_KEY = 'geogene_bulk_scan_draft_v1';

  function saveDraft() {
    try {
      var scannedRows = rows.filter(function (r) { return !!r.image_path; });
      if (scannedRows.length === 0) { localStorage.removeItem(DRAFT_KEY); return; }
      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        type: currentType(),
        savedAt: Date.now(),
        rows: scannedRows.map(function (r) {
          return { id: r.id, fields: r.fields, image_path: r.image_path, certificate_data: r.certificate_data || '' };
        }),
      }));
    } catch (e) { /* localStorage full/unavailable - draft simply won't survive a refresh this time */ }
  }

  function clearDraft() { try { localStorage.removeItem(DRAFT_KEY); } catch (e) {} }

  function checkForDraft() {
    var raw;
    try { raw = localStorage.getItem(DRAFT_KEY); } catch (e) { return; }
    if (!raw) return;
    var draft;
    try { draft = JSON.parse(raw); } catch (e) { clearDraft(); return; }
    if (!draft || !Array.isArray(draft.rows) || draft.rows.length === 0) { clearDraft(); return; }

    document.getElementById('bulkRestoreCount').textContent = draft.rows.length;
    var mins = Math.max(0, Math.round((Date.now() - (draft.savedAt || 0)) / 60000));
    document.getElementById('bulkRestoreWhen').textContent = mins < 1 ? 'just now' : (mins + ' minute(s) ago');
    restoreBanner.style.display = '';

    document.getElementById('bulkRestoreContinue').addEventListener('click', function () {
      typeSelect.value = draft.type || 'birth';
      draft.rows.forEach(function (r) {
        rowSeq = Math.max(rowSeq, r.id);
        rows.push({
          id: r.id,
          fields: r.fields || {},
          image_path: r.image_path,
          certificate_data: r.certificate_data || '',
          status: '✓ scanned (restored)',
          previewUrl: 'civil-registry-image.php?f=' + encodeURIComponent(String(r.image_path).split('/').pop()), // already uploaded before the refresh - served by the login-checked image endpoint
        });
      });
      reviewPanel.style.display = '';
      rerenderAll();
      restoreBanner.style.display = 'none';
    });
    document.getElementById('bulkRestoreDiscard').addEventListener('click', function () {
      clearDraft();
      restoreBanner.style.display = 'none';
    });
  }

  // Column layout per type - keeps the review table's fields matched to
  // what that certificate type actually has, same idea as the single
  // Add Record form's per-type field groups.
  var COLUMNS = {
    birth: [
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'person_name',     label: 'Name of Child' },
      { key: 'date_of_event',   label: 'Date of Birth', type: 'date' },
      { key: 'mother_name',     label: 'Name of Mother' },
      { key: 'father_name',     label: 'Name of Father' },
    ],
    marriage: [
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'person_name',     label: 'Name of Husband' },
      { key: 'spouse_name',     label: 'Name of Wife' },
      { key: 'date_of_event',   label: 'Date of Marriage', type: 'date' },
    ],
    death: [
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'person_name',     label: 'Name of Deceased' },
      { key: 'sex',             label: 'Sex', options: ['male', 'female'] },
      { key: 'date_of_event',   label: 'Date of Death', type: 'date' },
      { key: 'date_of_birth',   label: 'Date of Birth', type: 'date' },
    ],
  };

  function currentType() { return typeSelect.value; }

  function renderTableHead() {
    var cols = COLUMNS[currentType()];
    var html = '<th></th>';
    cols.forEach(function (c) { html += '<th>' + c.label + '</th>'; });
    html += '<th></th>';
    tableHead.innerHTML = html;
  }

  /** Full-screen viewer for a row's certificate photo (click the thumbnail). Click the image to zoom, Esc to close. */
  function openViewer(src) {
    var ov = document.getElementById('bulkViewer');
    if (!ov) {
      ov = document.createElement('div');
      ov.id = 'bulkViewer';
      ov.innerHTML = '<div class="bv-bar"><span>Click the image to zoom in/out &middot; Esc to close</span>' +
        '<button type="button" class="bv-close">&#10005; Close</button></div>' +
        '<div class="bv-stage"><img alt="Certificate photo"></div>';
      document.body.appendChild(ov);
      var closeViewer = function () { ov.style.display = 'none'; ov.querySelector('img').classList.remove('bv-zoom'); };
      ov.querySelector('.bv-close').addEventListener('click', closeViewer);
      ov.addEventListener('click', function (e) { if (e.target === ov || e.target.classList.contains('bv-stage')) closeViewer(); });
      ov.querySelector('img').addEventListener('click', function () { this.classList.toggle('bv-zoom'); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && ov.style.display !== 'none') closeViewer(); });
    }
    ov.querySelector('img').src = src;
    ov.style.display = 'flex';
  }

  function renderRow(row) {
    var cols = COLUMNS[currentType()];
    var tr = document.createElement('tr');
    tr.id = 'bulkrow-' + row.id;

    var thumbTd = document.createElement('td');
    var img = document.createElement('img');
    img.className = 'bulkThumb';
    img.src = row.previewUrl;
    img.title = 'Click to view the full certificate';
    img.addEventListener('click', function () { openViewer(row.previewUrl); });
    thumbTd.appendChild(img);
    tr.appendChild(thumbTd);

    cols.forEach(function (c) {
      var td = document.createElement('td');
      var input;
      if (c.options) {
        input = document.createElement('select');
        c.options.forEach(function (opt) {
          var o = document.createElement('option');
          o.value = opt;
          o.textContent = opt.charAt(0).toUpperCase() + opt.slice(1);
          if ((row.fields[c.key] || '').toLowerCase() === opt) o.selected = true;
          input.appendChild(o);
        });
      } else {
        input = document.createElement('input');
        input.type = 'text';
        input.value = row.fields[c.key] || '';
        if (c.type === 'date') input.placeholder = 'mm/dd/yyyy or as scanned';
      }
      input.dataset.key = c.key;
      input.addEventListener('input', function () { row.fields[c.key] = input.value; saveDraft(); });
      // Names / barangay -> Mixed Case when the cell is left ("ALDRIN PILOTON SAIN" -> "Aldrin Piloton Sain"),
      // names always as "Given Middle Surname" (the server does the same on Save All)
      if (['person_name', 'mother_name', 'father_name', 'spouse_name', 'barangay'].indexOf(c.key) !== -1) {
        input.placeholder = c.key === 'barangay' ? '' : 'Given Middle Surname';
        input.addEventListener('blur', function () {
          var v = input.value.trim();
          if (!v) return;
          var i = v.indexOf(',');
          if (c.key !== 'barangay' && i !== -1) v = (v.slice(i + 1).trim() + ' ' + v.slice(0, i).trim()).trim();
          v = v.replace(/\s+/g, ' ').toLowerCase().replace(/(^|[\s\-.'’(\/])(\p{L})/gu, function (m, a, b) { return a + b.toUpperCase(); });
          input.value = v; row.fields[c.key] = v; saveDraft();
        });
      }
      td.appendChild(input);
      tr.appendChild(td);
    });

    var statusTd = document.createElement('td');
    var statusDiv = document.createElement('div');
    statusDiv.className = 'bulk-row-status';
    statusDiv.id = 'bulkstatus-' + row.id;
    statusDiv.textContent = row.status || '';
    statusTd.appendChild(statusDiv);
    if (row.failed && row.file) {
      var retryBtn = document.createElement('button');
      retryBtn.type = 'button';
      retryBtn.className = 'bulk-retry-btn';
      retryBtn.textContent = '↻ Retry';
      retryBtn.addEventListener('click', function () {
        row.status = 'Waiting...';
        row.failed = false;
        rerenderAll();
        runScan(row, row.file);
      });
      statusTd.appendChild(retryBtn);
    }
    var removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'bulk-remove-btn';
    removeBtn.title = 'Remove this row from the batch';
    removeBtn.textContent = '✕';
    removeBtn.addEventListener('click', function () { removeRow(row.id); });
    statusTd.appendChild(removeBtn);
    tr.appendChild(statusTd);

    tableBody.appendChild(tr);
  }

  function removeRow(id) {
    rows = rows.filter(function (r) { return r.id !== id; });
    var tr = document.getElementById('bulkrow-' + id);
    if (tr) tr.remove();
    rowCountEl.textContent = rows.length;
    if (rows.length === 0) reviewPanel.style.display = 'none';
    saveDraft();
  }

  function rerenderAll() {
    renderTableHead();
    tableBody.innerHTML = '';
    rows.forEach(renderRow);
    rowCountEl.textContent = rows.length;
  }

  // Registry Type changed AFTER some rows already exist - keep the data,
  // just re-draw the table with the new type's columns (any field that
  // doesn't apply to the new type is simply left out of what gets saved).
  typeSelect.addEventListener('change', function () { rerenderAll(); saveDraft(); });

  // Words that are printed form labels, never part of a person's name.
  var FORM_LABEL_WORDS = /^(first|middle|middie|last|name|maiden|sex|date|birth)$/i;

  /** Light, generic cleanup of an OCR'd person name: keeps every letter (José, Peña, Núñez),
   *  hyphens, apostrophes and periods; drops stray symbols/digits, commas and tokens that are
   *  just form labels ("First Middle Last"). No word blacklist, so real names are not touched. */
  function cleanName(s) {
    if (!s) return '';
    var words = String(s)
      .replace(/[^\p{L}\s.'’\-]/gu, ' ')
      .replace(/,/g, ' ')
      .split(/\s+/)
      .filter(function (w) { return /\p{L}/u.test(w) && !FORM_LABEL_WORDS.test(w.replace(/[^\p{L}]/gu, '')); });
    return words.join(' ');
  }

  function nameWordCount(v) {
    return String(v || '').split(/\s+/).filter(function (w) { return /\p{L}{2,}/u.test(w); }).length;
  }

  /** "✓ Scanned" only if the key fields are really filled in; otherwise say exactly what to check. */
  function scanStatus(f, type) {
    var missing = [];
    if (!f.registry_number) missing.push('Registry No.');
    if (nameWordCount(f.person_name) < 2) missing.push('Name');
    var isoDate = /^\d{4}-\d{2}-\d{2}$/;
    if (!isoDate.test(f.date_of_event || '')) missing.push(type === 'birth' ? 'Date of Birth' : (type === 'marriage' ? 'Date of Marriage' : 'Date of Death'));
    if (type === 'death' && !isoDate.test(f.date_of_birth || '')) missing.push('Date of Birth');
    if (type === 'birth') {
      if (nameWordCount(f.mother_name) < 2) missing.push('Mother');
      if (nameWordCount(f.father_name) < 2) missing.push('Father');
    }
    if (type === 'marriage' && nameWordCount(f.spouse_name) < 2) missing.push('Wife');
    return missing.length ? '⚠ Check: ' + missing.join(', ') : '✓ Scanned';
  }

  /** Scans ONE photo (client OCR -> upload+extract) and resolves once its
   *  row is fully updated - used by the CONCURRENCY-wide runner below. */
  function scanOnePhoto(file) {
    var id = ++rowSeq;
    var row = { id: id, fields: {}, status: 'Waiting...', previewUrl: URL.createObjectURL(file), file: file };
    rows.push(row);
    renderRow(row);
    rowCountEl.textContent = rows.length;
    return runScan(row, file);
  }

  /** Marks a row as failed WITH the real reason, and re-draws it so its Retry button shows. */
  function failRow(row, msg) {
    row.status = 'Scan failed: ' + msg;
    row.failed = true;
    rerenderAll();
  }

  /** Reads the photo + uploads it + fills the row. Used for the first scan AND for Retry. */
  function runScan(row, file) {
    var id = row.id;
    row.failed = false;

    // Read the photo IN THE BROWSER first (fast - downscaled before OCR,
    // and running alongside up to CONCURRENCY-1 other photos at the same
    // time) so the server almost never has to run its own, much slower,
    // full-resolution Tesseract pass on every photo in the batch.
    // recognizeDetailed() also tells us whether the browser actually read
    // anything usable (and does a 2nd, enhanced pass on hard photos).
    // Falls back to recognize() if an older cached copy of the JS is loaded.
    var ocrPromise = window.CertOcrClient.recognizeDetailed
      ? window.CertOcrClient.recognizeDetailed([file], onOcrProgress, { type: currentType() })
      : window.CertOcrClient.recognize([file], onOcrProgress).then(function (t) { return { text: t, usable: !!t }; });

    function onOcrProgress(pct, phase) {
      var statusDiv = document.getElementById('bulkstatus-' + id);
      if (statusDiv) {
        var label = { enhancing: 'Poor photo - enhancing... ', turning: 'Checking the photo direction... ',
                      typed: 'Reading the typed answers... ' }[phase] || 'Reading in the browser... ';
        statusDiv.textContent = label + (phase === 'turning' ? '' : pct + '%');
      }
    }

    return ocrPromise.then(function (ocr) {
      var statusDiv = document.getElementById('bulkstatus-' + id);
      if (statusDiv) statusDiv.textContent = 'Uploading...';

      var fd = new FormData();
      fd.append('side', 'front');
      fd.append('registry_type', currentType());
      fd.append('certificate_image', file);

      // Only hand the server the browser's text if the browser really read
      // something (enough high-confidence words). If it read next to nothing,
      // sending it would only stop the server's own OCR from trying.
      if (ocr && ocr.usable && ocr.text) {
        fd.append('ocr_text_client', ocr.text);
      }
      // Marriage: the typed-text-only readings WITH word positions (read by the two-column layout reader on the server)
      if (ocr && ocr.typedVariants && ocr.typedVariants.length) {
        fd.append('ocr_words', JSON.stringify(ocr.typedVariants));
      }

      return fetch('civil-registry-scan-inline.php', { method: 'POST', body: fd });
    })
      .then(function (r) {
        // Read as text first, so that a non-JSON reply (login page after the session expired, a PHP
        // error page, an empty reply) is reported for what it is - not as a vague "network error".
        return r.text().then(function (txt) {
          var parsed = null;
          try { parsed = JSON.parse(txt); } catch (e) { /* not JSON */ }
          if (parsed) return parsed;
          console.error('Bulk scan: reply was not JSON. HTTP ' + r.status + ' ' + r.url + ' | first 500 chars:', txt.slice(0, 500));
          if ((r.redirected && /login\.php/i.test(r.url)) || (/<form/i.test(txt) && /password/i.test(txt))) {
            return { ok: false, error: 'your login session ended - please log in again, then press Retry.' };
          }
          if (!txt.trim()) return { ok: false, error: 'the server sent an empty reply (HTTP ' + r.status + ').' };
          return { ok: false, error: 'the server sent an unreadable reply (HTTP ' + r.status + '): ' +
            txt.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160) };
        });
      })
      .then(function (data) {
        var statusDiv = document.getElementById('bulkstatus-' + id);
        if (data && data.ok) {
          var legacy = data.legacy || {};
          var common = data.common || {};
          // The server already normalizes dates (yyyy-mm-dd when readable) and maps the
          // barangay to the official spelling, so those are used as-is - no scan-specific
          // text patches here (the old ones, e.g. 'hedge', 'Icklum', also mangled real names).
          row.fields.registry_number = common.registry_number || '';
          row.fields.city_municipality = common.city_municipality || '';
          row.fields.barangay = common.barangay || '';
          row.fields.date_of_event = legacy.date_of_event || '';
          // Death certificates also carry the deceased's Date of Birth (Birth certificates use Date of Birth as the event date above)
          row.fields.date_of_birth = legacy.date_of_birth || '';

          row.fields.person_name = [legacy.first_name, legacy.middle_name, legacy.last_name]
            .map(cleanName)
            .filter(function (v) { return v !== ''; }).join(' ');
          row.fields.father_name = cleanName(legacy.father_name);
          row.fields.mother_name = cleanName(legacy.mother_name);
          row.fields.sex = legacy.sex || '';
          var spouse = legacy.spouse_name || '';
          if (spouse.indexOf(',') !== -1) {
            var parts = spouse.split(',');
            spouse = (parts.slice(1).join(',').trim() + ' ' + parts[0].trim()).trim();
          }
          row.fields.spouse_name = spouse;
          row.image_path = data.image_path || '';
          row.certificate_data = JSON.stringify(data.cert_fields || {});
          // update the status shown in the review table too (it used to stay on "Waiting..." after a successful scan)
          row.status = data.warning && !row.fields.registry_number && !row.fields.person_name
            ? '⚠ Nothing could be read - fill in manually'
            : scanStatus(row.fields, currentType());
          if (statusDiv) statusDiv.textContent = row.status;
          rerenderAll();
          saveDraft(); // this row is now fully uploaded+scanned - safe to persist
        } else {
          failRow(row, (data && data.error) || 'unknown error');
        }
      })
      .catch(function (err) {
        console.error('Bulk scan error for row ' + id + ':', err);
        failRow(row, 'could not complete (' + String((err && err.message) || err) + ').');
      });
  }

  scanBtn.addEventListener('click', function () {
    var files = Array.prototype.slice.call(fileInput.files || []);
    if (!files.length) { alert('Please choose the photos to scan first.'); return; }

    scanBtn.disabled = true;
    reviewPanel.style.display = '';
    var total = files.length;
    var nextIndex = 0;
    var done = 0;
    progressEl.textContent = 'Scanning 0 of ' + total + ' (' + CONCURRENCY + ' dungan)...';

    // Up to CONCURRENCY photos read/scanned AT THE SAME TIME instead of
    // strictly one-after-another - each "runner" grabs the next unstarted
    // file as soon as it finishes its own, until none are left.
    function runner() {
      var i = nextIndex++;
      if (i >= total) return Promise.resolve();
      return scanOnePhoto(files[i]).then(function () {
        done++;
        progressEl.textContent = 'Scanning ' + done + ' of ' + total + '...';
        return runner();
      });
    }

    var runners = [];
    for (var r = 0; r < Math.min(CONCURRENCY, total); r++) runners.push(runner());
    Promise.all(runners).then(function () {
      progressEl.textContent = 'Done - scanned ' + total + ' photo' + (total === 1 ? '' : 's') + '.';
      scanBtn.disabled = false;
    });
  });

  saveBtn.addEventListener('click', function () {
    if (!rows.length) { alert('There is nothing else to save.'); return; }
    saveBtn.disabled = true;
    saveResultEl.textContent = 'Saving...';

    var payload = {
      registry_type: currentType(),
      records: rows.map(function (r) {
        return Object.assign({ image_path: r.image_path || '', certificate_data: r.certificate_data || '' }, r.fields);
      }),
    };

    fetch('civil-registry-bulk-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        saveBtn.disabled = false;
        if (!data || !data.ok) {
          saveResultEl.textContent = 'Save failed: ' + ((data && data.error) || 'unknown error');
          return;
        }
        var msg = 'Saved ' + data.saved + ' of ' + rows.length + ' record' + (rows.length === 1 ? '' : 's') + '.';
        if (data.failed && data.failed.length) {
          msg += ' ' + data.failed.length + ' failed:';
          data.failed.forEach(function (f) {
            msg += '\n- Row ' + (f.row + 1) + ': ' + f.reason;
            var tr = document.getElementById('bulkrow-' + (rows[f.row] && rows[f.row].id));
            if (tr) tr.classList.add('bulk-row-error');
          });
        }
        // Mark every successfully-saved row so staff can see at a glance
        // which ones are done, without them disappearing mid-review.
        data.saved_row_indexes.forEach(function (idx) {
          var tr = document.getElementById('bulkrow-' + (rows[idx] && rows[idx].id));
          if (tr) tr.classList.add('bulk-row-saved');
        });
        saveResultEl.style.whiteSpace = 'pre-line';
        saveResultEl.textContent = msg;

        // Saved rows no longer need to survive a refresh - drop only
        // those from the draft, keep any that failed so staff can retry.
        var savedIds = data.saved_row_indexes.map(function (idx) { return rows[idx] && rows[idx].id; });
        rows = rows.filter(function (r) { return savedIds.indexOf(r.id) === -1; });
        if (rows.length === 0) clearDraft(); else saveDraft();
      })
      .catch(function () {
        saveBtn.disabled = false;
        saveResultEl.textContent = 'Save failed (network error).';
      });
  });

  renderTableHead();
  checkForDraft();
})();
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
