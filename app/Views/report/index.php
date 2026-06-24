<?php
/**
 * รายงานสถิติ (Executive dashboard)
 * @var int $year; @var int $area; @var array $areas; @var bool $isAdmin
 * @var array $targets; @var array $kpi; @var array $funnel; @var array $typeDist
 * @var array $scoreHist; @var array $provinces; @var array $utils; @var array $utilLabels
 */
$pct = fn($a, $b) => $b > 0 ? round($a * 100 / $b, 1) : 0.0;
$areaName = $area === 2 ? 'พื้นที่เกาะ' : 'พื้นที่สูง';

// ---- KPI ----
$target = (int) $targets['total'];
$done   = (int) $kpi['done'];
$kpiCards = [
  ['ti' => 'school',    'tone' => 'primary', 'label' => 'โรงเรียนเป้าหมาย', 'n' => $target,
   'sub' => 'สูง ' . number_format($targets['high']) . ' · เกาะ ' . number_format($targets['island'])],
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
    <a class="btn btn-sm btn-outline-secondary" href="<?= App::url('report/export?area=' . $area) ?>"><?= nh_icon('fileText', 15) ?> Export CSV</a>
  </div>

  <h1 class="h5 fw-bold mb-1">ภาพรวมการคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ</h1>
  <p class="text-muted small">สรุปความคืบหน้า ระดับความยุ่งยาก การกระจายคะแนน และสาธารณูปโภค<?= $isAdmin ? ' · ระดับประเทศ' : '' ?></p>

  <?php if (count($areas) > 1): ?>
  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $area === 1 ? 'active' : '' ?>" href="<?= App::url('report?area=1') ?>">พื้นที่สูง</a></li>
    <li class="nav-item"><a class="nav-link <?= $area === 2 ? 'active' : '' ?>" href="<?= App::url('report?area=2') ?>">พื้นที่เกาะ</a></li>
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
    mapTypeId: 'terrain', streetViewControl: false, mapTypeControl: false });
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
