<?php
/**
 * page.php — ประกอบหน้า HTML ทั้งหน้า (โครง + CSS + JS + ส่วนภาพรวม/เปรียบเทียบ/ภาคผนวก)
 */

function renderPage(array $C): string
{
    $H = $C['H']; $I = $C['I'];
    $meta = $C['meta'];
    $title = 'รายงานผลการประเมินโรงเรียนพื้นที่ลักษณะพิเศษ';
    $sub   = $meta['scopeLabel'];

    $nav = [
        'exec'    => 'บทสรุปผู้บริหาร',
        'compare' => 'เปรียบเทียบสองกลุ่มพื้นที่',
        'confirm' => 'การรับรองการคงอยู่',
        'high'    => 'กลุ่มโรงเรียนพื้นที่สูง',
        'island'  => 'กลุ่มโรงเรียนพื้นที่เกาะ',
        'trend'   => 'แนวโน้มรายปีงบประมาณ',
        'quality' => 'คุณภาพข้อมูล',
        'appendix'=> 'ภาคผนวก: รายชื่อโรงเรียน',
        'method'  => 'ระเบียบวิธีและนิยาม',
    ];

    $html  = "<!doctype html>\n<html lang=\"th\">\n<head>\n<meta charset=\"utf-8\">\n";
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
    $html .= '<title>' . h($title) . ' — ' . h($sub) . "</title>\n";
    $html .= "<style>\n" . reportCss() . "\n</style>\n</head>\n<body>\n";

    // ---------------- ปก ----------------
    $html .= '<header class="cover">'
        . '<div class="cover-in">'
        . '<div class="eyebrow">' . h($meta['appName']) . '</div>'
        . '<h1>' . h($title) . '</h1>'
        . '<p class="cover-sub">กลุ่มโรงเรียนพื้นที่สูง และกลุ่มโรงเรียนพื้นที่เกาะ<br>' . h($sub) . '</p>'
        . '<div class="cover-meta">'
        . '<span>สร้างรายงานเมื่อ ' . h($meta['generatedAt']) . '</span>'
        . '<span>ฐานข้อมูล <code>' . h($meta['dbName']) . '</code> @ ' . h($meta['dbHost']) . '</span>'
        . '<span>แหล่งตั้งค่า <code>' . h($meta['cfgFile']) . '</code></span>'
        . '</div>'
        . '</div></header>';

    // ---------------- แถบนำทาง ----------------
    $html .= '<nav class="toc"><div class="toc-in">';
    foreach ($nav as $id => $label) $html .= '<a href="#' . $id . '">' . h($label) . '</a>';
    $html .= '<button type="button" class="btn-print" onclick="window.print()">พิมพ์ / บันทึก PDF</button>';
    $html .= '</div></nav>';

    $html .= '<main>';

    // ---------------- บทสรุปผู้บริหาร ----------------
    $html .= execSummary($C);

    // ---------------- เปรียบเทียบสองกลุ่ม ----------------
    $html .= compareSection($C);

    // ---------------- การรับรองการคงอยู่ ----------------
    $html .= confirmSection($C);

    // ---------------- สองกลุ่มพื้นที่ ----------------
    $html .= '<h2 class="h2" id="high"><span class="tag high">พื้นที่สูง</span> ผลการประเมินกลุ่มโรงเรียนพื้นที่สูง</h2>';
    $html .= $H['nScored'] > 0
        ? renderArea($H, 'high')
        : note('ไม่มีข้อมูลการประเมินของกลุ่มพื้นที่สูงในขอบเขตที่เลือก', 'warn');

    $html .= '<h2 class="h2" id="island"><span class="tag island">พื้นที่เกาะ</span> ผลการประเมินกลุ่มโรงเรียนพื้นที่เกาะ</h2>';
    $html .= $I['nScored'] > 0
        ? renderArea($I, 'island')
        : note('ไม่มีข้อมูลการประเมินของกลุ่มพื้นที่เกาะในขอบเขตที่เลือก', 'warn');

    // ---------------- แนวโน้ม ----------------
    $html .= trendSection($C);

    // ---------------- คุณภาพข้อมูล ----------------
    $html .= qualitySection($C);

    // ---------------- ภาคผนวก ----------------
    $html .= appendixSection($C);

    // ---------------- ระเบียบวิธี ----------------
    $html .= methodSection($C);

    $html .= '</main>';
    $html .= '<footer class="foot"><div>' . h($title) . ' · สร้างอัตโนมัติจากฐานข้อมูล ' . h($meta['dbName'])
           . ' เมื่อ ' . h($meta['generatedAt']) . '</div>'
           . '<div class="dim">ไฟล์นี้เป็นเอกสาร HTML แบบสมบูรณ์ในตัว (กราฟทั้งหมดเป็น SVG ฝังในไฟล์) เปิดดูแบบออฟไลน์ได้</div></footer>';
    $html .= "\n<script>\n" . reportJs() . "\n</script>\n</body>\n</html>";
    return $html;
}

