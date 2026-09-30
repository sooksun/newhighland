<?php
/**
 * charts.php — กราฟทั้งหมดของรายงาน วาดเป็น inline SVG ด้วย PHP ล้วน
 * ไม่พึ่ง Chart.js / D3 / CDN ใด ๆ เพื่อให้ไฟล์ HTML ที่ได้เปิดดูได้แบบออฟไลน์
 * สีและตัวอักษรอ้างอิงตัวแปร CSS (--ink/--muted/--grid/--axis) จึงสลับโหมดสว่าง/มืดได้
 */

/** จานสีหลัก */
const PAL = ['#2563eb','#0d9488','#f59e0b','#dc2626','#7c3aed','#0891b2','#65a30d','#db2777','#475569','#b45309'];
/** สีตามระดับความยุ่งยาก 0-3 */
const TIER_COLOR = [0 => '#94a3b8', 1 => '#38bdf8', 2 => '#f59e0b', 3 => '#dc2626'];
/** สีประจำกลุ่มพื้นที่ */
const AREA_COLOR = [1 => '#0d9488', 2 => '#2563eb'];

function svgEsc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function svgOpen(float $w, float $h, string $cls = ''): string
{
    return '<svg class="chart ' . $cls . '" viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" '
         . 'preserveAspectRatio="xMidYMid meet" role="img" xmlns="http://www.w3.org/2000/svg">';
}

function svgText(float $x, float $y, string $t, string $anchor = 'middle', float $size = 12,
                 string $fill = 'var(--ink)', string $weight = '400', float $rot = 0): string
{
    $tr = $rot ? ' transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')"' : '';
    return '<text x="' . $x . '" y="' . $y . '" text-anchor="' . $anchor . '" font-size="' . $size
         . '" fill="' . $fill . '" font-weight="' . $weight . '"' . $tr . '>' . svgEsc($t) . '</text>';
}

/** ปัดขอบบนของแกนให้เป็นเลขสวย */
function niceMax(float $v): float
{
    if ($v <= 0) return 1;
    $exp = (int) floor(log10($v));
    $f   = $v / (10 ** $exp);
    foreach ([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $s) {
        if ($f <= $s) return $s * (10 ** $exp);
    }
    return 10 ** ($exp + 1);
}

/**
 * กราฟแท่งแนวตั้ง — $data = label => value
 * $o: h, color, colors[label=>color], dp, rotate (เอียงป้ายแกน x), suffix
 */
function chartBar(array $data, array $o = []): string
{
    $w = 900;
    $h = (float) ($o['h'] ?? 300);
    $rotate = (bool) ($o['rotate'] ?? false);
    $padL = 58; $padR = 16; $padT = 24; $padB = $rotate ? 104 : 46;
    $dp = (int) ($o['dp'] ?? 0);
    $labels = array_keys($data);
    $vals   = array_map('floatval', array_values($data));
    $n = max(1, count($vals));
    $maxV = niceMax(max(0.0001, $vals ? max($vals) : 1));
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $bw    = $plotW / $n;
    $barW  = min(70.0, $bw * 0.62);

    $s = svgOpen($w, $h);
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4;
        $s .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="var(--grid)"/>';
        $s .= svgText($padL - 8, $y + 4, nf($maxV * $i / 4, $maxV < 10 ? 1 : 0), 'end', 11, 'var(--muted)');
    }
    foreach ($vals as $i => $v) {
        $x  = $padL + $bw * $i + ($bw - $barW) / 2;
        $bh = $plotH * ($v / $maxV);
        $c  = $o['colors'][$labels[$i]] ?? ($o['color'] ?? PAL[0]);
        $s .= '<rect x="' . $x . '" y="' . ($padT + $plotH - $bh) . '" width="' . $barW . '" height="' . max(0.0, $bh)
            . '" rx="3" fill="' . $c . '"><title>' . svgEsc($labels[$i] . ': ' . nf($v, $dp)) . '</title></rect>';
        if ($v > 0) {
            $s .= svgText($x + $barW / 2, $padT + $plotH - $bh - 6, nf($v, $dp) . ($o['suffix'] ?? ''), 'middle', 11, 'var(--ink)', '600');
        }
        if ($rotate) {
            $s .= svgText($x + $barW / 2 - 2, $padT + $plotH + 14, mb_strimwidth((string) $labels[$i], 0, 26, '…'), 'end', 11, 'var(--muted)', '400', -40);
        } else {
            $s .= svgText($x + $barW / 2, $padT + $plotH + 18, (string) $labels[$i], 'middle', 11, 'var(--muted)');
        }
    }
    $s .= '<line x1="' . $padL . '" y1="' . ($padT + $plotH) . '" x2="' . ($w - $padR) . '" y2="' . ($padT + $plotH) . '" stroke="var(--axis)" stroke-width="1.5"/>';
    return $s . '</svg>';
}

