# newhighland — ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ (ปีงบประมาณ 2569)

ระบบเขียนใหม่ด้วย **PHP ล้วน + MySQL (PDO)** แทนระบบเดิม (PHPRunner) ที่ `../highland`
ใช้ฐานข้อมูลเดิม `ssrainfo_ssra` และบัญชี/รหัสผ่านเดิม

## เอกสาร
- [docs/PRD.md](docs/PRD.md) — ความต้องการระบบ (4 ส่วน, โมเดลข้อมูล, สูตรคะแนน)
- [docs/PLAN.md](docs/PLAN.md) — แผนพัฒนาเป็น Phase

## โครงสร้าง
```
index.php          front controller (web root = โฟลเดอร์นี้ ตามค่า Laragon)
.htaccess          rewrite -> index.php + ปิดกั้น app/ config/ database/ docs/
config/config.php  ตั้งค่า DB, Google API key, ปี acad_year
app/Core/          App, Db, Router, View, Request, Auth, Csrf, Flash
app/Controllers/   AuthController, HomeController (เพิ่มส่วน 1–4 ใน Phase ถัดไป)
app/Views/         layouts/, auth/, home/, errors/
assets/            css/js/img (เข้าถึงตรงได้)
```

## การทดสอบ
```bash
php tests/run.php          # unit + integration (ดู tests/README.md)
php tests/run.php unit     # เฉพาะ unit (ไม่แตะ DB)
```
102 assertions ครอบคลุมสูตรคะแนน (สูง/เกาะ), Router, Csrf, Upload และ round-trip ฐานข้อมูล

## การรัน
- **Laragon (Apache):** เข้า `http://newhighland.test/` หรือ `http://localhost/newhighland/`
- **PHP built-in (ทดสอบ):** `php -S 127.0.0.1:8099 index.php` แล้วเข้า `http://127.0.0.1:8099/`

## สถานะ
- ✅ **Phase 0** — โครงรันได้ + ต่อ DB + login (โรงเรียน/เขต/สพฐ.) + dashboard ตาม role
- ✅ **Phase 1** — ส่วนที่ 1 ครบ flow: เลือกสังกัด/โรงเรียน → ปักหมุด → วัดความสูง (ตัดสินภูเขา/ราบ)
  → แบบประเมิน 16 ข้อ (11 แท็บ) → คิดคะแนน (ScoreService ตรวจตรงกับเรคคอร์ดจริง 68.14) → พิมพ์
  - ทดสอบ end-to-end ผ่าน: บันทึกลง DB ได้ sum_score/highland_type ถูกต้อง
- ✅ **ส่วนที่ 3 + 4 (รับรองการคงอยู่)** — ตาราง `school_confirm` + import roster 2566 (พื้นที่สูง 1,481 / เกาะ 123)
  → โรงเรียนยืนยันคงอยู่/ยุบรวม → เขตรับรอง → สพฐ. เห็นชอบ (กรอง/สถิติ)
- ✅ **ส่วนที่ 1 เพิ่ม** — แท็บรับรอง สพท./สพฐ. (role-gated) + อัปโหลดไฟล์แนบ (refdoc ข้อ 4,7,8,9,10,15,16)
- ✅ **ส่วนที่ 2 (ประเมินพื้นที่เกาะใหม่)** — เลือกโรงเรียน → แบบประเมิน 15 ข้อ (gate "เป็นเกาะ" + ที่ตั้ง/คมนาคม/สาธารณูปโภค/อื่นๆ + ครู-บุคลากร) → คะแนน (เต็ม 100) → แท็บรับรอง สพท./สพฐ. + อัปโหลด → พิมพ์
  - ScoreService ตรวจตรงกับเรคคอร์ดจริง (sc 1091560035 = 71.38 / type 3)
- ✅ **PDF (พิมพ์จริง)** — `PdfService::highlandEval` (mPDF overlay 4 หน้า บน template.pdf) และ `islandEval`
  (FPDF overlay 3 หน้า บนภาพฟอร์ม, ฟอนต์ TIS-620) — ใช้ vendor/เทมเพลต/ฟอนต์เดิมผ่าน config `mpdf_path`/`mpdf_island_path`
  - `highland/print` และ `island/print` ออกเป็น PDF (application/pdf) แล้ว
- ⏭️ **ถัดไป** — migration เพิ่ม DEFAULT/UNIQUE key, dashboard สถิติภาพรวม สพฐ.

## ฐานข้อมูลที่เพิ่ม (database/)
- `migration_2569_confirm.sql` — ตาราง `school_confirm`
- `seed_confirm_2569.sql` — รายชื่อรับรองจาก roster 2566 (auto-generated)
- เพิ่ม index `idx_sc_id` บน `master_school(sc_id)` (เร่งการ JOIN/ค้นหา)

## โครงสร้างเพิ่มเติม (Phase 1)
```
app/Models/        MasterSao, MasterSchool, Province, Hilltrib, CriteriaOption, HighlandEval
app/Services/      ScoreService (คิดคะแนน 16 ข้อ), SchoolContext (รวมบริบทโรงเรียน)
app/Controllers/   SchoolController, MapController, HighlandEvalController
app/Views/         highland/{select,eval,print}, map/{search,elevation}
```

## หมายเหตุ
- `config/config.php` ตั้ง `acad_year = 2569` (ยืนยันค่ากับผู้เกี่ยวข้อง — ดู PRD §13.1)
- รหัสผ่านเดิมเป็น plaintext จึงเทียบตรงเพื่อความเข้ากันได้ (วางแผนย้าย hash ภายหลัง — PRD §10)
