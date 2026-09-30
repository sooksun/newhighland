-- ย้อนกลับการเติมพิกัด 2 โรงเรียนที่ไม่มีพิกัด (รายชื่อคัดกรอง 2569)
USE newssra;
UPDATE school_location SET lat = '', lng = '' WHERE id = '1058420085';  -- บ้านรักไทย (เดิมมีแถวแต่ lat/lng ว่าง)
DELETE FROM school_location WHERE id = '1050130506';                    -- เพียงหลวง ๑ (บ้านท่าตอน) (เดิมไม่มีแถว)
