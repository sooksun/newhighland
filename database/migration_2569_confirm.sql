-- ====================================================================
-- ตารางรับรองการคงอยู่ของโรงเรียนพื้นที่พิเศษ (ส่วนที่ 3 = พื้นที่สูง, ส่วนที่ 4 = พื้นที่เกาะ)
-- ปีงบประมาณ 2569 — ออกแบบใหม่แทนการตั้งชื่อฟิลด์ผูกปี (PRD §5.4, §13.3)
-- area_type: 1 = พื้นที่สูง (highland), 2 = พื้นที่เกาะ (island)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `school_confirm` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `sc_id`            BIGINT       NOT NULL,
  `acadyears`        INT          NOT NULL,
  `area_type`        TINYINT      NOT NULL,                 -- 1=พื้นที่สูง 2=พื้นที่เกาะ
  `sc_name`          VARCHAR(255) NOT NULL DEFAULT '',
  `provinces`        VARCHAR(125) NOT NULL DEFAULT '',
  `sao_id`           INT              NULL,                 -- master_sao.sao_id (จาก master_school.sao_code)

  -- โรงเรียนยืนยันการคงอยู่
  `opened`           TINYINT          NULL,                 -- 1=ยังเปิด/คงอยู่, 0=ยุบ/รวม/เลิก
  `merge_status`     INT              NULL,                 -- merge_status.id
  `merged_to`        BIGINT           NULL,                 -- sc_id ที่ไปยุบรวมด้วย
  `school_note`      VARCHAR(255) NOT NULL DEFAULT '',
  `school_confirmed` TINYINT      NOT NULL DEFAULT 0,       -- 1=โรงเรียนยืนยันแล้ว
  `school_confirm_at` DATETIME        NULL,

  -- เขตพื้นที่ (สพท.) รับรอง
  `sao_status`       TINYINT      NOT NULL DEFAULT 0,       -- 0=รอ, 1=รับรอง, 2=ไม่รับรอง
  `sao_comment`      VARCHAR(255) NOT NULL DEFAULT '',
  `sao_at`           DATETIME         NULL,

  -- สพฐ.
  `spt_status`       TINYINT      NOT NULL DEFAULT 0,
  `spt_comment`      VARCHAR(255) NOT NULL DEFAULT '',
  `spt_at`           DATETIME         NULL,

  UNIQUE KEY `uniq_sc` (`sc_id`, `acadyears`, `area_type`),
  KEY `idx_sao`  (`sao_id`, `area_type`, `acadyears`),
  KEY `idx_area` (`area_type`, `acadyears`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
