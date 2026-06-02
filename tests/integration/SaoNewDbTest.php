<?php
/** Integration: SaoNew (dropdown สังกัด) — อ่านอย่างเดียว ไม่แก้ข้อมูล */

use App\Core\App;
use App\Models\SaoNew;

T::group('Integration: SaoNew — dropdown สังกัด (DB)');

if (App::config('db') === null) {
    App::boot(dirname(__DIR__, 2));
}
$cfg = App::config('db');
try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
    new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
} catch (\Throwable $e) {
    T::skip('เชื่อมต่อฐานข้อมูลไม่ได้ — ข้าม (' . $e->getMessage() . ')');
    return;
}

$all = SaoNew::all();
T::true(count($all) > 200, 'มีรายการสังกัด > 200 (พบ ' . count($all) . ')');
T::true(isset($all[0]['areacode'], $all[0]['sao']), 'แต่ละแถวมี areacode + sao');

// round-trip ด้วย PK ที่คงที่
T::eq('สพป.เชียงราย เขต 3', SaoNew::name('57030000'), 'name(57030000) = สพป.เชียงราย เขต 3');
T::eq('57030000', SaoNew::areacodeByName('สพป.เชียงราย เขต 3'), 'areacodeByName ย้อนกลับได้');
T::eq('', SaoNew::name('ไม่มีจริง999'), 'areacode ไม่มี → คืนค่าว่าง');
