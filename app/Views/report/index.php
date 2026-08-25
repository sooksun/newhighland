<?php
/**
 * รายงานสถิติ (Executive dashboard)
 * @var int $year; @var int $area; @var array $areas; @var bool $isAdmin
 * @var array $targets; @var array $kpi; @var array $funnel; @var array $typeDist
 * @var array $scoreHist; @var array $provinces; @var array $utils; @var array $utilLabels
 */
$pct = fn($a, $b) => $b > 0 ? round($a * 100 / $b, 1) : 0.0;
$areaName = $area === 2 ? 'พื้นที่เกาะ' : 'พื้นที่สูง';

// เวลาที่ประมวลผลข้อมูลล่าสุด (จาก cache) — แสดงเป็นเวลาไทย/ปี พ.ศ.
$asOf = '-';
if (!empty($computedAt)) {
    $dt = (new DateTime('@' . (int) $computedAt))->setTimezone(new DateTimeZone('Asia/Bangkok'));
    $thMonths = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
    $asOf = (int) $dt->format('j') . ' ' . $thMonths[(int) $dt->format('n')] . ' ' . ((int) $dt->format('Y') + 543) . ' ' . $dt->format('H:i') . ' น.';
}

// ---- KPI ----
// "เป้าหมาย" = โรงเรียนที่ยังคงอยู่จริง (ตัด ยุบ/รวม/เลิก + ขาดคุณสมบัติ ออกจาก roster เดิม)
$rosterTotal  = (int) ($targets['total'] ?? 0);
$rosterClosed = (int) ($targets['closed'] ?? 0);
$rosterDq     = (int) ($targets['disqualified'] ?? 0);
$target       = (int) ($targets['active'] ?? $rosterTotal);
$done   = (int) $kpi['done'];
$kpiCards = [
  ['ti' => 'school',    'tone' => 'primary', 'label' => 'โรงเรียนเป้าหมาย', 'n' => $target,
   'sub' => 'สูง ' . number_format((int) ($targets['high_active'] ?? $targets['high'])) . ' · เกาะ ' . number_format((int) ($targets['island_active'] ?? $targets['island']))],
  ['ti' => 'clipboard', 'tone' => 'emerald', 'label' => 'ดำเนินการแล้ว', 'n' => $done,
   'sub' => $pct($done, $target) . '% ของเป้าหมาย'],
  ['ti' => 'award',     'tone' => 'island',  'label' => 'ผ่านเกณฑ์พิเศษ', 'n' => (int) $kpi['passed'],
   'sub' => '≥ 50 คะแนน · ' . $pct($kpi['passed'], $done) . '%'],
  ['ti' => 'shieldCheck','tone' => 'warning','label' => 'เขตรับรองแล้ว', 'n' => (int) $kpi['sao_certed'],
   'sub' => $pct($kpi['sao_certed'], $done) . '% ของที่ประเมิน'],
  ['ti' => 'flag',      'tone' => 'primary', 'label' => 'สพฐ. ประกาศ', 'n' => (int) $kpi['spt_announced'],
   'sub' => $pct($kpi['spt_announced'], $done) . '% ของที่ประเมิน'],
];
$tones = [
  'primary' => 'background:var(--brand-primary-050);color:var(--brand-primary-700);',
  'emerald' => 'background:var(--brand-emerald-050);color:var(--brand-emerald);',
  'island'  => 'background:var(--island-050);color:var(--island-700);',
  'warning' => 'background:var(--warning-bg);color:var(--warning);',
];

// ---- Funnel ----
$fbase  = max((int) $funnel['pinned'], (int) $funnel['evaluated'], 1);
$fSteps = [
  ['ปักหมุด / เริ่ม', (int) $funnel['pinned'],        '#185FA5'],
  ['ประเมินเสร็จ',     (int) $funnel['evaluated'],     '#378ADD'],
  ['ผ่านเกณฑ์',        (int) $funnel['passed'],        '#1D9E75'],
  ['เขตรับรอง (สพท.)', (int) $funnel['sao_certed'],    '#BA7517'],
  ['สพฐ. ประกาศ',      (int) $funnel['spt_announced'], '#5F5E5A'],
];

// ---- โดนัทระดับความยุ่งยาก ----
$typeLabels = [($area === 2 ? 'ไม่เป็นพื้นที่เกาะ' : 'ไม่เป็นพื้นที่สูง'), 'ยุ่งยาก', 'ยุ่งยากมาก', 'ยุ่งยากมากที่สุด'];
$typeColors = ['#B4B2A9', '#85B7EB', '#378ADD', '#185FA5'];
$typeData   = [(int) $typeDist[0], (int) $typeDist[1], (int) $typeDist[2], (int) $typeDist[3]];
$typeTotal  = array_sum($typeData);

