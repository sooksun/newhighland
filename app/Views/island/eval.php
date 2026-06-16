<?php
/** @var array $ctx; @var array $e */
use App\Controllers\IslandEvalController;
use App\Models\IslandOption;

$val = fn($k, $d = '') => View::e($e[$k] ?? $d);
$num = fn($k) => (string) ($e[$k] ?? '');
$fmtTel = function (string $tel): string {
    $d = preg_replace('/\D/', '', $tel);
    return strlen($d) === 10 ? substr($d,0,3).'-'.substr($d,3,3).'-'.substr($d,6,4) : $tel;
};
$radio = function (string $name, string $nn, $current) {
    $h = '';
    foreach (IslandOption::get($nn) as $o) {
        $ck = ((int) $current === (int) $o['id']) ? 'checked' : '';
        $h .= '<div class="form-check"><input class="form-check-input" type="radio" name="' . $name . '" id="' . $name . '_' . $o['id'] . '" value="' . $o['id'] . '" ' . $ck . '>'
            . '<label class="form-check-label" for="' . $name . '_' . $o['id'] . '">' . View::e($o['label']) . '</label></div>';
    }
    return $h;
};
$refdoc = function (string $nn) use ($e) {
    $cur = $e["citeria{$nn}_refdoc"] ?? '';
    $h = '<div class="mt-2"><label class="form-label small text-muted">แนบเอกสาร/ภาพ (pdf/jpg/png)</label>'
       . '<input type="file" class="form-control form-control-sm" name="refdoc_' . $nn . '" accept=".pdf,.jpg,.jpeg,.png">';
    if (is_string($cur) && $cur !== '' && strpos($cur, 'uploads/') === 0) {
        $h .= '<a class="small d-inline-block mt-1" target="_blank" href="' . View::e(App::url($cur)) . '">📎 ไฟล์ที่แนบไว้</a>';
    }
    return $h . '</div>';
};
$tabs = [['general','ข้อมูลโรงเรียน'],['students','จำนวนนักเรียน'],['staff','ครู/บุคลากร'],
  ['geo','สภาพภูมิศาสตร์'],['loc','ที่ตั้ง'],['transport','การคมนาคม'],['utility','สาธารณูปโภค'],
  ['other','ความยุ่งยากอื่น'],['spt','ความเห็น สพท.'],['obec','ความเห็น สพฐ.']];
$certStatus = [0 => 'รอพิจารณา', 1 => 'รับรอง / เห็นชอบ', 2 => 'ไม่รับรอง / ไม่เห็นชอบ'];
$canSao = Auth::isSao() || Auth::isAdmin();
$canSpt = Auth::isAdmin();
?>
<div class="container">
<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-3);">
  <span class="badge badge-island"><?= nh_icon('waves', 13) ?> ประเมินพื้นที่เกาะ</span>
  <span class="badge badge-neutral"><?= View::e($ctx['sc_name']) ?> · <?= View::e($ctx['sc_id']) ?></span>
  <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปี <?= View::e(App::acadYear()) ?></span>
</div>
<div style="margin-bottom:var(--s-4);"><?= nh_island_stepper('eval') ?></div>

<div class="card card-pad nh-row between wrap gap-3 mb-4">
  <div>
    <h1 style="font-size:var(--fs-h2);margin-bottom:2px;"><?= View::e($ctx['sc_name']) ?> <span class="muted">(<?= View::e($ctx['sc_id']) ?>)</span></h1>
    <div class="muted" style="font-size:var(--fs-sm);">จังหวัด<?= View::e($ctx['province']) ?> · แบบประเมินพื้นที่เกาะ</div>
  </div>
  <div class="nh-row gap-4 items-end wrap">
    <div class="text-c">
      <div class="muted" style="font-size:var(--fs-xs);">คะแนนรวม (เต็ม 100)</div>
      <div style="font-family:var(--font-head);font-weight:700;font-size:2rem;line-height:1.1;color:var(--island-700);" class="tnum"><?= number_format((float)($e['sum_score'] ?? 0), 2) ?></div>
      <span class="badge <?= ((int)($e['island_type'] ?? 0) > 0) ? 'badge-success' : 'badge-neutral' ?>"><span class="badge-dot"></span> <?= View::e(IslandEvalController::typeLabel((int)($e['island_type'] ?? 0))) ?></span>
    </div>
    <a href="<?= App::url('island/print?sc_id=' . $ctx['sc_id']) ?>" class="btn btn-outline-secondary" target="_blank"><?= nh_icon('printer', 18) ?> พิมพ์</a>
  </div>
</div>

