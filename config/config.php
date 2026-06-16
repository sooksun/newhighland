<?php
/**
 * Central configuration for the newhighland system (ปีงบประมาณ 2569).
 * NOTE: keep this file OUT of the web root in production, or protect it via .htaccess
 * (the root .htaccess in this project denies direct web access to /config).
 */

return [

    // ---- Database (ฐานข้อมูลเดิม ssrainfo_ssra) ----
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'ssrainfo_ssra',
        'user'    => 'root',
        'pass'    => '',            // Laragon default root has no password locally
        'charset' => 'utf8mb4',
    ],
    
    // ---- ปีการศึกษา/ปีงบประมาณที่ใช้เป็นคีย์ acadyears ----
    // TODO (PRD §13.1): ยืนยันค่า acadyears ของรอบปี 2569 กับผู้เกี่ยวข้อง
    // ระบบเดิมใช้ 2567; ตั้ง default ไว้ที่ 2569 และเปลี่ยนได้ที่นี่ที่เดียว
    'acad_year' => 2569,

    // ---- Google Maps / Elevation / Directions / Places ----
    // TODO (PRD §13.5): พิจารณาออกคีย์ใหม่ + จำกัด HTTP referrer ใน Google Console
    'google_maps_key' => 'AIzaSyA-qO_KS9xBX1I_uDebo_3giC7zY5v4p5c',

    // ---- เกณฑ์ความสูง (เมตร) สำหรับตัดสินภูเขา/พื้นราบ ----
    'elevation_threshold' => 500,

    // ---- mPDF (ใช้ vendor + template + ฟอนต์เดิมจากระบบ highland เพื่อเลี่ยงสำเนา ~97MB) ----
    // ภายในต้องมี: vendor/autoload.php, template.pdf, chk.gif, fonts/THSarabunNew*.ttf, tmp/
    'mpdf_path'        => 'D:/laragon/www/highland/mpdf',
    'mpdf_island_path' => 'D:/laragon/www/highland/island_pdf',

    // ---- บัญชีระดับ สพฐ. (admin) ----
    // login จาก master_saonew ที่ user อยู่ในรายการนี้จะได้ role = admin
    // TODO (PRD §3): ยืนยันบัญชี สพฐ. จริง
    'admin_users' => [
        // 'tok',
    ],

    // ---- App ----
    'app_name'  => 'ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ',
    'app_env'   => 'local',         // local | production
    'debug'     => true,
];
