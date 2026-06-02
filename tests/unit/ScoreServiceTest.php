<?php
use App\Services\ScoreService;

T::group('ScoreService (พื้นที่สูง) — สูตรคะแนน 16 ข้อ');

// ---- normal: เรคคอร์ดจริง sc 1063020130 (acadyears 2567) = 68.14 / type 2 ----
$v = [
    'citeria01' => 500, 'average_height' => 258, 'citeria02' => 3, 'citeria03' => 1,
    'citeria04' => 2, 'citeria041' => 0, 'citeria05' => 136, 'citeria06' => 1,
    'citeria07' => '1,2,3', 'citeria08' => 2, 'citeria09' => 3, 'citeria10' => '3,4',
    'citeria13' => 53, 'citeria14' => 0, 'citeria15' => 0, 'citeria16' => 1, 'stu_sum' => 169,
];
$hill = [['hilltrib_number' => 16], ['hilltrib_number' => 153]];
$r = ScoreService::calcHighland($v, $hill);
T::close(30, $r['score01'], 'ข้อ1 ความสูง = 30');
T::close(3, $r['score02'], 'ข้อ2 = 3');
T::close(5, $r['score05'], 'ข้อ5 ระยะทาง 136>80 → cap 5');
T::close(4, $r['score07'], 'ข้อ7 max(1,2,3)=3 → 4');
T::close(0, $r['score10'], 'ข้อ10 max(3,4)=4 → 0');
T::close(5, $r['score11'], 'ข้อ11 ร้อยละ100 → 5');
T::close(2, $r['score12'], 'ข้อ12 จำนวนกลุ่ม=2');
T::close(3.14, $r['score13'], 'ข้อ13 53/169 → 3.14');
T::close(68.14, $r['sum_score'], 'รวม = 68.14');
T::eq(2, $r['highland_type'], 'type = 2 (60–69)');
T::eq(169, $r['stu_hilltrib'], 'รวมนักเรียนชาติพันธุ์ = 169');

// ---- gate ข้อ 1 (ความสูง) ----
$g = fn($c01, $avg) => ScoreService::calcHighland(
    ['citeria01' => $c01, 'average_height' => $avg, 'stu_sum' => 100], []
)['score01'];
T::close(0, $g(200, 300), 'ข้อ1: 200<เฉลี่ย300 และ<500 → 0 (พื้นราบ)');
T::close(24, $g(300, 300), 'ข้อ1: =เฉลี่ย → 15+300*15/500 = 24');
T::close(30, $g(900, 300), 'ข้อ1: 900 cap 500 → 30');
T::close(30, $g(500, 300), 'ข้อ1: =500 → 30');

// ---- ข้อ 4: ตอบ "ไม่มี"(2) บังคับ citeria041=0 ----
$s4 = fn($c04, $c041) => ScoreService::calcHighland(
    ['citeria04' => $c04, 'citeria041' => $c041, 'stu_sum' => 100], []
)['score04'];
T::close(0, $s4(2, 10), 'ข้อ4: ตอบไม่มี → 0 แม้กรอกระยะทาง');
T::close(5, $s4(1, 10), 'ข้อ4: มี + ระยะทาง 10 → cap 5');
T::close(3, $s4(1, 3), 'ข้อ4: มี + ระยะทาง 3 → 3');

// ---- caps / edge ----
$base = ['stu_sum' => 100];
T::close(5, ScoreService::calcHighland($base + ['citeria05' => 999], [])['score05'], 'ข้อ5 cap 80km → 5');
T::close(5, ScoreService::calcHighland($base + ['citeria14' => 999], [])['score14'], 'ข้อ14 cap 50 → 5');
T::close(5, ScoreService::calcHighland(['stu_sum' => 100, 'citeria13' => 999], [])['score13'], 'ข้อ13 ร้อยละ cap 50 → 5');

// ข้อ 15: switch ตามจำนวนสาขา (1→3,2→4,3+→5)
T::close(3, ScoreService::calcHighland($base + ['citeria15' => 1], [])['score15'], 'ข้อ15: 1 สาขา → 3');
T::close(4, ScoreService::calcHighland($base + ['citeria15' => 2], [])['score15'], 'ข้อ15: 2 สาขา → 4');
T::close(5, ScoreService::calcHighland($base + ['citeria15' => 9], [])['score15'], 'ข้อ15: 9 สาขา cap 3 → 5');
T::close(0, ScoreService::calcHighland($base + ['citeria15' => 0], [])['score15'], 'ข้อ15: 0 สาขา → 0');

// comma list ว่าง / ค่าผิด
T::close(0, ScoreService::calcHighland($base + ['citeria07' => ''], [])['score07'], 'ข้อ7 ว่าง → 0');
T::close(6, ScoreService::calcHighland($base + ['citeria07' => '1'], [])['score07'], 'ข้อ7 =1 → 6');

// ---- failure-safe: stu_sum = 0 ต้องไม่ division by zero ----
$z = ScoreService::calcHighland(['stu_sum' => 0, 'citeria13' => 5], [['hilltrib_number' => 5]]);
T::true(is_numeric($z['sum_score']), 'stu_sum=0 ไม่ crash (กัน /0)');
T::close(5, $z['score11'], 'stu_sum=0 → ใช้ตัวหาร 1 → score11 = 5');

// ---- type tiers (ขอบเขต) ----
$mk = function (array $extra) { return ScoreService::calcHighland(array_merge(['stu_sum' => 100], $extra), []); };
T::eq(0, $mk(['citeria02' => 5])['highland_type'], 'รวมต่ำ → type 0');
// 50 พอดี: score01(30 ผ่าน citeria01=500,avg0) + score03 5 + score02 5 + score06 5 + score16 5 = 50
$fifty = $mk(['citeria01' => 500, 'average_height' => 0, 'citeria02' => 1, 'citeria03' => 1, 'citeria06' => 1, 'citeria16' => 1]);
T::close(50, $fifty['sum_score'], 'ชุดทดสอบรวม = 50 พอดี');
T::eq(1, $fifty['highland_type'], 'sum 50 → type 1');