<ul class="nav nav-pills flex-wrap gap-1 mb-3" role="tablist">
  <?php foreach ($tabs as $i => $t): ?>
    <li class="nav-item"><button class="nav-link <?= $i===0?'active':'' ?>" data-bs-toggle="pill" data-bs-target="#it-<?= $t[0] ?>" type="button"><?= ($i+1).'. '.View::e($t[1]) ?></button></li>
  <?php endforeach; ?>
</ul>

<form method="post" action="<?= App::url('island/eval/save') ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <input type="hidden" name="sc_id" value="<?= View::e($ctx['sc_id']) ?>">
  <div class="tab-content">

    <div class="tab-pane fade show active" id="it-general">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">รหัสโรงเรียน</label><input class="form-control" value="<?= View::e($ctx['sc_id']) ?>" readonly></div>
        <div class="col-md-6"><label class="form-label">ชื่อโรงเรียน</label><input class="form-control" name="sc_names" value="<?= $val('sc_names', $ctx['sc_name']) ?>"></div>
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
          <input class="form-control tel-fmt" name="director_tel" inputmode="numeric" maxlength="12" placeholder="081-277-1948" pattern="\d{3}-\d{3}-\d{4}" value="<?= View::e($fmtTel($val('director_tel'))) ?>">
          <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
        </div>
        <div class="col-md-3"><label class="form-label">ผู้กรอกข้อมูล</label><input class="form-control" name="editor_name" value="<?= $val('editor_name') ?>"></div>
        <div class="col-md-3"><label class="form-label">โทรผู้กรอก</label>
          <input class="form-control tel-fmt" name="editor_tel" inputmode="numeric" maxlength="12" placeholder="081-277-1948" pattern="\d{3}-\d{3}-\d{4}" value="<?= View::e($fmtTel($val('editor_tel'))) ?>">
          <div class="invalid-feedback">กรุณากรอกเบอร์โทร 10 หลัก</div>
        </div>
        <div class="col-12"><label class="form-label">ที่อยู่</label><input class="form-control" name="adresss" value="<?= $val('adresss') ?>"></div>
        <div class="col-md-3"><label class="form-label">หมู่บ้าน</label><input class="form-control" name="viledges" value="<?= $val('viledges') ?>"></div>
        <div class="col-md-1"><label class="form-label">หมู่</label><input class="form-control" name="moo" value="<?= $num('moo') ?>"></div>
        <div class="col-md-3"><label class="form-label">ตำบล</label><input class="form-control" name="subdistrict" value="<?= $val('subdistrict') ?>"></div>
        <div class="col-md-2"><label class="form-label">อำเภอ</label><input class="form-control" name="district" value="<?= $val('district') ?>"></div>
        <div class="col-md-3"><label class="form-label">จังหวัด</label><input class="form-control" name="provinces" value="<?= $val('provinces', $ctx['province']) ?>"></div>
        <div class="col-md-3"><label class="form-label">ละติจูด</label><input class="form-control" name="lat" value="<?= $val('lat') ?>"></div>
        <div class="col-md-3"><label class="form-label">ลองจิจูด</label><input class="form-control" name="lng" value="<?= $val('lng') ?>"></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-students">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">ก่อนประถม</label><input type="number" class="form-control stu" name="stu_kinder" value="<?= $num('stu_kinder') ?>"></div>
        <div class="col-md-3"><label class="form-label">ประถมศึกษา</label><input type="number" class="form-control stu" name="stu_prim" value="<?= $num('stu_prim') ?>"></div>
        <div class="col-md-3"><label class="form-label">ม.ต้น</label><input type="number" class="form-control stu" name="stu_second" value="<?= $num('stu_second') ?>"></div>
        <div class="col-md-3"><label class="form-label">ม.ปลาย</label><input type="number" class="form-control stu" name="stu_high" value="<?= $num('stu_high') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-bold">รวมนักเรียน</label><input type="number" class="form-control fw-bold" id="stu_sum_view" value="<?= $num('stu_sum') ?>" readonly></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-staff">
      <div class="card border-0 shadow-sm"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">ผู้บริหาร</label><input type="number" class="form-control tch" name="teacher" value="<?= $num('teacher') ?>"></div>
        <div class="col-md-3"><label class="form-label">ข้าราชการครู</label><input type="number" class="form-control tch" name="gov_employee" value="<?= $num('gov_employee') ?>"></div>
        <div class="col-md-3"><label class="form-label">พนักงานราชการ</label><input type="number" class="form-control tch" name="panuk" value="<?= $num('panuk') ?>"></div>
        <div class="col-md-3"><label class="form-label">ลูกจ้างประจำ</label><input type="number" class="form-control tch" name="perm_teacher" value="<?= $num('perm_teacher') ?>"></div>
        <div class="col-md-3"><label class="form-label">อัตราจ้างสายสอน</label><input type="number" class="form-control tch" name="perm_employee" value="<?= $num('perm_employee') ?>"></div>
        <div class="col-md-3"><label class="form-label">อัตราจ้างสายสนับสนุน</label><input type="number" class="form-control tch" name="suport_teaching" value="<?= $num('suport_teaching') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-bold">รวมบุคลากร</label><input type="number" class="form-control fw-bold" id="tch_sum_view" value="<?= $num('sum_teacher') ?>" readonly></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-geo">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <label class="form-label fw-bold">1.1 โรงเรียนตั้งอยู่ในพื้นที่ที่เป็นเกาะ (ด่านคัดกรอง)</label>
        <div class="alert alert-warning py-2 small">หากตอบ "ไม่ใช่" จะไม่จัดเป็นโรงเรียนพื้นที่เกาะ</div>
        <?= $radio('citeria01', '01', $e['citeria01'] ?? 0) ?>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-loc">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">2.1 เขตการปกครองส่วนท้องถิ่น</label><?= $radio('citeria02', '02', $e['citeria02'] ?? 0) ?></div>
        <div class="mb-2"><label class="form-label fw-bold">2.2 ลักษณะที่ตั้ง</label><?= $radio('citeria03', '03', $e['citeria03'] ?? 0) ?></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-transport">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">3.1 พาหนะที่ใช้เดินทางจากเกาะไปแผ่นดินใหญ่</label><?= $radio('citeria04', '04', $e['citeria04'] ?? 0) ?><?= $refdoc('04') ?></div>
        <div class="row g-3 mb-3">
          <div class="col-md-3"><label class="form-label fw-bold">3.2 ระยะทางทางบก (กม.)</label><input type="number" step="0.01" class="form-control" name="citeria05" value="<?= $num('citeria05') ?>"></div>
          <div class="col-md-3"><label class="form-label fw-bold">3.3 ระยะทางทางน้ำ (กม.)</label><input type="number" step="0.01" class="form-control" name="citeria06" value="<?= $num('citeria06') ?>"></div>
          <div class="col-md-3"><label class="form-label fw-bold">3.4 เวลาทางน้ำ (นาที)</label><input type="number" class="form-control" name="citeria07" value="<?= $num('citeria07') ?>"></div>
          <div class="col-md-3"><label class="form-label fw-bold">3.5 ค่าโดยสารทางน้ำ (บาท/เที่ยว)</label><input type="number" step="0.01" class="form-control" name="citeria08" value="<?= $num('citeria08') ?>"></div>
        </div>
        <div class="mb-2"><label class="form-label fw-bold">3.6 ลักษณะการเดินทางต่อจากท่าเรือถึงโรงเรียน</label><?= $radio('citeria09', '09', $e['citeria09'] ?? 0) ?><?= $refdoc('09') ?></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-utility">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">4.1 ระบบไฟฟ้า</label><?= $radio('citeria10', '10', $e['citeria10'] ?? 0) ?><?= $refdoc('10') ?></div>
        <div class="mb-4"><label class="form-label fw-bold">4.2 แหล่งน้ำอุปโภค-บริโภค</label><?= $radio('citeria11', '11', $e['citeria11'] ?? 0) ?><?= $refdoc('11') ?></div>
        <div class="mb-4"><label class="form-label fw-bold">4.3 ระบบอินเทอร์เน็ต</label><?= $radio('citeria12', '12', $e['citeria12'] ?? 0) ?><?= $refdoc('12') ?></div>
        <div class="mb-2"><label class="form-label fw-bold">4.4 ระบบโทรศัพท์</label><?= $radio('citeria13', '13', $e['citeria13'] ?? 0) ?><?= $refdoc('13') ?></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-other">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="mb-4"><label class="form-label fw-bold">5.1 เป็นโรงเรียนพื้นที่พิเศษตามประกาศกระทรวงการคลัง</label><?= $radio('citeria14', '14', $e['citeria14'] ?? 0) ?><?= $refdoc('14') ?></div>
        <div class="mb-2" style="max-width:320px"><label class="form-label fw-bold">5.2 จำนวนนักเรียนยากจน/ยากจนพิเศษ (คน)</label><input type="number" class="form-control" name="citeria15" value="<?= $num('citeria15') ?>"></div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-spt">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">ความเห็นคณะกรรมการ ระดับ สพท. (เขตพื้นที่)</h2>
        <?php if ($canSao): ?>
          <div class="row g-2" style="max-width:560px">
            <div class="col-md-5"><label class="form-label small">ผลการรับรอง</label>
              <select id="sao_status" class="form-select"><?php foreach ($certStatus as $k=>$v): ?><option value="<?= $k ?>" <?= (int)($e['confirmstatus']??0)===$k?'selected':'' ?>><?= View::e($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label small">ความเห็น/หมายเหตุ</label><textarea id="sao_comment" class="form-control" rows="2"><?= View::e($e['confirmcomment'] ?? '') ?></textarea></div>
            <div class="col-12"><button type="button" class="btn btn-primary btn-sm" onclick="islandCert('sao')">บันทึกการรับรอง (สพท.)</button></div>
          </div>
        <?php else: ?><p>ผลการรับรอง: <b><?= View::e($certStatus[(int)($e['confirmstatus']??0)] ?? '-') ?></b></p><p class="text-muted"><?= View::e($e['confirmcomment'] ?? '') ?></p><?php endif; ?>
      </div></div>
    </div>

    <div class="tab-pane fade" id="it-obec">
      <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">ความเห็นคณะกรรมการ ระดับ สพฐ.</h2>
        <?php if ($canSpt): ?>
          <div class="row g-2" style="max-width:560px">
            <div class="col-md-5"><label class="form-label small">ผลการพิจารณา</label>
              <select id="spt_status" class="form-select"><?php foreach ($certStatus as $k=>$v): ?><option value="<?= $k ?>" <?= (int)($e['spt_commit']??0)===$k?'selected':'' ?>><?= View::e($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label small">ความเห็น/หมายเหตุ</label><textarea id="spt_comment" class="form-control" rows="2"><?= View::e($e['spt_comment'] ?? '') ?></textarea></div>
            <div class="col-12"><button type="button" class="btn btn-primary btn-sm" onclick="islandCert('spt')">บันทึกความเห็น (สพฐ.)</button></div>
          </div>
        <?php else: ?><p>ผลการพิจารณา: <b><?= View::e($certStatus[(int)($e['spt_commit']??0)] ?? '-') ?></b></p><p class="text-muted"><?= View::e($e['spt_comment'] ?? '') ?></p><?php endif; ?>
      </div></div>
    </div>

  </div>
  <div class="d-flex justify-content-end mt-3 gap-2 sticky-bottom bg-body py-2">
    <button type="submit" class="btn btn-primary px-4">บันทึก + คิดคะแนน</button>
  </div>
</form>

<script>
(function(){
  const csrf=<?= json_encode(Csrf::token()) ?>, scId=<?= (int)$ctx['sc_id'] ?>;
  const certUrl=<?= json_encode(App::url('island/eval/cert')) ?>;
  function sumBy(cls,target){let s=0;document.querySelectorAll(cls).forEach(i=>s+=(parseInt(i.value)||0));document.getElementById(target).value=s;}
  document.querySelectorAll('.stu').forEach(i=>i.addEventListener('input',()=>sumBy('.stu','stu_sum_view')));
  document.querySelectorAll('.tch').forEach(i=>i.addEventListener('input',()=>sumBy('.tch','tch_sum_view')));
  document.querySelectorAll('input.tel-fmt').forEach(function(inp){
    inp.addEventListener('input',function(){
      const d=this.value.replace(/\D/g,'').slice(0,10);
      if(d.length<=3)this.value=d;
      else if(d.length<=6)this.value=d.slice(0,3)+'-'+d.slice(3);
      else this.value=d.slice(0,3)+'-'+d.slice(3,6)+'-'+d.slice(6);
    });
  });
  window.islandCert=async function(level){
    const status=document.getElementById(level+'_status').value, comment=document.getElementById(level+'_comment').value;
    const body=new URLSearchParams({_csrf:csrf,sc_id:scId,level,status,comment});
    const r=await fetch(certUrl,{method:'POST',body,headers:{'X-Requested-With':'XMLHttpRequest'}});
    const d=await r.json(); alert(d.ok?'บันทึกการรับรองเรียบร้อย':'บันทึกไม่สำเร็จ');
  };

  // ด่านคัดกรอง: กันการส่งฟอร์มโดยยังไม่ตอบข้อ 1.1 (เป็นเกาะหรือไม่)
  // เพราะถ้าไม่เลือก ระบบจะจัดเป็น "ไม่ใช่พื้นที่เกาะ" โดยผู้ใช้อาจไม่ตั้งใจ
  const evalForm=document.querySelector('form[action$="island/eval/save"]');
  if(evalForm){
    evalForm.addEventListener('submit',function(e){
      if(!evalForm.querySelector('input[name="citeria01"]:checked')){
        e.preventDefault();
        const tabBtn=document.querySelector('[data-bs-target="#it-geo"]');
        if(tabBtn) tabBtn.click();
        alert('กรุณาตอบข้อ 1.1 (ด่านคัดกรอง): โรงเรียนตั้งอยู่ในพื้นที่ที่เป็นเกาะหรือไม่\nหากไม่เลือก ระบบจะถือว่าไม่ใช่พื้นที่เกาะ');
      }
    });
  }
})();
</script>
</div><!-- /.container -->
