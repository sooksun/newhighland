<?php /** @var int $area; @var array $rows; @var array $mergeOpts */
use App\Models\SchoolConfirm;
$fmtTel = function (string $tel): string {
    $d = preg_replace('/\D/', '', $tel);
    return strlen($d) === 10 ? substr($d,0,3).'-'.substr($d,3,3).'-'.substr($d,6,4) : $tel;
}; ?>

<div class="container-fluid px-3 px-lg-4">

<style>
.confirm-legend{position:fixed;top:calc(var(--nav-h) + 16px);right:16px;width:320px;max-width:calc(100vw - 32px);z-index:35;max-height:calc(100vh - var(--nav-h) - 32px);overflow:auto;}
.confirm-legend-toggle{position:fixed;top:calc(var(--nav-h) + 16px);right:16px;z-index:35;display:none;align-items:center;gap:6px;}
.confirm-legend .lg-term{font-weight:700;}
@media(max-width:991.98px){.confirm-legend,.confirm-legend-toggle{display:none !important;}}
</style>

<!-- Floating panel: คำอธิบายประเภทการเลิกสถานศึกษา -->
<aside class="confirm-legend card border-0 shadow" id="confirmLegend" aria-label="คำอธิบายประเภทการเลิกสถานศึกษา">
  <div class="card-header bg-body d-flex align-items-center justify-content-between py-2 px-3">
    <span class="fw-bold small d-inline-flex align-items-center gap-1"><?= nh_icon('info', 15) ?> คำอธิบายประเภท</span>
    <button type="button" class="btn-close" id="legendClose" aria-label="ปิด"></button>
  </div>
  <div class="card-body p-3 small">
    <div class="mb-3">
      <div class="lg-term text-danger mb-1">โรงเรียนยุบ</div>
      <div class="text-secondary">การยุบเลิกโรงเรียน (ตาม ม.๓๖ พ.ร.บ.ระเบียบบริหารราชการกระทรวงศึกษาธิการ) โดยทั่วไปคือ<b>ยุบไปรวม</b>กับโรงเรียนอื่น โรงเรียนเดิม<b>สิ้นสภาพ</b>การเป็นสถานศึกษา</div>
    </div>
    <div class="mb-3">
      <div class="lg-term mb-1">โรงเรียนเลิก</div>
      <div class="text-secondary">การ<b>เลิกสถานศึกษา</b> เมื่อ (๑) ไม่มีนักเรียนที่จะจัดการเรียนการสอน หรือ (๒) จำนวนนักเรียนลดลงจนพัฒนาคุณภาพการศึกษาไม่ได้ (ระเบียบฯ ๒๕๕๐ ข้อ ๑๑)</div>
    </div>
    <div>
      <div class="lg-term text-primary mb-1">โรงเรียนเรียนรวม</div>
      <div class="text-secondary">การนำนักเรียนของโรงเรียนที่อยู่ใกล้กันตั้งแต่ <b>๒ แห่งขึ้นไป</b> มาเรียนรวมกัน (เป็นชั้น/ช่วงชั้น) เพื่อให้บริหารจัดการศึกษามีประสิทธิภาพ — โรงเรียนเดิม<b>ยังคงสภาพอยู่</b> (ระเบียบฯ ๒๕๕๐ ข้อ ๒)</div>
    </div>
    <div class="text-muted mt-2 pt-2 border-top" style="font-size:.72rem;">
      อ้างอิง: ระเบียบกระทรวงศึกษาธิการ ว่าด้วยการจัดตั้ง รวม หรือเลิกสถานศึกษาขั้นพื้นฐาน พ.ศ. ๒๕๕๐
    </div>
  </div>
</aside>
<button type="button" class="btn btn-primary btn-sm confirm-legend-toggle shadow" id="legendOpen">
  <?= nh_icon('info', 16) ?> คำอธิบายประเภท
</button>

<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-3);">
  <span class="badge badge-primary"><?= nh_icon('shieldCheck', 13) ?> รับรองการคงอยู่</span>
