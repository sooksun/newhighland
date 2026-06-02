<?php
namespace App\Services;

/**
 * คิดคะแนนแบบประเมินโรงเรียนพื้นที่สูง (16 ข้อ รวม 100 คะแนน)
 * ตรรกะอ้างอิงจาก highland_eval_events.php :: BeforeEdit (เวอร์ชันที่ถูกต้อง)
 * ตรวจสอบความถูกต้องกับเรคคอร์ดจริง sc_id=1063020130 (acadyears 2567) = 68.14 / type 2
 *
 * คะแนนเต็มรายข้อ: 1=30, 2..6=5, 7=6, 8..10=3, 11..16=5  → รวม 100
 * เกณฑ์ผ่าน: sum_score >= 50 (50-59=type1, 60-69=type2, 70+=type3)
 */
class ScoreService
{
    /** ปัดทศนิยม 2 ตำแหน่ง (เทียบ format_number(x,2) เดิม) */
    private static function fmt(float $x): float
    {
        return round($x, 2);
    }

    /** ค่าสูงสุดจาก comma-list เช่น "1,2,3" -> 3 (ใช้กับข้อ 7-10 ที่เลือกได้หลายข้อ) */
    private static function maxOf(string $csv): int
    {
        $max = 0;
        foreach (explode(',', $csv) as $p) {
            $p = (int) trim($p);
            if ($p > $max) $max = $p;
        }
        return $max;
    }

    private static function map(int $key, array $table): float
    {
        return (float) ($table[$key] ?? 0);
    }

    /**
     * @param array $v   ค่าจาก highland_eval (citeria01..16, citeria041, stu_sum, average_height, acadyears)
     * @param array $hilltribRows แถวจาก highland_eval_hilltrib [['hilltrib_number'=>..], ...]
     * @return array     score01..16, sum_score, highland_type, citeria11, citeria12
     */
    public static function calcHighland(array $v, array $hilltribRows): array
    {
        $out = [];
        $stuSum = max(1, (int) ($v['stu_sum'] ?? 0));   // กัน division by zero
        $avg    = (float) ($v['average_height'] ?? 0);

        // ข้อ 1 — ระดับความสูง (เต็ม 30)
        $c01 = (float) ($v['citeria01'] ?? 0);
        if ($c01 >= 500 || $c01 >= $avg) {
            $c = min($c01, 500);
            $out['score01'] = self::fmt(15 + ($c * 15 / 500));
        } else {
            $out['score01'] = 0.0;
        }

        // ข้อ 2 — เขตติดต่อชายแดน (5)
        $out['score02'] = self::map((int) ($v['citeria02'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 2, 5 => 0]);

        // ข้อ 3 — เขตการปกครองส่วนท้องถิ่น (5)
        $out['score03'] = self::map((int) ($v['citeria03'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 0]);

        // ข้อ 4 — เส้นทางรถยนต์ 2 ล้อไปไม่ได้ (5); ถ้าตอบ "ไม่มี"(2) ระยะทาง=0
        $c041 = (float) ($v['citeria041'] ?? 0);
        if ((int) ($v['citeria04'] ?? 0) === 2) $c041 = 0;
        $out['score04'] = self::fmt(min($c041, 5));

        // ข้อ 5 — ระยะทางรวม (5), เพดาน 80 กม.
        $out['score05'] = self::fmt(min((float) ($v['citeria05'] ?? 0), 80) * 5 / 80);

        // ข้อ 6 — ขนส่งสาธารณะ (5)
        $out['score06'] = self::map((int) ($v['citeria06'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 1, 5 => 1]);

        // ข้อ 7 — แหล่งน้ำ (6) ใช้ค่าสูงสุด
        $out['score07'] = self::map(self::maxOf((string) ($v['citeria07'] ?? '')), [1 => 6, 2 => 5, 3 => 4, 4 => 3, 5 => 2, 6 => 1]);

        // ข้อ 8 — ไฟฟ้า (3)
        $out['score08'] = self::map(self::maxOf((string) ($v['citeria08'] ?? '')), [1 => 3, 2 => 0]);

        // ข้อ 9 — โทรศัพท์ (3)
        $out['score09'] = self::map(self::maxOf((string) ($v['citeria09'] ?? '')), [1 => 3, 2 => 2, 3 => 1, 4 => 0]);

        // ข้อ 10 — อินเทอร์เน็ต (3)
        $out['score10'] = self::map(self::maxOf((string) ($v['citeria10'] ?? '')), [1 => 3, 2 => 2, 3 => 1, 4 => 0]);

        // ข้อ 11 — ร้อยละนักเรียนชาติพันธุ์ (5) — คำนวณจาก hilltrib rows
        $sumHill = 0;
        foreach ($hilltribRows as $r) $sumHill += (int) ($r['hilltrib_number'] ?? 0);
        $pct11 = min($sumHill * 100 / $stuSum, 100);
        $out['citeria11']  = self::fmt($pct11);
        $out['score11']    = self::fmt($pct11 * 5 / 100);

        // ข้อ 12 — จำนวนกลุ่มชาติพันธุ์ (5) — นับจำนวนกลุ่ม เพดาน 5
        $groups = count($hilltribRows);
        $out['citeria12'] = $groups;
        $out['score12']   = self::fmt(min($groups, 5));

        // ข้อ 13 — นักเรียนยากจน (5), ร้อยละเพดาน 50
        $pct13 = min((float) ($v['citeria13'] ?? 0) * 100 / $stuSum, 50);
        $out['score13'] = self::fmt($pct13 * 5 / 50);

        // ข้อ 14 — นักเรียนพักนอน (5), เพดาน 50 คน
        $out['score14'] = self::fmt(min((float) ($v['citeria14'] ?? 0), 50) * 5 / 50);

        // ข้อ 15 — โรงเรียนสาขา/ห้องเรียนสาขา (5)
        $c15 = min((int) ($v['citeria15'] ?? 0), 3);
        $out['score15'] = self::map($c15, [1 => 3, 2 => 4, 3 => 5]);

        // ข้อ 16 — พื้นที่พิเศษกระทรวงการคลัง (5)
        $out['score16'] = self::map((int) ($v['citeria16'] ?? 0), [1 => 5, 2 => 0]);

        // รวมคะแนน
        $sum = 0.0;
        for ($i = 1; $i <= 16; $i++) $sum += $out[sprintf('score%02d', $i)];
        $out['sum_score'] = self::fmt($sum);

        // ระดับ
        if ($sum >= 70)      $out['highland_type'] = 3;
        elseif ($sum >= 60)  $out['highland_type'] = 2;
        elseif ($sum >= 50)  $out['highland_type'] = 1;
        else                 $out['highland_type'] = 0;

        $out['stu_hilltrib']       = $sumHill;
        $out['stu_hilltrib_group'] = $groups;

        return $out;
    }
}