/**
 * กราฟแท่งแนวนอน — $rows = [['label'=>, 'value'=>, 'note'=>?, 'color'=>?], ...]
 */
function chartHBar(array $rows, array $o = []): string
{
    $w = 900;
    $rowH = (float) ($o['rowH'] ?? 26);
    $padL = (float) ($o['padL'] ?? 250);
    $padR = 110; $padT = 10; $padB = 26;
    $n = max(1, count($rows));
    $h = $padT + $padB + $rowH * $n;
    $dp = (int) ($o['dp'] ?? 0);
    $vals = $rows ? array_map(fn($r) => (float) $r['value'], $rows) : [1.0];
    $maxV = niceMax(max(0.0001, max($vals)));
    $plotW = $w - $padL - $padR;

    $s = svgOpen($w, $h);
    for ($i = 0; $i <= 4; $i++) {
        $x = $padL + $plotW * $i / 4;
        $s .= '<line x1="' . $x . '" y1="' . $padT . '" x2="' . $x . '" y2="' . ($padT + $rowH * $n) . '" stroke="var(--grid)"/>';
        $s .= svgText($x, $padT + $rowH * $n + 16, nf($maxV * $i / 4, $maxV < 10 ? 1 : 0), 'middle', 10, 'var(--muted)');
    }
    foreach (array_values($rows) as $i => $r) {
        $y  = $padT + $rowH * $i;
        $v  = (float) $r['value'];
        $bw = $plotW * ($v / $maxV);
        $c  = $r['color'] ?? ($o['color'] ?? PAL[0]);
        $s .= svgText($padL - 8, $y + $rowH / 2 + 4, mb_strimwidth((string) $r['label'], 0, 44, '…'), 'end', 11.5, 'var(--ink)');
        $s .= '<rect x="' . $padL . '" y="' . ($y + 4) . '" width="' . max(1.0, $bw) . '" height="' . ($rowH - 8)
            . '" rx="3" fill="' . $c . '"><title>' . svgEsc($r['label'] . ': ' . nf($v, $dp)) . '</title></rect>';
        $s .= svgText($padL + $bw + 6, $y + $rowH / 2 + 4, nf($v, $dp) . (isset($r['note']) ? '  ' . $r['note'] : ''), 'start', 11, 'var(--ink)', '600');
    }
    return $s . '</svg>';
}

/**
 * โดนัท + คำอธิบายด้านขวา — $data = label => value
 */