</div>
<?php
// auto menu filter: แสดงเฉพาะแท็บพื้นที่ที่ตรงคุณสมบัติของโรงเรียน
$tabAreas = \App\Services\SchoolMenu::current()['areas'] ?: [1, 2];
?>
<?php if (count($tabAreas) > 1): ?>
<ul class="nav nav-pills mb-3">
  <?php foreach ([1=>'พื้นที่สูง', 2=>'พื้นที่เกาะ'] as $av => $al): if (in_array($av, $tabAreas, true)): ?>
    <li class="nav-item"><a class="nav-link <?= $area===$av?'active':'' ?>" href="<?= App::url('confirm?area='.$av) ?>"><?= $al ?></a></li>
  <?php endif; endforeach; ?>
</ul>
<?php endif; ?>

<h1 class="h5 fw-bold mb-1">ยืนยันการคงอยู่ของโรงเรียน</h1>
<p class="text-muted small">ปีงบประมาณ <?= View::e(App::acadYear()) ?> · <?= View::e(SchoolConfirm::areaLabel($area)) ?></p>

<?php if (!$rows): ?>
  <div class="alert alert-warning">โรงเรียนของท่านไม่อยู่ในรายชื่อ<?= View::e(SchoolConfirm::areaLabel($area)) ?> ปี 2566 (ไม่ต้องยืนยันในส่วนนี้)</div>
<?php endif; ?>

