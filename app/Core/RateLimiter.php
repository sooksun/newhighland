<?php
namespace App\Core;

/**
 * จำกัดจำนวนครั้งที่พยายามทำงานต่อคีย์ภายในกรอบเวลา (เช่น กัน brute-force หน้า login)
 * เก็บสถานะเป็นไฟล์ JSON ใต้ storage/throttle/ — ไม่ต้องใช้ฐานข้อมูล
 * ออกแบบแบบ fail-open: ถ้าเขียนไฟล์ไม่ได้ จะไม่ขัดขวางผู้ใช้ปกติ
 */
class RateLimiter
{
    private static function path(string $key): string
    {
        return App::rootDir() . '/storage/throttle/' . hash('sha256', $key) . '.json';
    }

    private static function normalize($data): array
    {
        return is_array($data) ? ($data + ['start' => 0, 'count' => 0]) : ['start' => 0, 'count' => 0];
    }

    /** ตรวจว่าถึง/เกินลิมิตหรือยัง (อ่านอย่างเดียว ไม่เพิ่มตัวนับ) */
    public static function tooMany(string $key, int $max, int $window): array
    {
        $file = self::path($key);
        $d = self::normalize(is_file($file) ? json_decode((string) @file_get_contents($file), true) : null);
        $now = time();
        if ($d['start'] + $window < $now) {
            return ['blocked' => false, 'retryAfter' => 0];   // กรอบเวลาเดิมหมดอายุแล้ว
        }
        return [
            'blocked'    => $d['count'] >= $max,
            'retryAfter' => (int) max(0, ($d['start'] + $window) - $now),
        ];
    }

    /** บันทึกความพยายามที่ล้มเหลว +1 (ล็อกไฟล์กัน race) */
    public static function hit(string $key, int $window): void
    {
        $file = self::path($key);
        $dir  = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) return;
        $fp = @fopen($file, 'c+');
        if ($fp === false) return;
        if (flock($fp, LOCK_EX)) {
            $d   = self::normalize(json_decode((string) stream_get_contents($fp), true));
            $now = time();
            if ($d['start'] + $window < $now) $d = ['start' => $now, 'count' => 0];
            $d['count']++;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode($d));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /** ล้างตัวนับ (เรียกเมื่อทำงานสำเร็จ เช่น login ผ่าน) */
    public static function clear(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) @unlink($file);
    }
}
