<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\HighlandEval;

/**
 * ส่วนที่ 1 (ขั้นที่ 5): รายการ "รออนุมัติ" — ให้ สพท. (เขต) / สพฐ. รับรองผลประเมินพื้นที่สูง
 * ที่โรงเรียนทำเสร็จแล้ว (มี sum_score) มุมมองคล้ายหน้ารับรองการคงอยู่ (ConfirmController)
 * บันทึกลงคอลัมน์รับรองของ highland_eval: สพท. = confirmstatus/confirmcomment, สพฐ. = spt_commit/spt_comment
 */
class HighlandCertController
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

        $saoId   = Auth::isSao() ? (int) Auth::saoId() : null;   // สพฐ. = ทุกเขต
        $filters = [
            'q'        => trim((string) Request::get('q', '')),
            'province' => trim((string) Request::get('province', '')),
            'status'   => Request::get('status', ''),
        ];

        View::render('highland/cert_list', [
            'title'   => 'รออนุมัติ — รับรองผลประเมินพื้นที่สูง',
            'rows'    => HighlandEval::listForCert($saoId, $this->year, $filters),
            'stats'   => HighlandEval::certStats($saoId, $this->year),
            'filters' => $filters,
        ]);
    }

    /** เขต (สพท.) บันทึกผลการรับรอง */
    public function sao(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);

        HighlandEval::saveCert($scId, $this->year, [
            'confirmstatus'  => (int) Request::post('confirmstatus', 0),
            'confirmcomment' => (string) Request::post('confirmcomment', ''),
        ]);
        Flash::success('บันทึกการรับรองระดับเขต (สพท.) เรียบร้อย');
        App::redirect('highland/cert#row' . $scId);
    }

    /** สพฐ. บันทึกความเห็น */
    public function spt(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !HighlandEval::exists($scId, $this->year)) {
            Flash::error('ไม่พบรายการประเมิน');
            App::redirect('highland/cert');
        }

        HighlandEval::saveCert($scId, $this->year, [
            'spt_commit'  => (int) Request::post('spt_commit', 0),
            'spt_comment' => (string) Request::post('spt_comment', ''),
        ]);
        Flash::success('บันทึกความเห็น สพฐ. เรียบร้อย');
        App::redirect('highland/cert#row' . $scId);
    }

    /** เขตรับรองได้เฉพาะโรงเรียนในสังกัดตน (สพฐ. รับรองได้ทุกแห่ง) */
    private function guard(int $scId): void
    {
        if (!$scId || !Auth::canAccessSchool($scId) || !HighlandEval::exists($scId, $this->year)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
    }
}
