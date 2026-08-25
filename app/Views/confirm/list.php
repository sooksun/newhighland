<?php /** @var int $area; @var array $rows; @var array $stats; @var array $filters; @var array $mergeOpts */
use App\Models\SchoolConfirm;
$isAdmin = Auth::isAdmin();
// query string ของตัวกรองปัจจุบัน — ส่งให้ปุ่ม "ดูรายละเอียด" เพื่อให้ปุ่มย้อนกลับคงตัวกรอง/แท็บเดิม
$backQs = http_build_query(array_filter([
    'area'       => $area,
    'q'          => $filters['q'] ?? '',
    'province'   => $filters['province'] ?? '',
    'confirmed'  => $filters['confirmed'] ?? '',
    'sao_status' => $filters['sao_status'] ?? '',
], fn($x) => $x !== '' && $x !== null));
?>
<div class="container">
<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-3);">
  <span class="badge badge-primary"><?= nh_icon('shieldCheck', 13) ?> รับรองการคงอยู่</span>
  <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปีงบประมาณ <?= View::e(App::acadYear()) ?></span>
</div>
<?php $listAreas = \App\Services\SchoolMenu::current()['areas'] ?: [1, 2]; ?>
<?php if (count($listAreas) > 1): ?>
<ul class="nav nav-pills mb-3">
  <?php foreach ([1=>'พื้นที่สูง', 2=>'พื้นที่เกาะ'] as $av => $al): if (in_array($av, $listAreas, true)): ?>
    <li class="nav-item"><a class="nav-link <?= $area===$av?'active':'' ?>" href="<?= App::url('confirm?area='.$av) ?>"><?= $al ?></a></li>
  <?php endif; endforeach; ?>
</ul>
<?php endif; ?>

<h1 class="h5 fw-bold mb-1">รับรองการคงอยู่ — <?= View::e(SchoolConfirm::areaLabel($area)) ?></h1>
<p class="text-muted small">ปีงบประมาณ <?= View::e(App::acadYear()) ?><?= $isAdmin ? ' · ทุกเขต' : ' · '.View::e(Auth::saoName()) ?></p>

<div class="row g-2 mb-3">
  <?php foreach ([['ทั้งหมด',$stats['total']??0,'secondary'],['โรงเรียนยืนยันแล้ว',$stats['confirmed']??0,'info'],['เขตรับรองแล้ว',$stats['approved']??0,'success'],['ยุบ/รวม/เลิก',$stats['closed']??0,'danger'],['ขาดคุณสมบัติ',$stats['disqualified']??0,'warning']] as $c): ?>
    <div class="col-6 col-md"><div class="card border-0 shadow-sm"><div class="card-body py-2">
      <div class="text-muted small"><?= View::e($c[0]) ?></div><div class="h5 fw-bold mb-0 text-<?= $c[2] ?>"><?= number_format((int)$c[1]) ?></div>
    </div></div></div>
  <?php endforeach; ?>
</div>

<form class="row g-2 mb-3" method="get" action="<?= App::url('confirm') ?>">
  <input type="hidden" name="area" value="<?= View::e($area) ?>">
  <div class="col-md-3"><input class="form-control form-control-sm" name="q" value="<?= View::e($filters['q']) ?>" placeholder="ชื่อ/รหัสโรงเรียน"></div>
  <div class="col-md-2"><input class="form-control form-control-sm" name="province" value="<?= View::e($filters['province']) ?>" placeholder="จังหวัด"></div>
  <div class="col-md-2"><select class="form-select form-select-sm" name="confirmed">
    <option value="">โรงเรียนยืนยัน: ทั้งหมด</option>
    <option value="1" <?= $filters['confirmed']==='1'?'selected':'' ?>>ยืนยันแล้ว</option>
    <option value="0" <?= $filters['confirmed']==='0'?'selected':'' ?>>ยังไม่ยืนยัน</option>
  </select></div>
  <div class="col-md-2"><select class="form-select form-select-sm" name="sao_status">
    <option value="">เขตรับรอง: ทั้งหมด</option>
    <option value="0" <?= $filters['sao_status']==='0'?'selected':'' ?>>รอรับรอง</option>
    <option value="1" <?= $filters['sao_status']==='1'?'selected':'' ?>>รับรองแล้ว</option>
    <option value="2" <?= $filters['sao_status']==='2'?'selected':'' ?>>ไม่รับรอง</option>
  </select></div>
  <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">กรอง</button></div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <span class="text-muted small">พบ <?= number_format(count($rows)) ?> รายการ</span>
  <a class="btn btn-sm btn-outline-primary" href="<?= App::url('confirm/word?area=' . (int) $area) ?>" title="ดาวน์โหลดบัญชีแนบท้าย รายชื่อโรงเรียนเดิมที่ยืนยันการคงอยู่แล้ว แบ่งตามสำนักงานเขตพื้นที่ (Word)"><?= nh_icon('fileText', 15) ?> ดาวน์โหลดรายชื่อที่ยืนยัน (Word)</a>
