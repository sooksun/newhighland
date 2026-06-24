<?php
/**
 * Central configuration for the newhighland system (ปีงบประมาณ 2569).
 * NOTE: keep this file OUT of the web root in production, or protect it via .htaccess
 * (the root .htaccess in this project denies direct web access to /config).
 */

// หา path ของไฟล์ PDF (mPDF/FPDF) ที่บันเดิลไว้ในโปรเจกต์
// เดินขึ้นจากโฟลเดอร์ของไฟล์ config นี้ แล้วลองหา vendor_pdf/<name> ก่อน ตามด้วย <name>
// ตรง root โปรเจกต์ (รองรับทั้งไฟล์ config อยู่ที่ config/ และ config/server/ และรองรับ
// ทั้งวางไว้ที่ newmain/vendor_pdf/mpdf หรือ newmain/mpdf) ; ถ้าไม่พบ fallback ไประบบเดิม
$pdfDir = static function (string $name): string {
    $d = __DIR__;
    for ($i = 0; $i < 5; $i++) {
        foreach ([$d . '/vendor_pdf/' . $name, $d . '/' . $name] as $cand) {
            if (is_dir($cand)) {
                return $cand;
            }
        }
        $d = dirname($d);
    }
    return 'D:/laragon/www/highland/' . $name; // fallback (dev)
};

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

    // ---- mPDF / FPDF (vendor + template + ฟอนต์) ----
    // ไฟล์ถูกบันเดิลไว้ใน /vendor_pdf ของโปรเจกต์ เพื่อให้พิมพ์ PDF ได้ทุกเครื่อง
    // (local + production) โดยไม่ผูกกับ path ของระบบเดิม highland อีกต่อไป
    // ใช้ path แบบ relative กับ root โปรเจกต์ (__DIR__ = โฟลเดอร์ config) ; ถ้าไม่พบ
    // จะ fallback ไปใช้ของระบบเดิม highland บนเครื่อง dev
    // ภายใน vendor_pdf/mpdf ต้องมี: vendor/autoload.php, template.pdf, chk.gif,
    // fonts/THSarabunNew*.ttf และ vendor/mpdf/mpdf/tmp/ (ต้องเขียนได้บน server)
    'mpdf_path'        => $pdfDir('mpdf'),
    'mpdf_island_path' => $pdfDir('island_pdf'),

    // ---- บัญชีระดับ สพฐ. (admin) ----
    // login จาก master_saonew ที่ user อยู่ในรายการนี้จะได้ role = admin
    // TODO (PRD §3): ยืนยันบัญชี สพฐ. จริง
    'admin_users' => [
        'tok',
        'bismee',
        'admin@obec',
    ],

    // ---- App ----
    'app_name'  => 'ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ',
    'app_env'   => 'local',         // local | production
    'debug'     => false,
];
