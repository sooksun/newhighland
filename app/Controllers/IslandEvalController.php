<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\IslandEval;
use App\Models\IslandOption;
use App\Models\MasterSao;
use App\Models\SaoNew;
use App\Models\SystemStatus;
use App\Services\IslandScoreService;
use App\Services\SchoolContext;

/** ส่วนที่ 2: ประเมินโรงเรียนพื้นที่เกาะ (ใหม่) */
class IslandEvalController
{
    private int $year;
    public function __construct() { $this->year = App::acadYear(); }

    /** แปลง areacode จาก dropdown สังกัด เป็นชื่อสังกัด */
    private static function resolveSaoName(): string
    {
        $areacode = trim((string) Request::post('sao_areacode', ''));
        if ($areacode !== '') {
            $name = SaoNew::name($areacode);
            if ($name !== '') return $name;
        }
        return (string) Request::post('sao_names', '');
    }

    private function guard(int $scId): void
    {
        if (!$scId || !Auth::canAccessSchool($scId)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
    }

    /** เลือกสังกัด/โรงเรียน */
    public function select(): void
    {
        Auth::require();
        if (Auth::isSchool()) {
            $scId = (int) Auth::scId();
            View::render('island/select', [
                'title' => 'เริ่มประเมินพื้นที่เกาะ', 'mode' => 'school',
                'sc_id' => $scId, 'sc_name' => Auth::name(), 'sao_name' => Auth::saoName(),
                'in_roster' => \App\Models\SchoolConfirm::inRoster(\App\Models\SchoolConfirm::AREA_ISLAND, $this->year, (string) $scId),
            ]);
            return;
        }
        $saoList = Auth::isAdmin() ? MasterSao::all() : array_filter([MasterSao::find((int) Auth::saoId())]);
        View::render('island/select', [
            'title' => 'เริ่มประเมินพื้นที่เกาะ',
            'mode' => Auth::isAdmin() ? 'admin' : 'sao',
            'sao_list' => $saoList, 'sel_sao_id' => Auth::isSao() ? Auth::saoId() : null,
        ]);
    }

    public function start(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        if (!$scId || !Auth::canAccessSchool($scId)) {
            Flash::error('ไม่พบโรงเรียน หรือไม่มีสิทธิ์'); App::redirect('island');
        }
        if (Auth::isSchool() && !SystemStatus::isOpen()) {
            Flash::error(SystemStatus::closedMessage());
            App::redirect('island');
        }
        // โรงเรียนพื้นที่เกาะเดิม (ในรายชื่อ) ผ่านประเมินแล้ว → ไม่ต้องประเมินใหม่ ให้ไปรับรองการคงอยู่
        if (\App\Models\SchoolConfirm::inRoster(\App\Models\SchoolConfirm::AREA_ISLAND, $this->year, (string) $scId)) {
            Flash::info('โรงเรียนนี้เป็นพื้นที่เกาะเดิม (ผ่านการประเมินแล้ว) — โปรดยืนยันที่หน้ารับรองการคงอยู่');
            App::redirect('confirm?area=' . \App\Models\SchoolConfirm::AREA_ISLAND);
        }
        // กรองเบื้องต้น: คัดกรองพื้นที่เกาะได้เฉพาะโรงเรียนในจังหวัดที่เคยมีพื้นที่เกาะ
        if (!\App\Models\SchoolConfirm::isEligibleSchool(\App\Models\SchoolConfirm::AREA_ISLAND, $this->year, (string) $scId)) {
            Flash::error('โรงเรียนนี้อยู่ในจังหวัดที่ไม่เคยมีโรงเรียนพื้นที่เกาะ จึงไม่เข้าเกณฑ์คัดกรองพื้นที่เกาะ');
            App::redirect('island');
        }
        $ctx = SchoolContext::build($scId, $this->year);
        IslandEval::ensure($scId, $this->year, $ctx['sc_name'], $ctx['province']);
        $_SESSION['work_sc_id'] = $scId;
        App::redirect('island/eval?sc_id=' . $scId);
    }

    public function edit(): void
    {
        Auth::require();
        $scId = (int) Request::get('sc_id', $_SESSION['work_sc_id'] ?? 0);
        $this->guard($scId);
        $eval = IslandEval::find($scId, $this->year);
        if (!$eval) { Flash::info('กรุณาเริ่มจากการเลือกโรงเรียน'); App::redirect('island'); }

        $selectedSao = SaoNew::areacodeOf($scId);
        if ($selectedSao === '' && !empty($eval['sao_names'])) {
            $selectedSao = SaoNew::areacodeByName((string) $eval['sao_names']);
        }

        View::render('island/eval', [
            'title'       => 'แบบประเมินพื้นที่เกาะ',
            'ctx'         => SchoolContext::build($scId, $this->year),
            'e'           => $eval,
            'saoList'     => SaoNew::all(),
            'selectedSao' => $selectedSao,
        ]);
    }

    public function save(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        if (Auth::isSchool() && !SystemStatus::isOpen()) {
            Flash::error(SystemStatus::closedMessage());
            App::redirect('island/eval?sc_id=' . $scId);
        }
        $eval = IslandEval::find($scId, $this->year) ?? [];

        $form = [
            'sc_names' => (string) Request::post('sc_names', $eval['sc_names'] ?? ''),
            'sao_names' => self::resolveSaoName(),
            'director_name' => (string) Request::post('director_name', ''),
            'director_tel' => (string) Request::post('director_tel', ''),
            'editor_name' => (string) Request::post('editor_name', ''),
            'editor_tel' => (string) Request::post('editor_tel', ''),
            'lgo' => (string) Request::post('lgo', ''),
            'adresss' => (string) Request::post('adresss', ''),
            'viledges' => (string) Request::post('viledges', ''),
            'moo' => (int) Request::post('moo', 0),
            'subdistrict' => (string) Request::post('subdistrict', ''),
            'district' => (string) Request::post('district', ''),
            'provinces' => (string) Request::post('provinces', ''),
            'lat' => (string) Request::post('lat', ''),
            'lng' => (string) Request::post('lng', ''),
            // นักเรียน
            'stu_kinder' => (int) Request::post('stu_kinder', 0),
            'stu_prim' => (int) Request::post('stu_prim', 0),
            'stu_second' => (int) Request::post('stu_second', 0),
            'stu_high' => (int) Request::post('stu_high', 0),
            // ครู/บุคลากร
            'teacher' => (int) Request::post('teacher', 0),
            'panuk' => (int) Request::post('panuk', 0),
            'gov_employee' => (int) Request::post('gov_employee', 0),
            'perm_teacher' => (int) Request::post('perm_teacher', 0),
            'perm_employee' => (int) Request::post('perm_employee', 0),
            'suport_teaching' => (int) Request::post('suport_teaching', 0),
            // คำตอบเกณฑ์
            'citeria01' => (int) Request::post('citeria01', 0),
            'citeria02' => (int) Request::post('citeria02', 0),
            'citeria03' => (int) Request::post('citeria03', 0),
            'citeria04' => (int) Request::post('citeria04', 0),
            'citeria05' => (float) Request::post('citeria05', 0),
            'citeria06' => (float) Request::post('citeria06', 0),
            'citeria07' => (int) Request::post('citeria07', 0),
            'citeria08' => (float) Request::post('citeria08', 0),
            'citeria09' => (int) Request::post('citeria09', 0),
            'citeria10' => (int) Request::post('citeria10', 0),
            'citeria11' => (int) Request::post('citeria11', 0),
            'citeria12' => (int) Request::post('citeria12', 0),
            'citeria13' => (int) Request::post('citeria13', 0),
            'citeria14' => (int) Request::post('citeria14', 0),
            'citeria15' => (int) Request::post('citeria15', 0),
        ];
        $form['stu_sum'] = $form['stu_kinder'] + $form['stu_prim'] + $form['stu_second'] + $form['stu_high'];
        $form['sum_teacher'] = $form['teacher'] + $form['panuk'] + $form['gov_employee']
            + $form['perm_teacher'] + $form['perm_employee'] + $form['suport_teaching'];
        $form['distance_to_province'] = $form['citeria05'];

        // ไฟล์แนบ
        foreach (['04', '09', '10', '11', '12', '13', '14'] as $nn) {
            $path = Upload::save("refdoc_$nn", "uploads/island/{$this->year}", "{$scId}_citeria{$nn}");
            if ($path !== null) $form["citeria{$nn}_refdoc"] = $path;
        }

        $scores = IslandScoreService::calcIsland(array_merge($eval, $form));
        // ด่านคัดกรอง: ถ้าไม่ใช่เกาะ → ไม่จัดเป็นพื้นที่เกาะ
        if ($form['citeria01'] !== 1) $scores['island_type'] = 0;

        IslandEval::save($scId, $this->year, $form, $scores);

        $msg = $form['citeria01'] !== 1
            ? 'บันทึกแล้ว — ไม่ผ่านด่านคัดกรอง (ไม่ใช่พื้นที่เกาะ)'
            : sprintf('บันทึกสำเร็จ — คะแนนรวม %.2f (%s)', $scores['sum_score'], self::typeLabel((int) $scores['island_type']));
        Flash::success($msg);
        App::redirect('island/eval?sc_id=' . $scId);
    }

    /** บันทึกการรับรอง สพท./สพฐ. (AJAX) */
    public function cert(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        $level = (string) Request::post('level', '');
        $status = (int) Request::post('status', 0);
        $comment = (string) Request::post('comment', '');
        if ($level === 'sao') {
            IslandEval::saveCert($scId, $this->year, ['confirmstatus' => $status, 'confirmcomment' => $comment]);
        } elseif ($level === 'spt') {
            if (!Auth::isAdmin()) View::json(['error' => 'forbidden'], 403);
            IslandEval::saveCert($scId, $this->year, ['spt_commit' => $status, 'spt_comment' => $comment]);
        } else {
            View::json(['error' => 'bad level'], 400);
        }
        View::json(['ok' => true]);
    }

    public function print(): void
    {
        Auth::require();
        $scId = (int) Request::get('sc_id', 0);
        $this->guard($scId);
        if (!IslandEval::exists($scId, $this->year)) { Flash::error('ยังไม่มีข้อมูล'); App::redirect('island'); }
        \App\Services\PdfService::islandEval($scId, $this->year);
    }

    public static function typeLabel(int $t): string
    {
        return [0 => 'ไม่เป็นพื้นที่เกาะ (ต่ำกว่า 50)', 1 => 'ยุ่งยาก (50–59)', 2 => 'ยุ่งยากมาก (60–69)', 3 => 'ยุ่งยากมากที่สุด (70+)'][$t] ?? '-';
    }
}
