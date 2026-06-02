<?php
namespace App\Models;

use App\Core\Db;

/** จังหวัด — province: PROVINCE_NAME, lat/lng (ศาลากลาง), high (ความสูงเฉลี่ยจังหวัด) */
class Province
{
    public static function byName(string $name): ?array
    {
        $name = trim($name);
        return Db::one(
            'SELECT PROVINCE_ID, PROVINCE_NAME, lat, lng, high FROM province WHERE PROVINCE_NAME = ? LIMIT 1',
            [$name]
        ) ?? Db::one(
            'SELECT PROVINCE_ID, PROVINCE_NAME, lat, lng, high FROM province WHERE PROVINCE_NAME LIKE ? LIMIT 1',
            ["%$name%"]
        );
    }
}
