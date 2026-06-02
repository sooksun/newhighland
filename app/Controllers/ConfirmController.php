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
        $a = (int) Request::input('area', SchoolConfirm::AREA_HIGHLAND);
        return in_array($a, [1, 2], true) ? $a : 1;
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
        $opened = (int) Request::post('opened', 1);
        SchoolConfirm::schoolConfirm(
            (int) $row['id'],
            $opened,
            $opened === 1 ? 1 : 0,
            $opened === 0 ? (int) Request::post('merged_to', 0) : null,
            (string) Request::post('school_note', '')
        );
        Flash::success('บันทึกการยืนยันเรียบร้อย');
        App::redirect('confirm?area=' . $row['area_type']);
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