// =====================================================================
//  บทสรุปผู้บริหาร
// =====================================================================
function execSummary(array $C): string
{
    $H = $C['H']; $I = $C['I'];
    $totScored = $H['nScored'] + $I['nScored'];
    $totPass   = $H['nPass'] + $I['nPass'];
    $totFail   = $H['nFail'] + $I['nFail'];
    $totStu    = $H['stuPassed'] + $I['stuPassed'];
    $t3 = $H['tierDist'][3] + $I['tierDist'][3];
    $t2 = $H['tierDist'][2] + $I['tierDist'][2];
    $t1 = $H['tierDist'][1] + $I['tierDist'][1];
    $provs = count(array_unique(array_merge(array_keys($H['byProvince']), array_keys($I['byProvince']))));
    $saos  = count(array_unique(array_merge(array_keys($H['bySao']),      array_keys($I['bySao']))));

    $k = '<div class="kpis wide">'
        . kpiCard('โรงเรียนที่ประเมินแล้วทั้งสิ้น', nf($totScored) . ' แห่ง', 'พื้นที่สูง ' . nf($H['nScored']) . ' · พื้นที่เกาะ ' . nf($I['nScored']))
        . kpiCard('ผ่านเกณฑ์พื้นที่ลักษณะพิเศษ', nf($totPass) . ' แห่ง', pct((float) $totPass, (float) max(1, $totScored)) . ' ของที่ประเมิน', 'good')
        . kpiCard('ไม่ผ่านเกณฑ์', nf($totFail) . ' แห่ง', pct((float) $totFail, (float) max(1, $totScored)) . ' ของที่ประเมิน', 'bad')
        . kpiCard('กลุ่มที่ 3 ยุ่งยากมากที่สุด', nf($t3) . ' แห่ง', 'กลุ่ม 2 = ' . nf($t2) . ' · กลุ่ม 1 = ' . nf($t1))
        . kpiCard('นักเรียนในโรงเรียนที่ผ่านเกณฑ์', nf($totStu) . ' คน', 'ครอบคลุม ' . nf($provs) . ' จังหวัด')
        . kpiCard('เขตพื้นที่การศึกษาที่เกี่ยวข้อง', nf($saos) . ' เขต', 'ทั้งสองกลุ่มพื้นที่รวมกัน')
        . '</div>';

    // เรื่องเล่าเชิงตัวเลข
    $topProvH = array_key_first($H['byProvince']) ?: '–';
    $topProvI = array_key_first($I['byProvince']) ?: '–';
    $bestCorrH = $H['corr'][0]['label'] ?? '–';
    $bestCorrI = $I['corr'][0]['label'] ?? '–';

    $narr = '<div class="narr">'
        . '<p>รายงานฉบับนี้สรุปผลการคัดกรองและประเมินโรงเรียนพื้นที่ลักษณะพิเศษ ตามแบบประเมิน 2 ชุด คือ '
        . '<b>พื้นที่ภูเขาสูงในถิ่นทุรกันดาร 16 ตัวชี้วัด</b> และ <b>พื้นที่เกาะ 15 ตัวชี้วัด</b> '
        . 'ทั้งสองชุดมีคะแนนเต็ม 100 คะแนน และใช้เกณฑ์ผ่านเดียวกันคือ <b>50 คะแนนขึ้นไป</b> '
        . 'โดยจำแนกความยุ่งยากในการบริหารเป็น 3 กลุ่มตามช่วงคะแนน (50–59, 60–69 และ 70 คะแนนขึ้นไป)</p>'

        . '<p>ในขอบเขตข้อมูล' . h($C['meta']['scopeLabel']) . ' มีโรงเรียนที่ผ่านการคิดคะแนนแล้วรวม <b>' . nf($totScored)
        . ' แห่ง</b> ในจำนวนนี้ผ่านเกณฑ์ <b>' . nf($totPass) . ' แห่ง</b> คิดเป็นร้อยละ '
        . number_format(pctf((float) $totPass, (float) max(1, $totScored)), 1) . ' ของโรงเรียนที่ประเมิน '
        . 'แยกเป็นกลุ่มพื้นที่สูง ' . nf($H['nPass']) . ' แห่ง (อัตราผ่าน ' . number_format($H['passRate'], 1) . '%) '
        . 'และกลุ่มพื้นที่เกาะ ' . nf($I['nPass']) . ' แห่ง (อัตราผ่าน ' . number_format($I['passRate'], 1) . '%)</p>'

        . '<p>คะแนนเฉลี่ยของกลุ่มพื้นที่สูงอยู่ที่ <b>' . nf($H['scoreStats']['mean'], 2) . '</b> คะแนน (S.D. '
        . nf($H['scoreStats']['sd'], 2) . ') ขณะที่กลุ่มพื้นที่เกาะอยู่ที่ <b>' . nf($I['scoreStats']['mean'], 2)
        . '</b> คะแนน (S.D. ' . nf($I['scoreStats']['sd'], 2) . ') '
        . ($H['scoreStats']['mean'] !== null && $I['scoreStats']['mean'] !== null
            ? 'กลุ่ม' . ($I['scoreStats']['mean'] > $H['scoreStats']['mean'] ? 'พื้นที่เกาะ' : 'พื้นที่สูง')
              . 'มีคะแนนเฉลี่ยสูงกว่าอีกกลุ่ม ' . nf(abs((float) $I['scoreStats']['mean'] - (float) $H['scoreStats']['mean']), 2) . ' คะแนน '
              . 'ซึ่งสะท้อนโครงสร้างน้ำหนักตัวชี้วัดที่ต่างกัน มิใช่การเทียบความยากลำบากโดยตรง' : '')
        . '</p>'

        . '<p>ในเชิงพื้นที่ จังหวัดที่มีโรงเรียนกลุ่มพื้นที่สูงมากที่สุดคือ <b>' . h((string) $topProvH) . '</b> '
        . 'ส่วนกลุ่มพื้นที่เกาะกระจุกตัวมากที่สุดที่จังหวัด <b>' . h((string) $topProvI) . '</b> '
        . 'ปัจจัยที่สัมพันธ์กับคะแนนรวมในทางบวกมากที่สุดคือ <b>' . h((string) $bestCorrH) . '</b> สำหรับพื้นที่สูง '
        . 'และ <b>' . h((string) $bestCorrI) . '</b> สำหรับพื้นที่เกาะ</p>'
        . '</div>';

    return '<h2 class="h2" id="exec">บทสรุปผู้บริหาร</h2>' . $k . card('ภาพรวมผลการประเมิน', $narr);
}

