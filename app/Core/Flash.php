<?php
namespace App\Core;

/** ข้อความแจ้งเตือนชั่วคราว (one-time) ข้าม redirect */
class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $m): void { self::add('success', $m); }
    public static function error(string $m): void   { self::add('danger', $m); }
    public static function info(string $m): void    { self::add('info', $m); }

    /** ดึงทั้งหมดแล้วล้าง */
    public static function pull(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
}
