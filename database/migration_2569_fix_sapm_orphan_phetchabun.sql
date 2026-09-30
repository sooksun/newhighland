-- =============================================================================
-- migration_2569_fix_sapm_orphan_phetchabun.sql
-- วัตถุประสงค์ : คืน ร.ร. สังกัด สพม.เพชรบูรณ์ ที่ "ตกค้าง" ให้เขตมองเห็น/เลือกประเมินใหม่ได้
-- ฐานข้อมูล   : ssrainfo_ssra
-- รากของปัญหา : migration_2569_fix_sapm_sao.sql ได้สร้าง master_sao 'สพม.เพชรบูรณ์'
--               (sao_id 245) และย้าย ร.ร. มาให้ "เฉพาะที่อยู่ใน roster (school_confirm)"
--               = 3 แห่ง  ส่วน ร.ร.จังหวัดเพชรบูรณ์ที่เหลืออีก 36 แห่ง ยังค้างใต้ sao_id เก่า
--               223 ('สพม.เขต 40(เพชรบูรณ์)')  เนื่องจาก UPDATE master_school เดิม JOIN
--               school_confirm จึงไม่แตะ ร.ร.นอก roster
-- อาการ       : บัญชี สพม.เพชรบูรณ์ (login u0067 -> byName -> sao_id 245) มองไม่เห็น ร.ร. 36 แห่ง
--               นี้ในทุกมุมมองเขต — เลือกประเมินใหม่ไม่ได้ (canAccessSchool ใช้
--               master_school.sao_code = 245) และเมื่อ ร.ร.เหล่านี้ประเมินเสร็จก็จะไม่ขึ้น
--               หน้า "รออนุมัติ" (highland/cert กรอง m.sao_code = 245)
-- convention  : master_school.sao_code (INT) = master_sao.sao_id ของเขตนั้น
-- ความปลอดภัย : old 223 = 'สพม.เขต 40' เป็นเขตจังหวัดเดียว (เพชรบูรณ์ล้วน 36/36) -> ย้ายทั้งก้อน
--               ปลอดภัย และไม่มีบัญชี login ใด resolve ไปที่ 223 จึงไม่เกิดการเห็นข้ามเขต
--               idempotent: รันซ้ำได้ (หลังย้ายจะไม่เหลือแถวที่ sao_code=223)
-- หมายเหตุ    : master_school = MyISAM (ไม่มี transaction/rollback) — คำสั่งเป็น UPDATE ล้วน
--               scope แคบ จึงไม่ใช้ START TRANSACTION
-- เครื่อง/วันที่ : localhost (Laragon) 2026-06-24
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- =============================================================================
-- PRE-CHECK (คาดผล: sao_code 223 = 36 แห่ง, 245 = 3 แห่ง)
-- =============================================================================
SELECT m.sao_code, TRIM(s.sao_name) AS sao_name, COUNT(*) AS schools
FROM master_school m JOIN master_sao s ON s.sao_id = m.sao_code
WHERE m.sao_code IN (223, 245)
GROUP BY m.sao_code, s.sao_name ORDER BY m.sao_code;

-- ยืนยันว่า 223 มีเฉพาะจังหวัดเพชรบูรณ์ (คาด: เพชรบูรณ์ 36, อื่น ๆ 0)
SELECT provinces, COUNT(*) AS n FROM master_school WHERE sao_code = 223 GROUP BY provinces;

-- =============================================================================
-- MAIN
-- =============================================================================

-- 1) ย้าย ร.ร. จังหวัดเพชรบูรณ์ ที่ตกค้างใต้ 223 -> 245 (คืน canAccessSchool ให้เขต)
UPDATE master_school SET sao_code = 245 WHERE sao_code = 223 AND provinces = 'เพชรบูรณ์';

-- 2) เผื่อมีแถว roster ที่ยังค้าง 223 ใน school_confirm (ปกติ = 0 เพราะย้ายไป 245 แล้ว) — idempotent
UPDATE school_confirm SET sao_id = 245
WHERE acadyears = 2569 AND provinces = 'สพม.เพชรบูรณ์' AND sao_id = 223;

-- =============================================================================
-- POST-CHECK (คาดผล: sao_code 223 = 0, 245 = 39)
-- =============================================================================
SELECT m.sao_code, TRIM(s.sao_name) AS sao_name, COUNT(*) AS schools
FROM master_school m JOIN master_sao s ON s.sao_id = m.sao_code
WHERE m.sao_code IN (223, 245)
GROUP BY m.sao_code, s.sao_name ORDER BY m.sao_code;