// =====================================================================
//  เปรียบเทียบสองกลุ่ม
// =====================================================================
function compareSection(array $C): string
{
    $H = $C['H']; $I = $C['I'];
    $s = '<h2 class="h2" id="compare">เปรียบเทียบกลุ่มพื้นที่สูงกับกลุ่มพื้นที่เกาะ</h2>';

    // สัดส่วนระดับความยุ่งยาก
    $rows = [];
    foreach ([1 => $H, 2 => $I] as $a => $A) {
        $parts = [];
        foreach ([3, 2, 1, 0] as $t) $parts[TYPE_SHORT[$t]] = $A['tierDist'][$t];
        $rows[] = ['label' => areaName($a), 'parts' => $parts];
    }
    $segC = [TYPE_SHORT[3] => TIER_COLOR[3], TYPE_SHORT[2] => TIER_COLOR[2], TYPE_SHORT[1] => TIER_COLOR[1], TYPE_SHORT[0] => TIER_COLOR[0]];

    // ตารางเทียบ
    $cmp = [
        ['จำนวนโรงเรียนในรอบประเมิน', nf($H['nAll']), nf($I['nAll'])],
        ['ประเมิน/คิดคะแนนแล้ว', nf($H['nScored']), nf($I['nScored'])],
        ['ผ่านเกณฑ์ (≥ 50)', nf($H['nPass']), nf($I['nPass'])],
        ['อัตราผ่านเกณฑ์', number_format($H['passRate'], 1) . '%', number_format($I['passRate'], 1) . '%'],
        ['คะแนนเฉลี่ย', nf($H['scoreStats']['mean'], 2), nf($I['scoreStats']['mean'], 2)],
        ['ส่วนเบี่ยงเบนมาตรฐาน', nf($H['scoreStats']['sd'], 2), nf($I['scoreStats']['sd'], 2)],
        ['คะแนนมัธยฐาน', nf($H['scoreStats']['median'], 2), nf($I['scoreStats']['median'], 2)],
        ['คะแนนต่ำสุด – สูงสุด', nf($H['scoreStats']['min'], 2) . ' – ' . nf($H['scoreStats']['max'], 2),
                                  nf($I['scoreStats']['min'], 2) . ' – ' . nf($I['scoreStats']['max'], 2)],
        ['กลุ่มที่ 3 ยุ่งยากมากที่สุด', nf($H['tierDist'][3]), nf($I['tierDist'][3])],
        ['กลุ่มที่ 2 ยุ่งยากมาก', nf($H['tierDist'][2]), nf($I['tierDist'][2])],
        ['กลุ่มที่ 1 ยุ่งยาก', nf($H['tierDist'][1]), nf($I['tierDist'][1])],
        ['จำนวนจังหวัดที่เกี่ยวข้อง', nf(count($H['byProvince'])), nf(count($I['byProvince']))],
        ['จำนวนเขตพื้นที่การศึกษา', nf(count($H['bySao'])), nf(count($I['bySao']))],
        ['จำนวนนักเรียนรวม (ที่ประเมิน)', nf($H['stuAll']), nf($I['stuAll'])],
        ['จำนวนตัวชี้วัด', '16 ข้อ', '15 ข้อ (คิดคะแนน 14 ข้อ)'],
        ['เขตพื้นที่รับรองแล้ว', nf($H['cert']['sao_certed']), nf($I['cert']['sao_certed'])],
        ['สพฐ. ประกาศผลแล้ว', nf($H['cert']['spt_announced']), nf($I['cert']['spt_announced'])],
    ];

    // การกระจายคะแนนเทียบกัน (ร้อยละ)
    $binsPct = [];
    foreach ($H['bins'] as $k => $_) {
        $binsPct[$k] = [
            'พื้นที่สูง' => round(pctf((float) $H['bins'][$k], (float) max(1, $H['nScored'])), 1),
            'พื้นที่เกาะ' => round(pctf((float) ($I['bins'][$k] ?? 0), (float) max(1, $I['nScored'])), 1),
        ];
    }

    $s .= card('สัดส่วนระดับความยุ่งยากของแต่ละกลุ่ม', chartStacked($rows, $segC, ['padL' => 230]));
    $s .= card('การกระจายคะแนนเทียบกัน (ร้อยละของโรงเรียนในกลุ่มตนเอง)',
        chartGrouped($binsPct, ['พื้นที่สูง' => AREA_COLOR[1], 'พื้นที่เกาะ' => AREA_COLOR[2]], ['h' => 320, 'dp' => 1])
        . note('ใช้ร้อยละแทนจำนวน เพราะขนาดของสองกลุ่มต่างกันมาก (' . nf($H['nScored']) . ' เทียบกับ ' . nf($I['nScored']) . ' แห่ง)'));
    $s .= card('ตารางเปรียบเทียบตัวเลขสำคัญ',
        tbl(['รายการ', 'กลุ่มพื้นที่สูง', 'กลุ่มพื้นที่เกาะ'], $cmp, ['l','r','r']));
    return $s;
}

