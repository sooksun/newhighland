<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\MasterSchool;
use App\Models\SaoUser;
use App\Models\SchoolUser;

/**
 * จัดการบัญชีผู้ใช้ + รหัสผ่าน
 *   - สพฐ. (admin): จัดการได้ทุกบัญชี — tab=school → ตาราง `user` | tab=sao → `master_saonew`
 *   - สำนักงานเขต (sao): จัดการได้เฉพาะ "บัญชีโรงเรียนในเขตตน" (tab=school, scope ด้วย master_school.sao_code)
 * รหัสผ่านเก็บ plaintext เพื่อเข้ากันได้กับ Auth::attempt เดิม (PRD §10)
 */
class UserAdminController
{
    /** แท็บปัจจุบัน — sao บังคับเป็น 'school' เสมอ (จัดการเฉพาะบัญชีโรงเรียน) */
    private function resolveTab(string $raw): string
    {
        if (!Auth::isAdmin()) return 'school';
        return $raw === 'sao' ? 'sao' : 'school';
    }

    /** รายการ + ค้นหา (+ โหลดข้อมูลแถวเพื่อแก้ไข ถ้ามี ?edit=) */
    public function index(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        $isAdmin = Auth::isAdmin();
        $tab = $this->resolveTab((string) Request::get('tab', 'school'));
        $q   = trim((string) Request::get('q', ''));
        $editKey = (string) Request::get('edit', '');
        // แท็บเขต: แสดงเฉพาะเขตที่มีโรงเรียนพื้นที่สูง/เกาะ (default เปิด; ติ๊กออกเพื่อดูทุกเขต)
        $only = Request::get('only', '1') !== '0';

        if ($tab === 'sao') {
            $rows = SaoUser::search($q);
            if ($only) { $rows = SaoUser::keepWithRoster($rows, App::acadYear()); }
            $edit = $editKey !== '' ? SaoUser::find($editKey) : null;
            foreach ($rows as &$r) { $r['is_admin'] = SaoUser::isAdminAccount($r); } unset($r);
        } elseif ($isAdmin) {
            $rows = $q !== '' ? SchoolUser::search($q) : [];   // ตาราง user ใหญ่มาก — แสดงเมื่อค้นหา
            $edit = $editKey !== '' ? SchoolUser::find($editKey) : null;
        } else {
            // สำนักงานเขต: แสดงเฉพาะบัญชีโรงเรียนในเขตตน (เขตเล็กพอที่จะแสดงทั้งหมดได้)
            $saoId = (int) Auth::saoId();
            $rows  = SchoolUser::listBySao($saoId, $q);
            $edit  = $editKey !== '' ? SchoolUser::find($editKey) : null;
            if ($edit && !SchoolUser::inSao((string) $edit['citicens_id'], $saoId)) {
                $edit = null;   // กันแก้ไขบัญชีนอกเขต
            }
        }

        View::render('admin/users', [
            'title'      => 'จัดการผู้ใช้และรหัสผ่าน',
            'tab'        => $tab,
            'q'          => $q,
            'rows'       => $rows,
            'edit'       => $edit,
            'only'       => $only,
            'isAdmin'    => $isAdmin,
            'schoolCount'=> ($tab === 'school' && $isAdmin) ? SchoolUser::count() : 0,
        ]);
    }

    /** เพิ่ม/แก้ไขผู้ใช้ */
    public function save(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $tab = $this->resolveTab((string) Request::post('tab', 'school'));
        $key = trim((string) Request::post('key', ''));   // ว่าง = เพิ่มใหม่
        $back = 'admin/users?tab=' . $tab;

        if ($tab === 'sao') {
            $this->saveSao($key, $back);   // เฉพาะ admin (resolveTab บังคับ sao เป็น school แล้ว)
        } else {
            $this->saveSchool($key, $back);
        }
    }

    private function saveSao(string $key, string $back): void
    {
        $code = trim((string) Request::post('code', ''));
        $user = trim((string) Request::post('user', ''));
        $pass = (string) Request::post('password', '');

        if ($code === '' || $user === '') {
            Flash::error('กรุณากรอกชื่อหน่วยงานและชื่อผู้ใช้');
            App::redirect($back);
        }
        $user = mb_substr($user, 0, 20);
        if (SaoUser::userExists($user, $key !== '' ? $key : null)) {
            Flash::error('ชื่อผู้ใช้ "' . $user . '" ถูกใช้แล้ว');
            App::redirect($back);
        }

        if ($key === '') {
            // เพิ่มใหม่ — ต้องระบุ id (รหัสหน่วยงาน) ที่ไม่ซ้ำ
            $id = trim((string) Request::post('id', ''));
            if ($id === '') { Flash::error('กรุณาระบุรหัสหน่วยงาน (id)'); App::redirect($back); }
            if (SaoUser::idExists($id)) { Flash::error('รหัสหน่วยงาน "' . $id . '" มีอยู่แล้ว'); App::redirect($back); }
            if ($pass === '') { Flash::error('กรุณากำหนดรหัสผ่าน'); App::redirect($back); }
            SaoUser::create($id, [
                'code' => $code, 'code_name' => $id . ' - ' . $code,
                'user' => $user, 'password' => mb_substr($pass, 0, 100),
            ]);
            Flash::success('เพิ่มบัญชีเขต/สพฐ. "' . $user . '" เรียบร้อย');
        } else {
            $data = ['code' => $code, 'code_name' => $key . ' - ' . $code, 'user' => $user];
            if ($pass !== '') $data['password'] = mb_substr($pass, 0, 100);
            SaoUser::update($key, $data);
            Flash::success('แก้ไขบัญชี "' . $user . '" เรียบร้อย');
        }
        App::redirect($back);
    }

