<?php
namespace App\Services;

use App\Core\App;
use App\Models\HighlandEval;
use App\Models\IslandEval;

/**
 * สร้าง PDF แบบประเมิน ด้วย mPDF (overlay ข้อความบน template.pdf)
 * พอร์ตจาก highland/mpdf/index.php — ใช้ vendor/ฟอนต์/เทมเพลตเดิมผ่าน config 'mpdf_path'
 */
class PdfService
{
    /** แบบประเมินพื้นที่สูงในถิ่นทุรกันดาร (4 หน้า) */
    public static function highlandEval(int $scId, int $year): void
    {
        $base = rtrim((string) App::config('mpdf_path'), '/');
        require_once $base . '/vendor/autoload.php';

        $row = HighlandEval::find($scId, $year);
        if (!$row) { http_response_code(404); exit('ไม่พบข้อมูลแบบประเมิน'); }

        // ค่าต่าง ๆ
        $g = fn($k) => $row[$k] ?? '';
        $sc_names = $g('sc_names'); $sc_id = $g('sc_id'); $code = $g('sao_names');
        $adresss = $g('adresss'); $viledges = $g('viledges'); $moo = $g('moo');
        $subdistrict = $g('subdistrict'); $district = $g('district'); $provinces = $g('provinces');
        $stu_kinder = $g('stu_kinder'); $stu_prim = $g('stu_prim'); $stu_second = $g('stu_second');
        $stu_high = $g('stu_high'); $stu_sum = (float) $g('stu_sum'); if ($stu_sum <= 0) $stu_sum = 1;
        $stu_sleep_sum = $g('stu_sleep_sum'); $stu_sleep_boy = $g('stu_sleep_boy'); $stu_sleep_girl = $g('stu_sleep_girl');
        $lat = $g('lat'); $lng = $g('lng'); $highest = $g('highest');
        $average_height = $g('average_height'); $distance_to_province = $g('distance_to_province');
        $citeria041 = $g('citeria041'); $citeria11 = $g('citeria11');
        $citeria13 = (float) $g('citeria13'); $citeria14 = $g('citeria14'); $citeria15 = $g('citeria15');
        $editor_name = $g('editor_name'); $director_name = $g('director_name');
        foreach (['citeria02','citeria03','citeria04','citeria06','citeria07','citeria08','citeria09','citeria10','citeria16'] as $c) $$c = $g($c);
        foreach (['score01','score02','score03','score04','score05','score06','score07','score08','score09','score10','score11','score12','score13','score14','score15','score16','sum_score'] as $c) $$c = $g($c);

        // กลุ่มชาติพันธุ์
        $rows = HighlandEval::hilltribRows($scId, $year);
        $ethnic = [];
        $hilltrib_number1 = [];
        $snt = 0; $i = 0;
        foreach ($rows as $r) {
            $i++;
            $ethnic[$i] = $r['ethnic'];
            $hilltrib_number1[$i] = (int) $r['hilltrib_number'];
            $snt += (int) $r['hilltrib_number'];
        }
        $stu_hilltrib_group = $i;
        $en = fn($n) => $ethnic[$n] ?? '';
        $hn = fn($n) => $hilltrib_number1[$n] ?? '';
        $pc = fn($n) => isset($hilltrib_number1[$n]) ? number_format($hilltrib_number1[$n] * 100 / $stu_sum, 2) : '';

        $chk = $base . '/chk.gif';
        $img = fn($l, $t) => "<div style=\"position:fixed; left:{$l}px; top: {$t}px;\"><img src='{$chk}' width='47' height='36'></div>";

        // mPDF
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $mpdf = new \Mpdf\Mpdf([
            'tempDir'  => $base . '/vendor/mpdf/mpdf/tmp',
            'fontDir'  => array_merge($fontDirs, [$base . '/fonts', $base . '/tmp']),
            'fontdata' => $fontData + ['thsarabun' => [
                'R' => 'THSarabunNew.ttf', 'B' => 'THSarabunNew-Bold.ttf',
                'I' => 'THSarabunNew-Italic.ttf', 'BI' => 'THSarabunNew-BoldItalic.ttf',
            ]],
            'default_font_size' => 16,
            'default_font' => 'thsarabun',
        ]);
        $mpdf->SetSourceFile($base . '/template.pdf');

        // ---- หน้า 1 ----
        $mpdf->useTemplate($mpdf->importPage(1));
        // $fd  = text div (width:500px)
        // $num = number div (ไม่มี background)
        // ทุก div ต้องมี width (เหมือนต้นฉบับเดิม highland/mpdf) — ถ้าไม่กำหนด width
        // mPDF จะยุบ div แคบจนตัวเลขหลายหลัก (เช่น 12, 45, 62) ตัดบรรทัด 1 หลัก/บรรทัด
        $fd  = fn($l, $t, $v) => "<div style=\"position:fixed; left:{$l}px; top:{$t}px; width:500px;\">{$v}</div>";
        $num = $fd;

        $h = '';
        $h .= $fd(190, 122, $sc_names);
        $h .= $fd(490, 122, $sc_id);
        $h .= $fd(100, 150, $code);
        // ที่อยู่ — รวม adresss + viledges ใน div เดียว ป้องกันซ้อนทับ
        $fullAddr = (trim($adresss) !== '' ? trim($adresss) . ' ' : '') . $viledges
            . ' &nbsp;หมู่ ' . $moo . ' &nbsp;ต. ' . $subdistrict
            . ' &nbsp;อ. ' . $district . ' &nbsp;จ. ' . $provinces;
        $h .= $fd(80, 178, $fullAddr);
        $h .= $num(500, 263, $stu_kinder);
        $h .= $num(500, 295, $stu_prim);
        $h .= $num(500, 327, $stu_second);
        $h .= $num(500, 359, $stu_high);
        $h .= $num(500, 391, (int) $stu_sum);
        $tops = [480, 509, 537, 565, 593, 621];
        foreach ($tops as $k => $t) {
            $n = $k + 1;
            $h .= $fd(52,  $t, "{$n}.");
            $h .= $fd(100, $t, $en($n));
            $h .= $num(350, $t, $hn($n));
            $h .= $fd(500, $t, $pc($n));
        }
        $h .= $fd(385, 423, '*** แสดงผลในตารางได้สูงสุด 6 กลุ่มชาติพันธุ์ ***');
        $h .= $num(350, 650, $snt);
        $h .= $fd(500, 650, number_format((float) $citeria11, 2));
        $h .= $num(350, 675, $stu_hilltrib_group);
        $h .= $num(280, 703, $stu_sleep_sum);
        $h .= $num(415, 703, $stu_sleep_boy);
        $h .= $num(545, 703, $stu_sleep_girl);
        $h .= $fd(250, 759, $lat);
        $h .= $fd(250, 786, $lng);
        $h .= $fd(260, 842, $highest);
        $h .= $fd(555, 870, $average_height);
        $h .= $fd(390, 898, $distance_to_province);
        $mpdf->WriteHTML($h);

        // ---- หน้า 2 ----
        $mpdf->AddPage();
        $mpdf->useTemplate($mpdf->importPage(2));
        $h2 = '';
        $h2 .= $fd(112, 112, $highest);
        $h2 .= $fd(550, 174, "<b>&nbsp; {$score01} &nbsp;คะแนน</b>");
        $map2 = [1 => 250, 2 => 282, 3 => 314, 4 => 346, 5 => 380];
        if (isset($map2[(int) $citeria02])) $h2 .= $img(77, $map2[(int) $citeria02]);
        $h2 .= $fd(550, 390, "<b>&nbsp; {$score02} &nbsp;คะแนน</b>");
        $map3 = [1 => 472, 2 => 504, 3 => 536, 4 => 568];
        if (isset($map3[(int) $citeria03])) $h2 .= $img(77, $map3[(int) $citeria03]);
        $h2 .= $fd(550, 578, "<b>&nbsp; {$score03} &nbsp;คะแนน</b>");
        if ((int) $citeria04 === 1) {
            $h2 .= $img(77, 715);
            $h2 .= $fd(180, 748, $citeria041);
            $h2 .= $fd(550, 748, "<b>&nbsp; {$score04} &nbsp;คะแนน</b>");
        } elseif ((int) $citeria04 === 2) {
            $h2 .= $img(77, 774);
            $h2 .= $fd(550, 812, "<b>&nbsp; {$score04} &nbsp;คะแนน</b>");
        }
        $h2 .= $fd(500, 837, $distance_to_province);
        $h2 .= $fd(550, 897, "<b>&nbsp; {$score05} &nbsp;คะแนน</b>");
        $mpdf->WriteHTML($h2);

        // ---- หน้า 3 ----
        $mpdf->AddPage();
        $mpdf->useTemplate($mpdf->importPage(3));
        $h3 = '';
        $map6 = [1 => 29, 2 => 61, 3 => 93, 4 => 125, 5 => 157];
        if (isset($map6[(int) $citeria06])) $h3 .= $img(77, $map6[(int) $citeria06]);
        $h3 .= $fd(550, 167, "<b>&nbsp; {$score06} &nbsp;คะแนน</b>");
        $map7 = [1 => 248, 2 => 280, 3 => 312, 4 => 344, 5 => 377, 6 => 409];
        foreach (explode(',', (string) $citeria07) as $cv) if (isset($map7[(int) $cv])) $h3 .= $img(77, $map7[(int) $cv]);
        $h3 .= $fd(550, 419, "<b>&nbsp; {$score07} &nbsp;คะแนน</b>");
        $map8 = [1 => 501, 2 => 562];
        foreach (explode(',', (string) $citeria08) as $cv) if (isset($map8[(int) $cv])) $h3 .= $img(77, $map8[(int) $cv]);
        $h3 .= $fd(550, 572, "<b>&nbsp; {$score08} &nbsp;คะแนน</b>");
        $map9 = [1 => 651, 2 => 683, 3 => 715, 4 => 747];
        foreach (explode(',', (string) $citeria09) as $cv) if (isset($map9[(int) $cv])) $h3 .= $img(77, $map9[(int) $cv]);
        $h3 .= $fd(550, 757, "<b>&nbsp; {$score09} &nbsp;คะแนน</b>");
        $map10 = [1 => 838, 2 => 870, 3 => 902, 4 => 934];
        foreach (explode(',', (string) $citeria10) as $cv) if (isset($map10[(int) $cv])) $h3 .= $img(77, $map10[(int) $cv]);
        $h3 .= $fd(550, 944, "<b>&nbsp; {$score10} &nbsp;คะแนน</b>");
        $mpdf->WriteHTML($h3);

        // ---- หน้า 4 ----
        $mpdf->AddPage();
        $mpdf->useTemplate($mpdf->importPage(4));
        $h4 = '';
        $h4 .= $fd(440, 30,  $citeria11);
        $h4 .= $fd(550, 30,  "<b>&nbsp; {$score11} &nbsp;คะแนน</b>");
        $h4 .= $num(310, 86, $stu_hilltrib_group);
        $h4 .= $fd(550, 86,  "<b>&nbsp; {$score12} &nbsp;คะแนน</b>");
        $c13pct = number_format($citeria13 * 100 / $stu_sum, 2);
        $h4 .= $fd(520, 168, $c13pct);
        $h4 .= $fd(550, 168, "<b>&nbsp; {$score13} &nbsp;คะแนน</b>");
        $h4 .= $fd(255, 280, $citeria14);
        $h4 .= $fd(550, 280, "<b>&nbsp; {$score14} &nbsp;คะแนน</b>");
        $h4 .= $fd(345, 338, $citeria15);
        $h4 .= $fd(550, 368, "<b>&nbsp; {$score15} &nbsp;คะแนน</b>");
        $map16 = [1 => 418, 2 => 450];
        if (isset($map16[(int) $citeria16])) $h4 .= $img(77, $map16[(int) $citeria16]);
        $h4 .= $fd(550, 460, "<b>&nbsp; {$score16} &nbsp;คะแนน</b>");
        $h4 .= $fd(430, 550, "<b>รวมคะแนนทั้งหมด&nbsp; {$sum_score} &nbsp;คะแนน</b>");
        $h4 .= $fd(355, 650, "<b>ลงชื่อ.........................................ผู้บันทึก</b>");
        $h4 .= $fd(370, 680, "<b>&nbsp;&nbsp;&nbsp;&nbsp;({$editor_name})</b>");
        $h4 .= $fd(370, 710, "<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ผู้บันทึกข้อมูล</b>");
        $h4 .= $fd(355, 780, "<b>ลงชื่อ.........................................ผู้รับรองข้อมูล</b>");
        $h4 .= $fd(370, 810, "<b>({$director_name})</b>");
        $h4 .= $fd(340, 840, "<b>ผู้อำนวยการโรงเรียน {$sc_names}</b>");
        $mpdf->WriteHTML($h4);

        $mpdf->Output('highland_' . $scId . '_' . $year . '.pdf', 'I');
        exit;
    }

