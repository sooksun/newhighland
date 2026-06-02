# PLAN — แผนการพัฒนาระบบ newhighland (ปีงบประมาณ 2569)

> อ้างอิง: `docs/PRD.md` | Stack: PHP ล้วน + MySQL (PDO) | ใช้ DB เดิม `ssrainfo_ssra`
> หลักการ: ทำส่วนที่ 1 ให้ครบ end-to-end ก่อน แล้วค่อย reuse โครงไปส่วนที่ 2–4

---

## ลำดับการพัฒนา (Phases)

### Phase 0 — เตรียมโครงสร้าง (Foundation)
เป้าหมาย: มีโครงรันได้ + ต่อ DB เดิม + login เดิม
- [ ] 0.1 สร้างโครงโฟลเดอร์ตาม PRD §4.2 (`public/`, `app/`, `config/`)
- [ ] 0.2 `config/config.php` — DB creds, Google API key, `ACAD_YEAR` (ยืนยันค่ากับผู้เกี่ยวข้องก่อน)
- [ ] 0.3 `app/Core/Db.php` — PDO singleton (utf8mb4, prepared statements)
- [ ] 0.4 `app/Core/Router.php` + `public/index.php` — front controller
- [ ] 0.5 `app/Core/View.php` — render layout + Bootstrap 5.3, ฟอนต์ Sarabun
- [ ] 0.6 `app/Core/Auth.php` — อ่าน session เดิม / login จาก `user` + `master_saonew`, helper `isAdmin()`, `currentScId()`, `currentSaoCode()`
- [ ] 0.7 `app/Core/Csrf.php` — token helper
- [ ] 0.8 Layout + เมนูตาม role (โรงเรียน / เขต / สพฐ.)

### Phase 1 — ส่วนที่ 1: ประเมินพื้นที่สูงใหม่ (หลัก)
เป้าหมาย: flow ครบตาม PRD §6.1 ข้อ 1–7

**1A. เลือกโรงเรียน**
- [ ] 1.1 `SchoolController::select()` — dropdown สังกัด (`master_saonew`) → โรงเรียน (`school` กรอง `sao_code`)
- [ ] 1.2 ประกอบพารามิเตอร์เข้า map: `id, name, province, lat0/lng0` (โรงเรียน), `lat2/lng2` (ศาลากลางจาก `province`), `high` (province.high), `location_high`

**1B. แผนที่ + ความสูง**
- [ ] 1.3 `MapController::search()` + View — พอร์ตจาก `ssar_search.php` (Places + Elevation ณ จุด)
- [ ] 1.4 `MapController::saveLatLng()` — พอร์ต `map_uplatlng.php` → เขียน `school_location` ด้วย PDO
- [ ] 1.5 `MapController::elevation()` + View — พอร์ต `ssar_elevation.php` (Directions 512 จุด, หา highest, distance×1.0345, ตัดสินภูเขา/ราบ)
- [ ] 1.6 `MapController::saveElevation()` — พอร์ต `map_upcomment.php` → upsert `highland_eval` (PK `sc_id+acadyears`, **ใช้ `ACAD_YEAR` ไม่ฮาร์ดโค้ด**)
- [ ] 1.7 ตรรกะแยกทาง: พื้นราบ→จบ, ภูเขา→ไปแบบประเมิน

**1C. แบบประเมิน 16 ข้อ (11 แท็บ)**
- [ ] 1.8 `HighlandEvalController::edit()` — โหลดแถว + render 11 แท็บ (PRD §6.3)
- [ ] 1.9 แท็บข้อมูลทั่วไป + นักเรียน (auto stu_sum)
- [ ] 1.10 แท็บชาติพันธุ์ — CRUD `highland_eval_hilltrib` (เพิ่ม/ลบแถว) + คำนวณร้อยละ/จำนวนกลุ่ม
- [ ] 1.11 แท็บภูมิศาสตร์/คมนาคม/สาธารณูปโภค/นักเรียน/อื่นๆ (ข้อ 1–16) + อัปโหลด `*_refdoc`
- [ ] 1.12 `HighlandEvalController::save()` + validation + CSRF