function chartDonut(array $data, array $colors = [], array $o = []): string
{
    $w = 900;
    $h = (float) ($o['h'] ?? max(250, 40 + count($data) * 26));
    $cx = 150; $cy = $h / 2; $rOut = 95; $rIn = 58;
    $total = (float) array_sum($data);

    $s = svgOpen($w, $h);
    $ang = -M_PI / 2;
    $i = 0;
    foreach ($data as $label => $v) {
        $v = (float) $v;
        if ($total > 0 && $v > 0) {
            $frac  = $v / $total;
            $a2    = $ang + $frac * 2 * M_PI;
            $c     = $colors[$label] ?? PAL[$i % count(PAL)];
            if ($frac >= 0.9999) {
                $s .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . (($rOut + $rIn) / 2) . '" fill="none" stroke="' . $c
                    . '" stroke-width="' . ($rOut - $rIn) . '"/>';
            } else {
                $large = $frac > 0.5 ? 1 : 0;
                $x1 = $cx + $rOut * cos($ang); $y1 = $cy + $rOut * sin($ang);
                $x2 = $cx + $rOut * cos($a2);  $y2 = $cy + $rOut * sin($a2);
                $x3 = $cx + $rIn  * cos($a2);  $y3 = $cy + $rIn  * sin($a2);
                $x4 = $cx + $rIn  * cos($ang); $y4 = $cy + $rIn  * sin($ang);
                $s .= '<path d="M' . $x1 . ' ' . $y1 . ' A' . $rOut . ' ' . $rOut . ' 0 ' . $large . ' 1 ' . $x2 . ' ' . $y2
                    . ' L' . $x3 . ' ' . $y3 . ' A' . $rIn . ' ' . $rIn . ' 0 ' . $large . ' 0 ' . $x4 . ' ' . $y4 . ' Z" fill="' . $c . '">'
                    . '<title>' . svgEsc($label . ': ' . nf($v) . ' (' . pct($v, $total) . ')') . '</title></path>';
            }
            $ang = $a2;
        }
        $i++;
    }
    $s .= svgText($cx, $cy - 2, nf($total), 'middle', 28, 'var(--ink)', '700');
    $s .= svgText($cx, $cy + 20, (string) ($o['centerLabel'] ?? 'แห่ง'), 'middle', 12, 'var(--muted)');

    $ly = $cy - (count($data) * 26) / 2 + 14;
    $i = 0;
    foreach ($data as $label => $v) {
        $c = $colors[$label] ?? PAL[$i % count(PAL)];
        $s .= '<rect x="300" y="' . ($ly - 10) . '" width="13" height="13" rx="3" fill="' . $c . '"/>';
        $s .= svgText(322, $ly + 1, (string) $label, 'start', 12.5, 'var(--ink)');
        $s .= svgText(770, $ly + 1, nf((float) $v) . ' ' . ($o['unit'] ?? 'แห่ง'), 'end', 12.5, 'var(--ink)', '600');
        $s .= svgText(872, $ly + 1, $total > 0 ? pct((float) $v, $total) : '–', 'end', 12.5, 'var(--muted)');
        $ly += 26;
        $i++;
    }
    return $s . '</svg>';
}

/**
 * แท่งซ้อนแนวนอน (สัดส่วน 100%) — $rows = [['label'=>, 'parts'=>[seg=>value]], ...]
 */
function chartStacked(array $rows, array $segColors, array $o = []): string
{
    $w = 900;
    $rowH = (float) ($o['rowH'] ?? 32);
    $padL = (float) ($o['padL'] ?? 210);
    $padR = 66; $padT = 8; $legendH = 32;
    $n = max(1, count($rows));
    $h = $padT + $rowH * $n + $legendH;
    $plotW = $w - $padL - $padR;

    $s = svgOpen($w, $h);
    foreach (array_values($rows) as $i => $r) {
        $y   = $padT + $rowH * $i;
        $tot = max(0.0001, (float) array_sum($r['parts']));
        $s  .= svgText($padL - 8, $y + $rowH / 2 + 4, mb_strimwidth((string) $r['label'], 0, 36, '…'), 'end', 11.5, 'var(--ink)');
        $x = $padL;
        foreach ($r['parts'] as $seg => $v) {
            $v = (float) $v;
            if ($v <= 0) continue;
            $bw = $plotW * ($v / $tot);
            $c  = $segColors[$seg] ?? PAL[0];
            $s .= '<rect x="' . $x . '" y="' . ($y + 5) . '" width="' . $bw . '" height="' . ($rowH - 10) . '" fill="' . $c . '">'
                . '<title>' . svgEsc($r['label'] . ' — ' . $seg . ': ' . nf($v) . ' (' . pct($v, $tot) . ')') . '</title></rect>';
            if ($bw > 32) $s .= svgText($x + $bw / 2, $y + $rowH / 2 + 4, nf($v), 'middle', 10.5, '#ffffff', '600');
            $x += $bw;
        }
        $s .= svgText($w - $padR + 6, $y + $rowH / 2 + 4, nf($tot), 'start', 11, 'var(--muted)');
    }
    $lx = $padL;
    $ly = $padT + $rowH * $n + 20;
    foreach ($segColors as $seg => $c) {
        $s .= '<rect x="' . $lx . '" y="' . ($ly - 9) . '" width="11" height="11" rx="2" fill="' . $c . '"/>';
        $s .= svgText($lx + 16, $ly + 1, (string) $seg, 'start', 11, 'var(--muted)');
        $lx += 26 + mb_strwidth((string) $seg) * 6.6;
    }
    return $s . '</svg>';
}

