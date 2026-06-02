<?php /** @var int $area; @var array $rows; @var array $mergeOpts */
use App\Models\SchoolConfirm; ?>

<div class="container">
<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-3);">
  <span class="badge badge-primary"><?= nh_icon('shieldCheck', 13) ?> รับรองการคงอยู่</span>
</div>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $area===1?'active':'' ?>" href="<?= App::url('confirm?area=1') ?>">พื้นที่สูง</a></li>
  <li class="nav-item"><a class="nav-link <?= $area===2?'active':'' ?>" href="<?= App::url('confirm?area=2') ?>">พื้นที่เกาะ</a></li>
</ul>

<h1 class="h5 fw-bold mb-1">ยืนยันการคงอยู่ของโรงเรียน</h1>
<p class="text-muted small">ปีงบประมาณ <?= View::e(App::acadYear()) ?> · <?= View::e(SchoolConfirm::areaLabel($area)) ?></p>

<?php if (!$rows): ?>
  <div class="alert alert-warning">โรงเรียนของท่านไม่อยู่ในรายชื่อ<?= View::e(SchoolConfirm::areaLabel($area)) ?> ปี 2566 (ไม่ต้องยืนยันในส่วนนี้)</div>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
  <div class="card border-0 shadow-sm mb-3" style="max-width:720px;">
    <div class="card-body">
      <h2 class="h6 fw-bold"><?= View::e($r['sc_name']) ?> <span class="text-muted">(<?= View::e($r['sc_id']) ?>)</span></h2>
      <div class="text-muted small mb-3">จังหวัด<?= View::e($r['provinces']) ?></div>

      <?php if ($r['school_confirmed']): ?>
        <div class="alert alert-success py-2 small mb-3">
          ✓ ยืนยันแล้วเมื่อ <?= View::e($r['school_confirm_at']) ?> —
          สถานะ: <b><?= $r['opened'] ? 'ยังเปิดทำการเรียนการสอน' : 'ยุบ/รวม/เลิก' ?></b>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= App::url('confirm/school') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= View::e($r['id']) ?>">
        <input type="hidden" name="area" value="<?= View::e($area) ?>">

        <div class="mb-3">
          <label class="form-label fw-bold d-block">สถานะปัจจุบันของโรงเรียน</label>
          <div class="form-check">
            <input class="form-check-input opened-radio" type="radio" name="opened" id="op1_<?= $r['id'] ?>" value="1" <?= ((int)($r['opened']??1)!==0)?'checked':'' ?>>
            <label class="form-check-label" for="op1_<?= $r['id'] ?>">ยังเปิดทำการเรียนการสอน (คงอยู่ ไม่ยุบ/รวม/เลิก)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input opened-radio" type="radio" name="opened" id="op0_<?= $r['id'] ?>" value="0" <?= ((int)($r['opened']??1)===0)?'checked':'' ?>>
            <label class="form-check-label" for="op0_<?= $r['id'] ?>">ยุบ / รวม / เลิกสถานศึกษาไปแล้ว</label>
          </div>
        </div>

        <div class="mb-3 merged-box" style="<?= ((int)($r['opened']??1)===0)?'':'display:none' ?>">
          <label class="form-label">รหัสโรงเรียนที่ไปยุบรวมด้วย (ถ้ามี)</label>
          <input class="form-control" name="merged_to" value="<?= View::e($r['merged_to']) ?>" placeholder="เช่น 1063160141">
        </div>

        <div class="mb-3">
          <label class="form-label">หมายเหตุ</label>
          <input class="form-control" name="school_note" value="<?= View::e($r['school_note']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">บันทึกการยืนยัน</button>
      </form>

      <hr>
      <div class="small">
        <div>ผลรับรองระดับเขต (สพท.): <b><?= View::e(SchoolConfirm::statusLabel((int)$r['sao_status'])) ?></b>
          <?= $r['sao_comment'] ? '— '.View::e($r['sao_comment']) : '' ?></div>
        <div>ความเห็น สพฐ.: <b><?= View::e(SchoolConfirm::statusLabel((int)$r['spt_status'])) ?></b></div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.opened-radio').forEach(function (el) {
  el.addEventListener('change', function () {
    var box = this.closest('form').querySelector('.merged-box');
    box.style.display = (this.value === '0') ? '' : 'none';
  });
});
</script>
</div><!-- /.container -->
