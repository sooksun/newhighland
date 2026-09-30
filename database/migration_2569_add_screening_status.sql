-- ปีงบประมาณ 2569: ตารางสถานะเปิด/ปิดระบบคัดกรอง (สวิตช์เดียว ทั้งระบบ)
-- ใช้แถวเดียว (id=1) เก็บสถานะ + ข้อความแจ้งตอนปิด + ผู้แก้ไขล่าสุด
CREATE TABLE IF NOT EXISTS `screening_status` (
  `id` TINYINT UNSIGNED NOT NULL,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `closed_message` VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` VARCHAR(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `screening_status` (`id`, `is_open`, `closed_message`)
VALUES (1, 1, '')
ON DUPLICATE KEY UPDATE `id` = `id`;
