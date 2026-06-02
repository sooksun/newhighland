<?php
/** @var array $ctx; @var array $e; @var array $options; @var array $hilltribList; @var array $hilltribRows */
use App\Controllers\HighlandEvalController;

$val = fn($k, $d = '') => View::e($e[$k] ?? $d);
$num = fn($k) => (string) ($e[$k] ?? '');

// แปลง/ทำความสะอาดเบอร์โทรเป็นรูปแบบ "081-277-1948"
$fmtTel = function (string $tel): string {
    $digits = preg_replace('/\D/', '', $tel);   // เอาเฉพาะตัวเลข
    if (strlen($digits) === 10) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    }
    return $tel;   // คืนค่าเดิมถ้าไม่ใช่ 10 หลัก
};

// radio group สำหรับข้อเลือก 1 ข้อ (citeria02,03,04,06,16)
$radio = function (string $name, array $opts, $current) {
    $h = '';
    foreach ($opts as $o) {
        $checked = ((int) $current === (int) $o['id']) ? 'checked' : '';
        $h .= '<div class="form-check"><input class="form-check-input" type="radio" name="' . $name . '" id="' . $name . '_' . $o['id'] . '" value="' . $o['id'] . '" ' . $checked . '>'
            . '<label class="form-check-label" for="' . $name . '_' . $o['id'] . '">' . View::e($o['label']) . '</label></div>';
    }
    return $h;
};
// checkbox group สำหรับเลือกหลายข้อ (citeria07-10)
$checks = function (string $name, array $opts, $currentCsv) {
    $sel = array_filter(array_map('intval', explode(',', (string) $currentCsv)));
    $h = '';
    foreach ($opts as $o) {
        $checked = in_array((int) $o['id'], $sel, true) ? 'checked' : '';
        $h .= '<div class="form-check"><input class="form-check-input" type="checkbox" name="' . $name . '[]" id="' . $name . '_' . $o['id'] . '" value="' . $o['id'] . '" ' . $checked . '>'
            . '<label class="form-check-label" for="' . $name . '_' . $o['id'] . '">' . View::e($o['label']) . '</label></div>';
    }
    return $h;
};
$tabs = [
  ['general','ข้อมูลโรงเรียน'],['students','จำนวนนักเรียน'],['ethnic','กลุ่มชาติพันธุ์'],
  ['geo','สภาพภูมิศาสตร์'],['transport','คมนาคม'],['utility','สาธารณูปโภค'],
  ['stu','ข้อมูลนักเรียน'],['other','ความยุ่งยากอื่น'],['spt','ความเห็น สพท.'],['obec','ความเห็น สพฐ.'],
];
?>
<div class="container">
<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-3);">
  <span class="badge badge-highland"><?= nh_icon('mountain', 13) ?> ประเมินพื้นที่สูง</span>
  <span class="badge badge-neutral"><?= View::e($ctx['sc_name']) ?> · <?= View::e($ctx['sc_id']) ?></span>
  <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปี <?= View::e(App::acadYear()) ?></span>
</div>
<div style="margin-bottom:var(--s-4);"><?= nh_stepper('eval') ?></div>

<div class="card card-pad nh-row between wrap gap-3 mb-4">
  <div>
    <h1 style="font-size:var(--fs-h2);margin-bottom:2px;"><?= View::e($ctx['sc_name']) ?> <span class="muted">(<?= View::e($ctx['sc_id']) ?>)</span></h1>
    <div class="muted" style="font-size:var(--fs-sm);">จังหวัด<?= View::e($ctx['province']) ?> · แบบประเมิน 16 ตัวชี้วัด</div>
  </div>
  <div class="nh-row gap-4 items-end wrap">
    <div class="text-c">
      <div class="muted" style="font-size:var(--fs-xs);">คะแนนรวมปัจจุบัน</div>
      <div style="font-family:var(--font-head);font-weight:700;font-size:2rem;line-height:1.1;color:var(--brand-primary);" class="tnum" id="sumScore"><?= number_format((float)($e['sum_score'] ?? 0), 2) ?></div>
      <span class="badge <?= ((int)($e['highland_type'] ?? 0) > 0) ? 'badge-success' : 'badge-neutral' ?>">
        <span class="badge-dot"></span> <?= View::e(HighlandEvalController::typeLabel((int)($e['highland_type'] ?? 0))) ?>
      </span>
    </div>
    <a href="<?= App::url('highland/print?sc_id=' . $ctx['sc_id']) ?>" class="btn btn-outline-secondary" target="_blank"><?= nh_icon('printer', 18) ?> พิมพ์</a>
  </div>
