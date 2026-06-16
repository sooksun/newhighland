<?php
/** หน้าแรกสาธารณะ — ข้อมูลโครงการ + ปุ่มเข้าสู่ระบบ */
$loginUrl = App::url('auth/login');
$acad = App::acadYear();

$procs = [
  [1,'mountain','highland','ประเมินพื้นที่สูงใหม่','คัดกรองโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดารที่ยังไม่เคยได้รับการประเมิน เต็มกระบวนการ','พร้อมใช้งาน','badge-success'],
  [2,'waves','island','ประเมินพื้นที่เกาะใหม่','คัดกรองโรงเรียนพื้นที่เกาะตามเกณฑ์เฉพาะ ใช้กระบวนการเดียวกับพื้นที่สูง','พร้อมใช้งาน','badge-success'],
  [3,'shieldCheck','highland','รับรองการคงอยู่ — พื้นที่สูง','ยืนยันสถานะโรงเรียนที่เป็นพื้นที่พิเศษอยู่แล้ว (ไม่ยุบ / ไม่เลิก / ไม่รวม)','พร้อมใช้งาน','badge-success'],
  [4,'shieldCheck','island','รับรองการคงอยู่ — พื้นที่เกาะ','ยืนยันการคงอยู่ของโรงเรียนพื้นที่เกาะ พร้อมการรับรองจากสำนักงานเขต','พร้อมใช้งาน','badge-success'],
];
$flow = [
  ['mapPin','ปักหมุดที่ตั้ง','ระบุพิกัดโรงเรียนบนแผนที่'],
  ['ruler','วัดความสูง/ระยะทาง','ความสูงสูงสุด + ระยะถึงศาลากลาง'],
  ['clipboard','แบบประเมิน 16 ข้อ','กรอกข้อมูล 11 หมวด'],
  ['award','คิดคะแนน','รวมคะแนน + จัดระดับ'],
  ['printer','พิมพ์ผล','ออกเอกสาร PDF'],
  ['shieldCheck','รับรองโดย สพท.','ยืนยันข้อมูลถูกต้อง โดย สพป./สพม.'],
];
$flowIsland = [
  ['school','เลือกโรงเรียน','สังกัด · โรงเรียนพื้นที่เกาะ'],
  ['clipboard','แบบประเมิน 15 ข้อ','กรอกข้อมูล · คิดคะแนน (เต็ม 100)'],
  ['printer','พิมพ์ผล','ออกเอกสาร PDF'],
  ['shieldCheck','รับรองโดย สพท.','ยืนยันข้อมูลถูกต้อง โดย สพป./สพม.'],
];
$accentBg = fn($cls) => $cls === 'island'
  ? 'background:var(--island-050);color:var(--island-700);'
  : 'background:var(--highland-050);color:var(--highland-700);';
