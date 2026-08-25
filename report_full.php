<?php
/**
 * report_full.php — ทางเข้าแบบไฟล์เดี่ยวสำหรับ "รายงานผลการประเมินฉบับเต็ม (HTML)"
 *
 * มีไว้เพื่อให้ deploy บน production ได้โดย **ไม่ต้องแก้ไฟล์เดิมของระบบเลย**
 * (index.php / .htaccess / View ไม่ต้องแตะ) — เพราะ .htaccess ส่งเฉพาะ request
 * ที่ไม่ตรงกับไฟล์จริงไป front controller ไฟล์นี้จึงถูกเรียกตรงได้
 *
 *   https://<โดเมน>/newmain/report_full.php                 → แสดงรายงาน
 *   https://<โดเมน>/newmain/report_full.php?download=1      → ดาวน์โหลดเป็น .html
 *   https://<โดเมน>/newmain/report_full.php?year=2569       → เจาะจงปีงบประมาณ
 *   https://<โดเมน>/newmain/report_full.php?year=all        → รวมทุกปี
 *
 * สิทธิ์: เฉพาะระดับ สพฐ. (admin) — ตรวจสิทธิ์ใน FullReportController
 * ถ้าติดตั้ง route `report/full` ใน index.php แล้ว จะใช้เส้นทางไหนก็ได้ (โค้ดชุดเดียวกัน)
 */

require __DIR__ . '/app/bootstrap.php';

(new App\Controllers\FullReportController())->index();
