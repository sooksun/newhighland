-- =============================================================================
-- migration_2569_fix_missing_schools2.sql
-- วัตถุประสงค์ : เพิ่มโรงเรียนที่ผ่านการประเมิน highland ปี 2567 แล้ว
--               แต่ขาดหายจาก master_school (จึงไม่ถูก seed เข้า school_confirm 2569)
-- ฐานข้อมูล   : ssrainfo_ssra
-- อ้างอิง     : ส่วนเสริมของ migration_2569_fix_missing_schools.sql (6 โรงเรียนชุดแรก)
--               GAP query: highland_eval(2567) LEFT JOIN master_school -> NULL = 3 โรงเรียน
-- ผู้รายงาน   : ตรวจสอบ "บ้านป่าคาใหม่ สาขาบ้านป่าหวาย" หายจากระบบ
-- =============================================================================
--  หมายเหตุ scope: รหัส SMIS/OBEC ของทั้ง 3 โรงเรียน ยืนยันจากผู้ใช้แล้ว
--   • #1 บ้านป่าคาใหม่ สาขาบ้านป่าหวาย  สพป.ตาก เขต 2     SMIS 63020130 / OBEC 160271
--   • #2 บ้านพุตะเคียน                  สพป.ราชบุรี เขต 1  SMIS 70010062 / OBEC 480305
--   • #3 บ้านโป่งกระทิงบน               สพป.ราชบุรี เขต 1  SMIS 70010184 / OBEC 480339
--
--  สถานะ      : APPLIED บน local (Laragon) แล้ว 2026-06-24 (master_school +3, school_confirm +3)
--
--  ⚠️ คำเตือนสำคัญ (MyISAM):
--    master_school ENGINE = MyISAM → "ไม่รองรับ transaction" : START TRANSACTION/ROLLBACK
--    ใช้ไม่ได้กับตารางนี้ (เขียนแล้วเขียนเลย) และไม่มี UNIQUE key บน sc_id → INSERT IGNORE
--    "กันซ้ำไม่ได้". สคริปต์นี้จึงใช้ INSERT ... WHERE NOT EXISTS เพื่อให้รันซ้ำได้อย่างปลอดภัย
--    (idempotent). อย่าทดลองรันแบบ dry-run/rollback กับ master_school — แถวจะค้างในตารางจริง
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- =============================================================================
-- PRE-CHECK (คาดผล: 0 / 0)
-- =============================================================================

-- [A] ยังไม่มีใน master_school (expect 0)
SELECT COUNT(*) AS 'pre: sc_id ใน master_school (expect 0)'
FROM master_school
WHERE sc_id IN ('1063020130','1070480305','1070480339');

-- [B] ยังไม่มีใน school_confirm 2569 (expect 0)
SELECT COUNT(*) AS 'pre: sc_id ใน school_confirm 2569 (expect 0)'
FROM school_confirm
WHERE acadyears = 2569 AND sc_id IN (1063020130,1070480305,1070480339);

-- [C] ยืนยัน sao_id ปลายทางมีจริงใน master_sao (expect 2 rows: 126, 137)
SELECT sao_id, sao_name FROM master_sao WHERE sao_id IN (126, 137) ORDER BY sao_id;

-- =============================================================================
-- MAIN — ทุก INSERT เป็นแบบ idempotent (INSERT ... SELECT ... WHERE NOT EXISTS)
--        รันซ้ำได้โดยไม่เกิดแถวซ้ำ ทั้งบน master_school (MyISAM) และ school_confirm
--        *ไม่* ใช้ START TRANSACTION เพราะ master_school = MyISAM ไม่รองรับ
-- =============================================================================

-- ─── 1. master_school ───────────────────────────────────────────────────────
--  sao_code (INT) = sao_id ใน master_sao   |  remote=1 (พื้นที่สูง)
--  ตำบล(districts)/อำเภอ(amphures)/ผอ.(dir_name) อ้างอิงจาก highland_eval 2567

-- [TAK] บ้านป่าคาใหม่ สาขาบ้านป่าหวาย — สพป.ตาก เขต 2 (sao_id 126)
INSERT INTO master_school
    (sc_id, sc_smis, sc_obec, sao_code, sc_name, dir_name, address,
     districts, amphures, provinces, zipcodes, email, website, telephone,
     establish, status, remote, last_update)
