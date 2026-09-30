<?php
/**
 * ดูข้อมูลรายโรงเรียน (อ่านอย่างเดียว) — สำหรับ สพท./สพฐ. ในขั้นรับรองผลประเมินพื้นที่สูง
 * @var array $ctx; @var array $e; @var array $options; @var array $hilltribRows; @var string $back
 */
use App\Controllers\HighlandEvalController;

$scId   = (int) $ctx['sc_id'];
$backTo = App::url('highland/cert' . ($back !== '' ? '?' . $back : ''));

$v   = fn($k, $d = '—') => ($e[$k] ?? '') !== '' ? View::e($e[$k]) : $d;       // ข้อความ
$n   = fn($k) => number_format((float) ($e[$k] ?? 0), ((float) ($e[$k] ?? 0) == (int) ($e[$k] ?? 0)) ? 0 : 2);

// แปลง id คำตอบ → ป้ายข้อความ จากชุดตัวเลือก
$optLabel = function (string $nn, $id) use ($options): string {
    foreach ($options[$nn] ?? [] as $o) {
        if ((int) $o['id'] === (int) $id) return (string) $o['label'];
    }
    return '';
};
// แสดงคำตอบของแต่ละข้อตามชนิด (radio / multi / num)
$answer = function (string $nn, string $type) use ($e, $optLabel): string {
    $raw = $e['citeria' . $nn] ?? '';
    if ($raw === '' || $raw === null) return '—';
    if ($type === 'radio') {
        $l = $optLabel($nn, $raw);
        return View::e($l !== '' ? $l : (string) $raw);
    }
    if ($type === 'multi') {
        $ids = array_filter(array_map('intval', explode(',', (string) $raw)), fn($x) => $x > 0);
        $out = [];
        foreach ($ids as $id) { $l = $optLabel($nn, $id); $out[] = $l !== '' ? $l : (string) $id; }
        return $out ? View::e(implode(' · ', $out)) : '—';
    }
    return View::e((string) $raw);   // num
};

// เมตาข้อมูล 16 ตัวชี้วัด: [ป้าย, ชนิด]
$items = [
    '01' => ['ระดับความสูง ณ จุดสูงสุดของเส้นทาง (ม.)',      'num'],
    '02' => ['เขตติดต่อชายแดนประเทศเพื่อนบ้าน',               'radio'],
    '03' => ['เขตการปกครองส่วนท้องถิ่น',                      'radio'],
    '04' => ['เส้นทางที่รถยนต์ขับเคลื่อน 2 ล้อไปไม่ได้',       'radio'],
    '05' => ['ระยะทางรวมเส้นทางหลัก (กม.)',                   'num'],
    '06' => ['ลักษณะการเดินทางด้วยขนส่งสาธารณะ',              'radio'],
    '07' => ['แหล่งน้ำอุปโภค-บริโภค',                          'multi'],
    '08' => ['ระบบไฟฟ้า',                                     'multi'],
    '09' => ['ระบบโทรศัพท์',                                   'multi'],
    '10' => ['ระบบอินเทอร์เน็ต',                               'multi'],
    '11' => ['ร้อยละนักเรียนชาติพันธุ์ (%)',                   'num'],
    '12' => ['จำนวนกลุ่มชาติพันธุ์ (กลุ่ม)',                   'num'],
    '13' => ['จำนวนนักเรียนยากจน/ยากจนพิเศษ',                 'num'],
    '14' => ['จำนวนนักเรียนพักนอน',                           'num'],
    '15' => ['จำนวนโรงเรียนสาขา/ห้องเรียนสาขา',               'num'],
    '16' => ['โรงเรียนพื้นที่พิเศษตามประกาศกระทรวงการคลัง',    'radio'],
];

