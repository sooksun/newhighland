<?php
namespace App\Models;

use App\Core\Db;

/**
 * แบบประเมินพื้นที่สูง — highland_eval (คีย์เชิงตรรกะ: sc_id + acadyears)
 * หมายเหตุ: ตารางเดิมไม่มี PRIMARY/UNIQUE key จึงทำ upsert แบบ explicit (เช็คก่อน insert/update)
 * เพื่อเลี่ยงแถวซ้ำ (แทน ON DUPLICATE KEY ของระบบเดิม — PRD §10)
 */
class HighlandEval
{
    /** คอลัมน์ที่อนุญาตให้อัปเดตจากฟอร์ม (กัน mass assignment) */
    private const FORM_FIELDS = [
        // ข้อมูลทั่วไป
        'sc_names', 'director_name', 'director_tel', 'editor_name', 'editor_tel',
        'sao_names', 'adresss', 'viledges', 'moo', 'subdistrict', 'district', 'provinces', 'lgo',
        // นักเรียน
        'stu_kinder', 'stu_prim', 'stu_second', 'stu_high', 'stu_sum',
        'stu_sleep_boy', 'stu_sleep_girl', 'stu_sleep_sum',
        // คำตอบเกณฑ์ (ข้อ 01,05,11,12 มาจากระบบ/คำนวณ)
        'citeria02', 'citeria03', 'citeria04', 'citeria041', 'citeria06',
        'citeria07', 'citeria08', 'citeria09', 'citeria10',
        'citeria13', 'citeria14', 'citeria15', 'citeria16',
        // เหตุผลที่ขอคัดกรองครั้งนี้ (สั้น ๆ ≤255 ตัวอักษร)
        'screen_reason',
        // เอกสารแนบ
        'citeria02_refdoc', 'citeria03_refdoc', 'citeria04_refdoc', 'citeria06_refdoc',
        'citeria07_refdoc', 'citeria08_refdoc', 'citeria09_refdoc', 'citeria10_refdoc',
        'citeria13_refdoc', 'citeria14_refdoc', 'citeria15_refdoc', 'citeria16_refdoc',
    ];

    /** คอลัมน์คะแนน/ค่าที่คำนวณโดย ScoreService */
    private const SCORE_FIELDS = [
        'score01', 'score02', 'score03', 'score04', 'score05', 'score06', 'score07', 'score08',
        'score09', 'score10', 'score11', 'score12', 'score13', 'score14', 'score15', 'score16',
        'sum_score', 'highland_type', 'citeria11', 'citeria12', 'stu_hilltrib', 'stu_hilltrib_group',
    ];

    /** คอลัมน์การรับรอง (สพท. = confirmstatus/confirmcomment, สพฐ. = spt_commit/spt_comment) */
    private const CERT_FIELDS = ['confirmstatus', 'confirmcomment', 'spt_commit', 'spt_comment', 'fail_reason'];

    public static function find(int $scId, int $year): ?array
    {
        return Db::one('SELECT * FROM highland_eval WHERE sc_id = ? AND acadyears = ? LIMIT 1', [$scId, $year]);
    }

    public static function exists(int $scId, int $year): bool
    {
        return (bool) Db::scalar('SELECT 1 FROM highland_eval WHERE sc_id = ? AND acadyears = ? LIMIT 1', [$scId, $year]);
    }

    /**
     * บันทึกค่าพิกัด/ความสูง/ระยะทาง จากขั้นตอนแผนที่ (แทน map_upcomment.php)
     * $geo: ['sc_names','lat','lng','highest','average_height','distance']
     */
    public static function upsertGeo(int $scId, int $year, array $geo): void
    {
        $fields = [
            'sc_names'             => (string) ($geo['sc_names'] ?? ''),
            'lat'                  => (string) ($geo['lat'] ?? ''),
            'lng'                  => (string) ($geo['lng'] ?? ''),
            'highest'              => (float)  ($geo['highest'] ?? 0),
            'citeria01'            => (float)  ($geo['highest'] ?? 0),
            'distance_to_province' => (float)  ($geo['distance'] ?? 0),
            'citeria05'            => (int)    round((float) ($geo['distance'] ?? 0)),
            'average_height'       => (float)  ($geo['average_height'] ?? 0),
        ];

        if (self::exists($scId, $year)) {
            self::updateRaw($scId, $year, $fields);
        } else {
            $fields['sc_id']     = $scId;
            $fields['acadyears'] = $year;
            self::insertRaw($fields);
        }
    }

    /** อัปเดตค่าจากฟอร์ม + คะแนน (เฉพาะคอลัมน์ในรายการอนุญาต) */
    public static function save(int $scId, int $year, array $form, array $scores): void
    {
        $data = [];
        foreach (self::FORM_FIELDS as $c) {
            if (array_key_exists($c, $form)) $data[$c] = $form[$c];
        }
        foreach (self::SCORE_FIELDS as $c) {
            if (array_key_exists($c, $scores)) $data[$c] = $scores[$c];
        }
        if (!$data) return;

        if (self::exists($scId, $year)) {
            self::updateRaw($scId, $year, $data);
        } else {
            $data['sc_id'] = $scId;
            $data['acadyears'] = $year;
            self::insertRaw($data);
        }
    }

    /** บันทึกการรับรอง (สพท./สพฐ.) — เฉพาะคอลัมน์รับรอง */
    public static function saveCert(int $scId, int $year, array $fields): void
    {
        $data = [];
        foreach (self::CERT_FIELDS as $c) {
            if (array_key_exists($c, $fields)) $data[$c] = $fields[$c];
        }
        if ($data && self::exists($scId, $year)) {
            self::updateRaw($scId, $year, $data);
        }
    }

