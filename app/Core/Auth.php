<?php
namespace App\Core;

/**
 * Authentication — ใช้บัญชี/รหัสผ่านเดิม (PRD §3)
 *   - โรงเรียน : ตาราง `user`        (คอลัมน์ user / password / sc_id / sao_id)
 *   - เขต/สพฐ. : ตาราง `master_saonew` (คอลัมน์ user / password / id=รหัสเขต / code_name)
 *
 * หมายเหตุหนี้เทคนิค (PRD §10): รหัสผ่านในระบบเดิมเก็บแบบ plaintext
 * จึงต้องเทียบตรงเพื่อความเข้ากันได้กับบัญชีเดิม — ควรวางแผนย้ายไป password_hash ภายหลัง
 */
class Auth
{
    public const ROLE_SCHOOL = 'school';
    public const ROLE_SAO    = 'sao';
    public const ROLE_ADMIN  = 'admin';

    /** พยายามล็อกอิน คืน true ถ้าสำเร็จ */
    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);

        // 1) โรงเรียน
        $u = Db::one(
            'SELECT citicens_id, name, sc_id, sao_id FROM `user` WHERE `user` = ? AND `password` = ? LIMIT 1',
            [$username, $password]
        );
        if ($u) {
            $sao = $u['sao_id'] ? \App\Models\MasterSao::find((int) $u['sao_id']) : null;
            // auto menu filter: คำนวณเมนูที่ตรงคุณสมบัติของโรงเรียน เก็บไว้ใน session
            $menu = \App\Services\SchoolMenu::forSchool((int) $u['sc_id'], App::acadYear());
            $_SESSION['auth'] = [
                'role'        => self::ROLE_SCHOOL,
                'username'    => $username,
                'name'        => $u['name'],
                'sc_id'       => (int) $u['sc_id'],
                'sao_id'      => (int) $u['sao_id'],
                'sao_name'    => $sao['sao_name'] ?? '',
                'sao_code'    => null,
                'login_table' => 'user',
                'menus'       => $menu['nav'],
                'confirm_areas' => $menu['areas'],
            ];
            session_regenerate_id(true);
            return true;
        }

        // 2) เขต / สพฐ.
        $s = Db::one(
            'SELECT id, code, code_name FROM master_saonew WHERE `user` = ? AND `password` = ? LIMIT 1',
            [$username, $password]
        );
        if ($s) {
            $isAdmin = in_array($username, (array) App::config('admin_users'), true)
                    || str_contains((string) $s['code_name'], 'สพฐ');
            // map ชื่อสังกัด (master_saonew.code) -> master_sao.sao_id (ใช้กรองโรงเรียน)
            $sao = \App\Models\MasterSao::byName((string) $s['code'])
                ?? \App\Models\MasterSao::byCode(substr((string) $s['id'], 0, 4));
            $_SESSION['auth'] = [
                'role'        => $isAdmin ? self::ROLE_ADMIN : self::ROLE_SAO,
                'username'    => $username,
                'name'        => $s['code_name'],
                'sc_id'       => null,
                'sao_id'      => $sao['sao_id'] ?? null,
                'sao_name'    => $sao['sao_name'] ?? $s['code'],
                'sao_code'    => $s['id'],         // รหัสเขต เช่น '63020000'
                'login_table' => 'master_saonew',
            ];
            // auto menu filter: เขต (ไม่ใช่ สพฐ.) เห็นเฉพาะพื้นที่ที่ตนดูแล
            if (!$isAdmin && !empty($sao['sao_id'])) {
                $menu = \App\Services\SchoolMenu::forSao((int) $sao['sao_id'], App::acadYear());
                $_SESSION['auth']['menus']         = $menu['nav'];
                $_SESSION['auth']['confirm_areas'] = $menu['areas'];
            }
            session_regenerate_id(true);
            return true;
        }

        return false;
    }

    public static function check(): bool        { return !empty($_SESSION['auth']); }
    public static function user(): ?array       { return $_SESSION['auth'] ?? null; }
    public static function role(): ?string      { return $_SESSION['auth']['role'] ?? null; }
    public static function name(): string       { return $_SESSION['auth']['name'] ?? ''; }
    public static function scId(): ?int         { return $_SESSION['auth']['sc_id'] ?? null; }
    public static function saoId(): ?int         { return $_SESSION['auth']['sao_id'] ?? null; }
    public static function saoName(): string     { return $_SESSION['auth']['sao_name'] ?? ''; }
    public static function saoCode(): ?string   { return $_SESSION['auth']['sao_code'] ?? null; }
    /** เมนูที่อนุญาตของโรงเรียน (auto menu filter) — null ถ้ายังไม่ได้คำนวณ */
    public static function menus(): ?array        { return $_SESSION['auth']['menus'] ?? null; }
    /** พื้นที่รับรองการคงอยู่ที่อนุญาต (1=สูง, 2=เกาะ) — null ถ้ายังไม่ได้คำนวณ */
    public static function confirmAreas(): ?array { return $_SESSION['auth']['confirm_areas'] ?? null; }

    public static function isSchool(): bool { return self::role() === self::ROLE_SCHOOL; }
    public static function isSao(): bool    { return self::role() === self::ROLE_SAO; }
    public static function isAdmin(): bool  { return self::role() === self::ROLE_ADMIN; }

    /** ตรวจสิทธิ์เข้าถึงโรงเรียน sc_id ตาม role */
    public static function canAccessSchool(int $scId): bool
    {
        if (self::isAdmin()) return true;
        if (self::isSchool()) return $scId === self::scId();
        if (self::isSao()) {
            $saoId = self::saoId();
            if (!$saoId) return false;
            return (bool) Db::scalar(
                'SELECT 1 FROM master_school WHERE sc_id = ? AND sao_code = ? LIMIT 1',
                [(string) $scId, $saoId]
            );
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** บังคับว่าต้องล็อกอิน (และ optionally อยู่ใน role ที่อนุญาต) */
    public static function require(array $roles = []): void
    {
        if (!self::check()) {
            Flash::error('กรุณาเข้าสู่ระบบก่อน');
            App::redirect('auth/login');
        }
        if ($roles && !in_array(self::role(), $roles, true)) {
            http_response_code(403);
            View::render('errors/403', [], 'layout');
            exit;
        }
    }
}
