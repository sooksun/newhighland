<?php
namespace App\Models;

use App\Core\Db;

/**
 * สถิติสำหรับหน้า "รายงานสถิติ" (DashboardController) — รวม query แบบ aggregate ไว้ที่เดียว
 *
 * หลักการ (ออกแบบใหม่เพื่อความเร็ว — ดู docs/plan velvety-launching-plum):
 *  - กรองตาม role: สพฐ. (admin) = ทุกเขต ($saoId = null); เขต (sao) = เฉพาะสังกัดตน
 *    *ไม่* join master_school 30k แถวต่อ query แล้ว — admin ตัด join ทิ้ง, เขต resolve รายชื่อ
 *    sc_id ครั้งเดียว (saoScids) แล้วกรองด้วย e.sc_id IN (...) (master_school มี idx_sao_code)
 *  - funnel + typeDist + scoreHist + utilities + ส่วน kpi ของพื้นที่หนึ่ง = query เดียว (areaStats)
 *    ด้วย conditional SUM แล้ว memoize ต่อ request
 *  - join ที่จำเป็น (school_new/master_school) แก้ชนิดให้ใช้ index: school_new ใช้
 *    CAST(e.sc_id AS CHAR) (PK varchar), master_school ใช้ CONVERT(e.sc_id USING utf8)
 *    (idx_sc_id เป็น charset 3 ไบต์) — ใช้ชื่อ "utf8" ไม่ใช่ "utf8mb3" เพราะ production
 *    = MariaDB 10.4 ยังไม่รู้จักชื่อ "utf8mb3" (มีตั้งแต่ 10.6) → "Unknown character set"
 *  - คอลัมน์/ชื่อตารางทั้งหมดมาจากค่าคงที่ภายใน ไม่ใช่ค่าจากผู้ใช้ (กัน SQL injection)
 */
class DashboardStat
{
    /** ค่าคงที่ต่อประเภทพื้นที่ */
    private static function cfg(int $area): array
    {
        return $area === 2
            ? ['table' => 'island_eval',   'type' => 'island_type',   'pinned' => "(e.lat <> '')"]
            : ['table' => 'highland_eval', 'type' => 'highland_type', 'pinned' => "(e.lat <> '' OR e.highest > 0)"];
    }

    /** เลขข้อสาธารณูปโภคตามประเภทพื้นที่ (น้ำ/ไฟ/โทร/เน็ต) */
    public static function utilConfig(int $area): array
    {
        return $area === 2
            ? ['water' => '11', 'power' => '10', 'phone' => '13', 'net' => '12']
            : ['water' => '07', 'power' => '08', 'phone' => '09', 'net' => '10'];
    }

    /** จำนวนระดับสูงสุดของแต่ละข้อ (เท่ากันทั้งสองพื้นที่) */
    private const UTIL_MAX = ['water' => 6, 'power' => 2, 'phone' => 4, 'net' => 4];

    // ---- role scope (ไม่ join master_school) ----

    /**
     * รายชื่อ sc_id (int) ของโรงเรียนในสังกัดเขต — resolve ครั้งเดียว (memoize)
     * admin (saoId=null) → null = ไม่กรอง; เขต → array (อาจว่างถ้าเขตไม่มี ร.ร.)
     * ใช้ idx_sao_code; CAST sc_id เป็น UNSIGNED ให้ตรงชนิด e.sc_id (INT)
     */
    private static function saoScids(?int $saoId): ?array
    {
        if ($saoId === null) return null;
        static $memo = [];
        if (array_key_exists($saoId, $memo)) return $memo[$saoId];
        $rows = Db::all('SELECT CAST(m.sc_id AS UNSIGNED) id FROM master_school m WHERE m.sao_code = ?', [$saoId]);
        return $memo[$saoId] = array_map(fn($r) => (int) $r['id'], $rows);
    }

    /** แปลงชุด sc_id เป็น [sqlFragment, params] สำหรับกรอง eval (e.sc_id IN ...) */
    private static function evalScope(?array $scids): array
    {
        if ($scids === null) return ['', []];           // admin = ไม่กรอง
        if ($scids === [])   return [' AND 1=0', []];   // เขตไม่มี ร.ร. = ผลว่าง
        $ph = implode(',', array_fill(0, count($scids), '?'));
        return [" AND e.sc_id IN ($ph)", $scids];
    }

