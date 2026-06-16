<?php
/**
 * Integration: กรองเบื้องต้นตามจังหวัด (SchoolConfirm::eligibleProvinces / isEligibleSchool)
 * อ่านอย่างเดียว ไม่แก้ข้อมูล — ตรวจว่าจังหวัดพื้นที่สูง/เกาะแยกกันถูกต้อง
 */

use App\Core\App;
use App\Models\SchoolConfirm;

T::group('Integration: กรองเบื้องต้นตามจังหวัด (DB)');

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

$yr = App::acadYear();
$hi = SchoolConfirm::eligibleProvinces(SchoolConfirm::AREA_HIGHLAND, $yr);
$is = SchoolConfirm::eligibleProvinces(SchoolConfirm::AREA_ISLAND, $yr);

T::true(count($hi) >= 10, 'พื้นที่สูงมีหลายจังหวัด (พบ ' . count($hi) . ')');
T::true(count($is) >= 5,  'พื้นที่เกาะมีหลายจังหวัด (พบ ' . count($is) . ')');

// จังหวัดภูเขา ต้องอยู่ในชุดพื้นที่สูง แต่ไม่อยู่ในชุดพื้นที่เกาะ
T::true(in_array('เชียงใหม่', $hi, true), 'เชียงใหม่ = จังหวัดพื้นที่สูง');
T::true(!in_array('เชียงใหม่', $is, true), 'เชียงใหม่ ไม่ใช่จังหวัดพื้นที่เกาะ');

// จังหวัดเกาะ ต้องอยู่ในชุดพื้นที่เกาะ แต่ไม่อยู่ในชุดพื้นที่สูง
T::true(in_array('ภูเก็ต', $is, true), 'ภูเก็ต = จังหวัดพื้นที่เกาะ');
T::true(!in_array('ภูเก็ต', $hi, true), 'ภูเก็ต ไม่ใช่จังหวัดพื้นที่สูง');

// โรงเรียนพื้นที่สูงเดิม (สพป.เชียงใหม่ เขต 3) เข้าเกณฑ์พื้นที่สูง แต่ไม่เข้าเกณฑ์พื้นที่เกาะ
$scHigh = '1050130502';   // ไทยรัฐวิทยา 12 (บ้านเอก) — รายชื่อเดิมพื้นที่สูง
T::true(SchoolConfirm::isEligibleSchool(SchoolConfirm::AREA_HIGHLAND, $yr, $scHigh), 'โรงเรียนพื้นที่สูงเดิม เข้าเกณฑ์พื้นที่สูง');
T::true(!SchoolConfirm::isEligibleSchool(SchoolConfirm::AREA_ISLAND, $yr, $scHigh), 'โรงเรียนพื้นที่สูงเดิม ไม่เข้าเกณฑ์พื้นที่เกาะ');
