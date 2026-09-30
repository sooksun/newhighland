-- =============================================================================
-- migration_2569_add_central_admin_users.sql
-- วัตถุประสงค์ : เพิ่มบัญชีผู้ใช้ระดับ สพฐ. (ส่วนกลาง) อีก 10 บัญชี (admin01–admin10)
--               ใช้รหัสผ่านชุดเดียวกันชั่วคราว — ให้แต่ละคนเปลี่ยนรหัสผ่านเองทันทีหลัง login ครั้งแรก
--               ผ่านหน้า "จัดการผู้ใช้" (เมนู จัดการผู้ใช้ > แท็บ เขต/สพฐ. > แก้ไขบัญชีตัวเอง)
-- ฐานข้อมูล   : ssrainfo_ssra
-- หมายเหตุ    : - ไม่ต้องแก้ config 'admin_users' — code_name มีคำว่า "สพฐ" จึงได้สิทธิ์ admin
--                 โดยอัตโนมัติ (ดู Auth::attempt, app/Core/Auth.php)
--               - รหัสผ่านเก็บ plaintext ตามระบบเดิม (Auth::attempt เทียบตรง — PRD §10)
--               - รหัสผ่านเริ่มต้นด้านล่างเป็น "ค่าชั่วคราว" ใช้ร่วมกันทั้ง 10 บัญชี
--                 แจ้งผู้ใช้แต่ละคนให้เปลี่ยนทันทีหลัง login ครั้งแรก
-- ทดสอบแล้ว  : localhost (Laragon) 2026-07-02
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- ใช้ INSERT ... SELECT ... WHERE NOT EXISTS เพื่อกันซ้ำ (ตารางเก่าอาจไม่มี unique key) — รันซ้ำได้ปลอดภัย
INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000003', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin01', 'admin01', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin01' OR id = '00000003');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000004', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin02', 'admin02', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin02' OR id = '00000004');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000005', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin03', 'admin03', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin03' OR id = '00000005');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000006', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin04', 'admin04', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin04' OR id = '00000006');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000007', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin05', 'admin05', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin05' OR id = '00000007');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000008', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin06', 'admin06', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin06' OR id = '00000008');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000009', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin07', 'admin07', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin07' OR id = '00000009');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000010', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin08', 'admin08', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin08' OR id = '00000010');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000011', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin09', 'admin09', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin09' OR id = '00000011');

INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000012', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin10', 'admin10', 'ObecCentral#2569'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin10' OR id = '00000012');

-- ตรวจผล
SELECT id, code, code_name, user, password FROM master_saonew WHERE user LIKE 'admin%' ORDER BY id;
