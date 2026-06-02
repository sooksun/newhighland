<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;

class AuthController
{
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
        $username = (string) Request::post('username', '');
        $password = (string) Request::post('password', '');

        if ($username === '' || $password === '') {
            Flash::error('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            App::redirect('auth/login');
        }

        if (Auth::attempt($username, $password)) {
            Flash::success('เข้าสู่ระบบสำเร็จ');
            App::redirect('dashboard');
        }

        Flash::error('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        App::redirect('auth/login');
    }

    public function logout(): void
    {
        Auth::logout();
        App::redirect('auth/login');
    }
}
