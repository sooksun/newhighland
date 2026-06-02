<?php
namespace App\Models;

use App\Core\Db;

/** สังกัด (เขตพื้นที่) — master_sao: sao_id(int), sao_code, sao_name, sao_group(1=สพป,2=สพม), lat/lng */
class MasterSao
{
    public static function all(): array
    {
        return Db::all('SELECT sao_id, sao_code, sao_name, sao_group, region FROM master_sao ORDER BY sao_name');
    }

    public static function find(int $saoId): ?array
    {
        return Db::one('SELECT * FROM master_sao WHERE sao_id = ?', [$saoId]);
    }

    /** map ชื่อสังกัด (จาก master_saonew.code) -> แถว master_sao */
    public static function byName(string $name): ?array
    {
        return Db::one('SELECT * FROM master_sao WHERE sao_name = ? LIMIT 1', [trim($name)]);
    }

    /** map รหัส 4 หลัก (เช่น "6302") -> แถว master_sao */
    public static function byCode(string $code): ?array
    {
        return Db::one('SELECT * FROM master_sao WHERE sao_code = ? LIMIT 1', [$code]);
    }
}