?>
<!-- mini top bar -->
<header class="nh-topbar">
  <div class="container nav-inner">
    <a class="brand" href="<?= App::url('') ?>">
      <?= nh_brand_mark(38, 'brand-mark') ?>
      <span class="brand-text"><b>โรงเรียนพื้นที่ลักษณะพิเศษ</b><span>สนผ. · สพฐ.</span></span>
    </a>
    <div class="nav-right">
      <a href="#program" class="nh-nav-link nav-desktop">เกี่ยวกับโครงการ</a>
      <a href="#flow" class="nh-nav-link nav-desktop">ขั้นตอน</a>
      <button type="button" class="theme-toggle" id="themeToggle" aria-label="สลับธีม" title="สลับโหมดสว่าง/มืด"></button>
      <a href="<?= $loginUrl ?>" class="btn btn-primary"><?= nh_icon('login', 18) ?> เข้าสู่ระบบ</a>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="container hero-grid">
    <div class="anim-up">
      <div class="hero-pills">
        <span class="badge badge-highland"><?= nh_icon('mountain', 13) ?> พื้นที่ภูเขาสูง</span>
        <span class="badge badge-island"><?= nh_icon('waves', 13) ?> พื้นที่เกาะ</span>
        <span class="badge badge-neutral"><?= nh_icon('calendar', 13) ?> ปีงบประมาณ <?= View::e($acad) ?></span>
      </div>
      <h1>ระบบคัดกรองและรับรอง<br><span class="hl">โรงเรียนพื้นที่ลักษณะพิเศษ</span></h1>
      <p class="lede">
        เครื่องมือกลางของ สพฐ. สำหรับประเมิน คัดกรอง และรับรองสถานะโรงเรียนพื้นที่ภูเขาสูง
        ในถิ่นทุรกันดารและพื้นที่เกาะ ครบทุกกระบวนการในที่เดียว — ตั้งแต่ปักหมุด วัดความสูง
        กรอกแบบประเมิน ไปจนถึงการรับรองและพิมพ์ผล
      </p>
      <div class="hero-cta">
        <a href="<?= $loginUrl ?>" class="btn btn-primary btn-lg"><?= nh_icon('login', 20) ?> เข้าสู่ระบบ</a>
        <a class="btn btn-outline-secondary btn-lg" href="#program"><?= nh_icon('fileText', 20) ?> แนวทางการดำเนินงาน</a>
      </div>
    </div>
    <div class="hero-art anim-up">
      <div class="scene" role="img" aria-label="ภาพประกอบโรงเรียนในพื้นที่ลักษณะพิเศษ">
        <img src="<?= App::asset('img/scene-banner.png') ?>" alt="โรงเรียนที่ตั้งในพื้นที่ลักษณะพิเศษ — ภูเขาสูงและเกาะ">
      </div>
      <div class="float-card" style="top:-14px;left:-14px;">
        <span class="fc-ic" style="background:var(--highland-050);color:var(--highland-700);"><?= nh_icon('mountain', 20) ?></span>
        <div><b>ความสูงสูงสุด</b><span>เกณฑ์ &ge; 500 ม.</span></div>
      </div>
      <div class="float-card" style="bottom:-16px;right:-10px;">
        <span class="fc-ic" style="background:var(--success-bg);color:var(--success);"><?= nh_icon('award', 20) ?></span>
        <div><b>คะแนนรวม &ge; 50</b><span>= พื้นที่พิเศษ</span></div>
      </div>
      <img src="<?= App::asset('img/student-braids.png') ?>" alt="นักเรียนชาวเขา" class="student-cut hero-student">
    </div>
  </div>
  <div class="container" style="padding-bottom:var(--s-8);">
    <div class="card card-pad statband">
      <?php foreach ([['4','กระบวนการหลัก'],['2','ประเภทพื้นที่พิเศษ'],['16','ตัวชี้วัดการประเมิน'],['22','จังหวัดเป้าหมาย']] as [$n,$l]): ?>
        <div class="stat-big"><div class="n tnum"><?= View::e($n) ?></div><div class="l"><?= View::e($l) ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PROGRAM / processes -->
<section class="section" id="program" style="background:var(--surface);">
  <div class="container">
    <div class="prog-head-row" style="margin-bottom:var(--s-6);">
      <img src="<?= App::asset('img/student-south-purple.png') ?>" alt="นักเรียนโรงเรียนพื้นที่ภาคใต้" class="student-cut prog-student" style="width:230px;">
      <div class="section-head grow" style="margin:0;">
        <div class="eyebrow">ขอบเขตของระบบ</div>
        <h2>4 กระบวนการสำหรับปีงบประมาณ <?= View::e($acad) ?></h2>
        <p>รองรับทั้งการประเมินโรงเรียนที่ตกหล่นแบบเต็มกระบวนการ และการยืนยันการคงอยู่ของโรงเรียนที่เป็นพื้นที่พิเศษอยู่แล้ว</p>
      </div>
      <img src="<?= App::asset('img/student-hill-girl.png') ?>" alt="นักเรียนชาวเขา" class="student-cut prog-student" style="width:215px;">
    </div>
    <div class="proc-grid">
      <?php foreach ($procs as [$n,$ic,$cls,$t,$d,$tag,$tagcls]): ?>
        <article class="card card-hover proc">
          <span class="p-num"><?= $n ?></span>
          <span class="p-ic" style="<?= $accentBg($cls) ?>"><?= nh_icon($ic, 26) ?></span>
          <div>
            <div class="nh-row gap-2 wrap" style="margin-bottom:6px;">
              <h3><?= View::e($t) ?></h3>
              <span class="badge <?= $tagcls ?>"><?= View::e($tag) ?></span>
            </div>
            <p><?= View::e($d) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FLOW -->
