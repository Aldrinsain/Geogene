/*
 * GeoGene - top search bar.
 * Type what you are looking for ("map", "birth", "disabled users", a person's name ...) and
 * jump straight to that part of the system. Works the same for Staff and Admin: the list of places
 * comes from window.GG_SEARCH (see staff/includes/global-search-config.php).
 *   Up / Down = move   Enter = open   Ctrl+Enter = open in a new tab   Esc = close
 *   "/" or Ctrl+K anywhere = jump into the search bar
 */
(function () {
  var cfg = window.GG_SEARCH;
  var input = document.getElementById('ggSearchInput');
  if (!cfg || !input) return;

  var host = input.closest('.topbar-search');
  host.classList.add('gg-sr-host');
  var panel = document.createElement('div');
  panel.className = 'gg-sr';
  panel.id = 'ggSearchResults';
  panel.setAttribute('role', 'listbox');
  host.appendChild(panel);

  input.placeholder = cfg.placeholder || 'Search pages or records...';
  input.setAttribute('autocomplete', 'off');
  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-expanded', 'false');
  input.setAttribute('aria-controls', 'ggSearchResults');
  // (the little "/" key badge was removed - the "/" and Ctrl+K shortcuts still work, see below)

  var rows = [];      // the selectable results currently shown
  var active = -1;

  function fold(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function highlight(text, toks) {
    var out = esc(text);
    toks.forEach(function (t) {
      if (t.length < 2) return;
      out = out.replace(new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig'), '<mark>$1</mark>');
    });
    return out;
  }

  // how well does one place match every word typed?  (0 = not a match)
  function score(item, toks) {
    var t = fold(item.t), k = fold(item.k), d = fold(item.d), words = t.split(/[^a-z0-9]+/), kw = k.split(/[^a-z0-9]+/), s = 0;
    for (var i = 0; i < toks.length; i++) {
      var tok = toks[i], hit = 0;
      if (t.indexOf(tok) === 0) hit = 8;
      else if (words.some(function (w) { return w.indexOf(tok) === 0; })) hit = 6;
      else if (t.indexOf(tok) !== -1) hit = 4;
      else if (kw.some(function (w) { return w.indexOf(tok) === 0; })) hit = 4;
      else if (k.indexOf(tok) !== -1) hit = 3;
      else if (d.indexOf(tok) !== -1) hit = 1;
      if (!hit) return 0;
      s += hit;
    }
    return s + (item.top ? 0.5 : 0);
  }

  function build(query) {
    var q = query.trim(), toks = fold(q).split(/\s+/).filter(Boolean);
    var html = '', i = 0;
    rows = [];

    function addRow(r, hlToks) {
      var idx = rows.length; rows.push(r);
      html += '<a class="gg-sr-item" role="option" data-i="' + idx + '" href="' + esc(r.url) + '">' +
        '<span class="gg-sr-ico">' + r.ico + '</span>' +
        '<span class="gg-sr-main"><div class="gg-sr-title">' + highlight(r.title, hlToks || []) + '</div>' +
        (r.desc ? '<div class="gg-sr-desc">' + esc(r.desc) + '</div>' : '') + '</span>' +
        '<span class="gg-sr-tag">' + esc(r.tag) + '</span></a>';
    }

    if (!toks.length) {
      html += '<div class="gg-sr-head">Quick links</div>';
      cfg.items.filter(function (x) { return x.top; }).forEach(function (x) {
        addRow({ title: x.t, desc: x.d, tag: x.g || 'Page', url: x.u, ico: x.i || '📄' });
      });
    } else {
      var matched = cfg.items.map(function (x) { return { x: x, s: score(x, toks) }; })
        .filter(function (m) { return m.s > 0; })
        .sort(function (a, b) { return b.s - a.s; }).slice(0, 7);
      if (matched.length) {
        html += '<div class="gg-sr-head">Go to</div>';
        matched.forEach(function (m) { addRow({ title: m.x.t, desc: m.x.d, tag: m.x.g || 'Page', url: m.x.u, ico: m.x.i || '📄' }, toks); });
      }
      if (q.length >= 2 && cfg.actions && cfg.actions.length) {
        html += '<div class="gg-sr-head">Search for \u201c' + esc(q) + '\u201d</div>';
        cfg.actions.forEach(function (a) {
          addRow({ title: a.t.replace('{q}', q), desc: a.d, tag: 'Search', url: a.u + encodeURIComponent(q), ico: a.i || '🔎' });
        });
      }
      if (!rows.length) html += '<div class="gg-sr-empty">Nothing found for \u201c' + esc(q) + '\u201d.</div>';
    }
    html += '<div class="gg-sr-foot"><span>\u2191\u2193 move</span><span>Enter open</span><span>Esc close</span></div>';
    panel.innerHTML = html;
    active = rows.length ? 0 : -1;
    paint();
  }

  function paint() {
    var els = panel.querySelectorAll('.gg-sr-item');
    for (var i = 0; i < els.length; i++) els[i].classList.toggle('active', i === active);
    if (els[active] && els[active].scrollIntoView) els[active].scrollIntoView({ block: 'nearest' });
  }
  function open() { panel.classList.add('open'); input.setAttribute('aria-expanded', 'true'); }
  function close() { panel.classList.remove('open'); input.setAttribute('aria-expanded', 'false'); }
  function go(r, newTab) {
    if (!r) return;
    if (newTab) window.open(r.url, '_blank'); else window.location.href = r.url;
  }

  input.addEventListener('focus', function () { build(input.value); open(); });
  input.addEventListener('input', function () { build(input.value); open(); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); open(); if (rows.length) { active = (active + 1) % rows.length; paint(); } }
    else if (e.key === 'ArrowUp') { e.preventDefault(); if (rows.length) { active = (active - 1 + rows.length) % rows.length; paint(); } }
    else if (e.key === 'Enter') { e.preventDefault(); go(rows[active], e.ctrlKey || e.metaKey); }
    else if (e.key === 'Escape') { close(); input.blur(); }
  });
  panel.addEventListener('mousemove', function (e) {
    var el = e.target.closest('.gg-sr-item');
    if (el) { var n = Number(el.getAttribute('data-i')); if (n !== active) { active = n; paint(); } }
  });
  panel.addEventListener('mousedown', function (e) { e.preventDefault(); });   // keep focus in the box while clicking
  document.addEventListener('click', function (e) { if (!host.contains(e.target)) close(); });

  document.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
    if ((e.key === '/' && !typing && !e.ctrlKey && !e.metaKey && !e.altKey) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
      e.preventDefault(); input.focus(); input.select();
    }
  });
})();
