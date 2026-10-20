<?php
// GeoGene Admin - shared-module embed wrapper.
//
// Civil Registry Records, Family Tree, and Map View are the SAME data and
// the SAME code as the Staff portal (per the project requirement: "do not
// create a separate Admin database/records - use the same records as
// Staff"). Since the Staff files themselves must never be modified, this
// wrapper keeps the browser on an /admin/... URL, with the Admin
// sidebar/topbar staying visible the whole time, while the actual shared
// page loads in an iframe. The iframe's OWN header/sidebar (which belongs
// to the Staff portal, unmodified) is hidden purely via JavaScript/CSS
// injected FROM THIS PAGE after it loads - no staff file is edited.
//
// Expects: $embedSrc (relative path to the staff page), $pageTitle, $pageSub.
require_once __DIR__ . '/admin-auth-guard.php';
require_once __DIR__ . '/../../config/db.php';

// The embedded Staff page already renders its own page title/description
// at the top (it's the exact same page Staff sees) - showing this admin
// page's own $pageTitle/$pageSub above it as well just repeats the same
// heading twice. So only the Staff page's own heading is shown; $pageTitle
// is still used for the browser tab title and the iframe's title attribute.
$suppressPageHeading = true;
require_once __DIR__ . '/admin-header.php';
?>

<div class="panel" style="padding:0;overflow:hidden;">
  <?php
    // Cache-bust every time this Admin page loads, so the browser can
    // never serve a stale cached copy of the embedded Staff page inside
    // the iframe (this has been observed to happen even after a hard
    // refresh of the outer Admin page in some browsers).
    $embedSrcBusted = $embedSrc . (strpos($embedSrc, '?') !== false ? '&' : '?') . '_t=' . time();
  ?>
  <iframe id="embedFrame" src="<?= htmlspecialchars($embedSrcBusted) ?>" title="<?= htmlspecialchars($pageTitle) ?>"
    style="width:100%;height:560px;border:none;display:block;background:#fff;"></iframe>
</div>

<script>
(function () {
  var frame = document.getElementById('embedFrame');
  var resizeObserver = null;

  function resizeFrameToContent(doc) {
    try {
      var target = doc.documentElement || doc.body;
      var h = Math.max(
        doc.body ? doc.body.scrollHeight : 0,
        doc.documentElement ? doc.documentElement.scrollHeight : 0
      );
      if (h > 0) frame.style.height = h + 'px';
    } catch (e) {}
  }

  function stripEmbeddedChrome() {
    try {
      var doc = frame.contentDocument || frame.contentWindow.document;
      if (!doc || !doc.head) return;

      if (!doc.getElementById('geogeneAdminEmbedStyle')) {
        var style = doc.createElement('style');
        style.id = 'geogeneAdminEmbedStyle';
        // Hide the Staff portal's own sidebar/topbar - this page already
        // has its own Admin sidebar/topbar, so showing both would be
        // redundant. Also disable the embedded page's own scrolling: the
        // iframe is auto-resized to fit its content below, so only the
        // OUTER Admin page ever scrolls - never two scrollbars at once.
        style.textContent =
          '.sidebar{display:none !important;}' +
          '.topbar{display:none !important;}' +
          '.content-area{padding-top:18px !important;}' +
          'html,body{overflow:hidden !important;height:auto !important;}';
        doc.head.appendChild(style);
      }

      // Fit the iframe's height to its actual content, then keep it in
      // sync as the embedded page's content changes (search, pagination,
      // opening a record, expanding the family tree, etc).
      resizeFrameToContent(doc);
      if (resizeObserver) resizeObserver.disconnect();
      if (window.ResizeObserver && doc.body) {
        resizeObserver = new ResizeObserver(function () { resizeFrameToContent(doc); });
        resizeObserver.observe(doc.body);
      }
      // Fallback for content changes ResizeObserver might miss (e.g. map
      // tiles loading in), and for browsers without ResizeObserver.
      setTimeout(function () { resizeFrameToContent(doc); }, 400);
      setTimeout(function () { resizeFrameToContent(doc); }, 1200);
    } catch (e) {
      // Cross-origin or not-yet-ready - the embedded page still works fine
      // on its own, it just keeps its own scrollbar/header/sidebar.
    }
  }

  frame.addEventListener('load', stripEmbeddedChrome);
})();
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
