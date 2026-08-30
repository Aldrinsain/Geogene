<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ---------- Pre-registration mode: gamiton ang matched_record_id gikan sa
// Search Full Name step (dili na kinahanglan naka-login para maka-scan) ----------
if (isset($_GET['record_id'])) {
    $_SESSION['matched_record_id'] = (int) $_GET['record_id'];
}
if (isset($_GET['name'])) {
    $_SESSION['search_full_name'] = $_GET['name'];
}

// Kung naka-login na gyud (returning user nga gi-redirect diri para sa re-scan,
// bisan tuod dili na kini kasagaran mahitabo kay usa ra ka higayon ang scan),
// tugotan pud siya. Apan kung wala gyud naka-login UG walay matched_record_id,
// balik siya sa Search Full Name.
if (!isset($_SESSION['user_id']) && empty($_SESSION['matched_record_id'])) {
    header('Location: ../identity-verification/search-name.php');
    exit;
}
// GeoGene - ID Verification Flow (Scan -> Verify -> Result)
// Ibutang ni nga file ug ang id_verify.css / id_verify.js sa parehas nga folder sa XAMPP htdocs
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - ID Verification</title>
<link rel="stylesheet" href="../assets/css/id-verify.css">
</head>
<body>

<div class="phone">

  <!-- SCREEN 1: SCAN ID -->
  <section class="screen active" id="screen-scan">
    <header class="app-header">GeoGene</header>

    <h1 class="title">Scan Your ID</h1>
    <p class="subtitle">Place your valid government-issued ID within the frame for identity verification.</p>

    <div class="id-frame" id="idFrame">
      <video id="camStream" class="cam-stream" autoplay playsinline muted></video>
      <img id="idPreview" class="id-preview" alt="ID preview" style="display:none;">
      <div class="scan-corners">
        <span class="corner tl"></span><span class="corner tr"></span>
        <span class="corner bl"></span><span class="corner br"></span>
      </div>
      <p class="cam-error" id="camError" style="display:none;">Dili ma-access ang camera. Palihug tugoti ang camera permission unya i-reload ang page.</p>
      <canvas id="idCanvas" style="display:none;"></canvas>
    </div>

    <div class="tip-box">
      <span class="tip-icon">🛡</span>
      <span>Make sure your ID is clear, complete, and not blurry.</span>
    </div>

    <div class="capture-row" id="captureRow">
      <button class="capture-shutter" id="captureBtn" title="Capture ID" aria-label="Capture ID">
        <span class="shutter-inner"></span>
      </button>
      <span class="capture-hint">Tap to capture</span>
    </div>

    <div class="confirm-row" id="confirmRow" style="display:none;">
      <button class="btn-primary" id="confirmBtn"><span class="cam-icon">✔</span> Confirm &amp; Verify</button>
      <button class="btn-secondary" id="retakeBtn">Retake</button>
    </div>
  </section>

  <!-- SCREEN 2: VERIFYING -->
  <section class="screen" id="screen-verify">
    <header class="app-header">GeoGene</header>

    <h1 class="title">Verifying Information</h1>
    <p class="subtitle">Please wait while we verify your ID information with the MCRO database.</p>

    <div class="morph-wrap">
      <div class="morph-blob"></div>
    </div>

    <p class="verify-status" id="verifyStatus">Verifying information...</p>

    <div class="tip-box">
      <span class="tip-icon">🛡</span>
      <span>Your data is secure and will only be used for verification.</span>
    </div>
  </section>

  <!-- SCREEN 3: RESULT -->
  <section class="screen" id="screen-result">
    <header class="app-header">GeoGene</header>

    <div class="result-wrap" id="resultWrap">
      <!-- filled dynamically by JS -->
    </div>

    <button class="btn-primary" id="restartBtn">Try Again</button>
  </section>

</div>

<!-- Tesseract.js: runs OCR directly in the person's browser (WebAssembly),
     no server-side installation needed. -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="../assets/js/id-verify.js"></script>
</body>
</html>
