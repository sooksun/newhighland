<?php
namespace App\Models;

use App\Core\Db;

/**
 * สังกัด (เขตพื้นที่การศึกษา) สำหรับ dropdown — ตาราง sao_new
 *   PK: areacode (เช่น '57030000'), ชื่อ: sao (เช่น 'สพป.เชียงราย เขต 3'), sao_type, province
 * areacode ของโรงเรียนอ้างอิงจาก school_new.areacode (lookup ด้วย sc_id)
 */
class SaoNew
{
    /** รายการสังกัดทั้งหมด (สำหรับ dropdown) */
    public static function all(): array
    {
        return Db::all(
            "SELECT areacode, TRIM(sao) AS sao, sao_type, province
               FROM sao_new ORDER BY province, sao"
        );
    }

    /** ชื่อสังกัดจาก areacode */
    public static function name(string $areacode): string
    {
        $r = Db::scalar('SELECT TRIM(sao) FROM sao_new WHERE areacode = ? LIMIT 1', [$areacode]);
        return $r !== false ? (string) $r : '';
    }

    /** areacode ของโรงเรียน (จาก school_new) */
    public static function areacodeOf(int $scId): string
    {
        $r = Db::scalar('SELECT areacode FROM school_new WHERE sc_id = ? LIMIT 1', [(string) $scId]);
        return $r !== false ? (string) $r : '';
    }

    /** หา areacode ย้อนจากชื่อสังกัด (เผื่อข้อมูลเดิมเก็บเป็นชื่อ) */
    public static function areacodeByName(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '';
        $r = Db::scalar('SELECT areacode FROM sao_new WHERE TRIM(sao) = ? LIMIT 1', [$name]);
        return $r !== false ? (string) $r : '';
    }
}
