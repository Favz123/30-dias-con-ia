<?php
declare(strict_types=1);
// Recibe UNA prenda (foto + datos) desde la carga masiva y responde en JSON.
// app/ va junto a public_html; si se subió dentro de public_html, también funciona.
require is_file(__DIR__ . '/../../app/bootstrap.php') ? __DIR__ . '/../../app/bootstrap.php' : __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $estado, array $datos): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido.']);
}
if (!usuario_actual()) {
    responder(401, ['ok' => false, 'error' => 'La sesión se cerró. Vuelve a entrar al panel.']);
}
$enviado = (string) ($_POST['csrf'] ?? '');
if ($enviado === '' || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
    responder(400, ['ok' => false, 'error' => 'La sesión expiró. Recarga la página.']);
}

$f = leer_campos_prenda($_POST);
$errores = validar_prenda($f);
$archivo = $_FILES['foto'] ?? null;
[, $erroresFoto] = validar_archivo_foto($archivo, true);
$errores = array_merge($errores, $erroresFoto);
if ($errores) {
    responder(422, ['ok' => false, 'error' => implode(' ', $errores)]);
}

try {
    $codigo = crear_prenda($f, $archivo['tmp_name']);
    $_SESSION['ultima_talla'] = $f['talla'];
    $_SESSION['ultima_marca'] = $f['marca'];
    responder(200, ['ok' => true, 'codigo' => $codigo]);
} catch (PDOException $ex) {
    error_log('Closet (carga masiva): ' . $ex->getMessage());
    responder(500, ['ok' => false, 'error' => 'No se pudo guardar. Intenta de nuevo.']);
} catch (RuntimeException $ex) {
    responder(422, ['ok' => false, 'error' => $ex->getMessage()]);
}
