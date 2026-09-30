<?php
namespace App\Services;

/**
 * วิเคราะห์สถิติสำหรับหน้า "รายงานสถิติ" — ต่อยอด DashboardController
 * อ้างอิงข้อเสนอ docs/ข้อเสนอรายงานสถิติ_คัดกรองพื้นที่พิเศษ.md
 *
 *   U1  describe()  — สถิติเชิงพรรณนาตัวแปรต่อเนื่อง (N/mean/SD/min/Q1/median/Q3/max)
 *   B2  corr        — สหสัมพันธ์ (Pearson r) ของแต่ละปัจจัยกับคะแนนรวม (บวก/ลบ)
 *   B4  groupMeans  — ค่าเฉลี่ยปัจจัยจำแนกตามระดับผลคัดกรอง (0–3)
 *
 * รับ "แถวดิบ" จาก DashboardStat::analyticsRows() (กรอง role/ปี/ประเมินแล้วมาก่อน)
 * แล้วคำนวณใน PHP — ตัวเลขตรวจสอบตรงกับ pandas (highland 2565: highest mean=789.57,
 * r(highest, sum_score)=0.665) ดู docsref/stat_explore.py
 */
class StatService
{
    /** นิยามปัจจัยต่อประเภทพื้นที่ — [key, label, roles] (roles: desc/corr/group); 'outcome' = ตัวแปรเป้าหมาย */
    private static function factorDefs(int $area): array
    {
        if ($area === 2) {   // พื้นที่เกาะ
            return [
                ['sum_score',            'คะแนนรวม (เป้าหมาย)',          ['desc'], true],
                ['stu_sum',              'จำนวนนักเรียนรวม (คน)',         ['desc','corr','group'], false],
                ['distance_to_province', 'ระยะทางถึงศาลากลาง (กม.)',      ['desc','corr','group'], false],
                ['citeria05',            'ระยะทางเดินทางทางบก (กม.)',     ['desc','corr','group'], false],
                ['citeria06',            'ระยะทางเดินทางทางน้ำ (กม.)',    ['desc','corr','group'], false],
                ['citeria07',            'เวลาเดินทาง (นาที)',           ['desc','corr','group'], false],
                ['citeria08',            'ค่าโดยสาร (บาท)',              ['desc','corr','group'], false],
                ['pct_poor',             'ร้อยละนักเรียนยากจน',          ['desc','corr','group'], false],
            ];
        }
        return [   // พื้นที่สูง
            ['sum_score',            'คะแนนรวม (เป้าหมาย)',           ['desc'], true],
            ['highest',              'ความสูง ณ จุดตั้ง ร.ร. (ม.)',    ['desc','corr','group'], false],
            ['average_height',       'ความสูงเฉลี่ยของจังหวัด (ม.)',   ['desc','corr','group'], false],
            ['distance_to_province', 'ระยะทางถึงศาลากลาง (กม.)',       ['desc','corr','group'], false],
            ['stu_sum',              'จำนวนนักเรียนรวม (คน)',          ['desc','corr','group'], false],
            ['stu_hilltrib',         'นักเรียนชาติพันธุ์ (คน)',         ['desc','corr'], false],
            ['pct_hilltrib',         'ร้อยละนักเรียนชาติพันธุ์',        ['desc','corr','group'], false],
            ['stu_hilltrib_group',   'จำนวนกลุ่มชาติพันธุ์',           ['desc','corr','group'], false],
            ['stu_sleep_sum',        'นักเรียนพักนอน (คน)',           ['desc','corr','group'], false],
            ['pct_poor',             'ร้อยละนักเรียนยากจน',           ['desc','corr','group'], false],
        ];
    }

