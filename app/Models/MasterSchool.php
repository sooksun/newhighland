<?php
namespace App\Models;

use App\Core\Db;

/** ทะเบียนโรงเรียน — master_school: sc_id(varchar), sao_code(int)=master_sao.sao_id, sc_name, provinces, ... */
class MasterSchool
{
    /**
     * โรงเรียนในสังกัด (สำหรับ dropdown)
     * @param array|null $provinces ถ้าส่งมา = กรองเฉพาะจังหวัดในรายการ (กรองเบื้องต้นตามประเภทพื้นที่)
     *                              []  = ไม่มีจังหวัดที่เข้าเกณฑ์ → คืนว่าง
     */
    public static function bySao(int $saoId, ?array $provinces = null): array
    {
        // จังหวัดที่ใช้จริง = master_school.provinces ถ้ามี ไม่งั้นใช้ school_new.province (แหล่งจริง)
        $eff = 'COALESCE(NULLIF(m.provinces, ""), s.province, "")';
        $sql = "SELECT m.sc_id, m.sc_name, $eff AS provinces, m.districts, m.amphures, m.dir_name, m.address
                  FROM master_school m
             LEFT JOIN school_new s ON s.sc_id = m.sc_id
                 WHERE m.sao_code = ?";
        $p = [$saoId];
        if ($provinces !== null) {
            if (!$provinces) return [];
            $in   = implode(',', array_fill(0, count($provinces), '?'));
            $sql .= " AND $eff IN ($in)";
            $p    = array_merge($p, $provinces);
        }
        $sql .= ' ORDER BY m.sc_name';
        return Db::all($sql, $p);
    }

    public static function find(string $scId): ?array
    {
        return Db::one('SELECT * FROM master_school WHERE sc_id = ? LIMIT 1', [$scId]);
    }

    /**
     * จังหวัดที่ตั้งของโรงเรียน — ใช้ school_new.province เป็นหลัก (แหล่งจริง/ครบกว่า)
     * เผื่อ fallback ไป master_school.provinces หาก school_new ไม่มีข้อมูล
     */
    public static function provinceOf(string $scId): string
    {
        $p = (string) Db::scalar('SELECT province FROM school_new WHERE sc_id = ? LIMIT 1', [$scId]);
        if ($p === '') {
            $p = (string) Db::scalar('SELECT provinces FROM master_school WHERE sc_id = ? LIMIT 1', [$scId]);
        }
        return $p;
    }

    /** ค้นหาด้วยชื่อ/รหัส (เผื่อใช้ autocomplete) */
    public static function search(int $saoId, string $q, int $limit = 50): array
    {
        return Db::all(
            'SELECT sc_id, sc_name, provinces FROM master_school
              WHERE sao_code = ? AND (sc_name LIKE ? OR sc_id LIKE ?) ORDER BY sc_name LIMIT ' . (int) $limit,
            [$saoId, "%$q%", "%$q%"]
        );
    }
}
