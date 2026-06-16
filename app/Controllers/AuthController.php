<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\View;

class AuthController
{
    /** กัน brute-force: ผิดได้ไม่เกิน MAX_ATTEMPTS ครั้งต่อ IP ภายใน WINDOW วินาที */
    private const MAX_ATTEMPTS = 5;
    private const WINDOW       = 900;   // 15 นาที


    public function showLogin(): void
    {
        if (Auth::check()) {
            App::redirect('dashboard');
        }
        View::render('auth/login', ['title' => 'เข้าสู่ระบบ'], 'auth');
    }

    public function login(): void
    {
        Csrf::verify();
        $key = 'login:' . Request::ip();

        // กัน brute-force: ถ้าผิดเกินกำหนดแล้ว ปฏิเสธก่อนพยายามตรวจรหัสผ่าน
        $state = RateLimiter::tooMany($key, self::MAX_ATTEMPTS, self::WINDOW);
        if ($state['blocked']) {
            Flash::error('พยายามเข้าสู่ระบบไม่สำเร็จหลายครั้งเกินไป กรุณารออีกประมาณ '
                . (int) ceil($state['retryAfter'] / 60) . ' นาที แล้วลองใหม่');
            App::redirect('auth/login');
        }

        $username = (string) Request::post('username', '');
        $password = (string) Request::post('password', '');

        if ($username === '' || $password === '') {
            Flash::error('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            App::redirect('auth/login');
        }

        if (Auth::attempt($username, $password)) {
            RateLimiter::clear($key);                 // สำเร็จ → ล้างตัวนับ
            Flash::success('เข้าสู่ระบบสำเร็จ');
            App::redirect('dashboard');
        }

        RateLimiter::hit($key, self::WINDOW);          // ล้มเหลว → นับ +1
        $left = RateLimiter::tooMany($key, self::MAX_ATTEMPTS, self::WINDOW);
        $msg  = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        if ($left['blocked']) {
            $msg = 'พยายามเข้าสู่ระบบไม่สำเร็จหลายครั้งเกินไป กรุณารออีกประมาณ '
                . (int) ceil($left['retryAfter'] / 60) . ' นาที แล้วลองใหม่';
        }
        Flash::error($msg);
        App::redirect('auth/login');
    }

    public function logout(): void
    {
        Auth::logout();
        App::redirect('auth/login');
    }
}