    // ---- single-pass aggregation ต่อพื้นที่ (funnel + type + score + utilities + kpi) ----

    /**
     * query เดียวเหนือ eval table (ไม่ join master_school) — memoize ต่อ request
     * @return array{funnel:array,kpi:array,typeDist:array,scoreHist:array,utils:array}
     */
    private static function areaStats(int $area, ?array $scids, int $year): array
    {
        static $memo = [];
        $key = $area . '|' . $year . '|' . md5(json_encode($scids));
        if (isset($memo[$key])) return $memo[$key];

        $c = self::cfg($area);
        $typeCol = $c['type'];
        $multi = ($area === 1);   // พื้นที่สูง: ข้อ 07-10 เลือกได้หลายข้อ
        [$scopeSql, $scopeParams] = self::evalScope($scids);

        $cols = [
            "SUM({$c['pinned']}) pinned",                                              // funnel: ทุกแถวในปี
            "SUM(e.sum_score IS NOT NULL) evaluated",
            "SUM(e.sum_score >= 50) passed",
            "SUM(COALESCE(e.confirmstatus,0)=1) cert_all",                             // funnel (ไม่กรอง sum_score)
            "SUM(COALESCE(e.spt_commit,0)=1) spt_all",
            "SUM(e.sum_score IS NOT NULL AND COALESCE(e.confirmstatus,0)=1) cert_eval",// kpi (กรอง sum_score)
            "SUM(e.sum_score IS NOT NULL AND COALESCE(e.spt_commit,0)=1) spt_eval",
        ];
        for ($t = 0; $t <= 3; $t++) {
            $cols[] = "SUM(e.sum_score IS NOT NULL AND e.`$typeCol`=$t) t$t";
        }
        $cols[] = "SUM(e.sum_score IS NOT NULL AND e.sum_score < 50) h0";
        $cols[] = "SUM(e.sum_score >= 50 AND e.sum_score < 60) h1";
        $cols[] = "SUM(e.sum_score >= 60 AND e.sum_score < 70) h2";
        $cols[] = "SUM(e.sum_score >= 70 AND e.sum_score < 80) h3";
        $cols[] = "SUM(e.sum_score >= 80) h4";
        foreach (self::utilConfig($area) as $ukey => $nn) {
            $max  = self::UTIL_MAX[$ukey];
            $col  = 'citeria' . $nn;
            $expr = $multi ? self::maxLevelExpr($col, $max)
                           : "COALESCE(CAST(NULLIF(e.`$col`,'') AS UNSIGNED),0)";
            for ($lvl = 1; $lvl <= $max; $lvl++) {
                $cols[] = "SUM(e.sum_score IS NOT NULL AND ($expr)=$lvl) u_{$ukey}_{$lvl}";
            }
        }

        $sql = 'SELECT ' . implode(",\n       ", $cols) . "\n  FROM `{$c['table']}` e WHERE e.acadyears = ?" . $scopeSql;
        $r = Db::one($sql, array_merge([$year], $scopeParams)) ?? [];
        $g = fn($k) => (int) ($r[$k] ?? 0);

        $res = [
            'funnel' => [
                'pinned'        => $g('pinned'),
                'evaluated'     => $g('evaluated'),
                'passed'        => $g('passed'),
                'sao_certed'    => $g('cert_all'),
                'spt_announced' => $g('spt_all'),
            ],
            'kpi' => [
                'done'          => $g('evaluated'),
                'passed'        => $g('passed'),
                'sao_certed'    => $g('cert_eval'),
                'spt_announced' => $g('spt_eval'),
            ],
            'typeDist'  => [0 => $g('t0'), 1 => $g('t1'), 2 => $g('t2'), 3 => $g('t3')],
            'scoreHist' => ['<50' => $g('h0'), '50-59' => $g('h1'), '60-69' => $g('h2'), '70-79' => $g('h3'), '80+' => $g('h4')],
            'utils'     => [],
        ];
        foreach (self::utilConfig($area) as $ukey => $nn) {
            $max = self::UTIL_MAX[$ukey];
            $levels = []; $answered = 0;
            for ($lvl = 1; $lvl <= $max; $lvl++) { $n = $g("u_{$ukey}_{$lvl}"); $levels[$lvl] = $n; $answered += $n; }
            $res['utils'][$ukey] = ['nn' => $nn, 'max' => $max, 'levels' => $levels, 'answered' => $answered];
        }
        return $memo[$key] = $res;
    }

