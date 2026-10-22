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
  var POOL_SIZE = 1;
  var pool = []; // [{ promise: Promise<Worker|null>, busy: bool, onProgress: fn|null }]

  function ensurePool() {
    if (pool.length > 0) return pool;
    if (typeof Tesseract === 'undefined') return pool; // stays empty - Tesseract.js didn't load
    for (var i = 0; i < POOL_SIZE; i++) {
      (function (slot) {
        slot.promise = Tesseract.createWorker('eng', 1, {
          logger: function (m) {
            if (slot.onProgress && m.status === 'recognizing text' && typeof m.progress === 'number') {
              slot.onProgress(Math.round(m.progress * 100), slot.phase || 'reading');
            }
          },
        }).catch(function () { return null; });
      })(pool[pool.push({ promise: null, busy: false, onProgress: null, phase: null }) - 1]);
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

  // ------------------------------------------------------------------
  // Photo enhancement - used ONLY for a 2nd pass when the plain 1st pass
  // came out unreliable (typically a phone photo of a tinted/yellowed or
  // shadowed copy). Clean scans never go through this, because on them
  // the plain pass is already as good or better (tested).
  //   1. Use the BLUE channel only: ink stays dark on yellow/green paper.
  //   2. Stretch contrast between the 1st and 99th brightness percentile.
  //   3. Adaptive (local-mean) threshold -> black text on white, removes
  //      shadows/uneven lighting that fool Tesseract.
  // enhancePixels() is a pure function on {data (RGBA), width, height} so
  // it can be unit-tested outside the browser.
  // ------------------------------------------------------------------
  function enhancePixels(img) {
    var w = img.width, h = img.height, d = img.data, n = w * h, i, x, y;
    var g = new Uint8ClampedArray(n);
    for (i = 0; i < n; i++) g[i] = d[i * 4 + 2];
    var hist = new Uint32Array(256);
    for (i = 0; i < n; i++) hist[g[i]]++;
    var lo = 0, hi = 255, acc = 0, cut = n * 0.01;
    for (i = 0; i < 256; i++) { acc += hist[i]; if (acc >= cut) { lo = i; break; } }
    acc = 0;
    for (i = 255; i >= 0; i--) { acc += hist[i]; if (acc >= cut) { hi = i; break; } }
    if (hi - lo < 16) { lo = 0; hi = 255; } // almost flat image - don't blow up noise
    var span = hi - lo;
    for (i = 0; i < n; i++) {
      var v = (g[i] - lo) * 255 / span;
      g[i] = v < 0 ? 0 : (v > 255 ? 255 : v);
    }
    // integral image -> fast local mean
    var W1 = w + 1, integ = new Uint32Array(W1 * (h + 1));
    for (y = 0; y < h; y++) {
      var rowSum = 0;
      for (x = 0; x < w; x++) {
        rowSum += g[y * w + x];
        integ[(y + 1) * W1 + x + 1] = integ[y * W1 + x + 1] + rowSum;
      }
    }
    var r = Math.max(10, Math.round(Math.max(w, h) / 86)); // ~60px window on a 2600px photo
    var offset = 15;                                       // ~6% of the 0-255 range
    for (y = 0; y < h; y++) {
      var y0 = Math.max(0, y - r), y1 = Math.min(h - 1, y + r);
      for (x = 0; x < w; x++) {
        var x0 = Math.max(0, x - r), x1 = Math.min(w - 1, x + r);
        var cnt = (x1 - x0 + 1) * (y1 - y0 + 1);
        var sum = integ[(y1 + 1) * W1 + x1 + 1] - integ[y0 * W1 + x1 + 1] - integ[(y1 + 1) * W1 + x0] + integ[y0 * W1 + x0];
        var out = g[y * w + x] > sum / cnt - offset ? 255 : 0;
        var p = (y * w + x) * 4;
        d[p] = d[p + 1] = d[p + 2] = out;
        d[p + 3] = 255;
      }
    }
    return img;
  }

  /** Returns an enhanced PNG Blob of the photo (max maxDim px, never upscaled), or null on any error. */
  function enhance(file, maxDim) {
    return new Promise(function (resolve) {
      if (!file || !window.Image) { resolve(null); return; }
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        try {
          var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
          var scale = Math.min(1, maxDim / Math.max(w, h));
          var canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(w * scale));
          canvas.height = Math.max(1, Math.round(h * scale));
          var ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          URL.revokeObjectURL(url);
          var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          enhancePixels(imageData);
          ctx.putImageData(imageData, 0, 0);
          canvas.toBlob(function (blob) { resolve(blob || null); }, 'image/png');
        } catch (e) {
          URL.revokeObjectURL(url);
          resolve(null);
        }
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
      img.src = url;
    });
  }

  // How reliable a pass is, judged by Tesseract's own word confidences:
  // "solid" = words of 3+ letters it is more than 70% sure about.
  function countSolid(data) {
    var i, n = 0;
    if (data && Array.isArray(data.words) && data.words.length) {
      for (i = 0; i < data.words.length; i++) {
        var wd = data.words[i];
        if (wd && wd.confidence > 70 && String(wd.text || '').replace(/[^A-Za-z]/g, '').length >= 3) n++;
      }
      return n;
    }
    if (data && typeof data.tsv === 'string') { // tsv columns: ... conf(10) text(11)
      var lines = data.tsv.split('\n');
      for (i = 1; i < lines.length; i++) {
        var c = lines[i].split('\t');
        if (c.length >= 12 && parseFloat(c[10]) > 70 && c[11].replace(/[^A-Za-z]/g, '').length >= 3) n++;
      }
      return n;
    }
    // Last resort (no per-word data available): scale the word count by overall confidence.
    var words = String((data && data.text) || '').split(/\s+/).filter(function (t) { return t.replace(/[^A-Za-z]/g, '').length >= 3; }).length;
    return Math.round(words * ((data && data.confidence) || 0) / 100);
  }

  // Measured end-to-end (OCR -> field extraction, 3 real certificates): plain 2400px = 9.2/15,
  // plain 1800px = 8.0/15, enhanced 2nd pass = 5.7-7.2/15. The enhanced text reads more words but
  // its line layout breaks the field extractor, so the 2nd pass is OFF. Code kept for later use.
  var ENABLE_ENHANCED_PASS = false;
  var PLAIN_MAX_DIM = 2400;
  var ENHANCE_BELOW = 65;   // plain pass with mean confidence under this -> try the enhanced 2nd pass
  var ENHANCE_MAX_DIM = 2600; // enhanced pass needs near-full resolution (tested: 1800px loses the small print)
  var MIN_SOLID_WORDS = 30; // fewer solid words than this = nothing usable was read

  async function ocrPass(worker, image, psm) {
    try { await worker.setParameters({ tessedit_pageseg_mode: psm }); } catch (e) { /* keep going with whatever mode is set */ }
    var res = await worker.recognize(image);
    var data = (res && res.data) || {};
    return {
      text: data.text || '',
      confidence: typeof data.confidence === 'number' ? data.confidence : 0,
      solid: countSolid(data),
      enhanced: false,
    };
  }

  /** Plain pass first; only if it looks unreliable, an enhanced 2nd pass - keep whichever read more solid words. */
  async function recognizeOne(slot, worker, file) {
    slot.phase = 'reading';
    var best = await ocrPass(worker, await downscale(file, PLAIN_MAX_DIM), '3');
    if (ENABLE_ENHANCED_PASS && best.confidence < ENHANCE_BELOW) {
      slot.phase = 'enhancing';
      try {
        var enhancedImage = await enhance(file, ENHANCE_MAX_DIM);
        if (enhancedImage) {
          var second = await ocrPass(worker, enhancedImage, '6');
          second.enhanced = true;
          if (second.solid > best.solid) best = second;
        }
      } catch (e) { /* 2nd pass failed - the plain result is still there */ }
      try { await worker.setParameters({ tessedit_pageseg_mode: '3' }); } catch (e) {}
    }
    return best;
  }

  /**
   * Same as recognize(), but returns details so the caller can judge quality:
   *   { text, confidence, solid, enhanced, usable }
   * usable === false means almost nothing readable came out (callers should
   * NOT trust `text` then - e.g. let the server's own OCR try instead).
   * Never throws.
   */
  async function recognizeDetailed(files, onProgress) {
    var empty = { text: '', confidence: 0, solid: 0, enhanced: false, usable: false };
    var slot = await acquireSlot();
    if (!slot) return empty;
    var worker = await slot.promise;
    slot.onProgress = onProgress || null;
    var combined = '';
    var out = { text: '', confidence: 0, solid: 0, enhanced: false, usable: false };
    try {
      for (var i = 0; i < files.length; i++) {
        if (!files[i]) continue;
        if (i > 0 && combined.replace(/\s+/g, '').length > 40) break; // 1st photo was enough
        try {
          var r = await recognizeOne(slot, worker, files[i]);
          combined += '\n' + r.text;
          out.solid += r.solid;
          out.enhanced = out.enhanced || r.enhanced;
          if (i === 0) out.confidence = r.confidence;
        } catch (e) {
          // Skip this photo's OCR, keep whatever was already recognized.
        }
      }
    } finally {
      slot.onProgress = null;
      slot.phase = null;
      slot.busy = false; // free this worker for the next queued call
    }
    out.text = combined.trim();
    out.usable = out.solid >= MIN_SOLID_WORDS;
    return out;
  }

  /**
   * Runs in-browser OCR on up to 2 photo Files (same certificate side -
   * e.g. [front1, front2]) and returns the combined recognized text.
   * (Unchanged contract: still returns a plain string and never throws -
   * civil-registry-form.php relies on this. Use recognizeDetailed() to also
   * get a quality verdict.)
   */
  async function recognize(files, onProgress) {
    var r = await recognizeDetailed(files, onProgress);
    return r.text;
  }

  return { recognize: recognize, recognizeDetailed: recognizeDetailed, POOL_SIZE: POOL_SIZE, _enhancePixels: enhancePixels };
})();
