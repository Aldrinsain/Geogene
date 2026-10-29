  </div>
  </div>
</div>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('staffSidebar').classList.toggle('open');
});

/* ---------- Sidebar collapse (icon-only) toggle, desktop ---------- */
(function () {
  const sidebar = document.getElementById('staffSidebar');
  const btn = document.getElementById('sidebarCollapseBtn');
  if (!sidebar || !btn) return;

  btn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('adminSidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
  });
})();

/* ---------- Notification bell: dropdown toggle, tabs, mark-all-read ---------- */
(function () {
  const notifBtn       = document.getElementById('notifBtn');
  const notifDropdown  = document.getElementById('notifDropdown');
  const notifWrap      = document.getElementById('notifWrap');
  if (!notifBtn) return;

  const bellDot     = document.getElementById('bellDot');
  const menuBtn      = document.getElementById('notifMenuBtn');
  const menuDropdown = document.getElementById('notifMenuDropdown');
  const markAllBtn   = document.getElementById('markAllReadBtn');
  const tabs         = Array.from(notifDropdown.querySelectorAll('.notif-tab'));
  const items        = Array.from(notifDropdown.querySelectorAll('.notif-item'));
  const STORAGE_KEY   = 'geogene-admin-notif-read';

  function getReadIds() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
    catch (e) { return []; }
  }
  function setReadIds(ids) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
  }

  function activeTabMode() {
    const active = tabs.find(t => t.classList.contains('active'));
    return active ? active.dataset.tab : 'all';
  }

  function render() {
    const readIds = getReadIds();
    let unread = 0;
    const mode = activeTabMode();

    items.forEach(item => {
      const isRead = readIds.includes(item.dataset.id);
      const dot = item.querySelector('.notif-unread-dot');
      if (dot) dot.style.display = isRead ? 'none' : 'block';
      if (!isRead) unread++;
      item.style.display = (mode === 'unread' && isRead) ? 'none' : 'flex';
    });

    if (bellDot) {
      bellDot.textContent = unread > 9 ? '9+' : unread;
      bellDot.style.display = unread > 0 ? 'flex' : 'none';
    }
  }

  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = notifDropdown.style.display === 'block';
    notifDropdown.style.display = isOpen ? 'none' : 'block';
    if (menuDropdown) menuDropdown.style.display = 'none';
    const avatarDropdown = document.getElementById('avatarDropdown');
    if (avatarDropdown) avatarDropdown.style.display = 'none';
    const avatarChevron = document.getElementById('avatarChevron');
    if (avatarChevron) avatarChevron.style.transform = 'rotate(0deg)';
  });
  document.addEventListener('click', (e) => {
    if (!notifWrap.contains(e.target)) {
      notifDropdown.style.display = 'none';
      if (menuDropdown) menuDropdown.style.display = 'none';
    }
  });

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => {
        t.classList.remove('active');
        t.style.color = '#8a8f8a';
        t.style.borderBottomColor = 'transparent';
      });
      tab.classList.add('active');
      tab.style.color = '#1a1a1a';
      tab.style.borderBottomColor = '#1b6e3c';
      render();
    });
  });

  if (menuBtn && menuDropdown) {
    menuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      menuDropdown.style.display = menuDropdown.style.display === 'block' ? 'none' : 'block';
    });
  }

  if (markAllBtn) {
    markAllBtn.addEventListener('click', () => {
      setReadIds(items.map(i => i.dataset.id));
      if (menuDropdown) menuDropdown.style.display = 'none';
      render();
    });
  }

  items.forEach(item => {
    item.addEventListener('click', () => {
      const ids = getReadIds();
      if (!ids.includes(item.dataset.id)) {
        ids.push(item.dataset.id);
        setReadIds(ids);
      }
    });
  });

  render();
})();

