<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Models\CriteriaOption;
use App\Models\DashboardStat;
use App\Models\IslandOption;
use App\Services\SchoolMenu;

/**
 * หน้า "รายงานสถิติ" (Executive dashboard) — กราฟ/การ์ดสรุปภาพรวมการคัดกรอง + ส่งออก CSV
 * เฉพาะ สพท. (เขต) / สพฐ. ; เขตเห็นเฉพาะสังกัดตน, สพฐ. เห็นทุกเขต (DashboardStat คุมขอบเขต)
 */
class DashboardController
{
    private int $year;

    public function __construct()
    {
        $this->year = App::acadYear();
    }

    /** บริบทตาม role + พารามิเตอร์ ?area= : คืน [saoId, area, areas] */
    private function context(): array
    {
        $saoId = Auth::isSao() ? (int) Auth::saoId() : null;   // สพฐ. = null = ทุกเขต
        $areas = Auth::isAdmin() ? [1, 2] : (SchoolMenu::current()['areas'] ?: [1, 2]);
        $area  = (int) Request::get('area', $areas[0] ?? 1);
        if (!in_array($area, $areas, true)) $area = $areas[0] ?? 1;
        return [$saoId, $area, $areas];
    }

    /** ป้ายชื่อระดับสาธารณูปโภคแต่ละข้อ (key => [id => label]) */
    private function utilLabels(int $area): array
    {
        $out = [];
        foreach (DashboardStat::utilConfig($area) as $key => $nn) {
            $opts = $area === 1 ? CriteriaOption::get($nn) : IslandOption::get($nn);
            $map = [];
            foreach ($opts as $o) $map[(int) $o['id']] = $o['label'];
            $out[$key] = $map;
        }
        return $out;
    }

    public function index(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        [$saoId, $area, $areas] = $this->context();

        View::render('report/index', [
            'title'      => 'รายงานสถิติ — ภาพรวมการคัดกรอง',
            'year'       => $this->year,
            'area'       => $area,
            'areas'      => $areas,
            'isAdmin'    => Auth::isAdmin(),
            'targets'    => DashboardStat::targets($saoId, $this->year),
            'kpi'        => DashboardStat::kpis($saoId, $this->year),
            'funnel'     => DashboardStat::funnel($area, $saoId, $this->year),
            'typeDist'   => DashboardStat::typeDist($area, $saoId, $this->year),
            'scoreHist'  => DashboardStat::scoreHist($area, $saoId, $this->year),
            'provinces'  => DashboardStat::topProvinces($area, $saoId, $this->year),
            'utils'      => DashboardStat::utilities($area, $saoId, $this->year),
            'utilLabels' => $this->utilLabels($area),
            // ไทล์เพิ่ม: ชาติพันธุ์ (เฉพาะพื้นที่สูง), แผนที่หมุด, เขตค้างรับรอง
            'ethnic'     => $area === 1 ? DashboardStat::ethnic($saoId, $this->year) : null,
            'pins'       => DashboardStat::pins($area, $saoId, $this->year),
            'pending'    => DashboardStat::certByDistrict($area, $saoId, $this->year),
            'googleKey'  => (string) App::config('google_maps_key'),
        ]);
    }