    /** แบบประเมินพื้นที่เกาะ (3 หน้า, FPDF overlay บนภาพ + ฟอนต์ TIS-620) */
    public static function islandEval(int $scId, int $year): void
    {
        $base = rtrim((string) App::config('mpdf_island_path'), '/');
        if (!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', $base . '/fpdf/');
        require_once $base . '/fpdf/fpdf.php';

        $row = IslandEval::find($scId, $year);
        if (!$row) { http_response_code(404); exit('ไม่พบข้อมูลแบบประเมิน'); }

        $g = fn($k) => $row[$k] ?? '';
        // แปลงข้อความไทย UTF-8 -> TIS-620 (ฟอนต์ FPDF เป็น TIS-620)
        $t = fn($s) => iconv('UTF-8', 'TIS-620//IGNORE', (string) $s);

        $stu_sum = (float) $g('stu_sum'); if ($stu_sum <= 0) $stu_sum = 1;

        $pdf = new \FPDF();

        // ---------- หน้า 1 (Page15) ----------
        $pdf->AddPage();
        $pdf->AddFont('THSarabun', '', 'THSarabun.php');
        $pdf->SetFont('THSarabun', '', 16);
        $pdf->Image($base . '/fpdf/Page15.jpg', 0, 0, 209, 286);

        $L = 49;
        $pdf->SetY($L); $pdf->Cell(60); $pdf->Cell(60, 9, $t($g('sc_names')), 0, 0, 'L');
        $pdf->SetY($L); $pdf->Cell(122); $pdf->Cell(50, 9, $t($g('sc_id')), 0, 0, 'L');
        $L += 6; $pdf->SetY($L); $pdf->Cell(36); $pdf->Cell(150, 9, $t($g('sao_names')), 0, 0, 'L');

        $full = trim($g('adresss')) . ' ' . $g('viledges')
            . ' หมู่ที่ ' . trim($g('moo')) . ' ตำบล ' . trim($g('subdistrict'))
            . ' อำเภอ ' . trim($g('district')) . ' จังหวัด ' . trim($g('provinces'));
        $L += 6; $pdf->SetY($L); $pdf->Cell(36); $pdf->Cell(150, 9, $t($full), 0, 0, 'L');

        foreach ([['stu_kinder',21],['stu_prim',6],['stu_second',7],['stu_high',6],['stu_sum',7]] as $f) {
            $L += $f[1]; $pdf->SetY($L); $pdf->Cell(148); $pdf->Cell(18, 9, $g($f[0]), 0, 0, 'R');
        }
        foreach ([['admin',20],['teacher',7],['gov_employee',6],['perm_employee',7],['perm_teacher',7],['suport_teaching',7],['sum_teacher',6]] as $f) {
            $L += $f[1]; $pdf->SetY($L); $pdf->Cell(148); $pdf->Cell(18, 9, $g($f[0]), 0, 0, 'R');
        }
        $L += 13; $pdf->SetY($L); $pdf->Cell(80); $pdf->Cell(50, 9, $g('lat'), 0, 0, 'L');
        $L += 7;  $pdf->SetY($L); $pdf->Cell(80); $pdf->Cell(50, 9, $g('lng'), 0, 0, 'L');
        $L += 7;  $pdf->SetY($L); $pdf->Cell(100); $pdf->Cell(30, 9, $g('distance_to_province'), 0, 0, 'R');

        // ---------- หน้า 2 (Page16) ----------
        $pdf->AddPage();
        $pdf->SetFont('THSarabun', '', 16);
        $pdf->Image($base . '/fpdf/Page16.jpg', 0, 0, 209, 286);
        $M = 50;
        $chk = fn($top) => $pdf->Image($base . '/fpdf/checked.png', 48, $top, 5, 5);
        $score = fn($y, $val) => ($pdf->SetY($y) || true) && $pdf->Cell(150) === null
            ? $pdf->Cell(30, 9, $val . $t(' คะแนน'), 0, 0, 'R') : null;

        $c01 = (int) $g('citeria01');
        if ($c01 === 1) $chk($M + 4.5); elseif ($c01 === 2) $chk($M + 11);

        $map02 = [1 => [$M + 37, 10], 2 => [$M + 43.5, 8], 3 => [$M + 50, 6], 4 => [$M + 56.5, 4]];
        $c02 = (int) $g('citeria02'); $s02 = 0;
        if (isset($map02[$c02])) { $chk($map02[$c02][0]); $s02 = $map02[$c02][1]; }
        $pdf->SetY($M + 54.5); $pdf->Cell(150); $pdf->Cell(30, 9, $s02 . $t(' คะแนน'), 0, 0, 'R');

        $map03 = [1 => [$M + 69.5, 16], 2 => [$M + 82.5, 0]];
        $c03 = (int) $g('citeria03'); $s03 = 0;
        if (isset($map03[$c03])) { $chk($map03[$c03][0]); $s03 = $map03[$c03][1]; }
        $pdf->SetY($M + 87); $pdf->Cell(150); $pdf->Cell(30, 9, $s03 . $t(' คะแนน'), 0, 0, 'R');

        $map04 = [1 => [$M + 108.5, 20], 2 => [$M + 115, 15], 3 => [$M + 121.5, 10], 4 => [$M + 128, 5], 5 => [$M + 134.5, 0]];
        $c04 = (int) $g('citeria04'); $s04 = 0;
        if (isset($map04[$c04])) { $chk($map04[$c04][0]); $s04 = $map04[$c04][1]; }
        $pdf->SetY($M + 132.5); $pdf->Cell(150); $pdf->Cell(30, 9, $s04 . $t(' คะแนน'), 0, 0, 'R');

        $c05 = (float) $g('citeria05'); $s05 = min($c05, 20) * 5 / 20; if ($c05 < 0.1) $s05 = 0;
        $L = $M + 151; $pdf->SetY($L); $pdf->Cell(24); $pdf->Cell(16, 9, $c05, 0, 0, 'L');
        $pdf->SetY($L + 1); $pdf->Cell(150); $pdf->Cell(30, 9, number_format($s05, 2) . $t(' คะแนน'), 0, 0, 'R');

        $c06 = (float) $g('citeria06'); $s06 = min($c06, 20) * 5 / 20; if ($c06 < 0.1) $s06 = 0;
        $L += 13.5; $pdf->SetY($L); $pdf->Cell(112); $pdf->Cell(16, 9, $c06, 0, 0, 'L');
        $pdf->SetY($L + 1); $pdf->Cell(150); $pdf->Cell(30, 9, number_format($s06, 2) . $t(' คะแนน'), 0, 0, 'R');

        $c07 = (float) $g('citeria07'); $s07 = min($c07, 60) * 5 / 60; if ($c07 < 1) $s07 = 0;
        $L += 13.5; $pdf->SetY($L); $pdf->Cell(71); $pdf->Cell(16, 9, $c07, 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(150); $pdf->Cell(30, 9, number_format($s07, 2) . $t(' คะแนน'), 0, 0, 'R');

        $c08 = (float) $g('citeria08'); $s08 = min($c08, 500) * 5 / 500;
        $L += 13; $pdf->SetY($L); $pdf->Cell(84); $pdf->Cell(17, 9, $c08, 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(150); $pdf->Cell(30, 9, number_format($s08, 2) . $t(' คะแนน'), 0, 0, 'R');

        // ---------- หน้า 3 (Page17) ----------
        $pdf->AddPage();
        $pdf->SetFont('THSarabun', '', 16);
        $pdf->Image($base . '/fpdf/Page17.jpg', 0, 0, 209, 286);
        $M = 30;

        $map09 = [1 => [$M + 4.5, 5], 2 => [$M + 11, 4], 3 => [$M + 17.5, 3], 4 => [$M + 24, 2], 5 => [$M + 30.5, 1]];
        $c09 = (int) $g('citeria09'); $s09 = 0;
        if (isset($map09[$c09])) { $chk($map09[$c09][0]); $s09 = $map09[$c09][1]; }
        $pdf->SetY($M + 29); $pdf->Cell(150); $pdf->Cell(30, 9, $s09 . $t(' คะแนน'), 0, 0, 'R');

        $map10 = [1 => [$M + 50, 5], 2 => [$M + 63, 0]];
        $c10 = (int) $g('citeria10'); $s10 = 0;
        if (isset($map10[$c10])) { $chk($map10[$c10][0]); $s10 = $map10[$c10][1]; }
        $pdf->SetY($M + 61.5); $pdf->Cell(150); $pdf->Cell(30, 9, $s10 . $t(' คะแนน'), 0, 0, 'R');

        $map11 = [1 => [$M + 76, 10], 2 => [$M + 82.5, 8], 3 => [$M + 89, 6], 4 => [$M + 95, 4], 5 => [$M + 102, 2], 6 => [$M + 108.5, 0]];
        $c11 = (int) $g('citeria11'); $s11 = 0;
        if (isset($map11[$c11])) { $chk($map11[$c11][0]); $s11 = $map11[$c11][1]; }
        $pdf->SetY($M + 107); $pdf->Cell(150); $pdf->Cell(30, 9, $s11 . $t(' คะแนน'), 0, 0, 'R');

        $map12 = [1 => [$M + 122.5, 5], 2 => [$M + 129, 4], 3 => [$M + 135.5, 3], 4 => [$M + 142, 2]];
        $c12 = (int) $g('citeria12'); $s12 = 0;
        if (isset($map12[$c12])) { $chk($map12[$c12][0]); $s12 = $map12[$c12][1]; }
        $pdf->SetY($M + 140); $pdf->Cell(150); $pdf->Cell(30, 9, $s12 . $t(' คะแนน'), 0, 0, 'R');

        $map13 = [1 => [$M + 155, 5], 2 => [$M + 161.5, 4], 3 => [$M + 168, 3], 4 => [$M + 174.5, 2]];
        $c13 = (int) $g('citeria13'); $s13 = 0;
        if (isset($map13[$c13])) { $chk($map13[$c13][0]); $s13 = $map13[$c13][1]; }
        $pdf->SetY($M + 172); $pdf->Cell(150); $pdf->Cell(30, 9, $s13 . $t(' คะแนน'), 0, 0, 'R');

        $map14 = [1 => [$M + 194.5, 2], 2 => [$M + 201, 0]];
        $c14 = (int) $g('citeria14'); $s14 = 0;
        if (isset($map14[$c14])) { $chk($map14[$c14][0]); $s14 = $map14[$c14][1]; }
        $pdf->SetY($M + 199); $pdf->Cell(150); $pdf->Cell(30, 9, $s14 . $t(' คะแนน'), 0, 0, 'R');

        $c15 = (float) $g('citeria15'); $pct = $c15 * 100 / $stu_sum; $s15 = min($pct, 50) * 2 / 50;
        $L = $M + 205.5; $pdf->SetY($L); $pdf->Cell(134); $pdf->Cell(16, 9, $g('citeria15'), 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(150); $pdf->Cell(30, 9, number_format($s15, 2) . $t(' คะแนน'), 0, 0, 'R');

        $L += 6; $pdf->SetY($L); $pdf->Cell(100);
        $pdf->Cell(80, 9, $t('รวมคะแนนทั้งหมด  ') . number_format((float) $g('sum_score'), 2) . $t('  คะแนน'), 0, 0, 'R');

        $L += 10; $pdf->SetY($L); $pdf->Cell(15);
        $pdf->Cell(80, 9, $t('ลงชื่อ') . '......................................' . $t('ผู้บันทึก'), 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(100);
        $pdf->Cell(80, 9, $t('ลงชื่อ') . '......................................' . $t('ผู้รับรองข้อมูล'), 0, 0, 'C');
        $L += 8; $pdf->SetY($L); $pdf->Cell(14); $pdf->Cell(80, 9, '(' . $t($g('editor_name')) . ')', 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(95); $pdf->Cell(80, 9, '(' . $t($g('director_name')) . ')', 0, 0, 'C');
        $L += 8; $pdf->SetY($L); $pdf->Cell(14); $pdf->Cell(80, 9, $t('ผู้บันทึกข้อมูล'), 0, 0, 'C');
        $pdf->SetY($L); $pdf->Cell(95); $pdf->Cell(80, 9, $t('ผู้อำนวยการ') . $t($g('sc_names')), 0, 0, 'C');

        while (ob_get_level() > 0) { @ob_end_clean(); }
        $pdf->Output('island_' . $scId . '_' . $year . '.pdf', 'I');
        exit;
    }
}
