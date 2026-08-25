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
use App\Models\SystemStatus;

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

    /**
     * ดูรายละเอียดการคงอยู่รายโรงเรียน (อ่านอย่างเดียว) สำหรับเขต/สพฐ.
     * เข้าจากปุ่ม "ดูรายละเอียด" ในรายการ ; รับ ?back=<query ของรายการ> ให้ปุ่มย้อนกลับคงตัวกรองเดิม
     */
    public function view(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        $row = SchoolConfirm::get((int) Request::get('id', 0));
        if (!$row || (Auth::isSao() && (int) $row['sao_id'] !== (int) Auth::saoId())) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
        View::render('confirm/view', [
            'title' => 'รายละเอียดการคงอยู่ — ' . ($row['sc_name'] ?: $row['sc_id']),
            'r'     => $row,
            'area'  => (int) $row['area_type'],
            'back'  => ltrim(trim((string) Request::get('back', '')), '?'),
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
        if (!SystemStatus::isOpen()) {
            Flash::error(SystemStatus::closedMessage());
            App::redirect('confirm?area=' . $row['area_type']);
        }

        // สถานะการคงอยู่: 1=คงอยู่, 0=ยุบ/รวม/เลิก, 2=ขาดคุณสมบัติ (กันค่าอื่นที่ไม่รู้จัก → คงอยู่)
        $opened = (int) Request::post('opened', 1);
        if (!in_array($opened, [SchoolConfirm::OPENED_YES, SchoolConfirm::OPENED_CLOSED, SchoolConfirm::OPENED_DISQUALIFIED], true)) {
            $opened = SchoolConfirm::OPENED_YES;
        }
        $profile = [];
        foreach (array_merge(SchoolConfirm::PROFILE_TEXT_FIELDS, SchoolConfirm::PROFILE_INT_FIELDS) as $f) {
            $profile[$f] = Request::post($f, '');
        }

        // กรณียุบ/รวม/เลิก: เก็บประเภท + (ถ้าไปเรียนรวม) ชื่อ/รหัสโรงเรียนปลายทาง
        $closeType    = $opened === SchoolConfirm::OPENED_CLOSED ? (int) Request::post('close_type', 0) : 0;
        $isMerge      = $closeType === SchoolConfirm::CLOSE_MERGE;
        $mergedTo     = $isMerge ? (int) Request::post('merged_to', 0) : null;
        $mergedToName = $isMerge ? (string) Request::post('merged_to_name', '') : '';
        // กรณีขาดคุณสมบัติ: เก็บเหตุผล (เช่น มีสะพานเชื่อมแผ่นดินใหญ่)
        $disqualifyReason = $opened === SchoolConfirm::OPENED_DISQUALIFIED ? (string) Request::post('disqualify_reason', '') : '';

        $wantSubmit = Request::post('action') === 'submit';   // "ส่งข้อมูล" = ล็อก, อื่น ๆ = บันทึกร่าง

        // ความครบถ้วน: โรงเรียนที่ "คงอยู่" (opened=1) ต้องมีจำนวนนักเรียนรวม > 0 และครูรวม > 0 จึงจะ "ส่ง" ได้
        // (กรณียุบ/รวม/เลิก ไม่ต้องมีข้อมูลนักเรียน/ครู) — ถ้ายังไม่ครบ บันทึกเป็นร่างได้ แต่ส่งไม่ได้
        $stdTotal = max(0, (int) ($profile['std_male'] ?? 0)) + max(0, (int) ($profile['std_female'] ?? 0));
        $tchTotal = array_sum(array_map(
            fn($f) => max(0, (int) ($profile[$f] ?? 0)),
            ['tch_govt', 'tch_hire', 'tch_deputy', 'tch_director']
        ));
        $incomplete = $opened === SchoolConfirm::OPENED_YES && ($stdTotal === 0 || $tchTotal === 0);
        $submit = $wantSubmit && !$incomplete;   // ส่งไม่ได้ถ้าข้อมูลไม่ครบ → บันทึกเป็นร่างแทน

        SchoolConfirm::schoolConfirm(
            (int) $row['id'],
            $opened,
            $opened === SchoolConfirm::OPENED_YES ? 1 : 0,
            $mergedTo,
            (string) Request::post('school_note', ''),
            $profile,
            $closeType,
            $mergedToName,
            $submit,
            $disqualifyReason
        );
        if ($wantSubmit && $incomplete) {
            Flash::error('ยังส่งข้อมูลไม่ได้ — ต้องกรอก “จำนวนนักเรียนรวม” และ “จำนวนครูและผู้บริหารรวม” ให้มากกว่า 0 ก่อนส่ง (ระบบบันทึกเป็นร่างให้แล้ว แก้ไขเพิ่มแล้วกดส่งอีกครั้ง)');
        } else {
            Flash::success($submit
                ? 'ส่งข้อมูลเรียบร้อย — ระบบล็อกการแก้ไขแล้ว (ติดต่อเขตหากต้องการแก้ไข)'
                : 'บันทึกข้อมูล (ร่าง) เรียบร้อย — กลับมาแก้ไข/อัปโหลดเพิ่มได้ภายหลัง');
        }
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
            $this->respondSave(false, 'ไม่พบรายการ หรือไม่มีสิทธิ์', $row ?: null);
        }
        $status = (int) Request::post('sao_status', 0);
        // "รับรอง" (สถานะ 1) ได้เฉพาะเมื่อข้อมูลโรงเรียนครบ: คงอยู่ + นักเรียนรวม > 0 + ครูรวม > 0
        // (สถานะ รอ/ไม่รับรอง และกรณียุบ/รวม/เลิก ไม่ต้องตรวจ)
        if ($status === 1 && (int) $row['opened'] === 1
            && ((int) ($row['std_total'] ?? 0) === 0 || (int) ($row['tch_total'] ?? 0) === 0)) {
            $this->respondSave(false, 'รับรองไม่ได้ — ข้อมูลโรงเรียนไม่ครบ (จำนวนนักเรียนรวมหรือจำนวนครูรวมเป็น 0) กรุณาให้โรงเรียนกรอกข้อมูลให้ครบก่อน', $row);
        }
        SchoolConfirm::saoCert((int) $row['id'], $status, (string) Request::post('sao_comment', ''));
        $this->respondSave(true, 'บันทึกการรับรองระดับเขตเรียบร้อย', $row);
    }

    /**
     * ส่งออก "บัญชีแนบท้าย รายชื่อโรงเรียนเดิมที่ยืนยันการคงอยู่" เป็น Word (.docx) — แบ่งตามสำนักงานเขต
     * เฉพาะเขต (สพท.) / สพฐ. ; เขตเห็นเฉพาะสังกัดตน (เหมือน index())
     */
    public function exportWord(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        $saoId = Auth::isSao() ? (int) Auth::saoId() : null;
        \App\Services\WordReportService::confirmAppendix($this->area(), $saoId, $this->year);
    }

    /** สพฐ. */
    public function spt(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        Csrf::verify();
        $row = SchoolConfirm::get((int) Request::post('id', 0));
        if (!$row) { $this->respondSave(false, 'ไม่พบรายการ', null); }
        SchoolConfirm::sptCert((int) $row['id'], (int) Request::post('spt_status', 0), (string) Request::post('spt_comment', ''));
        $this->respondSave(true, 'บันทึกความเห็น สพฐ. เรียบร้อย', $row);
    }

    /**
     * ตอบผลการบันทึก (สพท./สพฐ.) — AJAX คืน JSON (ให้ฝั่งหน้าเว็บเด้ง modal "บันทึกแล้ว" โดยไม่โหลดหน้าใหม่)
     * ส่วนการเรียกแบบปกติ (ไม่ใช่ AJAX) ยัง flash + redirect กลับหน้าเดิมเหมือนเดิม
     */
    private function respondSave(bool $ok, string $msg, ?array $row): void
    {
        if (Request::isAjax()) {
            View::json(['ok' => $ok, 'message' => $msg], $ok ? 200 : 422);
        }
        $ok ? Flash::success($msg) : Flash::error($msg);
        $area = $row['area_type'] ?? $this->area();
        App::redirect('confirm?area=' . $area . ($row ? '#row' . $row['id'] : ''));
    }
}
