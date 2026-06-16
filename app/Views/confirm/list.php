<?php /** @var int $area; @var array $rows; @var array $stats; @var array $filters; @var array $mergeOpts */
use App\Models\SchoolConfirm;
$isAdmin = Auth::isAdmin();
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
  <?php foreach ([['ทั้งหมด',$stats['total']??0,'secondary'],['โรงเรียนยืนยันแล้ว',$stats['confirmed']??0,'info'],['เขตรับรองแล้ว',$stats['approved']??0,'success'],['ยุบ/รวม/เลิก',$stats['closed']??0,'danger']] as $c): ?>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
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

<div class="text-muted small mb-2">พบ <?= number_format(count($rows)) ?> รายการ</div>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
  <thead class="table-light"><tr>
    <th>โรงเรียน</th><th>จังหวัด</th><th>โรงเรียนยืนยัน</th><th>รับรอง (สพท.)</th><?= $isAdmin?'<th>สพฐ.</th>':'' ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr id="row<?= $r['id'] ?>">
      <td><?= View::e($r['sc_name']) ?><div class="text-muted small"><?= View::e($r['sc_id']) ?></div></td>
      <td><?= View::e($r['provinces']) ?></td>
      <td>
        <?php if ($r['school_confirmed']): ?>
          <span class="badge bg-<?= $r['opened']?'info':'danger' ?>"><?= $r['opened']?'คงอยู่':'ยุบ/รวม/เลิก' ?></span>
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
      </td>
      <td>
        <form method="post" action="<?= App::url('confirm/sao') ?>" class="d-flex gap-1 align-items-center">
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
        <form method="post" action="<?= App::url('confirm/spt') ?>" class="d-flex gap-1">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="area" value="<?= $area ?>">
          <select name="spt_status" class="form-select form-select-sm" style="width:auto">
            <?php foreach ([0=>'รอ',1=>'เห็นชอบ',2=>'ไม่เห็นชอบ'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= (int)$r['spt_status']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
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