</div>

<ul class="nav nav-pills flex-wrap gap-1 mb-3" id="evalTabs" role="tablist">
  <?php foreach ($tabs as $i => $t): ?>
    <li class="nav-item"><button class="nav-link <?= $i===0?'active':'' ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= $t[0] ?>" type="button"><?= ($i+1).'. '.View::e($t[1]) ?></button></li>
  <?php endforeach; ?>
</ul>

<?php
// ช่องอัปโหลดเอกสาร/ภาพแนบ + ลิงก์ไฟล์เดิม
$refdoc = function (string $nn) use ($e) {
    $cur = $e["citeria{$nn}_refdoc"] ?? '';
    $h = '<div class="mt-2"><label class="form-label small text-muted">แนบเอกสาร/ภาพ (pdf/jpg/png)</label>'
       . '<input type="file" class="form-control form-control-sm" name="refdoc_' . $nn . '" accept=".pdf,.jpg,.jpeg,.png">';
    if ($cur !== '') {
        $h .= '<a class="small d-inline-block mt-1" target="_blank" href="' . View::e(App::url($cur)) . '">📎 ไฟล์ที่แนบไว้</a>';
    }
    return $h . '</div>';
};
?>
<form method="post" action="<?= App::url('highland/eval/save') ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <input type="hidden" name="sc_id" value="<?= View::e($ctx['sc_id']) ?>">

  <div class="tab-content">
    <!-- 1 ข้อมูลโรงเรียน -->
    <div class="tab-pane fade show active" id="tab-general">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">รหัสโรงเรียน</label><input class="form-control" value="<?= View::e($ctx['sc_id']) ?>" readonly></div>
        <div class="col-md-6"><label class="form-label">ชื่อโรงเรียน</label><input class="form-control" name="sc_names" value="<?= $val('sc_names', $ctx['sc_name']) ?>"></div>
        <div class="col-md-3"><label class="form-label">ปีการศึกษา</label><input class="form-control" value="<?= View::e(App::acadYear()) ?>" readonly></div>
        <div class="col-md-6"><label class="form-label">สังกัด</label>
          <select class="form-select" name="sao_areacode">
            <option value="">— เลือกสังกัด —</option>
            <?php foreach (($saoList ?? []) as $s): ?>
              <option value="<?= View::e($s['areacode']) ?>" <?= ((string)($selectedSao ?? '') === (string)$s['areacode']) ? 'selected' : '' ?>>
                <?= View::e($s['sao']) ?> (<?= View::e($s['province']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label">ผู้อำนวยการ</label><input class="form-control" name="director_name" value="<?= $val('director_name') ?>"></div>
        <div class="col-md-3"><label class="form-label">โทร ผอ.</label>
          <input class="form-control tel-fmt" id="director_tel" name="director_tel"
                 inputmode="numeric" maxlength="12" placeholder="081-277-1948"
                 pattern="\d{3}-\d{3}-\d{4}"
                 value="<?= View::e($fmtTel($val('director_tel'))) ?>">
          <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
        </div>
        <div class="col-md-3"><label class="form-label">ผู้กรอกข้อมูล</label><input class="form-control" name="editor_name" value="<?= $val('editor_name') ?>"></div>
        <div class="col-md-3"><label class="form-label">โทรผู้กรอก</label>
          <input class="form-control tel-fmt" id="editor_tel" name="editor_tel"
                 inputmode="numeric" maxlength="12" placeholder="081-277-1948"
                 pattern="\d{3}-\d{3}-\d{4}"
                 value="<?= View::e($fmtTel($val('editor_tel'))) ?>">
          <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
        </div>
        <div class="col-md-6"><label class="form-label">อปท. ที่ตั้ง</label><input class="form-control" name="lgo" value="<?= $val('lgo') ?>"></div>
        <div class="col-12"><label class="form-label">ที่อยู่</label><input class="form-control" name="adresss" value="<?= $val('adresss') ?>"></div>
        <div class="col-md-3"><label class="form-label">หมู่บ้าน</label><input class="form-control" name="viledges" value="<?= $val('viledges') ?>"></div>
        <div class="col-md-1"><label class="form-label">หมู่</label><input class="form-control" name="moo" value="<?= $num('moo') ?>"></div>
        <div class="col-md-3"><label class="form-label">ตำบล</label><input class="form-control" name="subdistrict" value="<?= $val('subdistrict') ?>"></div>
        <div class="col-md-2"><label class="form-label">อำเภอ</label><input class="form-control" name="district" value="<?= $val('district') ?>"></div>
        <div class="col-md-3"><label class="form-label">จังหวัด</label><input class="form-control" name="provinces" value="<?= $val('provinces', $ctx['province']) ?>"></div>
        <div class="col-md-3"><label class="form-label">ละติจูด</label><input class="form-control" value="<?= $val('lat') ?>" readonly></div>
        <div class="col-md-3"><label class="form-label">ลองจิจูด</label><input class="form-control" value="<?= $val('lng') ?>" readonly></div>
        <div class="col-md-3"><label class="form-label">ความสูง ณ จุดสูงสุด (ม.)</label><input class="form-control" value="<?= $num('highest') ?>" readonly></div>
        <div class="col-md-3"><label class="form-label">ความสูงเฉลี่ยจังหวัด (ม.)</label><input class="form-control" value="<?= $num('average_height') ?>" readonly></div>
      </div></div>
    </div>

    <!-- 2 จำนวนนักเรียน -->
    <div class="tab-pane fade" id="tab-students">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">ก่อนประถม</label><input type="number" class="form-control stu" name="stu_kinder" value="<?= $num('stu_kinder') ?>"></div>
        <div class="col-md-3"><label class="form-label">ประถมศึกษา</label><input type="number" class="form-control stu" name="stu_prim" value="<?= $num('stu_prim') ?>"></div>
        <div class="col-md-3"><label class="form-label">ม.ต้น</label><input type="number" class="form-control stu" name="stu_second" value="<?= $num('stu_second') ?>"></div>
        <div class="col-md-3"><label class="form-label">ม.ปลาย</label><input type="number" class="form-control stu" name="stu_high" value="<?= $num('stu_high') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-bold">รวมนักเรียน</label><input type="number" class="form-control fw-bold" id="stu_sum_view" value="<?= $num('stu_sum') ?>" readonly></div>
      </div></div>
    </div>

    <!-- 3 กลุ่มชาติพันธุ์ (AJAX) -->
    <div class="tab-pane fade" id="tab-ethnic">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <table class="table table-bordered align-middle" id="hilltribTable">
          <thead class="table-light"><tr><th style="width:60px">ที่</th><th>กลุ่มชาติพันธุ์</th><th style="width:140px">จำนวน (คน)</th><th style="width:80px"></th></tr></thead>
          <tbody></tbody>
          <tfoot><tr><td colspan="2" class="text-end fw-bold">รวม</td><td class="fw-bold" id="hillTotal">0</td><td></td></tr></tfoot>
        </table>
        <div class="row g-2 align-items-end">
          <div class="col-md-5"><label class="form-label small">เลือกกลุ่มชาติพันธุ์</label>
            <select id="newEthnic" class="form-select">
              <?php foreach ($hilltribList as $h): ?><option value="<?= View::e($h['ethnic_id']) ?>"><?= View::e($h['ethnic']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label small">จำนวน</label><input type="number" id="newEthnicNum" class="form-control" value="0"></div>
          <div class="col-md-2"><button type="button" class="btn btn-success w-100" id="addEthnicBtn">+ เพิ่ม</button></div>
        </div>
        <p class="text-muted small mt-2 mb-0">ข้อ 11 (ร้อยละชาติพันธุ์) และข้อ 12 (จำนวนกลุ่ม) คำนวณอัตโนมัติจากตารางนี้</p>
      </div></div>
    </div>

    <!-- 4 ภูมิศาสตร์ -->
    <div class="tab-pane fade" id="tab-geo">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">1. ระดับความสูง ณ จุดสูงสุดของเส้นทาง (ดึงอัตโนมัติ)</label>
          <input class="form-control" value="<?= $num('citeria01') ?> เมตร" readonly></div>
        <div class="mb-4"><label class="form-label fw-bold">2. เขตติดต่อชายแดนประเทศเพื่อนบ้าน</label><?= $radio('citeria02', $options['02'], $e['citeria02'] ?? 0) ?></div>
        <div class="mb-2"><label class="form-label fw-bold">3. เขตการปกครองส่วนท้องถิ่น</label><?= $radio('citeria03', $options['03'], $e['citeria03'] ?? 0) ?></div>
      </div></div>
    </div>

    <!-- 5 คมนาคม -->
    <div class="tab-pane fade" id="tab-transport">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">4. เส้นทางที่รถยนต์ขับเคลื่อน 2 ล้อไปไม่ได้</label>
          <?= $radio('citeria04', $options['04'], $e['citeria04'] ?? 0) ?>
          <div class="mt-2" style="max-width:280px"><label class="form-label small">ระยะทางช่วงนี้ (กม.)</label>
            <input type="number" step="0.01" class="form-control" name="citeria041" value="<?= $num('citeria041') ?>"></div>
          <?= $refdoc('04') ?></div>
        <div class="mb-4"><label class="form-label fw-bold">5. ระยะทางรวมเส้นทางหลัก (ดึงอัตโนมัติ)</label>
          <input class="form-control" value="<?= $num('citeria05') ?> กิโลเมตร" readonly></div>
        <div class="mb-2"><label class="form-label fw-bold">6. ลักษณะการเดินทางด้วยขนส่งสาธารณะ</label><?= $radio('citeria06', $options['06'], $e['citeria06'] ?? 0) ?></div>
      </div></div>
    </div>

    <!-- 6 สาธารณูปโภค -->
    <div class="tab-pane fade" id="tab-utility">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">7. แหล่งน้ำอุปโภค-บริโภค (เลือกได้หลายข้อ)</label><?= $checks('citeria07', $options['07'], $e['citeria07'] ?? '') ?><?= $refdoc('07') ?></div>
        <div class="mb-4"><label class="form-label fw-bold">8. ระบบไฟฟ้า (เลือกได้หลายข้อ)</label><?= $checks('citeria08', $options['08'], $e['citeria08'] ?? '') ?><?= $refdoc('08') ?></div>
        <div class="mb-4"><label class="form-label fw-bold">9. ระบบโทรศัพท์ (เลือกได้หลายข้อ)</label><?= $checks('citeria09', $options['09'], $e['citeria09'] ?? '') ?><?= $refdoc('09') ?></div>
        <div class="mb-2"><label class="form-label fw-bold">10. ระบบอินเทอร์เน็ต (เลือกได้หลายข้อ)</label><?= $checks('citeria10', $options['10'], $e['citeria10'] ?? '') ?><?= $refdoc('10') ?></div>
      </div></div>
    </div>

    <!-- 7 ข้อมูลนักเรียน -->
    <div class="tab-pane fade" id="tab-stu">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">11. ร้อยละนักเรียนชาติพันธุ์ (อัตโนมัติ)</label><input class="form-control" value="<?= $num('citeria11') ?> %" readonly></div>
        <div class="col-md-4"><label class="form-label fw-bold">12. จำนวนกลุ่มชาติพันธุ์ (อัตโนมัติ)</label><input class="form-control" value="<?= $num('citeria12') ?> กลุ่ม" readonly></div>
        <div class="col-md-4"><label class="form-label fw-bold">13. จำนวนนักเรียนยากจน/ยากจนพิเศษ</label><input type="number" class="form-control" name="citeria13" value="<?= $num('citeria13') ?>"></div>
      </div></div>
    </div>

    <!-- 8 ความยุ่งยากอื่น -->
    <div class="tab-pane fade" id="tab-other">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-4"><label class="form-label fw-bold">14. จำนวนนักเรียนพักนอน</label><input type="number" class="form-control" name="citeria14" value="<?= $num('citeria14') ?>"></div>
          <div class="col-md-4"><label class="form-label fw-bold">15. จำนวนโรงเรียนสาขา/ห้องเรียนสาขา</label><input type="number" class="form-control" name="citeria15" value="<?= $num('citeria15') ?>"><?= $refdoc('15') ?></div>
        </div>
        <div class="mb-2"><label class="form-label fw-bold">16. เป็นโรงเรียนพื้นที่พิเศษตามประกาศกระทรวงการคลัง</label><?= $radio('citeria16', $options['16'], $e['citeria16'] ?? 0) ?><?= $refdoc('16') ?></div>
      </div></div>
    </div>

    <?php
      $certStatus = [0 => 'รอพิจารณา', 1 => 'รับรอง / เห็นชอบ', 2 => 'ไม่รับรอง / ไม่เห็นชอบ'];
      $canSao  = Auth::isSao() || Auth::isAdmin();
      $canSpt  = Auth::isAdmin();
    ?>
    <!-- 9 ความเห็นระดับ สพท. (เขต) -->
    <div class="tab-pane fade" id="tab-spt">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">ความเห็นคณะกรรมการ ระดับ สพท. (เขตพื้นที่)</h2>
        <?php if ($canSao): ?>
          <div class="row g-2" style="max-width:560px">
            <div class="col-md-5"><label class="form-label small">ผลการรับรอง</label>
              <select id="sao_status" class="form-select">
                <?php foreach ($certStatus as $k=>$v): ?><option value="<?= $k ?>" <?= (int)($e['confirmstatus']??0)===$k?'selected':'' ?>><?= View::e($v) ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-12"><label class="form-label small">ความเห็น/หมายเหตุ</label>
              <textarea id="sao_comment" class="form-control" rows="2"><?= View::e($e['confirmcomment'] ?? '') ?></textarea></div>
            <div class="col-12"><button type="button" class="btn btn-primary btn-sm" onclick="certSubmit('sao')">บันทึกการรับรอง (สพท.)</button></div>
          </div>
        <?php else: ?>
          <p>ผลการรับรอง: <b><?= View::e($certStatus[(int)($e['confirmstatus']??0)] ?? '-') ?></b></p>
          <p class="text-muted"><?= View::e($e['confirmcomment'] ?? '') ?></p>
        <?php endif; ?>
      </div></div>
    </div>

    <!-- 10 ความเห็นระดับ สพฐ. -->
    <div class="tab-pane fade" id="tab-obec">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">ความเห็นคณะกรรมการ ระดับ สพฐ.</h2>
        <?php if ($canSpt): ?>
          <div class="row g-2" style="max-width:560px">
            <div class="col-md-5"><label class="form-label small">ผลการพิจารณา</label>
              <select id="spt_status" class="form-select">
                <?php foreach ($certStatus as $k=>$v): ?><option value="<?= $k ?>" <?= (int)($e['spt_commit']??0)===$k?'selected':'' ?>><?= View::e($v) ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-12"><label class="form-label small">ความเห็น/หมายเหตุ</label>
              <textarea id="spt_comment" class="form-control" rows="2"><?= View::e($e['spt_comment'] ?? '') ?></textarea></div>
            <div class="col-12"><button type="button" class="btn btn-primary btn-sm" onclick="certSubmit('spt')">บันทึกความเห็น (สพฐ.)</button></div>
          </div>
        <?php else: ?>
          <p>ผลการพิจารณา: <b><?= View::e($certStatus[(int)($e['spt_commit']??0)] ?? '-') ?></b></p>
          <p class="text-muted"><?= View::e($e['spt_comment'] ?? '') ?></p>
        <?php endif; ?>
      </div></div>
    </div>
  </div>

  <div class="d-flex justify-content-end mt-3 gap-2 sticky-bottom bg-body py-2">
    <button type="submit" class="btn btn-primary px-4">บันทึก + คิดคะแนน</button>
  </div>
</form>

<script>
(function () {
  const csrf = <?= json_encode(Csrf::token()) ?>;
  const scId = <?= (int)$ctx['sc_id'] ?>;
  const urls = {
    add: <?= json_encode(App::url('highland/hilltrib/add')) ?>,
    del: <?= json_encode(App::url('highland/hilltrib/delete')) ?>,
    cert: <?= json_encode(App::url('highland/eval/cert')) ?>,
  };

  // Auto-format เบอร์โทร "081-277-1948" ขณะพิมพ์ (ทุก input.tel-fmt)
  document.querySelectorAll('input.tel-fmt').forEach(function (inp) {
    inp.addEventListener('input', function () {
      const digits = this.value.replace(/\D/g, '').slice(0, 10);
      if (digits.length <= 3)      this.value = digits;
      else if (digits.length <= 6) this.value = digits.slice(0,3) + '-' + digits.slice(3);
      else                         this.value = digits.slice(0,3) + '-' + digits.slice(3,6) + '-' + digits.slice(6);
      this.classList.remove('is-invalid');
    });
  });
  // ตรวจสอบรูปแบบก่อน submit
  const evalForm = document.querySelector('form[action*="eval/save"]');
  if (evalForm) {
    evalForm.addEventListener('submit', function (e) {
      let firstInvalid = null;
      evalForm.querySelectorAll('input.tel-fmt').forEach(function (inp) {
        if (inp.value !== '' && !/^\d{3}-\d{3}-\d{4}$/.test(inp.value)) {
          inp.classList.add('is-invalid');
          if (!firstInvalid) firstInvalid = inp;
        }
      });
      if (firstInvalid) { e.preventDefault(); firstInvalid.focus(); }
    });
  }

  // บันทึกการรับรอง สพท./สพฐ.
  window.certSubmit = async function (level) {
    const status = document.getElementById(level + '_status').value;
    const comment = document.getElementById(level + '_comment').value;
    const body = new URLSearchParams({_csrf: csrf, sc_id: scId, level, status, comment});
    const res = await fetch(urls.cert, {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
    const data = await res.json();
    alert(data.ok ? 'บันทึกการรับรองเรียบร้อย' : 'บันทึกไม่สำเร็จ');
  };
  const ethnicNames = <?= json_encode(array_column($hilltribList, 'ethnic', 'ethnic_id'), JSON_UNESCAPED_UNICODE) ?>;

  // รวมจำนวนนักเรียนอัตโนมัติ
  function recalcStu() {
    let s = 0;
    document.querySelectorAll('.stu').forEach(i => s += (parseInt(i.value) || 0));
    document.getElementById('stu_sum_view').value = s;
  }
  document.querySelectorAll('.stu').forEach(i => i.addEventListener('input', recalcStu));

  // ตารางชาติพันธุ์
  function renderHill(state) {
    const tb = document.querySelector('#hilltribTable tbody');
    tb.innerHTML = '';
    (state.rows || []).forEach((r, i) => {
      const name = r.ethnic || ethnicNames[r.hilltrib] || r.hilltrib;
      tb.insertAdjacentHTML('beforeend',
        `<tr><td>${i+1}</td><td>${name}</td><td>${r.hilltrib_number}</td>
         <td><button type="button" class="btn btn-sm btn-outline-danger" data-del="${r.hilltrib}">ลบ</button></td></tr>`);
    });
    document.getElementById('hillTotal').textContent = state.total || 0;
    if (state.sum_score != null) document.getElementById('sumScore').textContent = (+state.sum_score).toFixed(2);
    tb.querySelectorAll('[data-del]').forEach(b =>
      b.addEventListener('click', () => post(urls.del, {hilltrib: b.dataset.del})));
  }
  async function post(url, extra) {
    const body = new URLSearchParams(Object.assign({_csrf: csrf, sc_id: scId}, extra));
    const res = await fetch(url, {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
    renderHill(await res.json());
  }
  document.getElementById('addEthnicBtn').addEventListener('click', () => {
    post(urls.add, {hilltrib: document.getElementById('newEthnic').value, hilltrib_number: document.getElementById('newEthnicNum').value || 0});
  });

  renderHill(<?= json_encode([
      'rows'  => $hilltribRows,
      'total' => array_sum(array_column($hilltribRows, 'hilltrib_number')),
  ], JSON_UNESCAPED_UNICODE) ?>);
})();
</script>
</div><!-- /.container -->

