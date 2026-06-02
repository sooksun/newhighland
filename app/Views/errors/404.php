<div class="container">
  <div class="error-page" style="flex:none;padding:var(--s-8) var(--s-5);">
    <div>
      <div style="color:var(--brand-primary);margin-bottom:var(--s-3);"><?= nh_icon('search', 56) ?></div>
      <div class="error-code">404</div>
      <h1 class="mt-2">ไม่พบหน้าที่ต้องการ</h1>
      <p class="muted mt-2">หน้าที่ท่านเรียกอาจถูกย้าย ลบ หรือไม่มีอยู่ในระบบ</p>
      <a href="<?= App::url('dashboard') ?>" class="btn btn-primary mt-4"><?= nh_icon('home', 18) ?> กลับหน้าหลัก</a>
    </div>
  </div>
</div>
