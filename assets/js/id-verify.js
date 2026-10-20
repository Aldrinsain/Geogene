const idFrame = document.getElementById('idFrame');
const camStream = document.getElementById('camStream');
const camError = document.getElementById('camError');
const idPreview = document.getElementById('idPreview');
const idCanvas = document.getElementById('idCanvas');
const captureRow = document.getElementById('captureRow');
const captureBtn = document.getElementById('captureBtn');
const confirmRow = document.getElementById('confirmRow');
const confirmBtn = document.getElementById('confirmBtn');
const retakeBtn = document.getElementById('retakeBtn');
const restartBtn = document.getElementById('restartBtn');
const flashBtn = document.getElementById('flashBtn');

const screenScan = document.getElementById('screen-scan');
const screenVerify = document.getElementById('screen-verify');
const screenResult = document.getElementById('screen-result');
const resultWrap = document.getElementById('resultWrap');
const verifyStatus = document.getElementById('verifyStatus');

let stream = null;
let torchOn = false;

// ---------- FLASHLIGHT / TORCH ----------
// Dili tanan mobile camera/browser naa'y torch support (ang front camera
// walay flash gyud, ug pipila ka Android/iPhone browsers wala mo-expose
// niini). I-check una human mo-start ang camera; i-tago ra ang button
// kung dili gyud supported imbes mag-alert nga makapalibog.
function setupFlashButton() {
  if (!stream) {
    flashBtn.style.display = 'none';
    return;
  }
  const track = stream.getVideoTracks()[0];
  const capabilities = track.getCapabilities ? track.getCapabilities() : {};

  if (capabilities.torch) {
    flashBtn.style.display = 'flex';
  } else {
    flashBtn.style.display = 'none';
  }
}

flashBtn.addEventListener('click', async () => {
  if (!stream) return;
  const track = stream.getVideoTracks()[0];
  try {
    torchOn = !torchOn;
    await track.applyConstraints({ advanced: [{ torch: torchOn }] });
    flashBtn.classList.toggle('on', torchOn);
  } catch (err) {
    console.error('Torch error:', err);
    torchOn = false;
    alert('Unable to toggle the flashlight on your device/browser.');
  }
});

// ---------- CAMERA ----------
async function startCamera() {

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert("Camera API not supported sa browser.");
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        // Ask for a sharp picture - small ID text needs resolution to be read
        width: { ideal: 1920 },
        height: { ideal: 1080 }
      },
      audio: false
    });

    // Best effort: keep the camera auto-focusing on the ID (ignored if not supported)
    try {
      const vt = stream.getVideoTracks()[0];
      const caps = vt.getCapabilities ? vt.getCapabilities() : {};
      if (caps.focusMode && caps.focusMode.indexOf('continuous') !== -1) {
        await vt.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
      }
    } catch (e) { /* not supported - fine */ }

    camStream.srcObject = stream;
    camStream.style.display = 'block';
    camError.style.display = 'none';

    torchOn = false;
    flashBtn.classList.remove('on');
    setupFlashButton();

  } catch (err) {

    console.error('Camera error:', err);

    alert(
      "Camera Error:\n\n" +
      err.name +
      "\n\n" +
      err.message
    );

    camStream.style.display = 'none';
    camError.style.display = 'block';
  }
}

// Start camera as soon as the page loads
startCamera();

// I-stop ang tanan camera tracks para dili na mag-drain og battery/mag-block
// sa laing app sa camera human na ma-capture ang litrato.
function stopCamera() {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  flashBtn.style.display = 'none';
  flashBtn.classList.remove('on');
  torchOn = false;
}