    /** ส่งออกข้อมูลรายงานเป็น CSV (UTF-8 + BOM ให้ Excel อ่านภาษาไทยได้ถูก) */
    public function export(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        [$saoId, $area] = $this->context();
        $y = $this->year;

        $targets = DashboardStat::targets($saoId, $y);
        $kpi     = DashboardStat::kpis($saoId, $y);
        $type    = DashboardStat::typeDist($area, $saoId, $y);
        $score   = DashboardStat::scoreHist($area, $saoId, $y);
        $prov    = DashboardStat::topProvinces($area, $saoId, $y, 100);
        $utils   = DashboardStat::utilities($area, $saoId, $y);
        $pending = DashboardStat::certByDistrict($area, $saoId, $y, 200);
        $ethnic  = $area === 1 ? DashboardStat::ethnic($saoId, $y, 100) : null;
        $uLabels = $this->utilLabels($area);

        $areaName  = $area === 2 ? 'พื้นที่เกาะ' : 'พื้นที่สูง';
        $typeNames = [($area === 2 ? 'ไม่เป็นพื้นที่เกาะ' : 'ไม่เป็นพื้นที่สูง'), 'ยุ่งยาก', 'ยุ่งยากมาก', 'ยุ่งยากมากที่สุด'];
        $utilNames = ['water' => 'น้ำ/ประปา', 'power' => 'ไฟฟ้า', 'phone' => 'โทรศัพท์', 'net' => 'อินเทอร์เน็ต'];
        $pct = fn($a, $b) => $b > 0 ? round($a * 100 / $b, 1) : 0.0;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report_' . $y . '_area' . $area . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");                 // BOM
        $w = fn(array $r) => fputcsv($out, $r);

        $w(['รายงานสถิติการคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ']);
        $w(['ปีงบประมาณ', $y, 'ประเภทพื้นที่', $areaName, 'ขอบเขต', Auth::isAdmin() ? 'ทุกเขต (สพฐ.)' : Auth::saoName()]);
        $w([]);

        $w(['== ภาพรวม (KPI) ==']);
        $w(['ตัวชี้วัด', 'จำนวน']);
        $w(['โรงเรียนเป้าหมาย', $targets['total']]);
        $w(['  - พื้นที่สูง', $targets['high']]);
        $w(['  - พื้นที่เกาะ', $targets['island']]);
        $w(['ดำเนินการแล้ว (ประเมินเสร็จ)', $kpi['done']]);
        $w(['ผ่านเกณฑ์ (คะแนน >= 50)', $kpi['passed']]);
        $w(['เขตรับรองแล้ว', $kpi['sao_certed']]);
        $w(['สพฐ. ประกาศ', $kpi['spt_announced']]);
        $w([]);

        $tTotal = array_sum($type);
        $w(['== ระดับความยุ่งยาก (' . $areaName . ') ==']);
        $w(['ระดับ', 'จำนวน', 'ร้อยละ']);
        foreach ($type as $lvl => $n) $w([$typeNames[$lvl] ?? $lvl, $n, $pct($n, $tTotal)]);
        $w([]);

        $w(['== การกระจายคะแนนรวม ==']);
        $w(['ช่วงคะแนน', 'จำนวน']);
        foreach ($score as $bucket => $n) $w([$bucket, $n]);
        $w([]);

        $w(['== จังหวัดที่ผ่านเกณฑ์ ==']);
        $w(['จังหวัด', 'จำนวนผ่านเกณฑ์']);
        foreach ($prov as $r) $w([$r['province'], $r['passed']]);
        $w([]);

        $w(['== สาธารณูปโภค ==']);
        $w(['ประเภท', 'ระดับการเข้าถึง', 'จำนวน', 'ร้อยละ']);
        foreach ($utils as $key => $u) {
            $ans = max(1, (int) $u['answered']);
            foreach ($u['levels'] as $l => $n) {
                $w([$utilNames[$key] ?? $key, $uLabels[$key][$l] ?? ('ระดับ ' . $l), $n, $pct($n, $ans)]);
            }
        }
        $w([]);

        if ($ethnic !== null) {
            $w(['== กลุ่มชาติพันธุ์ ==']);
            $w(['รวมทั้งหมด', 'กลุ่ม', $ethnic['groups'], 'นักเรียน', $ethnic['students'], 'โรงเรียน', $ethnic['schools']]);
            $w(['กลุ่มชาติพันธุ์', 'นักเรียน', 'โรงเรียน']);
            foreach ($ethnic['top'] as $r) $w([$r['ethnic'], $r['students'], $r['schools']]);
            $w([]);
        }

        $w(['== เขตที่ยังค้างรับรอง ==']);
        $w(['เขตพื้นที่', 'ประเมินเสร็จ', 'ค้างรับรอง', 'รับรองแล้ว', 'ร้อยละรับรอง']);
        foreach ($pending as $d) $w([$d['sao_name'], $d['done'], $d['pending'], $d['approved'], $pct((int) $d['approved'], (int) $d['done'])]);

        fclose($out);
        exit;
    }
}
