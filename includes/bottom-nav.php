<?php
// $activeNav should be set by the including page: 'dashboard' | 'tree' | 'map' | 'profile' | 'report'
$activeNav = $activeNav ?? '';
require_once __DIR__ . '/logo.php';
?>
<nav class="bottom-nav">

  <!-- HOME -->
  <a href="../dashboard/dashboard.php"
     class="nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">

    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg"
           width="24" height="24"
           viewBox="0 0 24 24"
           fill="currentColor">
        <path d="M3 13h1v7c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-7h1c.4 0 .77-.24.92-.62.15-.37.07-.8-.22-1.09l-8.99-9a.996.996 0 0 0-1.41 0l-9.01 9c-.29.29-.37.72-.22 1.09s.52.62.92.62Zm7 7v-5h4v5zm2-15.59 6 6V20h-2v-5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v5H6v-9.59z"/>
      </svg>
    </span>

    Home
  </a>


  <!-- FAMILY TREE -->
<a href="../family-tree/tree.php"
   class="nav-item <?= $activeNav === 'tree' ? 'active' : '' ?>">

  <span class="nav-icon" style="position: relative; top: 6px;">
    <svg
      width="24"
      height="24"
      style="position: relative; top: -5px;"
      viewBox="-133.12 -133.12 1290.24 1290.24"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      stroke="none"
      transform="rotate(90)"
    >
      <path
        d="M853.333333 192V149.333333h-192v106.666667H320v234.666667H170.666667v42.666666h149.333333v234.666667h341.333333v106.666667h192v-42.666667h-149.333333v-170.666667h149.333333v-42.666666h-192v106.666666H362.666667V298.666667h298.666666v106.666666h192v-42.666666h-149.333333V192z"
        fill="currentColor"
      />

      <path
        d="M85.333333 426.666667h170.666667v170.666666H85.333333z"
        fill="currentColor"
      />

      <path
        d="M768 298.666667h170.666667v170.666667h-170.666667z
           M768 85.333333h170.666667v170.666667h-170.666667z
           M426.666667 192h170.666666v170.666667h-170.666667z
           M426.666667 661.333333h170.666667v170.666667h-170.666667z
           M768 768h170.666667v170.666667h-170.666667z
           M768 554.666667h170.666667v170.666667h-170.666667z"
        fill="currentColor"
      />
    </svg>
  </span>

  Family Tree
</a>


  <!-- MAP VIEW -->
  <a href="../map-view/map.php"
     class="nav-item <?= $activeNav === 'map' ? 'active' : '' ?>">

    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg"
           width="24" height="24"
           viewBox="0 0 24 24"
           fill="currentColor">

        <path d="M6 8.44c-.02 5.1 5.17 9.18 5.39 9.35.18.14.4.21.61.21s.43-.07.61-.21c.22-.17 5.41-4.25 5.39-9.35C18 4.89 15.31 2 12 2S6 4.89 6 8.44m10 0c.01 3.19-2.74 6.08-4 7.24-1.26-1.15-4.01-4.04-4-7.24C8 5.99 9.79 4 12 4s4 1.99 4 4.44"></path>

        <path d="M12 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4m6.02 8.73c-.4.64-.84 1.23-1.27 1.76C18.88 16.97 20 17.68 20 18c0 .51-2.75 2-8 2s-8-1.49-8-2c0-.32 1.12-1.03 3.25-1.51-.43-.53-.86-1.12-1.27-1.76C3.66 15.37 2 16.44 2 18c0 2.75 5.18 4 10 4s10-1.25 10-4c0-1.56-1.67-2.63-3.98-3.27"></path>

      </svg>
    </span>

    Map View
  </a>


  <!-- MY PROFILE -->
  <a href="../profile/profile.php"
     class="nav-item <?= $activeNav === 'profile' ? 'active' : '' ?>">

    <span class="nav-icon">
      <svg xmlns="http://www.w3.org/2000/svg"
           width="24" height="24"
           viewBox="0 0 24 24"
           fill="currentColor">

        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3M4 22h16c.55 0 1-.45 1-1v-1c0-3.86-3.14-7-7-7h-4c-3.86 0-7 3.14-7 7v1c0 .55.45 1 1 1m6-7h4c2.76 0 5 2.24 5 5H5c0-2.76 2.24-5 5-5"></path>

      </svg>
    </span>

    Profile
  </a>