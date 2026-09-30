<?php
namespace App\Services;

use App\Models\HighlandEval;
use App\Models\IslandEval;
use App\Models\SchoolConfirm;

/**
 * ส่งออก "บัญชีแนบท้าย" รายชื่อโรงเรียนพื้นที่ลักษณะพิเศษ เป็นไฟล์ Word (.docx)
 * รูปแบบตามเอกสารราชการเดิม (docsref/4.แนบท้าย 2.docx): กระดาษ A4 แนวนอน, ฟอนต์ Angsana New 16pt,
 * แบ่งตารางตาม "สำนักงานเขตพื้นที่การศึกษา" (หนึ่งเขต = หนึ่งตาราง, ขึ้นหน้าใหม่)
 *
 * มี 2 ชนิดรายงาน:
 *   1) ผลคัดกรอง (appendix)  — highland_eval/island_eval ; คอลัมน์สุดท้าย = ระดับความยุ่งยาก
 *   2) รายชื่อที่ยืนยันคงอยู่ (confirmAppendix) — school_confirm (โรงเรียนเก่าที่ยืนยันแล้ว) ; คอลัมน์สุดท้าย = ระดับความยุ่งยาก (เดิม)
 *
 * สร้าง .docx เองด้วย ZipArchive (โปรเจกต์นี้ไม่มี Composer — แพทเทิร์นเดียวกับ XlsxReportService/PdfService)
 */
class WordReportService
{
    /** ป้ายระดับความยุ่งยาก (แบบสั้น ตรงกับเอกสารต้นแบบ) */
    private const DIFF = [1 => 'ยุ่งยาก', 2 => 'ยุ่งยากมาก', 3 => 'ยุ่งยากมากที่สุด'];

    /** ความกว้างคอลัมน์ (dxa) — ตรงกับ docsref/4.แนบท้าย 2.docx */
    private const COLW = [724, 1751, 5087, 1701, 1701, 1701, 1981];

    private const HEAD_EVAL    = ['ลำดับ', 'รหัสสถานศึกษา', 'ชื่อสถานศึกษา', 'ตำบล', 'อำเภอ', 'จังหวัด', 'ระดับความยุ่งยาก'];
    private const HEAD_CONFIRM = ['ลำดับ', 'รหัสสถานศึกษา', 'ชื่อสถานศึกษา', 'ตำบล', 'อำเภอ', 'จังหวัด', 'ระดับความยุ่งยาก'];

    // ---------- 1) ผลคัดกรอง (ประเมินพื้นที่สูง/เกาะ) ----------

    /** สร้างและส่งไฟล์ผลคัดกรองออก HTTP ทันที (จบด้วย exit) */
    public static function appendix(int $area, ?int $saoId, int $year): void
    {
        $fname = ($area === 2 ? 'appendix2_island_' : 'appendix1_highland_') . $year . '.docx';
        self::stream(self::render($area, $saoId, $year), $fname);
    }

