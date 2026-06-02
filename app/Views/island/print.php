<?php
/** @var array $ctx; @var array $e */
use App\Controllers\IslandEvalController;
use App\Models\IslandOption;

$opt = function (string $nn, $v) { return IslandOption::SETS[$nn][(int)$v] ?? '-'; };
$sc = fn($k) => number_format((float)($e[$k] ?? 0), 2);
$rows = [
  ['2.1 เขตการปกครอง', $opt('02', $e['citeria02'] ?? 0), $sc('score02'), 10],
  ['2.2 ลักษณะที่ตั้ง', $opt('03', $e['citeria03'] ?? 0), $sc('score03'), 16],
  ['3.1 พาหนะ', $opt('04', $e['citeria04'] ?? 0), $sc('score04'), 20],
  ['3.2 ระยะทางทางบก', ($e['citeria05'] ?? 0).' กม.', $sc('score05'), 5],
  ['3.3 ระยะทางทางน้ำ', ($e['citeria06'] ?? 0).' กม.', $sc('score06'), 5],
  ['3.4 เวลาทางน้ำ', ($e['citeria07'] ?? 0).' นาที', $sc('score07'), 5],
  ['3.5 ค่าโดยสาร', ($e['citeria08'] ?? 0).' บาท', $sc('score08'), 5],
  ['3.6 การเดินทางต่อ', $opt('09', $e['citeria09'] ?? 0), $sc('score09'), 5],
  ['4.1 ระบบไฟฟ้า', $opt('10', $e['citeria10'] ?? 0), $sc('score10'), 5],
  ['4.2 แหล่งน้ำ', $opt('11', $e['citeria11'] ?? 0), $sc('score11'), 10],
  ['4.3 อินเทอร์เน็ต', $opt('12', $e['citeria12'] ?? 0), $sc('score12'), 5],
  ['4.4 โทรศัพท์', $opt('13', $e['citeria13'] ?? 0), $sc('score13'), 5],
  ['5.1 พื้นที่พิเศษคลัง', $opt('14', $e['citeria14'] ?? 0), $sc('score14'), 2],
  ['5.2 นักเรียนยากจน', ($e['citeria15'] ?? 0).' คน', $sc('score15'), 2],
];
?>
<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>แบบประเมินเกาะ <?= View::e($ctx['sc_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'Sarabun',sans-serif;font-size:14px}@media print{.noprint{display:none}}</style></head>
<body class="p-4">
<div class="noprint mb-3 text-end"><button class="btn btn-primary btn-sm" onclick="window.print()">พิมพ์</button></div>
<div class="text-center mb-3"><h4 class="fw-bold mb-1">แบบคัดกรองโรงเรียนพื้นที่เกาะ</h4><div>ปีงบประมาณ <?= View::e(App::acadYear()) ?></div></div>
<table class="table table-sm table-borderless w-auto mb-3"><tbody>
  <tr><td class="fw-bold">โรงเรียน</td><td><?= View::e($ctx['sc_name']) ?> (<?= View::e($ctx['sc_id']) ?>)</td></tr>
  <tr><td class="fw-bold">สังกัด</td><td><?= View::e($e['sao_names'] ?? '') ?></td></tr>
  <tr><td class="fw-bold">จังหวัด</td><td><?= View::e($ctx['province']) ?></td></tr>
  <tr><td class="fw-bold">เป็นเกาะ</td><td><?= ((int)($e['citeria01']??0)===1)?'ใช่':'ไม่ใช่' ?></td></tr>
  <tr><td class="fw-bold">นักเรียนรวม</td><td><?= (int)($e['stu_sum'] ?? 0) ?> คน</td></tr>
</tbody></table>
<table class="table table-sm table-bordered">
  <thead class="table-light"><tr><th>รายการ</th><th>ข้อมูล</th><th class="text-end">คะแนน</th><th class="text-end">เต็ม</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?><tr><td><?= View::e($r[0]) ?></td><td><?= View::e($r[1]) ?></td><td class="text-end"><?= View::e($r[2]) ?></td><td class="text-end text-muted"><?= View::e($r[3]) ?></td></tr><?php endforeach; ?></tbody>
  <tfoot>
    <tr class="fw-bold"><td colspan="2" class="text-end">คะแนนรวม</td><td class="text-end"><?= number_format((float)($e['sum_score'] ?? 0), 2) ?></td><td class="text-end">100</td></tr>
    <tr><td colspan="4">ผลการจำแนก: <b><?= View::e(IslandEvalController::typeLabel((int)($e['island_type'] ?? 0))) ?></b></td></tr>
  </tfoot>
</table>
</body></html>
