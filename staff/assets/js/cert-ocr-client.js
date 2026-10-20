/**
 * cert-ocr-client.js
 * ------------------------------------------------------------------
 * Shared in-browser OCR helper (wraps Tesseract.js) used by BOTH:
 *   - civil-registry-form.php  (single "SCAN FRONT / SCAN BACK" buttons)
 *   - civil-registry-bulk-scan.php  (Bulk Scan - many photos in one go)
 *
 * Why this exists: reading a full-resolution phone photo (often 3000-4000px
 * wide) with Tesseract.js is what actually makes scanning feel "grabe ka
 * dugay" (really slow) - both pages had their own copy of this logic, so a
 * speed fix in one place kept getting missed in the other. Now there's one
 * place to tune:
 *   1. Downscale each photo to ~1800px on its longest side before OCR -
 *      Tesseract doesn't read printed form text any more accurately much
 *      above that, but recognition time scales roughly with pixel count.
 *   2. Only OCR a 2nd/backup photo of the same side if the 1st one didn't
 *      already produce enough text - running OCR twice roughly doubles
 *      the time for little benefit in the normal case.
 *   3. A small POOL of Tesseract.js workers (not just one) so Bulk Scan can
 *      genuinely read several certificates AT THE SAME TIME instead of one
 *      strictly after another - recognize() calls automatically queue for
 *      the next free worker in the pool.
 *
 * Requires the Tesseract.js CDN script to already be loaded on the page
 * (see the <script src=".../tesseract.min.js"> tag before this file).
 * Every function here degrades gracefully to "" if Tesseract.js isn't
 * available (e.g. no internet) - callers should treat that as "let the
 * server's own OCR try instead", never as a hard error.
 */
window.CertOcrClient = (function () {
  // How many certificates can be read AT ONCE. Each worker is a separate
  // Tesseract instance (its own WASM + language data in memory), so this
  // trades RAM/CPU for speed - 3 is a reasonable default for a normal
  // laptop/desktop. civil-registry-bulk-scan.php's own concurrency limit
  // for uploads should stay <= this number to actually benefit from it.
  var POOL_SIZE = 3;
  var pool = []; // [{ promise: Promise<Worker|null>, busy: bool, onProgress: fn|null }]

  function ensurePool() {
    if (pool.length > 0) return pool;
    if (typeof Tesseract === 'undefined') return pool; // stays empty - Tesseract.js didn't load
    for (var i = 0; i < POOL_SIZE; i++) {
      (function (slot) {
        slot.promise = Tesseract.createWorker('eng', 1, {
          logger: function (m) {
            if (slot.onProgress && m.status === 'recognizing text' && typeof m.progress === 'number') {
              slot.onProgress(Math.round(m.progress * 100));
            }
          },
        }).catch(function () { return null; });
      })(pool[pool.push({ promise: null, busy: false, onProgress: null }) - 1]);
    }
    return pool;
  }

  /** Waits for (and reserves) the next free worker slot. Resolves to null
   *  if Tesseract.js itself never loaded, so callers can bail out cleanly. */
  async function acquireSlot() {
    var slots = ensurePool();
    if (slots.length === 0) return null;
    for (;;) {
      for (var i = 0; i < slots.length; i++) {
        if (!slots[i].busy) {
          slots[i].busy = true;
          var worker = await slots[i].promise;
          if (!worker) { slots[i].busy = false; continue; } // that worker failed to init - try another slot
          return slots[i];
        }
      }
      await new Promise(function (r) { setTimeout(r, 60); }); // all busy - wait for one to free up
    }
  }

  /** Shrinks a photo to at most maxDim px on its longest side. Only affects
   *  the copy sent to Tesseract - callers keep uploading the ORIGINAL file
   *  for storage/printing. Falls back to the original file on any error. */
  function downscale(file, maxDim) {
    return new Promise(function (resolve) {
      if (!file || !window.Image) { resolve(file); return; }
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        try {
          var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
          var scale = Math.min(1, maxDim / Math.max(w, h));
          if (scale >= 1) { URL.revokeObjectURL(url); resolve(file); return; }
          var canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(w * scale));
          canvas.height = Math.max(1, Math.round(h * scale));
          var ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          URL.revokeObjectURL(url);
          canvas.toBlob(function (blob) { resolve(blob || file); }, 'image/jpeg', 0.85);
        } catch (e) {
          URL.revokeObjectURL(url);
          resolve(file);
        }
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }

  /**
   * Runs in-browser OCR on up to 2 photo Files (same certificate side -
   * e.g. [front1, front2]) and returns the combined recognized text.
   * onProgress(percent) is called repeatedly while the CURRENT photo is
   * being read (optional). Safe to call this many times concurrently
   * (e.g. one call per certificate in a Bulk Scan batch) - each call
   * grabs its own worker from the pool and queues if all are busy.
   * Returns "" if Tesseract.js isn't available/fails to load - never
   * throws, so callers can always safely fall back to server-side OCR.
   */
  async function recognize(files, onProgress) {
    var slot = await acquireSlot();
    if (!slot) return '';
    var worker = await slot.promise;
    slot.onProgress = onProgress || null;
    var combined = '';
    try {
      for (var i = 0; i < files.length; i++) {
        if (!files[i]) continue;
        if (i > 0 && combined.replace(/\s+/g, '').length > 40) break; // 1st photo was enough
        try {
          var small = await downscale(files[i], 1800);
          var result = await worker.recognize(small);
          combined += '\n' + (result && result.data && result.data.text ? result.data.text : '');
        } catch (e) {
          // Skip this photo's OCR, keep whatever was already recognized.
        }
      }
    } finally {
      slot.onProgress = null;
      slot.busy = false; // free this worker for the next queued call
    }
    return combined.trim();
  }

  return { recognize: recognize, POOL_SIZE: POOL_SIZE };
})();