/**
 * กราฟสหสัมพันธ์แบบสองทาง (บวกไปขวา ลบไปซ้าย) — $rows = [['label'=>,'r'=>,'n'=>], ...]
 */
function chartDiverging(array $rows, array $o = []): string
{
    $w = 900;
    $rowH = 28; $padL = 260; $padR = 70; $padT = 26; $padB = 22;
    $n = max(1, count($rows));
    $h = $padT + $padB + $rowH * $n;
    $plotW = $w - $padL - $padR;
    $mid   = $padL + $plotW / 2;
    $half  = $plotW / 2;

    $s = svgOpen($w, $h);
    foreach ([-1.0, -0.5, 0.0, 0.5, 1.0] as $t) {
        $x = $mid + $half * $t;
        $s .= '<line x1="' . $x . '" y1="' . $padT . '" x2="' . $x . '" y2="' . ($padT + $rowH * $n) . '" stroke="'
            . ($t == 0.0 ? 'var(--axis)' : 'var(--grid)') . '"/>';
        $s .= svgText($x, $padT - 8, number_format($t, 1), 'middle', 10, 'var(--muted)');
    }
    foreach (array_values($rows) as $i => $r) {
        $y  = $padT + $rowH * $i;
        $rv = (float) $r['r'];
        $bw = abs($rv) * $half;
        $x  = $rv >= 0 ? $mid : $mid - $bw;
        $c  = $rv >= 0 ? '#0d9488' : '#dc2626';
        $s .= svgText($padL - 10, $y + $rowH / 2 + 4, mb_strimwidth((string) $r['label'], 0, 44, '…'), 'end', 11.5, 'var(--ink)');
        $s .= '<rect x="' . $x . '" y="' . ($y + 5) . '" width="' . max(1.0, $bw) . '" height="' . ($rowH - 10) . '" rx="3" fill="' . $c . '">'
            . '<title>' . svgEsc($r['label'] . ': r = ' . number_format($rv, 3) . ' (n=' . ($r['n'] ?? 0) . ')') . '</title></rect>';
        $tx = $rv >= 0 ? $x + $bw + 6 : $x - 6;
        $s .= svgText($tx, $y + $rowH / 2 + 4, number_format($rv, 3), $rv >= 0 ? 'start' : 'end', 11, 'var(--ink)', '600');
    }
    return $s . '</svg>';
}

/**
 * กราฟเส้นหลายชุด — $series = [name => [x=>y]], $xLabels = ลำดับแกน x
 */
