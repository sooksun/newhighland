<?php
namespace App\Services;

use App\Core\App;
use App\Core\Auth;
use App\Models\SchoolConfirm;

/**
 * Auto menu filter — คำนวณเมนู/พื้นที่ที่ "ตรงคุณสมบัติ" ของโรงเรียน
 * เพื่อให้ผู้ใช้ระดับโรงเรียนเห็นเฉพาะเมนูที่เกี่ยวข้อง (กันลงข้อมูลผิดประเภท)
 *
 * ตรรกะ (PRD §2): โรงเรียน 1 แห่งจะอยู่ "พื้นที่สูง" หรือ "พื้นที่เกาะ" อย่างใดอย่างหนึ่ง และ
 *   - ถ้าอยู่ในรายชื่อเดิม (roster) ของประเภทนั้น  → ทำ "รับรองการคงอยู่" (เมนู confirm)
 *   - ถ้ายังไม่อยู่ในรายชื่อ แต่จังหวัดเข้าเกณฑ์    → ทำ "ประเมินใหม่" (เมนู highland/island)
 */
class SchoolMenu
{
    private static ?array $cache = null;

    /** @return array{nav: string[], areas: int[], track: string} */
    public static function forSchool(int $scId, int $year): array
    {
        $sc = (string) $scId;
        $hConfirm = SchoolConfirm::inRoster(SchoolConfirm::AREA_HIGHLAND, $year, $sc);
        $iConfirm = SchoolConfirm::inRoster(SchoolConfirm::AREA_ISLAND,   $year, $sc);
        // เข้าเกณฑ์ประเมินใหม่เฉพาะเมื่อยังไม่อยู่ในรายชื่อเดิมของประเภทนั้น
        $hEval = !$hConfirm && SchoolConfirm::isEligibleSchool(SchoolConfirm::AREA_HIGHLAND, $year, $sc);
        $iEval = !$iConfirm && SchoolConfirm::isEligibleSchool(SchoolConfirm::AREA_ISLAND,   $year, $sc);

        $nav   = ['', 'dashboard'];        // แดชบอร์ดเห็นเสมอ
        $areas = [];
        if ($hEval) $nav[] = 'highland';
        if ($iEval) $nav[] = 'island';
        if ($hConfirm) $areas[] = SchoolConfirm::AREA_HIGHLAND;
        if ($iConfirm) $areas[] = SchoolConfirm::AREA_ISLAND;
        if ($areas) $nav[] = 'confirm';

        $track = ($hConfirm || $iConfirm) ? 'confirm' : (($hEval || $iEval) ? 'eval' : 'none');

        return ['nav' => array_values(array_unique($nav)), 'areas' => $areas, 'track' => $track];
    }

    /**
     * เมนู/พื้นที่ของ "เขตพื้นที่" (sao) — จำแนกจากประเภทพื้นที่ของโรงเรียนที่เขตดูแล
     * เขตพื้นที่สูงเห็นเฉพาะ คัดกรองพื้นที่สูง + รออนุมัติพื้นที่สูง + รับรองการคงอยู่พื้นที่สูง
     * @return array{nav: string[], areas: int[], cert: string, track: string}
     */
    public static function forSao(int $saoId, int $year): array
    {
        $areas = SchoolConfirm::saoAreas($saoId, $year);
        $hasH  = in_array(SchoolConfirm::AREA_HIGHLAND, $areas, true);
        $hasI  = in_array(SchoolConfirm::AREA_ISLAND, $areas, true);

        $nav  = ['', 'dashboard'];
        if ($hasH) $nav[] = 'highland';
        if ($hasI) $nav[] = 'island';
        // หน้า "รออนุมัติ" ของเขตชี้ไปพื้นที่ที่ตรงคุณสมบัติ (ไม่มีเขตใดมีทั้งสองพื้นที่)
        $cert = $hasI && !$hasH ? 'island/cert' : 'highland/cert';
        if ($areas) { $nav[] = $cert; $nav[] = 'confirm'; }

        return ['nav' => array_values(array_unique($nav)), 'areas' => $areas, 'cert' => $cert, 'track' => 'sao'];
    }

    private static function certFor(array $areas): string
    {
        return in_array(SchoolConfirm::AREA_ISLAND, $areas, true)
            && !in_array(SchoolConfirm::AREA_HIGHLAND, $areas, true) ? 'island/cert' : 'highland/cert';
    }

    /** เมนู/พื้นที่ของผู้ใช้ปัจจุบัน — อ่านจาก session (เก็บตอน login) หรือคำนวณสดถ้า session เก่า */
    public static function current(): array
    {
        if (self::$cache !== null) return self::$cache;
        $navSess = Auth::menus();
        if ($navSess !== null) {
            $areas = Auth::confirmAreas() ?? [];
            return self::$cache = ['nav' => $navSess, 'areas' => $areas, 'cert' => self::certFor($areas), 'track' => ''];
        }
        if (Auth::isSao())    return self::$cache = self::forSao((int) Auth::saoId(), App::acadYear());
        if (Auth::isSchool()) return self::$cache = self::forSchool((int) Auth::scId(), App::acadYear());
        // admin: เห็นทุกพื้นที่
        return self::$cache = ['nav' => ['', 'dashboard', 'highland', 'island', 'highland/cert', 'confirm'],
                               'areas' => [1, 2], 'cert' => 'highland/cert', 'track' => 'admin'];
    }
}