// ---- ฮิสโทแกรมคะแนน ----
$scoreLabels = array_keys($scoreHist);
$scoreData   = array_values(array_map('intval', $scoreHist));
$scoreColors = array_map(fn($l) => $l === '<50' ? '#B4B2A9' : '#378ADD', $scoreLabels);

// ---- อันดับจังหวัด ----
$provLabels = array_map(fn($r) => (string) $r['province'], $provinces);
$provData   = array_map(fn($r) => (int) $r['passed'], $provinces);

// ---- สาธารณูปโภค ----
$sev = ['#E24B4A', '#EF9F27', '#B4B2A9', '#97C459', '#1D9E75'];   // ขาดแคลนมาก → พร้อม
$sevColor = function (int $k, int $max) use ($sev): string {
  $idx = $max <= 1 ? 0 : (int) round(($k - 1) / ($max - 1) * 4);
  return $sev[max(0, min(4, $idx))];
};
$utilMeta = [
  'water' => ['icon' => 'droplet', 'label' => 'น้ำ / ประปา',     'deprived' => [1, 2]],
  'power' => ['icon' => 'zap',     'label' => 'ไฟฟ้า',           'deprived' => [1]],
  'phone' => ['icon' => 'phone',   'label' => 'โทรศัพท์',         'deprived' => [1, 2]],
  'net'   => ['icon' => 'wifi',    'label' => 'อินเทอร์เน็ต',     'deprived' => [1, 2]],
];

// ---- กลุ่มชาติพันธุ์ (พื้นที่สูง) ----
$ethTop    = $ethnic['top'] ?? [];
$ethLabels = array_map(fn($r) => (string) $r['ethnic'], $ethTop);
$ethData   = array_map(fn($r) => (int) $r['students'], $ethTop);

// ---- แผนที่หมุด ----
$pinData = array_map(fn($r) => [
  'lat'   => (float) $r['lat'],
  'lng'   => (float) $r['lng'],
  'name'  => (string) ($r['sc_name'] ?? ''),
  'prov'  => (string) ($r['province'] ?? ''),
  'type'  => (int) $r['type'],
  'score' => (float) $r['sum_score'],
], $pins);

