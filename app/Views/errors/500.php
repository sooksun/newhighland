<?php
/**
 * หน้าแสดงข้อผิดพลาดระบบ (500) — แบบ standalone ไม่พึ่ง layout/DB
 * ถูก include โดยตรงจาก App::handleException() / App::handleShutdown()
 * จึงต้องไม่เรียกฐานข้อมูลหรือ service ใด ๆ (เผื่อกรณี DB ล่ม)
 */
$base = \App\Core\App::basePath();
?><!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เกิดข้อผิดพลาดของระบบ</title>
  <style>
    body{margin:0;font-family:'Sarabun',system-ui,-apple-system,'Segoe UI',sans-serif;background:#f1f5f9;color:#1e293b;
      display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px;}
    .box{background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(15,23,42,.08);max-width:520px;width:100%;
      padding:40px 32px;text-align:center;}
    .code{font-size:4rem;font-weight:800;color:#dc2626;line-height:1;margin-bottom:8px;}
    h1{font-size:1.4rem;margin:0 0 8px;}
    p{color:#64748b;margin:0 0 24px;line-height:1.6;}
    a{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 24px;border-radius:10px;font-weight:600;}
    a:hover{background:#1d4ed8;}
  </style>
</head>
<body>
  <div class="box">
    <div class="code">500</div>
    <h1>เกิดข้อผิดพลาดของระบบ</h1>
    <p>ขออภัย ระบบไม่สามารถดำเนินการตามคำขอได้ในขณะนี้<br>
       ข้อมูลที่ท่านกรอกอาจยังไม่ถูกบันทึก กรุณาลองใหม่อีกครั้ง<br>
       หากยังพบปัญหา โปรดติดต่อผู้ดูแลระบบ</p>
    <a href="<?= htmlspecialchars($base . '/dashboard', ENT_QUOTES, 'UTF-8') ?>">กลับหน้าหลัก</a>
  </div>
</body>
</html>
