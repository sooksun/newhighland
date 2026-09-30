-- =============================================================================
-- migration_2569_fix_sapm_sao.sql
-- วัตถุประสงค์ : ปรับ master_sao/data ให้ บัญชี สพม. (มัธยมศึกษา) แบบใหม่ (province-based)
--               login แล้ว resolve sao_id ได้ → เมนูปรากฏ
-- รากของปัญหา : ปฏิรูป สพม. 2564 (42 เขต -> 62 เขตจังหวัด) — master_sao ยังมีแต่ชื่อเก่า
--               "สพม.เขต N" (sao_id 184-225, sao_code='1017') แต่บัญชี login ใช้ชื่อใหม่
--               "สพม.<จังหวัด>" จึง byName ไม่เจอ + byCode ก็ไม่เจอ (sao_code dummy) -> NULL
-- วิธีแก้      : เพิ่ม master_sao ชื่อใหม่ต่อเขต (sao_id 233+) แล้วย้าย school_confirm/master_school
--               ของโรงเรียนในเขตนั้นมา (แยกตามจังหวัดจริงของโรงเรียน) — ตามแบบที่ทำกับ สพม.น่าน(227)
--               หมายเหตุ: master_school ย้ายเฉพาะ ร.ร.ในรายชื่อ (roster) เพราะ sao_id เก่าของ สพม.
--               ไม่มีบัญชี login ใดใช้ จึงไม่มีปัญหาเห็นข้ามเขต (ต่างจากกรณี สพป.พะเยา)
-- เขตที่กระทบ  : 15 เขต สพม. (sao_id ใหม่ 233-247)
-- ทดสอบแล้ว  : localhost (Laragon) 2026-06-23
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- PRE-CHECK: แถว สพม. ที่ยัง orphan (sao_id เก่าไม่มีบัญชี login resolve ถึง) — คาดรวม 51 แถว
SELECT sao_id, COUNT(*) rows_ FROM school_confirm WHERE acadyears=2569 AND sao_id IN (191,200,201,196,221,197,222,218,194,219,217,193,223,202) GROUP BY sao_id;

START TRANSACTION;

-- 1) เพิ่ม master_sao ชื่อใหม่
--    ใช้ INSERT ... SELECT ... WHERE NOT EXISTS (ไม่ใช่ INSERT IGNORE) เพราะ master_sao
--    ไม่มี unique key บน sao_id — INSERT IGNORE จะไม่กันซ้ำ ทำให้รันซ้ำเกิด duplicate
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 233, 2, '1017', 'สพม.กาญจนบุรี', '', '', 6 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=233);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 234, 2, '1017', 'สพม.จันทบุรี ตราด', '', '', 4 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=234);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 235, 2, '1017', 'สพม.ชลบุรี ระยอง', '', '', 4 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=235);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 236, 2, '1017', 'สพม.ตรัง กระบี่', '', '', 5 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=236);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 237, 2, '1017', 'สพม.ตาก', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=237);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 238, 2, '1017', 'สพม.พังงา ภูเก็ต ระนอง', '', '', 5 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=238);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 239, 2, '1017', 'สพม.พิษณุโลก อุตรดิตถ์', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=239);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 240, 2, '1017', 'สพม.ลำปาง ลำพูน', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=240);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 241, 2, '1017', 'สพม.สุราษฎร์ธานี ชุมพร', '', '', 5 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=241);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 242, 2, '1017', 'สพม.เชียงราย', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=242);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 243, 2, '1017', 'สพม.เชียงใหม่', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=243);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 244, 2, '1017', 'สพม.เพชรบุรี', '', '', 5 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=244);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 245, 2, '1017', 'สพม.เพชรบูรณ์', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=245);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 246, 2, '1017', 'สพม.เลย หนองบัวลำภู', '', '', 3 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=246);
INSERT INTO master_sao (sao_id, sao_group, sao_code, sao_name, lat, lng, region) SELECT 247, 2, '1017', 'สพม.แม่ฮ่องสอน', '', '', 2 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_sao WHERE sao_id=247);

