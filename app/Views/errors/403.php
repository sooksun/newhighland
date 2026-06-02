<div class="container">
  <div class="error-page" style="flex:none;padding:var(--s-8) var(--s-5);">
    <div>
      <div style="color:var(--danger);margin-bottom:var(--s-3);"><?= nh_icon('shieldCheck', 56) ?></div>
      <div class="error-code">403</div>
      <h1 class="mt-2">ไม่มีสิทธิ์เข้าถึงหน้านี้</h1>
      <p class="muted mt-2">บัญชีของท่านไม่ได้รับอนุญาตให้เข้าถึงส่วนนี้ของระบบ</p>
      <a href="<?= App::url('dashboard') ?>" class="btn btn-primary mt-4"><?= nh_icon('home', 18) ?> กลับหน้าหลัก</a>
    </div>
  </div>
</div>
