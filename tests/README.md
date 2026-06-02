# Tests — newhighland

ชุดทดสอบแบบ **pure PHP** ไม่พึ่ง PHPUnit/composer (รันด้วย PHP ของ Laragon ได้เลย)

## วิธีรัน
```bash
php tests/run.php              # รันทั้งหมด (unit + integration)
php tests/run.php unit         # เฉพาะ unit (ไม่แตะฐานข้อมูล)
php tests/run.php integration  # เฉพาะ integration (ต้องมี MySQL)
```
ออก exit code `1` เมื่อมี assertion ล้มเหลว (ใช้กับ CI ได้)

ตัวอย่าง (Windows/Laragon):
```
"D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" tests\run.php
```

## โครงสร้าง
- `lib.php` — harness เล็ก (T::eq / T::close / T::true / T::false / T::skip + สรุปผล)
- `run.php` — autoload `App\` + รวมไฟล์เทสต์
- `unit/` — ตรรกะล้วน ไม่ใช้ DB
  - `ScoreServiceTest` — สูตรคะแนนพื้นที่สูง 16 ข้อ (normal/edge/cap/gate/div-by-zero/tiers) ตรวจกับเรคคอร์ดจริง 68.14
  - `IslandScoreServiceTest` — สูตรคะแนนพื้นที่เกาะ 15 ข้อ ตรวจกับเรคคอร์ดจริง 71.38
  - `RouterTest` — การจับคู่ route + `{param}`
  - `CsrfTest` — token/ตรวจสอบ
  - `UploadTest` — การปฏิเสธไฟล์ (นามสกุล/ขนาด/error)
- `integration/` — ผ่าน MySQL จริง
  - `HighlandEvalDbTest` — round-trip: upsertGeo → hilltrib → ScoreService → save → cert → SchoolConfirm
    **ความปลอดภัย:** ใช้ `sc_id=999999001`, `acadyears=9999` (sentinel ไม่มีจริง) และลบทิ้งใน `finally`
    + ยืนยัน teardown เป็น 0; จะ **SKIP** อัตโนมัติถ้าต่อ DB ไม่ได้

## ความเสี่ยงที่ยังไม่ครอบคลุม (untested risks)
- **เส้นทางสำเร็จของ Upload** (move_uploaded_file) ทดสอบได้เฉพาะผ่าน HTTP จริง — ครอบคลุมแล้วใน manual e2e (curl) แต่ยังไม่มี automated
- **PdfService** (mPDF/FPDF) — ต้องพึ่ง vendor/เทมเพลตภายนอก; ยืนยันด้วย e2e (ได้ `%PDF`) แต่ไม่ assert เนื้อหา PDF
- **Controllers/Map/Auth ผ่าน HTTP** (CSRF, redirect, สิทธิ์ตาม role) — ยืนยันด้วย manual curl e2e; ยังไม่มี HTTP integration อัตโนมัติ
- **Google Maps JS** (ปักหมุด/วัดความสูง) — ฝั่ง client ไม่มี automated test
