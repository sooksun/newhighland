<?php
/**
 * Front controller (web root = โฟลเดอร์โปรเจกต์ ตามค่าเริ่มต้นของ Laragon)
 * โฟลเดอร์ app/, config/, database/, docs/, docsref/ ถูกปิดกั้นจากเว็บด้วย .htaccess
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Request;
use App\Controllers\AuthController;
use App\Controllers\LandingController;
use App\Controllers\HomeController;
use App\Controllers\DashboardController;
use App\Controllers\SchoolController;
use App\Controllers\MapController;
use App\Controllers\HighlandEvalController;
use App\Controllers\HighlandCertController;
use App\Controllers\ConfirmController;
use App\Controllers\IslandEvalController;
use App\Controllers\IslandCertController;
use App\Controllers\UserAdminController;

$router = new Router();

// ---- หน้าแรกสาธารณะ (Landing) + Dashboard (ต้องล็อกอิน) ----
$router->get('',          [LandingController::class, 'index']);
$router->get('dashboard', [HomeController::class, 'index']);
$router->get('report',        [DashboardController::class, 'index']);   // รายงานสถิติ (สพท./สพฐ.)
$router->get('report/export', [DashboardController::class, 'export']);  // ส่งออก CSV

// ---- Authentication ----
$router->get('auth/login',  [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login']);
$router->get('auth/logout', [AuthController::class, 'logout']);

// ---- ส่วนที่ 1: ประเมินพื้นที่สูงใหม่ ----
// 1A เลือกสังกัด/โรงเรียน
$router->get('highland',          [SchoolController::class, 'index']);
$router->get('highland/schools',  [SchoolController::class, 'schools']);   // AJAX JSON
$router->post('highland/start',   [SchoolController::class, 'start']);
// 1B ปักหมุด + วัดความสูง
$router->get('map/search',         [MapController::class, 'search']);
$router->post('map/savelatlng',    [MapController::class, 'saveLatLng']);   // AJAX
$router->get('map/elevation',      [MapController::class, 'elevation']);
$router->post('map/saveelevation', [MapController::class, 'saveElevation']);// AJAX
// 1C/1D แบบประเมิน + คะแนน
$router->get('highland/eval',           [HighlandEvalController::class, 'edit']);
$router->post('highland/eval/save',     [HighlandEvalController::class, 'save']);
$router->post('highland/hilltrib/add',  [HighlandEvalController::class, 'addHilltrib']);   // AJAX
$router->post('highland/hilltrib/delete',[HighlandEvalController::class, 'deleteHilltrib']);// AJAX
$router->post('highland/eval/cert',     [HighlandEvalController::class, 'cert']);          // AJAX (สพท./สพฐ.)
// 1E พิมพ์
$router->get('highland/print',     [HighlandEvalController::class, 'print']);
// 1F ขั้นที่ 5: รายการรออนุมัติ — สพท./สพฐ. รับรองผลประเมิน
$router->get('highland/cert',      [HighlandCertController::class, 'index']);
$router->get('highland/cert/view', [HighlandCertController::class, 'view']);   // ดูข้อมูลรายโรงเรียน (อ่านอย่างเดียว)
$router->post('highland/cert/sao', [HighlandCertController::class, 'sao']);
$router->post('highland/cert/spt', [HighlandCertController::class, 'spt']);

// ---- ส่วนที่ 3 + 4: รับรองการคงอยู่ (พื้นที่สูง/เกาะ) ----
$router->get('confirm',         [ConfirmController::class, 'index']);
$router->get('confirm/view',    [ConfirmController::class, 'view']);   // ดูรายละเอียดรายโรงเรียน (อ่านอย่างเดียว)
$router->post('confirm/school', [ConfirmController::class, 'school']);
$router->post('confirm/sao',    [ConfirmController::class, 'sao']);
$router->post('confirm/spt',    [ConfirmController::class, 'spt']);
$router->post('confirm/unlock', [ConfirmController::class, 'unlock']);

// ---- ส่วนที่ 2: ประเมินพื้นที่เกาะใหม่ ----
$router->get('island',            [IslandEvalController::class, 'select']);
$router->post('island/start',     [IslandEvalController::class, 'start']);
$router->get('island/eval',       [IslandEvalController::class, 'edit']);
$router->post('island/eval/save', [IslandEvalController::class, 'save']);
$router->post('island/eval/cert', [IslandEvalController::class, 'cert']);
$router->get('island/print',      [IslandEvalController::class, 'print']);
// ขั้นที่ 4: รายการรออนุมัติ — สพท./สพฐ. รับรองผลประเมินพื้นที่เกาะ
$router->get('island/cert',       [IslandCertController::class, 'index']);
$router->post('island/cert/sao',  [IslandCertController::class, 'sao']);
$router->post('island/cert/spt',  [IslandCertController::class, 'spt']);

// ---- จัดการผู้ใช้ + รหัสผ่าน (เฉพาะ สพฐ. admin) ----
$router->get('admin/users',           [UserAdminController::class, 'index']);
$router->post('admin/users/save',     [UserAdminController::class, 'save']);
$router->post('admin/users/password', [UserAdminController::class, 'password']);
$router->post('admin/users/delete',   [UserAdminController::class, 'delete']);

$router->dispatch(Request::method(), Request::path());
