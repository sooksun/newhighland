<?php /** @var array $status */
$isOpen = (bool) (int) $status['is_open'];
?>
<div class="container" style="max-width:720px;">

  <span class="badge badge-primary" style="margin-bottom:var(--s-3);"><?= nh_icon('shieldCheck', 13) ?> สพฐ. (ส่วนกลาง)</span>
  <h1 class="h5 fw-bold mb-1">ปิด-เปิดระบบการคัดกรอง</h1>
  <p class="text-muted small">คุมทั้งระบบด้วยสวิตช์เดียว — เมื่อ "ปิด" โรงเรียนจะดูข้อมูลเดิมได้ตามปกติ แต่กรอก/บันทึก/ส่งข้อมูลไม่ได้
    (ประเมินพื้นที่สูง, ประเมินพื้นที่เกาะ, รับรองการคงอยู่) ส่วนสำนักงานเขต/สพฐ. ยังรับรองข้อมูลได้ตามปกติ</p>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="nh-row gap-2 align-items-center mb-2">
        <span class="badge <?= $isOpen ? 'bg-success' : 'bg-danger' ?>">
          <?= nh_icon($isOpen ? 'checkCircle' : 'alertCircle', 13) ?> <?= $isOpen ? 'เปิดอยู่' : 'ปิดอยู่' ?>
        </span>
        <?php if (!empty($status['updated_at'])): ?>
          <span class="text-muted small">แก้ไขล่าสุด <?= View::e($status['updated_at']) ?>
            <?= !empty($status['updated_by']) ? 'โดย ' . View::e($status['updated_by']) : '' ?></span>
        <?php endif; ?>
      </div>

      <form method="post" action="<?= App::url('admin/system/save') ?>">
        <?= Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label small fw-bold d-block mb-1">สถานะระบบ</label>
          <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="is_open" id="statusOpen" value="1" <?= $isOpen ? 'checked' : '' ?>>
            <label class="btn btn-outline-success" for="statusOpen"><?= nh_icon('checkCircle', 14) ?> เปิดระบบ</label>

            <input type="radio" class="btn-check" name="is_open" id="statusClosed" value="0" <?= !$isOpen ? 'checked' : '' ?>>
            <label class="btn btn-outline-danger" for="statusClosed"><?= nh_icon('alertCircle', 14) ?> ปิดระบบ</label>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold mb-1" for="closedMessage">ข้อความแจ้งโรงเรียนตอนปิดระบบ (ไม่บังคับ)</label>
          <textarea class="form-control form-control-sm" id="closedMessage" name="closed_message" rows="2"
            maxlength="255" placeholder="เช่น ปิดรับข้อมูลชั่วคราว เปิดอีกครั้งวันที่ ..."><?= View::e($status['closed_message'] ?? '') ?></textarea>
          <div class="form-text small">ถ้าเว้นว่าง ระบบจะแสดงข้อความเริ่มต้น "ระบบปิดรับข้อมูลชั่วคราว กรุณาติดต่อ สพฐ."</div>
        </div>

        <button class="btn btn-sm btn-primary">บันทึก</button>
      </form>
    </div>
  </div>

</div>
