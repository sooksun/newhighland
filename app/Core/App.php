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

        // ---- จัดการข้อผิดพลาดแบบรวมศูนย์ ----
        // debug=on: แสดงรายละเอียดเพื่อ dev | debug=off (production): ปิด display_errors
        // กัน leak stack trace/SQL/path และแสดงหน้า 500 ที่เป็นมิตรแทน
        error_reporting(E_ALL);
        if (self::config('debug')) {
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }
        if (PHP_SAPI !== 'cli') {
            set_exception_handler([self::class, 'handleException']);
            register_shutdown_function([self::class, 'handleShutdown']);
        }
    }

    private static bool $errorHandled = false;

    /** จัดการ exception ที่ไม่ถูก catch — บันทึก log จริง, แสดงหน้า 500 ที่ปลอดภัย */
    public static function handleException(\Throwable $e): void
    {
        self::$errorHandled = true;
        error_log('[newhighland] ' . get_class($e) . ': ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (self::config('debug')) {
            echo '<pre style="white-space:pre-wrap;padding:16px;">'
                . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
            return;
        }
        self::render500();
    }

    /** ดักจับ fatal error ตอนจบสคริปต์ (เช่น error ระดับ E_ERROR) ไม่ให้ขึ้นจอขาว */
    public static function handleShutdown(): void
    {
        if (self::$errorHandled) return;
        $err = error_get_last();
        if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }
        error_log('[newhighland] FATAL: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        if (self::config('debug')) return;   // debug: ปล่อยให้ PHP แสดงตามปกติ
        if (!headers_sent()) {
            http_response_code(500);
        }
        self::render500();
    }

    /** แสดงหน้า 500 แบบ standalone (ไม่พึ่ง layout/DB เผื่อกรณี DB ล่ม) */
    private static function render500(): void
    {
        while (ob_get_level() > 0) ob_end_clean();   // ทิ้ง output ที่ค้างก่อนเกิด error
        $file = self::$rootDir . '/app/Views/errors/500.php';
        if (is_file($file)) {
            include $file;
        } else {
            echo 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่ภายหลัง';
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

    /** path ของ asset: App::asset('css/app.css') — แนบ ?v=filemtime กันแคชเมื่อไฟล์เปลี่ยน */
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $url  = self::url('assets/' . $path);
        $abs  = self::$rootDir . '/assets/' . $path;
        if (is_file($abs)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($abs);
        }
        return $url;
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
