<?php
namespace App\Core;

/** จัดการอัปโหลดไฟล์แนบ (เอกสาร/ภาพประกอบ) — เก็บใต้ uploads/ และคืน path แบบ relative */
class Upload
{
    private const ALLOWED = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_BYTES = 8388608; // 8 MB

    /**
     * @return string|null  relative path เช่น "uploads/highland/2569/123_citeria04.pdf"
     *                       (เก็บลง DB; แสดงผลด้วย App::url($path))
     */
    public static function save(string $key, string $destRelDir, string $baseName): ?string
    {
        if (empty($_FILES[$key]) || ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $f   = $_FILES[$key];
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true) || $f['size'] <= 0 || $f['size'] > self::MAX_BYTES) {
            return null;
        }
        $destRelDir = trim($destRelDir, '/');
        $absDir = App::rootDir() . '/' . $destRelDir;
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return null;
        }
        $fname = preg_replace('/[^A-Za-z0-9_\-]/', '', $baseName) . '.' . $ext;
        $abs   = $absDir . '/' . $fname;
        if (!move_uploaded_file($f['tmp_name'], $abs)) {
            return null;
        }
        return $destRelDir . '/' . $fname;
    }
}
