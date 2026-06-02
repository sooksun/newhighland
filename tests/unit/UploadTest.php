<?php
use App\Core\Upload;

T::group('Upload — การปฏิเสธไฟล์ที่ไม่ผ่านเงื่อนไข');

// helper จำลอง $_FILES แล้วเรียก Upload::save (เส้นทาง reject จะคืน null ก่อนแตะ filesystem)
$try = function (array $file) {
    $_FILES = ['doc' => $file];
    return Upload::save('doc', 'uploads/test', 'x');
};

// ไม่มีไฟล์ส่งมา
$_FILES = [];
T::eq(null, Upload::save('doc', 'uploads/test', 'x'), 'ไม่มี key ไฟล์ → null');

// error != OK (เช่น UPLOAD_ERR_NO_FILE)
T::eq(null, $try(['name' => 'a.pdf', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 10, 'tmp_name' => '']), 'error=NO_FILE → null');
T::eq(null, $try(['name' => 'a.pdf', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 10, 'tmp_name' => '']), 'error=INI_SIZE → null');

// นามสกุลไม่อนุญาต
T::eq(null, $try(['name' => 'a.exe', 'error' => UPLOAD_ERR_OK, 'size' => 10, 'tmp_name' => '/tmp/x']), 'นามสกุล .exe → null');
T::eq(null, $try(['name' => 'a.php', 'error' => UPLOAD_ERR_OK, 'size' => 10, 'tmp_name' => '/tmp/x']), 'นามสกุล .php → null');

// ขนาดเกิน 8MB
T::eq(null, $try(['name' => 'a.pdf', 'error' => UPLOAD_ERR_OK, 'size' => 9 * 1024 * 1024, 'tmp_name' => '/tmp/x']), 'ขนาด > 8MB → null');

// ขนาด 0
T::eq(null, $try(['name' => 'a.pdf', 'error' => UPLOAD_ERR_OK, 'size' => 0, 'tmp_name' => '/tmp/x']), 'ขนาด 0 → null');

$_FILES = [];
// หมายเหตุ: เส้นทางสำเร็จ (move_uploaded_file) ทดสอบได้เฉพาะผ่าน HTTP จริง — ครอบคลุมใน integration/e2e