<?php foreach ($rows as $r): $locked = SchoolConfirm::isLocked($r); ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <h2 class="h6 fw-bold"><?= View::e($r['sc_name']) ?> <span class="text-muted">(<?= View::e($r['sc_id']) ?>)</span></h2>
      <div class="text-muted small mb-3">จังหวัด<?= View::e($r['provinces']) ?></div>

      <?php if ($locked): ?>
        <div class="alert alert-secondary py-2 small mb-3">
          🔒 <b>ส่งข้อมูลแล้ว</b> เมื่อ <?= View::e($r['submitted_at']) ?> — ล็อกการแก้ไข
          (ต้องการแก้ไขใหม่ ติดต่อสำนักงานเขตพื้นที่เพื่อปลดล็อก)
        </div>
      <?php elseif ($r['school_confirmed']): ?>
        <div class="alert alert-info py-2 small mb-3">
          📝 บันทึกข้อมูล (ร่าง) แล้วเมื่อ <?= View::e($r['school_confirm_at']) ?> — <b>ยังแก้ไขได้</b>
          เมื่อกรอกครบถ้วนให้กด “ส่งข้อมูล”
        </div>
      <?php endif; ?>

      <form method="post" action="<?= App::url('confirm/school') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= View::e($r['id']) ?>">
        <input type="hidden" name="area" value="<?= View::e($area) ?>">

        <fieldset <?= $locked ? 'disabled' : '' ?>>
        <?php $op = (int) ($r['opened'] ?? 1); ?>
        <div class="mb-3">
          <label class="form-label fw-bold d-block">สถานะปัจจุบันของโรงเรียน</label>
          <div class="form-check">
            <input class="form-check-input opened-radio" type="radio" name="opened" id="op1_<?= $r['id'] ?>" value="1" <?= $op===1?'checked':'' ?>>
            <label class="form-check-label" for="op1_<?= $r['id'] ?>">ยังเปิดทำการเรียนการสอน (คงอยู่ ไม่ยุบ/รวม/เลิก)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input opened-radio" type="radio" name="opened" id="op0_<?= $r['id'] ?>" value="0" <?= $op===0?'checked':'' ?>>
            <label class="form-check-label" for="op0_<?= $r['id'] ?>">ยุบ / รวม / เลิกสถานศึกษาไปแล้ว</label>
          </div>
          <div class="form-check">
            <input class="form-check-input opened-radio" type="radio" name="opened" id="op2_<?= $r['id'] ?>" value="2" <?= $op===2?'checked':'' ?>>
            <label class="form-check-label" for="op2_<?= $r['id'] ?>">ขาดคุณสมบัติ (ยังเปิดสอนแต่ไม่เข้าเกณฑ์พื้นที่พิเศษ เช่น มีสะพานเชื่อมแผ่นดินใหญ่)</label>
          </div>
        </div>

        <!-- กรณี ขาดคุณสมบัติ: ระบุเหตุผล -->
        <div class="mb-3 disqualify-box" style="<?= $op===2?'':'display:none' ?>">
          <label class="form-label fw-bold d-block mb-1">เหตุผลที่ขาดคุณสมบัติ</label>
          <input class="form-control" name="disqualify_reason" maxlength="255" value="<?= View::e($r['disqualify_reason'] ?? '') ?>" placeholder="เช่น มีสะพานเชื่อมกับแผ่นดินใหญ่ / ถนนเข้าถึงสะดวกแล้ว">
        </div>

        <!-- กรณี ยุบ / รวม / เลิก -->
        <div class="mb-3 closed-box" style="<?= $op===0?'':'display:none' ?>">
          <label class="form-label fw-bold d-block">ระบุประเภท</label>
          <?php $ct = (int)($r['close_type'] ?? 0); foreach (SchoolConfirm::CLOSE_TYPES as $cv => $cl): ?>
          <div class="form-check">
            <input class="form-check-input close-type-radio" type="radio" name="close_type" id="ct<?= $cv ?>_<?= $r['id'] ?>" value="<?= $cv ?>" <?= $ct===$cv?'checked':'' ?>>
            <label class="form-check-label" for="ct<?= $cv ?>_<?= $r['id'] ?>"><?= View::e($cl) ?></label>
          </div>
          <?php endforeach; ?>

          <div class="merge-name-box mt-2 ps-4" style="<?= $ct===SchoolConfirm::CLOSE_MERGE?'':'display:none' ?>">
            <label class="form-label mb-1">รหัสโรงเรียนที่ไปยุบรวมด้วย (ถ้ามี)</label>
            <input class="form-control mb-2" name="merged_to" value="<?= View::e($r['merged_to']) ?>" placeholder="เช่น 1063160141">
            <label class="form-label mb-1">ชื่อโรงเรียนที่ไปยุบรวมด้วย (ถ้ามี)</label>
            <input class="form-control" name="merged_to_name" value="<?= View::e($r['merged_to_name'] ?? '') ?>" placeholder="เช่น โรงเรียนบ้านหนองบัว">
          </div>
        </div>

        <!-- กรณี ยังเปิดทำการเรียนการสอน: กรอกข้อมูลครู/นักเรียน (เฉพาะสถานะ "คงอยู่") -->
        <div class="open-box" style="<?= $op===1?'':'display:none' ?>">
        <hr class="my-3">
        <h3 class="h6 fw-bold mb-2">ข้อมูลโรงเรียน (สำหรับปีงบประมาณ <?= View::e(App::acadYear()) ?>)</h3>

        <div class="row g-2 mb-2">
          <div class="col-md-8">
            <label class="form-label mb-1">ชื่อ-สกุล ผู้อำนวยการโรงเรียน</label>
            <input class="form-control" name="director_name" value="<?= View::e($r['director_name'] ?? '') ?>" placeholder="เช่น นายสมชาย ใจดี">
          </div>
          <div class="col-md-4">
            <label class="form-label mb-1">เบอร์โทร ผอ.</label>
            <input class="form-control tel-fmt" name="director_phone" inputmode="numeric" maxlength="12" placeholder="081-277-1948" pattern="\d{3}-\d{3}-\d{4}" value="<?= View::e($fmtTel($r['director_phone'] ?? '')) ?>">
            <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-8">
            <label class="form-label mb-1">ชื่อ-สกุล ผู้กรอกข้อมูล</label>
            <input class="form-control" name="informant_name" value="<?= View::e($r['informant_name'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label mb-1">เบอร์โทร ผู้กรอก</label>
            <input class="form-control tel-fmt" name="informant_phone" inputmode="numeric" maxlength="12" placeholder="081-277-1948" pattern="\d{3}-\d{3}-\d{4}" value="<?= View::e($fmtTel($r['informant_phone'] ?? '')) ?>">
            <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
          </div>
        </div>

        <label class="form-label fw-bold mb-1">จำนวนนักเรียน (คน)</label>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="form-label mb-1 small text-muted">ชาย</label>
            <input class="form-control std-in" type="number" min="0" name="std_male" value="<?= View::e((int)($r['std_male'] ?? 0)) ?>">
          </div>
          <div class="col-4">
            <label class="form-label mb-1 small text-muted">หญิง</label>
            <input class="form-control std-in" type="number" min="0" name="std_female" value="<?= View::e((int)($r['std_female'] ?? 0)) ?>">
          </div>
          <div class="col-4">
            <label class="form-label mb-1 small text-muted">รวม</label>
            <input class="form-control std-total bg-light" type="number" tabindex="-1" readonly value="<?= View::e((int)($r['std_total'] ?? 0)) ?>">
          </div>
        </div>

        <label class="form-label fw-bold mb-1">จำนวนครูและผู้บริหาร (คน)</label>
        <div class="row g-2 mb-3">
          <div class="col-6 col-md">
            <label class="form-label mb-1 small text-muted">ครู (ข้าราชการ)</label>
            <input class="form-control tch-in" type="number" min="0" name="tch_govt" value="<?= View::e((int)($r['tch_govt'] ?? 0)) ?>">
          </div>
          <div class="col-6 col-md">
            <label class="form-label mb-1 small text-muted">ครู (อัตราจ้าง)</label>
            <input class="form-control tch-in" type="number" min="0" name="tch_hire" value="<?= View::e((int)($r['tch_hire'] ?? 0)) ?>">
          </div>
          <div class="col-6 col-md">
            <label class="form-label mb-1 small text-muted">รองผู้อำนวยการ</label>
            <input class="form-control tch-in" type="number" min="0" name="tch_deputy" value="<?= View::e((int)($r['tch_deputy'] ?? 0)) ?>">
          </div>
          <div class="col-6 col-md">
            <label class="form-label mb-1 small text-muted">ผู้อำนวยการ</label>
            <input class="form-control tch-in" type="number" min="0" name="tch_director" value="<?= View::e((int)($r['tch_director'] ?? 0)) ?>">
          </div>
          <div class="col-6 col-md">
            <label class="form-label mb-1 small text-muted">รวม</label>
            <input class="form-control tch-total bg-light" type="number" tabindex="-1" readonly value="<?= View::e((int)($r['tch_total'] ?? 0)) ?>">
          </div>
        </div>
        </div><!-- /.open-box -->

        <div class="mb-3">
          <label class="form-label">หมายเหตุ</label>
          <input class="form-control" name="school_note" value="<?= View::e($r['school_note']) ?>">
        </div>
        </fieldset>

        <?php if ($locked): ?>
          <div class="text-muted small"><?= nh_icon('info', 14) ?> ข้อมูลถูกล็อก — หากต้องการแก้ไข กรุณาให้สำนักงานเขตพื้นที่ปลดล็อก</div>
        <?php else: ?>
          <div class="nh-row gap-2 wrap">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary"><?= nh_icon('checkCircle', 15) ?> บันทึกข้อมูล (ร่าง)</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary js-submit-confirm"
                    onclick="return confirm('ยืนยันการส่งข้อมูล? เมื่อส่งแล้วจะแก้ไขไม่ได้จนกว่าเขตจะปลดล็อก');"><?= nh_icon('shieldCheck', 15) ?> ส่งข้อมูล</button>
          </div>
          <div class="text-danger small mt-1 js-submit-hint" style="display:none"><?= nh_icon('alertCircle', 14) ?> ต้องกรอก “จำนวนนักเรียนรวม” และ “จำนวนครูและผู้บริหารรวม” ให้มากกว่า 0 ก่อนจึงจะส่งได้ (บันทึกร่างได้)</div>
          <div class="text-muted small mt-1">บันทึกร่างไว้ก่อน แล้วกลับมาแก้ไข/อัปโหลดเพิ่มได้ — กด “ส่งข้อมูล” เมื่อครบถ้วน</div>
        <?php endif; ?>
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
// Floating panel คำอธิบายประเภท — ปิด/เปิดได้ (จำสถานะใน localStorage)
(function () {
  var legend = document.getElementById('confirmLegend');
  var openBtn = document.getElementById('legendOpen');
  var closeBtn = document.getElementById('legendClose');
  if (!legend || !openBtn) return;
  function set(open) {
    legend.style.display = open ? '' : 'none';
    openBtn.style.display = open ? 'none' : 'inline-flex';
    try { localStorage.setItem('nh-confirm-legend', open ? '1' : '0'); } catch (e) {}
  }
  if (closeBtn) closeBtn.addEventListener('click', function () { set(false); });
  openBtn.addEventListener('click', function () { set(true); });
  try { if (localStorage.getItem('nh-confirm-legend') === '0') set(false); } catch (e) {}
})();