// ---------- CAPTURE (shutter button) ----------
captureBtn.addEventListener('click', () => {
  if (!stream) {
    alert('Camera access has not been granted. Please allow camera permission.');
    return;
  }

  // Kung wala pa fully load ang camera video (sagad mahitabo sa mobile
  // kung dali ra kaayo mo-tap human mo-open ang page), ang videoWidth/
  // videoHeight kay 0 - naa'y "IndexSizeError" nga silent nga naga-crash sa
  // sunod nga linya (canvas 0x0) nga naga-stop sa pag-proceed padulong sa
  // Confirm/Retake buttons. I-check una aron dili na mahitabo ang crash.
  if (!camStream.videoWidth || !camStream.videoHeight) {
    alert('The camera is not fully loaded yet. Please wait 1-2 seconds, then tap again.');
    return;
  }

  try {
    // The preview uses object-fit: cover, so part of the camera picture is hidden.
    // Keep ONLY the visible part: the ID then fills more of the photo (bigger text = better reading).
    const vw = camStream.videoWidth;
    const vh = camStream.videoHeight;
    const box = camStream.getBoundingClientRect();
    let sx = 0, sy = 0, sw = vw, sh = vh;
    if (box.width > 0 && box.height > 0) {
      const scale = Math.max(box.width / vw, box.height / vh);
      sw = Math.min(vw, box.width / scale);
      sh = Math.min(vh, box.height / scale);
      sx = (vw - sw) / 2;
      sy = (vh - sh) / 2;
    }
    idCanvas.width = Math.round(sw);
    idCanvas.height = Math.round(sh);
    const ctx = idCanvas.getContext('2d');
    ctx.drawImage(camStream, sx, sy, sw, sh, 0, 0, idCanvas.width, idCanvas.height);

    idPreview.src = idCanvas.toDataURL('image/jpeg', 0.9);
    idPreview.style.display = 'block';
    camStream.style.display = 'none';

    stopCamera();

    captureRow.style.display = 'none';
    confirmRow.style.display = 'flex';
  } catch (err) {
    console.error('Capture error:', err);
    alert('Something went wrong while taking the photo. Please try again.');
  }
});

// ---------- CONFIRM (proceed to verification) ----------
confirmBtn.addEventListener('click', () => {
  showScreen(screenVerify);
  runVerification();
});

// ---------- RETAKE ----------
retakeBtn.addEventListener('click', () => {
  idPreview.style.display = 'none';
  idPreview.src = '';
  confirmRow.style.display = 'none';
  captureRow.style.display = 'flex';
  startCamera();
});

function showScreen(target) {
  [screenScan, screenVerify, screenResult].forEach(s => s.classList.remove('active'));
  target.classList.add('active');
}

// ---------- VERIFICATION ----------
// Flow: capture -> (browser) OCR kung kinahanglan -> send litrato + text sa server ->
// ang SERVER ang mo-check kung valid nga government ID, sakto ang ID number,
// naa ang ngalan sa MCRO records, ug motugma ang birthdate.

const MAX_UPLOAD_WIDTH = 2200;

// ---- picture variants for OCR -------------------------------------------------
// Different cards read best in different ways (white ePhilID = plain grey, glossy green
// PhilHealth = binarized / red channel, small license text = big + sharp), so the ID is
// read several ways and all the text is combined.
function scaledCanvas(src, targetW) {
  const scale = targetW / src.width;
  const c = document.createElement('canvas');
  c.width = Math.round(src.width * scale);
  c.height = Math.round(src.height * scale);
  const ctx = c.getContext('2d', { willReadFrequently: true });
  ctx.imageSmoothingQuality = 'high';
  ctx.drawImage(src, 0, 0, c.width, c.height);
  return c;
}

function grayPlane(canvas, mode) {
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const d = img.data;
  const n = canvas.width * canvas.height;
  const g = new Uint8ClampedArray(n);
  const hist = new Uint32Array(256);
  for (let i = 0, p = 0; i < d.length; i += 4, p++) {
    const v = (mode === 'red' ? d[i] : d[i] * 0.299 + d[i + 1] * 0.587 + d[i + 2] * 0.114) | 0;
    g[p] = v; hist[v]++;
  }
  // 2% - 98% contrast stretch
  let lo = 0, hi = 255, acc = 0;
  for (let i = 0; i < 256; i++) { acc += hist[i]; if (acc >= n * 0.02) { lo = i; break; } }
  acc = 0;
  for (let i = 255; i >= 0; i--) { acc += hist[i]; if (acc >= n * 0.02) { hi = i; break; } }
  const range = Math.max(1, hi - lo);
  for (let p = 0; p < n; p++) g[p] = Math.max(0, Math.min(255, ((g[p] - lo) * 255) / range));
  return { g, w: canvas.width, h: canvas.height, ctx, img };
}

function paintGray(plane) {
  const d = plane.img.data;
  for (let i = 0, p = 0; i < d.length; i += 4, p++) { d[i] = d[i + 1] = d[i + 2] = plane.g[p]; d[i + 3] = 255; }
  plane.ctx.putImageData(plane.img, 0, 0);
}

function variantGray(src, mode) {            // mode: 'gray' | 'red'
  const c = scaledCanvas(src, 2200);
  const plane = grayPlane(c, mode);
  paintGray(plane);
  return c;
}

