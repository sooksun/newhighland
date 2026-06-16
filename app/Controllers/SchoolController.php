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
use App\Models\SchoolConfirm;

/** ส่วนที่ 1 (1A): เลือกสังกัด → เลือกโรงเรียน → เริ่มประเมิน */
class SchoolController
{
    public function index(): void
    {
        Auth::require();

        // โรงเรียน: ไปต่อที่โรงเรียนตนเองเลย
        if (Auth::isSchool()) {
            $scId = (int) Auth::scId();
            View::render('highland/select', [
                'title'    => 'เริ่มประเมินพื้นที่สูง',
                'mode'     => 'school',
                'sc_id'    => $scId,
                'sc_name'  => Auth::name(),
                'sao_name' => Auth::saoName(),
                'in_roster'=> SchoolConfirm::inRoster(SchoolConfirm::AREA_HIGHLAND, App::acadYear(), (string) $scId),
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

    /** AJAX: รายชื่อโรงเรียนตามสังกัด (JSON) — กรองเบื้องต้นตามจังหวัดที่เคยมีพื้นที่สูง */
    public function schools(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        $saoId = (int) Request::get('sao_id', 0);
        if (!$saoId) View::json([]);

        if (Auth::isSao() && $saoId !== Auth::saoId()) {
            View::json(['error' => 'forbidden'], 403);
        }
        // area: 1=พื้นที่สูง (ค่าเริ่มต้น), 2=พื้นที่เกาะ (หน้า island เรียก endpoint นี้พร้อม area=2)
        $area = (int) Request::get('area', SchoolConfirm::AREA_HIGHLAND);
        $area = in_array($area, [1, 2], true) ? $area : SchoolConfirm::AREA_HIGHLAND;
        $year = App::acadYear();

        $schools = MasterSchool::bySao($saoId, SchoolConfirm::eligibleProvinces($area, $year));
        // ทำเครื่องหมายโรงเรียนที่อยู่ในรายชื่อเดิม (ผ่านประเมินแล้ว) → ฝั่ง UI จะ disable การประเมิน
        $roster = array_flip(array_map('strval', SchoolConfirm::rosterScIds($area, $year)));
        foreach ($schools as &$s) {
            $s['in_roster'] = isset($roster[(string) $s['sc_id']]) ? 1 : 0;
        }
        unset($s);
        View::json($schools);
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
        // โรงเรียนพื้นที่สูงเดิม (ในรายชื่อ) ผ่านประเมินแล้ว → ไม่ต้องประเมินใหม่ ให้ไปรับรองการคงอยู่
        if (SchoolConfirm::inRoster(SchoolConfirm::AREA_HIGHLAND, App::acadYear(), (string) $scId)) {
            Flash::info('โรงเรียนนี้เป็นพื้นที่สูงเดิม (ผ่านการประเมินแล้ว) — โปรดยืนยันที่หน้ารับรองการคงอยู่');
            App::redirect('confirm?area=' . SchoolConfirm::AREA_HIGHLAND);
        }
        // กรองเบื้องต้น: คัดกรองพื้นที่สูงได้เฉพาะโรงเรียนในจังหวัดที่เคยมีพื้นที่สูง
        if (!SchoolConfirm::isEligibleSchool(SchoolConfirm::AREA_HIGHLAND, App::acadYear(), (string) $scId)) {
            Flash::error('โรงเรียนนี้อยู่ในจังหวัดที่ไม่เคยมีโรงเรียนพื้นที่สูง จึงไม่เข้าเกณฑ์คัดกรองพื้นที่สูง');
            App::redirect('highland');
        }
        $_SESSION['work_sc_id'] = $scId;
        App::redirect('map/search?sc_id=' . $scId);
    }
}
