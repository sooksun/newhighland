-- =============================================================================
-- migration_2569_fix_orphan_sao_eval.sql
-- วัตถุประสงค์ : แก้โรงเรียนที่ "ประเมินแล้ว" (highland_eval / island_eval ปี 2569)
--               แต่หน้า "รายงานสถิติ" แสดงเป็น "(ไม่ระบุเขต) sao_code=NULL"
-- รากของปัญหา : master_school.sao_code = NULL/0  หรือ  ไม่มีแถวใน master_school เลย
--               (คนละเคสกับ "กรุงเทพฯ มี ร.ร.ราชบุรี" ซึ่ง sao_code=1 ผิด —
--                เคสนั้นใช้ migration_2569_fix_sao_id.sql)
-- วิธีแก้      : map school_new.sao (ชื่อเขตแบบข้อความ) -> master_sao.sao_name -> sao_id
--               (1) ถ้ามีแถว master_school แต่ sao_code ว่าง  -> UPDATE
--               (2) ถ้าไม่มีแถวใน master_school              -> INSERT จาก school_new
-- convention  : master_school.sao_code (INT) = master_sao.sao_id
-- ปลอดภัย     : idempotent, scope เฉพาะ ร.ร.ที่ประเมินปี 2569 + sao_code ว่าง/ไม่มี
--               master_school = MyISAM (ไม่มี transaction/rollback) -> *** สำรอง DB ก่อนรัน ***
-- ลำดับ       : รัน migration_2569_fix_sao_id.sql (กรุงเทพ/พะเยา/น่าน/ราชบุรี) ก่อน แล้วค่อยไฟล์นี้
-- ผลคาด       : orphan ลดเหลือเฉพาะ ร.ร.ที่ school_new.sao ไม่ match master_sao
--               (เช่นชื่อ สพม. แบบใหม่) -> แก้ด้วย migration_2569_fix_sapm_orphan_nationwide.sql
--               หรือแก้มือ (ดู POST-CHECK [B])
-- =============================================================================
SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- -----------------------------------------------------------------------------
-- PRE-CHECK : รายชื่อ ร.ร. "ไม่ระบุเขต" (รหัส + ชื่อ + จังหวัด + เขตที่ควรเป็น)
--   เก็บผลลัพธ์ไว้เป็นหลักฐานก่อนแก้ (นี่คือรายการที่ผู้ใช้ขอ "แจ้งรหัส+รายชื่อ")
-- -----------------------------------------------------------------------------
SELECT x.sc_id,
       COALESCE(NULLIF(x.sc_names,''), m.sc_name, n.sc_name, '(ไม่มีชื่อ)') AS school_name,
       COALESCE(NULLIF(n.province,''), NULLIF(x.provinces,''), '')          AS province,
       n.sao                                                                AS sao_text,
       sug.sao_id   AS suggest_sao_id,
       sug.sao_name AS suggest_sao_name,
       CASE WHEN m.sc_id   IS NULL              THEN 'ไม่มีใน master_school'
            WHEN m.sao_code IS NULL OR m.sao_code=0 THEN 'sao_code ว่าง/0'
            ELSE 'sao_code ไม่ตรง master_sao' END AS problem
FROM (
    SELECT sc_id, sc_names, provinces FROM highland_eval WHERE acadyears=2569 AND sum_score IS NOT NULL
    UNION
    SELECT sc_id, sc_names, provinces FROM island_eval   WHERE acadyears=2569 AND sum_score IS NOT NULL
) x
LEFT JOIN master_school m  ON m.sc_id  = x.sc_id
LEFT JOIN master_sao    ms ON ms.sao_id = m.sao_code
LEFT JOIN school_new    n  ON n.sc_id  = x.sc_id
LEFT JOIN master_sao   sug ON REPLACE(TRIM(sug.sao_name),' ','') = REPLACE(TRIM(n.sao),' ','')
WHERE (m.sc_id IS NULL OR m.sao_code IS NULL OR m.sao_code=0 OR ms.sao_id IS NULL)
ORDER BY province, x.sc_id;

