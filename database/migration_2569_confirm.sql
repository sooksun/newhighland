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

  -- ข้อมูลผู้บริหาร/ผู้กรอก + จำนวนนักเรียน/ครู (เก็บเพิ่มตอนยืนยันการคงอยู่)
  `director_name`    VARCHAR(150) NOT NULL DEFAULT '',      -- ชื่อ-สกุล ผู้อำนวยการโรงเรียน
  `director_phone`   VARCHAR(30)  NOT NULL DEFAULT '',      -- เบอร์โทร ผู้อำนวยการ
  `informant_name`   VARCHAR(150) NOT NULL DEFAULT '',      -- ชื่อ-สกุล ผู้กรอกข้อมูล
  `informant_phone`  VARCHAR(30)  NOT NULL DEFAULT '',      -- เบอร์โทร ผู้กรอกข้อมูล
  `std_male`         INT          NOT NULL DEFAULT 0,       -- นักเรียนชาย
  `std_female`       INT          NOT NULL DEFAULT 0,       -- นักเรียนหญิง
  `std_total`        INT          NOT NULL DEFAULT 0,       -- นักเรียนรวม (คำนวณ male+female)
  `tch_govt`         INT          NOT NULL DEFAULT 0,       -- ครู (ข้าราชการ)
  `tch_hire`         INT          NOT NULL DEFAULT 0,       -- ครู (อัตราจ้าง)
  `tch_deputy`       INT          NOT NULL DEFAULT 0,       -- รองผู้อำนวยการ
  `tch_director`     INT          NOT NULL DEFAULT 0,       -- ผู้อำนวยการ
  `tch_total`        INT          NOT NULL DEFAULT 0,       -- ครู/ผู้บริหารรวม (คำนวณ)

  `sao_id`           INT              NULL,                 -- master_sao.sao_id (จาก master_school.sao_code)

  -- โรงเรียนยืนยันการคงอยู่
  `opened`           TINYINT          NULL,                 -- 1=ยังเปิด/คงอยู่, 0=ยุบ/รวม/เลิก
  `merge_status`     INT              NULL,                 -- merge_status.id
  `merged_to`        BIGINT           NULL,                 -- sc_id ที่ไปยุบรวมด้วย (ถ้าทราบรหัส)
  `close_type`       TINYINT      NOT NULL DEFAULT 0,       -- ประเภทการเลิก: 1=ยุบ, 2=เลิก, 3=ไปเรียนรวม
  `merged_to_name`   VARCHAR(255) NOT NULL DEFAULT '',      -- ชื่อโรงเรียนที่ไปเรียนรวม (เมื่อ close_type=3)
  `school_note`      VARCHAR(255) NOT NULL DEFAULT '',
  `school_confirmed` TINYINT      NOT NULL DEFAULT 0,       -- 1=โรงเรียนบันทึกข้อมูลแล้ว (ร่าง/ส่ง)
  `school_confirm_at` DATETIME        NULL,
  `submitted`        TINYINT      NOT NULL DEFAULT 0,       -- 1=ส่งข้อมูลแล้ว (ล็อกการแก้ไข) 0=ร่าง/ปลดล็อก
  `submitted_at`     DATETIME         NULL,

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
