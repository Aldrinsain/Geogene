<?php
// GeoGene Admin - lightweight SVG chart helpers.
// No external library, no CDN, no JavaScript required - renders entirely
// server-side so it always works even with no internet connection.

/**
 * Multi-series line/area chart.
 * @param string[] $labels   X-axis labels (e.g. months)
 * @param array    $series   [['label'=>string,'color'=>'#hex','data'=>int[]], ...]
 */
function geogene_svg_line_chart(array $labels, array $series, int $width = 620, int $height = 260): string
{
    $padL = 38; $padR = 14; $padT = 16; $padB = 34;
    $plotW = $width - $padL - $padR;
    $plotH = $height - $padT - $padB;

    $allValues = [0];
    foreach ($series as $s) { foreach ($s['data'] as $v) { $allValues[] = (float) $v; } }
    $maxVal = max($allValues);
    $maxVal = $maxVal <= 0 ? 4 : $maxVal;
    // Round the axis max up to a "nice" number.
    $niceMax = $maxVal <= 4 ? 4 : (ceil($maxVal / 5) * 5);

    $count = max(1, count($labels));
    $stepX = $count > 1 ? $plotW / ($count - 1) : 0;

    $toX = fn($i) => $padL + ($count > 1 ? $i * $stepX : $plotW / 2);
    $toY = fn($v) => $padT + $plotH - ($niceMax > 0 ? ($v / $niceMax) * $plotH : 0);

    $svg  = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;font-family:inherit;">';

    // Horizontal gridlines + y-axis labels (0, 25%, 50%, 75%, 100% of niceMax)
    for ($g = 0; $g <= 4; $g++) {
        $val = round(($niceMax / 4) * $g);
        $y = $toY($val);
        $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($padL + $plotW) . '" y2="' . $y . '" stroke="var(--border,#e5e7eb)" stroke-width="1" />';
        $svg .= '<text x="' . ($padL - 8) . '" y="' . ($y + 3) . '" font-size="9.5" fill="var(--text-secondary,#8a8f8a)" text-anchor="end">' . $val . '</text>';
    }

    // X-axis labels
    foreach ($labels as $i => $lbl) {
        $svg .= '<text x="' . $toX($i) . '" y="' . ($height - 10) . '" font-size="10" fill="var(--text-secondary,#8a8f8a)" text-anchor="middle">' . htmlspecialchars($lbl) . '</text>';
    }

    // Series: area fill + line + points
    foreach ($series as $s) {
        $pts = [];
        foreach ($s['data'] as $i => $v) { $pts[] = $toX($i) . ',' . $toY((float) $v); }
        $lineStr = implode(' ', $pts);

        $areaPts = $lineStr . ' ' . $toX($count - 1) . ',' . ($padT + $plotH) . ' ' . $toX(0) . ',' . ($padT + $plotH);
        $svg .= '<polygon points="' . $areaPts . '" fill="' . $s['color'] . '" fill-opacity="0.12" />';
        $svg .= '<polyline points="' . $lineStr . '" fill="none" stroke="' . $s['color'] . '" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" />';
        foreach ($s['data'] as $i => $v) {
            $svg .= '<circle cx="' . $toX($i) . '" cy="' . $toY((float) $v) . '" r="3.2" fill="' . $s['color'] . '" />';
        }
    }

    $svg .= '</svg>';
    return $svg;
}

/**
 * Donut chart with a legend.
 * @param array $segments [['label'=>string,'value'=>number,'color'=>'#hex'], ...]
 */
function geogene_svg_donut_chart(array $segments, int $size = 190): string
{
    $total = array_sum(array_column($segments, 'value'));
    $cx = $size / 2; $cy = $size / 2;
    $r = $size / 2 - 14;
    $circumference = 2 * M_PI * $r;

    $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg" style="width:' . $size . 'px;height:' . $size . 'px;transform:rotate(-90deg);">';
    $offset = 0;
    foreach ($segments as $seg) {
        $val = (float) $seg['value'];
        $frac = $total > 0 ? $val / $total : 0;
        $dash = $frac * $circumference;
        $gap = $circumference - $dash;
        if ($frac > 0) {
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $seg['color'] . '"'
                  . ' stroke-width="' . ($size * 0.16) . '" stroke-dasharray="' . $dash . ' ' . $gap . '"'
                  . ' stroke-dashoffset="-' . $offset . '" />';
        }
        $offset += $dash;
    }
    $svg .= '</svg>';
    return $svg;
}

function geogene_chart_legend(array $items): string
{
    $html = '<div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center;margin-top:12px;">';
    foreach ($items as $it) {
        $html .= '<span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary,#6b6b6b);">'
               . '<span style="width:10px;height:10px;border-radius:3px;background:' . $it['color'] . ';display:inline-block;"></span>'
               . htmlspecialchars($it['label']) . '</span>';
    }
    $html .= '</div>';
    return $html;
}
