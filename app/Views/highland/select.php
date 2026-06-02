<?php /** @var string $mode */ ?>
<div class="container">
  <div class="nh-row gap-2 wrap anim-up" style="margin-bottom:var(--s-3);">
    <span class="badge badge-highland"><?= nh_icon('mountain', 13) ?> ประเมินพื้นที่สูง</span>
  </div>
  <div style="margin-bottom:var(--s-5);"><?= nh_stepper('select') ?></div>

  <div class="proc-grid" style="grid-template-columns:1fr .85fr;align-items:start;">
    <div class="card card-pad anim-up">
      <h1 style="font-size:var(--fs-h2);">เลือกสังกัดและโรงเรียน</h1>
      <p class="muted mt-2">เลือกสำนักงานเขตพื้นที่ต้นสังกัด จากนั้นเลือกโรงเรียนที่ต้องการประเมิน (ปี <?= View::e(App::acadYear()) ?>)</p>

      <form method="post" action="<?= App::url('highland/start') ?>" class="stack gap-5 mt-5">
        <?= Csrf::field() ?>

        <?php if ($mode === 'school'): ?>
          <div class="field">
            <label class="label">สังกัด</label>
            <input type="text" class="form-control" value="<?= View::e($sao_name) ?>" readonly>
          </div>
          <div class="field">
            <label class="label">โรงเรียน</label>
            <input type="text" class="form-control" value="<?= View::e($sc_name) ?>" readonly>
          </div>
          <input type="hidden" name="sc_id" value="<?= View::e($sc_id) ?>">

        <?php else: ?>
          <div class="field">
            <label class="label" for="sao">สังกัด (สพป./สพม.)<span class="req">*</span></label>
            <select id="sao" name="sao_id" class="form-select" <?= $mode === 'sao' ? 'disabled' : '' ?> required>
              <option value="">— เลือกสำนักงานเขต —</option>
              <?php foreach (($sao_list ?? []) as $s): ?>
                <option value="<?= View::e($s['sao_id']) ?>" <?= ((int)($sel_sao_id ?? 0) === (int)$s['sao_id']) ? 'selected' : '' ?>>
                  <?= View::e($s['sao_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($mode === 'sao'): ?>
              <input type="hidden" name="sao_id" value="<?= View::e($sel_sao_id) ?>">
            <?php endif; ?>
          </div>
          <div class="field">
            <label class="label" for="school">โรงเรียน<span class="req">*</span></label>
            <select id="school" name="sc_id" class="form-select" required>
              <option value="">— เลือกสังกัดก่อน —</option>
            </select>
          </div>
        <?php endif; ?>

        <div class="nh-row between gap-3 mt-2">
          <a href="<?= App::url('dashboard') ?>" class="btn btn-ghost"><?= nh_icon('chevLeft', 18) ?> ยกเลิก</a>
          <button type="submit" class="btn btn-primary btn-lg">ถัดไป: ปักหมุดตำแหน่ง <?= nh_icon('arrowRight', 18) ?></button>
        </div>
      </form>
    </div>

    <!-- info panel -->
    <aside class="card card-pad anim-up" style="background:var(--surface-1);">
      <div class="nh-row gap-3 items-start">
        <span class="s-ic" style="background:var(--highland-050);color:var(--highland-700);width:44px;height:44px;border-radius:12px;display:grid;place-items:center;flex:none;"><?= nh_icon('info', 22) ?></span>
        <div>
          <h3 style="font-size:var(--fs-h3);">ก่อนเริ่มประเมิน</h3>
          <p class="muted" style="font-size:var(--fs-sm);margin-top:4px;">เตรียมข้อมูลให้พร้อม เพื่อกรอกได้ครบในครั้งเดียว</p>
        </div>
      </div>
      <hr class="divider">
      <div class="stack gap-3">
        <?php foreach ([
          ['mapPin','พิกัดที่ตั้งโรงเรียน','ระบุได้จากแผนที่ในขั้นตอนถัดไป'],
          ['ruler','เส้นทางถึงศาลากลางจังหวัด','ระบบวัดความสูงและระยะทางให้อัตโนมัติ'],
          ['users','ข้อมูลนักเรียนและกลุ่มชาติพันธุ์','จำนวนแยกระดับชั้น และกลุ่มชาติพันธุ์'],
          ['fileText','เอกสารอ้างอิงแนบ','ภาพถ่าย/เอกสารประกอบแต่ละข้อ'],
        ] as [$ic, $t, $d]): ?>
          <div class="nh-row gap-3 items-start">
            <span style="color:var(--highland-700);flex:none;margin-top:2px;"><?= nh_icon($ic, 19) ?></span>
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
(function () {
  const saoSel = document.getElementById('sao');
  const schoolSel = document.getElementById('school');
  const base = <?= json_encode(App::url('highland/schools')) ?>;

  async function loadSchools(saoId) {
    schoolSel.innerHTML = '<option value="">กำลังโหลด...</option>';
    if (!saoId) { schoolSel.innerHTML = '<option value="">— เลือกสังกัดก่อน —</option>'; return; }
    try {
      const res = await fetch(base + '?sao_id=' + encodeURIComponent(saoId), {headers: {'X-Requested-With': 'XMLHttpRequest'}});
      const data = await res.json();
      schoolSel.innerHTML = '<option value="">— เลือกโรงเรียน —</option>';
      (data || []).forEach(s => {
        const o = document.createElement('option');
        o.value = s.sc_id;
        o.textContent = s.sc_name + ' (' + s.sc_id + ')';
        schoolSel.appendChild(o);
      });
    } catch (e) {
      schoolSel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>';
    }
  }

  saoSel.addEventListener('change', e => loadSchools(e.target.value));
  if (saoSel.value) loadSchools(saoSel.value);   // โหมดเขต: โหลดอัตโนมัติ
})();
</script>
<?php endif; ?>
