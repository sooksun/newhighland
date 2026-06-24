<?php
/** @var string $content; @var string $title */
$role = Auth::role();
$roleMeta = [
  'school' => ['label' => 'โรงเรียน',       'badge' => 'badge-highland'],
  'sao'    => ['label' => 'สำนักงานเขต',     'badge' => 'badge-island'],
  'admin'  => ['label' => 'สพฐ. (ส่วนกลาง)', 'badge' => 'badge-primary'],
];
$rm = $roleMeta[$role] ?? $roleMeta['school'];
$userName = (string) Auth::name();
$initial  = function_exists('mb_substr') ? mb_substr($userName !== '' ? $userName : 'ผ', 0, 1, 'UTF-8') : 'ผ';

// active nav: เทียบ path ปัจจุบันกับ base path
$curPath = trim(str_replace(App::basePath(), '', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), '/');
$seg = explode('/', $curPath)[0] ?? '';
$navLinks = [
  ['dashboard', 'แดชบอร์ด',          'dashboard',  ['', 'dashboard']],
  ['highland',  'ประเมินพื้นที่สูง',   'mountain',    ['highland', 'map']],
  ['island',    'ประเมินพื้นที่เกาะ',  'waves',       ['island']],
];
// รายงานสถิติ (Executive dashboard) — เฉพาะ สพท./สพฐ.
if (in_array($role, ['sao', 'admin'], true)) {
  $navLinks[] = ['report', 'รายงานสถิติ', 'trendUp', ['report']];
}
// ขั้นรับรอง (รออนุมัติ) — เฉพาะ สพท./สพฐ.; เขตชี้ไปพื้นที่ที่ตนดูแล, สพฐ. ครอบคลุมทั้งสองพื้นที่
if (in_array($role, ['sao', 'admin'], true)) {
  $certPath = $role === 'sao' ? \App\Services\SchoolMenu::current()['cert'] : 'highland/cert';
  $navLinks[] = [$certPath, 'รออนุมัติ (สพท.)', 'shieldCheck', ['highland/cert', 'island/cert']];
}
$navLinks[] = ['confirm', 'รับรองการคงอยู่', 'shieldCheck', ['confirm']];
// จัดการผู้ใช้ + รหัสผ่าน — สพฐ. (ทุกบัญชี) + สพท. (เฉพาะบัญชีโรงเรียนในเขตตน)
if (in_array($role, ['sao', 'admin'], true)) {
  $navLinks[] = ['admin/users', 'จัดการผู้ใช้', 'users', ['admin']];
}

// auto menu filter: โรงเรียน/เขต เห็นเฉพาะเมนูที่ตรงคุณสมบัติ (กันลงข้อมูลผิดประเภท); สพฐ. เห็นครบ
// 'admin/users' เป็นเมนูจัดการข้ามพื้นที่ — คงไว้เสมอสำหรับเขต (ไม่ถูกกรองออกแม้ไม่อยู่ใน nav ที่แคชไว้)
if (in_array($role, ['school', 'sao'], true)) {
  $allowedNav = \App\Services\SchoolMenu::current()['nav'];
  $navLinks = array_values(array_filter($navLinks, fn($l) => in_array($l[0], ['admin/users', 'report'], true) || in_array($l[0], $allowedNav, true)));
}

