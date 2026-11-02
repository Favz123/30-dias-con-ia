<?php
// Sólo para pruebas locales: imita las reglas de .htaccess con el servidor de PHP.
//   php -S localhost:8000 -t public_html tools/router.php
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$raiz = realpath(__DIR__ . '/../public_html');

if (preg_match('#^/talla/(extra-chica|chica|mediana|grande|extragrande)/?$#', $ruta, $m)) {
    $_GET['talla'] = $m[1];
    require $raiz . '/index.php';
    return true;
}
if (preg_match('#^/prenda/([A-Za-z]{1,3}-[0-9]+)/?$#', $ruta, $m)) {
    $_GET['c'] = $m[1];
    require $raiz . '/prenda.php';
    return true;
}
if ($ruta === '/sitemap.xml') {
    require $raiz . '/sitemap.php';
    return true;
}
if ($ruta === '/admin' ) {
    header('Location: /admin/');
    return true;
}
$archivo = $raiz . $ruta;
if (is_dir($archivo)) {
    $archivo = rtrim($archivo, '/') . '/index.php';
}
if (is_file($archivo)) {
    if (str_ends_with($archivo, '.php')) {
        chdir(dirname($archivo));
        require $archivo;
        return true;
    }
    return false; // archivos estáticos
}
http_response_code(404);
require $raiz . '/prenda.php';
return true;