    /** ประกอบไฟล์ผลคัดกรองเป็นสตริงไบต์ (แยกจาก HTTP เพื่อทดสอบ/ใช้ซ้ำ) */
    public static function render(int $area, ?int $saoId, int $year): string
    {
        [$rows, $typeCol] = $area === 2
            ? [IslandEval::listForExport($saoId, $year), 'island_type']
            : [HighlandEval::listForExport($saoId, $year), 'highland_type'];

        // เฉพาะโรงเรียนที่ประเมินเสร็จและเข้าเกณฑ์พื้นที่พิเศษ (ระดับ 1–3)
        // และตัดโรงเรียนที่ roster ระบุว่า "ยุบ/รวม/เลิก" หรือ "ขาดคุณสมบัติ" ออก
        // (โรงเรียนที่เสียคุณสมบัติแล้วไม่ควรอยู่ในบัญชีแนบท้ายรายชื่อทางการ)
        $leaving = array_flip(SchoolConfirm::leavingScIds($area, $year));
        $rows = array_filter($rows, fn($r) =>
            $r['sum_score'] !== null
            && (int) ($r[$typeCol] ?? 0) >= 1
            && !isset($leaving[(string) ($r['sc_id'] ?? '')]));

        $groups = self::groupBy($rows, fn($r) => self::officeName(trim((string) ($r['sao_names'] ?? ''))));
        $sections = [];
        foreach ($groups as $office => $list) {
            usort($list, fn($a, $b) => self::locKey($a) <=> self::locKey($b));
            $sections[] = ['office' => $office, 'rows' => array_map(function ($r) use ($typeCol) {
                return [
                    self::col($r, 'sc_id'),
                    self::schoolName(self::col($r, 'sc_names', 'm_sc_name')),
                    self::col($r, 'subdistrict'),
                    self::col($r, 'district'),
                    self::col($r, 'provinces', 'm_provinces'),
                    self::DIFF[(int) ($r[$typeCol] ?? 0)] ?? '',
                ];
            }, $list)];
        }

        $areaName = $area === 2 ? 'พื้นที่เกาะ' : 'พื้นที่ภูเขาสูงในถิ่นทุรกันดาร';
        $title = 'บัญชีแนบท้าย ' . ($area === 2 ? '2' : '1')
               . ' รายชื่อสถานศึกษาที่เป็นโรงเรียนพื้นที่ลักษณะพิเศษ (' . $areaName . ')'
               . ' ประจำปีงบประมาณ พ.ศ. ' . $year;

        return self::pack(self::compose($title, self::HEAD_EVAL, $sections,
            '— ยังไม่มีโรงเรียนที่เข้าเกณฑ์พื้นที่พิเศษในปีงบประมาณนี้ —'));
    }

    // ---------- 2) รายชื่อโรงเรียนเก่าที่ยืนยันการคงอยู่ ----------

    /** สร้างและส่งไฟล์รายชื่อที่ยืนยันคงอยู่ออก HTTP ทันที (จบด้วย exit) */
    public static function confirmAppendix(int $area, ?int $saoId, int $year): void
    {
        $fname = ($area === 2 ? 'confirmed2_island_' : 'confirmed1_highland_') . $year . '.docx';
        self::stream(self::renderConfirm($area, $saoId, $year), $fname);
    }

    /** ประกอบไฟล์รายชื่อที่ยืนยันคงอยู่เป็นสตริงไบต์ */
    public static function renderConfirm(int $area, ?int $saoId, int $year): string
    {
        $rows = SchoolConfirm::listConfirmedForAppendix($area, $year, $saoId);

        $groups = self::groupBy($rows, fn($r) => self::officeName(trim((string) ($r['sao_name'] ?? ''))));
        $sections = [];
        foreach ($groups as $office => $list) {
            usort($list, fn($a, $b) => self::locKey($a) <=> self::locKey($b));
            $sections[] = ['office' => $office, 'rows' => array_map(function ($r) {
                return [
                    self::col($r, 'sc_id'),
                    self::schoolName(self::col($r, 'sc_name')),
                    self::col($r, 'subdistrict'),
                    self::col($r, 'district'),
                    self::col($r, 'province', 'provinces'),   // school_new.province → school_confirm.provinces
                    self::DIFF[(int) ($r['diff_level'] ?? 0)] ?? '',   // ระดับความยุ่งยากเดิม (จากผลประเมิน legacy)
                ];
            }, $list)];
        }

        $areaName = $area === 2 ? 'พื้นที่เกาะ' : 'พื้นที่ภูเขาสูงในถิ่นทุรกันดาร';
        $title = 'บัญชีแนบท้าย รายชื่อโรงเรียนเดิม (' . $areaName . ') ที่ยืนยันการคงอยู่'
               . ' ประจำปีงบประมาณ พ.ศ. ' . $year;

        return self::pack(self::compose($title, self::HEAD_CONFIRM, $sections,
            '— ยังไม่มีโรงเรียนที่ยืนยันการคงอยู่ในปีงบประมาณนี้ —'));
    }

