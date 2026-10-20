<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/name-format.php';
require_once __DIR__ . '/lib/CertificateFormFields.php';
require_once __DIR__ . '/lib/CertificateDocument.php';

$recordId = (int) ($_GET['id'] ?? 0);
if ($recordId <= 0) { header('Location: civil-registry.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
$stmt->execute([$recordId]);
$row = $stmt->fetch();
if (!$row) { header('Location: civil-registry.php'); exit; }

if (empty($row['certificate_data'])) {
    // Older / plain records never went through Scan & Upload, so there is no
    // full official-form data to print exactly - send staff to the existing
    // summary View page instead.
    header('Location: civil-registry-view.php?id=' . $recordId);
    exit;
}

$type = $row['registry_type'];
$d = json_decode($row['certificate_data'], true) ?: [];
function v($d, $k) { return htmlspecialchars($d[$k] ?? ''); }
function vraw($d, $k) { return $d[$k] ?? ''; }

$pageTitle = 'Print Certificate';
$activeStaffNav = 'registry';
require_once __DIR__ . '/includes/staff-header.php';
?>
<style>
  /* ---------------------------------------------------------------
     Long bond paper (8.5in x 13in) replica of the official Municipal
     Form - Front and Back rendered as two separate pages, exactly
     like the real quadruplicate form. Toggle buttons switch which
     side is shown on screen; printing always outputs BOTH pages
     (Front on page 1, Back on page 2).

     The box-grid / affidavit-block / cert-head styling itself now
     lives in lib/CertificateDocument.php (cert_document_css()) so
     this print page and the editable Verify-Scan screen always
     render the identical layout - only the print-only page/paper
     rules stay here.
  --------------------------------------------------------------- */
  @page { size: 8.5in 13in; margin: 0.4in; }
  @media print {
    .sidebar, .topbar, .back-to-profile, .cr-print-actions, .page-toggle, #toastStack { display:none !important; }
    body, .main-content, .content-area { background:#fff !important; margin:0 !important; padding:0 !important; }
    .cert-page { box-shadow:none !important; border:none !important; display:block !important; margin:0 auto !important; }
    .cert-page.front { page-break-after: always; }
  }

  .cert-doc-wrap{display:flex;flex-direction:column;align-items:center;padding:10px 0 40px;}

  .page-toggle{display:flex;gap:8px;margin-bottom:16px;background:var(--bg-page);border:1px solid var(--border);border-radius:10px;padding:4px;}
  .page-toggle button{border:none;background:transparent;padding:8px 22px;border-radius:8px;font-size:12.5px;font-weight:700;color:var(--text-secondary);cursor:pointer;}
  .page-toggle button.active{background:#fff;color:var(--primary);box-shadow:0 1px 4px rgba(0,0,0,.12);}

  .cert-page{
    display:none;width:8.5in;min-height:12.2in;
    box-shadow:0 8px 30px rgba(0,0,0,.15);border:1px solid #ccc;
    padding:0.35in 0.45in;box-sizing:border-box;
    margin-bottom:26px;
  }
  .cert-page.active{display:block;}

  <?= cert_document_css() ?>
  .cr-print-actions{display:flex;gap:10px;justify-content:center;margin:18px 0 4px;}
</style>

<a href="civil-registry.php" class="back-to-profile" style="display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;color:var(--text-secondary);text-decoration:none;font-size:13px;font-weight:600;">
  ← Back to Civil Registry Records
</a>

<div class="cr-print-actions">
  <div class="page-toggle">
    <button type="button" class="active" id="btnFront" onclick="showSide('front')">📄 Front</button>
    <button type="button" id="btnBack" onclick="showSide('back')">📄 Back</button>
  </div>
</div>
<div class="cr-print-actions">
  <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Print Certificate (Front &amp; Back)</button>
</div>

<div class="cert-doc-wrap">

<?php
// ---------- footer helper (print-only: date prepared + record id stamp) ----------
function certFooter($d, $recordId) {
    echo '<div style="margin-top:16px;font-size:9.5px;color:#777;display:flex;justify-content:space-between;border-top:1px solid #ccc;padding-top:6px;">';
    echo '<span>Date Prepared: ' . (v($d, 'date_prepared') ?: '&mdash;') . '</span>';
    echo '<span>Record ID: MCRO-' . str_pad((string) $recordId, 6, '0', STR_PAD_LEFT) . '</span></div>';
}
?>

<!-- ============================== FRONT ============================== -->
<div class="cert-page front active" id="pageFront">
<?php cert_render_front($type, $d, false); ?>
<?php certFooter($d, $recordId); ?>
</div>
<!-- ============================== END FRONT ============================== -->

<!-- ============================== BACK ============================== -->
<div class="cert-page back" id="pageBack">
<?php cert_render_back($type, $d, false); ?>
<?php certFooter($d, $recordId); ?>
</div>
<!-- ============================== END BACK ============================== -->

</div>
<script>
  function showSide(side) {
    document.getElementById('pageFront').classList.toggle('active', side === 'front');
    document.getElementById('pageBack').classList.toggle('active', side === 'back');
    document.getElementById('btnFront').classList.toggle('active', side === 'front');
    document.getElementById('btnBack').classList.toggle('active', side === 'back');
  }
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>