-- 2) ย้าย school_confirm (เมนู) + master_school (canAccessSchool ของ ร.ร.ในรายชื่อ)
-- สพม.กาญจนบุรี  (เดิม sao_id 191 -> 233, 5 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=233
  WHERE sc.acadyears=2569 AND sc.sao_id IN (191) AND m.provinces IN ('กาญจนบุรี');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=233
  WHERE sc.acadyears=2569 AND sc.sao_id=233;

-- สพม.จันทบุรี ตราด  (เดิม sao_id 200 -> 234, 2 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=234
  WHERE sc.acadyears=2569 AND sc.sao_id IN (200) AND m.provinces IN ('จันทบุรี','ตราด');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=234
  WHERE sc.acadyears=2569 AND sc.sao_id=234;

-- สพม.ชลบุรี ระยอง  (เดิม sao_id 201 -> 235, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=235
  WHERE sc.acadyears=2569 AND sc.sao_id IN (201) AND m.provinces IN ('ชลบุรี','ระยอง');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=235
  WHERE sc.acadyears=2569 AND sc.sao_id=235;

-- สพม.ตรัง กระบี่  (เดิม sao_id 196 -> 236, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=236
  WHERE sc.acadyears=2569 AND sc.sao_id IN (196) AND m.provinces IN ('ตรัง','กระบี่');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=236
  WHERE sc.acadyears=2569 AND sc.sao_id=236;

-- สพม.ตาก  (เดิม sao_id 221 -> 237, 9 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=237
  WHERE sc.acadyears=2569 AND sc.sao_id IN (221) AND m.provinces IN ('ตาก');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=237
  WHERE sc.acadyears=2569 AND sc.sao_id=237;

-- สพม.พังงา ภูเก็ต ระนอง  (เดิม sao_id 197 -> 238, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=238
  WHERE sc.acadyears=2569 AND sc.sao_id IN (197) AND m.provinces IN ('พังงา','ภูเก็ต','ระนอง');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=238
  WHERE sc.acadyears=2569 AND sc.sao_id=238;

-- สพม.พิษณุโลก อุตรดิตถ์  (เดิม sao_id 222 -> 239, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=239
  WHERE sc.acadyears=2569 AND sc.sao_id IN (222) AND m.provinces IN ('พิษณุโลก','อุตรดิตถ์');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=239
  WHERE sc.acadyears=2569 AND sc.sao_id=239;

-- สพม.ลำปาง ลำพูน  (เดิม sao_id 218 -> 240, 4 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=240
  WHERE sc.acadyears=2569 AND sc.sao_id IN (218) AND m.provinces IN ('ลำปาง','ลำพูน');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=240
  WHERE sc.acadyears=2569 AND sc.sao_id=240;

-- สพม.สุราษฎร์ธานี ชุมพร  (เดิม sao_id 194 -> 241, 3 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=241
  WHERE sc.acadyears=2569 AND sc.sao_id IN (194) AND m.provinces IN ('สุราษฎร์ธานี','ชุมพร');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=241
  WHERE sc.acadyears=2569 AND sc.sao_id=241;

-- สพม.เชียงราย  (เดิม sao_id 219 -> 242, 3 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=242
  WHERE sc.acadyears=2569 AND sc.sao_id IN (219) AND m.provinces IN ('เชียงราย');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=242
  WHERE sc.acadyears=2569 AND sc.sao_id=242;

-- สพม.เชียงใหม่  (เดิม sao_id 217 -> 243, 9 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=243
  WHERE sc.acadyears=2569 AND sc.sao_id IN (217) AND m.provinces IN ('เชียงใหม่');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=243
  WHERE sc.acadyears=2569 AND sc.sao_id=243;

-- สพม.เพชรบุรี  (เดิม sao_id 193 -> 244, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=244
  WHERE sc.acadyears=2569 AND sc.sao_id IN (193) AND m.provinces IN ('เพชรบุรี');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=244
  WHERE sc.acadyears=2569 AND sc.sao_id=244;

-- สพม.เพชรบูรณ์  (เดิม sao_id 223 -> 245, 3 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=245
  WHERE sc.acadyears=2569 AND sc.sao_id IN (223) AND m.provinces IN ('เพชรบูรณ์');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=245
  WHERE sc.acadyears=2569 AND sc.sao_id=245;

-- สพม.เลย หนองบัวลำภู  (เดิม sao_id 202 -> 246, 1 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=246
  WHERE sc.acadyears=2569 AND sc.sao_id IN (202) AND m.provinces IN ('เลย','หนองบัวลำภู');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=246
  WHERE sc.acadyears=2569 AND sc.sao_id=246;

-- สพม.แม่ฮ่องสอน  (เดิม sao_id 217 -> 247, 7 ร.ร.)
UPDATE school_confirm sc JOIN master_school m ON m.sc_id=sc.sc_id SET sc.sao_id=247
  WHERE sc.acadyears=2569 AND sc.sao_id IN (217) AND m.provinces IN ('แม่ฮ่องสอน');
UPDATE master_school m JOIN school_confirm sc ON sc.sc_id=m.sc_id SET m.sao_code=247
  WHERE sc.acadyears=2569 AND sc.sao_id=247;

COMMIT;

-- POST-CHECK [A]: ต้องไม่เหลือ สพม. orphan (expect 0)
-- (ทุก sao_id ใหม่ 233-247 ควรมีข้อมูล)
SELECT sao_id, COUNT(*) rows_ FROM school_confirm WHERE acadyears=2569 AND sao_id BETWEEN 233 AND 247 GROUP BY sao_id ORDER BY sao_id;
-- POST-CHECK [B]: master_sao ใหม่ครบ
SELECT sao_id, sao_name FROM master_sao WHERE sao_id BETWEEN 233 AND 247 ORDER BY sao_id;
