<?php
/**
 * render.php — ประกอบผลวิเคราะห์เป็นหน้า HTML เดี่ยว (self-contained)
 * ทุกกราฟเป็น inline SVG, ตารางกรอง/ค้นหาด้วย JavaScript ล้วนที่ฝังในไฟล์
 */

// ---------------------------------------------------------------------
//  ชิ้นส่วน UI
// ---------------------------------------------------------------------

function kpiCard(string $label, string $value, string $sub = '', string $tone = ''): string
{
    return '<div class="kpi ' . $tone . '"><div class="kpi-v">' . h($value) . '</div>'
         . '<div class="kpi-l">' . h($label) . '</div>'
         . ($sub !== '' ? '<div class="kpi-s">' . h($sub) . '</div>' : '') . '</div>';
}

function card(string $title, string $body, string $lead = '', string $id = ''): string
{
    return '<section class="card"' . ($id ? ' id="' . h($id) . '"' : '') . '>'
         . '<h3>' . h($title) . '</h3>'
         . ($lead !== '' ? '<p class="lead">' . $lead . '</p>' : '')
         . $body . '</section>';
}

function note(string $html, string $kind = 'info'): string
{
    return '<p class="note ' . $kind . '">' . $html . '</p>';
}

/**
 * ตาราง — $head = [ชื่อคอลัมน์], $rows = [[cell,...]], $align = ['l','r',...]
 */
function tbl(array $head, array $rows, array $align = [], string $cls = ''): string
{
    $s = '<div class="tw"><table class="' . $cls . '"><thead><tr>';
    foreach ($head as $i => $hd) {
        $a = $align[$i] ?? 'l';
        $s .= '<th class="a-' . $a . '">' . h((string) $hd) . '</th>';
    }
    $s .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $s .= '<tr>';
        foreach (array_values($r) as $i => $c) {
            $a = $align[$i] ?? 'l';
            $s .= '<td class="a-' . $a . '">' . $c . '</td>';
        }
        $s .= '</tr>';
    }
    return $s . '</tbody></table></div>';
}

/** แถบสัดส่วนเล็ก ๆ ในตาราง */
function miniBar(float $pctVal, string $color = '#2563eb'): string
{
    $w = max(0.0, min(100.0, $pctVal));
    return '<span class="mb"><span class="mb-f" style="width:' . round($w, 1) . '%;background:' . $color . '"></span></span>';
}

function areaName(int $area): string
{
    return $area === 1 ? 'กลุ่มโรงเรียนพื้นที่สูง' : 'กลุ่มโรงเรียนพื้นที่เกาะ';
}
function areaShort(int $area): string
{
    return $area === 1 ? 'พื้นที่สูง' : 'พื้นที่เกาะ';
}

// ---------------------------------------------------------------------
//  ส่วนวิเคราะห์ของกลุ่มพื้นที่หนึ่ง
// ---------------------------------------------------------------------

