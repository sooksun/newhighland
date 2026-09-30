<?php
namespace App\Models;

use App\Core\Db;

/**
 * บัญชีผู้ใช้ระดับโรงเรียน — ตาราง `user`
 *   คีย์ : citicens_id (unique, ใช้เป็นตัวระบุแถว) ; login = `user` ; password = plaintext (legacy)
 * ใช้ในหน้าจัดการผู้ใช้ของ สพฐ. (admin) เท่านั้น
 *
 * หมายเหตุหนี้เทคนิค (PRD §10): รหัสผ่าน plaintext เพื่อเข้ากันได้กับ Auth::attempt เดิม
 */
class SchoolUser
{
    /** คอลัมน์ที่อนุญาตให้เขียน (mass-assignment guard) */
    public const FIELDS = ['name', 'user', 'password', 'sc_id', 'sao_id', 'email', 'level', 'user_group', 'type'];

    /** ค้นหา/แสดงรายการ (name / user / sc_id) — จำกัดผลเพื่อกันดึงทั้ง 3 หมื่นแถว */
    public static function search(string $q = '', int $limit = 200): array
    {
        $sql = "SELECT u.citicens_id, u.name, u.user, u.password, u.sc_id, u.sao_id, u.email,
                       (SELECT TRIM(s.sao_name) FROM master_sao s WHERE s.sao_id = u.sao_id LIMIT 1) AS sao_name
                  FROM `user` u";
        $p = [];
        $q = trim($q);
        if ($q !== '') {
            $sql .= " WHERE u.name LIKE ? OR u.user LIKE ? OR u.sc_id LIKE ?";
            $like = '%' . $q . '%';
            $p = [$like, $like, $like];
        }
        $sql .= " ORDER BY u.name LIMIT " . (int) $limit;
        return Db::all($sql, $p);
    }

    public static function count(): int
    {
        return (int) Db::scalar("SELECT COUNT(*) FROM `user`");
    }

    /**
     * รายการบัญชีโรงเรียน "ในเขต" — scope ด้วย master_school.sao_code = saoId (เขตปัจจุบันของโรงเรียน)
     * หมายเหตุ: ไม่ใช้ user.sao_id เพราะเป็นค่าที่ตั้งตอนสร้างบัญชีและมักล้าสมัย (stale) ไม่ตรง master_school
     * ใช้ในหน้าจัดการผู้ใช้ของ "สำนักงานเขต (สพท.)" — เห็นเฉพาะโรงเรียนในสังกัดตน
     */
    public static function listBySao(int $saoId, string $q = '', int $limit = 1000): array
    {
        $sql = "SELECT u.citicens_id, u.name, u.user, u.password, u.sc_id, u.sao_id, u.email,
                       (SELECT TRIM(s.sao_name) FROM master_sao s WHERE s.sao_id = m.sao_code LIMIT 1) AS sao_name
                  FROM `user` u
                  JOIN master_school m ON m.sc_id = u.sc_id
                 WHERE m.sao_code = ?";
        $p = [$saoId];
        $q = trim($q);
        if ($q !== '') {
            $sql .= " AND (u.name LIKE ? OR u.user LIKE ? OR u.sc_id LIKE ?)";
            $like = '%' . $q . '%';
            $p[] = $like; $p[] = $like; $p[] = $like;
        }
        $sql .= " ORDER BY u.name LIMIT " . (int) $limit;
        return Db::all($sql, $p);
    }

    /** บัญชีโรงเรียนนี้อยู่ในเขต saoId หรือไม่ (อิง master_school.sao_code ของ sc_id ปัจจุบัน) */
    public static function inSao(string $citicensId, int $saoId): bool
    {
        return (bool) Db::scalar(
            "SELECT 1 FROM `user` u JOIN master_school m ON m.sc_id = u.sc_id
              WHERE u.citicens_id = ? AND m.sao_code = ? LIMIT 1",
            [$citicensId, $saoId]
        );
    }

    public static function find(string $citicensId): ?array
    {
        return Db::one("SELECT * FROM `user` WHERE citicens_id = ? LIMIT 1", [$citicensId]);
    }

    /** username ซ้ำไหม (ยกเว้นแถวของตัวเอง) */
    public static function userExists(string $user, ?string $exceptCiticensId = null): bool
    {
        $sql = "SELECT 1 FROM `user` WHERE `user` = ?";
        $p = [$user];
        if ($exceptCiticensId !== null) { $sql .= " AND citicens_id <> ?"; $p[] = $exceptCiticensId; }
        return (bool) Db::scalar($sql . " LIMIT 1", $p);
    }

    /** เพิ่มผู้ใช้ใหม่ — คืน citicens_id ที่สร้าง (รันค่า MAX+1 เพราะตารางเดิมใช้เลขลำดับ) */
    public static function create(array $data): string
    {
        $next = (int) Db::scalar("SELECT COALESCE(MAX(CAST(citicens_id AS UNSIGNED)), 0) + 1 FROM `user`");
        $cols = ['citicens_id'];
        $vals = [(string) $next];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $data)) { $cols[] = "`$f`"; $vals[] = $data[$f]; }
        }
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        Db::exec("INSERT INTO `user` (" . implode(', ', $cols) . ") VALUES ($ph)", $vals);
        return (string) $next;
    }

    public static function update(string $citicensId, array $data): void
    {
        $set = [];
        $vals = [];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $data)) { $set[] = "`$f` = ?"; $vals[] = $data[$f]; }
        }
        if (!$set) return;
        $vals[] = $citicensId;
        Db::exec("UPDATE `user` SET " . implode(', ', $set) . " WHERE citicens_id = ?", $vals);
    }

    public static function setPassword(string $citicensId, string $password): void
    {
        Db::exec("UPDATE `user` SET `password` = ? WHERE citicens_id = ?", [$password, $citicensId]);
    }

    public static function delete(string $citicensId): void
    {
        Db::exec("DELETE FROM `user` WHERE citicens_id = ?", [$citicensId]);
    }
}