    // ---- การ์ด KPI ----

    /**
     * จำนวนเป้าหมายจาก roster การคงอยู่ (school_confirm — มี index idx_sao/idx_area อยู่แล้ว)
     * แยกสถานะการคงอยู่ (opened): active=ยังคงอยู่จริง (1 หรือยังไม่ยืนยัน=NULL),
     * closed=ยุบ/รวม/เลิก (0), disqualified=ขาดคุณสมบัติ (2)
     *   - total  = จำนวน roster เดิมทั้งหมด (เป้าหมายตั้งต้น)
     *   - active = เป้าหมายที่ "ยังคงอยู่จริง" (ตัด ยุบ/รวม/เลิก + ขาดคุณสมบัติ ออก)
     */
    public static function targets(?int $saoId, int $year): array
    {
        $sql = 'SELECT COUNT(*) total, SUM(area_type=1) high, SUM(area_type=2) island,
                       SUM(COALESCE(opened,1)=1) active,
                       SUM(area_type=1 AND COALESCE(opened,1)=1) high_active,
                       SUM(area_type=2 AND COALESCE(opened,1)=1) island_active,
                       SUM(opened=0) closed, SUM(opened=2) disqualified
                  FROM school_confirm c WHERE c.acadyears = ?';
        $p = [$year];
        if ($saoId !== null) { $sql .= ' AND c.sao_id = ?'; $p[] = $saoId; }
        $r = Db::one($sql, $p) ?? [];
        return [
            'total'         => (int) ($r['total'] ?? 0),
            'high'          => (int) ($r['high'] ?? 0),
            'island'        => (int) ($r['island'] ?? 0),
            'active'        => (int) ($r['active'] ?? 0),
            'high_active'   => (int) ($r['high_active'] ?? 0),
            'island_active' => (int) ($r['island_active'] ?? 0),
            'closed'        => (int) ($r['closed'] ?? 0),
            'disqualified'  => (int) ($r['disqualified'] ?? 0),
        ];
    }

    /** ดำเนินการ/ผ่านเกณฑ์/เขตรับรอง/สพฐ.ประกาศ — รวมสองพื้นที่จากผล areaStats (เลิก UNION) */
    public static function kpis(?int $saoId, int $year): array
    {
        $scids = self::saoScids($saoId);
        $h = self::areaStats(1, $scids, $year)['kpi'];
        $i = self::areaStats(2, $scids, $year)['kpi'];
        return [
            'done'          => $h['done']          + $i['done'],
            'passed'        => $h['passed']        + $i['passed'],
            'sao_certed'    => $h['sao_certed']    + $i['sao_certed'],
            'spt_announced' => $h['spt_announced'] + $i['spt_announced'],
        ];
    }

    // ---- wrappers (รูปแบบผลลัพธ์เหมือนเดิม — view/export ไม่ต้องแก้) ----

    public static function funnel(int $area, ?int $saoId, int $year): array
    {
        return self::areaStats($area, self::saoScids($saoId), $year)['funnel'];
    }

    /** คืน [0=>n,1=>n,2=>n,3=>n] */
    public static function typeDist(int $area, ?int $saoId, int $year): array
    {
        return self::areaStats($area, self::saoScids($saoId), $year)['typeDist'];
    }

    /** คืน [ '<50'=>n,'50-59'=>n,'60-69'=>n,'70-79'=>n,'80+'=>n ] */
    public static function scoreHist(int $area, ?int $saoId, int $year): array
    {
        return self::areaStats($area, self::saoScids($saoId), $year)['scoreHist'];
    }