function renderArea(array $A, string $anchor): string
{
    $area  = $A['area'];
    $color = AREA_COLOR[$area];
    $items = $area === 1 ? HIGH_ITEMS : ISLAND_ITEMS;
    $st    = $A['scoreStats'];
    $out   = '';

    // ---------- KPI ----------
    $out .= '<div class="kpis">'
        . kpiCard('โรงเรียนในรอบประเมิน', nf($A['nAll']) . ' แห่ง', 'ทุกสถานะ')
        . kpiCard('ประเมิน/คิดคะแนนแล้ว', nf($A['nScored']) . ' แห่ง', pct((float) $A['nScored'], (float) max(1, $A['nAll'])) . ' ของรอบ')
        . kpiCard('ผ่านเกณฑ์ (≥ 50 คะแนน)', nf($A['nPass']) . ' แห่ง', number_format($A['passRate'], 1) . '% ของที่ประเมิน', 'good')
        . kpiCard('ไม่ผ่านเกณฑ์', nf($A['nFail']) . ' แห่ง', pct((float) $A['nFail'], (float) max(1, $A['nScored'])) . ' ของที่ประเมิน', 'bad')
        . kpiCard('คะแนนเฉลี่ย', nf($st['mean'], 2), 'S.D. ' . nf($st['sd'], 2))
        . kpiCard('คะแนนมัธยฐาน', nf($st['median'], 2), 'ต่ำสุด ' . nf($st['min'], 2) . ' / สูงสุด ' . nf($st['max'], 2))
        . kpiCard('นักเรียนในกลุ่มที่ผ่านเกณฑ์', nf($A['stuPassed']) . ' คน', 'จากทั้งหมด ' . nf($A['stuAll']) . ' คน')
        . kpiCard('จังหวัดที่มีโรงเรียนกลุ่มนี้', nf(count($A['byProvince'])) . ' จังหวัด', nf(count($A['bySao'])) . ' เขตพื้นที่การศึกษา')
        . '</div>';

    // ---------- 1) การกระจายคะแนน ----------
    $binColors = [];
    foreach ($A['bins'] as $k => $_) {
        $lo = (int) $k;
        $binColors[$k] = $lo >= 70 ? TIER_COLOR[3] : ($lo >= 60 ? TIER_COLOR[2] : ($lo >= 50 ? TIER_COLOR[1] : TIER_COLOR[0]));
    }
    $iqr = ($st['q3'] ?? 0) - ($st['q1'] ?? 0);
    $skew = ($st['mean'] !== null && $st['median'] !== null)
        ? ($st['mean'] > $st['median'] + 0.5 ? 'เบ้ขวาเล็กน้อย (มีโรงเรียนคะแนนสูงลากค่าเฉลี่ยขึ้น)'
          : ($st['mean'] < $st['median'] - 0.5 ? 'เบ้ซ้ายเล็กน้อย (มีโรงเรียนคะแนนต่ำลากค่าเฉลี่ยลง)' : 'ค่อนข้างสมมาตร'))
        : '–';
    $lead = 'คะแนนเต็ม 100 คะแนน เกณฑ์ผ่านคือ <b>50 คะแนนขึ้นไป</b> — กลุ่มนี้มีคะแนนเฉลี่ย <b>'
          . nf($st['mean'], 2) . '</b> คะแนน มัธยฐาน <b>' . nf($st['median'], 2) . '</b> คะแนน '
          . 'พิสัยระหว่างควอไทล์ (IQR) ' . nf($iqr, 2) . ' คะแนน การกระจายจึง' . h($skew);
    $out .= card('การกระจายคะแนนรวม',
        chartBar($A['bins'], ['colors' => $binColors, 'h' => 300])
        . tbl(
            ['สถิติ', 'จำนวน (n)', 'ค่าเฉลี่ย', 'S.D.', 'ต่ำสุด', 'Q1', 'มัธยฐาน', 'Q3', 'สูงสุด'],
            [[ '<b>คะแนนรวม</b>', nf($st['n']), nf($st['mean'], 2), nf($st['sd'], 2), nf($st['min'], 2),
               nf($st['q1'], 2), nf($st['median'], 2), nf($st['q3'], 2), nf($st['max'], 2) ]],
            ['l','r','r','r','r','r','r','r','r']
        )
        . note('อ่านกราฟ: แท่งสีเทาคือช่วงคะแนนที่ <b>ไม่ผ่านเกณฑ์</b> (ต่ำกว่า 50) แท่งสีฟ้า/ส้ม/แดงคือกลุ่มที่ 1/2/3 ตามลำดับ '
             . 'จำนวนโรงเรียนที่กระจุกอยู่ช่วง ' . h((string) array_search(max($A['bins']), $A['bins'], true)) . ' คะแนนมากที่สุด ('
             . nf(max($A['bins'])) . ' แห่ง)'),
        $lead);

    // ---------- 2) ระดับความยุ่งยาก ----------
    $tierData = []; $tierCols = [];
    foreach ($A['tierDist'] as $t => $n) { $tierData[TYPE_LABEL[$t]] = $n; $tierCols[TYPE_LABEL[$t]] = TIER_COLOR[$t]; }
    $t3 = $A['tierDist'][3]; $t2 = $A['tierDist'][2]; $t1 = $A['tierDist'][1]; $t0 = $A['tierDist'][0];
    $out .= card('ระดับความยุ่งยากในการบริหาร',
        chartDonut($tierData, $tierCols, ['centerLabel' => 'ประเมินแล้ว (แห่ง)'])
        . note('โรงเรียนที่จัดอยู่ใน <b>กลุ่มที่ 3 — ยุ่งยากมากที่สุด</b> มี <b>' . nf($t3) . ' แห่ง</b> ('
             . pct((float) $t3, (float) max(1, $A['nScored'])) . ' ของที่ประเมิน) '
             . 'กลุ่มที่ 2 จำนวน ' . nf($t2) . ' แห่ง กลุ่มที่ 1 จำนวน ' . nf($t1) . ' แห่ง '
             . 'และยังมีอีก ' . nf($t0) . ' แห่งที่คะแนนไม่ถึงเกณฑ์ 50 คะแนน จึงไม่จัดเข้ากลุ่มโรงเรียนพื้นที่ลักษณะพิเศษในรอบนี้'),
        'ระดับความยุ่งยากกำหนดจากช่วงคะแนนรวม: 50–59 = กลุ่มที่ 1, 60–69 = กลุ่มที่ 2, 70 คะแนนขึ้นไป = กลุ่มที่ 3');

    // ---------- 3) คะแนนรายข้อ ----------
    $hb = []; $rowsItem = [];
    foreach ($A['items'] as $it) {
        if ((float) $it['max'] <= 0) continue;
        $hb[] = ['label' => 'ข้อ ' . $it['no'] . ' ' . $it['label'], 'value' => round((float) $it['pctOfMax'], 1),
                 'note' => '(' . nf($it['st']['mean'], 2) . '/' . nf($it['max']) . ')',
                 'color' => $it['pctOfMax'] >= 70 ? '#0d9488' : ($it['pctOfMax'] >= 40 ? '#f59e0b' : '#dc2626')];
    }
    foreach ($A['items'] as $it) {
        $share = $A['itemSumAll'] > 0 ? (float) $it['share'] / $A['itemSumAll'] * 100 : 0.0;
        $rowsItem[] = [
            'ข้อ ' . $it['no'],
            '<b>' . h($it['label']) . '</b><br><span class="dim">' . h($it['full']) . '</span>',
            nf($it['max']),
            nf($it['st']['mean'], 2),
            nf($it['st']['sd'], 2),
            nf($it['st']['median'], 2),
            nf($it['st']['max'], 2),
            $it['pctOfMax'] === null ? '–' : miniBar((float) $it['pctOfMax'], $color) . ' ' . nf($it['pctOfMax'], 1) . '%',
            nf($share, 1) . '%',
        ];
    }
    $best = null; $worst = null;
    foreach ($A['items'] as $it) {
        if ((float) $it['max'] <= 0 || $it['pctOfMax'] === null) continue;
        if ($best === null || $it['pctOfMax'] > $best['pctOfMax']) $best = $it;
        if ($worst === null || $it['pctOfMax'] < $worst['pctOfMax']) $worst = $it;
    }
    $out .= card('คะแนนรายข้อ — ได้คะแนนคิดเป็นร้อยละของคะแนนเต็มรายข้อ',
        chartHBar($hb, ['padL' => 260, 'dp' => 1, 'rowH' => 27])
        . tbl(['ข้อ', 'ตัวชี้วัด', 'เต็ม', 'เฉลี่ย', 'S.D.', 'มัธยฐาน', 'สูงสุด', 'ได้ร้อยละของเต็ม', 'สัดส่วนในคะแนนรวม'],
              $rowsItem, ['l','l','r','r','r','r','r','l','r'])
        . ($best && $worst ? note('ข้อที่โรงเรียนกลุ่มนี้ได้คะแนนใกล้เต็มมากที่สุดคือ <b>ข้อ ' . $best['no'] . ' ' . h($best['label'])
              . '</b> (เฉลี่ย ' . nf($best['st']['mean'], 2) . ' จาก ' . nf($best['max']) . ' คะแนน = ' . nf($best['pctOfMax'], 1) . '%) '
              . 'ส่วนข้อที่ได้คะแนนน้อยที่สุดเมื่อเทียบกับคะแนนเต็มคือ <b>ข้อ ' . $worst['no'] . ' ' . h($worst['label'])
              . '</b> (' . nf($worst['pctOfMax'], 1) . '%) — ข้อนี้แทบไม่ช่วยแยกความยุ่งยากของโรงเรียนในกลุ่มนี้') : ''),
        'คอลัมน์ "สัดส่วนในคะแนนรวม" บอกว่าคะแนนรวมของทั้งกลุ่มมาจากตัวชี้วัดข้อนั้นกี่เปอร์เซ็นต์ '
        . 'ตัวชี้วัดที่มีคะแนนเต็มสูง (เช่น ข้อ 1 ของพื้นที่สูง = 30 คะแนน, ข้อ 4 ของพื้นที่เกาะ = 20 คะแนน) จึงมีน้ำหนักมากกว่าโดยธรรมชาติ');

    // ---------- 4) การกระจายคำตอบรายข้อ ----------
    if ($A['optDist']) {
        $body = '';
        foreach ($A['optDist'] as $nn => $d) {
            $no = (int) $nn;
            $label = $items[$no][0] ?? ('ข้อ ' . $no);
            $rows = [];
            foreach ($d['labels'] as $id => $lb) {
                $n = $d['dist'][$id] ?? 0;
                $rows[] = ['label' => $lb, 'value' => $n, 'note' => '(' . pct((float) $n, (float) $d['answered']) . ')',
                           'color' => PAL[($id - 1) % count(PAL)]];
            }
            $body .= '<h4>ข้อ ' . $no . ' — ' . h($label) . ' <span class="dim">(ตอบ ' . nf($d['answered']) . ' แห่ง)</span></h4>'
                   . chartHBar($rows, ['padL' => 330, 'rowH' => 24]);
        }
        $out .= card('การกระจายคำตอบของตัวชี้วัดเชิงกลุ่ม', $body, '',
            $anchor . '-opt');
    }

    // ---------- 5) สาธารณูปโภค ----------
    if ($A['utils']) {
        $rows = []; $segColors = [];
        $maxSeg = 0;
        foreach ($A['utils'] as $name => $d) $maxSeg = max($maxSeg, count($d['labels']));
        for ($i = 1; $i <= $maxSeg; $i++) $segColors['ระดับ ' . $i] = PAL[($i - 1) % count(PAL)];
        foreach ($A['utils'] as $name => $d) {
            $parts = [];
            foreach ($d['labels'] as $id => $lb) $parts['ระดับ ' . $id] = $d['dist'][$id] ?? 0;
            $rows[] = ['label' => $name, 'parts' => $parts];
        }
        $legend = '<ul class="legend-list">';
        foreach ($A['utils'] as $name => $d) {
            $legend .= '<li><b>' . h($name) . '</b>: ';
            $bits = [];
            foreach ($d['labels'] as $id => $lb) $bits[] = 'ระดับ ' . $id . ' = ' . h($lb);
            $legend .= h('') . implode(' · ', array_map(fn($b) => '<span class="dim">' . $b . '</span>', $bits)) . '</li>';
        }
        $legend .= '</ul>';
        $out .= card('สภาพสาธารณูปโภคพื้นฐานของโรงเรียน',
            chartStacked($rows, $segColors, ['padL' => 200]) . $legend,
            'ระดับที่ตัวเลขน้อยกว่า หมายถึงสภาพที่ขาดแคลน/ยากลำบากกว่า (และได้คะแนนความยุ่งยากสูงกว่า) '
          . ($area === 1 ? 'ข้อ 7–10 ของพื้นที่สูงเลือกตอบได้มากกว่า 1 ข้อ รายงานนี้ใช้ "ระดับสูงสุดที่เลือก" ตามตรรกะการให้คะแนนของระบบ' : ''));
    }

    // ---------- 6) ภูมิภาค ----------
    if ($A['byRegion']) {
        $stackRows = []; $rowsR = [];
        foreach ($A['byRegion'] as $rg => $g) {
            $parts = [];
            foreach ([3, 2, 1, 0] as $t) $parts[TYPE_SHORT[$t]] = $g['tiers'][$t];
            $stackRows[] = ['label' => 'ภาค' . $rg, 'parts' => $parts];
            $rowsR[] = ['ภาค' . h($rg), nf($g['n']), nf($g['pass']), miniBar($g['passRate'], $color) . ' ' . nf($g['passRate'], 1) . '%',
                        nf($g['avg'], 2), nf($g['median'], 2), nf($g['tiers'][3]), nf($g['tiers'][2]), nf($g['tiers'][1]), nf($g['stu'])];
        }
        $segC = [TYPE_SHORT[3] => TIER_COLOR[3], TYPE_SHORT[2] => TIER_COLOR[2], TYPE_SHORT[1] => TIER_COLOR[1], TYPE_SHORT[0] => TIER_COLOR[0]];
        $topRegion = array_key_first($A['byRegion']);
        $out .= card('จำแนกตามภูมิภาค',
            chartStacked($stackRows, $segC, ['padL' => 170])
            . tbl(['ภูมิภาค', 'ประเมิน', 'ผ่านเกณฑ์', 'อัตราผ่าน', 'คะแนนเฉลี่ย', 'มัธยฐาน', 'กลุ่ม 3', 'กลุ่ม 2', 'กลุ่ม 1', 'นักเรียนรวม'],
                   $rowsR, ['l','r','r','l','r','r','r','r','r','r'])
            . note('ภูมิภาคที่มีโรงเรียนกลุ่มนี้มากที่สุดคือ <b>ภาค' . h((string) $topRegion) . '</b> จำนวน '
                 . nf($A['byRegion'][$topRegion]['n']) . ' แห่ง (อัตราผ่านเกณฑ์ '
                 . nf($A['byRegion'][$topRegion]['passRate'], 1) . '%)'));
    }

    // ---------- 7) จังหวัด ----------
    if ($A['byProvince']) {
        $topN = array_slice($A['byProvince'], 0, 20, true);
        $hbP = [];
        foreach ($topN as $p => $g) {
            $hbP[] = ['label' => $p, 'value' => $g['n'], 'note' => '(ผ่าน ' . nf($g['pass']) . ')', 'color' => $color];
        }
        $rowsP = [];
        foreach ($A['byProvince'] as $p => $g) {
            $rowsP[] = [h($p), h(regionOf($p)), nf($g['n']), nf($g['pass']),
                        miniBar($g['passRate'], $color) . ' ' . nf($g['passRate'], 1) . '%',
                        nf($g['avg'], 2), nf($g['tiers'][3]), nf($g['tiers'][2]), nf($g['tiers'][1]), nf($g['tiers'][0]), nf($g['stu'])];
        }
        $out .= card('จำแนกตามจังหวัด',
            '<h4>20 จังหวัดที่มีโรงเรียนกลุ่มนี้มากที่สุด</h4>'
            . chartHBar($hbP, ['padL' => 180, 'rowH' => 25])
            . '<h4>ตารางเต็ม (' . nf(count($A['byProvince'])) . ' จังหวัด)</h4>'
            . tbl(['จังหวัด', 'ภูมิภาค', 'ประเมิน', 'ผ่านเกณฑ์', 'อัตราผ่าน', 'คะแนนเฉลี่ย', 'กลุ่ม 3', 'กลุ่ม 2', 'กลุ่ม 1', 'ไม่ผ่าน', 'นักเรียน'],
                   $rowsP, ['l','l','r','r','l','r','r','r','r','r','r'], 'sortable'));
    }

    // ---------- 8) เขตพื้นที่การศึกษา ----------
    if ($A['bySao']) {
        $topS = array_slice($A['bySao'], 0, 20, true);
        $hbS = [];
        foreach ($topS as $s => $g) $hbS[] = ['label' => $s, 'value' => $g['n'], 'note' => '(ผ่าน ' . nf($g['pass']) . ')', 'color' => $color];
        $rowsS = [];
        foreach ($A['bySao'] as $s => $g) {
            $rowsS[] = [h($s), nf($g['n']), nf($g['pass']),
                        miniBar($g['passRate'], $color) . ' ' . nf($g['passRate'], 1) . '%',
                        nf($g['avg'], 2), nf($g['tiers'][3]), nf($g['tiers'][2]), nf($g['tiers'][1]), nf($g['tiers'][0])];
        }
        $out .= card('จำแนกตามสำนักงานเขตพื้นที่การศึกษา (สพท.)',
            '<h4>20 เขตพื้นที่ที่มีโรงเรียนกลุ่มนี้มากที่สุด</h4>'
            . chartHBar($hbS, ['padL' => 260, 'rowH' => 25])
            . '<h4>ตารางเต็ม (' . nf(count($A['bySao'])) . ' เขต)</h4>'
            . tbl(['สำนักงานเขตพื้นที่การศึกษา', 'ประเมิน', 'ผ่านเกณฑ์', 'อัตราผ่าน', 'คะแนนเฉลี่ย', 'กลุ่ม 3', 'กลุ่ม 2', 'กลุ่ม 1', 'ไม่ผ่าน'],
                   $rowsS, ['l','r','r','l','r','r','r','r','r'], 'sortable'));
    }

    // ---------- 9) ขนาดโรงเรียน ----------
    if ($A['bySize']) {
        $g1 = []; $rowsZ = [];
        foreach ($A['bySize'] as $sz => $g) {
            $g1[$sz] = ['จำนวนที่ประเมิน' => $g['n'], 'ผ่านเกณฑ์' => $g['pass']];
            $rowsZ[] = [h($sz), nf($g['n']), nf($g['pass']), miniBar($g['passRate'], $color) . ' ' . nf($g['passRate'], 1) . '%',
                        nf($g['avg'], 2), nf($g['median'], 2), nf($g['tiers'][3]), nf($g['tiers'][2]), nf($g['tiers'][1])];
        }
        $out .= card('จำแนกตามขนาดโรงเรียน (ตามจำนวนนักเรียน)',
            chartGrouped($g1, ['จำนวนที่ประเมิน' => '#94a3b8', 'ผ่านเกณฑ์' => $color], ['h' => 300])
            . tbl(['ขนาดโรงเรียน', 'ประเมิน', 'ผ่านเกณฑ์', 'อัตราผ่าน', 'คะแนนเฉลี่ย', 'มัธยฐาน', 'กลุ่ม 3', 'กลุ่ม 2', 'กลุ่ม 1'],
                   $rowsZ, ['l','r','r','l','r','r','r','r','r'])
            . note('เกณฑ์ขนาดโรงเรียนใช้ตามแนวปฏิบัติของ สพฐ.: เล็ก ≤ 120 คน, กลาง 121–300 คน, ใหญ่ 301–499 คน, ใหญ่พิเศษ 500 คนขึ้นไป'));
    }

    // ---------- 10) ปัจจัยเชิงบริบท ----------
    $boxRows = []; $rowsC = [];
    foreach ($A['context'] as $i => $c) {
        $s = $c['st'];
        if (($s['n'] ?? 0) === 0) continue;
        $boxRows[] = ['label' => $c['label'], 'st' => $s, 'color' => PAL[$i % count(PAL)]];
        $rowsC[] = [h($c['label']), nf($s['n']), nf($s['mean'], 2), nf($s['sd'], 2), nf($s['min'], 2),
                    nf($s['q1'], 2), nf($s['median'], 2), nf($s['q3'], 2), nf($s['max'], 2)];
    }
    $out .= card('ปัจจัยเชิงบริบทของโรงเรียน — สถิติเชิงพรรณนา',
        chartBox($boxRows)
        . tbl(['ปัจจัย', 'n', 'ค่าเฉลี่ย', 'S.D.', 'ต่ำสุด', 'Q1', 'มัธยฐาน', 'Q3', 'สูงสุด'], $rowsC,
              ['l','r','r','r','r','r','r','r','r'])
        . note('อ่านแผนภาพกล่อง: กล่องคือช่วง Q1–Q3 (โรงเรียนครึ่งกลาง 50%) เส้นทึบกลางกล่องคือมัธยฐาน '
             . 'วงกลมคือค่าเฉลี่ย และหนวดสองข้างคือค่าต่ำสุด-สูงสุด — <b>แต่ละแถวปรับสเกลของตัวเอง</b> จึงใช้ดูรูปทรงการกระจาย ไม่ใช่เทียบขนาดข้ามแถว'),
        'ตัวเลขทั้งหมดคำนวณจากโรงเรียนที่ประเมินแล้ว ' . nf($A['nScored']) . ' แห่ง (ค่าว่างถูกตัดออกรายตัวแปร)');

    // ---------- 11) สหสัมพันธ์ ----------
    $posTop = $A['corr'][0] ?? null;
    $negTop = end($A['corr']) ?: null;
    $rowsR2 = [];
    foreach ($A['corr'] as $c) {
        $dir = $c['r'] > 0.0001 ? 'แปรผันตาม (+)' : ($c['r'] < -0.0001 ? 'แปรผกผัน (−)' : 'ไม่มีทิศทาง');
        $rowsR2[] = [h($c['label']), nf($c['n']), '<b>' . number_format((float) $c['r'], 3) . '</b>', h($dir), h($c['strength'])];
    }
    $out .= card('ปัจจัยใดสัมพันธ์กับคะแนนรวมมากที่สุด (สหสัมพันธ์เพียร์สัน)',
        chartDiverging($A['corr'])
        . tbl(['ปัจจัย', 'n', 'สัมประสิทธิ์สหสัมพันธ์ (r)', 'ทิศทาง', 'ระดับความสัมพันธ์'], $rowsR2, ['l','r','r','l','l'])
        . ($posTop ? note('ปัจจัยที่สัมพันธ์กับคะแนนรวม<b>ในทางบวก</b>มากที่สุดคือ <b>' . h($posTop['label']) . '</b> (r = '
             . number_format((float) $posTop['r'], 3) . ', ระดับ' . h($posTop['strength']) . ') '
             . ($negTop && $negTop['r'] < 0 ? 'ส่วนปัจจัยที่สัมพันธ์<b>ในทางลบ</b>มากที่สุดคือ <b>' . h($negTop['label']) . '</b> (r = '
             . number_format((float) $negTop['r'], 3) . ')' : '')) : '')
        . note('ข้อควรระวัง: ค่า r บอก<b>ความสัมพันธ์</b> ไม่ใช่<b>สาเหตุ</b> และปัจจัยหลายตัวเป็นองค์ประกอบของสูตรคะแนนอยู่แล้ว '
             . 'จึงมีความสัมพันธ์สูงโดยโครงสร้าง', 'warn'),
        'ค่า r อยู่ระหว่าง −1 ถึง +1 : ค่าบวกหมายถึงปัจจัยยิ่งมากคะแนนยิ่งสูง ค่าลบหมายถึงปัจจัยยิ่งมากคะแนนยิ่งต่ำ');

    // ---------- 12) ค่าเฉลี่ยปัจจัยตามระดับความยุ่งยาก ----------
    $rowsG = [];
    foreach ($A['groupMeans'] as $g) {
        $lo = $g['means'][0]; $hi = $g['means'][3];
        $trend = ($lo !== null && $hi !== null)
            ? ($hi > $lo ? '<span class="up">▲ สูงขึ้นตามระดับ</span>' : ($hi < $lo ? '<span class="down">▼ ลดลงตามระดับ</span>' : '– คงที่'))
            : '–';
        $rowsG[] = [h($g['label']), nf($g['means'][0], 1), nf($g['means'][1], 1), nf($g['means'][2], 1), nf($g['means'][3], 1), $trend];
    }
    $out .= card('ค่าเฉลี่ยของปัจจัย จำแนกตามระดับความยุ่งยาก',
        tbl(['ปัจจัย', 'ไม่ผ่าน (n=' . nf($A['tierDist'][0]) . ')', 'กลุ่ม 1 (n=' . nf($A['tierDist'][1]) . ')',
             'กลุ่ม 2 (n=' . nf($A['tierDist'][2]) . ')', 'กลุ่ม 3 (n=' . nf($A['tierDist'][3]) . ')', 'แนวโน้ม'],
            $rowsG, ['l','r','r','r','r','l'])
        . note('ตารางนี้ตอบคำถามว่า "โรงเรียนที่ยุ่งยากมากที่สุดมีลักษณะต่างจากโรงเรียนที่ไม่ผ่านเกณฑ์อย่างไร" '
             . 'ปัจจัยที่มีลูกศร ▲ คือปัจจัยที่แยกกลุ่มได้ชัด — ยิ่งระดับสูงขึ้น ค่าเฉลี่ยยิ่งมาก'));

    // ---------- 13) แผนภาพการกระจาย ----------
    $sc = []; $xKey = $area === 1 ? 'highest' : 'citeria07';
    $xLbl = $area === 1 ? 'ความสูง ณ จุดสูงสุดของเส้นทาง (เมตร)' : 'เวลาเดินทาง (นาที)';
    foreach ($A['scoredRows'] as $x) {
        $v = num($x[$xKey] ?? null);
        if ($v === null || $v <= 0) continue;
        $sc[] = ['x' => $v, 'y' => (float) $x['_score'], 'tier' => $x['_tier'], 'label' => (string) $x['sc_names']];
    }
    if (count($sc) >= 5) {
        $out .= card('ความสัมพันธ์ระหว่างปัจจัยหลักกับคะแนนรวม (แผนภาพการกระจาย)',
            chartScatter($sc, ['xLabel' => $xLbl, 'yLabel' => 'คะแนนรวม', 'yMax' => 100, 'threshold' => 50, 'h' => 400])
            . note('จุดหนึ่งจุดคือโรงเรียนหนึ่งแห่ง (สีตามระดับความยุ่งยาก) เส้นประแนวนอนสีแดงคือเกณฑ์ผ่าน 50 คะแนน '
                 . 'เส้นประเฉียงคือเส้นแนวโน้มกำลังสองน้อยที่สุด — n = ' . nf(count($sc)) . ' แห่งที่มีข้อมูลปัจจัยนี้'));
    }

    // ---------- 14) ชาติพันธุ์ ----------
    if ($area === 1 && $A['ethnicTop']) {
        $hbE = [];
        foreach (array_slice($A['ethnicTop'], 0, 15) as $i => $e) {
            $hbE[] = ['label' => $e['ethnic'], 'value' => $e['students'], 'note' => '(' . nf($e['schools']) . ' โรงเรียน)',
                      'color' => PAL[$i % count(PAL)]];
        }
        $rowsE = [];
        foreach ($A['ethnicTop'] as $e) {
            $rowsE[] = [h($e['ethnic']), nf($e['students']), nf($e['schools']),
                        pct((float) $e['students'], (float) max(1, $A['ethnicSum']['students']))];
        }
        $out .= card('กลุ่มชาติพันธุ์ของนักเรียนในโรงเรียนพื้นที่สูง',
            chartHBar($hbE, ['padL' => 200, 'rowH' => 25])
            . tbl(['กลุ่มชาติพันธุ์', 'จำนวนนักเรียน (คน)', 'จำนวนโรงเรียน', 'สัดส่วน'], $rowsE, ['l','r','r','r'])
            . note('รวม <b>' . nf($A['ethnicSum']['groups']) . ' กลุ่มชาติพันธุ์</b> นักเรียน <b>'
                 . nf($A['ethnicSum']['students']) . ' คน</b> ใน <b>' . nf($A['ethnicSum']['schools'])
                 . ' โรงเรียน</b> — ข้อมูลนี้เป็นฐานคะแนนของตัวชี้วัดข้อ 11 (ร้อยละนักเรียนชาติพันธุ์) และข้อ 12 (จำนวนกลุ่มชาติพันธุ์)'));
    }

    // ---------- 15) อันดับโรงเรียน ----------
    $mk = function (array $list) use ($area) {
        $r = [];
        foreach ($list as $i => $x) {
            $r[] = [
                nf($i + 1),
                '<b>' . h((string) $x['sc_names']) . '</b><br><span class="dim">' . h((string) $x['sao_names']) . '</span>',
                h((string) $x['_prov']),
                '<b>' . nf($x['_score'], 2) . '</b>',
                '<span class="pill t' . $x['_tier'] . '">' . h(TYPE_SHORT[$x['_tier']]) . '</span>',
                nf($x['stu_sum'] ?? 0),
                $area === 1 ? nf($x['highest'] ?? null, 0) : nf($x['citeria07'] ?? null, 0),
            ];
        }
        return $r;
    };
    $head = ['#', 'โรงเรียน / เขตพื้นที่', 'จังหวัด', 'คะแนนรวม', 'ระดับ', 'นักเรียน',
             $area === 1 ? 'ความสูง (ม.)' : 'เวลาเดินทาง (นาที)'];
    $al = ['r','l','l','r','l','r','r'];
    $top15 = array_slice($A['ranked'], 0, 15);
    $bot15 = array_slice(array_reverse($A['ranked']), 0, 15);
    $out .= card('โรงเรียนที่คะแนนสูงสุดและต่ำสุด',
        '<h4>15 อันดับคะแนนสูงสุด</h4>' . tbl($head, $mk($top15), $al)
        . '<h4>15 อันดับคะแนนต่ำสุด</h4>' . tbl($head, $mk($bot15), $al));

    // ---------- 16) การรับรอง ----------
    $c = $A['cert'];
    $funnel = [
        'ประเมิน/คิดคะแนนแล้ว' => $A['nScored'],
        'ผ่านเกณฑ์ 50 คะแนน'   => $A['nPass'],
        'เขตพื้นที่รับรอง'       => $c['sao_certed'],
        'สพฐ. ประกาศผล'        => $c['spt_announced'],
    ];
    $out .= card('สถานะการรับรองผลการประเมิน',
        chartBar($funnel, ['color' => $color, 'h' => 280])
        . note('จากโรงเรียนที่ผ่านเกณฑ์ ' . nf($A['nPass']) . ' แห่ง มีการรับรองโดยสำนักงานเขตพื้นที่แล้ว '
             . nf($c['sao_certed']) . ' แห่ง (' . pct((float) $c['sao_certed'], (float) max(1, $A['nPass'])) . ') และ สพฐ. ประกาศผลแล้ว '
             . nf($c['spt_announced']) . ' แห่ง — คงเหลือรอการรับรองอีก ' . nf($c['pending']) . ' รายการ'));

    return $out;
}
