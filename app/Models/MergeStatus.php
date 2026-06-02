<?php
namespace App\Models;

use App\Core\Db;

/** สถานะการคงอยู่/ยุบรวม — merge_status: id, merge_status */
class MergeStatus
{
    public static function all(): array
    {
        return Db::all('SELECT id, merge_status FROM merge_status ORDER BY id DESC');
    }
}
