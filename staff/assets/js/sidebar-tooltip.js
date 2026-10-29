/*
 * GeoGene - icon-only (collapsed) sidebar: show the item's name when the mouse
 * (or keyboard focus) is on its icon. Used by BOTH the Staff and Admin portals.
 *
 * The tooltip is ONE position:fixed element attached to <body>, so the sidebar's
 * overflow can never clip it (the old CSS-only ::after tooltips were clipped).
 * It only shows while the sidebar has the "collapsed" class on desktop widths.
 */
(function () {
  'use strict';
  var sidebar = document.getElementById('staffSidebar');
  if (!sidebar) return;

  var collapseBtn = document.getElementById('sidebarCollapseBtn');
  var themeIcon   = document.getElementById('themeToggleIcon');
  var mobile      = window.matchMedia('(max-width: 900px)');
  var SEL         = '.sidebar-item, #themeToggleIcon, #sidebarCollapseBtn';

  var tip = document.createElement('div');
  tip.className = 'sb-tip';
  tip.setAttribute('role', 'tooltip');
  document.body.appendChild(tip);

  var current = null;

  // Native browser title bubbles on the links would show a 2nd (white) tooltip - remove them.
  var titled = sidebar.querySelectorAll('.sidebar-item[title]');
  for (var k = 0; k < titled.length; k++) {
    if (!titled[k].getAttribute('data-label')) titled[k].setAttribute('data-label', titled[k].getAttribute('title'));
    titled[k].removeAttribute('title');
  }

  function isCollapsed() {
    return sidebar.classList.contains('collapsed') && !mobile.matches;
  }

  // The name people see when the sidebar is expanded (the link's text, without its icon).
  function labelFor(el) {
    if (el.id === 'sidebarCollapseBtn') return 'Expand sidebar';
    if (el.id === 'themeToggleIcon') {
      var t = document.getElementById('themeToggleText');
      return (t && t.textContent.trim()) || 'Toggle theme';
    }
    var clone = el.cloneNode(true);
    var icons = clone.querySelectorAll('.si-icon');
    for (var i = 0; i < icons.length; i++) icons[i].parentNode.removeChild(icons[i]);
    var text = clone.textContent.replace(/\s+/g, ' ').trim();
    return text || el.getAttribute('data-label') || '';
  }

  function show(el) {
    if (!isCollapsed()) return;
    var text = labelFor(el);
    if (!text) return;
    current = el;
    tip.textContent = text;
    var r = el.getBoundingClientRect();
    tip.style.left = (r.right + 12) + 'px';
    tip.style.top  = (r.top + r.height / 2) + 'px';
    tip.classList.add('show');
  }

  function hide() {
    current = null;
    tip.classList.remove('show');
  }

  sidebar.addEventListener('mouseover', function (e) {
    var el = e.target.closest ? e.target.closest(SEL) : null;
    if (el && sidebar.contains(el)) { if (el !== current) show(el); }
    else hide();
  });
  sidebar.addEventListener('mouseout', function (e) {
    var el = e.target.closest ? e.target.closest(SEL) : null;
    if (el && !el.contains(e.relatedTarget)) hide();
  });
  sidebar.addEventListener('mouseleave', hide);
  sidebar.addEventListener('focusin', function (e) {
    var el = e.target.closest ? e.target.closest(SEL) : null;
    if (el) show(el);
  });
  sidebar.addEventListener('focusout', hide);
  sidebar.addEventListener('scroll', hide);
  window.addEventListener('resize', hide);
  // e.g. clicking the sun/moon changes its name (Light Mode <-> Dark Mode): refresh the open tooltip
  sidebar.addEventListener('click', function () {
    if (current) { var el = current; setTimeout(function () { if (current === el) show(el); }, 0); }
  });

  // Keep the native browser titles from doubling up with our tooltip while collapsed.
  var themeTitle = themeIcon ? themeIcon.getAttribute('title') : null;
  function syncState() {
    var collapsed = sidebar.classList.contains('collapsed');
    if (collapseBtn) {
      collapseBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
      if (collapsed) collapseBtn.removeAttribute('title');
      else collapseBtn.setAttribute('title', 'Collapse sidebar');
    }
    if (themeIcon && themeTitle) {
      if (collapsed) themeIcon.removeAttribute('title');
      else themeIcon.setAttribute('title', themeTitle);
    }
    hide();
  }
  new MutationObserver(syncState).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
  syncState();
})();
