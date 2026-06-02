<?php
namespace App\Models;

use App\Core\Db;

/**
 * ตัวเลือกของแต่ละข้อ จากตาราง citeria_master{NN}
 * ข้อที่มี master: 02,03,04,06,07,08,09,10,16 (id, masterNN)
 * ข้อ 07,08,09,10 = เลือกได้หลายข้อ (checkbox)
 */
class CriteriaOption
{
    private const MULTI = ['07', '08', '09', '10'];

    /** คืน [['id'=>1,'label'=>'...'], ...] ของข้อ NN เช่น '02' */
    public static function get(string $nn): array
    {
        $table = 'citeria_master' . $nn;
        $rows  = Db::all("SELECT id, `master{$nn}` AS label FROM `{$table}` ORDER BY id");
        // ทำความสะอาด <br>/\n ในป้ายข้อความ (เช่น ข้อ 8)
        foreach ($rows as &$r) {
            $r['id']    = (int) $r['id'];
            $r['label'] = trim(str_replace(['\n', '<br>'], [' ', ' '], (string) $r['label']));
        }
        return $rows;
    }

    public static function isMulti(string $nn): bool
    {
        return in_array($nn, self::MULTI, true);
    }

    /** โหลดทุกข้อที่มี master ทีเดียว => ['02'=>[...], ...] */
    public static function allSets(): array
    {
        $out = [];
        foreach (['02', '03', '04', '06', '07', '08', '09', '10', '16'] as $nn) {
            $out[$nn] = self::get($nn);
        }
        return $out;
    }
}
