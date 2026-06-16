<?php /** @var string $mode */ ?>
<div class="container">
  <div class="nh-row gap-2 wrap anim-up" style="margin-bottom:var(--s-3);">
    <span class="badge badge-island"><?= nh_icon('waves', 13) ?> ประเมินพื้นที่เกาะ</span>
  </div>
  <div style="margin-bottom:var(--s-5);"><?= nh_island_stepper('select') ?></div>

  <div class="proc-grid" style="grid-template-columns:1fr .85fr;align-items:start;">
    <div class="card card-pad anim-up">
      <h1 style="font-size:var(--fs-h2);">เลือกสังกัดและโรงเรียน</h1>
      <p class="muted mt-2">เลือกสำนักงานเขตพื้นที่ต้นสังกัด จากนั้นเลือกโรงเรียนที่ต้องการประเมินพื้นที่เกาะ (ปี <?= View::e(App::acadYear()) ?>)</p>

      <form method="post" action="<?= App::url('island/start') ?>" class="stack gap-5 mt-5">
        <?= Csrf::field() ?>
        <?php if ($mode === 'school'): ?>
          <?php if (!empty($in_roster)): ?>
            <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
              <?= nh_icon('info', 20) ?>
              <div>โรงเรียนนี้เป็น<b>พื้นที่เกาะเดิม</b> (ผ่านการประเมินแล้ว) จึงไม่ต้องประเมินใหม่ — เพียง<b>ยืนยันการคงอยู่</b>เท่านั้น</div>
            </div>
          <?php endif; ?>
          <div class="field"><label class="label">สังกัด</label><input type="text" class="form-control" value="<?= View::e($sao_name) ?>" readonly></div>
          <div class="field"><label class="label">โรงเรียน</label><input type="text" class="form-control" value="<?= View::e($sc_name) ?>" readonly></div>
          <input type="hidden" name="sc_id" value="<?= View::e($sc_id) ?>">
        <?php else: ?>
          <div class="field"><label class="label" for="sao">สังกัด (สพป./สพม.)<span class="req">*</span></label>
            <select id="sao" name="sao_id" class="form-select" <?= $mode==='sao'?'disabled':'' ?> required>
              <option value="">— เลือกสำนักงานเขต —</option>
              <?php foreach (($sao_list ?? []) as $s): ?>
                <option value="<?= View::e($s['sao_id']) ?>" <?= ((int)($sel_sao_id??0)===(int)$s['sao_id'])?'selected':'' ?>><?= View::e($s['sao_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($mode==='sao'): ?><input type="hidden" name="sao_id" value="<?= View::e($sel_sao_id) ?>"><?php endif; ?>
          </div>
          <div class="field"><label class="label" for="school">โรงเรียน<span class="req">*</span></label>
            <select id="school" name="sc_id" class="form-select" required><option value="">— เลือกสังกัดก่อน —</option></select>
            <div class="muted" style="font-size:var(--fs-xs);margin-top:6px;"><?= nh_icon('info', 12) ?> แสดงเฉพาะโรงเรียนในจังหวัดที่เคยมีโรงเรียนพื้นที่เกาะ (กรองเบื้องต้นตามเกณฑ์)</div>
          </div>
        <?php endif; ?>

        <div class="nh-row between gap-3 mt-2">
          <a href="<?= App::url('dashboard') ?>" class="btn btn-ghost"><?= nh_icon('chevLeft', 18) ?> ยกเลิก</a>
          <?php if ($mode === 'school' && !empty($in_roster)): ?>
            <a href="<?= App::url('confirm?area=2') ?>" class="btn btn-primary btn-lg"><?= nh_icon('shieldCheck', 18) ?> ไปยืนยันการคงอยู่</a>
          <?php else: ?>
            <button type="submit" class="btn btn-primary btn-lg">ถัดไป: กรอกแบบประเมิน <?= nh_icon('arrowRight', 18) ?></button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- info panel -->
    <aside class="card card-pad anim-up" style="background:var(--surface-1);">
      <div class="nh-row gap-3 items-start">
        <span style="background:var(--island-050,#e6f6f6);color:var(--island-700,#0b6e6e);width:44px;height:44px;border-radius:12px;display:grid;place-items:center;flex:none;"><?= nh_icon('info', 22) ?></span>
        <div>
          <h3 style="font-size:var(--fs-h3);">ก่อนเริ่มประเมิน</h3>
          <p class="muted" style="font-size:var(--fs-sm);margin-top:4px;">เตรียมข้อมูลให้พร้อม เพื่อกรอกได้ครบในครั้งเดียว</p>
        </div>
      </div>
      <hr class="divider">
      <div class="stack gap-3">
        <?php foreach ([
          ['waves','ที่ตั้งและสภาพเกาะ','เป็นเกาะ/ติดทะเล · การเดินทางขึ้น-ลงเกาะ'],
          ['users','ข้อมูลนักเรียนและบุคลากร','จำนวนแยกระดับชั้น · ครู/บุคลากร'],
          ['ruler','การคมนาคม/สาธารณูปโภค','เส้นทาง · ไฟฟ้า · น้ำ · สัญญาณ'],
          ['fileText','เอกสารอ้างอิงแนบ','ภาพถ่าย/เอกสารประกอบแต่ละข้อ'],
        ] as [$ic, $t, $d]): ?>
          <div class="nh-row gap-3 items-start">
            <span style="color:var(--island-700,#0b6e6e);flex:none;margin-top:2px;"><?= nh_icon($ic, 19) ?></span>
            <div><b style="font-family:var(--font-head);font-size:var(--fs-sm);"><?= View::e($t) ?></b>
              <div class="muted" style="font-size:var(--fs-xs);"><?= View::e($d) ?></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div>

<?php if ($mode !== 'school'): ?>
<script>
(function(){
  const saoSel=document.getElementById('sao'), schoolSel=document.getElementById('school');
  const base=<?= json_encode(App::url('highland/schools')) ?>;
  async function load(id){
    schoolSel.innerHTML='<option value="">กำลังโหลด...</option>';
    if(!id){schoolSel.innerHTML='<option value="">— เลือกสังกัดก่อน —</option>';return;}
    try{const r=await fetch(base+'?sao_id='+encodeURIComponent(id)+'&area=2',{headers:{'X-Requested-With':'XMLHttpRequest'}});
      const d=await r.json(); schoolSel.innerHTML='<option value="">— เลือกโรงเรียน —</option>';
      (d||[]).forEach(s=>{const o=document.createElement('option');o.value=s.sc_id;
        if(s.in_roster){o.disabled=true;o.textContent=s.sc_name+' ('+s.sc_id+') — ผ่านประเมินแล้ว / รับรองการคงอยู่';}
        else{o.textContent=s.sc_name+' ('+s.sc_id+')';}
        schoolSel.appendChild(o);});
    }catch(e){schoolSel.innerHTML='<option value="">โหลดไม่สำเร็จ</option>';}
  }
  saoSel.addEventListener('change',e=>load(e.target.value));
  if(saoSel.value) load(saoSel.value);
})();
</script>
<?php endif; ?>
