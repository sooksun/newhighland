<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\View;

/**
 * หน้าแรกสาธารณะ (Landing) — ข้อมูลโครงการ + ปุ่มเข้าสู่ระบบ
 * ไม่ต้องล็อกอิน ; ใช้ layout 'public' (มี topbar/footer ในตัว view)
 */
class LandingController
{
    public function index(): void
    {
        View::render('home/landing', ['title' => 'หน้าแรก'], 'public');
    }
}
