<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;

/**
 * "รายงานฉบับเต็ม (HTML)" — สร้างรายงานผลการประเมินโรงเรียนพื้นที่ลักษณะพิเศษทุกมิติ
 * เป็นไฟล์ HTML เดี่ยว (กราฟเป็น inline SVG ทั้งหมด เปิดออฟไลน์ได้)
 *
 * ใช้โค้ดชุดเดียวกับสคริปต์ CLI tools/report/generate_report.php ผ่าน tools/report/build.php
 * มีไว้เพื่อให้สร้างรายงานจากข้อมูล production ได้ แม้เครื่อง production จะเข้า php CLI ไม่ได้
 *
 * เส้นทาง:
 *   GET report/full            → แสดงรายงานในเบราว์เซอร์
 *   GET report/full?download=1 → ดาวน์โหลดเป็นไฟล์ .html
 *   พารามิเตอร์ ?year=2569 | all | auto (ค่าเริ่มต้น = auto)
 *
 * เฉพาะระดับ สพฐ. (admin) เพราะรายงานครอบคลุมข้อมูลทุกเขตทั่วประเทศ
 */
class FullReportController
{
    public function index(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);

        $year = (string) Request::get('year', 'auto');
        if (!in_array($year, ['auto', 'all'], true)) {
            $year = (string) (int) $year;
            if ($year === '0') $year = 'auto';
        }

        require_once dirname(__DIR__, 2) . '/tools/report/build.php';

        // ส่ง config ที่ระบบใช้อยู่จริงเข้าไป (ไม่ต้องเดา path ไฟล์ config)
        $cfg = [
            'db'        => App::config('db'),
            'acad_year' => App::acadYear(),
            'app_name'  => App::config('app_name'),
        ];

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        try {
            $r = buildReport(Db::conn(), $cfg, $year, 'ค่าตั้งค่าที่ระบบใช้งานอยู่ (config ของเซิร์ฟเวอร์)');
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'สร้างรายงานไม่สำเร็จ: ' . $e->getMessage();
            return;
        }

        $name = 'report_special_area_' . ($year === 'all' ? 'all' : ($r['years'][0] ?? 'x')) . '.html';

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        if (Request::get('download')) {
            header('Content-Disposition: attachment; filename="' . $name . '"');
        }
        echo $r['html'];
    }
}