</div>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
  <thead class="table-light"><tr>
    <th>โรงเรียน</th><th>จังหวัด</th><th>โรงเรียนยืนยัน</th><th>รับรอง (สพท.)</th><?= $isAdmin?'<th>สพฐ.</th>':'' ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr id="row<?= $r['id'] ?>">
      <td><?= View::e($r['sc_name']) ?><div class="text-muted small"><?= View::e($r['sc_id']) ?></div>
        <a class="btn btn-sm btn-outline-primary py-0 px-2 mt-1" href="<?= App::url('confirm/view?id=' . $r['id'] . ($backQs !== '' ? '&back=' . rawurlencode($backQs) : '')) ?>" title="ดูรายละเอียดโรงเรียน"><?= nh_icon('eye', 14) ?> ดูรายละเอียด</a>
      </td>
      <td><?= View::e($r['provinces']) ?></td>
      <td>
        <?php if ($r['school_confirmed']): ?>
          <?php $op = (int)($r['opened'] ?? 1); $opColor = [1=>'info', 0=>'danger', 2=>'warning text-dark'][$op] ?? 'info'; ?>
          <span class="badge bg-<?= $opColor ?>"><?= View::e(SchoolConfirm::openedLabel($op)) ?></span>
          <?php if (SchoolConfirm::isLocked($r)): ?>
            <span class="badge bg-secondary">🔒 ส่งแล้ว</span>
            <form method="post" action="<?= App::url('confirm/unlock') ?>" class="d-inline"
                  onsubmit="return confirm('ปลดล็อกให้โรงเรียนกลับมาแก้ไขข้อมูลใหม่?');">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="area" value="<?= $area ?>">
              <button class="btn btn-sm btn-outline-warning py-0 px-1"><?= nh_icon('edit', 13) ?> ปลดล็อก</button>
            </form>
          <?php else: ?>
            <span class="badge bg-warning text-dark">ร่าง</span>
          <?php endif; ?>
        <?php else: ?><span class="badge bg-light text-muted">ยังไม่ยืนยัน</span><?php endif; ?>
        <?php if ((int)$r['opened'] === 1 && ((int)($r['std_total'] ?? 0) === 0 || (int)($r['tch_total'] ?? 0) === 0)): ?>
          <div class="text-danger small mt-1"><?= nh_icon('alertCircle', 12) ?> ข้อมูลนักเรียน/ครูไม่ครบ — รับรองไม่ได้</div>
        <?php endif; ?>
      </td>
      <td>
        <form method="post" action="<?= App::url('confirm/sao') ?>" class="d-flex gap-1 align-items-center js-confirm-save">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="area" value="<?= $area ?>">
          <select name="sao_status" class="form-select form-select-sm" style="width:auto">
            <?php foreach ([0=>'รอ',1=>'รับรอง',2=>'ไม่รับรอง'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= (int)$r['sao_status']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
          <input name="sao_comment" class="form-control form-control-sm" style="width:130px" value="<?= View::e($r['sao_comment']) ?>" placeholder="หมายเหตุ">
          <button class="btn btn-sm btn-outline-primary">บันทึก</button>
        </form>
      </td>
      <?php if ($isAdmin): ?>
      <td>
        <form method="post" action="<?= App::url('confirm/spt') ?>" class="d-flex gap-1 align-items-center js-confirm-save">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="area" value="<?= $area ?>">
          <select name="spt_status" class="form-select form-select-sm" style="width:auto">
            <?php foreach ([0=>'รอ',1=>'เป็นโรงเรียนพื้นที่ลักษณะพิเศษ',2=>'ไม่เป็นโรงเรียนพื้นที่ลักษณะพิเศษ'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= (int)$r['spt_status']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
          <input name="spt_comment" class="form-control form-control-sm" style="width:130px" value="<?= View::e($r['spt_comment'] ?? '') ?>" placeholder="หมายเหตุ">
          <button class="btn btn-sm btn-outline-secondary">บันทึก</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div><!-- /.container -->

<!-- Modal แจ้งผลการบันทึก (สพท./สพฐ.) — เด้งขึ้นโดยไม่โหลดหน้าใหม่ -->
<div class="modal fade" id="cfSaveModal" tabindex="-1" aria-labelledby="cfSaveMsg" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center py-4">
        <div id="cfSaveIconOk" class="text-success mb-2"><?= nh_icon('checkCircle', 44) ?></div>
        <div id="cfSaveIconErr" class="text-danger mb-2" style="display:none;"><?= nh_icon('alertCircle', 44) ?></div>
        <div id="cfSaveMsg" class="fw-semibold">บันทึกแล้ว</div>
      </div>
      <div class="modal-footer justify-content-center py-2 border-0">
        <button type="button" class="btn btn-sm btn-primary px-4" data-bs-dismiss="modal">ตกลง</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof bootstrap === 'undefined') return;
  var modalEl = document.getElementById('cfSaveModal');
  var modal   = new bootstrap.Modal(modalEl);
  var msgEl   = document.getElementById('cfSaveMsg');
  var iconOk  = document.getElementById('cfSaveIconOk');
  var iconErr = document.getElementById('cfSaveIconErr');

  function showResult(ok, message) {
    msgEl.textContent = message || (ok ? 'บันทึกแล้ว' : 'บันทึกไม่สำเร็จ');
    if (iconOk)  iconOk.style.display  = ok ? '' : 'none';
    if (iconErr) iconErr.style.display = ok ? 'none' : '';
    modal.show();
  }

  document.querySelectorAll('form.js-confirm-save').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();   // อยู่หน้าเดิม ไม่โหลดใหม่
      var btn = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
      var orig = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = 'กำลังบันทึก…'; }

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
      .then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, j: j }; })
                       .catch(function () { return { ok: r.ok, j: {} }; });
      })
      .then(function (res) {
        var ok = res.ok && res.j.ok !== false;
        // สำเร็จ: แสดง "บันทึกแล้ว" ตามที่กำหนด; ไม่สำเร็จ: แสดงเหตุผลจากเซิร์ฟเวอร์ (เช่น ข้อมูลไม่ครบ)
        showResult(ok, ok ? 'บันทึกแล้ว' : (res.j.message || 'บันทึกไม่สำเร็จ'));
      })
      .catch(function () { showResult(false, 'เกิดข้อผิดพลาด กรุณาลองใหม่'); })
      .finally(function () { if (btn) { btn.disabled = false; btn.innerHTML = orig; } });
    });
  });
});
</script>