/* ---------- Profile avatar: dropdown, view photo, change photo ---------- */
(function () {
  const avatarBtn      = document.getElementById('avatarBtn');
  const avatarDropdown = document.getElementById('avatarDropdown');
  const avatarWrap      = document.getElementById('avatarWrap');
  const viewPhotoBtn    = document.getElementById('viewPhotoBtn');
  const changePhotoBtn  = document.getElementById('changePhotoBtn');
  const fileInput       = document.getElementById('avatarFileInput');

  const modalOverlay    = document.getElementById('photoModalOverlay');
  const modalImg        = document.getElementById('photoModalImg');
  const modalClose      = document.getElementById('photoModalClose');
  const modalChangeBtn  = document.getElementById('photoModalChangeBtn');

  if (!avatarBtn) return;

  function currentAvatarSrc() {
    const img = document.getElementById('avatarImg');
    return img ? img.getAttribute('src') : null;
  }

  const avatarChevron = document.getElementById('avatarChevron');
  function setAvatarDropdown(open) {
    avatarDropdown.style.display = open ? 'block' : 'none';
    if (avatarChevron) avatarChevron.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
  }

  avatarBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = avatarDropdown.style.display === 'block';
    setAvatarDropdown(!isOpen);
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown) notifDropdown.style.display = 'none';
  });
  document.addEventListener('click', (e) => {
    if (!avatarWrap.contains(e.target)) setAvatarDropdown(false);
  });

  viewPhotoBtn.addEventListener('click', () => {
    setAvatarDropdown(false);
    const src = currentAvatarSrc();
    if (!src) {
      fileInput.click();
      return;
    }
    modalImg.src = src;
    modalOverlay.style.display = 'flex';
  });

  modalClose.addEventListener('click', () => modalOverlay.style.display = 'none');
  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) modalOverlay.style.display = 'none';
  });

  changePhotoBtn.addEventListener('click', () => {
    setAvatarDropdown(false);
    fileInput.click();
  });
  modalChangeBtn.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;

    if (file.size > 3 * 1024 * 1024) {
      alert('File size exceeds the 3MB limit. Please select a smaller image.');
      fileInput.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('photo', file);

    changePhotoBtn.textContent = '⏳ Uploading...';

    fetch('../staff/upload-profile-photo.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        changePhotoBtn.textContent = '📷 Change Profile Photo';
        if (!data.success) {
          alert(data.message || 'An error occurred while uploading your photo.');
          return;
        }
        const bust = '../' + data.url.replace(/^\.\.\//, '') + '?t=' + Date.now();
        let img = document.getElementById('avatarImg');
        if (!img) {
          avatarBtn.innerHTML = '<img src="" alt="Profile" id="avatarImg" width="34" height="34" style="width:100%;height:100%;object-fit:cover;display:block;">';
          img = document.getElementById('avatarImg');
        }
        img.src = bust;
        modalImg.src = bust;
      })
      .catch(() => {
        changePhotoBtn.textContent = '📷 Change Profile Photo';
        alert('Unable to connect to the server. Please try again.');
      })
      .finally(() => { fileInput.value = ''; });
  });
})();

