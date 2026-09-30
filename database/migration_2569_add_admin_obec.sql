-- =============================================================================
-- migration_2569_add_admin_obec.sql
-- วัตถุประสงค์ : เพิ่มบัญชีผู้ใช้ "admin@obec" เป็น admin ระดับ สพฐ. (ส่วนกลาง)
-- ฐานข้อมูล   : ssrainfo_ssra
-- หมายเหตุ    : - ต้องเพิ่ม 'admin@obec' ใน config 'admin_users' ด้วย (ทำแล้วใน config/config.php)
--               - code_name มีคำว่า 'สพฐ' จึงได้สิทธิ์ admin โดยอัตโนมัติเช่นกัน (เผื่อไว้)
--               - รหัสผ่านเก็บ plaintext ตามระบบเดิม (Auth::attempt เทียบตรง) —
--                 แนะนำให้เปลี่ยนรหัสผ่านหลัง login ครั้งแรกผ่านหน้า "จัดการผู้ใช้"
-- อ้างอิง     : มิเรอร์จาก migration_2569_add_admin_bismee.sql (ใช้ id ถัดไป = 00000002)
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = '';

-- ใช้ INSERT ... SELECT ... WHERE NOT EXISTS เพื่อกันซ้ำ (ตารางเก่าอาจไม่มี unique key)
INSERT INTO master_saonew (id, code, code_name, user, password)
SELECT '00000002', 'สพฐ. (ส่วนกลาง)', 'สพฐ. (ส่วนกลาง) — admin@obec', 'admin@obec', 'p@obec'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM master_saonew WHERE user = 'admin@obec' OR id = '00000002');

-- ตรวจผล
SELECT id, code, code_name, user, password FROM master_saonew WHERE user = 'admin@obec';
