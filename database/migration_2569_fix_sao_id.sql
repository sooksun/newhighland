-- =============================================================================
-- migration_2569_fix_sao_id.sql
-- วัตถุประสงค์ : แก้ sao_id/sao_code ที่ผูกผิดเขต ทำให้ผู้ใช้ระดับเขต (สพท.) บางเขต
--               login แล้ว "เมนูไม่ปรากฏ" (saoAreas คืนค่าว่าง) และเปิดดูโรงเรียน
--               ของตนไม่ได้ (canAccessSchool = false)
-- ฐานข้อมูล   : ssrainfo_ssra
-- รากของปัญหา : master_school.sao_code/school_confirm.sao_id ของบางเขตมีช่องเหลื่อม
--               (off-by-one) จาก master_sao เช่น โรงเรียน "สพป.พะเยา เขต 2" ถูกเก็บ
--               sao_id=112 ซึ่งใน master_sao = "สพป.เชียงราย เขต 1"; ค่าที่ถูกคือ 111
--               ส่วน login map ชื่อเขต -> master_sao ได้ 111 จึง query ไม่เจอข้อมูล
-- convention  : master_school.sao_code (INT) = master_sao.sao_id ของเขตนั้น
--               (อ้างอิง migration_2569_fix_missing_schools.sql)
-- เขตที่กระทบ  : สพป.พะเยา เขต 2 (111), สพม.น่าน (227), สพป.เชียงใหม่ เขต 6 (98)
-- ทดสอบแล้ว  : localhost (Laragon) 2026-06-23
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- =============================================================================
-- PRE-CHECK : ก่อนรัน (คาดผลตามคอมเมนต์)
-- =============================================================================

-- [A] school_confirm 2569 ที่ provinces (=ชื่อเขต) ไม่ตรงกับ sao_id ที่ map ได้จาก master_sao
--     (expect รวม 54 แถว: พะเยาเขต2=42, สพม.น่าน=11, เชียงใหม่เขต6=1)
SELECT sc.provinces, sc.sao_id AS now_id, ms.sao_id AS correct_id, COUNT(*) AS rows_
FROM school_confirm sc
JOIN master_sao ms ON ms.sao_name = sc.provinces
WHERE sc.acadyears = 2569 AND sc.sao_id <> ms.sao_id
GROUP BY sc.provinces, sc.sao_id, ms.sao_id;

-- [B] master_school: ปลายทางที่ถูกควรยังว่าง/มีของถูกอยู่แล้ว (พะเยาเขต2=111 expect 0, น่าน=227 expect 0)
SELECT 111 AS sao_code, COUNT(*) AS schools FROM master_school WHERE sao_code = 111
UNION ALL SELECT 227, COUNT(*) FROM master_school WHERE sao_code = 227;

-- =============================================================================
-- MAIN : รันใน transaction เพื่อ rollback ได้หากผิดพลาด
-- =============================================================================

START TRANSACTION;

-- ─── 0. ให้แน่ใจว่ามี master_sao 'สพม.น่าน' (sao_id 227) ก่อนย้ายข้อมูลน่านมา ──
--     (บาง DB อาจยังไม่มี entry นี้ — ถ้ามีแล้ว INSERT IGNORE จะข้าม)
--     ถ้าไม่มี byName('สพม.น่าน') จะ resolve ไม่ได้ → เมนูไม่ขึ้น
-- (ใช้ INSERT ... SELECT ... WHERE NOT EXISTS แทน INSERT IGNORE เพราะ master_sao
--  ไม่มี unique key บน sao_id — INSERT IGNORE จะไม่กันซ้ำ ทำให้รันซ้ำได้ duplicate)
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region)
SELECT 227, 2, '1017', 'สพม.น่าน', '', '', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id = 227);

