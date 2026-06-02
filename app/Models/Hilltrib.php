<?php
namespace App\Models;

use App\Core\Db;

/** กลุ่มชาติพันธุ์ — hilltrib: ethnic_id, ethnic */
class Hilltrib
{
    public static function all(): array
    {
        return Db::all('SELECT ethnic_id, TRIM(ethnic) AS ethnic FROM hilltrib ORDER BY ethnic_id');
    }

    /** map ethnic_id => ethnic */
    public static function lookup(): array
    {
        $out = [];
        foreach (self::all() as $r) $out[(int) $r['ethnic_id']] = $r['ethnic'];
        return $out;
    }
}
