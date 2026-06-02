<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * PDO singleton. ทุก query ในระบบใหม่ต้องผ่านที่นี่ และใช้ prepared statements เท่านั้น
 * (แก้หนี้เทคนิค SQL injection จากระบบเดิม — PRD §10).
 */
class Db
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $cfg = App::config('db');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']
            );
            try {
                self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // ตารางเดิม (highland_eval ฯลฯ) มีคอลัมน์ NOT NULL จำนวนมากที่ไม่มี DEFAULT
                // ระบบเดิมทำงานบน MySQL ที่ปิด strict mode — ตั้งให้สอดคล้องกัน เพื่อให้ INSERT
                // เฉพาะบางคอลัมน์ได้ค่าเริ่มต้นโดยปริยาย (PRD §10: หนี้เทคนิค ควรเพิ่ม DEFAULT ภายหลัง)
                self::$pdo->exec("SET SESSION sql_mode = ''");
            } catch (PDOException $e) {
                http_response_code(500);
                $msg = App::config('debug') ? $e->getMessage() : 'Database connection error';
                exit('DB error: ' . htmlspecialchars($msg));
            }
        }
        return self::$pdo;
    }

    /** SELECT หลายแถว */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** SELECT แถวเดียว (หรือ null) */
    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /** ค่าคอลัมน์เดียว */
    public static function scalar(string $sql, array $params = [])
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    /** INSERT/UPDATE/DELETE — คืนจำนวนแถวที่กระทบ */
    public static function exec(string $sql, array $params = []): int
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function lastId(): string
    {
        return self::conn()->lastInsertId();
    }
}