<section class="section" id="flow">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow"><?= nh_icon('mountain', 14) ?> ขั้นตอนการประเมินพื้นที่สูง</div>
      <h2>ประเมินโรงเรียนพื้นที่สูงในถิ่นทุรกันดาร มี 6 ขั้นตอน</h2>
      <p>กระบวนการประเมินพื้นที่สูงแบบเต็มรูปแบบ ทำได้ครบจบในระบบเดียว</p>
    </div>
    <div class="card card-pad">
      <div class="flow-steps">
        <?php foreach ($flow as [$ic,$t,$d]): ?>
          <div class="flow-step">
            <div class="fs-n"><?= nh_icon($ic, 22) ?></div>
            <h4><?= View::e($t) ?></h4>
            <p><?= View::e($d) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- FLOW: พื้นที่เกาะ -->
    <div class="section-head mt-5">
      <div class="eyebrow"><?= nh_icon('waves', 14) ?> ขั้นตอนการประเมินพื้นที่เกาะ</div>
      <h2>ประเมินโรงเรียนพื้นที่เกาะ ครบใน 4 ขั้นตอน</h2>
      <p>ใช้เกณฑ์เฉพาะของพื้นที่เกาะ ไม่ต้องปักหมุด/วัดความสูง — เลือกโรงเรียนแล้วกรอกแบบประเมินได้ทันที</p>
    </div>
    <div class="card card-pad">
      <div class="flow-steps">
        <?php foreach ($flowIsland as [$ic,$t,$d]): ?>
          <div class="flow-step">
            <div class="fs-n" style="background:var(--island-050);color:var(--island-700);"><?= nh_icon($ic, 22) ?></div>
            <h4><?= View::e($t) ?></h4>
            <p><?= View::e($d) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ภาพนักเรียนในพื้นที่ลักษณะพิเศษ -->
    <div class="card mt-5" style="padding:0;overflow:hidden;border-radius:var(--r-xl);position:relative;">
      <img src="<?= App::asset('img/students-south-group.png') ?>" alt="นักเรียนในโรงเรียนพื้นที่ลักษณะพิเศษ"
           style="width:100%;height:clamp(220px,30vw,360px);object-fit:cover;object-position:center 28%;display:block;">
      <div style="position:absolute;left:0;right:0;bottom:0;padding:var(--s-6) var(--s-5) var(--s-5);background:linear-gradient(to top,rgba(13,27,62,.82),rgba(13,27,62,.15) 70%,transparent);color:#fff;">
        <b style="font-family:var(--font-head);font-size:var(--fs-h3);display:block;">เพื่อเด็กทุกคนในพื้นที่ลักษณะพิเศษ</b>
        <div style="color:#dbe4f5;font-size:.9rem;margin-top:2px;">ทั้งบนภูเขาสูงในถิ่นทุรกันดาร และบนเกาะห่างไกล ทั่วประเทศ</div>
      </div>
    </div>

    <!-- criteria highlight + roles -->
    <div class="proc-grid mt-5">
      <div class="card card-pad">
        <div class="eyebrow" style="color:var(--highland-700);">เกณฑ์การตัดสิน</div>
        <h3 class="mt-2">คะแนนรวม &ge; 50 = โรงเรียนพื้นที่ลักษณะพิเศษ</h3>
        <div class="stack gap-2 mt-4">
          <?php foreach ([['50–59','ยุ่งยาก','badge-info'],['60–69','ยุ่งยากมาก','badge-warning'],['70 ขึ้นไป','ยุ่งยากมากที่สุด','badge-danger']] as $i => [$r,$l,$c]): ?>
            <div class="nh-row between" style="padding:8px 0;<?= $i < 2 ? 'border-bottom:1px solid var(--border);' : '' ?>">
              <span class="tnum" style="font-weight:600;font-family:var(--font-head);"><?= View::e($r) ?> คะแนน</span>
              <span class="badge <?= $c ?>"><?= View::e($l) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card card-pad" style="background:var(--brand-navy-900);color:#fff;border-color:transparent;">
        <div class="nh-row gap-3 items-start">
          <span class="fc-ic" style="background:rgba(255,255,255,.12);color:#fff;width:44px;height:44px;border-radius:12px;display:grid;place-items:center;flex:none;"><?= nh_icon('users', 22) ?></span>
          <div>
            <h3 style="color:#fff;">ผู้ใช้งาน 3 ระดับ</h3>
            <p style="color:#c8d4ea;font-size:.875rem;margin-top:4px;">สิทธิ์การเข้าถึงข้อมูลแยกตามบทบาท</p>
          </div>
        </div>
        <div class="stack gap-3 mt-5">
          <?php foreach ([['โรงเรียน','ปักหมุด กรอกแบบประเมิน ยืนยันการคงอยู่ พิมพ์ผล'],['สำนักงานเขต','ตรวจสอบและรับรองผลโรงเรียนในสังกัด'],['สพฐ. ส่วนกลาง','ดูภาพรวม ตั้งค่า และประกาศผลขั้นสุดท้าย']] as [$t,$d]): ?>
            <div class="nh-row gap-3 items-start">
              <span style="color:var(--brand-emerald);flex:none;margin-top:2px;"><?= nh_icon('checkCircle', 20) ?></span>
              <div><b style="font-family:var(--font-head);"><?= View::e($t) ?></b><div style="color:#b9c6df;font-size:.85rem;"><?= View::e($d) ?></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section" style="background:var(--surface);">
  <div class="container">
    <div class="card cta-band" style="background:radial-gradient(120% 160% at 50% -20%,var(--brand-primary-050),var(--surface));padding:var(--s-7) var(--s-6) 0;">
      <div class="nh-row between" style="align-items:flex-end;gap:var(--s-4);">
        <img src="<?= App::asset('img/student-south-purple.png') ?>" alt="นักเรียนโรงเรียนพื้นที่ภาคใต้" class="student-cut cta-student" style="width:330px;margin-bottom:-10px;">
        <div class="text-c grow" style="padding-bottom:var(--s-7);">
          <?= nh_brand_mark(56) ?>
          <h2 class="mt-4">พร้อมเริ่มการประเมินแล้วหรือยัง</h2>
          <p class="muted mt-2" style="max-width:48ch;margin:8px auto 0;">
            เข้าสู่ระบบด้วยบัญชีโรงเรียน สำนักงานเขต หรือ สพฐ. เพื่อเริ่มกระบวนการคัดกรองและรับรอง
          </p>
          <a href="<?= $loginUrl ?>" class="btn btn-primary btn-lg mt-5"><?= nh_icon('login', 20) ?> เข้าสู่ระบบ</a>
        </div>
        <img src="<?= App::asset('img/student-south-blue.png') ?>" alt="นักเรียนโรงเรียนพื้นที่ภาคใต้" class="student-cut cta-student" style="width:330px;margin-bottom:-10px;">
      </div>
    </div>
  </div>