**1D. คะแนน**
- [ ] 1.13 `ScoreService::calcHighland($values, $hilltribRows)` — สูตร PRD §6.5 (แหล่งความจริงเดียว)
- [ ] 1.14 Unit test สูตรคะแนน เทียบกับเรคคอร์ดจริงในระบบเดิม (เช่น sc_id 1063020130)

**1E. รับรอง + พิมพ์**
- [ ] 1.15 `SaoController` — เขตรับรองผล (`sao_proved2569`, ความเห็น), สพฐ. (`spt_commit`)
- [ ] 1.16 `PdfService::highlandEval()` — พอร์ต `mpdf/index.php`
- [ ] 1.17 ล็อกแก้ไขหลังรับรอง (ตาม §13 ข้อ 7)

### Phase 2 — ส่วนที่ 3+4: รับรองการคงอยู่
เป้าหมาย: PRD §8 (ทำได้เลย ไม่ต้องรอเกาะเกณฑ์ใหม่)
- [ ] 2.1 migration: ฟิลด์/ตารางรับรองปี 2569 (ตาม §13 ข้อ 3)
- [ ] 2.2 import รายชื่อตั้งต้นจาก xlsx 2566 (สูง + เกาะ) → ตารางรายชื่อ/สถานะ
- [ ] 2.3 `HighlandConfirmController` + `IslandConfirmController` — list/filter/ยืนยัน
- [ ] 2.4 หน้าโรงเรียนยืนยัน (opened / merged + merge_status)
- [ ] 2.5 หน้าเขตรับรอง + dashboard สพฐ.

### Phase 3 — ส่วนที่ 2: ประเมินพื้นที่เกาะใหม่ (รอเกณฑ์)
- [ ] 3.1 รับเอกสารเกณฑ์เกาะ → เติม PRD §7
- [ ] 3.2 `IslandEvalController` + `ScoreService::calcIsland()` (reuse โครงส่วนที่ 1)
- [ ] 3.3 PDF เกาะ

### Phase 4 — Hardening
- [ ] 4.1 ตรวจ SQL injection / CSRF / validation ครบ
- [ ] 4.2 จำกัด Google API key (referrer), ย้าย secret ออกจากโค้ด
- [ ] 4.3 ทดสอบ end-to-end + ทดสอบบนมือถือ
- [ ] 4.4 คู่มือผู้ใช้ (โรงเรียน/เขต)

---

## ลำดับความสำคัญ (แนะนำ)
1. **Phase 0 → Phase 1** (ส่วนที่ 1 ครบ) — เป็นแกนกลาง พิสูจน์สถาปัตยกรรม
2. **Phase 2** (ส่วนที่ 3–4) — ทำได้ขนานไปได้ ใช้ข้อมูลพร้อม
3. **Phase 3** (ส่วนที่ 2) — เมื่อได้เกณฑ์เกาะ
4. **Phase 4** ตลอดทาง

## Definition of Done (ต่อ task)
- ใช้ PDO prepared statements เท่านั้น
- ไม่มีปี/secret ฮาร์ดโค้ด (อยู่ใน config)
- คะแนน/ตรรกะ ตรงกับ PRD §6.5 และเทียบผลกับระบบเดิมได้
- ผ่าน validation ฝั่ง server + CSRF

## ความเสี่ยงหลัก
- **เกณฑ์เกาะยังไม่มี** → กันงานส่วนที่ 2 ออกเป็น Phase 3
- **Google API quota/billing** → ตรวจคีย์ + โควตาก่อนเริ่ม Phase 1B
- **คุณภาพข้อมูล `province.high`/`lat`/`lng`** → ตรวจครบทุกจังหวัดเป้าหมาย 22 จังหวัด (PRD ข้อมูลนิยาม)
- **PK `(sc_id, acadyears)`** ต้องสรุปค่า `acadyears` ให้ชัดก่อนเขียน migration
