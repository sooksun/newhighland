<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\SystemStatus;

/** ปิด/เปิดระบบคัดกรอง (สวิตช์เดียว ทั้งระบบ) — เฉพาะ สพฐ. (admin) */
class SystemStatusController
{
    public function index(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        View::render('admin/system_status', [
            'title'  => 'ปิด-เปิดระบบการคัดกรอง',
            'status' => SystemStatus::info(),
        ]);
    }

    public function save(): void
    {
        Auth::require([Auth::ROLE_ADMIN]);
        Csrf::verify();
        $open    = Request::post('is_open') === '1';
        $message = (string) Request::post('closed_message', '');
        SystemStatus::setStatus($open, $message, Auth::name());
        Flash::success($open ? 'เปิดระบบคัดกรองแล้ว — โรงเรียนกรอก/ส่งข้อมูลได้ตามปกติ' : 'ปิดระบบคัดกรองแล้ว — โรงเรียนดูข้อมูลได้ แต่กรอก/ส่งข้อมูลไม่ได้');
        App::redirect('admin/system');
    }
}
