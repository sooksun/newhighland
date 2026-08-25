-- ====================================================================
-- เพิ่มฟิลด์ที่ยังขาดใน school_confirm (production)
-- ใช้ ADD COLUMN IF NOT EXISTS — ไม่ต้องการสิทธิ์ information_schema
-- รันได้ซ้ำโดยไม่ error (MariaDB 10.0+ / MySQL 8.0.32+)
-- ====================================================================
-- วิธีใช้ใน phpMyAdmin: วาง SQL นี้ทั้งหมดแล้วกด Go
--   (ใช้ delimiter ; ตามค่า default — ไม่ต้องเปลี่ยน delimiter)
-- ====================================================================

-- ---- profile fields ----
ALTER TABLE `school_confirm`
  ADD COLUMN IF NOT EXISTS `director_name`   VARCHAR(150) NOT NULL DEFAULT '' AFTER `provinces`,
  ADD COLUMN IF NOT EXISTS `director_phone`  VARCHAR(30)  NOT NULL DEFAULT '' AFTER `director_name`,
  ADD COLUMN IF NOT EXISTS `informant_name`  VARCHAR(150) NOT NULL DEFAULT '' AFTER `director_phone`,
  ADD COLUMN IF NOT EXISTS `informant_phone` VARCHAR(30)  NOT NULL DEFAULT '' AFTER `informant_name`,
  ADD COLUMN IF NOT EXISTS `std_male`        INT          NOT NULL DEFAULT 0  AFTER `informant_phone`,
  ADD COLUMN IF NOT EXISTS `std_female`      INT          NOT NULL DEFAULT 0  AFTER `std_male`,
  ADD COLUMN IF NOT EXISTS `std_total`       INT          NOT NULL DEFAULT 0  AFTER `std_female`,
  ADD COLUMN IF NOT EXISTS `tch_govt`        INT          NOT NULL DEFAULT 0  AFTER `std_total`,
  ADD COLUMN IF NOT EXISTS `tch_hire`        INT          NOT NULL DEFAULT 0  AFTER `tch_govt`,
  ADD COLUMN IF NOT EXISTS `tch_deputy`      INT          NOT NULL DEFAULT 0  AFTER `tch_hire`,
  ADD COLUMN IF NOT EXISTS `tch_director`    INT          NOT NULL DEFAULT 0  AFTER `tch_deputy`,
  ADD COLUMN IF NOT EXISTS `tch_total`       INT          NOT NULL DEFAULT 0  AFTER `tch_director`;

-- ---- close_type, merged_to_name ----
ALTER TABLE `school_confirm`
  ADD COLUMN IF NOT EXISTS `close_type`     TINYINT      NOT NULL DEFAULT 0  AFTER `merged_to`,
  ADD COLUMN IF NOT EXISTS `merged_to_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `close_type`;

-- ---- submitted, submitted_at ----
ALTER TABLE `school_confirm`
  ADD COLUMN IF NOT EXISTS `submitted`    TINYINT  NOT NULL DEFAULT 0 AFTER `school_confirm_at`,
  ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME     NULL           AFTER `submitted`;
