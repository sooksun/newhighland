<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\CriteriaOption;
use App\Models\Hilltrib;
use App\Models\HighlandEval;
use App\Models\SaoNew;
use App\Models\SystemStatus;
use App\Services\ScoreService;
use App\Services\SchoolContext;

/** ส่วนที่ 1 (1C/1D/1E): แบบประเมิน 16 ข้อ + คิดคะแนน + พิมพ์ */
class HighlandEvalController
{
    private int $year;

    public function __construct()
    {
        $this->year = App::acadYear();
    }

    private function guard(int $scId): void
    {
        if (!$scId || !Auth::canAccessSchool($scId)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
    }

    /** แปลง/ทำความสะอาดเบอร์โทรเป็นรูปแบบ "081-277-1948" (server-side) */
    private static function normTel(string $tel): string
    {
        $d = preg_replace('/\D/', '', $tel);
        if (strlen($d) === 10) {
            return substr($d, 0, 3) . '-' . substr($d, 3, 3) . '-' . substr($d, 6, 4);
        }
        return $tel;
    }

    /** แปลง areacode จาก dropdown สังกัด เป็นชื่อสังกัดเพื่อเก็บลง sao_names */
    private static function resolveSaoName(): string
    {
        $areacode = trim((string) Request::post('sao_areacode', ''));
        if ($areacode !== '') {
            $name = SaoNew::name($areacode);
            if ($name !== '') return $name;
        }
        return (string) Request::post('sao_names', '');   // เผื่อกรณีไม่มีในรายการ
    }

    /** แบบประเมิน */
    public function edit(): void
    {
        Auth::require();
        $scId = (int) Request::get('sc_id', $_SESSION['work_sc_id'] ?? 0);
        $this->guard($scId);

        $eval = HighlandEval::find($scId, $this->year);
        if (!$eval) {
            Flash::info('กรุณาปักหมุดและวัดความสูงก่อนกรอกแบบประเมิน');
            App::redirect('map/search?sc_id=' . $scId);
        }

        $ctx = SchoolContext::build($scId, $this->year);

        // สังกัด: เลือกค่าเริ่มต้นจาก school_new.areacode (แหล่งจริง) มิฉะนั้นย้อนจากชื่อที่เคยบันทึก
        $selectedSao = SaoNew::areacodeOf($scId);
        if ($selectedSao === '' && !empty($eval['sao_names'])) {
            $selectedSao = SaoNew::areacodeByName((string) $eval['sao_names']);
        }

        View::render('highland/eval', [
            'title'        => 'แบบประเมินพื้นที่สูง',
            'ctx'          => $ctx,
            'e'            => $eval,
            'options'      => CriteriaOption::allSets(),
            'hilltribList' => Hilltrib::all(),
            'hilltribRows' => HighlandEval::hilltribRows($scId, $this->year),
            'saoList'      => SaoNew::all(),
            'selectedSao'  => $selectedSao,
        ]);
    }

    /** บันทึกแบบประเมิน + คิดคะแนน */
    public function save(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        if (Auth::isSchool() && !SystemStatus::isOpen()) {
            Flash::error(SystemStatus::closedMessage());
            App::redirect('highland/eval?sc_id=' . $scId);
        }

        $eval = HighlandEval::find($scId, $this->year) ?? [];

        // เก็บค่าจากฟอร์ม (เฉพาะที่แก้ไขได้)
        $form = [
            'sc_names'     => (string) Request::post('sc_names', $eval['sc_names'] ?? ''),
            // สังกัด: รับ areacode จาก dropdown แล้วแปลงเป็นชื่อสังกัดเพื่อเก็บ/พิมพ์
            'sao_names'    => self::resolveSaoName(),
            'director_name'=> (string) Request::post('director_name', ''),
            'director_tel' => self::normTel(Request::post('director_tel', '')),
            'editor_name'  => (string) Request::post('editor_name', ''),
            'editor_tel'   => self::normTel(Request::post('editor_tel', '')),
            'lgo'          => (string) Request::post('lgo', ''),
            'adresss'      => (string) Request::post('adresss', ''),
            'viledges'     => (string) Request::post('viledges', ''),
            'moo'          => (int) Request::post('moo', 0),
            'subdistrict'  => (string) Request::post('subdistrict', ''),
            'district'     => (string) Request::post('district', ''),
            'provinces'    => (string) Request::post('provinces', ''),
            // นักเรียน
            'stu_kinder'   => (int) Request::post('stu_kinder', 0),
            'stu_prim'     => (int) Request::post('stu_prim', 0),
            'stu_second'   => (int) Request::post('stu_second', 0),
            'stu_high'     => (int) Request::post('stu_high', 0),
            'stu_sleep_boy'=> (int) Request::post('stu_sleep_boy', 0),
            'stu_sleep_girl'=>(int) Request::post('stu_sleep_girl', 0),
            // คำตอบเกณฑ์
            'citeria02'    => (int) Request::post('citeria02', 0),
            'citeria03'    => (int) Request::post('citeria03', 0),
            'citeria04'    => (int) Request::post('citeria04', 0),
            'citeria041'   => (float) Request::post('citeria041', 0),
            'citeria06'    => (int) Request::post('citeria06', 0),
            'citeria07'    => self::csv(Request::post('citeria07', [])),
            'citeria08'    => self::csv(Request::post('citeria08', [])),
            'citeria09'    => self::csv(Request::post('citeria09', [])),
            'citeria10'    => self::csv(Request::post('citeria10', [])),
            'citeria13'    => (int) Request::post('citeria13', 0),
            'citeria14'    => (int) Request::post('citeria14', 0),
            'citeria15'    => (int) Request::post('citeria15', 0),
            'citeria16'    => (int) Request::post('citeria16', 0),
            // เหตุผลที่ขอคัดกรองครั้งนี้ (จำกัด 255 ตัวอักษร)
            'screen_reason'=> mb_substr(trim((string) Request::post('screen_reason', '')), 0, 255),
        ];
        $form['stu_sum']       = $form['stu_kinder'] + $form['stu_prim'] + $form['stu_second'] + $form['stu_high'];
        $form['stu_sleep_sum'] = $form['stu_sleep_boy'] + $form['stu_sleep_girl'];

        // ไฟล์แนบ (citeriaNN_refdoc) — อัปโหลดเฉพาะข้อที่ต้องแนบเอกสาร/ภาพ
        foreach (['04', '07', '08', '09', '10', '15', '16'] as $nn) {
            $path = \App\Core\Upload::save("refdoc_$nn", "uploads/highland/{$this->year}", "{$scId}_citeria{$nn}");
            if ($path !== null) $form["citeria{$nn}_refdoc"] = $path;
        }

        // คิดคะแนน: รวมค่า geo เดิม (citeria01, citeria05, average_height) เข้ากับฟอร์ม
        $merged = array_merge($eval, $form);
        $hilltribRows = HighlandEval::hilltribRows($scId, $this->year);
        $scores = ScoreService::calcHighland($merged, $hilltribRows);

        HighlandEval::save($scId, $this->year, $form, $scores);

        Flash::success(sprintf('บันทึกสำเร็จ — คะแนนรวม %.2f (%s)',
            $scores['sum_score'], self::typeLabel((int) $scores['highland_type'])));
        App::redirect('highland/eval?sc_id=' . $scId);
    }

    /** AJAX: เพิ่มกลุ่มชาติพันธุ์ */
    public function addHilltrib(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        if (Auth::isSchool() && !SystemStatus::isOpen()) View::json(['error' => SystemStatus::closedMessage()], 423);
        $ethnic = (int) Request::post('hilltrib', 0);
        $num    = (int) Request::post('hilltrib_number', 0);
        if ($ethnic > 0) {
            HighlandEval::addHilltrib($scId, $this->year, $ethnic, $num);
            $this->recompute($scId);
        }
        View::json($this->hilltribState($scId));
    }

    /** AJAX: ลบกลุ่มชาติพันธุ์ */
    public function deleteHilltrib(): void
    {
        Auth::require();
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        if (Auth::isSchool() && !SystemStatus::isOpen()) View::json(['error' => SystemStatus::closedMessage()], 423);
        HighlandEval::deleteHilltrib($scId, $this->year, (int) Request::post('hilltrib', 0));
        $this->recompute($scId);
        View::json($this->hilltribState($scId));
    }

    /** บันทึกการรับรองระดับ สพท./สพฐ. (AJAX) */
    public function cert(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $scId = (int) Request::post('sc_id', 0);
        $this->guard($scId);
        $level   = (string) Request::post('level', '');
        $status  = (int) Request::post('status', 0);
        $comment = (string) Request::post('comment', '');

        if ($level === 'sao') {
            HighlandEval::saveCert($scId, $this->year, ['confirmstatus' => $status, 'confirmcomment' => $comment]);
        } elseif ($level === 'spt') {
            if (!Auth::isAdmin()) View::json(['error' => 'forbidden'], 403);
            HighlandEval::saveCert($scId, $this->year, ['spt_commit' => $status, 'spt_comment' => $comment]);
        } else {
            View::json(['error' => 'bad level'], 400);
        }
        View::json(['ok' => true]);
    }

    /** พิมพ์เป็น PDF (mPDF overlay บน template.pdf) */
    public function print(): void
    {
        Auth::require();
        $scId = (int) Request::get('sc_id', 0);
        $this->guard($scId);
        if (!HighlandEval::exists($scId, $this->year)) {
            Flash::error('ยังไม่มีข้อมูลแบบประเมิน'); App::redirect('highland');
        }
        \App\Services\PdfService::highlandEval($scId, $this->year);
    }

    // ---- helpers ----

    private function recompute(int $scId): void
    {
        $eval = HighlandEval::find($scId, $this->year);
        if (!$eval) return;
        $rows   = HighlandEval::hilltribRows($scId, $this->year);
        $scores = ScoreService::calcHighland($eval, $rows);
        HighlandEval::save($scId, $this->year, [], $scores);
    }

    private function hilltribState(int $scId): array
    {
        $rows = HighlandEval::hilltribRows($scId, $this->year);
        $eval = HighlandEval::find($scId, $this->year);
        $total = 0;
        foreach ($rows as $r) $total += (int) $r['hilltrib_number'];
        return [
            'ok'        => true,
            'rows'      => $rows,
            'total'     => $total,
            'groups'    => count($rows),
            'sum_score' => $eval['sum_score'] ?? null,
        ];
    }

    private static function csv($val): string
    {
        if (is_array($val)) {
            $val = array_values(array_filter(array_map('intval', $val)));
            sort($val);
            return implode(',', $val);
        }
        return (string) $val;
    }

    public static function typeLabel(int $type): string
    {
        return [
            0 => 'ไม่เป็นพื้นที่สูง (ต่ำกว่า 50)',
            1 => 'ยุ่งยาก (50–59)',
            2 => 'ยุ่งยากมาก (60–69)',
            3 => 'ยุ่งยากมากที่สุด (70+)',
        ][$type] ?? '-';
    }
}