-- -----------------------------------------------------------------------------
-- FIX 1 : มีแถว master_school แต่ sao_code ว่าง/0  ->  เซตจาก school_new.sao
-- -----------------------------------------------------------------------------
UPDATE master_school m
JOIN school_new n ON n.sc_id = m.sc_id
JOIN master_sao ms ON REPLACE(TRIM(ms.sao_name),' ','') = REPLACE(TRIM(n.sao),' ','')
SET m.sao_code = ms.sao_id
WHERE (m.sao_code IS NULL OR m.sao_code = 0)
  AND m.sc_id IN (
      SELECT sc_id FROM highland_eval WHERE acadyears=2569 AND sum_score IS NOT NULL
      UNION
      SELECT sc_id FROM island_eval   WHERE acadyears=2569 AND sum_score IS NOT NULL
  );

-- -----------------------------------------------------------------------------
-- FIX 2 : ไม่มีแถวใน master_school  ->  INSERT จาก school_new (เซต sao_code ที่ถูก)
-- -----------------------------------------------------------------------------
INSERT INTO master_school (sc_id, sc_name, sao_code, provinces, status)
SELECT n.sc_id, n.sc_name, ms.sao_id, n.province, 1
FROM school_new n
JOIN master_sao ms ON REPLACE(TRIM(ms.sao_name),' ','') = REPLACE(TRIM(n.sao),' ','')
LEFT JOIN master_school m ON m.sc_id = n.sc_id
WHERE m.sc_id IS NULL
  AND n.sc_id IN (
      SELECT sc_id FROM highland_eval WHERE acadyears=2569 AND sum_score IS NOT NULL
      UNION
      SELECT sc_id FROM island_eval   WHERE acadyears=2569 AND sum_score IS NOT NULL
  );

-- -----------------------------------------------------------------------------
-- POST-CHECK
-- -----------------------------------------------------------------------------
-- [A] orphan ที่เหลือ (คาดลดลง; เหลือเฉพาะที่ school_new.sao ไม่ match master_sao)
SELECT COUNT(*) AS orphan_remaining
FROM (
    SELECT sc_id FROM highland_eval WHERE acadyears=2569 AND sum_score IS NOT NULL
    UNION
    SELECT sc_id FROM island_eval   WHERE acadyears=2569 AND sum_score IS NOT NULL
) x
LEFT JOIN master_school m  ON m.sc_id  = x.sc_id
LEFT JOIN master_sao    ms ON ms.sao_id = m.sao_code
WHERE (m.sc_id IS NULL OR m.sao_code IS NULL OR m.sao_code=0 OR ms.sao_id IS NULL);

-- [B] ร.ร.ที่ยังแก้อัตโนมัติไม่ได้ (school_new.sao ไม่ตรงชื่อใน master_sao) -> ต้องแก้มือ
SELECT x.sc_id,
       COALESCE(NULLIF(x.sc_names,''), n.sc_name, '(ไม่มีชื่อ)') AS school_name,
       n.province, n.sao AS sao_text
FROM (
    SELECT sc_id, sc_names FROM highland_eval WHERE acadyears=2569 AND sum_score IS NOT NULL
    UNION
    SELECT sc_id, sc_names FROM island_eval   WHERE acadyears=2569 AND sum_score IS NOT NULL
) x
LEFT JOIN school_new  n  ON n.sc_id = x.sc_id
LEFT JOIN master_school m ON m.sc_id = x.sc_id
LEFT JOIN master_sao   ms ON ms.sao_id = m.sao_code
LEFT JOIN master_sao  sug ON REPLACE(TRIM(sug.sao_name),' ','') = REPLACE(TRIM(n.sao),' ','')
WHERE (m.sc_id IS NULL OR m.sao_code IS NULL OR m.sao_code=0 OR ms.sao_id IS NULL)
  AND sug.sao_id IS NULL
ORDER BY n.province, x.sc_id;
