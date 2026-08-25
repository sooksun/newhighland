<?php
/**
 * ดูรายละเอียดการรับรองการคงอยู่รายโรงเรียน (อ่านอย่างเดียว) — สำหรับเขต (สพท.) / สพฐ.
 * @var array $r; @var int $area; @var string $back
 */
use App\Models\SchoolConfirm;

$backTo = App::url('confirm' . ($back !== '' ? '?' . $back : ''));
$fmtTel = function (string $tel): string {
    $d = preg_replace('/\D/', '', $tel);
    return strlen($d) === 10 ? substr($d, 0, 3) . '-' . substr($d, 3, 3) . '-' . substr($d, 6, 4) : $tel;
};
$txt = fn($k, $d = '—') => ($r[$k] ?? '') !== '' ? View::e($r[$k]) : $d;
$intv = fn($k) => number_format((int) ($r[$k] ?? 0));

$openedInt = (int) ($r['opened'] ?? 1);                     // 1=คงอยู่, 0=ยุบ/รวม/เลิก, 2=ขาดคุณสมบัติ
$isOpen    = $openedInt === SchoolConfirm::OPENED_YES;       // แสดงข้อมูลนักเรียน/ครูเฉพาะ "คงอยู่"
$opColor   = [1 => 'info', 0 => 'danger', 2 => 'warning text-dark'][$openedInt] ?? 'info';
$confirmed= (int) ($r['school_confirmed'] ?? 0) === 1;
$locked   = SchoolConfirm::isLocked($r);
$saoSt    = (int) ($r['sao_status'] ?? 0);
$sptSt    = (int) ($r['spt_status'] ?? 0);
$stBadge  = [0 => ['warning text-dark', 'รอรับรอง'], 1 => ['success', 'รับรองแล้ว'], 2 => ['danger', 'ไม่รับรอง']];
[$saoC, $saoL] = $stBadge[$saoSt] ?? ['secondary', '-'];
[$sptC, $sptL] = $stBadge[$sptSt] ?? ['secondary', '-'];
?>
<div class="container">

  <div class="nh-row between wrap gap-2" style="margin-bottom:var(--s-3);">
    <a class="btn btn-sm btn-outline-secondary" href="<?= View::e($backTo) ?>"><?= nh_icon('chevLeft', 16) ?> ย้อนกลับ</a>
    <span class="badge badge-primary"><?= nh_icon('shieldCheck', 13) ?> รายละเอียดการคงอยู่ · <?= View::e(SchoolConfirm::areaLabel($area)) ?></span>
  </div>

  <!-- หัวเรื่อง + สถานะ -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="nh-row between wrap gap-3 align-items-start">
      <div>
        <h1 class="h4 fw-bold mb-1"><?= View::e($r['sc_name'] ?: $r['sc_id']) ?></h1>
        <div class="text-muted small">รหัส <?= View::e($r['sc_id']) ?> · จังหวัด<?= View::e($r['provinces']) ?> · ปีงบประมาณ <?= View::e(App::acadYear()) ?></div>
      </div>
      <div class="d-flex gap-1 flex-wrap">
        <?php if ($confirmed): ?>
          <span class="badge bg-<?= $opColor ?>"><?= View::e(SchoolConfirm::openedLabel($openedInt)) ?></span>
          <span class="badge bg-<?= $locked ? 'secondary' : 'warning text-dark' ?>"><?= $locked ? '🔒 ส่งแล้ว' : '📝 ร่าง' ?></span>
        <?php else: ?>
          <span class="badge bg-light text-muted">ยังไม่ยืนยัน</span>
        <?php endif; ?>
        <span class="badge bg-<?= $saoC ?>">สพท.: <?= View::e($saoL) ?></span>
        <span class="badge bg-<?= $sptC ?>">สพฐ.: <?= View::e($sptL) ?></span>
      </div>
    </div>
    <?php if ($confirmed): ?>
      <div class="text-muted small mt-2">
        <?php if ($locked): ?>ส่งข้อมูลเมื่อ <?= $txt('submitted_at') ?>
        <?php else: ?>บันทึกร่างเมื่อ <?= $txt('school_confirm_at') ?><?php endif; ?>
      </div>
    <?php endif; ?>
  </div></div>

  <?php if (!$confirmed): ?>
    <div class="alert alert-warning">โรงเรียนยังไม่ได้ยืนยันการคงอยู่ในปีงบประมาณนี้</div>
  <?php endif; ?>

  <!-- สถานะการคงอยู่ -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h6 fw-bold mb-3"><?= nh_icon('school', 16) ?> สถานะการคงอยู่</h2>
    <div class="row g-3 small">
      <div class="col-md-4"><div class="text-muted">สถานะปัจจุบัน</div>
        <div><?php
          echo $openedInt === SchoolConfirm::OPENED_DISQUALIFIED
                 ? 'ขาดคุณสมบัติ (ยังเปิดสอนแต่ไม่เข้าเกณฑ์พื้นที่พิเศษ)'
                 : ($openedInt === SchoolConfirm::OPENED_CLOSED
                     ? 'ยุบ / รวม / เลิกสถานศึกษา'
                     : 'ยังเปิดทำการเรียนการสอน (คงอยู่)');
        ?></div></div>
      <?php if ($openedInt === SchoolConfirm::OPENED_CLOSED): ?>
        <div class="col-md-4"><div class="text-muted">ประเภทการเลิก</div>
          <div><?= View::e(SchoolConfirm::closeTypeLabel((int) ($r['close_type'] ?? 0))) ?></div></div>
        <?php if ((int) ($r['close_type'] ?? 0) === SchoolConfirm::CLOSE_MERGE): ?>
          <div class="col-md-4"><div class="text-muted">ไปเรียนรวมกับ</div>
            <div><?= $txt('merged_to_name') ?> <span class="text-muted"><?= ($r['merged_to'] ?? '') !== '' ? '(' . View::e($r['merged_to']) . ')' : '' ?></span></div></div>
        <?php endif; ?>
      <?php elseif ($openedInt === SchoolConfirm::OPENED_DISQUALIFIED): ?>
        <div class="col-md-8"><div class="text-muted">เหตุผลที่ขาดคุณสมบัติ</div>
          <div><?= $txt('disqualify_reason') ?></div></div>
      <?php endif; ?>
      <div class="col-12"><div class="text-muted">หมายเหตุของโรงเรียน</div><div><?= $txt('school_note') ?></div></div>
    </div>
  </div></div>

  <?php if ($isOpen): ?>
  <!-- ข้อมูลโรงเรียน (เฉพาะกรณีคงอยู่) -->
  <div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h6 fw-bold mb-3"><?= nh_icon('user', 16) ?> ข้อมูลผู้บริหาร / ผู้กรอก</h2>
    <div class="row g-3 small">
      <div class="col-md-6"><div class="text-muted">ผู้อำนวยการโรงเรียน</div>
        <div><?= $txt('director_name') ?> <span class="text-muted"><?= ($r['director_phone'] ?? '') !== '' ? '(' . View::e($fmtTel($r['director_phone'])) . ')' : '' ?></span></div></div>
      <div class="col-md-6"><div class="text-muted">ผู้กรอกข้อมูล</div>
        <div><?= $txt('informant_name') ?> <span class="text-muted"><?= ($r['informant_phone'] ?? '') !== '' ? '(' . View::e($fmtTel($r['informant_phone'])) . ')' : '' ?></span></div></div>
    </div>

    <hr class="my-3">
    <div class="row g-3">
      <div class="col-lg-5">
        <h3 class="fw-bold small mb-2"><?= nh_icon('users', 15) ?> จำนวนนักเรียน (คน)</h3>
        <div class="row g-2 small text-center">
          <div class="col"><div class="text-muted">ชาย</div><div class="fw-bold"><?= $intv('std_male') ?></div></div>
          <div class="col"><div class="text-muted">หญิง</div><div class="fw-bold"><?= $intv('std_female') ?></div></div>
          <div class="col"><div class="text-muted">รวม</div><div class="fw-bold text-primary"><?= $intv('std_total') ?></div></div>
        </div>
      </div>
      <div class="col-lg-7">
        <h3 class="fw-bold small mb-2"><?= nh_icon('users', 15) ?> จำนวนครูและผู้บริหาร (คน)</h3>
        <div class="row g-2 small text-center">
          <div class="col"><div class="text-muted">ครู (ขรก.)</div><div class="fw-bold"><?= $intv('tch_govt') ?></div></div>
          <div class="col"><div class="text-muted">ครู (จ้าง)</div><div class="fw-bold"><?= $intv('tch_hire') ?></div></div>
          <div class="col"><div class="text-muted">รอง ผอ.</div><div class="fw-bold"><?= $intv('tch_deputy') ?></div></div>
          <div class="col"><div class="text-muted">ผอ.</div><div class="fw-bold"><?= $intv('tch_director') ?></div></div>
          <div class="col"><div class="text-muted">รวม</div><div class="fw-bold text-primary"><?= $intv('tch_total') ?></div></div>
        </div>
      </div>
    </div>
  </div></div>
  <?php endif; ?>

  <!-- ผลการรับรอง -->
  <div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-2"><?= nh_icon('shieldCheck', 16) ?> การรับรองระดับเขต (สพท.)</h2>
      <p class="mb-1">ผลการรับรอง: <span class="badge bg-<?= $saoC ?>"><?= View::e($saoL) ?></span></p>
      <p class="text-muted small mb-0"><?= ($r['sao_comment'] ?? '') !== '' ? View::e($r['sao_comment']) : '— ไม่มีหมายเหตุ —' ?></p>
    </div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
      <h2 class="h6 fw-bold mb-2"><?= nh_icon('shieldCheck', 16) ?> ความเห็น สพฐ.</h2>
      <p class="mb-1">ผลการพิจารณา: <span class="badge bg-<?= $sptC ?>"><?= View::e($sptL) ?></span></p>
      <p class="text-muted small mb-0"><?= ($r['spt_comment'] ?? '') !== '' ? View::e($r['spt_comment']) : '— ไม่มีหมายเหตุ —' ?></p>
    </div></div></div>
  </div>

  <div class="mb-4">
    <a class="btn btn-outline-secondary" href="<?= View::e($backTo) ?>"><?= nh_icon('chevLeft', 16) ?> ย้อนกลับไปยังรายการ</a>
  </div>

</div><!-- /.container -->