// =====================================================================
//  การรับรองการคงอยู่ (school_confirm)
// =====================================================================
function confirmSection(array $C): string
{
    $cf = $C['confirm'];
    $year = $C['meta']['confirmYear'];
    $s = '<h2 class="h2" id="confirm">การรับรองการคงอยู่ของโรงเรียนพื้นที่ลักษณะพิเศษ ปีงบประมาณ ' . h((string) $year) . '</h2>';
    if (!$cf || ($cf['total'] ?? 0) === 0) {
        return $s . card('สถานะการรับรองการคงอยู่',
            note('ยังไม่มีข้อมูลบัญชีรายชื่อ (school_confirm) ของปีงบประมาณ ' . h((string) $year) . ' ในฐานข้อมูลนี้', 'warn'));
    }

    $k = '<div class="kpis wide">'
        . kpiCard('บัญชีรายชื่อตั้งต้น', nf($cf['total']) . ' แห่ง', 'พื้นที่สูง ' . nf($cf['high']) . ' · พื้นที่เกาะ ' . nf($cf['island']))
        . kpiCard('ยังคงอยู่จริง', nf($cf['active']) . ' แห่ง', pct((float) $cf['active'], (float) max(1, $cf['total'])) . ' ของบัญชี', 'good')
        . kpiCard('ยุบ / รวม / เลิกสถานศึกษา', nf($cf['closed']) . ' แห่ง', 'ตัดออกจากรอบประเมิน', 'bad')
        . kpiCard('ขาดคุณสมบัติ', nf($cf['disqualified']) . ' แห่ง', 'ไม่เข้าเกณฑ์พื้นที่พิเศษ', 'bad')
        . kpiCard('โรงเรียนยืนยันข้อมูลแล้ว', nf($cf['school_confirmed']) . ' แห่ง', pct((float) $cf['school_confirmed'], (float) max(1, $cf['total'])))
        . kpiCard('เขตพื้นที่รับรองแล้ว', nf($cf['sao_ok']) . ' แห่ง', 'สพฐ. อนุมัติแล้ว ' . nf($cf['spt_ok']) . ' แห่ง')
        . '</div>';

    $funnel = [
        'บัญชีรายชื่อตั้งต้น'   => $cf['total'],
        'ยังคงอยู่จริง'         => $cf['active'],
        'โรงเรียนยืนยัน'        => $cf['school_confirmed'],
        'ส่งให้เขตพิจารณา'      => $cf['submitted'],
        'เขตรับรอง'            => $cf['sao_ok'],
        'สพฐ. อนุมัติ'          => $cf['spt_ok'],
    ];
    $drop = $cf['total'] - $cf['active'];
    $body = chartBar($funnel, ['color' => '#7c3aed', 'h' => 300, 'rotate' => true])
        . note('กระบวนการรับรองการคงอยู่ไล่จากซ้ายไปขวา: จากบัญชีรายชื่อตั้งต้น ' . nf($cf['total']) . ' แห่ง '
             . 'มีที่ถูกตัดออกเพราะยุบ/รวม/เลิก หรือขาดคุณสมบัติรวม ' . nf($drop) . ' แห่ง '
             . 'เหลือเป้าหมายที่ยังคงอยู่จริง ' . nf($cf['active']) . ' แห่ง — ปัจจุบันเดินมาถึงขั้น "เขตรับรอง" แล้ว '
             . nf($cf['sao_ok']) . ' แห่ง คิดเป็น ' . pct((float) $cf['sao_ok'], (float) max(1, $cf['active'])) . ' ของเป้าหมาย');

    // จังหวัด
    $topP = array_slice($cf['byProvince'], 0, 20, true);
    $hb = [];
    foreach ($topP as $p => $n) $hb[] = ['label' => $p, 'value' => $n, 'color' => '#7c3aed'];

    return $s . $k
        . card('ขั้นตอนการรับรองการคงอยู่', $body)
        . card('บัญชีรายชื่อจำแนกตามจังหวัด (20 อันดับแรก)', chartHBar($hb, ['padL' => 180, 'rowH' => 25])
            . note('บัญชีนี้ครอบคลุม ' . nf(count($cf['byProvince'])) . ' จังหวัด รวมนักเรียน ' . nf($cf['std_total'])
                 . ' คน และครู/บุคลากร ' . nf($cf['tch_total']) . ' คน'));
}

// =====================================================================
//  แนวโน้มรายปี
// =====================================================================
function trendSection(array $C): string
{
    $tH = $C['trendH']; $tI = $C['trendI'];
    $years = array_map('strval', array_values(array_unique(array_merge(array_keys($tH), array_keys($tI)))));
    sort($years, SORT_NUMERIC);
    $s = '<h2 class="h2" id="trend">แนวโน้มรายปีงบประมาณ</h2>';
    if (!$years) return $s . card('แนวโน้ม', note('ไม่มีข้อมูลรายปี', 'warn'));

    $sCount = ['พื้นที่สูง' => [], 'พื้นที่เกาะ' => []];
    $sAvg   = ['พื้นที่สูง' => [], 'พื้นที่เกาะ' => []];
    $sPass  = ['พื้นที่สูง' => [], 'พื้นที่เกาะ' => []];
    foreach ($years as $y) {
        $yi = (int) $y;
        $sCount['พื้นที่สูง'][$y]  = $tH[$yi]['scored'] ?? null;
        $sCount['พื้นที่เกาะ'][$y] = $tI[$yi]['scored'] ?? null;
        $sAvg['พื้นที่สูง'][$y]    = $tH[$yi]['avg'] ?? null;
        $sAvg['พื้นที่เกาะ'][$y]   = $tI[$yi]['avg'] ?? null;
        $sPass['พื้นที่สูง'][$y]   = $tH[$yi]['passRate'] ?? null;
        $sPass['พื้นที่เกาะ'][$y]  = $tI[$yi]['passRate'] ?? null;
    }
    $col = ['พื้นที่สูง' => AREA_COLOR[1], 'พื้นที่เกาะ' => AREA_COLOR[2]];

    $rows = [];
    foreach ($years as $y) {
        $yi = (int) $y;
        $h1 = $tH[$yi] ?? null; $i1 = $tI[$yi] ?? null;
        $rows[] = [
            h((string) $y),
            $h1 ? nf($h1['scored']) : '–', $h1 ? nf($h1['pass']) : '–',
            $h1 && $h1['passRate'] !== null ? nf($h1['passRate'], 1) . '%' : '–',
            $h1 && $h1['avg'] !== null ? nf($h1['avg'], 2) : '–',
            $i1 ? nf($i1['scored']) : '–', $i1 ? nf($i1['pass']) : '–',
            $i1 && $i1['passRate'] !== null ? nf($i1['passRate'], 1) . '%' : '–',
            $i1 && $i1['avg'] !== null ? nf($i1['avg'], 2) : '–',
        ];
    }

    return $s
        . card('จำนวนโรงเรียนที่ประเมินในแต่ละปีงบประมาณ',
            chartLine($sCount, $years, ['colors' => $col, 'h' => 300])
            . note('ปีที่มีจำนวนสูงผิดปกติคือรอบคัดกรองใหญ่ (ประเมินใหม่ทั้งบัญชี) ส่วนปีที่มีจำนวนน้อยคือการประเมินเพิ่มเติม/รายกรณี'))
        . card('คะแนนเฉลี่ยรายปี',
            chartLine($sAvg, $years, ['colors' => $col, 'h' => 300, 'dp' => 2]))
        . card('อัตราผ่านเกณฑ์รายปี (ร้อยละ)',
            chartLine($sPass, $years, ['colors' => $col, 'h' => 300, 'dp' => 1]))
        . card('ตารางสรุปรายปี',
            tbl(['ปีงบประมาณ', 'สูง: ประเมิน', 'สูง: ผ่าน', 'สูง: อัตราผ่าน', 'สูง: คะแนนเฉลี่ย',
                 'เกาะ: ประเมิน', 'เกาะ: ผ่าน', 'เกาะ: อัตราผ่าน', 'เกาะ: คะแนนเฉลี่ย'],
                $rows, ['l','r','r','r','r','r','r','r','r'])
            . note('ตารางนี้นับ<b>ทุกปีงบประมาณที่มีข้อมูลในฐานข้อมูล</b> ไม่ถูกจำกัดด้วยขอบเขตปีของรายงานส่วนอื่น'));
}