    /** @return array key => ['nn'=>'07','max'=>6,'levels'=>[1=>n,...],'answered'=>int] */
    public static function utilities(int $area, ?int $saoId, int $year): array
    {
        return self::areaStats($area, self::saoScids($saoId), $year)['utils'];
    }

    // ---- อันดับจังหวัด (ผ่านเกณฑ์มากสุด) — ใช้ school_new ผ่าน CAST (eq_ref PK) ----

    public static function topProvinces(int $area, ?int $saoId, int $year, int $limit = 8): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::evalScope(self::saoScids($saoId));
        $prov = 'COALESCE(NULLIF(s.province,""), NULLIF(e.provinces,""))';
        $sql = "SELECT $prov province, COUNT(*) passed
                  FROM `{$c['table']}` e
                  LEFT JOIN school_new s ON s.sc_id = CAST(e.sc_id AS CHAR)
                 WHERE e.acadyears = ? AND e.sum_score >= 50 $rs
                 GROUP BY province
                HAVING province IS NOT NULL AND province <> ''
                 ORDER BY passed DESC
                 LIMIT " . (int) $limit;
        return Db::all($sql, array_merge([$year], $rp));
    }

    // ---- สาธารณูปโภค: นิพจน์ "ระดับสูงสุดที่เลือก" (comma-list, เทียบ ScoreService::maxOf) ----

    private static function maxLevelExpr(string $col, int $max): string
    {
        $clean = "REPLACE(e.`$col`,' ','')";
        $parts = [];
        for ($i = $max; $i >= 1; $i--) {
            $parts[] = "IF(FIND_IN_SET('$i', $clean),$i,0)";
        }
        return 'GREATEST(' . implode(',', $parts) . ')';
    }

    /** สรุปสถานะรับรองรายเขต (drill-down เขตที่ยัง "ค้างรับรอง") — join master_school แบบ indexed */
    public static function certByDistrict(int $area, ?int $saoId, int $year, int $limit = 15): array
    {
        $c = self::cfg($area);
        // join master_school มีอยู่ (ต้อง group ตาม sao_code) จึงกรองเขตด้วย m.sao_code ตรงนี้ได้
        $rs = ''; $rp = [];
        if ($saoId !== null) { $rs = ' AND m.sao_code = ?'; $rp = [$saoId]; }
        $sql = "SELECT m.sao_code,
                       COALESCE(NULLIF(ms.sao_name,''), CONCAT('(ไม่ระบุเขต) sao_code=', COALESCE(m.sao_code, 'NULL'))) sao_name,
                       COUNT(*)                            done,
                       SUM(COALESCE(e.confirmstatus,0)=0)  pending,
                       SUM(COALESCE(e.confirmstatus,0)=1)  approved
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m  ON m.sc_id  = CONVERT(e.sc_id USING utf8)
                  LEFT JOIN master_sao    ms ON ms.sao_id = m.sao_code
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs
                 GROUP BY m.sao_code, ms.sao_name
                HAVING pending > 0
                 ORDER BY pending DESC
                 LIMIT " . (int) $limit;
        return Db::all($sql, array_merge([$year], $rp));
    }

    // ---- กลุ่มชาติพันธุ์ (เฉพาะพื้นที่สูง — highland_eval_hilltrib) ----

    /** @return array{top: array, groups: int, students: int, schools: int} */
    public static function ethnic(?int $saoId, int $year, int $limit = 10): array
    {
        $scids = self::saoScids($saoId);
        if ($scids === null)      { $scope = '';          $sp = []; }
        elseif ($scids === [])    { $scope = ' AND 1=0';  $sp = []; }
        else { $ph = implode(',', array_fill(0, count($scids), '?')); $scope = " AND h.sc_id IN ($ph)"; $sp = $scids; }

        $base = "FROM highland_eval_hilltrib h
                 LEFT JOIN hilltrib t ON t.ethnic_id = h.hilltrib
                WHERE h.acadyears = ? AND TRIM(COALESCE(t.ethnic,'')) <> '' $scope";

        $top = Db::all(
            "SELECT TRIM(t.ethnic) ethnic, SUM(h.hilltrib_number) students, COUNT(DISTINCT h.sc_id) schools
             $base GROUP BY ethnic ORDER BY students DESC LIMIT " . (int) $limit,
            array_merge([$year], $sp)
        );
        $sum = Db::one(
            "SELECT COUNT(DISTINCT TRIM(t.ethnic)) `groups`,
                    COALESCE(SUM(h.hilltrib_number),0) students,
                    COUNT(DISTINCT h.sc_id) schools $base",
            array_merge([$year], $sp)
        ) ?? [];

        return [
            'top'      => $top,
            'groups'   => (int) ($sum['groups'] ?? 0),
            'students' => (int) ($sum['students'] ?? 0),
            'schools'  => (int) ($sum['schools'] ?? 0),
        ];
    }

    /** ปีงบประมาณที่มีข้อมูลประเมิน (distinct acadyears รวมสองพื้นที่) — เรียงใหม่→เก่า */
    public static function availableYears(): array
    {
        $rows = Db::all(
            'SELECT acadyears y FROM highland_eval WHERE sum_score > 0
             UNION SELECT acadyears y FROM island_eval WHERE sum_score > 0
             ORDER BY y DESC'
        );
        return array_map(fn($r) => (int) $r['y'], $rows);
    }

    // ---- แถวดิบสำหรับวิเคราะห์สถิติ (StatService: U1/B2/B4) ----

    /**
     * ดึงตัวแปรเชิงตัวเลขของโรงเรียนที่ "ประเมินแล้ว" (sum_score>0) ตาม role/ปี
     * คอลัมน์ทั้งหมดเป็นค่าคงที่ภายใน (ไม่รับจากผู้ใช้) — กัน SQL injection
     * ใช้ทำ describe / correlation / group-mean ฝั่ง PHP (ผลถูก cache ในชั้น controller)
     */
    public static function analyticsRows(int $area, ?int $saoId, int $year): array
    {
        [$rs, $rp] = self::evalScope(self::saoScids($saoId));
        if ($area === 2) {
            $tbl  = 'island_eval';
            $cols = 'e.sum_score, e.island_type, e.stu_sum, e.distance_to_province,
                     e.citeria05, e.citeria06, e.citeria07, e.citeria08, e.citeria15';
        } else {
            $tbl  = 'highland_eval';
            $cols = 'e.sum_score, e.highland_type, e.highest, e.average_height, e.distance_to_province,
                     e.stu_sum, e.stu_hilltrib, e.stu_hilltrib_group, e.stu_sleep_sum, e.citeria13';
        }
        $sql = "SELECT $cols
                  FROM `$tbl` e
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL AND e.sum_score > 0 $rs";
        return Db::all($sql, array_merge([$year], $rp));
    }

    // ---- พิกัดโรงเรียนสำหรับแผนที่หมุด — ใช้ school_new ผ่าน CAST (eq_ref PK) ----

    /** คืนเฉพาะแถวที่พิกัดอยู่ในกรอบประเทศไทย (กันค่าขยะ); type = ระดับความยุ่งยาก */
    public static function pins(int $area, ?int $saoId, int $year, int $limit = 800): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::evalScope(self::saoScids($saoId));
        $prov = 'COALESCE(NULLIF(s.province,""), NULLIF(e.provinces,""))';
        $sql = "SELECT e.sc_id,
                       COALESCE(NULLIF(e.sc_names,''), s.sc_name) sc_name,
                       e.lat, e.lng, e.`{$c['type']}` type, e.sum_score,
                       $prov province
                  FROM `{$c['table']}` e
                  LEFT JOIN school_new s ON s.sc_id = CAST(e.sc_id AS CHAR)
                 WHERE e.acadyears = ?
                   AND e.lat <> '' AND e.lng <> ''
                   AND CAST(e.lat AS DECIMAL(12,7)) BETWEEN 5  AND 21
                   AND CAST(e.lng AS DECIMAL(12,7)) BETWEEN 96 AND 106
                   $rs
                 ORDER BY e.sum_score DESC
                 LIMIT " . (int) $limit;
        return Db::all($sql, array_merge([$year], $rp));
    }
}
