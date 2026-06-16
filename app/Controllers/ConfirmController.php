<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\MergeStatus;
use App\Models\SchoolConfirm;

/** ส่วนที่ 3 (พื้นที่สูง) และ 4 (พื้นที่เกาะ): รับรองการคงอยู่ (ไม่ยุบ/เลิก/รวม) */
class ConfirmController
{
    private int $year;

    public function __construct()
    {
        $this->year = App::acadYear();
    }

    private function area(): int
    {
        $a = (int) Request::input('area', 0);
        $valid = in_array($a, [1, 2], true);
        // โรงเรียน/เขต: บังคับให้อยู่ในพื้นที่ที่ตรงคุณสมบัติ (กันเปิดแท็บผิดประเภท); สพฐ. เลือกได้ทุกพื้นที่
        if (Auth::isSchool() || Auth::isSao()) {
            $areas = \App\Services\SchoolMenu::current()['areas'];
            if ($areas) {
                return ($valid && in_array($a, $areas, true)) ? $a : (int) $areas[0];
            }
        }
        return $valid ? $a : SchoolConfirm::AREA_HIGHLAND;
    }

    public function index(): void
    {
        Auth::require();
        $area = $this->area();

        if (Auth::isSchool()) {
            $rows = array_values(array_filter(
                SchoolConfirm::forSchool((int) Auth::scId(), $this->year),
                fn($r) => (int) $r['area_type'] === $area
            ));
            View::render('confirm/school', [
                'title'    => 'ยืนยันการคงอยู่ — ' . SchoolConfirm::areaLabel($area),
                'area'     => $area,
                'rows'     => $rows,
                'mergeOpts'=> MergeStatus::all(),
            ]);
            return;
        }

        // เขต / สพฐ.
        $saoId = Auth::isSao() ? (int) Auth::saoId() : null;
        $filters = [
            'province'   => trim((string) Request::get('province', '')),
            'sao_status' => Request::get('sao_status', ''),
            'confirmed'  => Request::get('confirmed', ''),
            'q'          => trim((string) Request::get('q', '')),
        ];
        View::render('confirm/list', [
            'title'    => 'รับรองการคงอยู่ — ' . SchoolConfirm::areaLabel($area),
            'area'     => $area,
            'rows'     => SchoolConfirm::listFor($area, $this->year, $saoId, $filters),
            'stats'    => SchoolConfirm::stats($area, $this->year, $saoId),
            'filters'  => $filters,
            'mergeOpts'=> MergeStatus::all(),
        ]);
    }

    /** โรงเรียนยืนยันการคงอยู่ */
    public function school(): void
    {
        Auth::require([Auth::ROLE_SCHOOL]);
        Csrf::verify();
        $row = SchoolConfirm::get((int) Request::post('id', 0));
        if (!$row || (int) $row['sc_id'] !== (int) Auth::scId()) {
            Flash::error('ไม่พบรายการ หรือไม่มีสิทธิ์');
            App::redirect('confirm?area=' . $this->area());
        }
        // ส่งข้อมูลแล้ว = ล็อก: โรงเรียนแก้ไขไม่ได้จนกว่าเขตจะปลดล็อก
        if (SchoolConfirm::isLocked($row)) {
            Flash::error('ส่งข้อมูลแล้ว ไม่สามารถแก้ไขได้ กรุณาติดต่อสำนักงานเขตพื้นที่เพื่อปลดล็อก');
            App::redirect('confirm?area=' . $row['area_type']);
        }

        $opened = (int) Request::post('opened', 1);
        $profile = [];
        foreach (array_merge(SchoolConfirm::PROFILE_TEXT_FIELDS, SchoolConfirm::PROFILE_INT_FIELDS) as $f) {
            $profile[$f] = Request::post($f, '');
        }

        // กรณียุบ/รวม/เลิก: เก็บประเภท + (ถ้าไปเรียนรวม) ชื่อ/รหัสโรงเรียนปลายทาง
        $closeType    = $opened === 0 ? (int) Request::post('close_type', 0) : 0;
        $isMerge      = $closeType === SchoolConfirm::CLOSE_MERGE;
        $mergedTo     = $isMerge ? (int) Request::post('merged_to', 0) : null;
        $mergedToName = $isMerge ? (string) Request::post('merged_to_name', '') : '';

        $submit = Request::post('action') === 'submit';   // "ส่งข้อมูล" = ล็อก, อื่น ๆ = บันทึกร่าง
        SchoolConfirm::schoolConfirm(
            (int) $row['id'],
            $opened,
            $opened === 1 ? 1 : 0,
            $mergedTo,
            (string) Request::post('school_note', ''),
            $profile,
            $closeType,
            $mergedToName,
            $submit
        );
        Flash::success($submit
            ? 'ส่งข้อมูลเรียบร้อย — ระบบล็อกการแก้ไขแล้ว (ติดต่อเขตหากต้องการแก้ไข)'
            : 'บันทึกข้อมูล (ร่าง) เรียบร้อย — กลับมาแก้ไข/อัปโหลดเพิ่มได้ภายหลัง');
        App::redirect('confirm?area=' . $row['area_type']);
    }

    /** เขต/สพฐ. ปลดล็อกให้โรงเรียนกลับมาแก้ไขข้อมูลได้อีกครั้ง */
    public function unlock(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $row = SchoolConfirm::get((int) Request::post('id', 0));
        if (!$row || (Auth::isSao() && (int) $row['sao_id'] !== (int) Auth::saoId())) {
            Flash::error('ไม่พบรายการ หรือไม่มีสิทธิ์');
            App::redirect('confirm?area=' . $this->area());
        }
        SchoolConfirm::unlock((int) $row['id']);
        Flash::success('ปลดล็อกเรียบร้อย — โรงเรียนสามารถกลับมาแก้ไขข้อมูลได้อีกครั้ง');
        App::redirect('confirm?area=' . $row['area_type'] . '#row' . $row['id']);
    }

    /** เขตรับรอง */
    public function sao(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $row = SchoolConfirm::get((int) Request::post('id', 0));
        if (!$row || (Auth::isSao() && (int) $row['sao_id'] !== (int) Auth::saoId())) {
            Flash::error('ไม่พบรายการ หรือไม่มีสิทธิ์');
            App::redirect('confirm?area=' . $this->area());
        }
        SchoolConfirm::saoCert((int) $row['id'], (int) Request::post('sao_status', 0), (string) Request::post('sao_comment', ''));
        Flash::success('บันทึกการรับรองระดับเขตเรียบร้อย');
        App::redirect('confirm?area=' . $row['area_type'] . '#row' . $row['id']);
    }

    /** สพฐ. */
    public function spt(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        Csrf::verify();
        $row = SchoolConfirm::get((int) Request::post('id', 0));
        if (!$row) { Flash::error('ไม่พบรายการ'); App::redirect('confirm?area=' . $this->area()); }
        SchoolConfirm::sptCert((int) $row['id'], (int) Request::post('spt_status', 0), (string) Request::post('spt_comment', ''));
        Flash::success('บันทึกความเห็น สพฐ. เรียบร้อย');
        App::redirect('confirm?area=' . $row['area_type'] . '#row' . $row['id']);
    }
}
