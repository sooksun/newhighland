<?php
use App\Core\Router;

T::group('Router — การจับคู่เส้นทาง (pattern matching)');

$router = new Router();
$m = new ReflectionMethod(Router::class, 'match');
$m->setAccessible(true);
$match = fn(string $pattern, string $path) => $m->invoke($router, $pattern, $path);

// exact match → คืน array ว่าง
T::eq([], $match('highland/eval', 'highland/eval'), 'exact match → []');

// ไม่ตรง และไม่มี param → null
T::eq(null, $match('highland/eval', 'island/eval'), 'ไม่ตรง → null');
T::eq(null, $match('a/b/c', 'a/b'), 'ความยาว path ต่าง → null');

// param เดียว
T::eq(['id' => '5'], $match('highland/edit/{id}', 'highland/edit/5'), 'param {id}=5');
T::eq(['id' => '1063160141'], $match('school/{id}', 'school/1063160141'), 'param sc_id ยาว');

// param ไม่ครบ → null
T::eq(null, $match('highland/edit/{id}', 'highland/edit'), 'ขาด segment param → null');

// หลาย param
T::eq(['a' => 'x', 'b' => 'y'], $match('p/{a}/{b}', 'p/x/y'), 'สอง param');

// param ต้องไม่ข้าม '/'
T::eq(null, $match('p/{a}', 'p/x/y'), 'param ไม่กิน slot เกิน → null');

// หน้าแรก (path ว่าง)
T::eq([], $match('', ''), 'root path ว่าง → match');
