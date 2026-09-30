<?php
/**
 * data.php — ชั้นดึงข้อมูล + วิเคราะห์ สำหรับรายงานผลการประเมินโรงเรียนพื้นที่ลักษณะพิเศษ
 *
 * แนวคิด: ดึงแถวที่ต้องใช้ทั้งหมดจาก highland_eval / island_eval เข้ามาใน PHP ครั้งเดียว
 * (หลักพันแถว) แล้วคำนวณทุกมิติในหน่วยความจำ — ได้ตัวเลขที่ตรวจสอบย้อนกลับได้ และไม่ต้อง
 * พึ่ง SQL ซับซ้อนที่ผูกกับเวอร์ชัน MySQL/MariaDB ของ production
 */

// ---------------------------------------------------------------------
//  ตัวเลือกคำตอบรายข้อ
// ---------------------------------------------------------------------

/** ข้อของพื้นที่สูงที่มีตาราง master + จำนวนระดับที่ให้คะแนน */
const HIGH_OPT_ITEMS = ['02', '03', '04', '06', '07', '08', '09', '10', '16'];
const HIGH_MULTI     = ['07', '08', '09', '10'];      // เลือกได้หลายข้อ → ใช้ค่าสูงสุด
/** ข้อสาธารณูปโภค: พื้นที่สูง 07 น้ำ / 08 ไฟ / 09 โทร / 10 เน็ต */
const HIGH_UTIL = ['น้ำอุปโภค-บริโภค' => '07', 'ไฟฟ้า' => '08', 'โทรศัพท์' => '09', 'อินเทอร์เน็ต' => '10'];
/** ข้อสาธารณูปโภค: พื้นที่เกาะ 10 ไฟ / 11 น้ำ / 12 เน็ต / 13 โทร */
const ISLAND_UTIL = ['ไฟฟ้า' => '10', 'น้ำอุปโภค-บริโภค' => '11', 'อินเทอร์เน็ต' => '12', 'โทรศัพท์' => '13'];

/** ป้ายตัวเลือกของพื้นที่เกาะ (ตรงกับ App\Models\IslandOption::SETS) */
const ISLAND_OPT = [
    '02' => [1 => 'องค์การบริหารส่วนตำบล', 2 => 'เทศบาลตำบล', 3 => 'เทศบาลเมือง', 4 => 'เทศบาลนคร'],
    '03' => [1 => 'มีน้ำล้อมรอบ ไม่มีสะพานเชื่อม (เรือเท่านั้น)', 2 => 'มีน้ำล้อมรอบ มีสะพานเชื่อม/ไปได้ทั้งบกและน้ำ'],
    '04' => [1 => 'เรือไม่ประจำทางเป็นหลัก', 2 => 'เรือประจำทาง ไม่ตลอดปี', 3 => 'เรือประจำทาง ได้ทั้งปี', 4 => 'เรือเฟอร์รี่/แพขนานยนต์', 5 => 'ใช้รถสัญจรเป็นหลัก'],
    '09' => [1 => 'ทางเรือแล้วต่อด้วยเดินเท้า', 2 => 'เดินเท้าเท่านั้น', 3 => 'รถบนเส้นทางลำลอง/ทางเท้า', 4 => 'รถบนทางลาดยาง/คอนกรีต', 5 => 'ไม่ต้องเดินทางต่อ'],
    '10' => [1 => 'มีเฉพาะพลังงานทางเลือก (โซลาร์/ลม/เครื่องปั่นไฟ)', 2 => 'มีไฟฟ้าส่วนภูมิภาค'],
    '11' => [1 => 'ต่อจากแหล่งน้ำธรรมชาติ', 2 => 'สูบจากบ่อ/สระ ของชุมชน', 3 => 'สูบจากบ่อ/สระ ของโรงเรียน', 4 => 'ประปาชุมชน/หมู่บ้าน', 5 => 'ประปา อปท.', 6 => 'ประปาส่วนภูมิภาค'],
    '12' => [1 => 'ไม่มีเครือข่ายอินเทอร์เน็ต', 2 => 'มีเฉพาะจานดาวเทียม', 3 => 'มีสัญญาณไร้สาย (มือถือ)', 4 => 'มี Leased line / Fiber Optic'],
    '13' => [1 => 'ไม่มีสัญญาณโทรศัพท์', 2 => 'มีเฉพาะผ่านจานดาวเทียม', 3 => 'มีโทรศัพท์เคลื่อนที่เท่านั้น', 4 => 'มีโทรศัพท์พื้นฐาน'],
    '14' => [1 => 'ใช่ (พื้นที่พิเศษกระทรวงการคลัง)', 2 => 'ไม่ใช่'],
];

