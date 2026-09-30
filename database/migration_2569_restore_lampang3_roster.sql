-- =============================================================================
-- migration_2569_restore_lampang3_roster.sql
-- วัตถุประสงค์ : คืน 2 โรงเรียน สพป.ลำปาง เขต 3 กลับเข้า roster "รับรองการคงอยู่"
--               (school_confirm, area_type=1 พื้นที่สูง, acadyears 2569) ให้เมนูฝั่ง
--               โรงเรียนแสดง "ยืนยันการคงอยู่" แทนที่จะแสดง "คัดกรองใหม่"
--   - บ้านดอนไชย 1052500371  (เรียนรวมตั้งแต่ 26/7/2559)
--   - บ้านทุ่ง    1052500360  (เรียนรวม 15/8/2568)
--
-- อาการ       : ผู้ใช้ระดับโรงเรียน login แล้วเห็นเมนู "ประเมินพื้นที่สูงใหม่" (คัดกรองใหม่)
--               และยืนยันการคงอยู่ไม่ได้ ← SchoolMenu::forSchool() คำนวณสดตอน login:
--               อยู่ใน roster → เมนู confirm ; ไม่อยู่ → (จังหวัดเข้าเกณฑ์) เมนูคัดกรองใหม่
--               แปลว่าบน production แถวทั้งสองใน school_confirm(area 1, 2569) "หายไป"
--               (เครื่อง dev ยังมีอยู่ id=304, id=28) — คืนแถวให้ครบ
-- ฐานข้อมูล   : ssrainfo_ssra  (school_confirm = InnoDB, มี UNIQUE uniq_sc(sc_id,acadyears,area_type))
-- ปลอดภัย     : idempotent — ON DUPLICATE KEY อัปเดตแค่ sao_id/sc_name/provinces
--               ไม่ทับข้อมูลที่โรงเรียนกรอก (opened/std_*/tch_*/school_confirmed ฯลฯ)
-- หลังรัน      : โรงเรียนทั้งสอง login ใหม่ → จะเห็นเมนู "ยืนยันการคงอยู่"
--               (ยังต้องมีคน login ฝั่งโรงเรียนมากรอก "เรียนรวม" หรือใช้ฟีเจอร์บันทึกแทนของเขต)
-- =============================================================================
SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- PRE-CHECK: สถานะปัจจุบันบน production (ก่อนแก้)
--   ถ้าผลออกมา "มี 2 แถว area_type=1 acadyears=2569 sao_id=103 อยู่แล้ว" → ปัญหาไม่ใช่แถวหาย
--   ให้บอกโรงเรียน logout/login ใหม่ (เมนูถูก cache ใน session ตอน login) แล้วตรวจซ้ำ
SELECT sc_id, id, area_type, acadyears, sao_id, sc_name, provinces,
       opened, close_type, school_confirmed, sao_status
FROM school_confirm
WHERE sc_id IN (1052500371, 1052500360)
ORDER BY sc_id, area_type, acadyears;

-- FIX: คืนแถว roster พื้นที่สูงปี 2569 (ถ้าหาย) + บังคับ sao_id=103 (สพป.ลำปาง เขต 3)
INSERT INTO school_confirm (sc_id, acadyears, area_type, sc_name, provinces, sao_id) VALUES
  (1052500371, 2569, 1, 'บ้านดอนไชย', 'สพป.ลำปาง เขต 3', 103),
  (1052500360, 2569, 1, 'บ้านทุ่ง',   'สพป.ลำปาง เขต 3', 103)
ON DUPLICATE KEY UPDATE
  sao_id    = VALUES(sao_id),
  sc_name   = VALUES(sc_name),
  provinces = VALUES(provinces);

-- POST-CHECK: ต้องเห็นครบ 2 แถว area_type=1 acadyears=2569 sao_id=103
SELECT sc_id, id, area_type, acadyears, sao_id, sc_name, provinces, school_confirmed
FROM school_confirm
WHERE sc_id IN (1052500371, 1052500360) AND area_type = 1 AND acadyears = 2569
ORDER BY sc_id;

-- ยอด roster พื้นที่สูงหลังคืน (ถ้าเดิมหาย 2 แถว จะเพิ่มจาก 1481 → 1483)
SELECT SUM(area_type=1) high, SUM(area_type=2) island, COUNT(*) total
FROM school_confirm WHERE acadyears = 2569;
