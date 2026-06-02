<?php
use App\Services\IslandScoreService;

T::group('IslandScoreService (พื้นที่เกาะ) — สูตรคะแนน 15 ข้อ');

// ---- normal: เรคคอร์ดจริง sc 1091560035 (acadyears 2568) = 71.38 / type 3 ----
$v = [
    'citeria02' => 1, 'citeria03' => 1, 'citeria04' => 2, 'citeria05' => 10.52, 'citeria06' => 8.01,
    'citeria07' => 45, 'citeria08' => 100, 'citeria09' => 2, 'citeria10' => 2, 'citeria11' => 2,
    'citeria12' => 4, 'citeria13' => 3, 'citeria14' => 1, 'citeria15' => 54, 'stu_sum' => 76,
];
$r = IslandScoreService::calcIsland($v);
T::close(10, $r['score02'], 'ข้อ2.1 เขตปกครอง=1 → 10');
T::close(16, $r['score03'], 'ข้อ2.2 ลักษณะที่ตั้ง=1 → 16');
T::close(15, $r['score04'], 'ข้อ3.1 พาหนะ=2 → 15');
T::close(2.63, $r['score05'], 'ข้อ3.2 ทางบก 10.52 → 2.63');
T::close(2, $r['score06'], 'ข้อ3.3 ทางน้ำ 8.01 → 2.00');
T::close(3.75, $r['score07'], 'ข้อ3.4 เวลา 45 → 3.75');
T::close(1, $r['score08'], 'ข้อ3.5 ค่าโดยสาร 100 → 1');
T::close(8, $r['score11'], 'ข้อ4.2 น้ำ=2 → 8');
T::close(2, $r['score15'], 'ข้อ5.2 ยากจน 54/76=71% cap50 → 2');
T::close(71.38, $r['sum_score'], 'รวม = 71.38');
T::eq(3, $r['island_type'], 'type = 3 (70+)');
T::eq(0, $r['score01'], 'score01 = 0 (ข้อ 1.1 เป็นด่านคัดกรอง ไม่คิดคะแนน)');

// ---- option maps ขอบบน/ล่าง ----
$o = fn($k, $val) => IslandScoreService::calcIsland(['stu_sum' => 100, $k => $val]);
T::close(20, $o('citeria04', 1)['score04'], 'พาหนะ=1 (เรือไม่ประจำทาง) → 20');
T::close(0, $o('citeria04', 5)['score04'], 'พาหนะ=5 (รถ) → 0');
T::close(10, $o('citeria11', 1)['score11'], 'น้ำ=1 → 10');
T::close(0, $o('citeria11', 6)['score11'], 'น้ำ=6 (ประปาภูมิภาค) → 0');
T::close(5, $o('citeria10', 1)['score10'], 'ไฟ=1 (พลังงานทางเลือก) → 5');
T::close(0, $o('citeria10', 2)['score10'], 'ไฟ=2 (ภูมิภาค) → 0');

// ---- numeric caps ----
$b = ['stu_sum' => 100];
T::close(5, IslandScoreService::calcIsland($b + ['citeria05' => 999])['score05'], 'ทางบก cap 20 → 5');
T::close(5, IslandScoreService::calcIsland($b + ['citeria07' => 999])['score07'], 'เวลา cap 60 → 5');
T::close(5, IslandScoreService::calcIsland($b + ['citeria08' => 9999])['score08'], 'ค่าโดยสาร cap 500 → 5');
T::close(2, IslandScoreService::calcIsland($b + ['citeria15' => 999])['score15'], 'ยากจน cap 50% → 2');

// ---- failure-safe div by zero ----
$z = IslandScoreService::calcIsland(['stu_sum' => 0, 'citeria15' => 5]);
T::true(is_numeric($z['sum_score']), 'stu_sum=0 ไม่ crash');

// ---- type tiers ----
// score03(16)+score04(20)+score11(10)+score02(10)+score09(?) ... สร้างชุดควบคุม
$mk = fn(array $x) => IslandScoreService::calcIsland(array_merge(['stu_sum' => 100], $x));
$t49 = $mk(['citeria02' => 1, 'citeria03' => 1, 'citeria04' => 4]); // 10+16+5 = 31
T::eq(0, $t49['island_type'], 'รวม 31 → type 0');
$t60 = $mk(['citeria03' => 1, 'citeria04' => 1, 'citeria11' => 1, 'citeria02' => 4]); // 16+20+10+4 = 50
T::close(50, $t60['sum_score'], 'ชุดทดสอบรวม = 50');
T::eq(1, $t60['island_type'], 'sum 50 → type 1');