SELECT '1063020130','63020130','160271',126,'บ้านป่าคาใหม่ สาขาบ้านป่าหวาย','นายอัครพงษ์  รักสีขาว','',
       'คีรีราษฎร์','พบพระ','ตาก','','','','','',1,1,''
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM master_school WHERE sc_id = '1063020130');

-- [RB-1] บ้านพุตะเคียน — สพป.ราชบุรี เขต 1 (sao_id 137)
INSERT INTO master_school
    (sc_id, sc_smis, sc_obec, sao_code, sc_name, dir_name, address,
     districts, amphures, provinces, zipcodes, email, website, telephone,
     establish, status, remote, last_update)
SELECT '1070480305','70010062','480305',137,'บ้านพุตะเคียน','นางสาวสุนิษา แสงแพร','',
       'แก้มอ้น','จอมบึง','ราชบุรี','','','','','',1,1,''
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM master_school WHERE sc_id = '1070480305');

-- [RB-2] บ้านโป่งกระทิงบน — สพป.ราชบุรี เขต 1 (sao_id 137)
INSERT INTO master_school
    (sc_id, sc_smis, sc_obec, sao_code, sc_name, dir_name, address,
     districts, amphures, provinces, zipcodes, email, website, telephone,
     establish, status, remote, last_update)
SELECT '1070480339','70010184','480339',137,'บ้านโป่งกระทิงบน','นายประเสริฐ  นาคีสินธุ์','',
       'บ้านบึง','บ้านคา','ราชบุรี','','','','','',1,1,''
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM master_school WHERE sc_id = '1070480339');

-- ─── 2. school_confirm 2569 (area_type 1 = พื้นที่สูง) ───────────────────────
--  provinces เก็บชื่อสังกัด ตามรูปแบบ roster เดิม | id = AUTO_INCREMENT
--  ฟิลด์ผู้บริหาร/จำนวน นร.-ครู เว้นว่างให้โรงเรียนกรอกตอนยืนยันการคงอยู่

INSERT INTO school_confirm
    (sc_id, acadyears, area_type, sc_name, provinces, sao_id, opened,
     school_confirmed, submitted, sao_status, spt_status)
SELECT 1063020130,2569,1,'บ้านป่าคาใหม่ สาขาบ้านป่าหวาย','สพป.ตาก เขต 2',126,NULL,0,0,0,0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM school_confirm WHERE sc_id=1063020130 AND acadyears=2569 AND area_type=1);

INSERT INTO school_confirm
    (sc_id, acadyears, area_type, sc_name, provinces, sao_id, opened,
     school_confirmed, submitted, sao_status, spt_status)
SELECT 1070480305,2569,1,'บ้านพุตะเคียน','สพป.ราชบุรี เขต 1',137,NULL,0,0,0,0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM school_confirm WHERE sc_id=1070480305 AND acadyears=2569 AND area_type=1);

INSERT INTO school_confirm
    (sc_id, acadyears, area_type, sc_name, provinces, sao_id, opened,
     school_confirmed, submitted, sao_status, spt_status)
SELECT 1070480339,2569,1,'บ้านโป่งกระทิงบน','สพป.ราชบุรี เขต 1',137,NULL,0,0,0,0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM school_confirm WHERE sc_id=1070480339 AND acadyears=2569 AND area_type=1);

-- =============================================================================
-- POST-CHECK
-- =============================================================================

-- [A] เพิ่มเข้า master_school แล้ว (expect 3 แถว)
SELECT sc_id, sc_smis, sc_obec, sao_code, sc_name, provinces, status, remote
FROM master_school
WHERE sc_id IN ('1063020130','1070480305','1070480339')
ORDER BY sc_id;

-- [B] JOIN ครบ 3 ตาราง — โรงเรียนพร้อมแสดงในรายการรับรองของเขต (expect 3 แถว)
SELECT c.sc_id, ms.sc_name, sa.sao_name, c.area_type, c.sao_id,
       h.sum_score AS score2567
FROM school_confirm c
JOIN master_school ms ON ms.sc_id = c.sc_id
JOIN master_sao    sa ON sa.sao_id = c.sao_id
LEFT JOIN highland_eval h ON h.sc_id = c.sc_id AND h.acadyears = 2567
WHERE c.acadyears = 2569
  AND c.sc_id IN (1063020130,1070480305,1070480339)
ORDER BY c.sc_id;
