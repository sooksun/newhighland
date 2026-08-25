<?php
namespace App\Models;

use App\Core\App;
use App\Core\Db;

/**
 * บัญชีผู้ใช้ระดับเขต/สพฐ. — ตาราง `master_saonew`
 *   คีย์ : id (unique, varchar) ; login = `user` ; password = plaintext (legacy)
 *   code = ชื่อเขต, code_name = "id - ชื่อ"
 * บัญชีที่เป็น admin (สพฐ.) = user อยู่ใน config 'admin_users' หรือ code_name มี 'สพฐ'
 * ใช้ในหน้าจัดการผู้ใช้ของ สพฐ. (admin) เท่านั้น
 */
class SaoUser
{
    /** คอลัมน์ที่อนุญาตให้เขียน (mass-assignment guard) */
    public const FIELDS = ['code', 'code_name', 'user', 'password'];

    public static function search(string $q = '', int $limit = 500): array
    {
        $sql = "SELECT id, code, code_name, user, password FROM master_saonew";
        $p = [];
        $q = trim($q);
        if ($q !== '') {
            $sql .= " WHERE code LIKE ? OR code_name LIKE ? OR user LIKE ? OR id LIKE ?";
            $like = '%' . $q . '%';
            $p = [$like, $like, $like, $like];
        }
        $sql .= " ORDER BY id LIMIT " . (int) $limit;
        return Db::all($sql, $p);
    }

    public static function find(string $id): ?array
    {
        return Db::one("SELECT * FROM master_saonew WHERE id = ? LIMIT 1", [$id]);
    }

    /**
     * กรองเหลือเฉพาะบัญชีเขตที่มีโรงเรียนพื้นที่สูง/เกาะ (มีแถวใน school_confirm ปีนั้น)
     * — resolve sao_id ด้วย logic เดียวกับ Auth::attempt (byName ทน whitespace → byCode)
     *   พรีโหลด master_sao ครั้งเดียว ไม่ query ต่อแถว
     * — บัญชี admin/สพฐ. แสดงเสมอ (ไม่ใช่เขตพื้นที่ที่มีโรงเรียน แต่ควรเห็นไว้จัดการ)
     */
    public static function keepWithRoster(array $rows, int $year): array
    {
        $active = [];
        foreach (Db::all("SELECT DISTINCT sao_id FROM school_confirm WHERE acadyears = ?", [$year]) as $r) {
            $active[(int) $r['sao_id']] = true;
        }
        $byName = [];
        $byCode = [];
        foreach (Db::all("SELECT sao_id, sao_code, sao_name FROM master_sao") as $r) {
            $byName[str_replace(' ', '', trim((string) $r['sao_name']))] = (int) $r['sao_id'];
            if (!isset($byCode[(string) $r['sao_code']])) {
                $byCode[(string) $r['sao_code']] = (int) $r['sao_id'];
            }
        }
        return array_values(array_filter($rows, function (array $row) use ($active, $byName, $byCode) {
            if (self::isAdminAccount($row)) return true;
            $key = str_replace(' ', '', trim((string) ($row['code'] ?? '')));
            $sid = $byName[$key] ?? ($byCode[substr((string) ($row['id'] ?? ''), 0, 4)] ?? null);
            return $sid !== null && isset($active[$sid]);
        }));
    }

    public static function idExists(string $id): bool
    {
        return (bool) Db::scalar("SELECT 1 FROM master_saonew WHERE id = ? LIMIT 1", [$id]);
    }

    public static function userExists(string $user, ?string $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM master_saonew WHERE `user` = ?";
        $p = [$user];
        if ($exceptId !== null) { $sql .= " AND id <> ?"; $p[] = $exceptId; }
        return (bool) Db::scalar($sql . " LIMIT 1", $p);
    }

    /** บัญชีนี้เป็น admin (สพฐ.) ไหม — ตรรกะเดียวกับ Auth::attempt */
    public static function isAdminAccount(array $row): bool
    {
        return in_array($row['user'] ?? '', (array) App::config('admin_users'), true)
            || str_contains((string) ($row['code_name'] ?? ''), 'สพฐ');
    }

    public static function create(string $id, array $data): void
    {
        $cols = ['id'];
        $vals = [$id];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $data)) { $cols[] = "`$f`"; $vals[] = $data[$f]; }
        }
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        Db::exec("INSERT INTO master_saonew (" . implode(', ', $cols) . ") VALUES ($ph)", $vals);
    }

    public static function update(string $id, array $data): void
    {
        $set = [];
        $vals = [];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $data)) { $set[] = "`$f` = ?"; $vals[] = $data[$f]; }
        }
        if (!$set) return;
        $vals[] = $id;
        Db::exec("UPDATE master_saonew SET " . implode(', ', $set) . " WHERE id = ?", $vals);
    }

    public static function setPassword(string $id, string $password): void
    {
        Db::exec("UPDATE master_saonew SET `password` = ? WHERE id = ?", [$password, $id]);
    }

    public static function delete(string $id): void
    {
        Db::exec("DELETE FROM master_saonew WHERE id = ?", [$id]);
    }
}
