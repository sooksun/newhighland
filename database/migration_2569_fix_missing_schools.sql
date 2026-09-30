-- =============================================================================
-- migration_2569_fix_missing_schools.sql
-- วัตถุประสงค์ : เพิ่ม 6 โรงเรียนที่ขาดใน master_school
--               และแก้ไข sao_id = NULL ใน school_confirm ปีงบ 2569
-- ฐานข้อมูล   : ssrainfo_ssra
-- อ้างอิง     : Excel "รายชื่อโรงเรียนพื้นที่สูงในถิ่นทุรกันดาร 2566.xlsx"
--               ผลเปรียบเทียบ compare_schools_result.xlsx
-- ทดสอบแล้ว  : localhost (Laragon) 2026-06-18
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- =============================================================================
-- PRE-CHECK : ตรวจสอบสถานะก่อนรัน (ผลควรแสดง 6 / 6 / 0)
-- =============================================================================

-- [A] โรงเรียนที่จะเพิ่ม — ควรยังไม่มีใน master_school (expect = 0)
SELECT COUNT(*) AS 'pre: sc_id ใน master_school (expect 0)'
FROM master_school
WHERE sc_id IN ('1050131066','1057120742','1063022010',
                '1071020083','1071030105','1071030106');

-- [B] โรงเรียนใน school_confirm 2569 ที่ sao_id = NULL (expect = 6)
SELECT COUNT(*) AS 'pre: sao_id NULL ใน school_confirm 2569 (expect 6)'
FROM school_confirm
WHERE acadyears = 2569 AND sao_id IS NULL;

-- [C] ยืนยัน sao_id ที่จะใช้มีอยู่ใน master_sao (expect = 4 rows)
SELECT sao_id, sao_name
FROM master_sao
WHERE sao_id IN (95, 115, 141, 221)
ORDER BY sao_id;

-- =============================================================================
-- MAIN : รันภายใน transaction เพื่อ rollback ได้หากมีข้อผิดพลาด
-- =============================================================================

START TRANSACTION;

-- ─── 1. เพิ่ม 6 โรงเรียนเข้า master_school ──────────────────────────────────
--  INSERT IGNORE = ข้ามถ้า sc_id ซ้ำ (ป้องกันรัน 2 ครั้ง)
--  sc_smis ยังไม่ทราบ → '' (อัปเดตได้ภายหลังจากระบบ SMIS/OBEC)
--  sc_obec = ส่วนท้าย sc_id ไม่นับเลขศูนย์นำหน้า
--  sao_code (INT) = sao_id ใน master_sao ของเขตพื้นที่นั้น

INSERT IGNORE INTO master_school
    (sc_id,        sc_smis, sc_obec,  sao_code,
     sc_name,
     dir_name, address, districts, amphures, provinces,
     zipcodes, email, website, telephone, establish,
     status, remote, last_update)
VALUES
    -- 1. บ้านเด่นใหม่  |  สพป.เชียงใหม่ เขต 3  |  sao_id = 95
    ('1050131066', '', '131066', 95,
     'บ้านเด่นใหม่',
     '', '', '', '', 'เชียงใหม่',
     '', '', '', '', '',
     1, 0, ''),

    -- 2. สมถวิลจินตมัยบ้านห้วยแล้ง(ตชด.อนุสรณ์)  |  สพป.เชียงราย เขต 4  |  sao_id = 115
    ('1057120742', '', '120742', 115,
     'สมถวิลจินตมัยบ้านห้วยแล้ง(ตชด.อนุสรณ์)',
     '', '', '', '', 'เชียงราย',
     '', '', '', '', '',
     1, 0, ''),

    -- 3. โมโกรวิทยาคม  |  สพม.เขต 38 (สุโขทัย-ตาก)  |  sao_id = 221
    ('1063022010', '', '22010',  221,
     'โมโกรวิทยาคม',
     '', '', '', '', 'ตาก',
     '', '', '', '', '',
     1, 0, ''),

    -- 4. เพียงหลวง 3 (บ้านเหมืองแร่อีต่อง)  |  สพป.กาญจนบุรี เขต 3  |  sao_id = 141
    ('1071020083', '', '20083',  141,
     'เพียงหลวง 3 (บ้านเหมืองแร่อีต่อง) ในทูลกระหม่อมหญิงอุบลรัตนราชกัญญา สิริวัฒนาพรรณวดี',
     '', '', '', '', 'กาญจนบุรี',
     '', '', '', '', '',
     1, 0, ''),

    -- 5. บ้านกองม่องทะ สาขาบ้านไล่โว่  |  สพป.กาญจนบุรี เขต 3  |  sao_id = 141
    ('1071030105', '', '30105',  141,
     'บ้านกองม่องทะ สาขาบ้านไล่โว่',
     '', '', '', '', 'กาญจนบุรี',
     '', '', '', '', '',
     1, 0, ''),

    -- 6. บ้านกองม่องทะ สาขาบ้านสาละวะ  |  สพป.กาญจนบุรี เขต 3  |  sao_id = 141
    ('1071030106', '', '30106',  141,
     'บ้านกองม่องทะ สาขาบ้านสาละวะ',
     '', '', '', '', 'กาญจนบุรี',
     '', '', '', '', '',
     1, 0, '');

