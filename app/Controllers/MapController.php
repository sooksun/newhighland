<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\View;
use App\Models\HighlandEval;
use App\Models\SystemStatus;
use App\Services\SchoolContext;

/** ส่วนที่ 1 (1B): ปักหมุด + วัดความสูง/ระยะทาง (พอร์ตจาก ssar_search.php / ssar_elevation.php) */
class MapController
{
    private function ctxOrDeny(): array
    {
        Auth::require();
        $scId = (int) Request::get('sc_id', $_SESSION['work_sc_id'] ?? 0);
        if (!$scId || !Auth::canAccessSchool($scId)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
        return SchoolContext::build($scId, App::acadYear());
    }

    /** หน้าปักหมุด (Google Places + Elevation ณ จุด) */
    public function search(): void
    {
        $ctx = $this->ctxOrDeny();
        View::render('map/search', [
            'title'      => 'ปักหมุดตำแหน่งโรงเรียน',
            'ctx'        => $ctx,
            'google_key' => App::config('google_maps_key'),
        ], null);   // ไม่มี layout (เต็มจอแผนที่)
    }

    /** AJAX: บันทึกพิกัดที่ปักหมุด -> school_location */
    public function saveLatLng(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !Auth::canAccessSchool($scId)) View::json(['error' => 'forbidden'], 403);
        if (Auth::isSchool() && !SystemStatus::isOpen()) View::json(['error' => SystemStatus::closedMessage()], 423);

        SchoolContext::savePin(
            $scId,
            (string) Request::post('lat', ''),
            (string) Request::post('lng', ''),
            (float)  Request::post('location_high', 0)
        );
        View::json(['ok' => true, 'next' => App::url('map/elevation?sc_id=' . $scId)]);
    }

    /** หน้าวัดความสูงตลอดเส้นทาง โรงเรียน -> ศาลากลางจังหวัด */
    public function elevation(): void
    {
        $ctx = $this->ctxOrDeny();
        View::render('map/elevation', [
            'title'      => 'วัดความสูงและระยะทาง',
            'ctx'        => $ctx,
            'threshold'  => (int) App::config('elevation_threshold'),
            'google_key' => App::config('google_maps_key'),
        ], null);
    }

    /** AJAX: บันทึกผลความสูง/ระยะทาง -> highland_eval (upsert) + ตัดสินภูเขา/ราบ */
    public function saveElevation(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !Auth::canAccessSchool($scId)) View::json(['error' => 'forbidden'], 403);
        if (Auth::isSchool() && !SystemStatus::isOpen()) View::json(['error' => SystemStatus::closedMessage()], 423);

        $year      = App::acadYear();
        $highest   = (float) Request::post('highest', 0);
        $distance  = (float) Request::post('distance', 0);
        $avg       = (float) Request::post('average_height', 0);
        $threshold = (int) App::config('elevation_threshold');

        HighlandEval::upsertGeo($scId, $year, [
            'sc_names'       => (string) Request::post('name', ''),
            'lat'            => (string) Request::post('lat', ''),
            'lng'            => (string) Request::post('lng', ''),
            'highest'        => $highest,
            'distance'       => $distance,
            'average_height' => $avg,
        ]);

        // เป็นพื้นที่สูงเมื่อ จุดสูงสุด > ค่าเฉลี่ยจังหวัด หรือ > เกณฑ์ (500 ม.)
        $isHighland = ($highest > $avg) || ($highest > $threshold);

        View::json([
            'ok'         => true,
            'is_highland'=> $isHighland,
            'next'       => $isHighland
                ? App::url('highland/eval?sc_id=' . $scId)
                : App::url('dashboard'),
        ]);
    }
}
