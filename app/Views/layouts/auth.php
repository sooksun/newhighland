<?php /** @var string $content; @var string $title */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
    <title><?= View::e($title ?? 'เข้าสู่ระบบ') ?> | <?= View::e(App::config('app_name')) ?></title>
    <link rel="icon" type="image/png" href="<?= App::url('images/logo.png') ?>">
    <script>(function(){try{var t=localStorage.getItem('nh-theme')||'light';document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= App::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php $flashes = Flash::pull(); if ($flashes): ?>
<div style="position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:60;width:min(420px,92vw);">
  <?php foreach ($flashes as $f): $type = View::e($f['type']); ?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-start gap-2 shadow" role="alert">
      <?= nh_icon($f['type'] === 'success' ? 'checkCircle' : ($f['type'] === 'danger' ? 'alertCircle' : 'info'), 20) ?>
      <div class="grow"><?= View::e($f['message']) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="app-root">
  <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
