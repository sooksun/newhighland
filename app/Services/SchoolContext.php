<?php
namespace App\Services;

use App\Core\Db;
use App\Models\MasterSchool;
use App\Models\Province;
use App\Models\HighlandEval;

/**
 * รวบรวมบริบทของโรงเรียนหนึ่งแห่ง (ชื่อ, จังหวัด, พิกัดศาลากลาง+ความสูงเฉลี่ย, พิกัดที่ปักหมุด, แถว eval)
 * ใช้ร่วมกันโดย MapController และ HighlandEvalController
 */
class SchoolContext
{
    /** @return array{sc_id:int,sc_name:string,province:string,prov_lat:string,prov_lng:string,
     *               prov_high:float,sc_lat:string,sc_lng:string,location_high:float,eval:?array} */
    public static function build(int $scId, int $year): array
    {
        $master = MasterSchool::find((string) $scId);
        $eval   = HighlandEval::find($scId, $year);

        $scName = $master['sc_name'] ?? ($eval['sc_names'] ?? '');
        // จังหวัด: ใช้ school_new เป็นหลัก (master_school.provinces ว่างในหลายแถว) ผ่าน MasterSchool::provinceOf
        $province = MasterSchool::provinceOf((string) $scId);
        if ($province === '') {
            $province = (string) ($master['provinces'] ?? ($eval['provinces'] ?? ''));
        }

        $prov = $province !== '' ? Province::byName($province) : null;

        // พิกัดที่ปักหมุดไว้แล้ว (จาก school_location เป็นหลัก หรือจาก eval เดิม)
        $loc = Db::one('SELECT lat, lng, location_high FROM school_location WHERE id = ? LIMIT 1', [$scId]);

        $scLat = $loc['lat'] ?? ($eval['lat'] ?? '');
        $scLng = $loc['lng'] ?? ($eval['lng'] ?? '');

        return [
            'sc_id'         => $scId,
            'sc_name'       => $scName,
            'province'      => $province,
            'prov_lat'      => (string) ($prov['lat'] ?? ''),
            'prov_lng'      => (string) ($prov['lng'] ?? ''),
            'prov_high'     => (float)  ($prov['high'] ?? 0),
            'sc_lat'        => (string) $scLat,
            'sc_lng'        => (string) $scLng,
            'location_high' => (float)  ($loc['location_high'] ?? 0),
            'eval'          => $eval,
        ];
    }

    /** บันทึก/อัปเดตพิกัดที่ปักหมุดลง school_location (แทน map_uplatlng.php, ใช้ PDO) */
    public static function savePin(int $scId, string $lat, string $lng, float $locationHigh, string $url = ''): void
    {
        $exists = (bool) Db::scalar('SELECT 1 FROM school_location WHERE id = ? LIMIT 1', [$scId]);
        if ($exists) {
            Db::exec('UPDATE school_location SET lat = ?, lng = ?, location_high = ?, url = ? WHERE id = ?',
                [$lat, $lng, $locationHigh, $url, $scId]);
        } else {
            // school_location มีหลายคอลัมน์ NOT NULL ไม่มี default — ใส่ค่าเริ่มต้นให้ครบ
            Db::exec(
                'INSERT INTO school_location
                   (id, sname, lat, lng, location_high, pre, p, m, s, rpre, rp, rm, rs,
                    teacher, student, room, url, highest, distance, comment)
                 VALUES (?, "", ?, ?, ?, 0,0,0,0, 0,0,0,0, 0,0,0, ?, 0, 0, "")',
                [$scId, $lat, $lng, $locationHigh, $url]
            );
        }
    }
}