-- ─── 2. อัปเดต sao_id ใน school_confirm 2569 ─────────────────────────────────

UPDATE school_confirm SET sao_id = 95  WHERE sc_id = 1050131066 AND acadyears = 2569;
UPDATE school_confirm SET sao_id = 115 WHERE sc_id = 1057120742 AND acadyears = 2569;
UPDATE school_confirm SET sao_id = 221 WHERE sc_id = 1063022010 AND acadyears = 2569;
UPDATE school_confirm SET sao_id = 141 WHERE sc_id = 1071020083 AND acadyears = 2569;
UPDATE school_confirm SET sao_id = 141 WHERE sc_id = 1071030105 AND acadyears = 2569;
UPDATE school_confirm SET sao_id = 141 WHERE sc_id = 1071030106 AND acadyears = 2569;

COMMIT;

-- =============================================================================
-- POST-CHECK : ตรวจสอบผลหลังรัน (ผลควรแสดง 6 / 0 / 6)
-- =============================================================================

-- [A] โรงเรียนที่เพิ่มแล้วใน master_school (expect = 6)
SELECT sc_id, sc_name, provinces,
       sao_code,
       (SELECT sao_name FROM master_sao WHERE sao_id = m.sao_code) AS sao_name
FROM master_school m
WHERE sc_id IN ('1050131066','1057120742','1063022010',
                '1071020083','1071030105','1071030106')
ORDER BY sc_id;

-- [B] sao_id NULL ที่เหลือใน school_confirm 2569 (expect = 0)
SELECT COUNT(*) AS 'post: sao_id NULL ใน school_confirm 2569 (expect 0)'
FROM school_confirm
WHERE acadyears = 2569 AND sao_id IS NULL;

-- [C] ยืนยัน JOIN ครบทั้ง 3 ตาราง (expect = 6 rows)
SELECT sc.sc_id, ms.sc_name, ms.provinces, sa.sao_name, sc.sao_id
FROM school_confirm sc
JOIN master_school  ms ON ms.sc_id   = sc.sc_id
JOIN master_sao     sa ON sa.sao_id  = sc.sao_id
WHERE sc.sc_id IN (1050131066,1057120742,1063022010,
                   1071020083,1071030105,1071030106)
  AND sc.acadyears = 2569
ORDER BY sc.sc_id;

-- =============================================================================
-- แก้ไข sc_id 1050131064: จังหวัดผิด (ตาก→เชียงใหม่) และชื่อย่อ
-- ยืนยันจาก: sao_code=94=สพป.เชียงใหม่เขต2, sc_id prefix 1050, Excel สังกัด สพป.เชียงใหม่เขต2
-- =============================================================================

UPDATE master_school
SET provinces = 'เชียงใหม่',
    sc_name   = 'รัปปาปอร์ต (ตชด.บำรุงที่ 92)'
WHERE sc_id = '1050131064';
