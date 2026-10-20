/*!
 * gg-datepicker.js - GeoGene calendar date picker
 * ------------------------------------------------------------------
 * Turns every   <input class="dateMaskDisplay" data-real="REAL_ID">   into:
 *   - a typed mm/dd/yyyy field (auto-masked, validated), AND
 *   - a Material-style calendar dialog: "Select date" header with the chosen
 *     date, a pencil button that switches to type-in mode, a "August 2025 v"
 *     year/month chooser, prev/next arrows, and Clear / Cancel / OK actions.
 *
 * The REAL value (what PHP receives / MySQL stores) lives in the element
 * with id = data-real, always as ISO  yyyy-mm-dd  (or "" when empty/invalid).
 * Several displays may share one real input (e.g. Date of Birth / Marriage /
 * Death all feed #dateOfEventReal) - they are kept in sync.
 *
 * Optional attributes on the display input:
 *   data-min="1850-01-01"      earliest allowed date (default 1850-01-01)
 *   data-max="today|YYYY-MM-DD" latest allowed date   (default today)
 *   data-not-after="REAL_ID"   date may not be later than that real input
 *   data-label="Date Prepared" name used in error messages
 *
 * Public API:  GGDatePicker.autoInit(root?)  /  GGDatePicker.refresh(realId)
 * No dependencies.
 */
