<?php
use App\Core\Csrf;

T::group('Csrf — token + การตรวจสอบ');

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();   // CLI: ใช้ไฟล์ชั่วคราว
}
$_SESSION = [];

$tok = Csrf::token();
T::eq(64, strlen($tok), 'token ยาว 64 (bin2hex 32 ไบต์)');
T::true(ctype_xdigit($tok), 'token เป็น hex ล้วน');
T::eq($tok, Csrf::token(), 'token คงที่ภายใน session เดียว');

T::true(Csrf::check($tok), 'check token ถูกต้อง → true');
T::false(Csrf::check('ผิด'), 'check token ผิด → false');
T::false(Csrf::check(null), 'check null → false');
T::false(Csrf::check(''), 'check ว่าง → false');

// ไม่มี token ใน session → check ต้อง false เสมอ
$_SESSION = [];
T::false(Csrf::check('aaaa'), 'ไม่มี token ใน session → false');

// field() ต้องฝัง token ปัจจุบัน
$_SESSION = [];
$field = Csrf::field();
T::true(str_contains($field, 'name="_csrf"'), 'field มี input _csrf');
T::true(str_contains($field, $_SESSION['_csrf']), 'field ฝัง token จาก session');
