<?php
namespace App\Core;

/**
 * Application container: เก็บ config + base path + เริ่ม session
 * และให้ helper เข้าถึง config/url ทั่วทั้งระบบ
 */
class App
{
    private static array $config = [];
    private static string $basePath = '';   // URL base path เช่น '' หรือ '/newhighland'
    private static string $rootDir  = '';    // โฟลเดอร์รากของโปรเจกต์ (filesystem)

    public static function boot(string $rootDir): void
    {
        self::$rootDir = rtrim($rootDir, '/\\');
        self::$config  = require self::$rootDir . '/config/config.php';

        // คำนวณ base path ของ URL จาก SCRIPT_NAME (รองรับทั้ง newhighland.test/ และ localhost/newhighland/)
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        self::$basePath = ($script === '/' || $script === '.') ? '' : rtrim($script, '/');

        // เว็บเท่านั้น — CLI/cron ไม่ต้องใช้ session (กัน warning "headers already sent")
        if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (self::config('debug')) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    /** อ่าน config: App::config('acad_year') หรือ App::config('db')['host'] */
    public static function config(?string $key = null)
    {
        if ($key === null) return self::$config;
        return self::$config[$key] ?? null;
    }

    public static function rootDir(): string { return self::$rootDir; }
    public static function basePath(): string { return self::$basePath; }

    /** สร้าง URL ภายในระบบ: App::url('auth/login') => '/newhighland/auth/login' */
    public static function url(string $path = ''): string
    {
        return self::$basePath . '/' . ltrim($path, '/');
    }

    /** path ของ asset: App::asset('css/app.css') */
    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::url($path));
        exit;
    }

    public static function acadYear(): int
    {
        return (int) self::config('acad_year');
    }
}
