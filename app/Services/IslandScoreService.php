<?php
namespace App\Services;

/**
 * คิดคะแนนแบบประเมินโรงเรียนพื้นที่เกาะ (15 ข้อ รวม 100 คะแนน)
 * ตรรกะอ้างอิงจาก island_eval_events.php :: BeforeEdit
 * ตรวจสอบกับเรคคอร์ดจริง sc_id=1091560035 (acadyears 2568) = 71.38 / type 3
 *
 * โครงสร้าง (ภาคผนวก ค):
 *   1.1 เป็นเกาะ (citeria01) = ด่านคัดกรอง (ใช่/ไม่ใช่ — ไม่คิดคะแนน)
 *   2.1 เขตปกครอง (citeria02)=10   2.2 ลักษณะที่ตั้ง (citeria03)=16
 *   3.1 พาหนะ (citeria04)=20  3.2 ทางบก กม. (citeria05)=5  3.3 ทางน้ำ กม. (citeria06)=5
 *   3.4 เวลา นาที (citeria07)=5  3.5 ค่าโดยสาร บาท (citeria08)=5  3.6 เดินทางต่อ (citeria09)=5
 *   4.1 ไฟฟ้า (citeria10)=5  4.2 น้ำ (citeria11)=10  4.3 เน็ต (citeria12)=5  4.4 โทร (citeria13)=5
 *   5.1 พื้นที่พิเศษคลัง (citeria14)=2  5.2 นักเรียนยากจน คน (citeria15)=2
 */
class IslandScoreService
{
    private static function fmt(float $x): float { return round($x, 2); }
    private static function map(int $key, array $t): float { return (float) ($t[$key] ?? 0); }

    public static function calcIsland(array $v): array
    {
        $out = [];
        $stuSum = max(1, (int) ($v['stu_sum'] ?? 0));

        $out['score02'] = self::map((int) ($v['citeria02'] ?? 0), [1 => 10, 2 => 8, 3 => 6, 4 => 4]);
        $out['score03'] = self::map((int) ($v['citeria03'] ?? 0), [1 => 16, 2 => 0]);
        $out['score04'] = self::map((int) ($v['citeria04'] ?? 0), [1 => 20, 2 => 15, 3 => 10, 4 => 5, 5 => 0]);
        $out['score05'] = self::fmt(min((float) ($v['citeria05'] ?? 0), 20) * 5 / 20);   // ทางบก เพดาน 20 กม.
        $out['score06'] = self::fmt(min((float) ($v['citeria06'] ?? 0), 20) * 5 / 20);   // ทางน้ำ เพดาน 20 กม.
        $out['score07'] = self::fmt(min((float) ($v['citeria07'] ?? 0), 60) * 5 / 60);   // เวลา เพดาน 60 นาที
        $out['score08'] = self::fmt(min((float) ($v['citeria08'] ?? 0), 500) * 5 / 500); // ค่าโดยสาร เพดาน 500 บาท
        $out['score09'] = self::map((int) ($v['citeria09'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 2, 5 => 1]);
        $out['score10'] = self::map((int) ($v['citeria10'] ?? 0), [1 => 5, 2 => 0]);
        $out['score11'] = self::map((int) ($v['citeria11'] ?? 0), [1 => 10, 2 => 8, 3 => 6, 4 => 4, 5 => 2, 6 => 0]);
        $out['score12'] = self::map((int) ($v['citeria12'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 2]);
        $out['score13'] = self::map((int) ($v['citeria13'] ?? 0), [1 => 5, 2 => 4, 3 => 3, 4 => 2]);
        $out['score14'] = self::map((int) ($v['citeria14'] ?? 0), [1 => 2, 2 => 0]);

        // 5.2 นักเรียนยากจน — ร้อยละของนักเรียนทั้งหมด เพดาน 50% → เต็ม 2
        $pct = min((float) ($v['citeria15'] ?? 0) * 100 / $stuSum, 50);
        $out['score15'] = self::fmt($pct * 2 / 50);

        $sum = 0.0;
        foreach (['02','03','04','05','06','07','08','09','10','11','12','13','14','15'] as $nn) {
            $sum += $out['score' . $nn];
        }
        $out['score01']   = 0;            // ข้อ 1.1 เป็นด่านคัดกรอง ไม่คิดคะแนน
        $out['sum_score'] = self::fmt($sum);

        if ($sum >= 70)     $out['island_type'] = 3;
        elseif ($sum >= 60) $out['island_type'] = 2;
        elseif ($sum >= 50) $out['island_type'] = 1;
        else                $out['island_type'] = 0;

        return $out;
    }
}