// ---- วิเคราะห์สถิติ (U1 พรรณนา / B2 ปัจจัย→คะแนน / B4 ค่าเฉลี่ยข้ามระดับ) ----
$stats = $stats ?? ['n' => 0, 'descriptive' => [], 'corr' => [], 'groupMeans' => ['counts' => [0,0,0,0], 'rows' => []]];
$stN   = (int) ($stats['n'] ?? 0);
$gmCounts = $stats['groupMeans']['counts'] ?? [0,0,0,0];
// สีของแท่ง correlation: เขียว=บวกแรง, ฟ้า=บวกปานกลาง, ส้ม=ลบอ่อน, แดง=ลบ(≥0.3)
$corrLabels = array_map(fn($c) => $c['label'], $stats['corr']);
$corrData   = array_map(fn($c) => $c['r'], $stats['corr']);
$corrColors = array_map(function ($c) {
    $r = $c['r'];
    if ($r >= 0.5)  return '#1D9E75';
    if ($r >= 0.3)  return '#378ADD';
    if ($r >= 0)    return '#B4B2A9';
    if ($r <= -0.3) return '#E24B4A';
    return '#EF9F27';
}, $stats['corr']);

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<div class="container">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="margin:var(--s-2) 0;">
    <div class="nh-row gap-2 wrap">
      <span class="badge badge-primary"><?= nh_icon('trendUp', 13) ?> รายงานสถิติ</span>
      <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปีงบประมาณ <?= View::e($year) ?></span>
      <span class="badge <?= $area === 2 ? 'badge-island' : 'badge-highland' ?>"><?= View::e($areaName) ?></span>
      <span class="badge badge-neutral"><?= $isAdmin ? 'ทุกเขต' : View::e(Auth::saoName()) ?></span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <?php if (!empty($years) && count($years) > 1): ?>
      <form method="get" action="<?= App::url('report') ?>" class="d-inline-flex align-items-center gap-1">
        <input type="hidden" name="area" value="<?= (int) $area ?>">
        <label class="text-muted small mb-0" for="yearSel"><?= nh_icon('calendar', 13) ?> ปี</label>
        <select id="yearSel" name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
          <?php foreach ($years as $yy): ?>
            <option value="<?= (int) $yy ?>" <?= (int) $yy === (int) $year ? 'selected' : '' ?>><?= (int) $yy ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php endif; ?>
      <span class="text-muted small d-inline-flex align-items-center gap-1" title="ค่าที่ประมวลผลไว้ล่าสุด"><?= nh_icon('clock', 14) ?> ข้อมูล ณ <?= View::e($asOf) ?></span>
      <a class="btn btn-sm btn-primary" href="<?= View::e($refreshUrl ?? App::url('report?area=' . $area . '&refresh=1')) ?>" title="คำนวณค่าล่าสุดแบบ real-time แล้วบันทึกไว้"><?= nh_icon('refresh', 15) ?> ประมวลผลใหม่</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= App::url('report/export?area=' . $area . '&year=' . (int) $year) ?>"><?= nh_icon('fileText', 15) ?> Export CSV (สรุป)</a>
      <a class="btn btn-sm btn-outline-success" href="<?= App::url('report/export/xlsx?year=' . (int) $year) ?>" title="ส่งออกรายการประเมิน+รับรองรายโรงเรียนทุกแถว (4 ชีต: ประเมินพื้นที่สูง/เกาะ, รับรองคงอยู่พื้นที่สูง/เกาะ)"><?= nh_icon('fileText', 15) ?> Export Excel (รายงานการคัดกรอง+รับรอง)</a>
      <a class="btn btn-sm btn-outline-primary" href="<?= App::url('report/word?area=1&year=' . (int) $year) ?>" title="บัญชีแนบท้าย 1 — รายชื่อโรงเรียนพื้นที่สูงที่เข้าเกณฑ์ แบ่งตามสำนักงานเขตพื้นที่ (Word)"><?= nh_icon('fileText', 15) ?> แนบท้าย 1 (พื้นที่สูง)</a>
      <a class="btn btn-sm btn-outline-primary" href="<?= App::url('report/word?area=2&year=' . (int) $year) ?>" title="บัญชีแนบท้าย 2 — รายชื่อโรงเรียนพื้นที่เกาะที่เข้าเกณฑ์ แบ่งตามสำนักงานเขตพื้นที่ (Word)"><?= nh_icon('fileText', 15) ?> แนบท้าย 2 (พื้นที่เกาะ)</a>
<?php if ($isAdmin): ?>
      <a class="btn btn-sm btn-outline-dark" href="<?= App::url('report/full?year=' . (int) $year) ?>" target="_blank" rel="noopener" title="รายงานผลการประเมินฉบับเต็มทุกมิติ (กราฟ+ตัวเลข+คำบรรยาย) เป็นหน้า HTML เดี่ยว เปิดออฟไลน์/พิมพ์เป็น PDF ได้"><?= nh_icon('fileText', 15) ?> รายงานฉบับเต็ม (HTML)</a>
      <a class="btn btn-sm btn-outline-dark" href="<?= App::url('report/full?download=1&year=' . (int) $year) ?>" title="ดาวน์โหลดรายงานฉบับเต็มเป็นไฟล์ .html"><?= nh_icon('fileText', 15) ?> ดาวน์โหลด .html</a>
