<?php
/**
 * generate_report.php — สร้าง "รายงานผลการประเมินโรงเรียนพื้นที่ลักษณะพิเศษ" เป็นไฟล์ HTML เดี่ยว
 * (self-contained: กราฟทั้งหมดเป็น inline SVG ที่วาดด้วย PHP — ไม่พึ่ง CDN หรือ JS library ภายนอก)
 *
 * ครอบคลุมทั้งกลุ่มโรงเรียนพื้นที่สูง (16 ตัวชี้วัด) และกลุ่มโรงเรียนพื้นที่เกาะ (15 ตัวชี้วัด) ทุกมิติ:
 *   บทสรุปผู้บริหาร → เปรียบเทียบสองกลุ่ม → การรับรองการคงอยู่ → การกระจายคะแนน → ระดับความยุ่งยาก
 *   → คะแนนรายข้อ → การกระจายคำตอบ → สาธารณูปโภค → ภูมิภาค/จังหวัด/เขตพื้นที่ → ขนาดโรงเรียน
 *   → สถิติเชิงพรรณนา → สหสัมพันธ์ → ค่าเฉลี่ยตามระดับ → แผนภาพการกระจาย → กลุ่มชาติพันธุ์
 *   → อันดับโรงเรียน → แนวโน้มรายปี → คุณภาพข้อมูล → ภาคผนวกรายโรงเรียน → ระเบียบวิธี
 *
 * วิธีใช้ (CLI):
 *   php tools/report/generate_report.php                        # auto: ปีที่มีข้อมูลมากที่สุด
 *   php tools/report/generate_report.php --year=2569            # เจาะจงปีงบประมาณ
 *   php tools/report/generate_report.php --year=all             # รวมทุกปี
 *   php tools/report/generate_report.php --out=storage/x.html   # กำหนดไฟล์ผลลัพธ์
 *   php tools/report/generate_report.php --config=config/server/config.php
 *
 * บน production: อัปโหลดโฟลเดอร์ tools/report ขึ้นไปแล้วรันด้วย php CLI ของเครื่องนั้น
 * (สคริปต์จะเลือก config/server/config.php ให้อัตโนมัติถ้ามี) — ถ้าเข้า CLI ไม่ได้
 * ให้ใช้เมนู "รายงานฉบับเต็ม (HTML)" ในระบบ ซึ่งเรียกโค้ดชุดเดียวกันนี้ผ่านเว็บ
 */

declare(strict_types=1);
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('สคริปต์นี้รันได้เฉพาะทาง command line');
}

$ROOT = dirname(__DIR__, 2);

// ---------------------------------------------------------------- args ----
$opt = [];
foreach (array_slice($argv ?? [], 1) as $a) {
    if (preg_match('/^--([a-z_]+)(?:=(.*))?$/', $a, $m)) $opt[$m[1]] = $m[2] ?? '1';
}
$optYear   = $opt['year']   ?? 'auto';
$optOut    = $opt['out']    ?? null;
$optConfig = $opt['config'] ?? null;

$isAbs = static fn(string $p): bool => (bool) preg_match('#^([a-zA-Z]:)?[\\\\/]#', $p);

// -------------------------------------------------------------- config ----
$cfgFile = null;
foreach (array_filter([
    $optConfig ? ($isAbs($optConfig) ? $optConfig : $ROOT . '/' . $optConfig) : null,
    $ROOT . '/config/server/config.php',
    $ROOT . '/config/config.php',
]) as $c) {
    if (is_file($c)) { $cfgFile = $c; break; }
}
if (!$cfgFile) { fwrite(STDERR, "ไม่พบไฟล์ config\n"); exit(1); }
$CFG = require $cfgFile;
$db  = $CFG['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=" . ($db['charset'] ?? 'utf8mb4'),
        $db['user'], $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("SET SESSION sql_mode = ''");
} catch (Throwable $e) {
    fwrite(STDERR, 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . $e->getMessage() . "\n");
    exit(1);
}

require __DIR__ . '/build.php';

// --------------------------------------------------------------- build ----
$cfgLabel = str_replace($ROOT . DIRECTORY_SEPARATOR, '', $cfgFile);
fwrite(STDOUT, "• อ่านข้อมูลจาก {$db['name']} @ {$db['host']} ({$cfgLabel})\n");

try {
    $r = buildReport($pdo, $CFG, $optYear, $cfgLabel, static function (string $m): void {
        fwrite(STDOUT, '• ' . $m . "\n");
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'สร้างรายงานไม่สำเร็จ: ' . $e->getMessage() . "\n");
    exit(1);
}

// --------------------------------------------------------------- write ----
$outPath = $optOut ?: ('storage/reports/report_special_area_'
        . ($optYear === 'all' ? 'all' : ($r['years'][0] ?? 'x')) . '.html');
if (!$isAbs($outPath)) $outPath = $ROOT . '/' . $outPath;
@mkdir(dirname($outPath), 0775, true);
file_put_contents($outPath, $r['html']);

fwrite(STDOUT, '• เขียนไฟล์: ' . $outPath . ' (' . number_format(strlen($r['html']) / 1024, 1) . " KB)\n");
fwrite(STDOUT, "• เสร็จสิ้น\n");
