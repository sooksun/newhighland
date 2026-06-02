<?php
namespace App\Core;

/** CSRF token ป้องกันการส่งฟอร์มข้ามเว็บ (PRD §10) */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** ช่อง hidden สำหรับแทรกในฟอร์ม */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function check(?string $token): bool
    {
        return !empty($_SESSION['_csrf'])
            && is_string($token)
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /** เรียกในจุดรับ POST; ตายถ้าไม่ผ่าน */
    public static function verify(): void
    {
        if (!self::check(Request::post('_csrf'))) {
            http_response_code(419);
            exit('คำขอหมดอายุหรือไม่ถูกต้อง (CSRF). กรุณาลองใหม่.');
        }
    }
}
