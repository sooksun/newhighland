-- =============================================================================
-- migration_2569_remove_3_schools_roster.sql
-- วัตถุประสงค์ : นำ 3 โรงเรียนออกจาก "กลุ่มที่ผ่านการคัดกรองพื้นที่สูงแล้ว" (school_confirm)
--               เพื่อให้กลับไป "คัดกรองใหม่" ได้ และให้ยอด roster ตรงกับบัญชี Excel 2566
--               (พื้นที่สูง 1,481 + เกาะ 123 = 1,604)
-- ที่มา        : 3 โรงเรียนนี้ถูกเพิ่มไว้โดย migration_2569_fix_missing_schools2.sql
--               (ผ่านประเมิน 2567 แต่หายจาก master_school) — ภายหลังตกลงให้ "คัดกรองใหม่"
-- ฐานข้อมูล   : ssrainfo_ssra (school_confirm = InnoDB)
-- หมายเหตุ    : - ลบเฉพาะ school_confirm (roster) เท่านั้น ; *คง* master_school (ทะเบียน)
--                 ไว้ เพื่อให้เมนู "ประเมินพื้นที่สูงใหม่" ใช้ sao_code/สิทธิ์เข้าถึงได้
--               - ทั้ง 3 ยัง school_confirmed=0 (ยังไม่กรอกยืนยัน) → ลบแล้วไม่เสียข้อมูลผู้ใช้
--               - ย้อนกลับได้ด้วยการรัน migration_2569_fix_missing_schools2.sql ซ้ำ (มี NOT EXISTS guard)
--               - หลังรัน: กด "ประมวลผลใหม่" ในหน้ารายงานสถิติ เพื่อรีเฟรช cache (ยอดจะเป็น 1,604)
-- =============================================================================
SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- PRE-CHECK: 3 แถวที่จะลบ + ยอด roster ก่อน (คาด high=1484, total=1607)
SELECT sc_id, area_type, sc_name, provinces, school_confirmed
FROM school_confirm
WHERE acadyears = 2569 AND area_type = 1
  AND sc_id IN (1063020130, 1070480305, 1070480339)
ORDER BY sc_id;

SELECT SUM(area_type=1) high, SUM(area_type=2) island, COUNT(*) total
FROM school_confirm WHERE acadyears = 2569;

-- DELETE: ออกจาก roster พื้นที่สูง ปี 2569
DELETE FROM school_confirm
WHERE acadyears = 2569 AND area_type = 1
  AND sc_id IN (1063020130, 1070480305, 1070480339);

-- POST-CHECK: ยอดหลังลบ (คาด high=1481, island=123, total=1604) + เหลือ 3 รายการ = 0
SELECT SUM(area_type=1) high, SUM(area_type=2) island, COUNT(*) total
FROM school_confirm WHERE acadyears = 2569;

SELECT COUNT(*) AS 'remaining_of_3 (expect 0)'
FROM school_confirm
WHERE acadyears = 2569 AND sc_id IN (1063020130, 1070480305, 1070480339);
