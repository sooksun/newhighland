<?php
/** @var string $tab; @var string $q; @var array $rows; @var ?array $edit; @var int $schoolCount; @var bool $only; @var bool $isAdmin */
$isSao = $tab === 'sao';                 // แท็บปัจจุบันคือ "เขต / สพฐ." หรือไม่
$isAdmin = $isAdmin ?? Auth::isAdmin();  // บทบาทผู้ใช้: admin(สพฐ.) เห็น 2 แท็บ ; เขต(สพท.) เห็นเฉพาะโรงเรียนในเขตตน
// ค่าเริ่มต้นของฟอร์ม (เติมจาก $edit ถ้าอยู่โหมดแก้ไข)
$f = function (string $k, $def = '') use ($edit) { return $edit[$k] ?? $def; };
$editing = $edit !== null;
$editKey = $isSao ? ($edit['id'] ?? '') : ($edit['citicens_id'] ?? '');
?>
<div class="container">

  <span class="badge badge-primary" style="margin-bottom:var(--s-3);"><?= nh_icon('shieldCheck', 13) ?> <?= $isAdmin ? 'สพฐ. (ส่วนกลาง)' : 'สำนักงานเขต · ' . View::e(Auth::saoName()) ?></span>
  <h1 class="h5 fw-bold mb-1">จัดการผู้ใช้และรหัสผ่าน</h1>
  <p class="text-muted small"><?= $isAdmin
        ? 'เพิ่ม / แก้ไข / เปลี่ยนรหัสผ่าน บัญชีผู้ใช้ระดับโรงเรียน และสำนักงานเขต/สพฐ.'
        : 'เพิ่ม / แก้ไข / เปลี่ยนรหัสผ่าน บัญชีผู้ใช้ระดับโรงเรียน เฉพาะในเขตของท่าน' ?></p>

  <?php if ($isAdmin): ?>
  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= !$isSao ? 'active' : '' ?>" href="<?= App::url('admin/users?tab=school') ?>"><?= nh_icon('school', 14) ?> โรงเรียน</a></li>
    <li class="nav-item"><a class="nav-link <?= $isSao ? 'active' : '' ?>" href="<?= App::url('admin/users?tab=sao') ?>"><?= nh_icon('shieldCheck', 14) ?> เขต / สพฐ.</a></li>
  </ul>
  <?php endif; ?>

  <!-- ฟอร์มเพิ่ม/แก้ไข -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="fw-bold mb-2"><?= $editing ? '✏️ แก้ไขบัญชี' : '➕ เพิ่มบัญชีใหม่' ?> <?= $isSao ? '(เขต/สพฐ.)' : '(โรงเรียน)' ?></div>
      <form method="post" action="<?= App::url('admin/users/save') ?>" class="row g-2 align-items-end">
        <?= Csrf::field() ?>
        <input type="hidden" name="tab" value="<?= View::e($tab) ?>">
        <input type="hidden" name="key" value="<?= View::e($editKey) ?>">
        <?php if ($isSao): ?>
          <?php if (!$editing): ?>
          <div class="col-md-3"><label class="form-label small mb-0">รหัสหน่วยงาน (id)</label>
            <input name="id" class="form-control form-control-sm" required placeholder="เช่น 00560000"></div>
          <?php else: ?>
            <div class="col-md-3"><label class="form-label small mb-0">รหัสหน่วยงาน</label>
              <input class="form-control form-control-sm" value="<?= View::e($editKey) ?>" disabled></div>
          <?php endif; ?>
          <div class="col-md-4"><label class="form-label small mb-0">ชื่อหน่วยงาน</label>
            <input name="code" class="form-control form-control-sm" required value="<?= View::e($f('code')) ?>" placeholder="เช่น สพป.พะเยา เขต 2"></div>
          <div class="col-md-2"><label class="form-label small mb-0">ชื่อผู้ใช้</label>
            <input name="user" class="form-control form-control-sm" required maxlength="20" value="<?= View::e($f('user')) ?>"></div>
          <div class="col-md-2"><label class="form-label small mb-0">รหัสผ่าน<?= $editing ? ' (เว้นว่าง=ไม่เปลี่ยน)' : '' ?></label>
            <input name="password" class="form-control form-control-sm" <?= $editing ? '' : 'required' ?> maxlength="100"></div>
        <?php else: ?>
          <div class="col-md-4"><label class="form-label small mb-0">ชื่อโรงเรียน</label>
            <input name="name" class="form-control form-control-sm" required value="<?= View::e($f('name')) ?>"></div>
          <div class="col-md-2"><label class="form-label small mb-0">รหัสโรงเรียน (sc_id)</label>
            <input name="sc_id" class="form-control form-control-sm" required value="<?= View::e($f('sc_id')) ?>"></div>
          <div class="col-md-2"><label class="form-label small mb-0">ชื่อผู้ใช้</label>
            <input name="user" class="form-control form-control-sm" required maxlength="8" value="<?= View::e($f('user')) ?>"></div>
          <div class="col-md-2"><label class="form-label small mb-0">รหัสผ่าน<?= $editing ? ' (เว้นว่าง=ไม่เปลี่ยน)' : '' ?></label>
            <input name="password" class="form-control form-control-sm" <?= $editing ? '' : 'required' ?> maxlength="20"></div>
        <?php endif; ?>
        <div class="col-md-2 d-flex gap-1">
          <button class="btn btn-sm btn-primary flex-fill"><?= $editing ? 'บันทึก' : 'เพิ่ม' ?></button>
          <?php if ($editing): ?><a class="btn btn-sm btn-outline-secondary" href="<?= App::url('admin/users?tab=' . $tab . '&q=' . urlencode($q)) ?>">ยกเลิก</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- ค้นหา -->
  <form class="row g-2 mb-2" method="get" action="<?= App::url('admin/users') ?>">
    <input type="hidden" name="tab" value="<?= View::e($tab) ?>">
    <div class="col-md-6"><input class="form-control form-control-sm" name="q" value="<?= View::e($q) ?>"
        placeholder="<?= $isSao ? 'ค้นหา ชื่อหน่วยงาน / ผู้ใช้ / รหัส' : 'ค้นหา ชื่อโรงเรียน / ผู้ใช้ / รหัสโรงเรียน' ?>"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><?= nh_icon('search', 14) ?> ค้นหา</button></div>
    <?php if ($isSao): ?>
      <input type="hidden" name="only" value="0">
      <div class="col-md-4 d-flex align-items-center">
        <div class="form-check mb-0">
          <input class="form-check-input" type="checkbox" name="only" value="1" id="onlyChk" <?= $only ? 'checked' : '' ?> onchange="this.form.submit()">
          <label class="form-check-label small" for="onlyChk">เฉพาะเขตที่มีโรงเรียนพื้นที่สูง/เกาะ</label>
        </div>
      </div>
    <?php else: ?>
      <div class="col-md-4 text-muted small d-flex align-items-center"><?= $isAdmin
          ? 'มีบัญชีโรงเรียนทั้งหมด ' . number_format($schoolCount) . ' บัญชี — พิมพ์คำค้นเพื่อแสดง'
          : 'แสดงบัญชีโรงเรียนในเขตของท่าน — พิมพ์คำค้นเพื่อกรอง' ?></div>
    <?php endif; ?>
  </form>

  <div class="text-muted small mb-2">พบ <?= number_format(count($rows)) ?> รายการ<?= !$isSao && count($rows) >= 200 ? ' (แสดง 200 แรก — โปรดค้นหาให้แคบลง)' : '' ?></div>

  <div class="table-responsive">
  <table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr>
      <?php if ($isSao): ?>
        <th>หน่วยงาน</th><th>ชื่อผู้ใช้</th>
      <?php else: ?>
        <th>โรงเรียน</th><th>ชื่อผู้ใช้</th><th>สังกัด</th>
      <?php endif; ?>
      <th>รหัสผ่านปัจจุบัน</th>
      <th style="min-width:230px">รหัสผ่านใหม่</th>
      <th class="text-end">จัดการ</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="<?= $isSao ? 5 : 6 ?>" class="text-center text-muted py-4">
        <?= ($isAdmin && !$isSao && $q === '') ? 'พิมพ์คำค้นด้านบนเพื่อค้นหาบัญชีโรงเรียน'
            : (!$isAdmin ? 'ไม่พบบัญชีโรงเรียนในเขตของท่าน' : 'ไม่พบบัญชี') ?></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): $key = $isSao ? $r['id'] : $r['citicens_id']; ?>
      <tr>
        <?php if ($isSao): ?>
          <td><?= View::e($r['code_name'] ?: $r['code']) ?>
            <?php if (!empty($r['is_admin'])): ?><span class="badge bg-primary ms-1">admin</span><?php endif; ?>
            <div class="text-muted small"><?= View::e($r['id']) ?></div></td>
          <td><code><?= View::e($r['user']) ?></code></td>
        <?php else: ?>
          <td><?= View::e($r['name']) ?><div class="text-muted small"><?= View::e($r['sc_id']) ?></div></td>
          <td><code><?= View::e($r['user']) ?></code></td>
          <td class="small text-muted"><?= View::e($r['sao_name'] ?? '') ?></td>
        <?php endif; ?>
        <td class="text-nowrap">
          <?php $pw = (string) ($r['password'] ?? ''); ?>
          <code class="pw-cur" data-pw="<?= View::e($pw) ?>">••••••</code>
          <button type="button" class="btn btn-sm btn-ghost pw-eye" title="แสดง/ซ่อนรหัสผ่าน"><?= nh_icon('eye', 15) ?></button>
        </td>
        <td>
          <form method="post" action="<?= App::url('admin/users/password') ?>" class="d-flex gap-1">
            <?= Csrf::field() ?>
            <input type="hidden" name="tab" value="<?= View::e($tab) ?>">
            <input type="hidden" name="key" value="<?= View::e($key) ?>">
            <input type="hidden" name="q" value="<?= View::e($q) ?>">
            <input name="password" class="form-control form-control-sm" style="width:150px" required
                   maxlength="<?= $isSao ? 100 : 20 ?>" placeholder="รหัสผ่านใหม่">
            <button class="btn btn-sm btn-outline-primary" title="เปลี่ยนรหัสผ่าน"><?= nh_icon('key', 15) ?></button>
          </form>
        </td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-ghost" title="แก้ไข"
             href="<?= App::url('admin/users?tab=' . $tab . '&edit=' . urlencode($key) . '&q=' . urlencode($q)) ?>"><?= nh_icon('edit', 15) ?></a>
          <form method="post" action="<?= App::url('admin/users/delete') ?>" class="d-inline"
                onsubmit="return confirm('ยืนยันลบบัญชีนี้?');">
            <?= Csrf::field() ?>
            <input type="hidden" name="tab" value="<?= View::e($tab) ?>">
            <input type="hidden" name="key" value="<?= View::e($key) ?>">
            <input type="hidden" name="q" value="<?= View::e($q) ?>">
            <button class="btn btn-sm btn-ghost text-danger" title="ลบ"><?= nh_icon('trash', 15) ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div><!-- /.container -->

<script>
// แสดง/ซ่อนรหัสผ่านปัจจุบัน (plaintext ตามระบบเดิม) — เฉพาะหน้า admin
(function () {
  document.querySelectorAll('.pw-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var c = this.parentNode.querySelector('.pw-cur');
      if (!c) return;
      if (c.dataset.shown === '1') { c.textContent = '••••••'; c.dataset.shown = '0'; }
      else { c.textContent = c.dataset.pw !== '' ? c.dataset.pw : '(ว่าง)'; c.dataset.shown = '1'; }
    });
  });
})();
</script>