// สลับฟอร์มตามสถานะ: '1' คงอยู่ → กรอกครู/นักเรียน | '0' ยุบ/รวม/เลิก → เลือกประเภท | '2' ขาดคุณสมบัติ → ระบุเหตุผล
document.querySelectorAll('.opened-radio').forEach(function (el) {
  el.addEventListener('change', function () {
    var form = this.closest('form');
    var v = this.value;
    var openBox = form.querySelector('.open-box');
    var closedBox = form.querySelector('.closed-box');
    var dqBox = form.querySelector('.disqualify-box');
    if (openBox)   openBox.style.display   = (v === '1') ? '' : 'none';
    if (closedBox) closedBox.style.display = (v === '0') ? '' : 'none';
    if (dqBox)     dqBox.style.display     = (v === '2') ? '' : 'none';
  });
});

// แสดงช่อง "ระบุชื่อโรงเรียน" เฉพาะเมื่อเลือก "ไปเรียนรวมกับโรงเรียนอื่น"
document.querySelectorAll('.close-type-radio').forEach(function (el) {
  el.addEventListener('change', function () {
    var box = this.closest('.closed-box').querySelector('.merge-name-box');
    if (box) box.style.display = (this.value === '3') ? '' : 'none';
  });
});

// Auto-format เบอร์โทร "081-277-1948" ขณะพิมพ์
document.querySelectorAll('input.tel-fmt').forEach(function (inp) {
  inp.addEventListener('input', function () {
    var d = this.value.replace(/\D/g, '').slice(0, 10);
    if (d.length <= 3)      this.value = d;
    else if (d.length <= 6) this.value = d.slice(0,3) + '-' + d.slice(3);
    else                    this.value = d.slice(0,3) + '-' + d.slice(3,6) + '-' + d.slice(6);
    this.classList.toggle('is-invalid', this.value !== '' && !/^\d{3}-\d{3}-\d{4}$/.test(this.value));
  });
});

