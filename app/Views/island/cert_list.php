<?php /** @var array $rows; @var array $stats; @var array $filters */
$isAdmin = Auth::isAdmin();
$typeLabels = [0 => 'ไม่เป็นพื้นที่เกาะ', 1 => 'ยุ่งยาก', 2 => 'ยุ่งยากมาก', 3 => 'ยุ่งยากมากที่สุด'];
$certBadge  = function (int $s): array {
    return [0 => ['warning', 'รออนุมัติ'], 1 => ['success', 'รับรองแล้ว'], 2 => ['danger', 'ไม่รับรอง']][$s] ?? ['secondary', '-'];
};
?>
<div class="container">

  <span class="badge badge-island" style="margin-bottom:var(--s-3);"><?= nh_icon('waves', 13) ?> ประเมินพื้นที่เกาะ</span>
  <?= nh_island_stepper('cert') ?>

  <?php if (count(\App\Services\SchoolMenu::current()['areas'] ?: [1, 2]) > 1): ?>
  <ul class="nav nav-pills mt-4 mb-2">
    <li class="nav-item"><a class="nav-link" href="<?= App::url('highland/cert') ?>">พื้นที่สูง</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= App::url('island/cert') ?>">พื้นที่เกาะ</a></li>
  </ul>
  <?php endif; ?>

  <div class="nh-row gap-2 wrap" style="margin:var(--s-2) 0;">
    <span class="badge badge-primary"><?= nh_icon('shieldCheck', 13) ?> รับรองผลประเมิน (สพท.)</span>
    <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปีงบประมาณ <?= View::e(App::acadYear()) ?></span>
  </div>

  <h1 class="h5 fw-bold mb-1">รออนุมัติ — รับรองผลการประเมินพื้นที่เกาะ</h1>
  <p class="text-muted small">โรงเรียนที่ประเมินเสร็จแล้ว รอ สพป./สพม. ยืนยันว่าข้อมูลถูกต้อง<?= $isAdmin ? ' · ทุกเขต' : ' · ' . View::e(Auth::saoName()) ?></p>

  <div class="row g-2 mb-3">
    <?php foreach ([
        ['ทั้งหมด',      $stats['total']    ?? 0, 'secondary'],
        ['รออนุมัติ',     $stats['pending']  ?? 0, 'warning'],
        ['รับรองแล้ว',    $stats['approved'] ?? 0, 'success'],
        ['ไม่รับรอง',     $stats['rejected'] ?? 0, 'danger'],
    ] as $c): ?>
      <div class="col-6 col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-2">
        <div class="text-muted small"><?= View::e($c[0]) ?></div>
        <div class="h5 fw-bold mb-0 text-<?= $c[2] ?>"><?= number_format((int) $c[1]) ?></div>
      </div></div></div>
    <?php endforeach; ?>
  </div>

  <form class="row g-2 mb-3" method="get" action="<?= App::url('island/cert') ?>">
    <div class="col-md-4"><input class="form-control form-control-sm" name="q" value="<?= View::e($filters['q']) ?>" placeholder="ชื่อ / รหัสโรงเรียน"></div>
    <div class="col-md-3"><input class="form-control form-control-sm" name="province" value="<?= View::e($filters['province']) ?>" placeholder="จังหวัด"></div>
    <div class="col-md-3"><select class="form-select form-select-sm" name="status">
      <option value="">สถานะรับรอง: ทั้งหมด</option>
      <option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>รออนุมัติ</option>
      <option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>รับรองแล้ว</option>
      <option value="2" <?= $filters['status'] === '2' ? 'selected' : '' ?>>ไม่รับรอง</option>
    </select></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">กรอง</button></div>
  </form>

  <div class="text-muted small mb-2">พบ <?= number_format(count($rows)) ?> รายการ</div>

  <div class="table-responsive">
  <table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr>
      <th>โรงเรียน</th>
      <th>จังหวัด</th>
      <th class="text-center">คะแนน</th>
      <th>ผลประเมิน</th>
      <th>รับรอง (สพท.)</th>
      <?= $isAdmin ? '<th>สพฐ.</th>' : '' ?>
      <th></th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="<?= $isAdmin ? 7 : 6 ?>" class="text-center text-muted py-4">ยังไม่มีโรงเรียนที่ประเมินเสร็จในปีงบประมาณนี้</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): $st = (int) ($r['confirmstatus'] ?? 0); [$bc, $bl] = $certBadge($st); ?>
      <tr id="row<?= View::e($r['sc_id']) ?>">
        <td>
          <?= View::e($r['sc_names'] ?: $r['sc_name']) ?>
          <span class="badge bg-<?= $bc ?> ms-1"><?= View::e($bl) ?></span>
          <div class="text-muted small"><?= View::e($r['sc_id']) ?></div>
        </td>
        <td><?= View::e($r['provinces'] ?: $r['m_provinces']) ?></td>
        <td class="text-center fw-bold"><?= number_format((float) $r['sum_score'], 2) ?></td>
        <td><span class="badge bg-light text-dark"><?= View::e($typeLabels[(int) ($r['island_type'] ?? 0)] ?? '-') ?></span></td>
        <td>
          <form method="post" action="<?= App::url('island/cert/sao') ?>" class="d-flex gap-1 align-items-center">
            <?= Csrf::field() ?><input type="hidden" name="sc_id" value="<?= View::e($r['sc_id']) ?>">
            <select name="confirmstatus" class="form-select form-select-sm" style="width:auto">
              <?php foreach ([0 => 'รออนุมัติ', 1 => 'รับรอง', 2 => 'ไม่รับรอง'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
            <input name="confirmcomment" class="form-control form-control-sm" style="width:140px" value="<?= View::e($r['confirmcomment'] ?? '') ?>" placeholder="หมายเหตุ">
            <button class="btn btn-sm btn-outline-primary">บันทึก</button>
          </form>
        </td>
        <?php if ($isAdmin): $sp = (int) ($r['spt_commit'] ?? 0); ?>
        <td>
          <form method="post" action="<?= App::url('island/cert/spt') ?>" class="d-flex gap-1">
            <?= Csrf::field() ?><input type="hidden" name="sc_id" value="<?= View::e($r['sc_id']) ?>">
            <select name="spt_commit" class="form-select form-select-sm" style="width:auto">
              <?php foreach ([0 => 'รอ', 1 => 'เห็นชอบ', 2 => 'ไม่เห็นชอบ'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $sp === $k ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary">บันทึก</button>
          </form>
        </td>
        <?php endif; ?>
        <td class="text-nowrap">
          <a class="btn btn-sm btn-ghost" href="<?= App::url('island/eval?sc_id=' . $r['sc_id']) ?>" title="เปิดแบบประเมิน"><?= nh_icon('clipboard', 16) ?></a>
          <a class="btn btn-sm btn-ghost" href="<?= App::url('island/print?sc_id=' . $r['sc_id']) ?>" title="พิมพ์ผล" target="_blank"><?= nh_icon('printer', 16) ?></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div><!-- /.container -->
