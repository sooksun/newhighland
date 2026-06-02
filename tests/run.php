<?php
/** Test runner — autoload App\ + รันทุกไฟล์ tests/unit และ tests/integration */
require __DIR__ . '/lib.php';

$root = dirname(__DIR__);
spl_autoload_register(function (string $class) use ($root) {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

$only = $argv[1] ?? '';   // เช่น: php tests/run.php unit  (รันเฉพาะ unit)

if ($only === '' || $only === 'unit') {
    foreach (glob(__DIR__ . '/unit/*.php') as $f) require $f;
}
if ($only === '' || $only === 'integration') {
    foreach (glob(__DIR__ . '/integration/*.php') as $f) require $f;
}

T::summary();
