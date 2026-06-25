-- =============================================================================
-- migration_2569_report_indexes.sql
-- วัตถุประสงค์ : เพิ่ม index ที่จำเป็นต่อหน้า "รายงานสถิติ" (report) ให้ query ใช้ index
--               แทนการ full-scan ตารางใหญ่ (master_school ~30k, school_new ~28k)
-- รากของปัญหา : eval.sc_id = INT แต่ master_school.sc_id/school_new.sc_id = VARCHAR
--               → join ใช้ index ไม่ได้ → full scan ต่อทุก query (ดู DashboardStat ที่แก้คู่กัน)
-- ฐานข้อมูล   : ssrainfo_ssra
-- หมายเหตุ    : - master_school / master_sao / highland_eval_hilltrib = MyISAM (ADD INDEX ได้
--                 แต่ไม่มี transaction → *** สำรอง DB ก่อนรัน ***)
--               - MySQL 8.0.30 ไม่รองรับ ADD INDEX IF NOT EXISTS → ใช้ guard ผ่าน
--                 information_schema.statistics เพื่อให้รันซ้ำได้ (idempotent)
--               - master_sao ใช้ index ธรรมดา (ไม่ทำ PK/UNIQUE เพราะ sao_id อาจมีซ้ำ
--                 ตามที่ระบุใน migration_2569_fix_sao_id.sql)
-- =============================================================================
SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- PRE-CHECK: index ที่มีอยู่ก่อน (ใช้เทียบหลังรัน)
SELECT table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) cols
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('highland_eval','island_eval','highland_eval_hilltrib','master_school','master_sao')
GROUP BY table_name, index_name ORDER BY table_name, index_name;

-- helper macro (เขียนซ้ำต่อ index): เพิ่มเฉพาะเมื่อยังไม่มี
-- highland_eval -------------------------------------------------------------
SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='highland_eval' AND index_name='idx_acad_score');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE highland_eval ADD INDEX idx_acad_score (acadyears, sum_score)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='highland_eval' AND index_name='idx_sc_id');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE highland_eval ADD INDEX idx_sc_id (sc_id)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- island_eval ---------------------------------------------------------------
SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='island_eval' AND index_name='idx_acad_score');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE island_eval ADD INDEX idx_acad_score (acadyears, sum_score)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='island_eval' AND index_name='idx_sc_id');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE island_eval ADD INDEX idx_sc_id (sc_id)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- highland_eval_hilltrib ----------------------------------------------------
SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='highland_eval_hilltrib' AND index_name='idx_acad');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE highland_eval_hilltrib ADD INDEX idx_acad (acadyears)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='highland_eval_hilltrib' AND index_name='idx_sc_id');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE highland_eval_hilltrib ADD INDEX idx_sc_id (sc_id)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='highland_eval_hilltrib' AND index_name='idx_hilltrib');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE highland_eval_hilltrib ADD INDEX idx_hilltrib (hilltrib)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- master_school -------------------------------------------------------------
SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='master_school' AND index_name='idx_sao_code');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE master_school ADD INDEX idx_sao_code (sao_code)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- master_sao ----------------------------------------------------------------
SET @x:=(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='master_sao' AND index_name='idx_sao_id');
SET @s:=IF(@x>0,'SELECT 1','ALTER TABLE master_sao ADD INDEX idx_sao_id (sao_id)');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- POST-CHECK: index หลังรัน (ควรเห็น idx_* ครบ)
SELECT table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) cols
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('highland_eval','island_eval','highland_eval_hilltrib','master_school','master_sao')
GROUP BY table_name, index_name ORDER BY table_name, index_name;