    // ---------- ตัวประกอบเอกสาร (ใช้ร่วมทั้ง 2 ชนิด) ----------

    /**
     * ประกอบ word/document.xml จาก sections
     * @param array $sections รายการ ['office' => ชื่อเขต, 'rows' => [[c1..c6], ...]] (c1..c6 = คอลัมน์หลัง "ลำดับ")
     */
    private static function compose(string $title, array $headers, array $sections, string $emptyMsg): string
    {
        $body = self::para(self::run(self::esc($title), true, 36), 'center', ['after' => 240]);

        if (!$sections) {
            $body .= self::para(self::run(self::esc($emptyMsg)), 'center');
        }

        $first = true;
        foreach ($sections as $sec) {
            $body .= self::para(
                self::run(self::esc((string) $sec['office']), true),
                'center',
                ['before' => 120, 'after' => 120, 'pageBreak' => !$first, 'keepNext' => true]
            );
            $body .= self::table($headers, $sec['rows']);
            $first = false;
        }

        $sect = '<w:sectPr>'
              . '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
              . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="720" w:footer="720" w:gutter="0"/>'
              . '</w:sectPr>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
             . '<w:body>' . $body . $sect . '</w:body></w:document>';
    }

    /** ตารางหนึ่งเขต — เติมคอลัมน์ "ลำดับ" อัตโนมัติ; จัดกึ่งกลางคอลัมน์ 0,1 และคอลัมน์สุดท้าย */
    private static function table(array $headers, array $dataRows): string
    {
        $last = count(self::COLW) - 1;
        $isCenter = fn(int $j) => $j === 0 || $j === 1 || $j === $last;

        $grid = '';
        foreach (self::COLW as $w) $grid .= '<w:gridCol w:w="' . $w . '"/>';

        $border = '<w:tblBorders>'
                . '<w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '<w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '<w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                . '</w:tblBorders>';
        $tblPr = '<w:tblPr><w:tblW w:w="14646" w:type="dxa"/>' . $border . '</w:tblPr>';

        // หัวตาราง (ตัวหนา + กึ่งกลาง + ซ้ำทุกหน้า)
        $head = '';
        foreach ($headers as $i => $h) {
            $head .= self::cell(self::COLW[$i], self::run(self::esc((string) $h), true), 'center');
        }
        $rowsXml = '<w:tr><w:trPr><w:tblHeader/></w:trPr>' . $head . '</w:tr>';

        // แถวข้อมูล
        $n = 0;
        foreach ($dataRows as $row) {
            $n++;
            $cells = self::cell(self::COLW[0], self::run((string) $n), 'center');
            foreach (array_values($row) as $k => $val) {
                $j = $k + 1;   // เลื่อนเพราะคอลัมน์ 0 = ลำดับ
                $cells .= self::cell(self::COLW[$j], self::run(self::esc((string) $val)), $isCenter($j) ? 'center' : 'left');
            }
            $rowsXml .= '<w:tr>' . $cells . '</w:tr>';
        }

        return '<w:tbl>' . $tblPr . '<w:tblGrid>' . $grid . '</w:tblGrid>' . $rowsXml . '</w:tbl>';
    }

    // ---------- helpers ----------

    /** จัดกลุ่ม + เรียงคีย์กลุ่ม (ชื่อเขต) */
    private static function groupBy(iterable $rows, callable $keyFn): array
    {
        $groups = [];
        foreach ($rows as $r) $groups[$keyFn($r)][] = $r;
        uksort($groups, fn($a, $b) => strcmp($a, $b));
        return $groups;
    }

    /** คีย์เรียงในเขต: จังหวัด → อำเภอ → ตำบล → ชื่อ (รองรับทั้ง schema ผลคัดกรอง/คงอยู่) */
    private static function locKey(array $r): array
    {
        return [
            self::col($r, 'provinces', 'province', 'm_provinces'),
            self::col($r, 'district'),
            self::col($r, 'subdistrict'),
            self::col($r, 'sc_names', 'sc_name', 'm_sc_name'),
        ];
    }