    /**
     * รายการประเมินที่ "เสร็จแล้ว" (มี sum_score) สำหรับให้ สพท./สพฐ. รับรอง — ขั้นที่ 5
     * JOIN master_school เพื่อกรองตามสังกัด (sao_code) และดึงชื่อโรงเรียน/จังหวัดเป็นทุนสำรอง
     * @param int|null $saoId  null = ทุกเขต (สพฐ.), มีค่า = เฉพาะเขตนั้น
     * @param array    $f      ['q','province','status']
     */
    public static function listForCert(?int $saoId, int $year, array $f = []): array
    {
        $sql = 'SELECT e.sc_id, e.sc_names, e.sao_names, e.provinces, e.sum_score, e.highland_type,
                       e.confirmstatus, e.confirmcomment, e.spt_commit, e.spt_comment, e.citeria16_refdoc,
                       m.sc_name, m.provinces AS m_provinces
                  FROM highland_eval e
             LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL';
        $p = [$year];
        if ($saoId !== null)              { $sql .= ' AND m.sao_code = ?'; $p[] = $saoId; }
        if (!empty($f['province']))       { $sql .= ' AND (e.provinces LIKE ? OR m.provinces LIKE ?)'; $p[] = '%' . $f['province'] . '%'; $p[] = '%' . $f['province'] . '%'; }
        if (isset($f['status']) && $f['status'] !== '') { $sql .= ' AND COALESCE(e.confirmstatus,0) = ?'; $p[] = (int) $f['status']; }
        if (!empty($f['q']))              { $sql .= ' AND (e.sc_names LIKE ? OR m.sc_name LIKE ? OR e.sc_id LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
        $sql .= ' ORDER BY COALESCE(e.confirmstatus,0), e.provinces, e.sc_names LIMIT 2000';
        return Db::all($sql, $p);
    }

    /**
     * ทุกแถวประเมินพื้นที่สูง (ไม่กรอง sum_score) — สำหรับส่งออกรายงาน Excel
     * @param int|null $saoId null = ทุกเขต (สพฐ.)
     */
    public static function listForExport(?int $saoId, int $year): array
    {
        $sql = 'SELECT e.*, m.sc_name AS m_sc_name, m.provinces AS m_provinces
                  FROM highland_eval e
             LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ?';
        $p = [$year];
        if ($saoId !== null) { $sql .= ' AND m.sao_code = ?'; $p[] = $saoId; }
        $sql .= ' ORDER BY e.provinces, e.sc_names LIMIT 20000';
        return Db::all($sql, $p);
    }

    /** สถิติสำหรับการ์ดสรุป (รออนุมัติ/รับรองแล้ว/ไม่รับรอง) — นับเฉพาะที่ประเมินเสร็จ */
    public static function certStats(?int $saoId, int $year): array
    {
        $sql = 'SELECT COUNT(*) total,
                       SUM(COALESCE(e.confirmstatus,0)=0) pending,
                       SUM(COALESCE(e.confirmstatus,0)=1) approved,
                       SUM(COALESCE(e.confirmstatus,0)=2) rejected
                  FROM highland_eval e';
        $p = [$year];
        if ($saoId !== null) {
            $sql .= ' LEFT JOIN master_school m ON m.sc_id = e.sc_id
                       WHERE e.acadyears = ? AND e.sum_score IS NOT NULL AND m.sao_code = ?';
            $p[] = $saoId;
        } else {
            $sql .= ' WHERE e.acadyears = ? AND e.sum_score IS NOT NULL';
        }
        return Db::one($sql, $p) ?? [];
    }

    private static function updateRaw(int $scId, int $year, array $data): void
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $scId;
        $params[] = $year;
        Db::exec("UPDATE highland_eval SET $set WHERE sc_id = ? AND acadyears = ?", $params);
    }

    private static function insertRaw(array $data): void
    {
        $cols = array_keys($data);
        $ph   = implode(', ', array_fill(0, count($cols), '?'));
        $list = implode(', ', array_map(fn($c) => "`$c`", $cols));
        Db::exec("INSERT INTO highland_eval ($list) VALUES ($ph)", array_values($data));
    }

    // ---- กลุ่มชาติพันธุ์ (highland_eval_hilltrib) ----

    public static function hilltribRows(int $scId, int $year): array
    {
        return Db::all(
            'SELECT h.sc_id, h.acadyears, h.hilltrib, h.hilltrib_number, TRIM(t.ethnic) AS ethnic
               FROM highland_eval_hilltrib h
               LEFT JOIN hilltrib t ON t.ethnic_id = h.hilltrib
              WHERE h.sc_id = ? AND h.acadyears = ? ORDER BY h.hilltrib',
            [$scId, $year]
        );
    }

    public static function addHilltrib(int $scId, int $year, int $ethnicId, int $number): void
    {
        // กันซ้ำ: ลบของเดิมกลุ่มเดียวกันก่อน
        Db::exec('DELETE FROM highland_eval_hilltrib WHERE sc_id = ? AND acadyears = ? AND hilltrib = ?',
            [$scId, $year, $ethnicId]);
        Db::exec('INSERT INTO highland_eval_hilltrib (sc_id, acadyears, hilltrib, hilltrib_number) VALUES (?,?,?,?)',
            [$scId, $year, $ethnicId, $number]);
    }

    public static function deleteHilltrib(int $scId, int $year, int $ethnicId): void
    {
        Db::exec('DELETE FROM highland_eval_hilltrib WHERE sc_id = ? AND acadyears = ? AND hilltrib = ?',
            [$scId, $year, $ethnicId]);
    }
}
