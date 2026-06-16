-- ====================================================================
-- เพิ่มฟิลด์เก็บข้อมูลในหน้า "รับรองการคงอยู่" (school_confirm)
--   - ชื่อ-สกุล + เบอร์โทร ผู้อำนวยการ
--   - ชื่อ-สกุล + เบอร์โทร ผู้กรอกข้อมูล
--   - จำนวนนักเรียน แยก ชาย/หญิง/รวม
--   - จำนวนครู แยก ข้าราชการ/อัตราจ้าง/รองผอ./ผอ. + รวม
-- ใช้กับฐานข้อมูลที่ "มีตาราง school_confirm อยู่แล้ว" (เช่น local)
-- สำหรับติดตั้งใหม่ ฟิลด์เหล่านี้รวมอยู่ใน migration_2569_confirm.sql แล้ว
-- หมายเหตุ: MySQL 8 ไม่รองรับ ADD COLUMN IF NOT EXISTS — รันครั้งเดียวพอ
-- ====================================================================
ALTER TABLE `school_confirm`
  ADD COLUMN `director_name`   VARCHAR(150) NOT NULL DEFAULT '' AFTER `provinces`,
  ADD COLUMN `director_phone`  VARCHAR(30)  NOT NULL DEFAULT '' AFTER `director_name`,
  ADD COLUMN `informant_name`  VARCHAR(150) NOT NULL DEFAULT '' AFTER `director_phone`,
  ADD COLUMN `informant_phone` VARCHAR(30)  NOT NULL DEFAULT '' AFTER `informant_name`,
  ADD COLUMN `std_male`        INT NOT NULL DEFAULT 0 AFTER `informant_phone`,
  ADD COLUMN `std_female`      INT NOT NULL DEFAULT 0 AFTER `std_male`,
  ADD COLUMN `std_total`       INT NOT NULL DEFAULT 0 AFTER `std_female`,
  ADD COLUMN `tch_govt`        INT NOT NULL DEFAULT 0 AFTER `std_total`,
  ADD COLUMN `tch_hire`        INT NOT NULL DEFAULT 0 AFTER `tch_govt`,
  ADD COLUMN `tch_deputy`      INT NOT NULL DEFAULT 0 AFTER `tch_hire`,
  ADD COLUMN `tch_director`    INT NOT NULL DEFAULT 0 AFTER `tch_deputy`,
  ADD COLUMN `tch_total`       INT NOT NULL DEFAULT 0 AFTER `tch_director`;

-- ประเภทการเลิกสถานศึกษา (เมื่อเลือก "ยุบ/รวม/เลิก") + ชื่อโรงเรียนที่ไปเรียนรวม
ALTER TABLE `school_confirm`
  ADD COLUMN `close_type`     TINYINT      NOT NULL DEFAULT 0  AFTER `merged_to`,
  ADD COLUMN `merged_to_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `close_type`;

-- สถานะ "ส่งข้อมูล/ล็อก": โรงเรียนบันทึกร่างได้เรื่อย ๆ จนกด "ส่งข้อมูล" → ล็อก
-- เจ้าหน้าที่ สพท. ปลดล็อก (submitted=0) ให้โรงเรียนกลับมาแก้ไขได้
ALTER TABLE `school_confirm`
  ADD COLUMN `submitted`    TINYINT  NOT NULL DEFAULT 0 AFTER `school_confirm_at`,
  ADD COLUMN `submitted_at` DATETIME NULL              AFTER `submitted`;
