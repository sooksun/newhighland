<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\IslandEval;

/**
 * ส่วนที่ 2 (ขั้นที่ 4): รายการ "รออนุมัติ" — ให้ สพท. (เขต) / สพฐ. รับรองผลประเมินพื้นที่เกาะ
 * ที่โรงเรียนทำเสร็จแล้ว (มี sum_score) คู่ขนานกับหน้ารับรองพื้นที่สูง (HighlandCertController)
 */
class IslandCertController
{
    private int $year;

    public function __construct()
    {
        $this->year = App::acadYear();
    }

    /** รายการโรงเรียนที่ประเมินเสร็จ รอ สพท./สพฐ. รับรอง */
    public function index(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);

        // สพฐ. กรองเฉพาะเขตได้ผ่าน ?sao= (drill-down จากหน้ารายงาน); เขตถูกล็อกที่สังกัดตน
        $saoParam = trim((string) Request::get('sao', ''));
        $saoId    = Auth::isSao() ? (int) Auth::saoId()
                  : ($saoParam !== '' ? (int) $saoParam : null);
        $saoFilter = (Auth::isAdmin() && $saoParam !== '') ? (int) $saoParam : '';
        $saoName   = $saoFilter !== '' ? (string) (\App\Models\MasterSao::find($saoFilter)['sao_name'] ?? '') : '';

        $filters = [
            'q'        => trim((string) Request::get('q', '')),
            'province' => trim((string) Request::get('province', '')),
            'status'   => Request::get('status', ''),
        ];

        View::render('island/cert_list', [
            'title'     => 'รออนุมัติ — รับรองผลประเมินพื้นที่เกาะ',
            'rows'      => IslandEval::listForCert($saoId, $this->year, $filters),
            'stats'     => IslandEval::certStats($saoId, $this->year),
            'filters'   => $filters,
            'saoFilter' => $saoFilter,
            'saoName'   => $saoName,
        ]);
    }

    /** เขต (สพท.) บันทึกผลการรับรอง */
    public function sao(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);

        IslandEval::saveCert($scId, $this->year, [
            'confirmstatus'  => (int) Request::post('confirmstatus', 0),
            'confirmcomment' => (string) Request::post('confirmcomment', ''),
        ]);
        Flash::success('บันทึกการรับรองระดับเขต (สพท.) เรียบร้อย');
        App::redirect('island/cert#row' . $scId);
    }

    /** สพฐ. บันทึกความเห็น */
    public function spt(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !IslandEval::exists($scId, $this->year)) {
            Flash::error('ไม่พบรายการประเมิน');
            App::redirect('island/cert');
        }

        IslandEval::saveCert($scId, $this->year, [
            'spt_commit'  => (int) Request::post('spt_commit', 0),
            'spt_comment' => (string) Request::post('spt_comment', ''),
        ]);
        Flash::success('บันทึกความเห็น สพฐ. เรียบร้อย');
        App::redirect('island/cert#row' . $scId);
    }

    /** เขตรับรองได้เฉพาะโรงเรียนในสังกัดตน (สพฐ. รับรองได้ทุกแห่ง) */
    private function guard(int $scId): void
    {
        if (!$scId || !Auth::canAccessSchool($scId) || !IslandEval::exists($scId, $this->year)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
    }
}
