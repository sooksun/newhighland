<?php
/**
 * build.php — ประกอบ "บริบทรายงาน" (ctx) จากฐานข้อมูล แล้วคืนค่าเป็น HTML ทั้งหน้า
 * ใช้ร่วมกันทั้งฝั่ง CLI (tools/report/generate_report.php) และฝั่งเว็บ
 * (App\Controllers\FullReportController) — ตรรกะการดึงข้อมูล/วิเคราะห์อยู่ที่เดียว
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/charts.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/page.php';

/**
 * @param PDO    $pdo
 * @param array  $CFG      config array (ใช้ app_name / acad_year / db)
 * @param string $yearOpt  'auto' | 'all' | '2569'
 * @param string $cfgLabel ข้อความบอกที่มาของ config (แสดงบนหน้าปก)
 * @param callable|null $log ฟังก์ชันรับข้อความความคืบหน้า (CLI ใช้ ; เว็บส่ง null)
 * @return array{html:string, ctx:array, years:array, scopeLabel:string}
 */
function buildReport(PDO $pdo, array $CFG, string $yearOpt = 'auto', string $cfgLabel = '', ?callable $log = null): array
{
    $log = $log ?? static function (string $m): void {};
    $db  = $CFG['db'] ?? ['name' => '-', 'host' => '-', 'port' => ''];

    // ---- ขอบเขตปี ----
    $yearsAvail = availableYears($pdo);
    if ($yearOpt === 'all') {
        $years = [];
        $scopeLabel = 'ทุกปีงบประมาณที่มีข้อมูลในระบบ (' . implode(', ', array_reverse(array_keys($yearsAvail))) . ')';
    } elseif ($yearOpt === 'auto') {
        $best = null; $bestN = -1;
        foreach ($yearsAvail as $y => $n) { if ($n > $bestN) { $bestN = $n; $best = $y; } }
        if ($best === null) throw new RuntimeException('ไม่พบข้อมูลการประเมินในฐานข้อมูล');
        $years = [$best];
        $scopeLabel = 'ปีงบประมาณ ' . $best . ' (รอบที่มีข้อมูลการประเมินมากที่สุดในฐานข้อมูล)';
    } else {
        $years = [(int) $yearOpt];
        $scopeLabel = 'ปีงบประมาณ ' . (int) $yearOpt;
    }
    $log('ขอบเขต: ' . $scopeLabel);

    // ---- ดึงข้อมูล ----
    $GLOBALS['HIGH_OPTIONS'] = loadHighOptions($pdo);
    $rowsH = loadEvalRows($pdo, 1, $years);
    $rowsI = loadEvalRows($pdo, 2, $years);
    $eth   = loadEthnic($pdo, $years);
    $log('พื้นที่สูง ' . count($rowsH) . ' ระเบียน / พื้นที่เกาะ ' . count($rowsI) . ' ระเบียน');

    // ---- วิเคราะห์ ----
    $H = analyzeArea($rowsH, 1, $eth);
    $I = analyzeArea($rowsI, 2);

    // แนวโน้มรายปี — ทุกปีเสมอ ไม่ผูกกับขอบเขตด้านบน
    $trendH = yearSummary(q($pdo, 'SELECT acadyears, sum_score FROM highland_eval'));
    $trendI = yearSummary(q($pdo, 'SELECT acadyears, sum_score FROM island_eval'));

    $confirmYear = (int) ($CFG['acad_year'] ?? ($years[0] ?? 2569));
    $confirm = confirmSummary(loadConfirm($pdo, $confirmYear));

    $ctx = [
        'H' => $H, 'I' => $I,
        'trendH' => $trendH, 'trendI' => $trendI,
        'confirm' => $confirm,
        'meta' => [
            'appName'     => (string) ($CFG['app_name'] ?? 'ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ'),
            'generatedAt' => nowThai(),
            'dbName'      => (string) ($db['name'] ?? '-'),
            'dbHost'      => ($db['host'] ?? '-') . (isset($db['port']) ? ':' . $db['port'] : ''),
            'cfgFile'     => $cfgLabel,
            'scopeLabel'  => $scopeLabel,
            'confirmYear' => $confirmYear,
            'years'       => $years,
        ],
    ];

    return ['html' => renderPage($ctx), 'ctx' => $ctx, 'years' => $years, 'scopeLabel' => $scopeLabel];
}
