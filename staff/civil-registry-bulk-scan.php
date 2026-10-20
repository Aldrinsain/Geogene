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
<script src="assets/js/cert-ocr-client.js"></script>

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

  // Certificates already read run this many AT ONCE (real parallel OCR,
  // not one-after-another) - matched to the OCR worker pool size in
  // assets/js/cert-ocr-client.js so we're not queuing more than it can
  // actually run at the same time.
  var CONCURRENCY = (window.CertOcrClient && window.CertOcrClient.POOL_SIZE) || 3;

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
          previewUrl: '../' + r.image_path, // already uploaded before the refresh - use the real server path
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
      { key: 'page_no',         label: 'Page No.' },
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'date_prepared',   label: 'Date Prepared', type: 'date' },
      { key: 'person_name',     label: 'Name of Child' },
      { key: 'date_of_event',   label: 'Date of Birth', type: 'date' },
      { key: 'mother_name',     label: 'Name of Mother' },
      { key: 'father_name',     label: 'Name of Father' },
    ],
    marriage: [
      { key: 'page_no',         label: 'Page No.' },
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'date_prepared',   label: 'Date Prepared', type: 'date' },
      { key: 'person_name',     label: 'Name of Husband' },
      { key: 'spouse_name',     label: 'Name of Wife' },
      { key: 'date_of_event',   label: 'Date of Marriage', type: 'date' },
    ],
    death: [
      { key: 'page_no',         label: 'Page No.' },
      { key: 'registry_number', label: 'Registry No.' },
      { key: 'barangay',        label: 'Barangay' },
      { key: 'date_prepared',   label: 'Date Prepared', type: 'date' },
      { key: 'person_name',     label: 'Name of Deceased' },
      { key: 'sex',             label: 'Sex', options: ['male', 'female'] },
      { key: 'date_of_event',   label: 'Date of Death', type: 'date' },
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

  function renderRow(row) {
    var cols = COLUMNS[currentType()];
    var tr = document.createElement('tr');
    tr.id = 'bulkrow-' + row.id;

    var thumbTd = document.createElement('td');
    var img = document.createElement('img');
    img.className = 'bulkThumb';
    img.src = row.previewUrl;
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

  /** Scans ONE photo (client OCR -> upload+extract) and resolves once its
   *  row is fully updated - used by the CONCURRENCY-wide runner below. */
  function scanOnePhoto(file) {
    var id = ++rowSeq;
    var row = { id: id, fields: {}, status: 'Waiting...', previewUrl: URL.createObjectURL(file) };
    rows.push(row);
    renderRow(row);
    rowCountEl.textContent = rows.length;

    // Read the photo IN THE BROWSER first (fast - downscaled before OCR,
    // and running alongside up to CONCURRENCY-1 other photos at the same
    // time) so the server almost never has to run its own, much slower,
    // full-resolution Tesseract pass on every photo in the batch.
    return window.CertOcrClient.recognize([file], function (pct) {
      var statusDiv = document.getElementById('bulkstatus-' + id);
      if (statusDiv) statusDiv.textContent = 'Reading in the browser... ' + pct + '%';
    }).then(function (clientText) {
      var statusDiv = document.getElementById('bulkstatus-' + id);
      if (statusDiv) statusDiv.textContent = 'Uploading...';

      var fd = new FormData();
      fd.append('side', 'front');
      fd.append('registry_type', currentType());
      fd.append('certificate_image', file);
      if (clientText) fd.append('ocr_text_client', clientText);

      return fetch('civil-registry-scan-inline.php', { method: 'POST', body: fd });
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var statusDiv = document.getElementById('bulkstatus-' + id);
        if (data && data.ok) {
          var legacy = data.legacy || {};
          var common = data.common || {};
          row.fields.registry_number = common.registry_number || '';
          row.fields.page_no = common.page_no || '';
          row.fields.date_prepared = common.date_prepared || '';
          row.fields.city_municipality = common.city_municipality || '';   // not a column in the table, but saved with the record
          row.fields.barangay = common.barangay || '';
          row.fields.date_of_event = legacy.date_of_event || '';
          row.fields.person_name = [legacy.first_name, legacy.middle_name, legacy.last_name]
            .filter(function (v) { return v && String(v).trim() !== ''; }).join(' ');
          row.fields.father_name = legacy.father_name || '';
          row.fields.mother_name = legacy.mother_name || '';
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
          row.status = row.fields.registry_number ? '✓ Scanned' : '⚠ Check fields';
          if (statusDiv) statusDiv.textContent = row.status;
          rerenderAll();
          saveDraft(); // this row is now fully uploaded+scanned - safe to persist
        } else {
          row.status = 'Scan failed: ' + ((data && data.error) || 'unknown error');
          if (statusDiv) statusDiv.textContent = row.status;
        }
      })
      .catch(function () {
        var statusDiv = document.getElementById('bulkstatus-' + id);
        row.status = 'Scan failed (network error)';
        if (statusDiv) statusDiv.textContent = row.status;
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
