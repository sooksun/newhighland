<?php
/** @var array $stats */
$role = $stats['role'];
$acad = $stats['acad_year'];
$roleMeta = [
  'school' => ['label' => 'โรงเรียน',       'badge' => 'badge-highland', 'greet' => 'ยินดีต้อนรับ'],
  'sao'    => ['label' => 'สำนักงานเขต',     'badge' => 'badge-island',   'greet' => 'ภาพรวมสำนักงานเขต'],
  'admin'  => ['label' => 'สพฐ. (ส่วนกลาง)', 'badge' => 'badge-primary',  'greet' => 'ภาพรวมส่วนกลาง สพฐ.'],
];
$rm = $roleMeta[$role] ?? $roleMeta['school'];

// การ์ดกระบวนการ 4 ส่วน (accent ตามประเภทพื้นที่)
$tasks = [
  ['accent'=>'highland','icon'=>'mountain',   't'=>'ประเมินพื้นที่สูงใหม่',        'badge'=>'พร้อมใช้งาน','badgeCls'=>'badge-success','d'=>'คัดกรองโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดาร เต็มกระบวนการ','meta'=>'ปักหมุด → วัดความสูง → 16 ข้อ → พิมพ์','url'=>App::url('highland'),'action'=>'เริ่ม'],
  ['accent'=>'island',  'icon'=>'waves',      't'=>'ประเมินพื้นที่เกาะใหม่',       'badge'=>'พร้อมใช้งาน','badgeCls'=>'badge-success','d'=>'คัดกรองโรงเรียนพื้นที่เกาะตามเกณฑ์เฉพาะ','meta'=>'แบบประเมิน 15 ข้อ (เกาะ)','url'=>App::url('island'),'action'=>'เริ่ม'],
  ['accent'=>'highland','icon'=>'shieldCheck','t'=>'รับรองการคงอยู่ — พื้นที่สูง','badge'=>'พร้อมใช้งาน','badgeCls'=>'badge-success','d'=>'ยืนยันสถานะ (ไม่ยุบ / ไม่เลิก / ไม่รวม) และรับรองโดยสำนักงานเขต','meta'=>'รายชื่ออ้างอิงปี 2566','url'=>App::url('confirm?area=1'),'action'=>'เปิดรายการ'],
  ['accent'=>'island',  'icon'=>'shieldCheck','t'=>'รับรองการคงอยู่ — พื้นที่เกาะ','badge'=>'พร้อมใช้งาน','badgeCls'=>'badge-success','d'=>'ยืนยันการคงอยู่ของโรงเรียนพื้นที่เกาะ พร้อมการรับรอง','meta'=>'รายชื่ออ้างอิงปี 2566','url'=>App::url('confirm?area=2'),'action'=>'เปิดรายการ'],
];

$tones = [
  'primary' => 'background:var(--brand-primary-050);color:var(--brand-primary-700);',
  'emerald' => 'background:var(--brand-emerald-050);color:var(--brand-emerald);',
  'island'  => 'background:var(--island-050);color:var(--island-700);',
  'warning' => 'background:var(--warning-bg);color:var(--warning);',
];
?>
<div class="container">
  <div class="page-head anim-up">
    <div>
      <div class="nh-row gap-2 wrap">
        <span class="badge <?= $rm['badge'] ?>"><?= View::e($rm['label']) ?></span>
        <span class="badge badge-neutral"><?= nh_icon('calendar', 13) ?> ปีงบประมาณ <?= View::e($acad) ?></span>
      </div>
      <h1 class="mt-2"><?= View::e($rm['greet']) ?> · <?= View::e(Auth::name()) ?></h1>
      <p class="sub">เลือกกระบวนการที่ต้องการดำเนินการ หรือดูภาพรวมสถานะการประเมิน</p>
    </div>
    <?php if ($role === 'school'): ?>
      <a href="<?= App::url('highland') ?>" class="btn btn-primary"><?= nh_icon('mountain', 18) ?> เริ่มประเมินพื้นที่สูง</a>
    <?php endif; ?>
  </div>

  <div class="stat-grid anim-up">
    <div class="card stat-card">
      <span class="s-ic" style="<?= $tones['primary'] ?>"><?= nh_icon('clipboard', 24) ?></span>
      <div style="min-width:0;"><div class="n tnum"><?= number_format((int) $stats['highland_cnt']) ?></div><div class="l">แบบประเมินพื้นที่สูง (ปี <?= View::e($acad) ?>)</div></div>
    </div>
    <div class="card stat-card">
      <span class="s-ic" style="<?= $tones['emerald'] ?>"><?= nh_icon('list', 24) ?></span>
      <div style="min-width:0;"><div class="n tnum">16</div><div class="l">ตัวชี้วัดการประเมิน</div></div>
    </div>
    <div class="card stat-card">
      <span class="s-ic" style="<?= $tones['island'] ?>"><?= nh_icon('layers', 24) ?></span>
      <div style="min-width:0;"><div class="n tnum">2</div><div class="l">ประเภทพื้นที่พิเศษ</div></div>
    </div>
    <div class="card stat-card">
      <span class="s-ic" style="<?= $tones['warning'] ?>"><?= nh_icon('award', 24) ?></span>
      <div style="min-width:0;"><div class="n tnum">&ge;50</div><div class="l">เกณฑ์ผ่าน (คะแนนรวม)</div></div>
    </div>
  </div>

  <div class="nh-row between" style="margin-bottom:var(--s-4);">
    <h2 style="font-size:var(--fs-h3);">กระบวนการ</h2>
  </div>
  <div class="proc-grid anim-up">
    <?php foreach ($tasks as $t):
      $isIsland = $t['accent'] === 'island';
      $bar = $isIsland ? 'var(--island)' : 'var(--highland)';
      $icTone = $isIsland ? 'background:var(--island-050);color:var(--island-700);' : 'background:var(--highland-050);color:var(--highland-700);';
    ?>
      <article class="card card-hover task-card">
        <div class="accent-bar" style="background:<?= $bar ?>;"></div>
        <div class="tc-top">
          <span class="tc-ic" style="<?= $icTone ?>"><?= nh_icon($t['icon'], 27) ?></span>
          <div class="grow">
            <div class="nh-row gap-2 wrap" style="margin-bottom:5px;">
              <h3><?= View::e($t['t']) ?></h3>
              <span class="badge <?= $t['badgeCls'] ?>"><?= View::e($t['badge']) ?></span>
            </div>
            <p><?= View::e($t['d']) ?></p>
          </div>
        </div>
        <div class="tc-foot">
          <span class="muted" style="font-size:var(--fs-xs);"><?= View::e($t['meta']) ?></span>
          <a href="<?= View::e($t['url']) ?>" class="btn btn-sm btn-outline-secondary"><?= View::e($t['action']) ?> <?= nh_icon('arrowRight', 16) ?></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
