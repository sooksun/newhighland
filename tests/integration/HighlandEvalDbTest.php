<?php
/**
 * Integration: HighlandEval (model) + ScoreService + SchoolConfirm ผ่านฐานข้อมูลจริง
 * ความปลอดภัย: ใช้ sc_id/acadyears แบบ sentinel (ไม่มีจริง) และลบทิ้งทุกครั้ง — ไม่แตะข้อมูลจริง
 * จะ SKIP ทั้งหมดถ้าเชื่อมต่อฐานข้อมูลไม่ได้
 */

use App\Core\App;
use App\Core\Db;
use App\Models\HighlandEval;
use App\Models\SchoolConfirm;
use App\Services\ScoreService;

T::group('Integration: HighlandEval + ScoreService + SchoolConfirm (DB)');

// boot config (ครั้งเดียว) — project root = newhighland/
if (App::config('db') === null) {
    App::boot(dirname(__DIR__, 2));
}

// ตรวจการเชื่อมต่อแบบไม่ทำให้ทั้ง suite ตาย
$cfg = App::config('db');
try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
    new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
} catch (\Throwable $e) {
    T::skip('เชื่อมต่อฐานข้อมูลไม่ได้ — ข้าม integration (' . $e->getMessage() . ')');
    return;
}

$SC = 999999001;   // sentinel — ไม่มีในระบบจริง
$YR = 9999;

// teardown helper
$cleanup = function () use ($SC, $YR) {
    Db::exec('DELETE FROM highland_eval WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    Db::exec('DELETE FROM highland_eval_hilltrib WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    Db::exec('DELETE FROM school_confirm WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
};

try {
    $cleanup(); // เผื่อมีเศษค้างจากรอบก่อน

    // --- upsertGeo: สร้างแถวจากขั้นแผนที่ ---
    T::false(HighlandEval::exists($SC, $YR), 'เริ่มต้น: ยังไม่มีแถว');
    HighlandEval::upsertGeo($SC, $YR, [
        'sc_names' => 'TEST_SENTINEL', 'lat' => '16.9', 'lng' => '98.6',
        'highest' => 946, 'distance' => 136, 'average_height' => 258,
    ]);
    T::true(HighlandEval::exists($SC, $YR), 'upsertGeo สร้างแถวสำเร็จ');
    $row = HighlandEval::find($SC, $YR);
    T::close(946, (float) $row['highest'], 'highest ถูกบันทึก');
    T::close(946, (float) $row['citeria01'], 'citeria01 = highest');
    T::close(258, (float) $row['average_height'], 'average_height ถูกบันทึก');

    // --- upsertGeo อีกครั้ง (update ไม่ใช่ insert ซ้ำ) ---
    HighlandEval::upsertGeo($SC, $YR, ['sc_names' => 'TEST2', 'highest' => 900, 'distance' => 100, 'average_height' => 258]);
    $cnt = (int) Db::scalar('SELECT COUNT(*) FROM highland_eval WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    T::eq(1, $cnt, 'upsert ซ้ำ → ยังมีแถวเดียว (ไม่ duplicate)');

    // --- hilltrib add ---
    HighlandEval::addHilltrib($SC, $YR, 1, 153);
    HighlandEval::addHilltrib($SC, $YR, 16, 16);
    $hrows = HighlandEval::hilltribRows($SC, $YR);
    T::eq(2, count($hrows), 'hilltrib: 2 กลุ่ม');
    // add ซ้ำกลุ่มเดิม → แทนที่ ไม่เพิ่ม
    HighlandEval::addHilltrib($SC, $YR, 1, 100);
    $hrows2 = HighlandEval::hilltribRows($SC, $YR);
    T::eq(2, count($hrows2), 'hilltrib add ซ้ำกลุ่มเดิม → ยัง 2 กลุ่ม');

    // --- ScoreService + save round-trip ---
    HighlandEval::upsertGeo($SC, $YR, ['sc_names' => 'TEST', 'highest' => 946, 'distance' => 136, 'average_height' => 258]);
    HighlandEval::deleteHilltrib($SC, $YR, 1);
    HighlandEval::addHilltrib($SC, $YR, 1, 153);
    $eval = HighlandEval::find($SC, $YR);
    $form = [
        'stu_kinder' => 58, 'stu_prim' => 111, 'stu_second' => 0, 'stu_high' => 0, 'stu_sum' => 169,
        'citeria02' => 3, 'citeria03' => 1, 'citeria04' => 2, 'citeria041' => 0, 'citeria06' => 1,
        'citeria07' => '1,2,3', 'citeria08' => 2, 'citeria09' => 3, 'citeria10' => '3,4',
        'citeria13' => 53, 'citeria14' => 0, 'citeria15' => 0, 'citeria16' => 1,
    ];
    $merged = array_merge($eval, $form);
    $scores = ScoreService::calcHighland($merged, HighlandEval::hilltribRows($SC, $YR));
    HighlandEval::save($SC, $YR, $form, $scores);

    $saved = HighlandEval::find($SC, $YR);
    T::close(68.14, (float) $saved['sum_score'], 'sum_score persist = 68.14');
    T::eq(2, (int) $saved['highland_type'], 'highland_type persist = 2');
    T::close(3.14, (float) $saved['score13'], 'score13 persist = 3.14');
    T::eq(169, (int) $saved['stu_sum'], 'stu_sum persist = 169');

    // --- cert (สพท./สพฐ.) ---
    HighlandEval::saveCert($SC, $YR, ['confirmstatus' => 1, 'confirmcomment' => 'qa-ok']);
    $c = HighlandEval::find($SC, $YR);
    T::eq(1, (int) $c['confirmstatus'], 'เขตรับรอง → confirmstatus = 1');
    T::eq('qa-ok', $c['confirmcomment'], 'confirmcomment persist');

    // --- SchoolConfirm flow ---
    Db::exec('INSERT INTO school_confirm (sc_id, acadyears, area_type, sc_name, provinces, sao_id) VALUES (?,?,?,?,?,?)',
        [$SC, $YR, SchoolConfirm::AREA_HIGHLAND, 'TEST_SENTINEL', 'ตาก', 126]);
    $confirmRow = Db::one('SELECT id FROM school_confirm WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    $cid = (int) $confirmRow['id'];
    SchoolConfirm::schoolConfirm($cid, 1, 1, null, 'ยังเปิด');
    $sc1 = SchoolConfirm::get($cid);
    T::eq(1, (int) $sc1['school_confirmed'], 'โรงเรียนยืนยัน → school_confirmed = 1');
    T::eq(1, (int) $sc1['opened'], 'opened = 1 (คงอยู่)');
    SchoolConfirm::saoCert($cid, 1, 'รับรอง');
    $sc2 = SchoolConfirm::get($cid);
    T::eq(1, (int) $sc2['sao_status'], 'เขตรับรอง school_confirm → sao_status = 1');

} finally {
    $cleanup();
    // ยืนยันว่าล้างหมด
    $leftEval = (int) Db::scalar('SELECT COUNT(*) FROM highland_eval WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    $leftConf = (int) Db::scalar('SELECT COUNT(*) FROM school_confirm WHERE sc_id = ? AND acadyears = ?', [$SC, $YR]);
    T::eq(0, $leftEval + $leftConf, 'teardown: ลบข้อมูล sentinel หมดแล้ว');
}