function chartLine(array $series, array $xLabels, array $o = []): string
{
    $w = 900;
    $h = (float) ($o['h'] ?? 320);
    $padL = 58; $padR = 20; $padT = 24; $padB = 56;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $all = [];
    foreach ($series as $pts) foreach ($pts as $v) if ($v !== null) $all[] = (float) $v;
    $maxV = niceMax(max(0.0001, $all ? max($all) : 1));
    $nx = max(1, count($xLabels));
    $stepX = $nx > 1 ? $plotW / ($nx - 1) : 0;

    $s = svgOpen($w, $h);
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4;
        $s .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="var(--grid)"/>';
        $s .= svgText($padL - 8, $y + 4, nf($maxV * $i / 4, $maxV < 10 ? 1 : 0), 'end', 11, 'var(--muted)');
    }
    foreach ($xLabels as $i => $lb) {
        $x = $padL + $stepX * $i;
        $s .= svgText($x, $padT + $plotH + 20, (string) $lb, 'middle', 11.5, 'var(--muted)');
    }
    $ci = 0;
    foreach ($series as $name => $pts) {
        $c = $o['colors'][$name] ?? PAL[$ci % count(PAL)];
        $d = ''; $dots = '';
        foreach ($xLabels as $i => $lb) {
            $v = $pts[$lb] ?? null;
            if ($v === null) continue;
            $x = $padL + $stepX * $i;
            $y = $padT + $plotH - $plotH * ((float) $v / $maxV);
            $d .= ($d === '' ? 'M' : 'L') . $x . ' ' . $y . ' ';
            $dots .= '<circle cx="' . $x . '" cy="' . $y . '" r="4.5" fill="' . $c . '" stroke="var(--card)" stroke-width="1.5">'
                   . '<title>' . svgEsc($name . ' ' . $lb . ': ' . nf((float) $v, (int) ($o['dp'] ?? 0))) . '</title></circle>';
            $dots .= svgText($x, $y - 11, nf((float) $v, (int) ($o['dp'] ?? 0)), 'middle', 10.5, 'var(--ink)', '600');
        }
        if ($d !== '') $s .= '<path d="' . trim($d) . '" fill="none" stroke="' . $c . '" stroke-width="2.5" stroke-linejoin="round"/>' . $dots;
        $ci++;
    }
    $lx = $padL; $ly = $h - 12; $ci = 0;
    foreach ($series as $name => $pts) {
        $c = $o['colors'][$name] ?? PAL[$ci % count(PAL)];
        $s .= '<rect x="' . $lx . '" y="' . ($ly - 9) . '" width="11" height="11" rx="2" fill="' . $c . '"/>';
        $s .= svgText($lx + 16, $ly + 1, (string) $name, 'start', 11.5, 'var(--muted)');
        $lx += 30 + mb_strwidth((string) $name) * 6.6;
        $ci++;
    }
    return $s . '</svg>';
}

/**
 * แผนภาพการกระจาย + เส้นแนวโน้ม (least squares)
 * $pts = [['x'=>,'y'=>,'label'=>?,'tier'=>?], ...]
 */