<?php endif; ?>
    </div>
  </div>

  <h1 class="h5 fw-bold mb-1">ภาพรวมการคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ</h1>
  <p class="text-muted small">สรุปความคืบหน้า ระดับความยุ่งยาก การกระจายคะแนน และสาธารณูปโภค<?= $isAdmin ? ' · ระดับประเทศ' : '' ?></p>

  <?php if (count($areas) > 1): ?>
  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $area === 1 ? 'active' : '' ?>" href="<?= App::url('report?area=1&year=' . (int) $year) ?>">พื้นที่สูง</a></li>
    <li class="nav-item"><a class="nav-link <?= $area === 2 ? 'active' : '' ?>" href="<?= App::url('report?area=2&year=' . (int) $year) ?>">พื้นที่เกาะ</a></li>
  </ul>
  <?php endif; ?>

  <!-- KPI -->
  <div class="row g-2 mb-3">
    <?php foreach ($kpiCards as $c): ?>
      <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 d-flex align-items-center gap-2">
          <span class="s-ic flex-shrink-0" style="width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;<?= $tones[$c['tone']] ?>"><?= nh_icon($c['ti'], 20) ?></span>
          <div style="min-width:0;">
            <div class="text-muted" style="font-size:.72rem;line-height:1.2;"><?= View::e($c['label']) ?></div>
            <div class="h5 fw-bold mb-0"><?= number_format($c['n']) ?></div>
            <div class="text-muted" style="font-size:.68rem;"><?= View::e($c['sub']) ?></div>
          </div>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- สถานะการคงอยู่ของ roster เดิม (คงอยู่ / ยุบ-รวม-เลิก / ขาดคุณสมบัติ) -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
    <div class="d-flex flex-wrap align-items-center gap-3 gap-lg-4">
      <span class="text-muted small fw-bold d-inline-flex align-items-center gap-1"><?= nh_icon('shieldCheck', 14) ?> สถานะการคงอยู่ (จาก roster เดิม)</span>
      <?php foreach ([
        ['เป้าหมายตั้งต้น', $rosterTotal, 'secondary'],
        ['ยังคงอยู่', $target, 'info'],
        ['ยุบ/รวม/เลิก', $rosterClosed, 'danger'],
        ['ขาดคุณสมบัติ', $rosterDq, 'warning'],
      ] as $rc): ?>
        <span class="d-inline-flex align-items-baseline gap-1">
          <span class="text-muted small"><?= View::e($rc[0]) ?></span>
          <span class="fw-bold text-<?= $rc[2] ?>"><?= number_format((int) $rc[1]) ?></span>
        </span>
      <?php endforeach; ?>
      <span class="text-muted" style="font-size:.68rem;">* “เป้าหมาย” = ตัด ยุบ/รวม/เลิก + ขาดคุณสมบัติ ออกแล้ว</span>
    </div>
  </div></div>

  <!-- Funnel -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="text-muted small mb-3"><?= nh_icon('layers', 15) ?> เส้นทางกระบวนการ (funnel) — จำนวนคงเหลือแต่ละขั้น</div>
    <?php foreach ($fSteps as [$lbl, $val, $col]): $w = round($val * 100 / $fbase); ?>
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="text-muted small text-truncate" style="width:130px;flex-shrink:0;"><?= View::e($lbl) ?></span>
        <div class="flex-grow-1" style="background:var(--surface-2,#eef1f5);border-radius:6px;height:24px;overflow:hidden;">
          <div style="width:<?= $w ?>%;min-width:fit-content;height:100%;background:<?= $col ?>;display:flex;align-items:center;padding:0 8px;color:#fff;font-size:.78rem;white-space:nowrap;"><?= number_format($val) ?></div>
        </div>
        <span class="text-muted small text-end" style="width:42px;flex-shrink:0;"><?= $w ?>%</span>
      </div>
    <?php endforeach; ?>
  </div></div>

  <!-- โดนัท type + ฮิสโทแกรมคะแนน -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100"><div class="card-body">
        <div class="text-muted small mb-2"><?= nh_icon('layers', 15) ?> สัดส่วนระดับความยุ่งยาก</div>
        <div class="d-flex flex-wrap gap-2 mb-2" style="font-size:.74rem;">
          <?php foreach ($typeLabels as $i => $tl): ?>
            <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:<?= $typeColors[$i] ?>;"></span><?= View::e($tl) ?> <b><?= number_format($typeData[$i]) ?></b></span>
          <?php endforeach; ?>
        </div>
        <div style="position:relative;height:210px;">
          <canvas id="chType" role="img" aria-label="โดนัทสัดส่วนระดับความยุ่งยาก รวม <?= $typeTotal ?> โรงเรียน"></canvas>
        </div>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100"><div class="card-body">
        <div class="text-muted small mb-2"><?= nh_icon('trendUp', 15) ?> การกระจายคะแนนรวม</div>
        <div class="d-flex flex-wrap gap-3 mb-2" style="font-size:.74rem;">
          <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#B4B2A9;"></span>ไม่ผ่าน (&lt;50)</span>
          <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#378ADD;"></span>ผ่านเกณฑ์ (≥50)</span>
        </div>
        <div style="position:relative;height:210px;">
          <canvas id="chScore" role="img" aria-label="ฮิสโทแกรมการกระจายคะแนนรวมตามช่วงคะแนน"></canvas>
        </div>
      </div></div>
    </div>
  </div>

  <!-- อันดับจังหวัด -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="text-muted small mb-2"><?= nh_icon('mapPin', 15) ?> จังหวัดที่ผ่านเกณฑ์มากที่สุด</div>
    <?php if (!$provData): ?>
      <div class="text-muted small py-3 text-center">ยังไม่มีโรงเรียนที่ผ่านเกณฑ์ในปีงบประมาณนี้</div>
    <?php else: ?>
      <div style="position:relative;height:<?= max(160, count($provData) * 38 + 30) ?>px;">
        <canvas id="chProv" role="img" aria-label="กราฟแท่งแนวนอนจังหวัดที่ผ่านเกณฑ์มากที่สุด"></canvas>
      </div>
    <?php endif; ?>
  </div></div>

  <!-- ===== การวิเคราะห์เชิงสถิติ (U1 / B2 / B4) ===== -->
  <?php if ($stN > 0): ?>
  <div class="d-flex align-items-center gap-2 mt-4 mb-2">
    <span class="badge badge-primary"><?= nh_icon('trendUp', 13) ?> การวิเคราะห์เชิงสถิติ</span>
    <span class="text-muted small">วิเคราะห์จากโรงเรียนที่ประเมินแล้ว <b><?= number_format($stN) ?></b> แห่ง (<?= View::e($areaName) ?> · ปี <?= View::e($year) ?>)</span>
  </div>

  <!-- U1: สถิติเชิงพรรณนา -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="text-muted small mb-2"><?= nh_icon('list', 15) ?> สถิติเชิงพรรณนา — ตัวแปรเชิงเดี่ยว (U1)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
        <thead class="table-light"><tr>
          <th>ตัวแปร</th>
          <th class="text-center">N</th>
          <th class="text-end">ค่าเฉลี่ย</th>
          <th class="text-end">SD</th>
          <th class="text-end">ต่ำสุด</th>
          <th class="text-end">Q1</th>
          <th class="text-end">มัธยฐาน</th>
          <th class="text-end">Q3</th>
          <th class="text-end">สูงสุด</th>
        </tr></thead>
        <tbody>
        <?php foreach ($stats['descriptive'] as $d): $oc = !empty($d['outcome']); ?>
          <tr<?= $oc ? ' style="background:var(--brand-primary-050);font-weight:600;"' : '' ?>>
            <td><?= View::e($d['label']) ?></td>
            <td class="text-center text-muted"><?= number_format((int) $d['n']) ?></td>
            <td class="text-end"><?= $d['mean'] === null ? '-' : number_format($d['mean'], 2) ?></td>
            <td class="text-end text-muted"><?= $d['sd'] === null ? '-' : number_format($d['sd'], 2) ?></td>
            <td class="text-end text-muted"><?= $d['min'] === null ? '-' : number_format($d['min'], 2) ?></td>
            <td class="text-end text-muted"><?= $d['q1'] === null ? '-' : number_format($d['q1'], 2) ?></td>
            <td class="text-end"><?= $d['median'] === null ? '-' : number_format($d['median'], 2) ?></td>
            <td class="text-end text-muted"><?= $d['q3'] === null ? '-' : number_format($d['q3'], 2) ?></td>
            <td class="text-end text-muted"><?= $d['max'] === null ? '-' : number_format($d['max'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-muted mt-2" style="font-size:.68rem;">* SD = ส่วนเบี่ยงเบนมาตรฐาน (กลุ่มตัวอย่าง) · Q1/Q3 = ควอร์ไทล์ที่ 1/3 · แถวไฮไลต์ = ตัวแปรเป้าหมาย (คะแนนรวม)</div>
  </div></div>

  <!-- B2: ปัจจัย → คะแนนรวม -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="text-muted small"><?= nh_icon('trendUp', 15) ?> ปัจจัยที่ส่งผลต่อคะแนนรวม — สหสัมพันธ์ (B2)</div>
      <div class="d-flex flex-wrap gap-2" style="font-size:.68rem;">
        <span class="d-inline-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#1D9E75;"></span>บวกแรง</span>
        <span class="d-inline-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#378ADD;"></span>บวกปานกลาง</span>
        <span class="d-inline-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#EF9F27;"></span>ลบอ่อน</span>
        <span class="d-inline-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:#E24B4A;"></span>ลบ</span>
      </div>
    </div>
    <div style="position:relative;height:<?= max(180, count($corrData) * 34 + 30) ?>px;">
      <canvas id="chCorr" role="img" aria-label="กราฟแท่งสหสัมพันธ์ของปัจจัยกับคะแนนรวม"></canvas>
    </div>
    <div class="text-muted mt-2" style="font-size:.68rem;">* ค่าสหสัมพันธ์ r ∈ [−1, +1] · บวก = ปัจจัยสูงคะแนนสูง · ลบ = ปัจจัยสูงคะแนนต่ำ · ยิ่งห่างจาก 0 ยิ่งสัมพันธ์แรง</div>
  </div></div>

  <!-- B4: ค่าเฉลี่ยปัจจัยจำแนกตามระดับ -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="text-muted small mb-2"><?= nh_icon('layers', 15) ?> ค่าเฉลี่ยปัจจัยจำแนกตามระดับผลคัดกรอง — เปรียบเทียบหลายกลุ่ม (B4)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
        <thead class="table-light"><tr>
          <th>ปัจจัย (ค่าเฉลี่ย)</th>
          <?php foreach ($typeLabels as $i => $tl): ?>
            <th class="text-end"><?= View::e($tl) ?><br><span class="text-muted fw-normal" style="font-size:.7rem;">n=<?= number_format((int) ($gmCounts[$i] ?? 0)) ?></span></th>
          <?php endforeach; ?>
          <th class="text-center">แนวโน้ม</th>
        </tr></thead>
        <tbody>
        <?php foreach ($stats['groupMeans']['rows'] as $g):
          $up = $g['trend'] === 'up'; $down = $g['trend'] === 'down'; ?>
          <tr>
            <td><?= View::e($g['label']) ?></td>
            <?php foreach ($g['means'] as $m): ?>
              <td class="text-end"><?= $m === null ? '<span class="text-muted">-</span>' : number_format($m, 1) ?></td>
            <?php endforeach; ?>
            <td class="text-center">
              <?php if ($up): ?><span class="badge bg-success">เพิ่มตามระดับ ↑</span>
              <?php elseif ($down): ?><span class="badge bg-danger">ลดตามระดับ ↓</span>
              <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-muted mt-2" style="font-size:.68rem;">* เทียบค่าเฉลี่ยข้ามกลุ่มระดับความยุ่งยาก (0–3) · "เพิ่มตามระดับ" = โรงเรียนระดับสูงมีค่าปัจจัยนี้สูงกว่า (ปัจจัยบวก)</div>
  </div></div>
  <?php endif; ?>

  <?php if ($area === 1 && $ethTop): /* กลุ่มชาติพันธุ์ (เฉพาะพื้นที่สูง) */ ?>
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="text-muted small"><?= nh_icon('users', 15) ?> กลุ่มชาติพันธุ์ (นักเรียนมากที่สุด)</div>
      <div class="d-flex flex-wrap gap-3" style="font-size:.72rem;">
        <span class="text-muted">กลุ่มทั้งหมด <b><?= number_format($ethnic['groups']) ?></b></span>
        <span class="text-muted">นักเรียน <b><?= number_format($ethnic['students']) ?></b></span>
        <span class="text-muted">โรงเรียน <b><?= number_format($ethnic['schools']) ?></b></span>
      </div>
    </div>
    <div style="position:relative;height:<?= max(160, count($ethData) * 34 + 30) ?>px;">
      <canvas id="chEthnic" role="img" aria-label="กราฟแท่งกลุ่มชาติพันธุ์ที่มีนักเรียนมากที่สุด"></canvas>
    </div>
  </div></div>
  <?php endif; ?>

  <!-- สาธารณูปโภค -->
  <div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="text-muted small"><?= nh_icon('zap', 15) ?> สถานะสาธารณูปโภค — สัดส่วนตามระดับการเข้าถึง</div>
      <div class="d-flex flex-wrap gap-2" style="font-size:.68rem;">
        <?php foreach (['ขาดแคลนมาก' => 0, 'ขาดแคลน' => 1, 'ปานกลาง' => 2, 'ดี' => 3, 'พร้อม' => 4] as $lg => $i): ?>
          <span class="d-inline-flex align-items-center gap-1"><span style="width:9px;height:9px;border-radius:2px;background:<?= $sev[$i] ?>;"></span><?= $lg ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <?php foreach ($utilMeta as $key => $meta): $u = $utils[$key]; $ans = (int) $u['answered']; $dep = 0; ?>
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="text-muted small d-flex align-items-center gap-1" style="width:96px;flex-shrink:0;"><?= nh_icon($meta['icon'], 14) ?> <?= View::e($meta['label']) ?></span>
        <div class="flex-grow-1 d-flex" style="height:22px;border-radius:6px;overflow:hidden;background:var(--surface-2,#eef1f5);">
          <?php if ($ans > 0): for ($l = 1; $l <= $u['max']; $l++):
              $n = (int) $u['levels'][$l]; if ($n === 0) continue;
              $w = $n * 100 / $ans;
              if (in_array($l, $meta['deprived'], true)) $dep += $n;
              $lab = $utilLabels[$key][$l] ?? ('ระดับ ' . $l);
          ?>
            <div title="<?= View::e($lab) ?> — <?= number_format($n) ?> (<?= round($w, 1) ?>%)" style="width:<?= $w ?>%;background:<?= $sevColor($l, (int) $u['max']) ?>;"></div>
          <?php endfor; endif; ?>
        </div>
        <span class="small text-end" style="width:120px;flex-shrink:0;color:var(--danger,#c0392b);">
          <?= $ans > 0 ? $pct($dep, $ans) . '% ขาดแคลน' : '<span class="text-muted">ไม่มีข้อมูล</span>' ?>
        </span>
      </div>
    <?php endforeach; ?>
    <div class="text-muted mt-2" style="font-size:.68rem;">* ชี้ที่แถบเพื่อดูระดับและจำนวนจริง · "ขาดแคลน" = ใช้แหล่งพื้นฐาน/พลังงานทางเลือก/ไม่มีบริการ</div>
  </div></div>

  <!-- แผนที่หมุดที่ตั้งโรงเรียน -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="text-muted small"><?= nh_icon('mapPin', 15) ?> แผนที่ที่ตั้งโรงเรียน (<?= number_format(count($pinData)) ?> แห่ง)</div>
      <div class="d-flex flex-wrap gap-2" style="font-size:.72rem;">
        <?php foreach ($typeLabels as $i => $tl): ?>
          <span class="d-inline-flex align-items-center gap-1"><span style="width:11px;height:11px;border-radius:50%;background:<?= $typeColors[$i] ?>;"></span><?= View::e($tl) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($pinData): ?>
      <div id="nhmap" style="width:100%;height:380px;border-radius:10px;overflow:hidden;background:var(--surface-2,#eef1f5);"></div>
    <?php else: ?>
      <div class="text-muted small py-4 text-center">ยังไม่มีพิกัดโรงเรียนที่บันทึกไว้ในปีงบประมาณนี้</div>
    <?php endif; ?>
  </div></div>

  <!-- drill-down เขตที่ค้างรับรอง -->
  <div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div class="text-muted small mb-2"><?= nh_icon('clock', 15) ?> เขตที่ยังค้างรับรอง (<?= View::e($areaName) ?>) — เรียงค้างมากสุด</div>
    <?php if (!$pending): ?>
      <div class="text-muted small py-3 text-center">ทุกเขตในขอบเขตของท่านรับรองครบแล้ว</div>
    <?php else: $certBase = $area === 2 ? 'island/cert' : 'highland/cert'; ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light"><tr>
          <th>เขตพื้นที่</th>
          <th class="text-center">ประเมินเสร็จ</th>
          <th class="text-center">ค้างรับรอง</th>
          <th class="text-center">รับรองแล้ว</th>
          <th style="width:150px;">ความคืบหน้า</th>
        </tr></thead>
        <tbody>
        <?php foreach ($pending as $d): $dn = (int) $d['done']; $ap = (int) $d['approved']; $pp = $pct($ap, $dn); ?>
          <tr>
            <td><a href="<?= App::url($certBase . '?sao=' . (int) $d['sao_code'] . '&status=0') ?>" title="ไปหน้ารับรองของเขตนี้"><?= View::e($d['sao_name']) ?> <?= nh_icon('arrowRight', 13) ?></a></td>
            <td class="text-center"><?= number_format($dn) ?></td>
            <td class="text-center"><span class="badge bg-warning text-dark"><?= number_format((int) $d['pending']) ?></span></td>
            <td class="text-center"><?= number_format($ap) ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="flex-grow-1" style="background:var(--surface-2,#eef1f5);border-radius:6px;height:8px;overflow:hidden;">
                  <div style="width:<?= $pp ?>%;height:100%;background:#1D9E75;"></div>
                </div>
                <span class="text-muted" style="font-size:.7rem;width:36px;text-align:right;"><?= $pp ?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div></div>

</div><!-- /.container -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  var dark = (document.documentElement.getAttribute('data-theme') === 'dark');
  var grid = dark ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.08)';
  var tick = dark ? 'rgba(235,235,235,0.78)' : 'rgba(40,40,40,0.78)';
  Chart.defaults.font.family = "var(--font-sans), 'Sarabun', sans-serif";

  var elType = document.getElementById('chType');
  if (elType) new Chart(elType, {
    type: 'doughnut',
    data: { labels: <?= json_encode($typeLabels, $jsonFlags) ?>,
      datasets: [{ data: <?= json_encode($typeData, $jsonFlags) ?>,
        backgroundColor: <?= json_encode($typeColors, $jsonFlags) ?>,
        borderWidth: 2, borderColor: dark ? '#1b1f24' : '#fff' }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '58%',
      plugins: { legend: { display: false },
        tooltip: { callbacks: { label: function (c) {
          var t = <?= max(1, $typeTotal) ?>; return c.label + ': ' + c.raw + ' (' + Math.round(c.raw / t * 100) + '%)';
        } } } } }
  });

  var elScore = document.getElementById('chScore');
  if (elScore) new Chart(elScore, {
    type: 'bar',
    data: { labels: <?= json_encode($scoreLabels, $jsonFlags) ?>,
      datasets: [{ label: 'จำนวนโรงเรียน', data: <?= json_encode($scoreData, $jsonFlags) ?>,
        backgroundColor: <?= json_encode($scoreColors, $jsonFlags) ?>, borderRadius: 4 }] },
    options: { responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { grid: { display: false }, ticks: { color: tick } },
                y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } } } }
  });

  var elProv = document.getElementById('chProv');
  if (elProv) new Chart(elProv, {
    type: 'bar',
    data: { labels: <?= json_encode($provLabels, $jsonFlags) ?>,
      datasets: [{ label: 'ผ่านเกณฑ์', data: <?= json_encode($provData, $jsonFlags) ?>,
        backgroundColor: '#1D9E75', borderRadius: 4 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } },
                y: { grid: { display: false }, ticks: { color: tick } } } }
  });

  var elEth = document.getElementById('chEthnic');
  if (elEth) new Chart(elEth, {
    type: 'bar',
    data: { labels: <?= json_encode($ethLabels, $jsonFlags) ?>,
      datasets: [{ label: 'นักเรียน', data: <?= json_encode($ethData, $jsonFlags) ?>,
        backgroundColor: '#7F77DD', borderRadius: 4 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } },
                y: { grid: { display: false }, ticks: { color: tick } } } }
  });

  var elCorr = document.getElementById('chCorr');
  if (elCorr) new Chart(elCorr, {
    type: 'bar',
    data: { labels: <?= json_encode($corrLabels, $jsonFlags) ?>,
      datasets: [{ label: 'r กับคะแนนรวม', data: <?= json_encode($corrData, $jsonFlags) ?>,
        backgroundColor: <?= json_encode($corrColors, $jsonFlags) ?>, borderRadius: 4 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { callbacks: { label: function (c) { return 'r = ' + (c.raw > 0 ? '+' : '') + c.raw; } } } },
      scales: { x: { min: -1, max: 1, grid: { color: grid }, ticks: { color: tick, stepSize: 0.5 } },
                y: { grid: { display: false }, ticks: { color: tick } } } }
  });
})();
</script>