// หน้ารับรอง (cert) ต้อง active ที่เมนู "รออนุมัติ" เท่านั้น ไม่ใช่เมนูประเมิน
$onCert = str_starts_with($curPath, 'highland/cert') || str_starts_with($curPath, 'island/cert');
$navIsActive = function (string $path, array $matches) use ($curPath, $seg, $onCert): bool {
  if ($path === 'highland/cert' || $path === 'island/cert') return $onCert;   // เมนูรออนุมัติ
  if ($onCert) return false;                        // อยู่หน้า cert: เมนูอื่นไม่ active
  return in_array($seg, $matches, true);
};
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
    <title><?= View::e($title ?? '') ?> | <?= View::e(App::config('app_name')) ?></title>
    <link rel="icon" type="image/png" href="<?= App::url('images/logo.png') ?>">
    <script>(function(){try{var t=localStorage.getItem('nh-theme')||'light';document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= App::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-root">
<header class="nh-topbar">
  <div class="container nav-inner">
    <a class="brand" href="<?= App::url('dashboard') ?>">
      <?= nh_brand_mark(38, 'brand-mark') ?>
      <span class="brand-text">
        <b>โรงเรียนพื้นที่ลักษณะพิเศษ</b>
        <span>สนผ. · สพฐ. · ปีงบประมาณ <?= View::e(App::acadYear()) ?></span>
      </span>
    </a>

    <nav class="nav-links nav-desktop" aria-label="เมนูหลัก">
      <?php foreach ($navLinks as [$path, $label, $icon, $matches]): $active = $navIsActive($path, $matches) ? ' active' : ''; ?>
        <a class="nh-nav-link<?= $active ?>" href="<?= App::url($path) ?>"><?= nh_icon($icon, 17) ?> <?= View::e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-right">
      <button type="button" class="theme-toggle" id="themeToggle" aria-label="สลับธีม" title="สลับโหมดสว่าง/มืด"></button>
      <div class="usermenu">
        <span class="badge <?= $rm['badge'] ?>"><?= View::e($rm['label']) ?></span>
        <span class="u-meta"><b><?= View::e($userName) ?></b><span>เข้าสู่ระบบแล้ว</span></span>
        <span class="avatar" aria-hidden="true"><?= View::e($initial) ?></span>
        <a class="btn btn-icon btn-ghost" href="<?= App::url('auth/logout') ?>" title="ออกจากระบบ" aria-label="ออกจากระบบ"><?= nh_icon('logout', 18) ?></a>
      </div>
      <button type="button" class="btn btn-icon btn-ghost nav-toggle" id="navToggle" aria-label="เมนู"><?= nh_icon('menu') ?></button>
    </div>
  </div>
  <div class="container nav-collapse" id="navCollapse">
    <div class="stack gap-1" style="padding:8px 0 12px;">
      <?php foreach ($navLinks as [$path, $label, $icon, $matches]): $active = $navIsActive($path, $matches) ? ' active' : ''; ?>
        <a class="nh-nav-link<?= $active ?>" href="<?= App::url($path) ?>"><?= nh_icon($icon, 17) ?> <?= View::e($label) ?></a>
      <?php endforeach; ?>
      <a class="nh-nav-link" href="<?= App::url('auth/logout') ?>"><?= nh_icon('logout', 17) ?> ออกจากระบบ</a>
    </div>
  </div>
</header>

<main class="page">
  <?php if ($bc = nh_breadcrumb($curPath)): ?>
  <div class="container"><?= $bc ?></div>
  <?php endif; ?>
  <div class="container">
    <?php foreach (Flash::pull() as $f): $type = View::e($f['type']); ?>
      <div class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <?= nh_icon(in_array($f['type'], ['success'], true) ? 'checkCircle' : ($f['type'] === 'danger' ? 'alertCircle' : ($f['type'] === 'warning' ? 'alertTri' : 'info')), 20) ?>
        <div class="grow"><?= View::e($f['message']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
      </div>
    <?php endforeach; ?>
  </div>
  <?= $content ?>
</main>

<footer class="nh-footer">
  <div class="container nh-footer-inner">
    <div class="f-brand">
      <?= nh_brand_mark(44) ?>
      <div>
        <h4>ระบบคัดกรองและรับรองโรงเรียนพื้นที่ลักษณะพิเศษ</h4>
        <p style="color:#b9c6df;font-size:.875rem;max-width:42ch;">ประเมินและรับรองสถานะโรงเรียนพื้นที่ภูเขาสูงในถิ่นทุรกันดารและพื้นที่เกาะ ปีงบประมาณ <?= View::e(App::acadYear()) ?></p>
      </div>
    </div>
    <div>
      <h4>กระบวนการ</h4>
      <div class="f-list">
        <a href="<?= App::url('highland') ?>">ประเมินพื้นที่สูงใหม่</a>
        <a href="<?= App::url('island') ?>">ประเมินพื้นที่เกาะใหม่</a>
        <a href="<?= App::url('confirm?area=1') ?>">รับรองการคงอยู่ (พื้นที่สูง)</a>
        <a href="<?= App::url('confirm?area=2') ?>">รับรองการคงอยู่ (พื้นที่เกาะ)</a>
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
    <span>© <?= View::e(App::acadYear()) ?> สำนักนโยบายและแผนการศึกษาขั้นพื้นฐาน · สพฐ. กระทรวงศึกษาธิการ</span>
  </div>
</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  // ---- ปุ่มสลับธีม สว่าง/มืด (จำค่าใน localStorage) ----
  var ICON_MOON = '<?= addslashes(nh_icon('moon', 19)) ?>';
  var ICON_SUN  = '<?= addslashes(nh_icon('sun', 19)) ?>';
  var root = document.documentElement, btn = document.getElementById('themeToggle');
  function paint(t){ if(btn) btn.innerHTML = (t === 'dark' ? ICON_SUN : ICON_MOON); }
  paint(root.getAttribute('data-theme') || 'light');
  if (btn) btn.addEventListener('click', function () {
    var t = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    root.setAttribute('data-theme', t); root.setAttribute('data-bs-theme', t);
    try { localStorage.setItem('nh-theme', t); } catch (e) {}
    paint(t);
  });
  // ---- เมนูมือถือ ----
  var nt = document.getElementById('navToggle'), nc = document.getElementById('navCollapse');
  if (nt && nc) nt.addEventListener('click', function () { nc.classList.toggle('open'); });

  // ---- กันกดปุ่ม "บันทึก/ส่งข้อมูล" ซ้ำ (double submit) ----
  // ทำงานใน bubble phase: ถ้าฟอร์มยกเลิกการส่งเอง (validation/JS) จะข้าม
  // และใช้ setTimeout(0) เพื่อให้เบราว์เซอร์เก็บค่าปุ่มที่กด (name/value) ก่อนค่อยล็อกปุ่ม
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    setTimeout(function () {
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
        btn.disabled = true;
        btn.classList.add('disabled');
        if (btn.tagName === 'BUTTON' && !btn.dataset.label) {
          btn.dataset.label = btn.innerHTML;
          btn.innerHTML = 'กำลังบันทึก…';
        }
      });
    }, 0);
  }, false);
})();
</script>
</body>
</html>
