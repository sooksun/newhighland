<?php
namespace App\Models;

use App\Core\Db;

/**
 * สถิติสำหรับหน้า "รายงานสถิติ" (DashboardController) — รวม query แบบ aggregate ไว้ที่เดียว
 *
 * หลักการ:
 *  - กรองตาม role: สพฐ. (admin) = ทุกเขต ($saoId = null); เขต (sao) = เฉพาะสังกัดตน (master_school.sao_code)
 *    ใช้รูปแบบเดียวกับ HighlandEval::listForCert / Auth::canAccessSchool
 *  - รองรับ 2 ประเภทพื้นที่ผ่าน $area (1=พื้นที่สูง → highland_eval, 2=พื้นที่เกาะ → island_eval)
 *  - SQL portable (ไม่ใช้ window function/CTE) — เปอร์เซ็นต์คิดในชั้น view
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

    /** เงื่อนไขกรองตาม role สำหรับตาราง eval (JOIN master_school m) */
    private static function scope(?int $saoId): array
    {
        return $saoId === null ? ['', []] : [' AND m.sao_code = ?', [$saoId]];
    }

    // ---- การ์ด KPI (รวมพื้นที่สูง + เกาะ ในขอบเขตของผู้ใช้) ----

    /** จำนวนเป้าหมายจาก roster การคงอยู่ (school_confirm) */
    public static function targets(?int $saoId, int $year): array
    {
        $sql = 'SELECT COUNT(*) total, SUM(area_type=1) high, SUM(area_type=2) island
                  FROM school_confirm c WHERE c.acadyears = ?';
        $p = [$year];
        if ($saoId !== null) { $sql .= ' AND c.sao_id = ?'; $p[] = $saoId; }
        $r = Db::one($sql, $p) ?? [];
        return ['total' => (int)($r['total'] ?? 0), 'high' => (int)($r['high'] ?? 0), 'island' => (int)($r['island'] ?? 0)];
    }

    /** ดำเนินการ/ผ่านเกณฑ์/เขตรับรอง/สพฐ.ประกาศ — รวมทั้งสองตาราง (UNION ALL) */
    public static function kpis(?int $saoId, int $year): array
    {
        [$rs, $rp] = self::scope($saoId);
        $leg = "SELECT e.sum_score AS s,
                       (COALESCE(e.confirmstatus,0)=1) AS c,
                       (COALESCE(e.spt_commit,0)=1)    AS p
                  FROM %s e LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs";
        $sql = 'SELECT COUNT(*) done,
                       SUM(t.s >= 50) passed,
                       SUM(t.c)       sao_certed,
                       SUM(t.p)       spt_announced
                  FROM ( ' . sprintf($leg, 'highland_eval')
                       . ' UNION ALL ' . sprintf($leg, 'island_eval') . ' ) t';
        $params = array_merge([$year], $rp, [$year], $rp);
        $r = Db::one($sql, $params) ?? [];
        return [
            'done'          => (int)($r['done'] ?? 0),
            'passed'        => (int)($r['passed'] ?? 0),
            'sao_certed'    => (int)($r['sao_certed'] ?? 0),
            'spt_announced' => (int)($r['spt_announced'] ?? 0),
        ];
    }

    // ---- Funnel (ตามประเภทพื้นที่ที่เลือก) ----

    public static function funnel(int $area, ?int $saoId, int $year): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $sql = "SELECT
                  SUM({$c['pinned']})                       pinned,
                  SUM(e.sum_score IS NOT NULL)              evaluated,
                  SUM(e.sum_score >= 50)                    passed,
                  SUM(COALESCE(e.confirmstatus,0)=1)        sao_certed,
                  SUM(COALESCE(e.spt_commit,0)=1)           spt_announced
                FROM `{$c['table']}` e
                LEFT JOIN master_school m ON m.sc_id = e.sc_id
                WHERE e.acadyears = ? $rs";
        $r = Db::one($sql, array_merge([$year], $rp)) ?? [];
        return [
            'pinned'        => (int)($r['pinned'] ?? 0),
            'evaluated'     => (int)($r['evaluated'] ?? 0),
            'passed'        => (int)($r['passed'] ?? 0),
            'sao_certed'    => (int)($r['sao_certed'] ?? 0),
            'spt_announced' => (int)($r['spt_announced'] ?? 0),
        ];
    }

    // ---- โดนัทระดับความยุ่งยาก (highland_type / island_type) ----

    /** คืน [0=>n,1=>n,2=>n,3=>n] */
    public static function typeDist(int $area, ?int $saoId, int $year): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $sql = "SELECT e.`{$c['type']}` lvl, COUNT(*) n
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs
                 GROUP BY e.`{$c['type']}`";
        $out = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        foreach (Db::all($sql, array_merge([$year], $rp)) as $r) {
            $l = (int) $r['lvl'];
            if (isset($out[$l])) $out[$l] = (int) $r['n'];
        }
        return $out;
    }

    // ---- ฮิสโทแกรมคะแนนรวม ----

    /** คืน [ '<50'=>n,'50-59'=>n,'60-69'=>n,'70-79'=>n,'80+'=>n ] */
    public static function scoreHist(int $area, ?int $saoId, int $year): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $sql = "SELECT CASE WHEN e.sum_score < 50 THEN 0
                            WHEN e.sum_score < 60 THEN 1
                            WHEN e.sum_score < 70 THEN 2
                            WHEN e.sum_score < 80 THEN 3
                            ELSE 4 END bucket,
                       COUNT(*) n
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs
                 GROUP BY bucket";
        $labels = ['<50', '50-59', '60-69', '70-79', '80+'];
        $out = array_fill_keys($labels, 0);
        foreach (Db::all($sql, array_merge([$year], $rp)) as $r) {
            $out[$labels[(int) $r['bucket']] ?? '<50'] = (int) $r['n'];
        }
        return $out;
    }

    // ---- อันดับจังหวัด (ผ่านเกณฑ์มากสุด) ----

    public static function topProvinces(int $area, ?int $saoId, int $year, int $limit = 8): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $prov = 'COALESCE(NULLIF(s.province,""), NULLIF(e.provinces,""), m.provinces)';
        $sql = "SELECT $prov province, COUNT(*) passed
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m ON m.sc_id = e.sc_id
                  LEFT JOIN school_new   s ON s.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score >= 50 $rs
                 GROUP BY province
                HAVING province IS NOT NULL AND province <> ''
                 ORDER BY passed DESC
                 LIMIT " . (int) $limit;
        return Db::all($sql, array_merge([$year], $rp));
    }

    // ---- สาธารณูปโภค (น้ำ/ไฟ/โทร/เน็ต) ----

    /** สร้างนิพจน์ "ระดับสูงสุดที่เลือก" สำหรับข้อแบบ comma-list (เทียบ ScoreService::maxOf) */
    private static function maxLevelExpr(string $col, int $max): string
    {
        $clean = "REPLACE(e.`$col`,' ','')";
        $parts = [];
        for ($i = $max; $i >= 1; $i--) {
            $parts[] = "IF(FIND_IN_SET('$i', $clean),$i,0)";
        }
        return 'GREATEST(' . implode(',', $parts) . ')';
    }

    /**
     * @return array key => ['nn'=>'07','max'=>6,'levels'=>[1=>n,...],'answered'=>int]
     */
    public static function utilities(int $area, ?int $saoId, int $year): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $multi = ($area === 1);   // พื้นที่สูง: ข้อ 07-10 เลือกได้หลายข้อ
        $out = [];
        foreach (self::utilConfig($area) as $key => $nn) {
            $max = self::UTIL_MAX[$key];
            $col = 'citeria' . $nn;
            $expr = $multi
                ? self::maxLevelExpr($col, $max)
                : "COALESCE(CAST(NULLIF(e.`$col`,'') AS UNSIGNED),0)";
            $sql = "SELECT $expr lvl, COUNT(*) n
                      FROM `{$c['table']}` e
                      LEFT JOIN master_school m ON m.sc_id = e.sc_id
                     WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs
                     GROUP BY lvl";
            $levels = array_fill(1, $max, 0);
            $answered = 0;
            foreach (Db::all($sql, array_merge([$year], $rp)) as $r) {
                $l = (int) $r['lvl'];
                if ($l >= 1 && $l <= $max) { $levels[$l] = (int) $r['n']; $answered += (int) $r['n']; }
            }
            $out[$key] = ['nn' => $nn, 'max' => $max, 'levels' => $levels, 'answered' => $answered];
        }
        return $out;
    }

    /** สรุปสถานะรับรองรายเขต (drill-down เขตที่ยัง "ค้างรับรอง") + ชื่อเขตจาก master_sao */
    public static function certByDistrict(int $area, ?int $saoId, int $year, int $limit = 15): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $sql = "SELECT m.sao_code,
                       COALESCE(NULLIF(ms.sao_name,''), CONCAT('(ไม่ระบุเขต) sao_code=', COALESCE(m.sao_code, 'NULL'))) sao_name,
                       COUNT(*)                            done,
                       SUM(COALESCE(e.confirmstatus,0)=0)  pending,
                       SUM(COALESCE(e.confirmstatus,0)=1)  approved
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m  ON m.sc_id  = e.sc_id
                  LEFT JOIN master_sao    ms ON ms.sao_id = m.sao_code
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL $rs
                 GROUP BY m.sao_code, ms.sao_name
                HAVING pending > 0
                 ORDER BY pending DESC
                 LIMIT " . (int) $limit;
        return Db::all($sql, array_merge([$year], $rp));
    }

    // ---- กลุ่มชาติพันธุ์ (เฉพาะพื้นที่สูง — highland_eval_hilltrib) ----

    /**
     * @return array{top: array, groups: int, students: int, schools: int}
     *   top = [['ethnic'=>..,'students'=>..,'schools'=>..], ...] เรียงมาก→น้อย
     */
    public static function ethnic(?int $saoId, int $year, int $limit = 10): array
    {
        [$rs, $rp] = $saoId === null ? ['', []] : [' AND m.sao_code = ?', [$saoId]];
        $base = "FROM highland_eval_hilltrib h
                 LEFT JOIN hilltrib      t ON t.ethnic_id = h.hilltrib
                 LEFT JOIN master_school m ON m.sc_id     = h.sc_id
                WHERE h.acadyears = ? AND TRIM(COALESCE(t.ethnic,'')) <> '' $rs";

        $top = Db::all(
            "SELECT TRIM(t.ethnic) ethnic, SUM(h.hilltrib_number) students, COUNT(DISTINCT h.sc_id) schools
             $base GROUP BY ethnic ORDER BY students DESC LIMIT " . (int) $limit,
            array_merge([$year], $rp)
        );
        $sum = Db::one(
            "SELECT COUNT(DISTINCT TRIM(t.ethnic)) `groups`,
                    COALESCE(SUM(h.hilltrib_number),0) students,
                    COUNT(DISTINCT h.sc_id) schools $base",
            array_merge([$year], $rp)
        ) ?? [];

        return [
            'top'      => $top,
            'groups'   => (int) ($sum['groups'] ?? 0),
            'students' => (int) ($sum['students'] ?? 0),
            'schools'  => (int) ($sum['schools'] ?? 0),
        ];
    }

    // ---- พิกัดโรงเรียนสำหรับแผนที่หมุด ----

    /** คืนเฉพาะแถวที่พิกัดอยู่ในกรอบประเทศไทย (กันค่าขยะ); type = ระดับความยุ่งยาก */
    public static function pins(int $area, ?int $saoId, int $year, int $limit = 800): array
    {
        $c = self::cfg($area);
        [$rs, $rp] = self::scope($saoId);
        $prov = 'COALESCE(NULLIF(s.province,""), NULLIF(e.provinces,""), m.provinces)';
        $sql = "SELECT e.sc_id,
                       COALESCE(NULLIF(e.sc_names,''), m.sc_name) sc_name,
                       e.lat, e.lng, e.`{$c['type']}` type, e.sum_score,
                       $prov province
                  FROM `{$c['table']}` e
                  LEFT JOIN master_school m ON m.sc_id = e.sc_id
                  LEFT JOIN school_new   s ON s.sc_id = e.sc_id
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
