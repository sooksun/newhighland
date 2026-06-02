<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\MasterSao;
use App\Models\MasterSchool;

/** ส่วนที่ 1 (1A): เลือกสังกัด → เลือกโรงเรียน → เริ่มประเมิน */
class SchoolController
{
    public function index(): void
    {
        Auth::require();

        // โรงเรียน: ไปต่อที่โรงเรียนตนเองเลย
        if (Auth::isSchool()) {
            View::render('highland/select', [
                'title'    => 'เริ่มประเมินพื้นที่สูง',
                'mode'     => 'school',
                'sc_id'    => Auth::scId(),
                'sc_name'  => Auth::name(),
                'sao_name' => Auth::saoName(),
            ]);
            return;
        }

        // เขต/สพฐ.: เลือกสังกัด + โรงเรียน
        $saoList = Auth::isAdmin()
            ? MasterSao::all()
            : array_filter([MasterSao::find((int) Auth::saoId())]);

        View::render('highland/select', [
            'title'      => 'เริ่มประเมินพื้นที่สูง',
            'mode'       => Auth::isAdmin() ? 'admin' : 'sao',
            'sao_list'   => $saoList,
            'sel_sao_id' => Auth::isSao() ? Auth::saoId() : null,
        ]);
    }

    /** AJAX: รายชื่อโรงเรียนตามสังกัด (JSON) */
    public function schools(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        $saoId = (int) Request::get('sao_id', 0);
        if (!$saoId) View::json([]);

        if (Auth::isSao() && $saoId !== Auth::saoId()) {
            View::json(['error' => 'forbidden'], 403);
        }
        View::json(MasterSchool::bySao($saoId));
    }

    /** POST: เลือกโรงเรียนแล้วไปขั้นปักหมุด */
    public function start(): void
    {
        Auth::require();
        Csrf::verify();

        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !Auth::canAccessSchool($scId)) {
            Flash::error('ไม่พบโรงเรียน หรือไม่มีสิทธิ์เข้าถึง');
            App::redirect('highland');
        }
        $_SESSION['work_sc_id'] = $scId;
        App::redirect('map/search?sc_id=' . $scId);
    }
}
