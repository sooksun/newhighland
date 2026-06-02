<?php /** @var string $content; @var string $title */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'หน้าแรก') ?> | <?= View::e(App::config('app_name')) ?></title>
    <link rel="icon" type="image/png" href="<?= App::url('images/logo.png') ?>">
    <script>(function(){try{var t=localStorage.getItem('nh-theme')||'light';document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= App::asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-root">
  <?= $content ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var ICON_MOON = '<?= addslashes(nh_icon('moon', 19)) ?>';
  var ICON_SUN  = '<?= addslashes(nh_icon('sun', 19)) ?>';
  var root = document.documentElement, btn = document.getElementById('themeToggle');
  function paint(t){ if(btn) btn.innerHTML = (t === 'dark' ? ICON_SUN : ICON_MOON); }
  paint(root.getAttribute('data-theme') || 'light');
  if (btn) btn.addEventListener('click', function () {
    var t = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    root.setAttribute('data-theme', t); root.setAttribute('data-bs-theme', t);
    try { localStorage.setItem('nh-theme', t); } catch (e) {}
    paint(t);
  });
})();
</script>
</body>
</html>