function variantBinary(src) {                // adaptive threshold: removes glossy/coloured backgrounds
  const c = scaledCanvas(src, 2000);
  const plane = grayPlane(c, 'gray');
  const { w, h } = plane;
  // light 3x3 blur first so camera noise does not turn into speckles
  const src0 = plane.g;
  const g = new Float32Array(w * h);
  for (let y = 0; y < h; y++) {
    const ya = Math.max(0, y - 1), yb = Math.min(h - 1, y + 1);
    for (let x = 0; x < w; x++) {
      const xa = Math.max(0, x - 1), xb = Math.min(w - 1, x + 1);
      let sum = 0;
      for (let yy = ya; yy <= yb; yy++) for (let xx = xa; xx <= xb; xx++) sum += src0[yy * w + xx];
      g[y * w + x] = sum / ((yb - ya + 1) * (xb - xa + 1));
    }
  }
  const W = w + 1;
  const integral = new Float64Array(W * (h + 1));
  for (let y = 1; y <= h; y++) {
    let row = 0;
    for (let x = 1; x <= w; x++) {
      row += g[(y - 1) * w + (x - 1)];
      integral[y * W + x] = integral[(y - 1) * W + x] + row;
    }
  }
  const r = Math.max(12, Math.round(w / 40));
  const out = new Uint8ClampedArray(w * h);
  for (let y = 0; y < h; y++) {
    const y0 = Math.max(0, y - r), y1 = Math.min(h, y + r + 1);
    for (let x = 0; x < w; x++) {
      const x0 = Math.max(0, x - r), x1 = Math.min(w, x + r + 1);
      const area = (x1 - x0) * (y1 - y0);
      const mean = (integral[y1 * W + x1] - integral[y0 * W + x1] - integral[y1 * W + x0] + integral[y0 * W + x0]) / area;
      const v = g[y * w + x];
      // dark only if clearly darker than its surroundings (thin strokes, and no speckles in flat/dark areas)
      out[y * w + x] = (v < mean * 0.78 && mean - v > 18) ? 0 : 255;
    }
  }
  plane.g = out;
  paintGray(plane);
  return c;
}

function buildVariant(src, name) {
  if (name === 'bin') return variantBinary(src);
  if (name === 'red') return variantGray(src, 'red');
  if (name === 'orig') return scaledCanvas(src, 2000);
  return variantGray(src, 'gray');
}

// JPEG data-URL para i-send sa server (gi-limit ang gidak-on aron dili mabug-at)
function buildUploadImage(src) {
  let canvas = src;
  if (src.width > MAX_UPLOAD_WIDTH) {
    canvas = document.createElement('canvas');
    canvas.width = MAX_UPLOAD_WIDTH;
    canvas.height = Math.round(src.height * MAX_UPLOAD_WIDTH / src.width);
    canvas.getContext('2d').drawImage(src, 0, 0, canvas.width, canvas.height);
  }
  return canvas.toDataURL('image/jpeg', 0.9);
}

// First read: 2 passes. Only if the server cannot confirm the ID, a DEEPER read is done.
// PSM 6 / 11 (block / sparse text) read ID cards far better than PSM 3 on real phone photos.
const OCR_QUICK = [['gray', 6], ['red', 6]];
const OCR_DEEP  = [['orig', 6], ['gray', 11], ['bin', 11], ['red', 11]];

let ocrWorker = null;
async function ocrPasses(original, passes, label) {
  if (!ocrWorker) ocrWorker = await Tesseract.createWorker('eng');
  let text = '';
  for (let i = 0; i < passes.length; i++) {
    verifyStatus.textContent = label + ' (' + (i + 1) + '/' + passes.length + ')...';
    try {
      const canvas = buildVariant(original, passes[i][0]);
      await ocrWorker.setParameters({ tessedit_pageseg_mode: String(passes[i][1]), preserve_interword_spaces: '1' });
      const r = await ocrWorker.recognize(canvas);
      text += '\n' + r.data.text;
    } catch (e) {
      console.error('OCR pass failed:', passes[i], e);   // keep going with the other passes
    }
  }
  return text;
}

