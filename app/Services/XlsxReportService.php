<?php
namespace App\Services;

use App\Controllers\HighlandEvalController;
use App\Controllers\IslandEvalController;
use App\Core\App;
use App\Models\HighlandEval;
use App\Models\IslandEval;
use App\Models\SchoolConfirm;

/**
 * ส่งออกรายงานการคัดกรอง (ประเมินพื้นที่สูง/เกาะ) + รับรองการคงอยู่ เป็น Excel (.xlsx) หลายชีต
 * ใช้ PHPExcel รุ่นเก่า (บันเดิลไว้ที่ vendor_pdf/PHPExcel — ดู 'phpexcel_path' ใน config)
 * เพราะโปรเจกต์นี้ไม่มี Composer (เหมือนแพทเทิร์น mPDF ใน PdfService) — บน PHP 8.1 ไลบรารีนี้ยิง
 * deprecated-notice จำนวนมากแต่ทำงานถูกต้อง จึงต้อง suppress error_reporting ระหว่างสร้างไฟล์
 * เพื่อไม่ให้ข้อความ warning หลุดปนไปกับสตรีมไฟล์ binary
 */
class XlsxReportService
{
    /** ป้ายสถานะรับรอง (ใช้ร่วมกันทั้ง highland_eval.confirmstatus/spt_commit และ island_eval เดียวกัน) */
    private const CERT_LABELS = [0 => 'รอพิจารณา', 1 => 'รับรอง / เห็นชอบ', 2 => 'ไม่รับรอง / ไม่เห็นชอบ'];

    /** สร้างและส่งไฟล์ .xlsx ออกทาง HTTP ทันที (จบด้วย exit) */
    public static function screeningReport(?int $saoId, int $year): void
    {
        $base = rtrim((string) App::config('phpexcel_path'), '/');
        $prevErr = error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
        require_once $base . '/PHPExcel.php';

        $xls = new \PHPExcel();
        $xls->getProperties()
            ->setTitle('รายงานการคัดกรองและรับรอง ' . $year)
            ->setCreator('ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ');

        self::sheetHighland($xls, 0, $saoId, $year);
        self::sheetIsland($xls, 1, $saoId, $year);
        self::sheetConfirm($xls, 2, SchoolConfirm::AREA_HIGHLAND, 'รับรองคงอยู่-พื้นที่สูง', $saoId, $year);
        self::sheetConfirm($xls, 3, SchoolConfirm::AREA_ISLAND, 'รับรองคงอยู่-พื้นที่เกาะ', $saoId, $year);
        $xls->setActiveSheetIndex(0);

        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="screening_report_' . $year . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = \PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
        $writer->save('php://output');
        error_reporting($prevErr);
        exit;
    }