/** โหลดป้ายตัวเลือกของพื้นที่สูงจากตาราง citeria_master{NN} */
function loadHighOptions(PDO $pdo): array
{
    $out = [];
    foreach (HIGH_OPT_ITEMS as $nn) {
        try {
            $rows = q($pdo, "SELECT id, `master{$nn}` AS label FROM `citeria_master{$nn}` ORDER BY id");
        } catch (Throwable $e) {
            $rows = [];
        }
        $set = [];
        foreach ($rows as $r) {
            $set[(int) $r['id']] = trim(preg_replace('/\s+/u', ' ', str_replace(['\n', '<br>', '<br/>'], ' ', (string) $r['label'])));
        }
        $out[$nn] = $set;
    }
    return $out;
}

// ---------------------------------------------------------------------
//  ดึงแถวประเมิน
// ---------------------------------------------------------------------

/** คอลัมน์ที่ต้องใช้ (ไม่ดึง *_refdoc ซึ่งเป็น TEXT ขนาดใหญ่) */
function evalColumns(int $area): string
{
    $base = 'sc_id, acadyears, sc_names, provinces, district, subdistrict, sao_names, lat, lng,
             stu_kinder, stu_prim, stu_second, stu_high, stu_sum, distance_to_province,
             sum_score, confirmstatus, spt_commit, opened, status, date_created';
    if ($area === 1) {
        $c = $base . ', highest, average_height, stu_hilltrib, stu_hilltrib_group, stu_sleep_sum, stu_sleep_boy, stu_sleep_girl,
                       highland_type AS tier_col, citeria041';
        for ($i = 1; $i <= 16; $i++) $c .= sprintf(', citeria%02d, score%02d', $i, $i);
        return $c;
    }
    $c = $base . ', island_type AS tier_col, sum_teacher, teacher';
    for ($i = 1; $i <= 15; $i++) $c .= sprintf(', citeria%02d, score%02d', $i, $i);
    return $c;
}

/** @return array แถวประเมินของพื้นที่ $area ตามชุดปีที่กำหนด (ว่าง = ทุกปี) */
function loadEvalRows(PDO $pdo, int $area, array $years): array
{
    $tbl = $area === 1 ? 'highland_eval' : 'island_eval';
    $sql = 'SELECT ' . evalColumns($area) . " FROM `$tbl`";
    $p = [];
    if ($years) {
        $sql .= ' WHERE acadyears IN (' . implode(',', array_fill(0, count($years), '?')) . ')';
        $p = $years;
    }
    return q($pdo, $sql, $p);
}

/** กลุ่มชาติพันธุ์รายโรงเรียน (เฉพาะพื้นที่สูง) */
function loadEthnic(PDO $pdo, array $years): array
{
    $sql = "SELECT h.sc_id, h.acadyears, TRIM(COALESCE(t.ethnic,'')) ethnic, h.hilltrib_number n
              FROM highland_eval_hilltrib h
              LEFT JOIN hilltrib t ON t.ethnic_id = h.hilltrib";
    $p = [];
    if ($years) {
        $sql .= ' WHERE h.acadyears IN (' . implode(',', array_fill(0, count($years), '?')) . ')';
        $p = $years;
    }
    return q($pdo, $sql, $p);
}

