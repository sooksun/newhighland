-- =============================================================================
-- migration_2569_add_admin_bismee.sql
-- วัตถุประสงค์ : เพิ่มบัญชีผู้ใช้ "bismee" เป็น admin ระดับ สพฐ. (ส่วนกลาง)
-- ฐานข้อมูล   : ssrainfo_ssra
-- หมายเหตุ    : - ต้องเพิ่ม 'bismee' ใน config 'admin_users' ด้วย (ทำแล้วใน config)
--               - code_name มีคำว่า 'สพฐ' จึงได้สิทธิ์ admin โดยอัตโนมัติเช่นกัน (เผื่อไว้)
--               - รหัสผ่านเก็บ plaintext ตามระบบเดิม (Auth::attempt เทียบตรง)
--               - รหัสผ่านด้านล่างเป็น "ค่าชั่วคราว" — ให้เปลี่ยนทันทีผ่านหน้า "จัดการผู้ใช้"
--                 (เข้าด้วยบัญชี admin เดิม เช่น tok แล้วไปแท็บ เขต/สพฐ.)
-- ทดสอบแล้ว  : localhost (Laragon) 2026-06-23
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- ใช้ INSERT ... SELECT ... WHERE NOT EXISTS เพื่อกันซ้ำ (ตารางเก่าอาจไม่มี unique key)
INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000001', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — bismee', 'bismee', 'Bismee@2569'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'bismee' OR id = '00000001');

-- ตรวจผล
SELECT id, code, code_name, user, password FROM master_saonew WHERE user = 'bismee';