    /**
     * @param array $rows แถวจาก DashboardStat::analyticsRows()
     * @return array{n:int, descriptive:array, corr:array, groupMeans:array}
     */
    public static function analyze(array $rows, int $area): array
    {
        $defs    = self::factorDefs($area);
        $typeCol = $area === 2 ? 'island_type' : 'highland_type';
        $poorCol = $area === 2 ? 'citeria15' : 'citeria13';

        // ---- เตรียมข้อมูล: ค่าตัวเลข + ตัวแปรสร้างใหม่ (pct) + ระดับ ----
        $data = [];   // แต่ละแถว: [key => float|null, '_type'=>int, '_score'=>float]
        foreach ($rows as $r) {
            $stu = self::f($r['stu_sum'] ?? null);
            $row = [];
            foreach ($r as $k => $v) $row[$k] = self::f($v);
            // ตัวแปรสร้างใหม่ — ร้อยละ (เพดาน 100) เฉพาะเมื่อมีฐานนักเรียน
            $row['pct_hilltrib'] = ($stu > 0 && isset($row['stu_hilltrib']) && $row['stu_hilltrib'] !== null)
                ? min(100.0, $row['stu_hilltrib'] / $stu * 100) : null;
            $row['pct_poor'] = ($stu > 0 && isset($row[$poorCol]) && $row[$poorCol] !== null)
                ? min(100.0, $row[$poorCol] / $stu * 100) : null;
            $row['_type']  = (int) ($r[$typeCol] ?? 0);
            $row['_score'] = self::f($r['sum_score'] ?? null) ?? 0.0;
            $data[] = $row;
        }
        $n = count($data);

        // ---- U1 descriptive + B2 corr ----
        $descriptive = [];
        $corr = [];
        foreach ($defs as [$key, $label, $roles, $outcome]) {
            if (in_array('desc', $roles, true)) {
                $vals = [];
                foreach ($data as $row) if (($row[$key] ?? null) !== null) $vals[] = $row[$key];
                $descriptive[] = ['key' => $key, 'label' => $label, 'outcome' => $outcome] + self::describe($vals);
            }
            if (in_array('corr', $roles, true)) {
                $xs = []; $ys = [];
                foreach ($data as $row) {
                    if (($row[$key] ?? null) !== null) { $xs[] = $row[$key]; $ys[] = $row['_score']; }
                }
                $r = self::pearson($xs, $ys);
                $corr[] = [
                    'key'      => $key,
                    'label'    => $label,
                    'r'        => round($r, 3),
                    'dir'      => $r > 0.0001 ? 'pos' : ($r < -0.0001 ? 'neg' : 'zero'),
                    'strength' => self::strength($r),
                    'n'        => count($xs),
                ];
            }
        }
        // เรียง B2 จากบวกมากไปลบ
        usort($corr, fn($a, $b) => $b['r'] <=> $a['r']);

        // ---- B4 group means by type ----
        $counts = [0, 0, 0, 0];
        foreach ($data as $row) { $t = max(0, min(3, $row['_type'])); $counts[$t]++; }
        $gmRows = [];
        foreach ($defs as [$key, $label, $roles, $outcome]) {
            if (!in_array('group', $roles, true)) continue;
            $means = [];
            for ($t = 0; $t <= 3; $t++) {
                $acc = []; foreach ($data as $row) if ($row['_type'] === $t && ($row[$key] ?? null) !== null) $acc[] = $row[$key];
                $means[$t] = $acc ? round(array_sum($acc) / count($acc), 1) : null;
            }
            $lo = $means[0]; $hi = $means[3];
            $trend = ($lo !== null && $hi !== null) ? ($hi > $lo ? 'up' : ($hi < $lo ? 'down' : 'flat')) : 'na';
            $gmRows[] = ['key' => $key, 'label' => $label, 'means' => $means, 'trend' => $trend];
        }

        return [
            'n'           => $n,
            'descriptive' => $descriptive,
            'corr'        => $corr,
            'groupMeans'  => ['counts' => $counts, 'rows' => $gmRows],
        ];
    }

    // ---- สถิติพื้นฐาน ----

    /** แปลงเป็น float; ค่าว่าง/null → null */
    private static function f($v): ?float
    {
        if ($v === null || $v === '') return null;
        return is_numeric($v) ? (float) $v : null;
    }

    /** สถิติเชิงพรรณนา (SD แบบกลุ่มตัวอย่าง ddof=1; percentile แบบ linear interpolation — ตรงกับ pandas) */
    private static function describe(array $vals): array
    {
        $n = count($vals);
        if ($n === 0) return ['n' => 0, 'mean' => null, 'sd' => null, 'min' => null, 'q1' => null, 'median' => null, 'q3' => null, 'max' => null];
        sort($vals, SORT_NUMERIC);
        $mean = array_sum($vals) / $n;
        $sd = 0.0;
        if ($n > 1) {
            $ss = 0.0; foreach ($vals as $x) $ss += ($x - $mean) ** 2;
            $sd = sqrt($ss / ($n - 1));
        }
        return [
            'n'      => $n,
            'mean'   => round($mean, 2),
            'sd'     => round($sd, 2),
            'min'    => round($vals[0], 2),
            'q1'     => round(self::percentile($vals, 0.25), 2),
            'median' => round(self::percentile($vals, 0.50), 2),
            'q3'     => round(self::percentile($vals, 0.75), 2),
            'max'    => round($vals[$n - 1], 2),
        ];
    }

    /** percentile (linear interpolation) จาก array ที่เรียงแล้ว; p ∈ [0,1] */
    private static function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 0) return 0.0;
        if ($n === 1) return $sorted[0];
        $rank = $p * ($n - 1);
        $lo = (int) floor($rank);
        $hi = (int) ceil($rank);
        if ($lo === $hi) return $sorted[$lo];
        return $sorted[$lo] + ($sorted[$hi] - $sorted[$lo]) * ($rank - $lo);
    }

    /** Pearson correlation; คืน 0 ถ้าจับคู่ไม่ได้/แปรปรวนเป็นศูนย์ */
    private static function pearson(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) return 0.0;
        $sx = array_sum($x); $sy = array_sum($y);
        $sxx = 0.0; $syy = 0.0; $sxy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sxx += $x[$i] * $x[$i];
            $syy += $y[$i] * $y[$i];
            $sxy += $x[$i] * $y[$i];
        }
        $num = $n * $sxy - $sx * $sy;
        $den = sqrt(($n * $sxx - $sx * $sx) * ($n * $syy - $sy * $sy));
        return $den > 0 ? $num / $den : 0.0;
    }

    /** ระดับความแรงของสหสัมพันธ์ (|r|) */
    private static function strength(float $r): string
    {
        $a = abs($r);
        if ($a >= 0.5) return 'แรง';
        if ($a >= 0.3) return 'ปานกลาง';
        if ($a >= 0.1) return 'อ่อน';
        return 'แทบไม่มี';
    }
}
