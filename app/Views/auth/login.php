<div class="auth-wrap">
  <!-- art side -->
  <aside class="auth-art">
    <a class="brand" href="<?= App::url('') ?>" style="color:#fff;">
      <?= nh_brand_mark(40, 'brand-mark') ?>
      <span class="brand-text">
        <b style="color:#fff;">โรงเรียนพื้นที่ลักษณะพิเศษ</b>
        <span style="color:#cdd9ef;">สนผ. · สพฐ.</span>
      </span>
    </a>
    <div>
      <h2>ระบบคัดกรองและรับรอง<br>โรงเรียนพื้นที่ลักษณะพิเศษ</h2>
      <p>ประเมินโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดารและพื้นที่เกาะ ปีงบประมาณ <?= View::e(App::acadYear()) ?></p>
      <div class="a-points">
        <div class="a-point"><?= nh_icon('checkCircle', 20) ?> <span>ปักหมุดและวัดความสูงด้วยแผนที่</span></div>
        <div class="a-point"><?= nh_icon('checkCircle', 20) ?> <span>แบบประเมิน 16 ตัวชี้วัด คิดคะแนนอัตโนมัติ</span></div>
        <div class="a-point"><?= nh_icon('checkCircle', 20) ?> <span>รับรองผลและพิมพ์เอกสารในที่เดียว</span></div>
      </div>
    </div>
    <div class="auth-art-foot">
      <div class="scene" style="max-width:360px;flex:1;aspect-ratio:4/3;">
        <img src="<?= App::asset('img/scene-banner.png') ?>" alt="โรงเรียนในพื้นที่ลักษณะพิเศษ">
      </div>
      <img src="<?= App::asset('img/student-akha.png') ?>" alt="นักเรียนชาวเขา" class="student-cut auth-student">
    </div>
  </aside>

  <!-- form side -->
  <main class="auth-form-col">
    <div class="auth-card anim-up">
      <div class="text-c" style="margin-bottom:var(--s-5);">
        <h1 style="font-size:var(--fs-h2);">เข้าสู่ระบบ</h1>
        <p class="muted" style="margin-top:4px;">เลือกบทบาทและกรอกข้อมูลเพื่อเข้าใช้งาน</p>
      </div>

      <!-- role segmented (ช่วยแนะนำเท่านั้น ไม่กระทบการส่งฟอร์ม) -->
      <div class="role-seg" role="tablist" aria-label="เลือกบทบาท" style="margin-bottom:var(--s-5);">
        <button type="button" class="active" data-label="รหัสโรงเรียน"><?= nh_icon('school', 18) ?> โรงเรียน</button>
        <button type="button" data-label="ชื่อผู้ใช้"><?= nh_icon('building', 18) ?> เขตพื้นที่</button>
        <button type="button" data-label="ชื่อผู้ใช้"><?= nh_icon('shieldCheck', 18) ?> สพฐ.</button>
      </div>

      <form method="post" action="<?= App::url('auth/login') ?>" autocomplete="off" class="stack gap-4">
        <?= Csrf::field() ?>
        <div class="field">
          <label class="label" for="username" id="usernameLabel">รหัสโรงเรียน<span class="req">*</span></label>
          <div class="nh-ig">
            <span class="ig-icon"><?= nh_icon('user', 18) ?></span>
            <input type="text" id="username" name="username" class="form-control" placeholder="ชื่อผู้ใช้ / รหัสโรงเรียน" required autofocus autocomplete="username">
          </div>
        </div>
        <div class="field">
          <label class="label" for="password">รหัสผ่าน<span class="req">*</span></label>
          <div class="nh-ig">
            <span class="ig-icon"><?= nh_icon('shieldCheck', 18) ?></span>
            <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน" required autocomplete="current-password" style="padding-right:44px;">
            <button type="button" id="pwToggle" aria-label="แสดง/ซ่อนรหัสผ่าน"
              style="position:absolute;right:8px;background:none;border:0;color:var(--text-muted);padding:6px;display:flex;"><?= nh_icon('eye', 18) ?></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100"><?= nh_icon('login', 20) ?> เข้าสู่ระบบ</button>
      </form>

      <p class="hint text-c mt-4">
        ใช้บัญชีเดิม: โรงเรียน / เขตพื้นที่ (สพป./สพม.) / สพฐ.<br>
        ลืมรหัสผ่าน? ติดต่อสำนักงานเขตพื้นที่การศึกษาต้นสังกัด หรือผู้ดูแลระบบ สนผ. สพฐ.
      </p>
    </div>
  </main>
</div>

<script>
(function () {
  var segs = document.querySelectorAll('.role-seg button'), lbl = document.getElementById('usernameLabel');
  segs.forEach(function (b) {
    b.addEventListener('click', function () {
      segs.forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      if (lbl) lbl.innerHTML = b.dataset.label + '<span class="req">*</span>';
    });
  });
  var pt = document.getElementById('pwToggle'), pw = document.getElementById('password');
  if (pt && pw) pt.addEventListener('click', function () { pw.type = pw.type === 'password' ? 'text' : 'password'; });
})();
</script>