<?php if ($pinData): ?>
<script>
window.nhDashMap = function () {
  var pins = <?= json_encode($pinData, $jsonFlags) ?>;
  var typeLabels = <?= json_encode($typeLabels, $jsonFlags) ?>;
  var typeColors = <?= json_encode($typeColors, $jsonFlags) ?>;
  var el = document.getElementById('nhmap');
  if (!el || typeof google === 'undefined') return;
  var map = new google.maps.Map(el, { center: { lat: 15.0, lng: 101.5 }, zoom: 5,
    mapTypeId: 'terrain',
    streetViewControl: true,   // เปิด Street View (ลาก pegman ลงบนถนนเพื่อดูภาพถนน)
    // ปุ่มสลับมุมมอง: แผนที่ / ดาวเทียม / ผสม / ภูมิประเทศ
    mapTypeControl: true,
    mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
      mapTypeIds: ['roadmap', 'satellite', 'hybrid', 'terrain'] } });
  var info = new google.maps.InfoWindow();
  var bounds = new google.maps.LatLngBounds();
  pins.forEach(function (p) {
    var pos = { lat: p.lat, lng: p.lng };
    var mk = new google.maps.Marker({ position: pos, map: map,
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6,
        fillColor: typeColors[p.type] || '#888780', fillOpacity: 0.92, strokeColor: '#fff', strokeWeight: 1 } });
    mk.addListener('click', function () {
      info.setContent('<div style="font-size:13px;max-width:230px;color:#222;"><b>' + (p.name || '-') + '</b><br>' +
        (p.prov ? p.prov + '<br>' : '') + 'คะแนน ' + (Math.round(p.score * 100) / 100) + ' · ' + (typeLabels[p.type] || '-') + '</div>');
      info.open(map, mk);
    });
    bounds.extend(pos);
  });
  if (pins.length > 1) map.fitBounds(bounds);
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= View::e($googleKey) ?>&loading=async&callback=nhDashMap" async></script>
<?php endif; ?>