    /** เขียน header (ตัวหนา + freeze) + แถวข้อมูลลงชีตที่ $index (0 = ชีตแรกที่มีอยู่แล้ว) */
    private static function writeSheet(\PHPExcel $xls, int $index, string $title, array $headers, array $rows): void
    {
        if ($index === 0) {
            $sheet = $xls->getActiveSheet();
        } else {
            $xls->createSheet($index);
            $sheet = $xls->setActiveSheetIndex($index);
        }
        $sheet->setTitle(mb_substr($title, 0, 31));   // Excel จำกัดชื่อชีตไม่เกิน 31 ตัวอักษร
        // เขียนครั้งเดียว (header ต่อด้วยแถวข้อมูล) — เรียก fromArray() ซ้ำ 2 ครั้งบนชีตเดียวกัน
        // ทำให้ PHPExcel 1.7.5 ล้างแถวที่เขียนไปก่อนหน้าทิ้ง (แถว header หายไปทั้งแถว)
        $sheet->fromArray(array_merge([$headers], $rows), null, 'A1');

        $lastCol = \PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        for ($i = 0, $n = count($headers); $i < $n; $i++) {
            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private static function sheetHighland(\PHPExcel $xls, int $index, ?int $saoId, int $year): void
    {
        $headers = [
            'รหัสโรงเรียน', 'ชื่อโรงเรียน', 'สังกัด', 'จังหวัด',
            'ผู้บริหาร', 'เบอร์โทรผู้บริหาร', 'ผู้กรอกข้อมูล', 'เบอร์โทรผู้กรอก',
            'นร.อนุบาล', 'นร.ประถม', 'นร.ม.ต้น', 'นร.ม.ปลาย', 'นร.รวม',
            'จำนวนกลุ่มชาติพันธุ์', 'นร.พักนอนรวม',
            'คะแนนข้อ1', 'คะแนนข้อ2', 'คะแนนข้อ3', 'คะแนนข้อ4', 'คะแนนข้อ5', 'คะแนนข้อ6', 'คะแนนข้อ7', 'คะแนนข้อ8',
            'คะแนนข้อ9', 'คะแนนข้อ10', 'คะแนนข้อ11', 'คะแนนข้อ12', 'คะแนนข้อ13', 'คะแนนข้อ14', 'คะแนนข้อ15', 'คะแนนข้อ16',
            'คะแนนรวม', 'ระดับความยุ่งยาก', 'สถานะรับรองเขต', 'ความเห็นเขต', 'สถานะ สพฐ.', 'ความเห็น สพฐ.',
        ];

        $rows = [];
        foreach (HighlandEval::listForExport($saoId, $year) as $r) {
            $rows[] = [
                $r['sc_id'], $r['sc_names'] ?: $r['m_sc_name'], $r['sao_names'], $r['provinces'] ?: $r['m_provinces'],
                $r['director_name'], $r['director_tel'], $r['editor_name'], $r['editor_tel'],
                (int) $r['stu_kinder'], (int) $r['stu_prim'], (int) $r['stu_second'], (int) $r['stu_high'], (int) $r['stu_sum'],
                (int) $r['stu_hilltrib_group'], (int) $r['stu_sleep_sum'],
                $r['score01'], $r['score02'], $r['score03'], $r['score04'], $r['score05'], $r['score06'], $r['score07'], $r['score08'],
                $r['score09'], $r['score10'], $r['score11'], $r['score12'], $r['score13'], $r['score14'], $r['score15'], $r['score16'],
                $r['sum_score'],
                $r['sum_score'] !== null ? HighlandEvalController::typeLabel((int) $r['highland_type']) : '',
                self::CERT_LABELS[(int) ($r['confirmstatus'] ?? 0)] ?? '-', $r['confirmcomment'],
                self::CERT_LABELS[(int) ($r['spt_commit'] ?? 0)] ?? '-', $r['spt_comment'],
            ];
        }
        self::writeSheet($xls, $index, 'ประเมินพื้นที่สูง', $headers, $rows);
    }

    private static function sheetIsland(\PHPExcel $xls, int $index, ?int $saoId, int $year): void
    {
        $headers = [
            'รหัสโรงเรียน', 'ชื่อโรงเรียน', 'สังกัด', 'จังหวัด',
            'ผู้บริหาร', 'เบอร์โทรผู้บริหาร', 'ผู้กรอกข้อมูล', 'เบอร์โทรผู้กรอก',
            'นร.อนุบาล', 'นร.ประถม', 'นร.ม.ต้น', 'นร.ม.ปลาย', 'นร.รวม', 'ครู/บุคลากรรวม',
            'คะแนนข้อ1', 'คะแนนข้อ2', 'คะแนนข้อ3', 'คะแนนข้อ4', 'คะแนนข้อ5', 'คะแนนข้อ6', 'คะแนนข้อ7', 'คะแนนข้อ8',
            'คะแนนข้อ9', 'คะแนนข้อ10', 'คะแนนข้อ11', 'คะแนนข้อ12', 'คะแนนข้อ13', 'คะแนนข้อ14', 'คะแนนข้อ15',
            'คะแนนรวม', 'ระดับความยุ่งยาก', 'สถานะรับรองเขต', 'ความเห็นเขต', 'สถานะ สพฐ.', 'ความเห็น สพฐ.',
        ];

        $rows = [];
        foreach (IslandEval::listForExport($saoId, $year) as $r) {
            $rows[] = [
                $r['sc_id'], $r['sc_names'] ?: $r['m_sc_name'], $r['sao_names'], $r['provinces'] ?: $r['m_provinces'],
                $r['director_name'], $r['director_tel'], $r['editor_name'], $r['editor_tel'],
                (int) $r['stu_kinder'], (int) $r['stu_prim'], (int) $r['stu_second'], (int) $r['stu_high'], (int) $r['stu_sum'],
                (int) $r['sum_teacher'],
                $r['score01'], $r['score02'], $r['score03'], $r['score04'], $r['score05'], $r['score06'], $r['score07'], $r['score08'],
                $r['score09'], $r['score10'], $r['score11'], $r['score12'], $r['score13'], $r['score14'], $r['score15'],
                $r['sum_score'],
                $r['sum_score'] !== null ? IslandEvalController::typeLabel((int) $r['island_type']) : '',
                self::CERT_LABELS[(int) ($r['confirmstatus'] ?? 0)] ?? '-', $r['confirmcomment'],
                self::CERT_LABELS[(int) ($r['spt_commit'] ?? 0)] ?? '-', $r['spt_comment'],
            ];
        }
        self::writeSheet($xls, $index, 'ประเมินพื้นที่เกาะ', $headers, $rows);
    }

    private static function sheetConfirm(\PHPExcel $xls, int $index, int $area, string $title, ?int $saoId, int $year): void
    {
        $headers = [
            'รหัสโรงเรียน', 'ชื่อโรงเรียน', 'สังกัด (เขต)',
            'ผู้บริหาร', 'เบอร์ผู้บริหาร', 'ผู้ประสาน', 'เบอร์ผู้ประสาน',
            'นร.ชาย', 'นร.หญิง', 'นร.รวม', 'ครูข้าราชการ', 'ครูอัตราจ้าง', 'รองผอ.', 'ผอ.', 'ครู/บุคลากรรวม',
            'สถานะโรงเรียน', 'ประเภท (ยุบ/รวม/เลิก) / เหตุผลขาดคุณสมบัติ', 'ไปรวมกับ',
            'โรงเรียนยืนยันแล้ว', 'วันที่ยืนยัน', 'ส่งข้อมูลแล้ว', 'วันที่ส่ง',
            'สถานะรับรองเขต', 'ความเห็นเขต', 'วันที่เขตรับรอง',
            'สถานะ สพฐ.', 'ความเห็น สพฐ.', 'วันที่ สพฐ. พิจารณา',
        ];

        $rows = [];
        foreach (SchoolConfirm::listFor($area, $year, $saoId, [], 20000) as $r) {
            $rows[] = [
                $r['sc_id'], $r['sc_name'], $r['provinces'],
                $r['director_name'], $r['director_phone'], $r['informant_name'], $r['informant_phone'],
                (int) $r['std_male'], (int) $r['std_female'], (int) $r['std_total'],
                (int) $r['tch_govt'], (int) $r['tch_hire'], (int) $r['tch_deputy'], (int) $r['tch_director'], (int) $r['tch_total'],
                SchoolConfirm::openedLabel((int) $r['opened']),
                (int) $r['opened'] === SchoolConfirm::OPENED_DISQUALIFIED
                    ? ($r['disqualify_reason'] ?? '')
                    : ((int) $r['close_type'] > 0 ? SchoolConfirm::closeTypeLabel((int) $r['close_type']) : ''),
                $r['merged_to_name'],
                $r['school_confirmed'] ? 'ยืนยันแล้ว' : 'ยังไม่ยืนยัน', $r['school_confirm_at'],
                $r['submitted'] ? 'ส่งแล้ว' : 'ยังไม่ส่ง', $r['submitted_at'],
                SchoolConfirm::statusLabel((int) $r['sao_status']), $r['sao_comment'], $r['sao_at'],
                SchoolConfirm::statusLabel((int) $r['spt_status']), $r['spt_comment'], $r['spt_at'],
            ];
        }
        self::writeSheet($xls, $index, $title, $headers, $rows);
    }
}