/** สรุปการรับรองการคงอยู่ (school_confirm) ของปีที่ระบุ */
function loadConfirm(PDO $pdo, int $year): array
{
    try {
        $rows = q($pdo, 'SELECT area_type, opened, school_confirmed, submitted, sao_status, spt_status,
                                std_total, tch_total, provinces
                           FROM school_confirm WHERE acadyears = ?', [$year]);
    } catch (Throwable $e) {
        return [];
    }
    return $rows;
}

/** ปีงบประมาณทั้งหมดที่มีข้อมูลประเมิน */
function availableYears(PDO $pdo): array
{
    $rows = q($pdo, 'SELECT acadyears y, COUNT(*) n, SUM(sum_score > 0) scored FROM highland_eval GROUP BY acadyears
                     UNION ALL
                     SELECT acadyears y, COUNT(*) n, SUM(sum_score > 0) scored FROM island_eval GROUP BY acadyears');
    $agg = [];
    foreach ($rows as $r) {
        $y = (int) $r['y'];
        $agg[$y] = ($agg[$y] ?? 0) + (int) $r['scored'];
    }
    krsort($agg);
    return $agg;   // [ปี => จำนวนที่ประเมินแล้ว]
}

// ---------------------------------------------------------------------
//  วิเคราะห์
// ---------------------------------------------------------------------

/** ปัจจัยเชิงบริบทที่นำมาบรรยาย/หาสหสัมพันธ์ ต่อกลุ่มพื้นที่ */
function contextFactors(int $area): array
{
    if ($area === 2) {
        return [
            ['stu_sum',              'จำนวนนักเรียนรวม (คน)',      'คน'],
            ['sum_teacher',          'จำนวนครูและบุคลากรรวม (คน)',  'คน'],
            ['distance_to_province', 'ระยะทางถึงศาลากลาง (กม.)',    'กม.'],
            ['citeria05',            'ระยะทางเดินทางทางบก (กม.)',   'กม.'],
            ['citeria06',            'ระยะทางเดินทางทางน้ำ (กม.)',  'กม.'],
            ['citeria07',            'เวลาเดินทาง (นาที)',          'นาที'],
            ['citeria08',            'ค่าโดยสารต่อเที่ยว (บาท)',     'บาท'],
            ['citeria15',            'จำนวนนักเรียนยากจน (คน)',      'คน'],
            ['pct_poor',             'ร้อยละนักเรียนยากจน',          '%'],
        ];
    }
    return [
        ['highest',              'ความสูงจุดสูงสุดของเส้นทาง (ม.)', 'ม.'],
        ['average_height',       'ความสูงเฉลี่ยของจังหวัด (ม.)',    'ม.'],
        ['distance_to_province', 'ระยะทางถึงศาลากลาง (กม.)',        'กม.'],
        ['citeria041',           'ระยะทางที่รถ 2 ล้อไปไม่ได้ (กม.)', 'กม.'],
        ['stu_sum',              'จำนวนนักเรียนรวม (คน)',           'คน'],
        ['stu_hilltrib',         'จำนวนนักเรียนชาติพันธุ์ (คน)',     'คน'],
        ['pct_hilltrib',         'ร้อยละนักเรียนชาติพันธุ์',         '%'],
        ['stu_hilltrib_group',   'จำนวนกลุ่มชาติพันธุ์ (กลุ่ม)',     'กลุ่ม'],
        ['stu_sleep_sum',        'จำนวนนักเรียนพักนอน (คน)',        'คน'],
        ['citeria13',            'จำนวนนักเรียนยากจน (คน)',          'คน'],
        ['pct_poor',             'ร้อยละนักเรียนยากจน',              '%'],
    ];
}

/**
 * วิเคราะห์ทุกมิติของกลุ่มพื้นที่หนึ่ง
 * @param array $rows   แถวจาก loadEvalRows()
 * @param int   $area   1 = พื้นที่สูง, 2 = พื้นที่เกาะ
 * @param array $ethnic แถวจาก loadEthnic() (ใช้เฉพาะพื้นที่สูง)
 */
function analyzeArea(array $rows, int $area, array $ethnic = []): array
{
    $items    = $area === 1 ? HIGH_ITEMS : ISLAND_ITEMS;
    $nItems   = $area === 1 ? 16 : 15;
    $utilMap  = $area === 1 ? HIGH_UTIL : ISLAND_UTIL;
    $poorCol  = $area === 1 ? 'citeria13' : 'citeria15';

    // ---- เตรียมแถว: แปลงชนิด + ตัวแปรสร้างใหม่ ----
    $R = [];
    foreach ($rows as $r) {
        $stu   = (int) ($r['stu_sum'] ?? 0);
        $score = num($r['sum_score'] ?? null);
        $x = $r;
        $x['_score'] = $score;
        $x['_scored'] = ($score !== null && $score > 0);
        $x['_tier']  = $x['_scored'] ? tierOf($score) : null;
        $x['_pass']  = ($score !== null && $score >= 50);
        $x['_prov']  = trim((string) ($r['provinces'] ?? '')) ?: 'ไม่ระบุจังหวัด';
        $x['_sao']   = trim((string) ($r['sao_names'] ?? '')) ?: 'ไม่ระบุเขตพื้นที่';
        $x['_region'] = regionOf($x['_prov']);
        $x['_size']  = schoolSize($stu > 0 ? $stu : null);
        $x['pct_poor'] = $stu > 0 ? min(100.0, (float) ($r[$poorCol] ?? 0) * 100 / $stu) : null;
        if ($area === 1) {
            $x['pct_hilltrib'] = $stu > 0 ? min(100.0, (float) ($r['stu_hilltrib'] ?? 0) * 100 / $stu) : null;
        }
        $R[] = $x;
    }

    $scoredRows = array_values(array_filter($R, fn($x) => $x['_scored']));
    $scores     = array_map(fn($x) => (float) $x['_score'], $scoredRows);
    $nAll       = count($R);
    $nScored    = count($scoredRows);
    $nPass      = count(array_filter($scoredRows, fn($x) => $x['_pass']));

    // ---- ระดับความยุ่งยาก ----
    $tierDist = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
    foreach ($scoredRows as $x) $tierDist[$x['_tier']]++;

    // ---- คะแนนรายข้อ ----
    $itemStats = [];
    for ($i = 1; $i <= $nItems; $i++) {
        $col = sprintf('score%02d', $i);
        $vals = [];
        foreach ($scoredRows as $x) { $v = num($x[$col] ?? null); if ($v !== null) $vals[] = $v; }
        $st = describe($vals);
        $max = $items[$i][1];
        $itemStats[$i] = [
            'no'       => $i,
            'label'    => $items[$i][0],
            'full'     => $items[$i][2],
            'max'      => $max,
            'st'       => $st,
            'pctOfMax' => $max > 0 && $st['mean'] !== null ? $st['mean'] / $max * 100 : null,
            'share'    => $st['sum'],           // คะแนนสะสมทั้งกลุ่ม → ใช้หาสัดส่วนที่มาของคะแนนรวม
        ];
    }
    $itemSumAll = array_sum(array_map(fn($it) => (float) $it['share'], $itemStats));

    // ---- จำแนกตามมิติจัดกลุ่ม ----
    $group = function (string $key) use ($scoredRows): array {
        $g = [];
        foreach ($scoredRows as $x) {
            $k = $x[$key];
            if (!isset($g[$k])) $g[$k] = ['n' => 0, 'pass' => 0, 'sum' => 0.0, 'tiers' => [0=>0,1=>0,2=>0,3=>0], 'stu' => 0, 'scores' => []];
            $g[$k]['n']++;
            $g[$k]['sum'] += (float) $x['_score'];
            $g[$k]['scores'][] = (float) $x['_score'];
            $g[$k]['stu'] += (int) ($x['stu_sum'] ?? 0);
            $g[$k]['tiers'][$x['_tier']]++;
            if ($x['_pass']) $g[$k]['pass']++;
        }
        foreach ($g as $k => &$v) {
            $v['avg']      = $v['n'] > 0 ? $v['sum'] / $v['n'] : 0.0;
            $v['passRate'] = $v['n'] > 0 ? $v['pass'] / $v['n'] * 100 : 0.0;
            $v['median']   = $v['scores'] ? (function ($s) { sort($s); return percentile($s, 0.5); })($v['scores']) : 0.0;
            unset($v['scores']);
        }
        unset($v);
        return $g;
    };
    $byProvince = $group('_prov');
    $bySao      = $group('_sao');
    $byRegion   = $group('_region');
    $bySize     = $group('_size');
    uasort($byProvince, fn($a, $b) => $b['n'] <=> $a['n']);
    uasort($bySao,      fn($a, $b) => $b['n'] <=> $a['n']);
    uasort($byRegion,   fn($a, $b) => $b['n'] <=> $a['n']);
    $bySizeOrdered = [];
    foreach (SIZE_ORDER as $k) if (isset($bySize[$k])) $bySizeOrdered[$k] = $bySize[$k];

    // ---- ปัจจัยเชิงบริบท: สถิติเชิงพรรณนา + สหสัมพันธ์ + ค่าเฉลี่ยตามระดับ ----
    $factors = contextFactors($area);
    $context = []; $corr = []; $groupMeans = [];
    foreach ($factors as [$key, $label, $unit]) {
        $vals = []; $xs = []; $ys = [];
        $means = [0 => [], 1 => [], 2 => [], 3 => []];
        foreach ($scoredRows as $x) {
            $v = num($x[$key] ?? null);
            if ($v === null) continue;
            $vals[] = $v;
            $xs[] = $v; $ys[] = (float) $x['_score'];
            $means[$x['_tier']][] = $v;
        }
        $st = describe($vals);
        $context[] = ['key' => $key, 'label' => $label, 'unit' => $unit, 'st' => $st];
        $r = pearson($xs, $ys);
        $corr[] = ['key' => $key, 'label' => $label, 'r' => round($r, 3), 'n' => count($xs), 'strength' => corrStrength($r)];
        $gm = [];
        for ($t = 0; $t <= 3; $t++) $gm[$t] = $means[$t] ? round(array_sum($means[$t]) / count($means[$t]), 1) : null;
        $groupMeans[] = ['key' => $key, 'label' => $label, 'unit' => $unit, 'means' => $gm];
    }
    usort($corr, fn($a, $b) => $b['r'] <=> $a['r']);

    // ---- การกระจายคำตอบรายข้อ (เชิงกลุ่ม) ----
    $optDist = [];
    $optSets = $area === 1 ? ($GLOBALS['HIGH_OPTIONS'] ?? []) : ISLAND_OPT;
    foreach ($optSets as $nn => $labels) {
        if (!$labels) continue;
        $col = 'citeria' . $nn;
        $d = [];
        foreach ($labels as $id => $lb) $d[$id] = 0;
        $answered = 0;
        foreach ($scoredRows as $x) {
            $raw = (string) ($x[$col] ?? '');
            if ($raw === '') continue;
            $v = ($area === 1 && in_array($nn, HIGH_MULTI, true)) ? maxOfCsv($raw) : (int) $raw;
            if ($v > 0 && isset($d[$v])) { $d[$v]++; $answered++; }
        }
        if ($answered === 0) continue;
        $optDist[$nn] = ['labels' => $labels, 'dist' => $d, 'answered' => $answered];
    }

    // ---- สาธารณูปโภค ----
    $utils = [];
    foreach ($utilMap as $name => $nn) {
        if (isset($optDist[$nn])) $utils[$name] = $optDist[$nn];
    }

    // ---- การรับรอง ----
    $cert = [
        'sao_certed'    => count(array_filter($scoredRows, fn($x) => (int) ($x['confirmstatus'] ?? 0) === 1)),
        'spt_announced' => count(array_filter($scoredRows, fn($x) => (int) ($x['spt_commit'] ?? 0) === 1)),
        'pending'       => count(array_filter($scoredRows, fn($x) => (int) ($x['confirmstatus'] ?? 0) !== 1)),
    ];

    // ---- คุณภาพข้อมูล ----
    $quality = [
        'ทั้งหมดในรอบ'          => $nAll,
        'ประเมิน/คิดคะแนนแล้ว'  => $nScored,
        'ยังไม่มีคะแนน'          => $nAll - $nScored,
        'ไม่มีพิกัดแผนที่'        => count(array_filter($R, fn($x) => trim((string) ($x['lat'] ?? '')) === '')),
        'ไม่ระบุจังหวัด'         => count(array_filter($R, fn($x) => trim((string) ($x['provinces'] ?? '')) === '')),
        'ไม่ระบุเขตพื้นที่'       => count(array_filter($R, fn($x) => trim((string) ($x['sao_names'] ?? '')) === '')),
        'จำนวนนักเรียน = 0'     => count(array_filter($R, fn($x) => (int) ($x['stu_sum'] ?? 0) <= 0)),
    ];

    // ---- อันดับโรงเรียน ----
    $sorted = $scoredRows;
    usort($sorted, fn($a, $b) => (float) $b['_score'] <=> (float) $a['_score']);

    // ---- ชาติพันธุ์ (พื้นที่สูง) ----
    $ethnicTop = []; $ethnicSummary = ['groups' => 0, 'students' => 0, 'schools' => 0];
    if ($area === 1 && $ethnic) {
        $keep = [];
        foreach ($scoredRows as $x) $keep[$x['sc_id'] . '|' . $x['acadyears']] = true;
        $agg = []; $sch = [];
        foreach ($ethnic as $e) {
            if (!isset($keep[$e['sc_id'] . '|' . $e['acadyears']])) continue;
            $name = trim((string) $e['ethnic']);
            if ($name === '') continue;
            $agg[$name] = ($agg[$name] ?? 0) + (int) $e['n'];
            $sch[$name][$e['sc_id']] = true;
            $ethnicSummary['students'] += (int) $e['n'];
        }
        arsort($agg);
        foreach ($agg as $name => $n) $ethnicTop[] = ['ethnic' => $name, 'students' => $n, 'schools' => count($sch[$name])];
        $ethnicSummary['groups'] = count($agg);
        $allSch = [];
        foreach ($sch as $m) foreach ($m as $id => $_) $allSch[$id] = true;
        $ethnicSummary['schools'] = count($allSch);
    }

    // ---- นักเรียน/ครู ในกลุ่มที่ผ่านเกณฑ์ ----
    $stuPassed = 0; $stuAll = 0;
    foreach ($scoredRows as $x) {
        $stuAll += (int) ($x['stu_sum'] ?? 0);
        if ($x['_pass']) $stuPassed += (int) ($x['stu_sum'] ?? 0);
    }

    return [
        'area'       => $area,
        'rows'       => $R,
        'scoredRows' => $scoredRows,
        'nAll'       => $nAll,
        'nScored'    => $nScored,
        'nPass'      => $nPass,
        'nFail'      => $nScored - $nPass,
        'passRate'   => $nScored > 0 ? $nPass / $nScored * 100 : 0.0,
        'scoreStats' => describe($scores),
        'bins'       => scoreBins($scores),
        'tierDist'   => $tierDist,
        'items'      => $itemStats,
        'itemSumAll' => $itemSumAll,
        'byProvince' => $byProvince,
        'bySao'      => $bySao,
        'byRegion'   => $byRegion,
        'bySize'     => $bySizeOrdered,
        'context'    => $context,
        'corr'       => $corr,
        'groupMeans' => $groupMeans,
        'optDist'    => $optDist,
        'utils'      => $utils,
        'cert'       => $cert,
        'quality'    => $quality,
        'ranked'     => $sorted,
        'ethnicTop'  => $ethnicTop,
        'ethnicSum'  => $ethnicSummary,
        'stuAll'     => $stuAll,
        'stuPassed'  => $stuPassed,
    ];
}

/** ค่าสูงสุดจาก comma-list เช่น "1,2,3" → 3 (ข้อที่เลือกได้หลายคำตอบ) */
function maxOfCsv(string $csv): int
{
    $max = 0;
    foreach (explode(',', $csv) as $p) {
        $v = (int) trim($p);
        if ($v > $max) $max = $v;
    }
    return $max;
}

/** สรุปรายปีของกลุ่มพื้นที่ (ใช้ทำแนวโน้ม) */
function yearSummary(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $y = (int) $r['acadyears'];
        $s = num($r['sum_score'] ?? null);
        if (!isset($out[$y])) $out[$y] = ['n' => 0, 'scored' => 0, 'pass' => 0, 'sum' => 0.0, 'tiers' => [0=>0,1=>0,2=>0,3=>0]];
        $out[$y]['n']++;
        if ($s !== null && $s > 0) {
            $out[$y]['scored']++;
            $out[$y]['sum'] += $s;
            $out[$y]['tiers'][tierOf($s)]++;
            if ($s >= 50) $out[$y]['pass']++;
        }
    }
    foreach ($out as &$v) {
        $v['avg']      = $v['scored'] > 0 ? round($v['sum'] / $v['scored'], 2) : null;
        $v['passRate'] = $v['scored'] > 0 ? round($v['pass'] / $v['scored'] * 100, 1) : null;
    }
    unset($v);
    ksort($out);
    return $out;
}

/** สรุปสถานะการรับรองการคงอยู่ (school_confirm) */
function confirmSummary(array $rows): array
{
    $o = [
        'total' => 0, 'high' => 0, 'island' => 0,
        'active' => 0, 'closed' => 0, 'disqualified' => 0,
        'school_confirmed' => 0, 'submitted' => 0, 'sao_ok' => 0, 'spt_ok' => 0,
        'std_total' => 0, 'tch_total' => 0,
        'byProvince' => [],
    ];
    foreach ($rows as $r) {
        $o['total']++;
        $at = (int) $r['area_type'];
        if ($at === 1) $o['high']++; elseif ($at === 2) $o['island']++;
        $op = $r['opened'] === null ? 1 : (int) $r['opened'];
        if ($op === 1) $o['active']++; elseif ($op === 0) $o['closed']++; elseif ($op === 2) $o['disqualified']++;
        if ((int) $r['school_confirmed'] === 1) $o['school_confirmed']++;
        if ((int) $r['submitted'] === 1) $o['submitted']++;
        if ((int) $r['sao_status'] === 1) $o['sao_ok']++;
        if ((int) $r['spt_status'] === 1) $o['spt_ok']++;
        $o['std_total'] += (int) $r['std_total'];
        $o['tch_total'] += (int) $r['tch_total'];
        $p = trim((string) ($r['provinces'] ?? '')) ?: 'ไม่ระบุ';
        $o['byProvince'][$p] = ($o['byProvince'][$p] ?? 0) + 1;
    }
    arsort($o['byProvince']);
    return $o;
}
