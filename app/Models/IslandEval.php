<?php
namespace App\Models;

use App\Core\Db;

/** แบบประเมินพื้นที่เกาะ — island_eval (คีย์เชิงตรรกะ sc_id + acadyears); upsert แบบ explicit */
class IslandEval
{
    private const FORM_FIELDS = [
        'sc_names', 'director_name', 'director_tel', 'editor_name', 'editor_tel',
        'sao_names', 'adresss', 'viledges', 'moo', 'subdistrict', 'district', 'provinces', 'lgo', 'admin',
        'stu_kinder', 'stu_prim', 'stu_second', 'stu_high', 'stu_sum',
        'teacher', 'panuk', 'gov_employee', 'perm_teacher', 'perm_employee', 'suport_teaching', 'sum_teacher',
        'lat', 'lng', 'distance_to_province',
        'citeria01', 'citeria02', 'citeria03', 'citeria04', 'citeria05', 'citeria06', 'citeria07', 'citeria08',
        'citeria09', 'citeria10', 'citeria11', 'citeria12', 'citeria13', 'citeria14', 'citeria15',
        'citeria04_refdoc', 'citeria09_refdoc', 'citeria10_refdoc', 'citeria11_refdoc',
        'citeria12_refdoc', 'citeria13_refdoc', 'citeria14_refdoc',
    ];
    private const SCORE_FIELDS = [
        'score01','score02','score03','score04','score05','score06','score07','score08',
        'score09','score10','score11','score12','score13','score14','score15','sum_score','island_type',
    ];
    private const CERT_FIELDS = ['confirmstatus', 'confirmcomment', 'spt_commit', 'spt_comment'];

    public static function find(int $scId, int $year): ?array
    {
        return Db::one('SELECT * FROM island_eval WHERE sc_id = ? AND acadyears = ? LIMIT 1', [$scId, $year]);
    }
    public static function exists(int $scId, int $year): bool
    {
        return (bool) Db::scalar('SELECT 1 FROM island_eval WHERE sc_id = ? AND acadyears = ? LIMIT 1', [$scId, $year]);
    }

    /** สร้างแถวเริ่มต้นถ้ายังไม่มี (ตอนเริ่มประเมิน) */
    public static function ensure(int $scId, int $year, string $scName, string $province): void
    {
        if (!self::exists($scId, $year)) {
            self::insertRaw(['sc_id' => $scId, 'acadyears' => $year, 'sc_names' => $scName, 'provinces' => $province]);
        }
    }

    public static function save(int $scId, int $year, array $form, array $scores): void
    {
        $data = [];
        foreach (self::FORM_FIELDS as $c) if (array_key_exists($c, $form)) $data[$c] = $form[$c];
        foreach (self::SCORE_FIELDS as $c) if (array_key_exists($c, $scores)) $data[$c] = $scores[$c];
        if (!$data) return;
        if (self::exists($scId, $year)) self::updateRaw($scId, $year, $data);
        else { $data['sc_id'] = $scId; $data['acadyears'] = $year; self::insertRaw($data); }
    }

    public static function saveCert(int $scId, int $year, array $fields): void
    {
        $data = [];
        foreach (self::CERT_FIELDS as $c) if (array_key_exists($c, $fields)) $data[$c] = $fields[$c];
        if ($data && self::exists($scId, $year)) self::updateRaw($scId, $year, $data);
    }

    /**
     * รายการประเมินพื้นที่เกาะที่ "เสร็จแล้ว" (มี sum_score) สำหรับให้ สพท./สพฐ. รับรอง — ขั้นที่ 4
     * JOIN master_school เพื่อกรองตามสังกัด (sao_code) และดึงชื่อ/จังหวัดเป็นทุนสำรอง
     * @param int|null $saoId null = ทุกเขต (สพฐ.)
     * @param array    $f     ['q','province','status']
     */
    public static function listForCert(?int $saoId, int $year, array $f = []): array
    {
        $sql = 'SELECT e.sc_id, e.sc_names, e.sao_names, e.provinces, e.sum_score, e.island_type,
                       e.confirmstatus, e.confirmcomment, e.spt_commit, e.spt_comment,
                       m.sc_name, m.provinces AS m_provinces
                  FROM island_eval e
             LEFT JOIN master_school m ON m.sc_id = e.sc_id
                 WHERE e.acadyears = ? AND e.sum_score IS NOT NULL';
        $p = [$year];
        if ($saoId !== null)        { $sql .= ' AND m.sao_code = ?'; $p[] = $saoId; }
        if (!empty($f['province'])) { $sql .= ' AND (e.provinces LIKE ? OR m.provinces LIKE ?)'; $p[] = '%' . $f['province'] . '%'; $p[] = '%' . $f['province'] . '%'; }
        if (isset($f['status']) && $f['status'] !== '') { $sql .= ' AND COALESCE(e.confirmstatus,0) = ?'; $p[] = (int) $f['status']; }
        if (!empty($f['q']))        { $sql .= ' AND (e.sc_names LIKE ? OR m.sc_name LIKE ? OR e.sc_id LIKE ?)'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; $p[] = '%' . $f['q'] . '%'; }
        $sql .= ' ORDER BY COALESCE(e.confirmstatus,0), e.provinces, e.sc_names LIMIT 2000';
        return Db::all($sql, $p);
    }

    /** สถิติการ์ดสรุป (รออนุมัติ/รับรองแล้ว/ไม่รับรอง) — เฉพาะที่ประเมินเสร็จ */
    public static function certStats(?int $saoId, int $year): array
    {
        $sql = 'SELECT COUNT(*) total,
                       SUM(COALESCE(e.confirmstatus,0)=0) pending,
                       SUM(COALESCE(e.confirmstatus,0)=1) approved,
                       SUM(COALESCE(e.confirmstatus,0)=2) rejected
                  FROM island_eval e';
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
        $p = array_values($data); $p[] = $scId; $p[] = $year;
        Db::exec("UPDATE island_eval SET $set WHERE sc_id = ? AND acadyears = ?", $p);
    }
    private static function insertRaw(array $data): void
    {
        $cols = array_keys($data);
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $list = implode(', ', array_map(fn($c) => "`$c`", $cols));
        Db::exec("INSERT INTO island_eval ($list) VALUES ($ph)", array_values($data));
    }
}
