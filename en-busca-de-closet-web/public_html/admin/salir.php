<?php
declare(strict_types=1);
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('/admin/');
}
verificar_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();
redirigir('/admin/entrar.php');