// รวมจำนวนนักเรียน/ครู อัตโนมัติ + คุมปุ่ม “ส่งข้อมูล” ตามความครบถ้วน (แยกตามแต่ละฟอร์ม)
document.querySelectorAll('form').forEach(function (form) {
  function sumInto(inSel, totalSel) {
    var total = form.querySelector(totalSel);
    if (!total) return;
    var t = 0;
    form.querySelectorAll(inSel).forEach(function (i) { t += parseInt(i.value, 10) || 0; });
    total.value = t;
  }
  var submitBtn = form.querySelector('.js-submit-confirm');
  var hint = form.querySelector('.js-submit-hint');
  function isOpened() {
    var r = form.querySelector('.opened-radio:checked');
    return !r || r.value === '1';   // ค่าเริ่มต้น = คงอยู่
  }
  function num(sel) { var e = form.querySelector(sel); return e ? (parseInt(e.value, 10) || 0) : 0; }
  function checkComplete() {
    if (!submitBtn) return;
    // โรงเรียนคงอยู่: ต้องมีนักเรียนรวม > 0 และครูรวม > 0 ; ยุบ/รวม/เลิก: ส่งได้เลย
    var ok = !isOpened() || (num('.std-total') > 0 && num('.tch-total') > 0);
    submitBtn.disabled = !ok;
    if (hint) hint.style.display = ok ? 'none' : '';
  }
  function recalc() {
    sumInto('.std-in', '.std-total');
    sumInto('.tch-in', '.tch-total');
    checkComplete();
  }
  form.querySelectorAll('.std-in, .tch-in').forEach(function (i) {
    i.addEventListener('input', recalc);
  });
  form.querySelectorAll('.opened-radio').forEach(function (r) {
    r.addEventListener('change', checkComplete);
  });
  checkComplete();   // ตรวจตอนโหลด
});
</script>
</div><!-- /.container -->