(function (global) {
  'use strict';

  var MIN_ISO = '1850-01-01';
  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                'August', 'September', 'October', 'November', 'December'];
  var MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var DAYS_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  var DAYS_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  var WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

  /* ---------------- date helpers (all ISO strings, local time) ---------------- */
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); } // m is 1-based
  function validYMD(y, m, d) {
    if (!(y >= 1000 && m >= 1 && m <= 12 && d >= 1)) return false;
    var dt = new Date(y, m - 1, d);
    return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
  }
  function parseISO(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
    if (!m) return null;
    var y = +m[1], mo = +m[2], d = +m[3];
    return validYMD(y, mo, d) ? { y: y, m: mo, d: d } : null;
  }
  function parseDisplay(s) {
    var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s || '');
    if (!m) return null;
    var mo = +m[1], d = +m[2], y = +m[3];
    return validYMD(y, mo, d) ? { y: y, m: mo, d: d } : null;
  }
  function toDisplay(p) { return pad(p.m) + '/' + pad(p.d) + '/' + p.y; }
  function todayISO() {
    var n = new Date();
    return iso(n.getFullYear(), n.getMonth() + 1, n.getDate());
  }
  function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); } // m 1-based
  function addDays(s, n) {
    var p = parseISO(s);
    var dt = new Date(p.y, p.m - 1, p.d + n);
    return iso(dt.getFullYear(), dt.getMonth() + 1, dt.getDate());
  }
  function addMonths(s, n) {
    var p = parseISO(s);
    var t = new Date(p.y, p.m - 1 + n, 1);
    var y = t.getFullYear(), m = t.getMonth() + 1;
    return iso(y, m, Math.min(p.d, daysInMonth(y, m)));
  }
  function digitsToDisplay(digits) {
    digits = String(digits).replace(/\D/g, '').slice(0, 8);
    if (digits.length > 4) return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
    if (digits.length > 2) return digits.slice(0, 2) + '/' + digits.slice(2);
    return digits;
  }

  /* ---------------- field registry ---------------- */
  var fields = [];

  function Field(display) {
    this.display = display;
    this.real = document.getElementById(display.dataset.real);
    this.wrap = display.closest('.date-mask-wrap');
    var lbl = display.dataset.label;
    if (!lbl) {
      var row = display.closest('.form-row');
      var l = row && row.querySelector('label');
      lbl = l ? l.textContent.replace(/\*/g, '').trim() : 'Date';
    }
    this.label = lbl;
    this.min = display.dataset.min || MIN_ISO;
    this.maxAttr = display.dataset.max || 'today';
    this.notAfterId = display.dataset.notAfter || '';
    this.msg = document.createElement('div');
    this.msg.className = 'gg-dp-msg';
    this.msg.setAttribute('role', 'alert');
    this.msg.hidden = true;
    this.wrap.parentNode.insertBefore(this.msg, this.wrap.nextSibling);
  }

  Field.prototype.maxISO = function () {
    var m = this.maxAttr === 'today' ? todayISO() : this.maxAttr;
    if (this.notAfterId) {
      var ref = document.getElementById(this.notAfterId);
      if (ref && parseISO(ref.value) && ref.value < m) m = ref.value;
    }
    return m;
  };

  /** Validate the typed text. Returns {ok, iso, error}. Empty is OK ("" = no date). */
  Field.prototype.check = function (text) {
    var t = (text === undefined ? this.display.value : text).trim();
    if (!t) return { ok: true, iso: '' };
    var p = parseDisplay(t);
    if (!p) {
      if (/^\d{2}\/\d{2}\/\d{4}$/.test(t)) {
        return { ok: false, error: this.label + ': that date does not exist. Check the month and day.' };
      }
      return { ok: false, error: this.label + ': complete the date as mm/dd/yyyy.' };
    }
    var s = iso(p.y, p.m, p.d);
    if (s < this.min) {
      return { ok: false, error: this.label + ' cannot be earlier than ' + toDisplay(parseISO(this.min)) + '.' };
    }
    var max = this.maxISO();
    if (s > max) {
      var isToday = (max === todayISO());
      return {
        ok: false,
        error: isToday ? this.label + ' cannot be a future date.'
                       : this.label + ' cannot be later than ' + toDisplay(parseISO(max)) + '.'
      };
    }
    return { ok: true, iso: s };
  };

  /** Validate + push result into the real input. showError toggles the red state/message. */
  Field.prototype.apply = function (showError) {
    var res = this.check();
    this.real.value = res.ok ? res.iso : '';
    this.setError(res.ok || !showError ? '' : res.error);
    var self = this;
    fields.forEach(function (f) { // keep displays that share the same real input in sync
      if (f !== self && f.real === self.real && res.ok) {
        f.display.value = self.display.value;
        f.setError('');
      }
    });
    return res;
  };

  Field.prototype.setError = function (text) {
    this.display.classList.toggle('gg-invalid', !!text);
    if (text) this.display.setAttribute('aria-invalid', 'true'); else this.display.removeAttribute('aria-invalid');
    this.msg.textContent = text || '';
    this.msg.hidden = !text;
  };

  /* ---------------- placement (pure function, no DOM) ----------------
   * The dialog opens ON the calendar icon of the field that was clicked:
   *   - horizontally CENTRED on the icon (cx)
   *   - vertically CENTRED on the input row (cy)
   * then clamped so the WHOLE dialog is always inside the visible screen.
   * Result: a half-width field (Date Prepared) gets it centred over its icon, and a
   * full-width field (Date of Birth) gets it snapped against the right edge of the screen.
   *   anchor = {cx, cy} in viewport pixels;  w,h = dialog size;  vw,vh = visible viewport.
   */
  function computePlacement(anchor, w, h, vw, vh) {
    var M = 8;
    function clamp(v, lo, hi) { return Math.max(lo, Math.min(v, hi)); }
    var maxLeft = Math.max(M, vw - w - M);
    var maxTop = Math.max(M, vh - h - M);
    return {
      left: clamp(anchor.cx - w / 2, M, maxLeft),
      top: clamp(anchor.cy - h / 2, M, maxTop),
      side: 'icon'
    };
  }

  /* ---------------- popup (one shared element, moved between fields) ---------------- */
  // pop.pending = the date currently highlighted in the dialog; it is only written to the
  // form when the user presses OK (or Enter / double-click on a day). Cancel / Esc discard it.
  var pop = { el: null, field: null, y: 0, m: 0, view: 'days', mode: 'calendar', pending: '', focusISO: '', focusGrid: false };

  function el(tag, cls, attrs) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (attrs) Object.keys(attrs).forEach(function (k) { e.setAttribute(k, attrs[k]); });
    return e;
  }
  function svg(path, size) {
    return '<svg viewBox="0 0 24 24" width="' + (size || 20) + '" height="' + (size || 20) + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>';
  }
  var I_PREV = svg('<polyline points="15 18 9 12 15 6"/>');
  var I_NEXT = svg('<polyline points="9 18 15 12 9 6"/>');
  var I_DOWN = svg('<polyline points="6 9 12 15 18 9"/>', 16);
  var I_UP = svg('<polyline points="6 15 12 9 18 15"/>', 16);
  var I_EDIT = svg('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>');
  var I_CAL = svg('<rect x="3" y="4.5" width="18" height="16.5" rx="3"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>');

  function build() {
    var root = el('div', 'gg-dp', { role: 'dialog', 'aria-label': 'Choose a date', tabindex: '-1' });

    var header = el('div', 'gg-dp-header');
    pop.eyebrow = el('div', 'gg-dp-eyebrow');
    var row = el('div', 'gg-dp-headrow');
    pop.headline = el('div', 'gg-dp-headline', { 'aria-live': 'polite' });
    pop.modeBtn = el('button', 'gg-dp-iconbtn', { type: 'button', 'data-act': 'mode' });
    row.appendChild(pop.headline); row.appendChild(pop.modeBtn);
    header.appendChild(pop.eyebrow); header.appendChild(row);

    pop.body = el('div', 'gg-dp-body');

    var actions = el('div', 'gg-dp-actions');
    var clear = el('button', 'gg-dp-btn', { type: 'button', 'data-act': 'clear' }); clear.textContent = 'Clear';
    var sp = el('span', 'gg-dp-spacer');
    var cancel = el('button', 'gg-dp-btn', { type: 'button', 'data-act': 'cancel' }); cancel.textContent = 'Cancel';
    var ok = el('button', 'gg-dp-btn', { type: 'button', 'data-act': 'ok' }); ok.textContent = 'OK';
    actions.appendChild(clear); actions.appendChild(sp); actions.appendChild(cancel); actions.appendChild(ok);

    root.appendChild(header);
    root.appendChild(el('div', 'gg-dp-divider'));
    root.appendChild(pop.body);
    root.appendChild(actions);

    root.addEventListener('click', onClick);
    root.addEventListener('dblclick', function (e) {
      var d = e.target.closest('.gg-dp-day');
      if (d && !d.disabled) { pop.pending = d.getAttribute('data-iso'); commit(); }
    });
    root.addEventListener('keydown', onKeydown);
    root.addEventListener('mousedown', function (e) {
      // keep the typed form input from blurring (and flashing an error) when clicking inside the dialog
      if (e.target.tagName !== 'INPUT') e.preventDefault();
    });
    pop.el = root;
  }

  function bounds() {
    var f = pop.field;
    var min = f.min, max = f.maxISO();
    if (max < min) max = min;
    return { min: min, max: max, minP: parseISO(min), maxP: parseISO(max) };
  }
  function clampISO(s) {
    var b = bounds();
    return s < b.min ? b.min : (s > b.max ? b.max : s);
  }
  function clampView() {
    var b = bounds();
    var cur = pop.y * 12 + pop.m;
    var lo = b.minP.y * 12 + (b.minP.m - 1), hi = b.maxP.y * 12 + (b.maxP.m - 1);
    if (cur < lo) { pop.y = b.minP.y; pop.m = b.minP.m - 1; }
    if (cur > hi) { pop.y = b.maxP.y; pop.m = b.maxP.m - 1; }
  }
  function keepFocusInView() {
    var p = parseISO(pop.focusISO);
    var day = p ? p.d : 1;
    pop.focusISO = clampISO(iso(pop.y, pop.m + 1, Math.min(day, daysInMonth(pop.y, pop.m + 1))));
  }
  function shiftMonth(n) {
    var t = new Date(pop.y, pop.m + n, 1);
    pop.y = t.getFullYear(); pop.m = t.getMonth();
    clampView();
    keepFocusInView();
    render();
  }
  function longDate(s) {
    var p = parseISO(s);
    return DAYS_SHORT[new Date(p.y, p.m - 1, p.d).getDay()] + ', ' + MONTHS_SHORT[p.m - 1] + ' ' + p.d;
  }

  function onClick(e) {
    var t = e.target.closest('button');
    if (!t || t.disabled) return;
    var act = t.getAttribute('data-act');
    if (t.classList.contains('gg-dp-day')) { pop.pending = t.getAttribute('data-iso'); pop.focusISO = pop.pending; render(); return; }
    if (t.classList.contains('gg-dp-chip')) {
      if (t.hasAttribute('data-year')) { pop.y = +t.getAttribute('data-year'); clampView(); pop.view = 'months'; }
      else { pop.m = +t.getAttribute('data-month'); clampView(); pop.view = 'days'; }
      keepFocusInView(); render(); return;
    }
    if (act === 'prev') shiftMonth(-1);
    else if (act === 'next') shiftMonth(1);
    else if (act === 'ym') { pop.view = (pop.view === 'days') ? 'years' : 'days'; render(); }
    else if (act === 'mode') {
      pop.mode = (pop.mode === 'calendar') ? 'input' : 'calendar';
      pop.view = 'days';
      render();
    }
    else if (act === 'clear') { pop.pending = ''; commit(); }
    else if (act === 'cancel') close(true);
    else if (act === 'ok') commitFromUI();
  }

  /** Write pending into the form field and close. */
  function commit() {
    var f = pop.field;
    if (!f) return;
    f.display.value = pop.pending ? toDisplay(parseISO(pop.pending)) : '';
    f.apply(true);
    close(true);
  }
  /** OK button: in type-in mode, validate the typed text first. */
  function commitFromUI() {
    if (pop.mode === 'input') {
      var inp = pop.body.querySelector('.gg-dp-textinput');
      var txt = inp ? inp.value.trim() : '';
      if (txt) {
        var res = pop.field.check(txt);
        if (!res.ok) { showInputError(res.error); if (inp) inp.focus(); return; }
        pop.pending = res.iso;
      } else {
        pop.pending = '';
      }
    }
    commit();
  }
  function showInputError(msg) {
    var m = pop.body.querySelector('.gg-dp-inputmsg');
    var inp = pop.body.querySelector('.gg-dp-textinput');
    if (m) { m.textContent = msg || 'mm/dd/yyyy'; m.classList.toggle('gg-err', !!msg); }
    if (inp) { inp.classList.toggle('gg-invalid', !!msg); if (msg) inp.setAttribute('aria-invalid', 'true'); else inp.removeAttribute('aria-invalid'); }
  }

  function render() {
    renderInner();
    if (pop.el && pop.el.parentNode) position();
  }

  function renderInner() {
    var f = pop.field, b = bounds(), today = todayISO();
    var typing = pop.mode === 'input';

    // header
    pop.eyebrow.textContent = typing ? 'Enter date' : 'Select date';
    pop.headline.textContent = pop.pending ? longDate(pop.pending) : 'Not set';
    pop.headline.classList.toggle('gg-empty', !pop.pending);
    pop.modeBtn.innerHTML = typing ? I_CAL : I_EDIT;
    pop.modeBtn.setAttribute('aria-label', typing ? 'Switch to calendar' : 'Switch to typing the date');
    pop.modeBtn.title = typing ? 'Switch to calendar' : 'Type the date instead';

    pop.body.innerHTML = '';
    pop.body.classList.toggle('gg-typing', typing);

    if (typing) {
      var wrap = el('div', 'gg-dp-inputwrap');
      var lab = el('label', 'gg-dp-field');
      var cap = el('span'); cap.textContent = 'Date';
      var inp = el('input', 'gg-dp-textinput', { type: 'text', inputmode: 'numeric', maxlength: '10', placeholder: 'mm/dd/yyyy', autocomplete: 'off' });
      inp.value = pop.pending ? toDisplay(parseISO(pop.pending)) : '';
      lab.appendChild(cap); lab.appendChild(inp);
      var msg = el('div', 'gg-dp-inputmsg'); msg.textContent = 'mm/dd/yyyy';
      wrap.appendChild(lab); wrap.appendChild(msg);
      pop.body.appendChild(wrap);
      inp.addEventListener('input', function () {
        var masked = digitsToDisplay(inp.value);
        if (masked !== inp.value) inp.value = masked;
        if (masked.length === 10) {
          var res = pop.field.check(masked);
          if (res.ok) {
            showInputError('');
            pop.pending = res.iso;
            pop.headline.textContent = longDate(pop.pending); pop.headline.classList.remove('gg-empty');
          } else showInputError(res.error);
        } else {
          showInputError('');
          if (!masked) { pop.pending = ''; pop.headline.textContent = 'Not set'; pop.headline.classList.add('gg-empty'); }
        }
      });
      if (pop.focusGrid) { inp.focus(); }
      return;
    }

    // month/year row
    var nav = el('div', 'gg-dp-nav');
    var ym = el('button', 'gg-dp-ym', { type: 'button', 'data-act': 'ym', 'aria-expanded': pop.view === 'days' ? 'false' : 'true' });
    var ymLabel = pop.view === 'days' ? MONTHS[pop.m] + ' ' + pop.y : (pop.view === 'months' ? String(pop.y) : 'Select year');
    ym.innerHTML = '<span></span>' + (pop.view === 'days' ? I_DOWN : I_UP);
    ym.firstChild.textContent = ymLabel;
    nav.appendChild(ym);
    if (pop.view === 'days') {
      var arrows = el('span', 'gg-dp-arrows');
      var prev = el('button', 'gg-dp-iconbtn', { type: 'button', 'data-act': 'prev', 'aria-label': 'Previous month' });
      var next = el('button', 'gg-dp-iconbtn', { type: 'button', 'data-act': 'next', 'aria-label': 'Next month' });
      prev.innerHTML = I_PREV; next.innerHTML = I_NEXT;
      var cur = pop.y * 12 + pop.m;
      prev.disabled = cur <= b.minP.y * 12 + (b.minP.m - 1);
      next.disabled = cur >= b.maxP.y * 12 + (b.maxP.m - 1);
      arrows.appendChild(prev); arrows.appendChild(next);
      nav.appendChild(arrows);
    }
    pop.body.appendChild(nav);

    if (pop.view === 'years') {
      var yrs = el('div', 'gg-dp-years');
      for (var y = b.minP.y; y <= b.maxP.y; y++) {
        var yb = el('button', 'gg-dp-chip', { type: 'button', 'data-year': y });
        yb.textContent = y;
        if (y === pop.y) { yb.classList.add('gg-sel'); yb.setAttribute('aria-pressed', 'true'); }
        if (y === +today.slice(0, 4)) yb.classList.add('gg-today');
        yrs.appendChild(yb);
      }
      pop.body.appendChild(yrs);
      var selBtn = yrs.querySelector('.gg-sel');
      if (selBtn) yrs.scrollTop = selBtn.offsetTop - yrs.clientHeight / 2 + selBtn.offsetHeight / 2;
      return;
    }

    if (pop.view === 'months') {
      var mg = el('div', 'gg-dp-months');
      MONTHS.forEach(function (name, i) {
        var mb = el('button', 'gg-dp-chip', { type: 'button', 'data-month': i });
        mb.textContent = name.slice(0, 3);
        var first = iso(pop.y, i + 1, 1), last = iso(pop.y, i + 1, daysInMonth(pop.y, i + 1));
        if (last < b.min || first > b.max) mb.disabled = true;
        if (i === pop.m) { mb.classList.add('gg-sel'); mb.setAttribute('aria-pressed', 'true'); }
        mg.appendChild(mb);
      });
      pop.body.appendChild(mg);
      return;
    }

    // days view
    var week = el('div', 'gg-dp-week', { 'aria-hidden': 'true' });
    WEEKDAYS.forEach(function (w) { var sp = el('span'); sp.textContent = w; week.appendChild(sp); });
    pop.body.appendChild(week);

    var grid = el('div', 'gg-dp-grid', { role: 'grid' });
    var firstOfMonth = iso(pop.y, pop.m + 1, 1);
    var lead = new Date(pop.y, pop.m, 1).getDay();
    var start = addDays(firstOfMonth, -lead);
    var cells = (lead + daysInMonth(pop.y, pop.m + 1) > 35) ? 42 : 35;   // 5 rows when enough, 6 only if needed
    for (var i = 0; i < cells; i++) {
      var s2 = addDays(start, i), p = parseISO(s2);
      var out = (p.m - 1 !== pop.m);
      var btn = el('button', 'gg-dp-day', { type: 'button', 'data-iso': s2, tabindex: s2 === pop.focusISO ? '0' : '-1' });
      btn.textContent = out ? '' : p.d;   // like the native picker: leading/trailing days are blank
      var dow = new Date(p.y, p.m - 1, p.d).getDay();
      btn.setAttribute('aria-label', DAYS_LONG[dow] + ', ' + MONTHS[p.m - 1] + ' ' + p.d + ', ' + p.y);
      if (out) { btn.classList.add('gg-out'); btn.disabled = true; btn.tabIndex = -1; btn.setAttribute('aria-hidden', 'true'); }
      if (s2 === today) { btn.classList.add('gg-today'); btn.setAttribute('aria-current', 'date'); }
      if (s2 === pop.pending) { btn.classList.add('gg-sel'); btn.setAttribute('aria-pressed', 'true'); }
      if (!out && (s2 < b.min || s2 > b.max)) btn.disabled = true;
      grid.appendChild(btn);
    }
    pop.body.appendChild(grid);

    if (pop.focusGrid) {
      var fb = grid.querySelector('.gg-dp-day[tabindex="0"]');
      if (fb && !fb.disabled) fb.focus();
    }
  }

  function position() {
    var f = pop.field;
    if (!f || !pop.el || !pop.el.parentNode) return;
    var r = f.display.getBoundingClientRect();
    var icon = f.wrap.querySelector('.gg-dp-icon');
    var ir = icon ? icon.getBoundingClientRect() : null;
    var anchor = {
      cx: (ir && ir.width) ? (ir.left + ir.right) / 2 : r.right - 22,
      cy: (r.top + r.bottom) / 2
    };
    var vw = document.documentElement.clientWidth || window.innerWidth;
    var vh = window.innerHeight;
    var p = computePlacement(anchor, pop.el.offsetWidth, pop.el.offsetHeight, vw, vh);
    pop.el.style.left = Math.round(p.left) + 'px';
    pop.el.style.top = Math.round(p.top) + 'px';
  }

  function open(field, focusGrid) {
    if (!pop.el) build();
    if (pop.field && pop.field !== field) pop.field.display.removeAttribute('aria-expanded');
    pop.field = field;
    pop.mode = 'calendar';
    pop.view = 'days';
    pop.focusGrid = !!focusGrid;
    document.body.appendChild(pop.el);   // fixed + in <body>: never clipped / pushed around by the form layout

    var cur = parseISO(field.real.value) ? field.real.value : '';
    pop.pending = cur;
    pop.focusISO = clampISO(cur || todayISO());
    var p = parseISO(pop.focusISO);
    pop.y = p.y; pop.m = p.m - 1;
    field.display.setAttribute('aria-expanded', 'true');
    pop.el.classList.add('gg-open');
    render();
    position();
    pop.focusGrid = false; // only the very first render steals focus
  }

  function close(returnFocus) {
    if (!pop.el || !pop.field) return;
    var f = pop.field;
    pop.el.classList.remove('gg-open');
    if (pop.el.parentNode) pop.el.parentNode.removeChild(pop.el);
    f.display.removeAttribute('aria-expanded');
    pop.field = null;
    if (returnFocus) f.display.focus();
  }

  function isOpenFor(f) { return pop.field === f && pop.el && pop.el.parentNode; }

  function onKeydown(e) {
    var k = e.key;
    if (k === 'Escape') { e.preventDefault(); e.stopPropagation(); close(true); return; }

    if (k === 'Enter' && e.target.classList && e.target.classList.contains('gg-dp-textinput')) {
      e.preventDefault(); commitFromUI(); return;
    }

    if (k === 'Tab') { // keep Tab inside the dialog while it is open
      var items = Array.prototype.filter.call(
        pop.el.querySelectorAll('button, input'),
        function (n) { return !n.disabled && n.tabIndex >= 0; });
      if (!items.length) return;
      var i = items.indexOf(document.activeElement);
      var nextIdx = e.shiftKey ? (i <= 0 ? items.length - 1 : i - 1) : (i === items.length - 1 ? 0 : i + 1);
      e.preventDefault();
      items[nextIdx].focus();
      return;
    }

    var target = e.target;
    if (!target.classList || !target.classList.contains('gg-dp-day')) return;
    var s = target.getAttribute('data-iso');
    if (k === 'Enter') { e.preventDefault(); pop.pending = s; commit(); return; } // Enter on a day = pick + OK
    if (k === ' ') { e.preventDefault(); pop.pending = s; pop.focusISO = s; pop.focusGrid = true; render(); pop.focusGrid = false; return; }
    var pp = parseISO(s), dow = new Date(pp.y, pp.m - 1, pp.d).getDay();
    var n = null;
    if (k === 'ArrowLeft') n = addDays(s, -1);
    else if (k === 'ArrowRight') n = addDays(s, 1);
    else if (k === 'ArrowUp') n = addDays(s, -7);
    else if (k === 'ArrowDown') n = addDays(s, 7);
    else if (k === 'PageUp') n = addMonths(s, e.shiftKey ? -12 : -1);
    else if (k === 'PageDown') n = addMonths(s, e.shiftKey ? 12 : 1);
    else if (k === 'Home') n = addDays(s, -dow);
    else if (k === 'End') n = addDays(s, 6 - dow);
    if (n === null) return;
    e.preventDefault();
    n = clampISO(n);
    pop.focusISO = n;
    var np = parseISO(n);
    pop.y = np.y; pop.m = np.m - 1;
    pop.focusGrid = true;
    render();
    pop.focusGrid = false;
  }

  /* ---------------- wiring one display input ---------------- */
  var ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="3"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/><circle cx="8.5" cy="14" r=".9" fill="currentColor" stroke="none"/><circle cx="12" cy="14" r=".9" fill="currentColor" stroke="none"/><circle cx="15.5" cy="14" r=".9" fill="currentColor" stroke="none"/><circle cx="8.5" cy="17.5" r=".9" fill="currentColor" stroke="none"/><circle cx="12" cy="17.5" r=".9" fill="currentColor" stroke="none"/></svg>';

  function attach(display) {
    if (display.__gg) return;
    var real = document.getElementById(display.dataset.real);
    var wrap = display.closest('.date-mask-wrap');
    if (!real || !wrap) return;

    // replace the old decorative icon with a real, clickable button
    var oldIcon = wrap.querySelector('.date-mask-icon');
    if (oldIcon) oldIcon.parentNode.removeChild(oldIcon);
    var iconBtn = el('button', 'gg-dp-icon', { type: 'button', 'aria-label': 'Open calendar', title: 'Open calendar' });
    iconBtn.innerHTML = ICON_SVG;
    wrap.appendChild(iconBtn);

    var field = new Field(display);
    display.__gg = field;
    fields.push(field);
    display.setAttribute('aria-haspopup', 'dialog');

    display.addEventListener('input', function () {
      var masked = digitsToDisplay(display.value);
      if (masked !== display.value) display.value = masked;
      var complete = masked.length === 10;
      var res = field.apply(complete);       // show errors only once all 8 digits are typed
      if (!complete) field.setError('');
      if (isOpenFor(field) && pop.mode === 'calendar' && res.ok && (res.iso || !masked)) {   // dialog follows what is typed
        pop.pending = res.iso;
        if (res.iso) {
          pop.focusISO = res.iso;
          var p = parseISO(res.iso);
          pop.y = p.y; pop.m = p.m - 1;
          pop.view = 'days';
        }
        render();
      }
    });

    display.addEventListener('paste', function (e) {
      var txt = (e.clipboardData || global.clipboardData);
      txt = txt ? String(txt.getData('text')).trim() : '';
      var m;
      if ((m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(txt))) txt = pad(+m[2]) + pad(+m[3]) + m[1];
      else if ((m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(txt))) txt = pad(+m[1]) + pad(+m[2]) + m[3];
      else return; // anything else: let the normal paste + mask handle it
      e.preventDefault();
      display.value = txt;
      display.dispatchEvent(new Event('input', { bubbles: true }));
    });

    display.addEventListener('blur', function () {
      setTimeout(function () {
        if (isOpenFor(field)) return;           // user is picking from the calendar
        field.apply(true);
      }, 0);
    });

    display.addEventListener('click', function () {
      if (!isOpenFor(field)) open(field, false);
    });

    display.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (isOpenFor(field)) { var b = pop.body.querySelector('.gg-dp-day[tabindex="0"]'); if (b) b.focus(); }
        else open(field, true);
      } else if (e.key === 'Escape' && isOpenFor(field)) {
        e.preventDefault(); close(false);
      }
    });

    iconBtn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // don't blur the typed input (would flash an error)
    iconBtn.addEventListener('click', function () {
      if (isOpenFor(field)) close(true); else { open(field, true); }
    });
    iconBtn.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpenFor(field)) { e.preventDefault(); close(true); }
    });

    // block the form from submitting with a bad / incomplete visible date
    var form = display.closest('form');
    if (form && !form.__ggSubmit) {
      form.__ggSubmit = true;
      form.addEventListener('submit', function (e) {
        var firstBad = null;
        fields.forEach(function (f) {
          if (!form.contains(f.display) || f.display.offsetParent === null) return; // skip fields hidden by the Registry Type switch
          var res = f.apply(true);
          if (!res.ok && !firstBad) firstBad = f;
        });
        if (firstBad) {
          e.preventDefault();
          close(false);
          firstBad.display.focus();
          if (firstBad.display.scrollIntoView) firstBad.display.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
      });
    }
  }

  document.addEventListener('mousedown', function (e) {
    if (!pop.field) return;
    if (pop.field.wrap.contains(e.target) || (pop.el && pop.el.contains(e.target))) return;
    close(false);
  });
  window.addEventListener('resize', function () { if (pop.field) position(); });
  // Keep the dialog glued to its input when the page / form panel scrolls.
  var scrollTick = false;
  window.addEventListener('scroll', function (e) {
    if (!pop.field || scrollTick) return;
    if (pop.el && e.target && e.target.nodeType && pop.el.contains(e.target)) return;   // scrolling the year list
    scrollTick = true;
    (window.requestAnimationFrame || setTimeout)(function () {
      scrollTick = false;
      if (!pop.field) return;
      var a = pop.field.display.getBoundingClientRect();
      if (a.bottom < 0 || a.top > window.innerHeight) close(false);   // input scrolled out of view
      else position();
    });
  }, true);

  global.GGDatePicker = {
    _place: computePlacement,   // exposed for tests
    autoInit: function (root) {
      (root || document).querySelectorAll('.dateMaskDisplay').forEach(attach);
    },
    /** Re-read the typed text for every display bound to this real input (used after OCR autofill). */
    refresh: function (realId) {
      // All displays bound to this real input already hold the same text (the caller
      // updates them all), so validating ONE is enough - prefer the visible one.
      // (Validating every one would let a blank hidden display overwrite the visible one.)
      var bound = fields.filter(function (f) { return f.display.dataset.real === realId; });
      if (!bound.length) return;
      var visible = bound.filter(function (f) { return f.display.offsetParent !== null; });
      (visible[0] || bound[0]).apply(true);
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { global.GGDatePicker.autoInit(); });
  } else {
    global.GGDatePicker.autoInit();
  }
})(window);