// =====================================================================
//  คุณภาพข้อมูล
// =====================================================================
function qualitySection(array $C): string
{
    $H = $C['H']; $I = $C['I'];
    $keys = array_keys($H['quality']);
    $rows = [];
    foreach ($keys as $k) {
        $rows[] = [h($k), nf($H['quality'][$k] ?? 0), nf($I['quality'][$k] ?? 0), nf(($H['quality'][$k] ?? 0) + ($I['quality'][$k] ?? 0))];
    }
    $missH = []; $missI = [];
    foreach ($H['context'] as $c) $missH[$c['label']] = $H['nScored'] - ($c['st']['n'] ?? 0);
    foreach ($I['context'] as $c) $missI[$c['label']] = $I['nScored'] - ($c['st']['n'] ?? 0);

    $mrows = [];
    foreach ($missH as $lb => $n) $mrows[] = ['กลุ่มพื้นที่สูง', h($lb), nf($n), pct((float) $n, (float) max(1, $H['nScored']))];
    foreach ($missI as $lb => $n) $mrows[] = ['กลุ่มพื้นที่เกาะ', h($lb), nf($n), pct((float) $n, (float) max(1, $I['nScored']))];

    return '<h2 class="h2" id="quality">คุณภาพและความครบถ้วนของข้อมูล</h2>'
        . card('ความครบถ้วนของระเบียนหลัก',
            tbl(['รายการตรวจสอบ', 'กลุ่มพื้นที่สูง', 'กลุ่มพื้นที่เกาะ', 'รวม'], $rows, ['l','r','r','r'])
            . note('ระเบียนที่ "ยังไม่มีคะแนน" คือโรงเรียนที่อยู่ในรอบแล้วแต่ยังกรอกแบบประเมินไม่ครบ '
                 . 'จึงไม่ถูกนับในสถิติผลการประเมินทุกส่วนของรายงานนี้', 'warn'))
        . card('ค่าว่างรายตัวแปร (เฉพาะระเบียนที่ประเมินแล้ว)',
            tbl(['กลุ่มพื้นที่', 'ตัวแปร', 'จำนวนที่ไม่มีค่า', 'ร้อยละ'], $mrows, ['l','l','r','r'], 'sortable')
            . note('ตัวแปรที่มีค่าว่างมาก จะทำให้สถิติเชิงพรรณนาและค่าสหสัมพันธ์ของตัวแปรนั้นคำนวณจากกลุ่มตัวอย่างที่เล็กลง (ดูคอลัมน์ n ประกอบเสมอ)'));
}

