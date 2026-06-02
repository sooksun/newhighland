<?php
namespace App\Models;

use App\Core\Db;

/** การรับรองการคงอยู่ (school_confirm) — area_type 1=พื้นที่สูง, 2=พื้นที่เกาะ */
class SchoolConfirm
{
    public const AREA_HIGHLAND = 1;
    public const AREA_ISLAND   = 2;

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

    /** โรงเรียนยืนยันการคงอยู่ */
    public static function schoolConfirm(int $id, int $opened, ?int $mergeStatus, ?int $mergedTo, string $note): void
    {
        Db::exec(
            'UPDATE school_confirm SET opened = ?, merge_status = ?, merged_to = ?, school_note = ?,
                    school_confirmed = 1, school_confirm_at = NOW() WHERE id = ?',
            [$opened, $mergeStatus, $mergedTo ?: null, $note, $id]
        );
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
}
