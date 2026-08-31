<?php
/**
 * GeoGene detailed tree + family logo (leaf-by-leaf, matches reference art).
 * Usage: include this file, then echo geogene_logo($size, $color)
 */
function geogene_logo($size = 60, $color = '#39FF88') {
    ob_start();
    ?>
    <svg width="<?= $size ?>" height="<?= $size ?>" viewBox="0 0 500 500" fill="none" xmlns="http://www.w3.org/2000/svg">
      <g stroke="<?= $color ?>" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <!-- trunk splitting into arch -->
        <path d="M250,165
                 C248,210 248,250 236,285
                 C220,325 190,355 178,395
                 C170,420 168,445 168,465"/>
        <path d="M250,165
                 C252,210 252,250 264,285
                 C280,325 310,355 322,395
                 C330,420 332,445 332,465"/>

        <!-- main branch forks -->
        <path d="M250,200 C220,180 190,165 160,150 C130,135 100,128 75,132"/>
        <path d="M250,200 C280,180 310,165 340,150 C370,135 400,128 425,132"/>
        <path d="M235,230 C195,215 155,205 118,205"/>
        <path d="M265,230 C305,215 345,205 382,205"/>
        <path d="M220,270 C175,262 130,262 92,272"/>
        <path d="M280,270 C325,262 370,262 408,272"/>
        <path d="M210,320 C165,320 122,330 85,352"/>
        <path d="M290,320 C335,320 378,330 415,352"/>

        <!-- canopy outer leaf clusters (organic loops = leaf silhouettes) -->
        <g stroke-width="4">
          <path d="M75,132 C55,120 45,100 55,80 C68,90 78,105 75,132Z"/>
          <path d="M118,205 C90,195 72,175 80,150 C100,160 115,180 118,205Z"/>
          <path d="M92,272 C62,268 40,250 45,222 C70,228 90,248 92,272Z"/>
          <path d="M85,352 C55,352 32,335 34,306 C60,310 82,328 85,352Z"/>
          <path d="M160,150 C140,130 130,105 145,80 C165,92 175,115 160,150Z"/>
          <path d="M195,215 C175,190 168,162 188,138 C206,155 210,185 195,215Z"/>
          <path d="M175,262 C155,238 150,208 172,185 C188,205 190,235 175,262Z"/>
          <path d="M165,320 C145,298 140,268 162,246 C178,265 180,296 165,320Z"/>

          <path d="M425,132 C445,120 455,100 445,80 C432,90 422,105 425,132Z"/>
          <path d="M382,205 C410,195 428,175 420,150 C400,160 385,180 382,205Z"/>
          <path d="M408,272 C438,268 460,250 455,222 C430,228 410,248 408,272Z"/>
          <path d="M415,352 C445,352 468,335 466,306 C440,310 418,328 415,352Z"/>
          <path d="M340,150 C360,130 370,105 355,80 C335,92 325,115 340,150Z"/>
          <path d="M305,215 C325,190 332,162 312,138 C294,155 290,185 305,215Z"/>
          <path d="M325,262 C345,238 350,208 328,185 C312,205 310,235 325,262Z"/>
          <path d="M335,320 C355,298 360,268 338,246 C322,265 320,296 335,320Z"/>

          <path d="M250,165 C230,140 228,110 250,90 C272,110 270,140 250,165Z"/>
          <path d="M250,100 C235,80 236,58 252,42 C270,58 268,82 250,100Z"/>
        </g>

        <!-- people silhouettes under the arch -->
        <g stroke-width="5" stroke-linejoin="round">
          <!-- left family group -->
          <circle cx="182" cy="392" r="9"/>
          <path d="M170,410 Q182,398 194,410 L194,440 L170,440 Z"/>
          <circle cx="205" cy="378" r="11"/>
          <path d="M190,398 Q205,384 220,398 L220,440 L190,440 Z"/>
          <circle cx="232" cy="392" r="9"/>
          <path d="M220,412 Q232,400 244,412 L244,440 L220,440 Z"/>

          <!-- right family group -->
          <circle cx="268" cy="392" r="9"/>
          <path d="M256,412 Q268,400 280,412 L280,440 L256,440 Z"/>
          <circle cx="295" cy="378" r="11"/>
          <path d="M280,398 Q295,384 310,398 L310,440 L280,440 Z"/>
          <circle cx="318" cy="392" r="9"/>
          <path d="M306,410 Q318,398 330,410 L330,440 L306,440 Z"/>
        </g>

        <!-- ground wave -->
        <path d="M130,462 C180,478 320,478 370,462" stroke-width="5"/>
      </g>
    </svg>
    <?php
    return ob_get_clean();
}
?>