    private function saveSchool(string $key, string $back): void
    {
        $name = trim((string) Request::post('name', ''));
        $user = trim((string) Request::post('user', ''));
        $pass = (string) Request::post('password', '');
        $scId = trim((string) Request::post('sc_id', ''));

        if ($name === '' || $user === '' || $scId === '') {
            Flash::error('กรุณากรอกชื่อโรงเรียน ชื่อผู้ใช้ และรหัสโรงเรียน');
            App::redirect($back . '&q=' . urlencode($user));
        }
        $user = mb_substr($user, 0, 8);   // คอลัมน์ user.user = varchar(8)
        if (SchoolUser::userExists($user, $key !== '' ? $key : null)) {
            Flash::error('ชื่อผู้ใช้ "' . $user . '" ถูกใช้แล้ว');
            App::redirect($back . '&q=' . urlencode($user));
        }
        // หา sao_id จากทะเบียนโรงเรียน (best-effort) = เขตปัจจุบันของโรงเรียน
        $saoId = (int) (MasterSchool::find($scId)['sao_code'] ?? 0);

        // สำนักงานเขต (sao): จัดการได้เฉพาะโรงเรียนในเขตตน
        if (!Auth::isAdmin()) {
            $mySao = (int) Auth::saoId();
            if ($saoId !== $mySao) {
                Flash::error('โรงเรียนรหัส "' . $scId . '" ไม่อยู่ในเขตของท่าน — จัดการบัญชีไม่ได้');
                App::redirect($back . '&q=' . urlencode($user));
            }
            // แก้ไข: บัญชีเดิมต้องเป็นของโรงเรียนในเขตตน
            if ($key !== '' && !SchoolUser::inSao($key, $mySao)) {
                Flash::error('ไม่มีสิทธิ์แก้ไขบัญชีนอกเขตของท่าน');
                App::redirect($back);
            }
        }

        $data = ['name' => $name, 'user' => $user, 'sc_id' => $scId, 'sao_id' => $saoId];

        if ($key === '') {
            if ($pass === '') { Flash::error('กรุณากำหนดรหัสผ่าน'); App::redirect($back); }
            $data['password'] = mb_substr($pass, 0, 20);
            $data['level'] = 'user';
            SchoolUser::create($data);
            Flash::success('เพิ่มบัญชีโรงเรียน "' . $user . '" เรียบร้อย');
        } else {
            if ($pass !== '') $data['password'] = mb_substr($pass, 0, 20);
            SchoolUser::update($key, $data);
            Flash::success('แก้ไขบัญชี "' . $user . '" เรียบร้อย');
        }
        App::redirect($back . '&q=' . urlencode($user));
    }

    /** รีเซ็ต/เปลี่ยนรหัสผ่าน */
    public function password(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $tab = $this->resolveTab((string) Request::post('tab', 'school'));
        $key = trim((string) Request::post('key', ''));
        $pass = (string) Request::post('password', '');
        $back = 'admin/users?tab=' . $tab;

        if ($key === '' || $pass === '') {
            Flash::error('กรุณากำหนดรหัสผ่านใหม่');
            App::redirect($back);
        }
        if ($tab === 'sao') {
            SaoUser::setPassword($key, mb_substr($pass, 0, 100));   // เฉพาะ admin
        } else {
            // สำนักงานเขต: เปลี่ยนได้เฉพาะบัญชีในเขตตน
            if (!Auth::isAdmin() && !SchoolUser::inSao($key, (int) Auth::saoId())) {
                Flash::error('ไม่มีสิทธิ์เปลี่ยนรหัสผ่านบัญชีนอกเขตของท่าน');
                App::redirect($back);
            }
            SchoolUser::setPassword($key, mb_substr($pass, 0, 20));
            $back .= '&q=' . urlencode(trim((string) Request::post('q', '')));
        }
        Flash::success('เปลี่ยนรหัสผ่านเรียบร้อย');
        App::redirect($back);
    }

    /** ลบบัญชี */
    public function delete(): void
    {
        Auth::require([Auth::ROLE_SAO, Auth::ROLE_ADMIN]);
        Csrf::verify();
        $tab = $this->resolveTab((string) Request::post('tab', 'school'));
        $key = trim((string) Request::post('key', ''));
        $back = 'admin/users?tab=' . $tab;

        if ($key === '') { App::redirect($back); }

        if ($tab === 'sao') {
            // กันลบบัญชีตัวเอง (เฉพาะ admin)
            if ($key === (string) (Auth::user()['sao_code'] ?? '')) {
                Flash::error('ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้');
                App::redirect($back);
            }
            SaoUser::delete($key);
        } else {
            // สำนักงานเขต: ลบได้เฉพาะบัญชีในเขตตน
            if (!Auth::isAdmin() && !SchoolUser::inSao($key, (int) Auth::saoId())) {
                Flash::error('ไม่มีสิทธิ์ลบบัญชีนอกเขตของท่าน');
                App::redirect($back);
            }
            SchoolUser::delete($key);
            $back .= '&q=' . urlencode(trim((string) Request::post('q', '')));
        }
        Flash::success('ลบบัญชีเรียบร้อย');
        App::redirect($back);
    }
}
