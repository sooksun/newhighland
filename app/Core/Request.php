<?php
namespace App\Core;

/** ห่อ HTTP request เพื่อให้เข้าถึง method/path/input อย่างปลอดภัย */
class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /** path ที่ตัด base path ออกแล้ว เช่น 'auth/login' */
    public static function path(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri  = rawurldecode($uri);
        $base = App::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return trim($uri, '/');   // '' = หน้าแรก
    }

    /** ค่าจาก POST/GET (POST ก่อน) */
    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) $out[$k] = self::input($k);
        return $out;
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}
