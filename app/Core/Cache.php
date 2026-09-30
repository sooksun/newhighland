<?php
namespace App\Core;

/**
 * File cache อย่างง่าย (เก็บ JSON ใต้ storage/cache) — ใช้กับหน้า "รายงานสถิติ"
 * เพื่อแสดงค่าที่ประมวลผลไว้ล่าสุดทันที แล้วให้ผู้ใช้กด "ประมวลผลใหม่" เมื่อต้องการค่า real-time
 *
 * ความปลอดภัย: โฟลเดอร์ storage ถูกบล็อกจากเว็บใน .htaccess (และไฟล์ .json ก็ถูกบล็อกด้วย)
 * ไม่มี TTL อัตโนมัติ — ล้าง/รีเฟรชด้วยการเรียก forget() หรือเขียนทับด้วย put() เท่านั้น (manual)
 */
class Cache
{
    private static function dir(): string
    {
        $d = App::rootDir() . '/storage/cache';
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
        return $d;
    }

    private static function path(string $key): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_.-]/', '_', $key);
        return self::dir() . '/' . $safe . '.json';
    }

    /** อ่าน cache; คืน null ถ้าไม่มี/อ่านไม่ได้ */
    public static function get(string $key): ?array
    {
        $f = self::path($key);
        if (!is_file($f)) {
            return null;
        }
        $raw = @file_get_contents($f);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /** เขียน cache (atomic-ish ด้วย LOCK_EX) */
    public static function put(string $key, array $data): void
    {
        @file_put_contents(self::path($key), json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /** ลบ cache รายการเดียว */
    public static function forget(string $key): void
    {
        $f = self::path($key);
        if (is_file($f)) {
            @unlink($f);
        }
    }
}
