<?php
namespace App\Models;

use App\Core\Db;

/** การรับรองการคงอยู่ (school_confirm) — area_type 1=พื้นที่สูง, 2=พื้นที่เกาะ */
class SchoolConfirm
{
    public const AREA_HIGHLAND = 1;
    public const AREA_ISLAND   = 2;

    /** ฟิลด์ข้อมูลที่โรงเรียนกรอกตอนยืนยันการคงอยู่ (mass-assignment allow-list) */
    public const PROFILE_TEXT_FIELDS = ['director_name', 'director_phone', 'informant_name', 'informant_phone'];
    public const PROFILE_INT_FIELDS  = ['std_male', 'std_female', 'tch_govt', 'tch_hire', 'tch_deputy', 'tch_director'];

    /** ประเภทการเลิกสถานศึกษา (เมื่อ opened=0) */
    public const CLOSE_TYPES = [1 => 'โรงเรียนยุบ', 2 => 'โรงเรียนเลิก', 3 => 'โรงเรียนเรียนรวม'];
    public const CLOSE_MERGE = 3;   // เรียนรวม → ระบุรหัส/ชื่อโรงเรียนที่ไปยุบรวมด้วย

    public static function closeTypeLabel(int $t): string
    {
        return self::CLOSE_TYPES[$t] ?? '-';
    }

    public static function areaLabel(int $a): string
    {
        return $a === self::AREA_ISLAND ? 'พื้นที่เกาะ' : 'พื้นที่ภูเขาสูงในถิ่นทุรกันดาร';
    }

    public static function get(int $id): ?array
    {
        return Db::one('SELECT * FROM school_confirm WHERE id = ?', [$id]);
    }

    /** แถวของโรงเรียนหนึ่งแห่ง (ทุก area) ในปีนั้น */
    public static function forSchool(int $scId, int $year): array
    {
        return Db::all(
            'SELECT * FROM school_confirm WHERE sc_id = ? AND acadyears = ? ORDER BY area_type',
            [$scId, $year]
        );
    }

