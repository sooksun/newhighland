<?php
namespace App\Models;

use App\Core\Db;

/** ทะเบียนโรงเรียน — master_school: sc_id(varchar), sao_code(int)=master_sao.sao_id, sc_name, provinces, ... */
class MasterSchool
{
    /** โรงเรียนในสังกัด (สำหรับ dropdown) */
    public static function bySao(int $saoId): array
    {
        return Db::all(
            'SELECT sc_id, sc_name, provinces, districts, amphures, dir_name, address
               FROM master_school WHERE sao_code = ? ORDER BY sc_name',
            [$saoId]
        );
    }

    public static function find(string $scId): ?array
    {
        return Db::one('SELECT * FROM master_school WHERE sc_id = ? LIMIT 1', [$scId]);
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