$type   = (int) ($e['highland_type'] ?? 0);
$certSt = (int) ($e['confirmstatus'] ?? 0);
$sptSt  = (int) ($e['spt_commit'] ?? 0);
$certBadge = [0 => ['warning', 'รออนุมัติ'], 1 => ['success', 'รับรองแล้ว'], 2 => ['danger', 'ไม่รับรอง']];
$sptBadge  = [0 => ['secondary', 'รอพิจารณา'], 1 => ['success', 'เป็นโรงเรียนพื้นที่ลักษณะพิเศษ'], 2 => ['danger', 'ไม่เป็นโรงเรียนพื้นที่ลักษณะพิเศษ']];
[$cbC, $cbL] = $certBadge[$certSt] ?? ['secondary', '-'];
[$sbC, $sbL] = $sptBadge[$sptSt] ?? ['secondary', '-'];
?>
<div class="container">

  <div class="nh-row between wrap gap-2" style="margin-bottom:var(--s-3);">
    <a class="btn btn-sm btn-outline-secondary" href="<?= View::e($backTo) ?>"><?= nh_icon('chevLeft', 16) ?> ย้อนกลับ</a>
    <span class="badge badge-highland"><?= nh_icon('mountain', 13) ?> ดูข้อมูลการประเมินพื้นที่สูง</span>
  </div>

  <!-- หัวเรื่อง: ชื่อโรงเรียน + คะแนน + สถานะรับรอง -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="nh-row between wrap gap-3 align-items-start">
      <div>
        <h1 class="h4 fw-bold mb-1"><?= View::e($ctx['sc_name'] ?: ($e['sc_names'] ?? $scId)) ?></h1>
        <div class="text-muted small">
          รหัส <?= View::e($scId) ?> · จังหวัด<?= View::e($ctx['province']) ?> · ปีงบประมาณ <?= View::e(App::acadYear()) ?>
        </div>
        <div class="mt-2 d-flex gap-1 flex-wrap">
          <span class="badge bg-<?= $cbC ?>">สพท.: <?= View::e($cbL) ?></span>
          <span class="badge bg-<?= $sbC ?>">สพฐ.: <?= View::e($sbL) ?></span>
        </div>
      </div>
      <div class="text-center">
        <div class="text-muted small">คะแนนรวม</div>
        <div class="fw-bold" style="font-size:2rem;line-height:1.1;color:var(--brand-primary);"><?= number_format((float) ($e['sum_score'] ?? 0), 2) ?></div>
        <span class="badge bg-light text-dark"><?= View::e(HighlandEvalController::typeLabel($type)) ?></span>
      </div>
    </div>
    <div class="mt-3 d-flex gap-2 flex-wrap">
      <a class="btn btn-sm btn-outline-secondary" href="<?= App::url('highland/print?sc_id=' . $scId) ?>" target="_blank"><?= nh_icon('printer', 16) ?> พิมพ์ผล (PDF)</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= App::url('highland/eval?sc_id=' . $scId) ?>"><?= nh_icon('clipboard', 16) ?> เปิดแบบประเมิน</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= App::url('map/elevation?sc_id=' . $scId) ?>"><?= nh_icon('ruler', 16) ?> วัดความสูง/ระยะ</a>
    </div>
  </div></div>

  <!-- ข้อมูลทั่วไป -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h6 fw-bold mb-3"><?= nh_icon('school', 16) ?> ข้อมูลทั่วไป</h2>
    <div class="row g-3 small">
      <div class="col-md-6"><div class="text-muted">สังกัด</div><div><?= $v('sao_names') ?></div></div>
      <div class="col-md-3"><div class="text-muted">ผู้อำนวยการ</div><div><?= $v('director_name') ?></div></div>
      <div class="col-md-3"><div class="text-muted">โทร ผอ.</div><div><?= $v('director_tel') ?></div></div>
      <div class="col-md-6"><div class="text-muted">ผู้กรอกข้อมูล</div><div><?= $v('editor_name') ?> <span class="text-muted"><?= ($e['editor_tel'] ?? '') !== '' ? '(' . View::e($e['editor_tel']) . ')' : '' ?></span></div></div>
      <div class="col-md-6"><div class="text-muted">อปท. ที่ตั้ง</div><div><?= $v('lgo') ?></div></div>
      <div class="col-12"><div class="text-muted">ที่อยู่</div>
        <div><?= $v('adresss') ?> หมู่บ้าน<?= $v('viledges') ?> หมู่ <?= $v('moo') ?> ต.<?= $v('subdistrict') ?> อ.<?= $v('district') ?> จ.<?= $v('provinces') ?></div></div>
      <div class="col-md-3"><div class="text-muted">ละติจูด</div><div><?= $v('lat') ?></div></div>
      <div class="col-md-3"><div class="text-muted">ลองจิจูด</div><div><?= $v('lng') ?></div></div>
      <div class="col-md-3"><div class="text-muted">ความสูง ณ จุดสูงสุด (ม.)</div><div><?= $n('highest') ?></div></div>
      <div class="col-md-3"><div class="text-muted">ความสูงเฉลี่ยจังหวัด (ม.)</div><div><?= $n('average_height') ?></div></div>
    </div>
  </div></div>

  <!-- จำนวนนักเรียน + กลุ่มชาติพันธุ์ -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-3"><?= nh_icon('users', 16) ?> จำนวนนักเรียน</h2>
      <div class="row g-2 small text-center">
        <div class="col"><div class="text-muted">ก่อนประถม</div><div class="fw-bold"><?= $n('stu_kinder') ?></div></div>
        <div class="col"><div class="text-muted">ประถม</div><div class="fw-bold"><?= $n('stu_prim') ?></div></div>
        <div class="col"><div class="text-muted">ม.ต้น</div><div class="fw-bold"><?= $n('stu_second') ?></div></div>
        <div class="col"><div class="text-muted">ม.ปลาย</div><div class="fw-bold"><?= $n('stu_high') ?></div></div>
        <div class="col"><div class="text-muted">รวม</div><div class="fw-bold text-primary"><?= $n('stu_sum') ?></div></div>
      </div>
      <div class="row g-2 small text-center mt-1 pt-2 border-top">
        <div class="col"><div class="text-muted">พักนอน (ชาย)</div><div class="fw-bold"><?= $n('stu_sleep_boy') ?></div></div>
        <div class="col"><div class="text-muted">พักนอน (หญิง)</div><div class="fw-bold"><?= $n('stu_sleep_girl') ?></div></div>
        <div class="col"><div class="text-muted">รวมพักนอน</div><div class="fw-bold"><?= $n('stu_sleep_sum') ?></div></div>
      </div>
    </div></div></div>

    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-3"><?= nh_icon('users', 16) ?> กลุ่มชาติพันธุ์</h2>
      <?php if ($hilltribRows): ?>
        <table class="table table-sm mb-0 small">
          <thead class="table-light"><tr><th>กลุ่มชาติพันธุ์</th><th class="text-end">จำนวน (คน)</th></tr></thead>
          <tbody>
          <?php $tot = 0; foreach ($hilltribRows as $h): $tot += (int) $h['hilltrib_number']; ?>
            <tr><td><?= View::e($h['ethnic'] ?: $h['hilltrib']) ?></td><td class="text-end"><?= number_format((int) $h['hilltrib_number']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr class="fw-bold"><td class="text-end">รวม</td><td class="text-end"><?= number_format($tot) ?></td></tr></tfoot>
        </table>
      <?php else: ?>
        <p class="text-muted small mb-0">— ไม่มีข้อมูลกลุ่มชาติพันธุ์ —</p>
      <?php endif; ?>
    </div></div></div>
  </div>

  <!-- คะแนนรายตัวชี้วัด 16 ข้อ -->
  <div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h2 class="h6 fw-bold mb-3"><?= nh_icon('clipboard', 16) ?> ผลการประเมิน 16 ตัวชี้วัด</h2>
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle small mb-0">
      <thead class="table-light"><tr>
        <th style="width:48px" class="text-center">ข้อ</th>
        <th>ตัวชี้วัด</th>
        <th>คำตอบ / ค่า</th>
        <th class="text-end" style="width:90px">คะแนน</th>
      </tr></thead>
      <tbody>
      <?php foreach ($items as $nn => [$label, $type2]): ?>
        <tr>
          <td class="text-center text-muted"><?= (int) $nn ?></td>
          <td><?= View::e($label) ?></td>
          <td>
            <?= $answer($nn, $type2) ?>
            <?php if ($nn === '04' && (float) ($e['citeria041'] ?? 0) > 0): ?>
              <span class="text-muted">(ระยะ <?= number_format((float) $e['citeria041'], 2) ?> กม.)</span>
            <?php endif; ?>
          </td>
          <td class="text-end fw-bold"><?= number_format((float) ($e['score' . $nn] ?? 0), 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr class="fw-bold table-light">
        <td colspan="3" class="text-end">คะแนนรวม</td>
        <td class="text-end text-primary"><?= number_format((float) ($e['sum_score'] ?? 0), 2) ?></td>
      </tr></tfoot>
    </table>
    </div>
  </div></div>

  <!-- ความเห็นการรับรอง -->
  <div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-2"><?= nh_icon('shieldCheck', 16) ?> ความเห็น สพท. (เขตพื้นที่)</h2>
      <p class="mb-1">ผลการรับรอง: <span class="badge bg-<?= $cbC ?>"><?= View::e($cbL) ?></span></p>
      <p class="text-muted small mb-0"><?= ($e['confirmcomment'] ?? '') !== '' ? View::e($e['confirmcomment']) : '— ไม่มีหมายเหตุ —' ?></p>
    </div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-2"><?= nh_icon('shieldCheck', 16) ?> ความเห็น สพฐ.</h2>
      <p class="mb-1">ผลการพิจารณา: <span class="badge bg-<?= $sbC ?>"><?= View::e($sbL) ?></span></p>
      <p class="text-muted small mb-0"><?= ($e['spt_comment'] ?? '') !== '' ? View::e($e['spt_comment']) : '— ไม่มีหมายเหตุ —' ?></p>
    </div></div></div>
  </div>

  <div class="mb-4">
    <a class="btn btn-outline-secondary" href="<?= View::e($backTo) ?>"><?= nh_icon('chevLeft', 16) ?> ย้อนกลับไปยังรายการ</a>
  </div>

</div><!-- /.container -->