// =====================================================================
//  ภาคผนวก — ตารางรายโรงเรียน
// =====================================================================
function appendixSection(array $C): string
{
    $rows = [];
    foreach ([1 => $C['H'], 2 => $C['I']] as $a => $A) {
        foreach ($A['ranked'] as $x) {
            $rows[] = [
                'a'  => areaShort($a),
                'id' => (string) $x['sc_id'],
                'nm' => (string) $x['sc_names'],
                'pv' => (string) $x['_prov'],
                'rg' => (string) $x['_region'],
                'sa' => (string) $x['sao_names'],
                'yr' => (string) $x['acadyears'],
                'sc' => (float) $x['_score'],
                'tr' => (int) $x['_tier'],
                'st' => (int) ($x['stu_sum'] ?? 0),
            ];
        }
    }
    usort($rows, fn($p, $q) => $q['sc'] <=> $p['sc']);

    $body = '<div class="filters">'
        . '<input type="search" id="q" placeholder="ค้นหาชื่อโรงเรียน / จังหวัด / เขตพื้นที่ / รหัส…" oninput="filterRows()">'
        . '<select id="fArea" onchange="filterRows()"><option value="">ทุกกลุ่มพื้นที่</option>'
        . '<option value="พื้นที่สูง">พื้นที่สูง</option><option value="พื้นที่เกาะ">พื้นที่เกาะ</option></select>'
        . '<select id="fTier" onchange="filterRows()"><option value="">ทุกระดับ</option>'
        . '<option value="3">กลุ่ม 3 ยุ่งยากมากที่สุด</option><option value="2">กลุ่ม 2</option>'
        . '<option value="1">กลุ่ม 1</option><option value="0">ไม่ผ่านเกณฑ์</option></select>'
        . '<span class="dim" id="cnt"></span></div>';

    $trs = '';
    foreach ($rows as $r) {
        $trs .= '<tr data-a="' . h($r['a']) . '" data-t="' . $r['tr'] . '" data-s="'
             . h(mb_strtolower($r['nm'] . ' ' . $r['pv'] . ' ' . $r['sa'] . ' ' . $r['id'])) . '">'
             . '<td><span class="tag ' . ($r['a'] === 'พื้นที่สูง' ? 'high' : 'island') . '">' . h($r['a']) . '</span></td>'
             . '<td class="mono">' . h($r['id']) . '</td>'
             . '<td>' . h($r['nm']) . '</td>'
             . '<td>' . h($r['pv']) . '</td>'
             . '<td>' . h($r['rg']) . '</td>'
             . '<td>' . h($r['sa']) . '</td>'
             . '<td class="a-r">' . h($r['yr']) . '</td>'
             . '<td class="a-r"><b>' . nf($r['sc'], 2) . '</b></td>'
             . '<td><span class="pill t' . $r['tr'] . '">' . h(TYPE_SHORT[$r['tr']]) . '</span></td>'
             . '<td class="a-r">' . nf($r['st']) . '</td></tr>';
    }
    $body .= '<div class="tw tall"><table class="sortable" id="tblAll"><thead><tr>'
        . '<th>กลุ่มพื้นที่</th><th>รหัสโรงเรียน</th><th>ชื่อโรงเรียน</th><th>จังหวัด</th><th>ภูมิภาค</th>'
        . '<th>สำนักงานเขตพื้นที่การศึกษา</th><th class="a-r">ปีงบฯ</th><th class="a-r">คะแนนรวม</th>'
        . '<th>ระดับ</th><th class="a-r">นักเรียน</th></tr></thead><tbody>' . $trs . '</tbody></table></div>';

    return '<h2 class="h2" id="appendix">ภาคผนวก — รายชื่อโรงเรียนและคะแนนรายแห่ง</h2>'
        . card('ตารางรายโรงเรียน (' . nf(count($rows)) . ' รายการ)', $body,
            'พิมพ์คำค้นเพื่อกรอง หรือคลิกหัวคอลัมน์เพื่อเรียงลำดับ — ตารางนี้แสดงเฉพาะโรงเรียนที่คิดคะแนนแล้ว');
}

// =====================================================================
//  ระเบียบวิธี
// =====================================================================
function methodSection(array $C): string
{
    $m = $C['meta'];
    $body = '<h4>แหล่งข้อมูล</h4><ul>'
        . '<li>ตาราง <code>highland_eval</code> (แบบประเมินพื้นที่สูง 16 ข้อ) และ <code>island_eval</code> (พื้นที่เกาะ 15 ข้อ)</li>'
        . '<li>ตาราง <code>highland_eval_hilltrib</code> + <code>hilltrib</code> สำหรับกลุ่มชาติพันธุ์</li>'
        . '<li>ตาราง <code>school_confirm</code> สำหรับการรับรองการคงอยู่ ปีงบประมาณ ' . h((string) $m['confirmYear']) . '</li>'
        . '<li>ฐานข้อมูล <code>' . h($m['dbName']) . '</code> ที่ <code>' . h($m['dbHost']) . '</code> (ตั้งค่าจาก <code>' . h($m['cfgFile']) . '</code>)</li>'
        . '</ul>'
        . '<h4>นิยามที่ใช้</h4><ul>'
        . '<li><b>ประเมินแล้ว</b> = ระเบียนที่มี <code>sum_score &gt; 0</code> เท่านั้น (ระเบียนที่ยังไม่คิดคะแนนไม่ถูกนับในสถิติทุกส่วน)</li>'
        . '<li><b>ผ่านเกณฑ์</b> = คะแนนรวมตั้งแต่ 50 คะแนนขึ้นไป</li>'
        . '<li><b>ระดับความยุ่งยาก</b>: 50–59 = กลุ่มที่ 1, 60–69 = กลุ่มที่ 2, 70 คะแนนขึ้นไป = กลุ่มที่ 3</li>'
        . '<li><b>ขนาดโรงเรียน</b>: เล็ก ≤ 120 คน, กลาง 121–300 คน, ใหญ่ 301–499 คน, ใหญ่พิเศษ ≥ 500 คน</li>'
        . '<li><b>ภูมิภาค</b> จำแนกจากจังหวัดตามการแบ่ง 6 ภาคของราชบัณฑิตยสภา</li>'
        . '<li><b>ร้อยละนักเรียนยากจน / ชาติพันธุ์</b> คำนวณจากจำนวนนักเรียนรวมของโรงเรียนนั้น (เพดาน 100%)</li>'
        . '</ul>'
        . '<h4>วิธีทางสถิติ</h4><ul>'
        . '<li>ส่วนเบี่ยงเบนมาตรฐานใช้สูตรกลุ่มตัวอย่าง (ddof = 1)</li>'
        . '<li>ควอไทล์คำนวณแบบ linear interpolation (ตรงกับ pandas/NumPy ค่าปริยาย)</li>'
        . '<li>สหสัมพันธ์ใช้สัมประสิทธิ์เพียร์สัน คำนวณเฉพาะคู่ข้อมูลที่ไม่ว่างทั้งสองด้าน</li>'
        . '<li>เส้นแนวโน้มในแผนภาพการกระจายใช้วิธีกำลังสองน้อยที่สุด</li>'
        . '</ul>'
        . '<h4>ข้อจำกัด</h4><ul>'
        . '<li>ตัวชี้วัดหลายตัวเป็นองค์ประกอบของสูตรคะแนนโดยตรง ค่าสหสัมพันธ์กับคะแนนรวมจึงสูงโดยโครงสร้าง ไม่ควรตีความเป็นความสัมพันธ์เชิงสาเหตุ</li>'
        . '<li>ข้อมูลบางระเบียนไม่ครบถ้วน (ดูหัวข้อคุณภาพข้อมูล) สถิติรายตัวแปรจึงมี n ไม่เท่ากัน</li>'
        . '<li>รายงานสะท้อนสถานะฐานข้อมูล ณ เวลาที่สร้างไฟล์เท่านั้น (' . h($m['generatedAt']) . ')</li>'
        . '</ul>';
    return '<h2 class="h2" id="method">ระเบียบวิธีและนิยาม</h2>' . card('หมายเหตุประกอบรายงาน', $body);
}