function chartScatter(array $pts, array $o = []): string
{
    $w = 900;
    $h = (float) ($o['h'] ?? 380);
    $padL = 62; $padR = 20; $padT = 20; $padB = 56;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $xs = array_map(fn($p) => (float) $p['x'], $pts);
    $ys = array_map(fn($p) => (float) $p['y'], $pts);
    $xMax = niceMax(max(0.0001, $xs ? max($xs) : 1));
    $yMax = (float) ($o['yMax'] ?? niceMax(max(0.0001, $ys ? max($ys) : 1)));

    $s = svgOpen($w, $h);
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4;
        $s .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="var(--grid)"/>';
        $s .= svgText($padL - 8, $y + 4, nf($yMax * $i / 4, 0), 'end', 11, 'var(--muted)');
        $x = $padL + $plotW * $i / 4;
        $s .= '<line x1="' . $x . '" y1="' . $padT . '" x2="' . $x . '" y2="' . ($padT + $plotH) . '" stroke="var(--grid)"/>';
        $s .= svgText($x, $padT + $plotH + 18, nf($xMax * $i / 4, 0), 'middle', 11, 'var(--muted)');
    }
    // เส้นเกณฑ์ผ่าน 50 คะแนน
    if (($o['threshold'] ?? null) !== null) {
        $ty = $padT + $plotH - $plotH * ((float) $o['threshold'] / $yMax);
        $s .= '<line x1="' . $padL . '" y1="' . $ty . '" x2="' . ($w - $padR) . '" y2="' . $ty
            . '" stroke="#dc2626" stroke-width="1.5" stroke-dasharray="6 4"/>';
        $s .= svgText($w - $padR - 4, $ty - 6, 'เกณฑ์ผ่าน ' . nf((float) $o['threshold']) . ' คะแนน', 'end', 11, '#dc2626', '600');
    }
    foreach ($pts as $p) {
        $x = $padL + $plotW * (min((float) $p['x'], $xMax) / $xMax);
        $y = $padT + $plotH - $plotH * (min((float) $p['y'], $yMax) / $yMax);
        $c = TIER_COLOR[$p['tier'] ?? 0] ?? PAL[0];
        $s .= '<circle cx="' . round($x, 1) . '" cy="' . round($y, 1) . '" r="3.6" fill="' . $c . '" fill-opacity="0.62">'
            . '<title>' . svgEsc(($p['label'] ?? '') . ' — x=' . nf((float) $p['x'], 1) . ', คะแนน=' . nf((float) $p['y'], 2)) . '</title></circle>';
    }
    // เส้นแนวโน้ม
    $n = count($pts);
    if ($n >= 3) {
        $mx = array_sum($xs) / $n; $my = array_sum($ys) / $n;
        $sxy = 0.0; $sxx = 0.0;
        for ($i = 0; $i < $n; $i++) { $sxy += ($xs[$i] - $mx) * ($ys[$i] - $my); $sxx += ($xs[$i] - $mx) ** 2; }
        if ($sxx > 0) {
            $b = $sxy / $sxx; $a = $my - $b * $mx;
            $x1 = 0.0; $x2 = $xMax;
            $y1 = $a + $b * $x1; $y2 = $a + $b * $x2;
            $cy1 = $padT + $plotH - $plotH * (max(0.0, min($y1, $yMax)) / $yMax);
            $cy2 = $padT + $plotH - $plotH * (max(0.0, min($y2, $yMax)) / $yMax);
            $s .= '<line x1="' . $padL . '" y1="' . $cy1 . '" x2="' . ($w - $padR) . '" y2="' . $cy2
                . '" stroke="var(--ink)" stroke-width="2" stroke-dasharray="8 5" opacity="0.55"/>';
        }
    }
    $s .= svgText($padL + $plotW / 2, $h - 14, (string) ($o['xLabel'] ?? ''), 'middle', 12, 'var(--muted)');
    $s .= svgText(14, $padT + $plotH / 2, (string) ($o['yLabel'] ?? ''), 'middle', 12, 'var(--muted)', '400', -90);
    return $s . '</svg>';
}

/**
 * กราฟแท่งกลุ่ม — $groups = [groupLabel => [seriesName => value]]
 */
function chartGrouped(array $groups, array $seriesColors, array $o = []): string
{
    $w = 900;
    $h = (float) ($o['h'] ?? 320);
    $padL = 58; $padR = 16; $padT = 24; $padB = 62;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $names = array_keys($seriesColors);
    $all = [];
    foreach ($groups as $g) foreach ($g as $v) $all[] = (float) $v;
    $maxV = niceMax(max(0.0001, $all ? max($all) : 1));
    $ng = max(1, count($groups));
    $gw = $plotW / $ng;
    $bw = min(46.0, ($gw * 0.72) / max(1, count($names)));

    $s = svgOpen($w, $h);
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4;
        $s .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="var(--grid)"/>';
        $s .= svgText($padL - 8, $y + 4, nf($maxV * $i / 4, $maxV < 10 ? 1 : 0), 'end', 11, 'var(--muted)');
    }
    $gi = 0;
    foreach ($groups as $glabel => $g) {
        $x0 = $padL + $gw * $gi + ($gw - $bw * count($names)) / 2;
        foreach ($names as $k => $nm) {
            $v  = (float) ($g[$nm] ?? 0);
            $bh = $plotH * ($v / $maxV);
            $x  = $x0 + $bw * $k;
            $s .= '<rect x="' . ($x + 2) . '" y="' . ($padT + $plotH - $bh) . '" width="' . ($bw - 4) . '" height="' . max(0.0, $bh)
                . '" rx="3" fill="' . $seriesColors[$nm] . '"><title>' . svgEsc($glabel . ' — ' . $nm . ': ' . nf($v, (int) ($o['dp'] ?? 0))) . '</title></rect>';
            if ($v > 0) $s .= svgText($x + $bw / 2, $padT + $plotH - $bh - 5, nf($v, (int) ($o['dp'] ?? 0)), 'middle', 10, 'var(--ink)', '600');
        }
        $s .= svgText($padL + $gw * $gi + $gw / 2, $padT + $plotH + 18, mb_strimwidth((string) $glabel, 0, 22, '…'), 'middle', 11, 'var(--muted)');
        $gi++;
    }
    $lx = $padL; $ly = $h - 12;
    foreach ($seriesColors as $nm => $c) {
        $s .= '<rect x="' . $lx . '" y="' . ($ly - 9) . '" width="11" height="11" rx="2" fill="' . $c . '"/>';
        $s .= svgText($lx + 16, $ly + 1, (string) $nm, 'start', 11.5, 'var(--muted)');
        $lx += 30 + mb_strwidth((string) $nm) * 6.6;
    }
    return $s . '</svg>';
}

