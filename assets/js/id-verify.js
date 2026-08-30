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

const screenScan = document.getElementById('screen-scan');
const screenVerify = document.getElementById('screen-verify');
const screenResult = document.getElementById('screen-result');
const resultWrap = document.getElementById('resultWrap');
const verifyStatus = document.getElementById('verifyStatus');

let stream = null;

// ---------- CAMERA ----------
async function startCamera() {

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert("Camera API not supported sa browser.");
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' }
      },
      audio: false
    });

    camStream.srcObject = stream;
    camStream.style.display = 'block';
    camError.style.display = 'none';

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
// ---------- CAPTURE (shutter button) ----------
captureBtn.addEventListener('click', () => {
  if (!stream) {
    alert('Wala pa naka-access ang camera. Palihug tugoti ang camera permission.');
    return;
  }
  const w = camStream.videoWidth;
  const h = camStream.videoHeight;
  idCanvas.width = w;
  idCanvas.height = h;
  const ctx = idCanvas.getContext('2d');
  ctx.drawImage(camStream, 0, 0, w, h);

  idPreview.src = idCanvas.toDataURL('image/jpeg', 0.9);
  idPreview.style.display = 'block';
  camStream.style.display = 'none';

  stopCamera();

  captureRow.style.display = 'none';
  confirmRow.style.display = 'flex';
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
// ---------- VERIFICATION (real OCR via Tesseract.js) ----------
function runVerification() {
  verifyStatus.textContent = 'Reading ID details...';

  const steps = [
    'Reading ID details...',
    'Matching with MCRO database...',
    'Finalizing verification...'
  ];
  let i = 0;
  const stepInterval = setInterval(() => {
    if (i < steps.length) {
      verifyStatus.textContent = steps[i];
      i++;
    }
  }, 1400);

  // 1) OCR the captured ID image right here in the browser.
  Tesseract.recognize(idCanvas, 'eng', { logger: () => {} })
    .then(({ data: { text } }) => {
      // 2) Send the extracted text to the backend, which compares it
      //    against the matched MCRO record (or logged-in user's name).
      return fetch('complete-verification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ocr_text=' + encodeURIComponent(text)
      });
    })
    .then(res => res.json())
    .then(data => {
      clearInterval(stepInterval);
      if (!data.success) {
        showResult(false, data.error || 'Wala mi-verify. Sulayi pag-usab.');
        return;
      }
      showResult(!!data.verified, data.error, data.extracted);
    })
    .catch(err => {
      clearInterval(stepInterval);
      console.error('OCR/verification error:', err);
      showResult(false, 'Naay sayop sa pag-scan sa ID. Sulayi pag-usab gamit ang mas klaro nga litrato.');
    });
}

function showResult(isVerified, failMessage, extracted) {
  if (isVerified) {
    let extractedHtml = '';
    if (extracted && (extracted.date_of_birth || extracted.barangay)) {
      extractedHtml = `
        <div class="extracted-info">
          <div class="ei-title">Auto-filled from your ID</div>
          ${extracted.date_of_birth ? `<div class="ei-row"><span>Date of Birth</span><span>${extracted.date_of_birth}</span></div>` : ''}
          ${extracted.barangay ? `<div class="ei-row"><span>Barangay</span><span>${extracted.barangay}</span></div>` : ''}
        </div>
      `;
    }
    resultWrap.innerHTML = `
      <div class="result-icon success">✓</div>
      <h2 class="result-title">Identity Verified</h2>
      <p class="result-desc">Your ID information matches our records in the MCRO database. You may now proceed.</p>
      ${extractedHtml}
    `;
    restartBtn.style.display = 'none';

    const continueBtn = document.createElement('button');
    continueBtn.className = 'btn-primary';
    continueBtn.textContent = 'Continue to Create Account';
    continueBtn.onclick = () => window.location.href = '../auth/welcome.php';
    resultWrap.appendChild(continueBtn);
  } else {
    resultWrap.innerHTML = `
      <div class="result-icon fail">✕</div>
      <h2 class="result-title">Verification Failed</h2>
      <p class="result-desc">${failMessage || "We couldn't match your ID with our records. Please try scanning again with a clearer image."}</p>
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