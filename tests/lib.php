<?php
/**
 * Test harness แบบเบา (ไม่พึ่ง PHPUnit/composer) — รันด้วย php ของ Laragon
 *   php tests/run.php
 * ออก exit code != 0 เมื่อมี assertion ล้มเหลว (ใช้กับ CI ได้)
 */
final class T
{
    public static int $pass = 0;
    public static int $fail = 0;
    public static int $skip = 0;
    public static array $fails = [];
    private static string $group = '';

    public static function group(string $name): void
    {
        self::$group = $name;
        echo "\n# {$name}\n";
    }

    private static function ok(string $msg): void
    {
        self::$pass++;
        echo "  \xE2\x9C\x93 {$msg}\n";
    }

    private static function bad(string $msg, string $detail): void
    {
        self::$fail++;
        $line = "[" . self::$group . "] {$msg} — {$detail}";
        self::$fails[] = $line;
        echo "  \xE2\x9C\x97 {$msg} — {$detail}\n";
    }

    private static function show($v): string
    {
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_null($v)) return 'null';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return (string) $v;
    }

    public static function eq($expected, $actual, string $msg): void
    {
        if ($expected === $actual) self::ok($msg);
        else self::bad($msg, 'expected ' . self::show($expected) . ', got ' . self::show($actual));
    }

    /** เทียบตัวเลขทศนิยมแบบมี tolerance */
    public static function close(float $expected, $actual, string $msg, float $eps = 0.001): void
    {
        if (is_numeric($actual) && abs($expected - (float) $actual) < $eps) self::ok($msg);
        else self::bad($msg, 'expected ~' . $expected . ', got ' . self::show($actual));
    }

    public static function true($cond, string $msg): void
    {
        $cond ? self::ok($msg) : self::bad($msg, 'expected truthy, got ' . self::show($cond));
    }

    public static function false($cond, string $msg): void
    {
        !$cond ? self::ok($msg) : self::bad($msg, 'expected falsy, got ' . self::show($cond));
    }

    public static function skip(string $msg): void
    {
        self::$skip++;
        echo "  ~ SKIP: {$msg}\n";
    }

    public static function summary(): void
    {
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "PASS: " . self::$pass . "   FAIL: " . self::$fail . "   SKIP: " . self::$skip . "\n";
        if (self::$fail > 0) {
            echo "\nFAILURES:\n";
            foreach (self::$fails as $f) echo "  - {$f}\n";
            exit(1);
        }
        echo "ALL GREEN\n";
        exit(0);
    }
}