    private static function cell(int $w, string $runXml, string $align = 'left'): string
    {
        return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
             . self::para($runXml, $align) . '</w:tc>';
    }

    private static function para(string $runXml, string $align = 'left', array $opt = []): string
    {
        $pPr = '';
        if (!empty($opt['pageBreak'])) $pPr .= '<w:pageBreakBefore/>';
        if (!empty($opt['keepNext']))  $pPr .= '<w:keepNext/>';
        $sp = '';
        if (isset($opt['before'])) $sp .= ' w:before="' . (int) $opt['before'] . '"';
        if (isset($opt['after']))  $sp .= ' w:after="' . (int) $opt['after'] . '"';
        $pPr .= '<w:spacing' . ($sp !== '' ? $sp : ' w:after="0"') . ' w:line="300" w:lineRule="exact"/>';
        if ($align !== 'left') $pPr .= '<w:jc w:val="' . $align . '"/>';
        return '<w:p><w:pPr>' . $pPr . '</w:pPr>' . $runXml . '</w:p>';
    }

    /** run ข้อความ (Thai complex-script + Angsana New) — $text ต้อง escape มาก่อน */
    private static function run(string $text, bool $bold = false, int $sz = 32): string
    {
        $rPr = '<w:rPr><w:rFonts w:ascii="Angsana New" w:hAnsi="Angsana New" w:cs="Angsana New"/>'
             . ($bold ? '<w:b/><w:bCs/>' : '')
             . '<w:sz w:val="' . $sz . '"/><w:szCs w:val="' . $sz . '"/><w:cs/></w:rPr>';
        return '<w:r>' . $rPr . '<w:t xml:space="preserve">' . $text . '</w:t></w:r>';
    }

    /** อ่านค่าคอลัมน์ตัวแรกที่ไม่ว่าง (รองรับคอลัมน์สำรอง) แล้ว trim */
    private static function col(array $r, string ...$keys): string
    {
        foreach ($keys as $k) {
            $v = trim((string) ($r[$k] ?? ''));
            if ($v !== '') return $v;
        }
        return '';
    }

    /** เติม "โรงเรียน" หน้าชื่อถ้ายังไม่มี */
    private static function schoolName(string $name): string
    {
        return ($name !== '' && mb_strpos($name, 'โรงเรียน') !== 0) ? 'โรงเรียน' . $name : $name;
    }

    /**
     * ขยายชื่อย่อสำนักงานเขต (สพป./สพม.) เป็นชื่อเต็มแบบเอกสารราชการ
     * ข้อมูล master_sao เขียนไม่สม่ำเสมอ ("สพป.ระยอง เขต 1" vs "สพป .พังงา " มีช่องว่างคั่นจุด/ท้ายชื่อ)
     * จึงยอมให้มีช่องว่างรอบจุดได้ แล้ว trim ท้ายทิ้ง
     */
    private static function officeName(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '(ไม่ระบุสำนักงานเขตพื้นที่การศึกษา)';
        $s = preg_replace('/^สพป\s*\.?\s*/u', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษา', $s);
        $s = preg_replace('/^สพม\s*\.?\s*/u', 'สำนักงานเขตพื้นที่การศึกษามัธยมศึกษา', $s);
        return trim($s);
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** ประกอบ .docx (ZipArchive) คืนเป็นสตริงไบต์ */
    private static function pack(string $documentXml): string
    {
        $ctypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Angsana New" w:hAnsi="Angsana New" w:cs="Angsana New"/>'
            . '<w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr></w:rPrDefault>'
            . '<w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr></w:pPrDefault>'
            . '</w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            . '<w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/><w:tblPr/></w:style>'
            . '</w:styles>';

        $tmp = tempnam(sys_get_temp_dir(), 'nhdocx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $ctypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->close();

        $data = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    /** สตรีมไบต์ .docx ออก HTTP (จบด้วย exit) */
    private static function stream(string $data, string $filename): void
    {
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: max-age=0');
        echo $data;
        exit;
    }
}
