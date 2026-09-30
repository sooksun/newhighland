<?php
namespace App\Models;

use App\Core\Db;

/**
 * สถานะเปิด/ปิดระบบคัดกรอง (สวิตช์เดียว ทั้งระบบ) — ตาราง `screening_status` (แถวเดียว id=1)
 * ปิด = โรงเรียนดูข้อมูลเดิมได้ปกติ แต่กรอก/บันทึก/ส่งข้อมูลไม่ได้; สพท./สพฐ. ไม่ถูกจำกัด (รับรองได้ตามปกติ)
 */
class SystemStatus
{
    private static ?array $cache = null;

    private static function row(): array
    {
        if (self::$cache !== null) return self::$cache;
        $row = Db::one('SELECT is_open, closed_message, updated_at, updated_by FROM screening_status WHERE id = 1');
        return self::$cache = $row ?: ['is_open' => 1, 'closed_message' => '', 'updated_at' => null, 'updated_by' => null];
    }

    public static function isOpen(): bool
    {
        return (bool) (int) self::row()['is_open'];
    }

    public static function closedMessage(): string
    {
        $msg = trim((string) (self::row()['closed_message'] ?? ''));
        return $msg !== '' ? $msg : 'ระบบปิดรับข้อมูลชั่วคราว กรุณาติดต่อ สพฐ.';
    }

    public static function info(): array
    {
        return self::row();
    }

    public static function setStatus(bool $open, string $message, string $updatedBy): void
    {
        Db::exec(
            'UPDATE screening_status SET is_open = ?, closed_message = ?, updated_at = NOW(), updated_by = ? WHERE id = 1',
            [$open ? 1 : 0, mb_substr(trim($message), 0, 255), mb_substr($updatedBy, 0, 50)]
        );
        self::$cache = null;
    }
}