// =====================================================================
//  CSS / JS
// =====================================================================
function reportCss(): string
{
    return <<<'CSS'
*,*::before,*::after{box-sizing:border-box}
:root{
  --bg:#f1f5f9; --card:#ffffff; --ink:#0f172a; --muted:#64748b; --dim:#94a3b8;
  --grid:#e2e8f0; --axis:#94a3b8; --line:#e2e8f0; --accent:#0f766e;
  --good:#0d9488; --bad:#dc2626; --warn:#b45309;
  --shadow:0 1px 2px rgba(15,23,42,.06),0 8px 24px -12px rgba(15,23,42,.18);
}
@media (prefers-color-scheme:dark){
  :root{--bg:#0b1220;--card:#111a2b;--ink:#e6edf7;--muted:#93a4bd;--dim:#64748b;
        --grid:#1e2a3f;--axis:#475569;--line:#1e2a3f;--shadow:0 1px 2px rgba(0,0,0,.4),0 10px 30px -14px rgba(0,0,0,.7)}
}
html{scroll-behavior:smooth;scroll-padding-top:70px}
body{margin:0;background:var(--bg);color:var(--ink);
  font-family:"Sarabun","TH Sarabun New","Noto Sans Thai","Leelawadee UI","Segoe UI",system-ui,sans-serif;
  font-size:16px;line-height:1.65}
main{max-width:1180px;margin:0 auto;padding:0 20px 80px}
code{font-family:ui-monospace,"Cascadia Mono",Consolas,monospace;font-size:.88em;
  background:rgba(100,116,139,.14);padding:1px 6px;border-radius:5px}
.mono{font-family:ui-monospace,Consolas,monospace;font-size:.9em;color:var(--muted)}
.dim{color:var(--dim);font-size:.88em;font-weight:400}

/* ---- ปก ---- */
.cover{background:linear-gradient(135deg,#0f766e 0%,#115e59 45%,#1e3a8a 100%);color:#fff;padding:56px 20px 48px}
.cover-in{max-width:1180px;margin:0 auto}
.eyebrow{text-transform:none;letter-spacing:.06em;font-size:.95rem;opacity:.82;margin-bottom:10px}
.cover h1{margin:0 0 14px;font-size:2.35rem;line-height:1.25;font-weight:700;letter-spacing:-.01em}
.cover-sub{margin:0 0 22px;font-size:1.12rem;opacity:.94;line-height:1.6}
.cover-meta{display:flex;flex-wrap:wrap;gap:10px 22px;font-size:.9rem;opacity:.86}
.cover-meta code{background:rgba(255,255,255,.16);color:#fff}

/* ---- นำทาง ---- */
.toc{position:sticky;top:0;z-index:20;background:var(--card);border-bottom:1px solid var(--line);
  box-shadow:0 1px 12px -6px rgba(15,23,42,.3)}
.toc-in{max-width:1180px;margin:0 auto;padding:8px 20px;display:flex;gap:4px;align-items:center;
  overflow-x:auto;scrollbar-width:thin}
.toc a{color:var(--muted);text-decoration:none;font-size:.9rem;padding:7px 11px;border-radius:8px;white-space:nowrap}
.toc a:hover{background:rgba(100,116,139,.13);color:var(--ink)}
.btn-print{margin-left:auto;flex:none;background:var(--accent);color:#fff;border:0;border-radius:8px;
  padding:8px 15px;font:inherit;font-size:.88rem;cursor:pointer;white-space:nowrap}
.btn-print:hover{filter:brightness(1.08)}

/* ---- หัวข้อ ---- */
.h2{margin:44px 0 16px;font-size:1.5rem;font-weight:700;letter-spacing:-.01em;
  padding-bottom:10px;border-bottom:2px solid var(--line);display:flex;align-items:center;gap:10px}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:20px 22px;
  margin:0 0 18px;box-shadow:var(--shadow);break-inside:avoid}
.card h3{margin:0 0 6px;font-size:1.14rem;font-weight:700}
.card h4{margin:22px 0 8px;font-size:1rem;font-weight:700;color:var(--ink)}
.lead{margin:0 0 14px;color:var(--muted);font-size:.94rem;line-height:1.6}
.narr p{margin:0 0 12px;font-size:1rem;line-height:1.75}
.narr p:last-child{margin-bottom:0}
.note{margin:14px 0 0;padding:11px 14px;border-radius:10px;font-size:.92rem;line-height:1.6;
  background:rgba(37,99,235,.07);border-left:3px solid #2563eb;color:var(--ink)}
.note.warn{background:rgba(180,83,9,.09);border-left-color:#b45309}
.legend-list{margin:14px 0 0;padding-left:18px;font-size:.86rem;line-height:1.7;color:var(--muted)}
.legend-list li{margin-bottom:4px}

/* ---- KPI ---- */
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(215px,1fr));gap:12px;margin:0 0 18px}
.kpi{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px 18px;box-shadow:var(--shadow)}
.kpi-v{font-size:1.62rem;font-weight:700;line-height:1.25;letter-spacing:-.01em}
.kpi-l{font-size:.9rem;color:var(--muted);margin-top:3px}
.kpi-s{font-size:.8rem;color:var(--dim);margin-top:5px}
.kpi.good .kpi-v{color:var(--good)} .kpi.bad .kpi-v{color:var(--bad)}

/* ---- ตาราง ---- */
.tw{overflow-x:auto;margin-top:14px;border:1px solid var(--line);border-radius:10px}
.tw.tall{max-height:640px;overflow-y:auto}
table{border-collapse:collapse;width:100%;font-size:.9rem;background:var(--card)}
thead th{position:sticky;top:0;background:var(--card);text-align:left;font-weight:700;font-size:.86rem;
  padding:10px 12px;border-bottom:2px solid var(--line);white-space:nowrap;z-index:1}
td{padding:9px 12px;border-bottom:1px solid var(--line);vertical-align:top}
tbody tr:last-child td{border-bottom:0}
tbody tr:hover{background:rgba(100,116,139,.07)}
.a-r{text-align:right} .a-l{text-align:left} .a-c{text-align:center}
table.sortable thead th{cursor:pointer;user-select:none}
table.sortable thead th:hover{color:var(--accent)}

/* ---- ชิ้นเล็ก ---- */
.mb{display:inline-block;width:64px;height:8px;border-radius:4px;background:rgba(100,116,139,.22);
  overflow:hidden;vertical-align:middle;margin-right:6px}
.mb-f{display:block;height:100%;border-radius:4px}
.pill{display:inline-block;padding:2px 9px;border-radius:999px;font-size:.78rem;font-weight:600;color:#fff;white-space:nowrap}
.pill.t0{background:#94a3b8} .pill.t1{background:#38bdf8} .pill.t2{background:#f59e0b} .pill.t3{background:#dc2626}
.tag{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.8rem;font-weight:600;color:#fff;white-space:nowrap}
.tag.high{background:#0d9488} .tag.island{background:#2563eb}
.up{color:var(--good);font-weight:600} .down{color:var(--bad);font-weight:600}
.chart{display:block;margin:6px 0 2px;max-width:100%;height:auto}

/* ---- ตัวกรอง ---- */
.filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:12px}
.filters input,.filters select{font:inherit;font-size:.9rem;padding:8px 12px;border:1px solid var(--line);
  border-radius:9px;background:var(--card);color:var(--ink)}
.filters input{flex:1 1 300px;min-width:220px}

.foot{max-width:1180px;margin:0 auto;padding:26px 20px 50px;color:var(--muted);font-size:.86rem;
  border-top:1px solid var(--line);line-height:1.7}

@media print{
  body{background:#fff;font-size:11pt}
  .toc{display:none}
  .cover{background:#0f766e!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .card{box-shadow:none;border:1px solid #cbd5e1;page-break-inside:avoid}
  .h2{page-break-after:avoid}
  .tw.tall{max-height:none;overflow:visible}
  .filters{display:none}
}
@media (max-width:640px){
  .cover h1{font-size:1.7rem}
  main{padding:0 12px 60px}
  .card{padding:16px 14px}
}
CSS;
}

function reportJs(): string
{
    return <<<'JS'
// ---- กรองตารางภาคผนวก ----
function filterRows(){
  var q  = (document.getElementById('q').value || '').toLowerCase().trim();
  var fa = document.getElementById('fArea').value;
  var ft = document.getElementById('fTier').value;
  var rows = document.querySelectorAll('#tblAll tbody tr');
  var shown = 0;
  for (var i = 0; i < rows.length; i++) {
    var r = rows[i];
    var ok = (!q  || r.dataset.s.indexOf(q) !== -1)
          && (!fa || r.dataset.a === fa)
          && (!ft || r.dataset.t === ft);
    r.style.display = ok ? '' : 'none';
    if (ok) shown++;
  }
  document.getElementById('cnt').textContent = 'แสดง ' + shown.toLocaleString('th-TH') + ' จาก ' + rows.length.toLocaleString('th-TH') + ' รายการ';
}

// ---- เรียงลำดับตาราง (คลิกหัวคอลัมน์) ----
function cellVal(td){
  var t = (td.textContent || '').replace(/[,\s%]/g, '');
  var n = parseFloat(t);
  return (t !== '' && !isNaN(n) && /^-?[\d.]+$/.test(t)) ? n : (td.textContent || '').trim();
}
document.addEventListener('click', function(e){
  var th = e.target.closest ? e.target.closest('table.sortable thead th') : null;
  if (!th) return;
  var table = th.closest('table');
  var idx   = Array.prototype.indexOf.call(th.parentNode.children, th);
  var asc   = !(th.dataset.asc === 'true');
  Array.prototype.forEach.call(th.parentNode.children, function(x){ delete x.dataset.asc; x.textContent = x.textContent.replace(/ [▲▼]$/, ''); });
  th.dataset.asc = asc ? 'true' : 'false';
  th.textContent = th.textContent.replace(/ [▲▼]$/, '') + (asc ? ' ▲' : ' ▼');
  var tb = table.tBodies[0];
  var rows = Array.prototype.slice.call(tb.rows);
  rows.sort(function(a, b){
    var x = cellVal(a.cells[idx]), y = cellVal(b.cells[idx]);
    if (typeof x === 'number' && typeof y === 'number') return asc ? x - y : y - x;
    return asc ? String(x).localeCompare(String(y), 'th') : String(y).localeCompare(String(x), 'th');
  });
  rows.forEach(function(r){ tb.appendChild(r); });
});

if (document.getElementById('tblAll')) filterRows();
JS;
}