-- ─── 1. แก้ school_confirm.sao_id (คืนเมนูให้เขต) ────────────────────────────
--     scope ด้วยชื่อเขต (provinces) + ค่าเดิมที่ผิด → idempotent, ไม่แตะของถูก
--     (เช่น โรงเรียน เชียงราย เขต1 ที่อยู่ sao_id=112 ถูกแล้ว จะไม่ถูกแตะ)
UPDATE school_confirm SET sao_id = 111 WHERE acadyears = 2569 AND provinces = 'สพป.พะเยา เขต 2'   AND sao_id = 112;
UPDATE school_confirm SET sao_id = 227 WHERE acadyears = 2569 AND provinces = 'สพม.น่าน'           AND sao_id = 220;
UPDATE school_confirm SET sao_id = 98  WHERE acadyears = 2569 AND provinces = 'สพป.เชียงใหม่ เขต 6' AND sao_id = 94;
-- ราชบุรี เขต 1: 2 ร.ร. ถูกทิ้งไว้ที่ sao_id=1 (=สพป.กรุงเทพฯ ค่า default ผิด) → 137
-- (login map byName ไม่เจอเพราะ master_sao.sao_name มีช่องว่างเกิน จึงตก fallback byCode '7001'->137)
UPDATE school_confirm SET sao_id = 137 WHERE acadyears = 2569 AND provinces = 'สพป.ราชบุรี เขต 1' AND sao_id = 1;

-- ─── 2. แก้ master_school.sao_code (คืน canAccessSchool + กันเขตอื่นเห็นข้ามเขต) ─
--     พะเยา เขต 2: 141 ร.ร. จังหวัดพะเยา ถูกกองรวมใต้ 112 (เชียงราย เขต1) → 111
--     (เชียงราย เขต1 ที่เหลือใต้ 112 เป็นของถูก ไม่ถูกแตะ เพราะกรอง provinces='พะเยา')
UPDATE master_school SET sao_code = 111 WHERE sao_code = 112 AND provinces = 'พะเยา';

--     สพม.น่าน: 11 ร.ร. ในรายชื่อ ถูกเก็บใต้ 220 (สพม.เขต37 แพร่-น่าน) → 227
UPDATE master_school m
JOIN school_confirm sc ON sc.sc_id = m.sc_id
SET m.sao_code = 227
WHERE sc.acadyears = 2569 AND sc.provinces = 'สพม.น่าน' AND m.sao_code = 220;

--     เชียงใหม่ เขต 6: 1 ร.ร. (1050130534) ถูกเก็บใต้ 94 (เชียงใหม่ เขต2) → 98
UPDATE master_school SET sao_code = 98 WHERE sc_id = '1050130534' AND sao_code = 94;

--     ราชบุรี เขต 1: 2 ร.ร. ถูกเก็บใต้ 1 (กรุงเทพฯ) → 137
UPDATE master_school SET sao_code = 137 WHERE sc_id IN ('1070480317','1070480322') AND sao_code = 1;

COMMIT;

-- =============================================================================
-- POST-CHECK : หลังรัน
-- =============================================================================

-- [A] school_confirm 2569 ที่ยัง mismatch (expect = 0)
SELECT COUNT(*) AS 'post: school_confirm sao_id ยัง mismatch (expect 0)'
FROM school_confirm sc
JOIN master_sao ms ON ms.sao_name = sc.provinces
WHERE sc.acadyears = 2569 AND sc.sao_id <> ms.sao_id;

-- [B] saoAreas ของ 3 เขต ต้องมีข้อมูลแล้ว (expect: 111, 227, 98 มี area_type)
SELECT sao_id, GROUP_CONCAT(DISTINCT area_type ORDER BY area_type) AS areas, COUNT(*) AS rows_
FROM school_confirm
WHERE acadyears = 2569 AND sao_id IN (111, 227, 98)
GROUP BY sao_id;

-- [C] master_school: จำนวน ร.ร. ต่อเขตหลังย้าย (พะเยาเขต2=111, น่าน=227, เชียงใหม่เขต6=98)
SELECT m.sao_code, sa.sao_name, COUNT(*) AS schools
FROM master_school m JOIN master_sao sa ON sa.sao_id = m.sao_code
WHERE m.sao_code IN (111, 112, 227, 220, 98, 94)
GROUP BY m.sao_code, sa.sao_name
ORDER BY m.sao_code;
