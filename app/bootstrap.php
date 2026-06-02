<?php
/**
 * Bootstrap: PSR-4-ish autoloader + boot application.
 * โหลดไฟล์นี้จาก index.php (front controller)
 */

$rootDir = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($rootDir) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));           // Core\Db
    $file = $rootDir . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Global aliases เพื่อให้ไฟล์ View (รันใน global namespace) เรียก Core classes ได้แบบสั้น
// เช่น App::url(), View::e(), Csrf::field(), Flash::pull(), Auth::role()
foreach (['App', 'View', 'Auth', 'Csrf', 'Flash', 'Db'] as $alias) {
    if (!class_exists($alias, false)) {
        class_alias('App\\Core\\' . $alias, $alias);
    }
}

// View helpers (nh_icon, nh_brand_mark) — global functions ใช้ใน template
require $rootDir . '/app/Views/partials/helpers.php';

\App\Core\App::boot($rootDir);