/**
 * แผนภาพกล่อง (box plot) แนวนอนหลายตัวแปร — $rows = [['label'=>, 'st'=>describe()], ...]
 * ทุกแถวปรับสเกลของตัวเองเป็น 0-100% ของ max เพื่อเทียบรูปทรงการกระจาย
 */
function chartBox(array $rows, array $o = []): string
{
    $w = 900;
    $rowH = 38; $padL = 250; $padR = 92; $padT = 12; $padB = 10;
    $n = max(1, count($rows));
    $h = $padT + $padB + $rowH * $n;
    $plotW = $w - $padL - $padR;

    $s = svgOpen($w, $h);
    foreach (array_values($rows) as $i => $r) {
        $st = $r['st'];
        if (($st['n'] ?? 0) === 0) continue;
        $y   = $padT + $rowH * $i + $rowH / 2;
        $max = max(0.0001, (float) $st['max']);
        $sc  = fn($v) => $padL + $plotW * (max(0.0, (float) $v) / $max);
        $c   = $r['color'] ?? PAL[$i % count(PAL)];
        $s  .= svgText($padL - 10, $y + 4, mb_strimwidth((string) $r['label'], 0, 42, '…'), 'end', 11.5, 'var(--ink)');
        $s  .= '<line x1="' . $sc($st['min']) . '" y1="' . $y . '" x2="' . $sc($st['max']) . '" y2="' . $y . '" stroke="var(--axis)" stroke-width="1.2"/>';
        foreach (['min', 'max'] as $k) {
            $s .= '<line x1="' . $sc($st[$k]) . '" y1="' . ($y - 7) . '" x2="' . $sc($st[$k]) . '" y2="' . ($y + 7) . '" stroke="var(--axis)" stroke-width="1.2"/>';
        }
        $bx = $sc($st['q1']); $bw = max(2.0, $sc($st['q3']) - $bx);
        $s .= '<rect x="' . $bx . '" y="' . ($y - 11) . '" width="' . $bw . '" height="22" rx="3" fill="' . $c . '" fill-opacity="0.30" stroke="' . $c . '" stroke-width="1.4">'
            . '<title>' . svgEsc($r['label'] . ' — Q1=' . nf($st['q1'], 2) . ' | มัธยฐาน=' . nf($st['median'], 2) . ' | Q3=' . nf($st['q3'], 2)) . '</title></rect>';
        $s .= '<line x1="' . $sc($st['median']) . '" y1="' . ($y - 11) . '" x2="' . $sc($st['median']) . '" y2="' . ($y + 11) . '" stroke="' . $c . '" stroke-width="2.6"/>';
        $s .= '<circle cx="' . $sc($st['mean']) . '" cy="' . $y . '" r="3.4" fill="var(--card)" stroke="' . $c . '" stroke-width="2"/>';
        $s .= svgText($w - $padR + 8, $y + 4, 'สูงสุด ' . nf($st['max'], 1), 'start', 10.5, 'var(--muted)');
    }
    return $s . '</svg>';
}
