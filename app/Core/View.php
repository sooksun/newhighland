<?php
namespace App\Core;

/** Render view templates (.php) ภายใน layout */
class View
{
    /**
     * render('home/dashboard', ['x'=>1])  => app/Views/home/dashboard.php ใน layout
     * ตั้ง $layout = null เพื่อ render เฉพาะ view (เช่น หน้า map / AJAX)
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        $content = self::capture($view, $data);

        if ($layout === null) {
            echo $content;
            return;
        }
        // ตัวแปรที่ layout ใช้ได้: $content, $title, และ data อื่นๆ
        $data['content'] = $content;
        echo self::capture('layouts/' . $layout, $data);
    }

    /** render view ออกมาเป็น string */
    public static function capture(string $view, array $data = []): string
    {
        $file = App::rootDir() . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /** escape helper ใช้ใน template: <?= View::e($x) ?> */
    public static function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