/* ---------- Light / Dark mode toggle ---------- */
(function () {
  const toggleInput = document.getElementById('themeToggleInput');
  const toggleIcon  = document.getElementById('themeToggleIcon');
  const toggleText  = document.getElementById('themeToggleText');
  if (!toggleInput) return;

  const sunSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#e4990e" viewBox="2 2 20 20"><path d="M12 17.01c2.76 0 5.01-2.25 5.01-5.01S14.76 6.99 12 6.99 6.99 9.24 6.99 12s2.25 5.01 5.01 5.01M12 9c1.66 0 3.01 1.35 3.01 3.01s-1.35 3.01-3.01 3.01-3.01-1.35-3.01-3.01S10.34 9 12 9m1 10h-2v3h2zm0-17h-2v3h2zM2 11h3v2H2zm17 0h3v2h-3zM4.22 18.36l.71.71.71.71 1.06-1.06 1.06-1.06-.71-.71-.71-.71-1.06 1.06zM19.78 5.64l-.71-.71-.71-.71-1.06 1.06-1.06 1.06.71.71.71.71 1.06-1.06zm-12.02.7L6.7 5.28 5.64 4.22l-.71.71-.71.71L5.28 6.7l1.06 1.06.71-.71zm8.48 11.32 1.06 1.06 1.06 1.06.71-.71.71-.71-1.06-1.06-1.06-1.06-.71.71z"></path></svg>';
  const moonSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#bfbcbc" viewBox="2 2 20 20"><path d="M20.71 13.51c-.78.23-1.58.35-2.38.35-4.52 0-8.2-3.68-8.2-8.2 0-.8.12-1.6.35-2.38.11-.35.01-.74-.25-1s-.64-.36-1-.25A10.17 10.17 0 0 0 2 11.8C2 17.42 6.57 22 12.2 22c4.53 0 8.45-2.91 9.76-7.24.11-.35.01-.74-.25-1s-.64-.36-1-.25M12.2 20C7.68 20 4 16.32 4 11.8a8.15 8.15 0 0 1 4.18-7.15c-.03.34-.05.68-.05 1.02 0 5.62 4.57 10.2 10.2 10.2.34 0 .68-.02 1.02-.05C17.93 18.38 15.23 20 12.2 20M16 8l.94-2.06L19 5l-2.06-.94L16 2l-.94 2.06L13 5l2.06.94zm4.25-.5-.55 1.2-1.2.55 1.2.55.55 1.2.55-1.2 1.2-.55-1.2-.55z"></path></svg>';

  function applyThemeUI(isDark) {
    toggleInput.checked = isDark;
    toggleIcon.innerHTML = isDark ? moonSVG : sunSVG;
    toggleText.textContent = isDark ? 'Dark Mode' : 'Light Mode';
  }

  applyThemeUI(document.body.classList.contains('dark-mode'));

  // If an embedded Staff page (Civil Registry / Family Tree / Map / Profile)
  // is currently open in the iframe, keep its theme in sync with Admin's -
  // it has its OWN separate <body> and localStorage key
  // ('geogene-staff-theme' vs this page's 'geogene-admin-theme'), so
  // toggling here does nothing to it unless we explicitly push the change.
  function syncEmbeddedFrameTheme(isDark) {
    const frame = document.getElementById('embedFrame');
    if (!frame) return;
    try {
      const doc = frame.contentDocument || frame.contentWindow.document;
      if (!doc || !doc.body) return;
      doc.body.classList.toggle('dark-mode', isDark);
      if (frame.contentWindow && frame.contentWindow.localStorage) {
        frame.contentWindow.localStorage.setItem('geogene-staff-theme', isDark ? 'dark' : 'light');
      }
    } catch (e) {
      // Cross-origin or not loaded yet - safe to ignore.
    }
  }

  toggleInput.addEventListener('change', () => {
    const isDark = toggleInput.checked;
    document.body.classList.toggle('dark-mode', isDark);
    localStorage.setItem('geogene-admin-theme', isDark ? 'dark' : 'light');
    applyThemeUI(isDark);
    syncEmbeddedFrameTheme(isDark);

    fetch('../staff/save-theme-preference.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'dark_mode=' + (isDark ? '1' : '0')
    }).catch(() => {});
  });

  // Also re-apply whenever the embedded page itself (re)loads - e.g. the
  // user clicks a link inside it (view a record, search a name, etc) and
  // the iframe navigates to a fresh page load with its own default theme.
  document.getElementById('embedFrame')?.addEventListener('load', () => {
    syncEmbeddedFrameTheme(document.body.classList.contains('dark-mode'));
  });

  function toggleViaIcon() {
    toggleInput.checked = !toggleInput.checked;
    toggleInput.dispatchEvent(new Event('change'));
  }
  toggleIcon.addEventListener('click', toggleViaIcon);
  toggleIcon.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleViaIcon(); }
  });
})();
</script>
<script src="../staff/assets/js/sidebar-tooltip.js?v=<?= (int) @filemtime(__DIR__ . '/../../staff/assets/js/sidebar-tooltip.js') ?>"></script>
</body>
</html>