</section>

<!-- footer -->
<footer class="nh-footer">
  <div class="container nh-footer-inner">
    <div class="f-brand">
      <?= nh_brand_mark(44) ?>
      <div>
        <h4>ระบบคัดกรองและรับรองโรงเรียนพื้นที่ลักษณะพิเศษ</h4>
        <p style="color:#b9c6df;font-size:.875rem;max-width:42ch;">ประเมินและรับรองสถานะโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดารและพื้นที่เกาะ ปีงบประมาณ <?= View::e($acad) ?></p>
      </div>
    </div>
    <div>
      <h4>กระบวนการ</h4>
      <div class="f-list">
        <a href="<?= $loginUrl ?>">ประเมินพื้นที่สูงใหม่</a>
        <a href="<?= $loginUrl ?>">ประเมินพื้นที่เกาะใหม่</a>
        <a href="<?= $loginUrl ?>">รับรองการคงอยู่ (พื้นที่สูง)</a>
        <a href="<?= $loginUrl ?>">รับรองการคงอยู่ (พื้นที่เกาะ)</a>
      </div>
    </div>
    <div>
      <h4>หน่วยงาน</h4>
      <div class="f-list">
        <span style="color:#b9c6df;">สำนักนโยบายและแผนการศึกษาขั้นพื้นฐาน</span>
        <span style="color:#b9c6df;">สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน</span>
        <span style="color:#b9c6df;">กระทรวงศึกษาธิการ</span>
      </div>
    </div>
  </div>
  <div class="container nh-footer-bottom">
    <span>© <?= View::e($acad) ?> สำนักนโยบายและแผนการศึกษาขั้นพื้นฐาน · สพฐ. กระทรวงศึกษาธิการ</span>
  </div>
</footer>
