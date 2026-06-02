<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

class HomeController
{
    public function index(): void
    {
        Auth::require();

        // ตัวอย่างนับข้อมูลให้เห็นว่าต่อ DB ได้จริง (จะปรับเป็นสถิติจริงใน Phase ถัดไป)
        $acad = App::acadYear();
        $stats = [
            'acad_year'   => $acad,
            'highland_cnt'=> (int) Db::scalar('SELECT COUNT(*) FROM highland_eval WHERE acadyears = ?', [$acad]),
            'role'        => Auth::role(),
        ];

        View::render('home/dashboard', [
            'title' => 'หน้าหลัก',
            'stats' => $stats,
        ]);
    }
}
