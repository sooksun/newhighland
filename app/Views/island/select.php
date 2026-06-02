<?php /** @var string $mode */ ?>
<div class="container">
<div class="nh-row gap-2 wrap" style="margin-bottom:var(--s-4);">
  <span class="badge badge-island"><?= nh_icon('waves', 13) ?> ประเมินพื้นที่เกาะ</span>
  <span class="badge badge-neutral"><?= nh_icon('calendar', 12) ?> ปี <?= View::e(App::acadYear()) ?></span>
</div>

<div class="card card-pad" style="max-width:680px;">
    <h1 style="font-size:var(--fs-h2);margin-bottom:4px;">เริ่มประเมินโรงเรียนพื้นที่เกาะ</h1>
    <p class="muted" style="margin-bottom:var(--s-4);">เลือกสังกัดและโรงเรียน เพื่อกรอกแบบประเมินพื้นที่เกาะ (ปี <?= View::e(App::acadYear()) ?>)</p>
    <form method="post" action="<?= App::url('island/start') ?>">
      <?= Csrf::field() ?>
      <?php if ($mode === 'school'): ?>
        <div class="mb-3"><label class="form-label">สังกัด</label><input type="text" class="form-control" value="<?= View::e($sao_name) ?>" readonly></div>
        <div class="mb-3"><label class="form-label">โรงเรียน</label><input type="text" class="form-control" value="<?= View::e($sc_name) ?>" readonly></div>
        <input type="hidden" name="sc_id" value="<?= View::e($sc_id) ?>">
      <?php else: ?>
        <div class="mb-3"><label class="form-label">สังกัด (สพป./สพม.)</label>
          <select id="sao" name="sao_id" class="form-select" <?= $mode==='sao'?'disabled':'' ?> required>
            <option value="">— เลือกสังกัด —</option>
            <?php foreach (($sao_list ?? []) as $s): ?>
              <option value="<?= View::e($s['sao_id']) ?>" <?= ((int)($sel_sao_id??0)===(int)$s['sao_id'])?'selected':'' ?>><?= View::e($s['sao_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($mode==='sao'): ?><input type="hidden" name="sao_id" value="<?= View::e($sel_sao_id) ?>"><?php endif; ?>
        </div>
        <div class="mb-3"><label class="form-label">โรงเรียน</label>
          <select id="school" name="sc_id" class="form-select" required><option value="">— เลือกสังกัดก่อน —</option></select>
        </div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">ถัดไป: กรอกแบบประเมิน →</button>
    </form>
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
    try{const r=await fetch(base+'?sao_id='+encodeURIComponent(id),{headers:{'X-Requested-With':'XMLHttpRequest'}});
      const d=await r.json(); schoolSel.innerHTML='<option value="">— เลือกโรงเรียน —</option>';
      (d||[]).forEach(s=>{const o=document.createElement('option');o.value=s.sc_id;o.textContent=s.sc_name+' ('+s.sc_id+')';schoolSel.appendChild(o);});
    }catch(e){schoolSel.innerHTML='<option value="">โหลดไม่สำเร็จ</option>';}
  }
  saoSel.addEventListener('change',e=>load(e.target.value));
  if(saoSel.value) load(saoSel.value);
})();
</script>
<?php endif; ?>
