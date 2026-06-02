<?php
/** @var array $ctx; @var array $e; @var array $options; @var array $hilltribRows */
use App\Controllers\HighlandEvalController;

$labelOf = function (string $nn, $val) use ($options) {
    foreach ($options[$nn] ?? [] as $o) if ((int)$o['id'] === (int)$val) return $o['label'];
    return '-';
};
$labelsCsv = function (string $nn, $csv) use ($options) {
    $sel = array_filter(array_map('intval', explode(',', (string)$csv)));
    $out = [];
    foreach ($options[$nn] ?? [] as $o) if (in_array((int)$o['id'], $sel, true)) $out[] = $o['label'];
    return $out ? implode('; ', $out) : '-';
};
$sc = fn($k) => number_format((float)($e[$k] ?? 0), 2);
$rows = [
  ['1. ระดับความสูง ณ จุดสูงสุด', ($e['citeria01'] ?? 0).' ม.', $sc('score01'), 30],
  ['2. เขตติดต่อชายแดน', $labelOf('02', $e['citeria02'] ?? 0), $sc('score02'), 5],
  ['3. เขตการปกครองส่วนท้องถิ่น', $labelOf('03', $e['citeria03'] ?? 0), $sc('score03'), 5],
  ['4. เส้นทางรถ 2 ล้อไปไม่ได้', $labelOf('04', $e['citeria04'] ?? 0).' ('.($e['citeria041'] ?? 0).' กม.)', $sc('score04'), 5],
  ['5. ระยะทางรวม', ($e['citeria05'] ?? 0).' กม.', $sc('score05'), 5],
  ['6. ขนส่งสาธารณะ', $labelOf('06', $e['citeria06'] ?? 0), $sc('score06'), 5],
  ['7. แหล่งน้ำ', $labelsCsv('07', $e['citeria07'] ?? ''), $sc('score07'), 6],
  ['8. ระบบไฟฟ้า', $labelsCsv('08', $e['citeria08'] ?? ''), $sc('score08'), 3],
  ['9. ระบบโทรศัพท์', $labelsCsv('09', $e['citeria09'] ?? ''), $sc('score09'), 3],
  ['10. ระบบอินเทอร์เน็ต', $labelsCsv('10', $e['citeria10'] ?? ''), $sc('score10'), 3],
  ['11. ร้อยละนักเรียนชาติพันธุ์', ($e['citeria11'] ?? 0).' %', $sc('score11'), 5],
  ['12. จำนวนกลุ่มชาติพันธุ์', ($e['citeria12'] ?? 0).' กลุ่ม', $sc('score12'), 5],
  ['13. นักเรียนยากจน/ยากจนพิเศษ', ($e['citeria13'] ?? 0).' คน', $sc('score13'), 5],
  ['14. นักเรียนพักนอน', ($e['citeria14'] ?? 0).' คน', $sc('score14'), 5],
  ['15. โรงเรียนสาขา/ห้องเรียนสาขา', ($e['citeria15'] ?? 0), $sc('score15'), 5],
  ['16. พื้นที่พิเศษกระทรวงการคลัง', $labelOf('16', $e['citeria16'] ?? 0), $sc('score16'), 5],
];
?>
<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>แบบประเมิน <?= View::e($ctx['sc_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'Sarabun',sans-serif;font-size:14px}@media print{.noprint{display:none}}</style></head>
<body class="p-4">
<div class="noprint mb-3 text-end"><button class="btn btn-primary btn-sm" onclick="window.print()">พิมพ์</button></div>
<div class="text-center mb-3">
  <h4 class="fw-bold mb-1">แบบประเมินโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดาร</h4>
  <div>ปีงบประมาณ <?= View::e(App::acadYear()) ?></div>
</div>
<table class="table table-sm table-borderless w-auto mb-3"><tbody>
  <tr><td class="fw-bold">โรงเรียน</td><td><?= View::e($ctx['sc_name']) ?> (<?= View::e($ctx['sc_id']) ?>)</td></tr>
  <tr><td class="fw-bold">สังกัด</td><td><?= View::e($e['sao_names'] ?? '') ?></td></tr>
  <tr><td class="fw-bold">จังหวัด</td><td><?= View::e($ctx['province']) ?></td></tr>
  <tr><td class="fw-bold">พิกัด</td><td><?= View::e($e['lat'] ?? '') ?>, <?= View::e($e['lng'] ?? '') ?></td></tr>
  <tr><td class="fw-bold">นักเรียนรวม</td><td><?= (int)($e['stu_sum'] ?? 0) ?> คน</td></tr>
</tbody></table>

<?php if ($hilltribRows): ?>
<table class="table table-sm table-bordered w-auto mb-3"><thead><tr><th>กลุ่มชาติพันธุ์</th><th>จำนวน</th></tr></thead><tbody>
<?php foreach ($hilltribRows as $r): ?><tr><td><?= View::e($r['ethnic']) ?></td><td class="text-end"><?= (int)$r['hilltrib_number'] ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<table class="table table-sm table-bordered">
  <thead class="table-light"><tr><th>รายการ</th><th>ข้อมูล</th><th class="text-end">คะแนน</th><th class="text-end">เต็ม</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= View::e($r[0]) ?></td><td><?= View::e($r[1]) ?></td><td class="text-end"><?= View::e($r[2]) ?></td><td class="text-end text-muted"><?= View::e($r[3]) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr class="fw-bold"><td colspan="2" class="text-end">คะแนนรวม</td><td class="text-end"><?= number_format((float)($e['sum_score'] ?? 0), 2) ?></td><td class="text-end">100</td></tr>
    <tr><td colspan="4">ผลการจำแนก: <b><?= View::e(HighlandEvalController::typeLabel((int)($e['highland_type'] ?? 0))) ?></b></td></tr>
  </tfoot>
</table>
</body></html>