    /** รายการสำหรับเขต/สพฐ. (กรอง area + สังกัด + จังหวัด + สถานะ) */
    public static function listFor(int $area, int $year, ?int $saoId, array $f = []): array
    {
        $sql = 'SELECT * FROM school_confirm WHERE acadyears = ? AND area_type = ?';
        $p = [$year, $area];
        if ($saoId !== null) { $sql .= ' AND sao_id = ?'; $p[] = $saoId; }
        if (!empty($f['province'])) { $sql .= ' AND provinces LIKE ?'; $p[] = '%' . $f['province'] . '%'; }
        if (isset($f['sao_status']) && $f['sao_status'] !== '') { $sql .= ' AND sao_status = ?'; $p[] = (int) $f['sao_status']; }
        if (isset($f['confirmed']) && $f['confirmed'] !== '') { $sql .= ' AND school_confirmed = ?'; $p[] = (int) $f['confirmed']; }
        if (!empty($f['q'])) { $sql .= ' AND (sc_name LIKE ? OR sc_id LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
        $sql .= ' ORDER BY provinces, sc_name LIMIT 2000';
        return Db::all($sql, $p);
    }

    public static function stats(int $area, int $year, ?int $saoId): array
    {
        $sql = 'SELECT COUNT(*) total,
                       SUM(school_confirmed=1) confirmed,
                       SUM(sao_status=1) approved,
                       SUM(opened=0) closed
                  FROM school_confirm WHERE acadyears = ? AND area_type = ?';
        $p = [$year, $area];
        if ($saoId !== null) { $sql .= ' AND sao_id = ?'; $p[] = $saoId; }
        return Db::one($sql, $p) ?? [];
    }

    /**
     * โรงเรียนยืนยันการคงอยู่ + บันทึกข้อมูลผู้บริหาร/ผู้กรอก/จำนวนนักเรียน-ครู
     * @param array $profile ค่าจากฟอร์ม (เขียนเฉพาะคอลัมน์ใน allow-list เท่านั้น)
     */
    public static function schoolConfirm(
        int $id, int $opened, ?int $mergeStatus, ?int $mergedTo, string $note,
        array $profile = [], int $closeType = 0, string $mergedToName = '', bool $submit = false
    ): void
    {
        $cols = ['close_type = ?', 'merged_to_name = ?'];
        $vals = [$closeType, mb_substr(trim($mergedToName), 0, 255)];
        foreach (self::PROFILE_TEXT_FIELDS as $f) {
            $cols[] = "$f = ?";
            $vals[] = mb_substr(trim((string) ($profile[$f] ?? '')), 0, 150);
        }
        foreach (self::PROFILE_INT_FIELDS as $f) {
            $cols[] = "$f = ?";
            $vals[] = max(0, (int) ($profile[$f] ?? 0));
        }
        // ยอดรวม คำนวณฝั่ง server เพื่อไม่ให้ขัดแย้งกับรายการแยก
        $cols[] = 'std_total = ?';
        $vals[] = max(0, (int) ($profile['std_male'] ?? 0)) + max(0, (int) ($profile['std_female'] ?? 0));
        $cols[] = 'tch_total = ?';
        $vals[] = array_sum(array_map(
            fn($f) => max(0, (int) ($profile[$f] ?? 0)),
            ['tch_govt', 'tch_hire', 'tch_deputy', 'tch_director']
        ));

        $set = implode(', ', $cols);
        // กด "ส่งข้อมูล" → ล็อก (submitted=1); บันทึกร่าง → submitted=0 (ยังแก้ไขได้)
        $lock = $submit ? 'submitted = 1, submitted_at = NOW()' : 'submitted = 0, submitted_at = NULL';
        Db::exec(
            "UPDATE school_confirm SET opened = ?, merge_status = ?, merged_to = ?, school_note = ?, $set,
                    school_confirmed = 1, school_confirm_at = NOW(), $lock WHERE id = ?",
            array_merge([$opened, $mergeStatus, $mergedTo ?: null, $note], $vals, [$id])
        );
    }

    /** ส่งข้อมูลแล้วหรือยัง (ล็อกการแก้ไข) */
    public static function isLocked(?array $row): bool
    {
        return (int) ($row['submitted'] ?? 0) === 1;
    }

    /** เขต/สพฐ. ปลดล็อกให้โรงเรียนกลับมาแก้ไขได้ */
    public static function unlock(int $id): void
    {
        Db::exec('UPDATE school_confirm SET submitted = 0, submitted_at = NULL WHERE id = ?', [$id]);
    }

    /** เขตรับรอง */
    public static function saoCert(int $id, int $status, string $comment): void
    {
        Db::exec('UPDATE school_confirm SET sao_status = ?, sao_comment = ?, sao_at = NOW() WHERE id = ?',
            [$status, $comment, $id]);
    }

    /** สพฐ. */
    public static function sptCert(int $id, int $status, string $comment): void
    {
        Db::exec('UPDATE school_confirm SET spt_status = ?, spt_comment = ?, spt_at = NOW() WHERE id = ?',
            [$status, $comment, $id]);
    }

    public static function statusLabel(int $s): string
    {
        return [0 => 'รอรับรอง', 1 => 'รับรองแล้ว', 2 => 'ไม่รับรอง'][$s] ?? '-';
    }

    /**
     * กรองเบื้องต้น (PRD §2): จังหวัดที่ "เคยมี" โรงเรียนพื้นที่พิเศษประเภทนี้แล้ว
     * อ้างอิงรายชื่อเดิม (school_confirm) JOIN ทะเบียนโรงเรียนเพื่อแปลง sc_id → จังหวัดจริง
     * @param int $area 1=พื้นที่สูง, 2=พื้นที่เกาะ
     * @return string[] รายชื่อจังหวัด (ตรงรูปแบบ master_school.provinces)
     */
    public static function eligibleProvinces(int $area, int $year): array
    {
        // ใช้ school_new.province เป็นแหล่งจังหวัดหลัก (master_school.provinces ว่างในหลายแถว)
        $rows = Db::all(
            "SELECT DISTINCT s.province AS p
               FROM school_confirm c
               JOIN school_new s ON s.sc_id = c.sc_id
              WHERE c.area_type = ? AND c.acadyears = ? AND s.province <> ''
              ORDER BY s.province",
            [$area, $year]
        );
        return array_column($rows, 'p');
    }

    /** โรงเรียน (ตามจังหวัดที่ตั้ง) เข้าเกณฑ์คัดกรองประเภทนี้หรือไม่ */
    public static function isEligibleSchool(int $area, int $year, string $scId): bool
    {
        $prov = MasterSchool::provinceOf($scId);
        return $prov !== '' && in_array($prov, self::eligibleProvinces($area, $year), true);
    }

    /** โรงเรียนอยู่ในรายชื่อเดิม (roster) ของประเภทนี้แล้วหรือไม่ — ถ้าใช่ = ผ่านประเมินแล้ว ให้ทำ "รับรองการคงอยู่" แทน */
    public static function inRoster(int $area, int $year, string $scId): bool
    {
        return (bool) Db::scalar(
            'SELECT 1 FROM school_confirm WHERE sc_id = ? AND area_type = ? AND acadyears = ? LIMIT 1',
            [$scId, $area, $year]
        );
    }

    /** ประเภทพื้นที่ (area_type) ที่เขตหนึ่งดูแลอยู่ในปีนั้น — ใช้ auto menu filter ฝั่งเขต */
    public static function saoAreas(int $saoId, int $year): array
    {
        return array_map('intval', array_column(
            Db::all(
                'SELECT DISTINCT area_type FROM school_confirm WHERE sao_id = ? AND acadyears = ? ORDER BY area_type',
                [$saoId, $year]
            ),
            'area_type'
        ));
    }

    /** sc_id ทั้งหมดในรายชื่อเดิมของประเภทนี้ (ปีนั้น) — ใช้ทำ lookup ฝั่ง dropdown */
    public static function rosterScIds(int $area, int $year): array
    {
        return array_column(
            Db::all('SELECT sc_id FROM school_confirm WHERE area_type = ? AND acadyears = ?', [$area, $year]),
            'sc_id'
        );
    }
}