async function submitVerification(ocrText, deep) {
  const body = new URLSearchParams();
  body.set('ocr_text', ocrText);
  body.set('id_image', buildUploadImage(idCanvas));
  if (deep) body.set('deep', '1');
  const res = await fetch('complete-verification.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  });
  return res.json();
}

async function runVerification() {
  verifyStatus.textContent = 'Reading ID details...';
  try {
    let ocrText = '';
    try {
      ocrText = await ocrPasses(idCanvas, OCR_QUICK, 'Reading ID details');
    } catch (ocrErr) {
      // If Tesseract.js cannot load in this browser, keep going - the server may have its own OCR.
      console.error('Browser OCR error:', ocrErr);
    }

    verifyStatus.textContent = 'Checking ID and matching with MCRO records...';
    let data = await submitVerification(ocrText, false);

    // Could not confirm yet, but it is worth a closer look -> read the photo more carefully
    if (data && data.success && !data.verified && data.retry_ocr) {
      let more = '';
      try { more = await ocrPasses(idCanvas, OCR_DEEP, 'Taking a closer look'); } catch (e) { console.error(e); }
      verifyStatus.textContent = 'Checking again...';
      data = await submitVerification(ocrText + '\n' + more, true);
    }

    if (!data.success) {
      showResult({ verified: false, error: data.error || 'Verification failed. Please try again.' });
      return;
    }
    showResult(data);
  } catch (err) {
    console.error('Verification error:', err);
    showResult({ verified: false, error: 'Something went wrong while scanning the ID. Please try again with a clearer photo.' });
  } finally {
    if (ocrWorker) { try { await ocrWorker.terminate(); } catch (e) {} ocrWorker = null; }
  }
}

function escapeHtml(str) {
  return String(str == null ? '' : str).replace(/[&<>"']/g, ch => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]
  ));
}

// Checklist: unsa ang pasar ug unsa ang wala
function renderChecks(checks) {
  if (!checks || !checks.length) return '';
  const rows = checks.map(c => `
    <div class="check-row ${c.ok ? 'ok' : 'bad'}">
      <span class="check-mark">${c.ok ? '✓' : '✕'}</span>
      <span>${escapeHtml(c.label)}</span>
    </div>`).join('');
  return `<div class="check-list">${rows}</div>`;
}

function showResult(data) {
  const isVerified = !!data.verified;
  const extracted = data.extracted;
  const checksHtml = renderChecks(data.checks);

  if (isVerified) {
    let extractedHtml = '';
    if (extracted && (extracted.date_of_birth || extracted.barangay)) {
      extractedHtml = `
        <div class="extracted-info">
          <div class="ei-title">Auto-filled from your ID / MCRO record</div>
          ${extracted.date_of_birth ? `<div class="ei-row"><span>Date of Birth</span><span>${escapeHtml(extracted.date_of_birth)}</span></div>` : ''}
          ${extracted.barangay ? `<div class="ei-row"><span>Barangay</span><span>${escapeHtml(extracted.barangay)}</span></div>` : ''}
        </div>
      `;
    }
    resultWrap.innerHTML = `
      <div class="result-icon success">✓</div>
      <h2 class="result-title">Identity Verified</h2>
      <p class="result-desc">${data.id_type ? escapeHtml(data.id_type) + ' - ' : ''}Your ID is valid and your name matches our records in the MCRO database. You may now proceed.</p>
      ${checksHtml}
      ${extractedHtml}
    `;
    restartBtn.style.display = 'none';

    const continueBtn = document.createElement('button');
    continueBtn.className = 'btn-primary';
    continueBtn.textContent = 'Continue to Create Account';
    continueBtn.onclick = () => window.location.href = '../auth/welcome.php';
    resultWrap.appendChild(continueBtn);
  } else {
    // Ang debug info ipadala ra sa server kung ID_VERIFY_DEBUG = true (config/id-verification.php)
    let debugHtml = '';
    if (data.debug) {
      debugHtml = `
        <div class="extracted-info" style="text-align:left;">
          <div class="ei-title">Debug Info</div>
          <div class="ei-row" style="flex-direction:column;align-items:flex-start;gap:2px;">
            <span>Source: ${escapeHtml(data.debug.source || '')} | Type: ${escapeHtml(data.debug.id_type || '-')}</span>
            <span style="word-break:break-word;">${escapeHtml(data.debug.ocr_text_preview || '(nothing was read)')}</span>
          </div>
        </div>
      `;
    }
    let tipsHtml = '';
    if (data.tips && data.tips.length) {
      tipsHtml = '<div class="tips-list"><div class="tips-title">Tips for a better scan</div>' +
        data.tips.map(t => '<div class="tip-row">• ' + escapeHtml(t) + '</div>').join('') + '</div>';
    }
    resultWrap.innerHTML = `
      <div class="result-icon fail">✕</div>
      <h2 class="result-title">Verification Failed</h2>
      <p class="result-desc">${escapeHtml(data.error || "We couldn't match your ID with our records. Please try scanning again with a clearer image.")}</p>
      ${checksHtml}
      ${tipsHtml}
      ${debugHtml}
    `;
    restartBtn.style.display = 'block';
  }
  showScreen(screenResult);
}

// ---------- RESTART (from result screen) ----------
restartBtn.addEventListener('click', () => {
  idPreview.style.display = 'none';
  idPreview.src = '';
  confirmRow.style.display = 'none';
  captureRow.style.display = 'flex';
  showScreen(screenScan);
  startCamera();
});
